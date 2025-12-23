<?php
namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\DeliveryArea;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class DeliveryController extends Controller
{

    public function findDeliveryArea(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'area_name' => 'required_without_all:latitude,longitude|string|max:255',
            'latitude' => 'required_with:longitude|numeric|between:-90,90',
            'longitude' => 'required_with:latitude|numeric|between:-180,180',
            'city' => 'sometimes|string|max:100',
            'district' => 'sometimes|string|max:100',
            'branch_id' => 'sometimes|exists:branches,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صالحة',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = DeliveryArea::with('branch')->active();

        if ($request->has('area_name') && !empty($request->area_name)) {
            $nameAreas = $query->clone()
                ->where('area_name', 'like', "%{$request->area_name}%")
                ->when($request->branch_id, function($q) use ($request) {
                    $q->where('branch_id', $request->branch_id);
                })
                ->get();

            if ($nameAreas->isNotEmpty()) {
                return response()->json([
                    'success' => true,
                    'data' => $nameAreas,
                    'search_type' => 'by_name',
                    'count' => $nameAreas->count()
                ]);
            }
        }

        if ($request->latitude && $request->longitude) {
            $coordinateAreas = $this->findAreasByCoordinates(
                $request->latitude,
                $request->longitude,
                $request->city,
                $request->district,
                $request->branch_id
            );

            if ($coordinateAreas->isNotEmpty()) {
                return response()->json([
                    'success' => true,
                    'data' => $coordinateAreas,
                    'search_type' => 'by_coordinates',
                    'count' => $coordinateAreas->count()
                ]);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'لم يتم العثور على مناطق توصيل تناسب البحث'
        ], 404);
    }


    public function getBranchAreas($branchId)
    {
        $branch = Branch::with(['deliveryAreas' => function($query) {
            $query->active()->orderBy('delivery_fee');
        }])->find($branchId);

        if (!$branch) {
            return response()->json([
                'success' => false,
                'message' => 'الفرع غير موجود'
            ], 404);
        }

        $groupedAreas = $branch->deliveryAreas->groupBy('delivery_fee');

        return response()->json([
            'success' => true,
            'data' => [
                'branch' => $branch,
                'areas_by_fee' => $groupedAreas
            ]
        ]);
    }


    public function getAreasByFee(Request $request, $branchId)
    {
        $validator = Validator::make($request->all(), [
            'delivery_fee' => 'required|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $areas = DeliveryArea::with('branch')
            ->where('branch_id', $branchId)
            ->where('delivery_fee', $request->delivery_fee)
            ->active()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $areas
        ]);
    }

    public function calculateDeliveryFee(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'area_name' => 'required|string|max:255',
            'order_amount' => 'required|numeric|min:0',
            'branch_id' => 'sometimes|exists:branches,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $query = DeliveryArea::with('branch')
            ->active()
            ->where('area_name', 'like', "%{$request->area_name}%");

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        $area = $query->first();

        if (!$area) {
            return response()->json([
                'success' => false,
                'message' => 'المنطقة غير موجودة'
            ], 404);
        }

        if (!$area->meetsMinimumOrder($request->order_amount)) {
            return response()->json([
                'success' => false,
                'message' => 'الحد الأدنى للطلب في هذه المنطقة هو ' . $area->min_order_amount . ' جنيه',
                'min_order_amount' => $area->min_order_amount,
                'current_order_amount' => $request->order_amount
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'delivery_fee' => $area->delivery_fee,
                'estimated_delivery_time' => $area->estimated_delivery_time,
                'area' => $area,
                'branch' => $area->branch,
                'total_amount' => $request->order_amount + $area->delivery_fee
            ]
        ]);
    }

    public function getAllBranchesWithAreas()
    {
        $branches = Branch::active()
            ->with(['deliveryAreas' => function($query) {
                $query->active()->orderBy('delivery_fee');
            }])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $branches
        ]);
    }


    public function checkAddressDeliverability(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'address_id' => 'required_without:coordinates|exists:addresses,id',
            'latitude' => 'required_with:longitude|numeric',
            'longitude' => 'required_with:latitude|numeric',
            'order_amount' => 'required|numeric|min:0',
            'branch_id' => 'sometimes|exists:branches,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        if ($request->address_id) {
            $address = Address::find($request->address_id);
            if (!$address) {
                return response()->json([
                    'success' => false,
                    'message' => 'العنوان غير موجود'
                ], 404);
            }


            if (Auth::check() && $address->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح بالوصول لهذا العنوان'
                ], 403);
            }

            $latitude = $address->latitude;
            $longitude = $address->longitude;
            $city = $address->city;
            $district = $address->state;
        } else {
            $latitude = $request->latitude;
            $longitude = $request->longitude;
            $city = $request->city;
            $district = $request->district;
        }

        $areas = $this->findAreasByCoordinates(
            $latitude,
            $longitude,
            $city,
            $district,
            $request->branch_id
        );

        if ($areas->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'التوصيل غير متاح لهذا الموقع',
                'delivery_available' => false
            ], 404);
        }

        $area = $areas->first();

        if (!$area->meetsMinimumOrder($request->order_amount)) {
            return response()->json([
                'success' => false,
                'message' => 'الحد الأدنى للطلب في هذه المنطقة هو ' . $area->min_order_amount . ' جنيه',
                'min_order_amount' => $area->min_order_amount,
                'current_order_amount' => $request->order_amount,
                'delivery_available' => false
            ], 400);
        }

        return response()->json([
            'success' => true,
            'delivery_available' => true,
            'data' => [
                'delivery_fee' => $area->delivery_fee,
                'estimated_delivery_time' => $area->estimated_delivery_time,
                'area' => $area,
                'branch' => $area->branch,
                'total_amount' => $request->order_amount + $area->delivery_fee
            ]
        ]);
    }


    public function getUserAddressesDeliveryInfo()
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'يجب تسجيل الدخول'
            ], 401);
        }

        $user = Auth::user();
        $addresses = $user->addresses()->get();

        $addressesWithDeliveryInfo = $addresses->map(function ($address) {
            $deliveryAreas = $this->findAreasByCoordinates(
                $address->latitude,
                $address->longitude,
                $address->city,
                $address->state
            );

            $bestArea = $deliveryAreas->sortBy('delivery_fee')->first();

            return [
                'address' => $address,
                'available_delivery_areas' => $deliveryAreas,
                'best_delivery_option' => $bestArea,
                'is_deliverable' => $deliveryAreas->isNotEmpty(),
                'delivery_fee' => $bestArea ? $bestArea->delivery_fee : null,
                'estimated_delivery_time' => $bestArea ? $bestArea->estimated_delivery_time : null
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $addressesWithDeliveryInfo
        ]);
    }


    private function findAreasByCoordinates($lat, $lng, $city = null, $district = null, $branchId = null)
    {
        return DeliveryArea::with('branch')
            ->active()
            ->when($branchId, function($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })
            ->forLocation($lat, $lng, $city, $district)
            ->get()
            ->filter(function ($area) use ($lat, $lng) {
                if (!empty($area->polygon_coordinates)) {
                    return $area->containsLocation($lat, $lng);
                }
                return true;
            })
            ->sortBy('delivery_fee')
            ->values();
    }


    public function calculateShippingForAddress(Address $address, $orderAmount, $branchId = null)
    {
        $areas = $this->findAreasByCoordinates(
            $address->latitude,
            $address->longitude,
            $address->city,
            $address->state,
            $branchId
        );

        if ($areas->isEmpty()) {
            return [
                'success' => false,
                'message' => 'التوصيل غير متاح لهذا العنوان',
                'delivery_fee' => null
            ];
        }

        $area = $areas->first();

        if (!$area->meetsMinimumOrder($orderAmount)) {
            return [
                'success' => false,
                'message' => 'الحد الأدنى للطلب في هذه المنطقة هو ' . $area->min_order_amount . ' جنيه',
                'min_order_amount' => $area->min_order_amount,
                'delivery_fee' => null
            ];
        }

        return [
            'success' => true,
            'delivery_fee' => $area->delivery_fee,
            'area' => $area,
            'estimated_delivery_time' => $area->estimated_delivery_time
        ];
    }
}
