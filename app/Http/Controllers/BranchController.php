<?php
// app/Http/Controllers/BranchController.php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\DeliveryArea;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BranchController extends Controller
{

    public function index()
{
    $branches = Branch::active()->get();

    return response()->json([
        'success' => true,
        'data' => $branches
    ]);
}


    public function store(Request $request)
    {
        if (!Auth::user()->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح بهذا الإجراء'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'address' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:100',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'delivery_radius_km' => 'required|numeric|min:1',
            'delivery_fee_base' => 'required|numeric|min:0',
            'opening_hours' => 'required|array',
            'is_active' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $branch = Branch::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الفرع بنجاح',
            'data' => $branch
        ], 201);
    }


    public function show(Branch $branch)
    {
        $branch->load(['deliveryAreas' => function($query) {
            $query->active();
        }]);

        return response()->json([
            'success' => true,
            'data' => $branch
        ]);
    }

    public function update(Request $request, Branch $branch)
    {
        if (!Auth::user()->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح بهذا الإجراء'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:100',
            'address' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'email' => 'nullable|email|max:100',
            'latitude' => 'sometimes|numeric',
            'longitude' => 'sometimes|numeric',
            'delivery_radius_km' => 'sometimes|numeric|min:1',
            'delivery_fee_base' => 'sometimes|numeric|min:0',
            'opening_hours' => 'sometimes|array',
            'is_active' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $branch->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الفرع بنجاح',
            'data' => $branch
        ]);
    }

    public function destroy(Branch $branch)
    {
        if (!Auth::user()->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح بهذا الإجراء'
            ], 403);
        }

        if ($branch->orders()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف الفرع لأنه مرتبط بطلبات'
            ], 400);
        }

        $branch->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الفرع بنجاح'
        ]);
    }

    public function addDeliveryArea(Request $request, Branch $branch)
    {
        if (!Auth::user()->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح بهذا الإجراء'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'area_name' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'district' => 'nullable|string|max:100',
            'delivery_fee' => 'required|numeric|min:0',
            'min_order_amount' => 'required|numeric|min:0',
            'estimated_delivery_time' => 'required|integer|min:15',
            'polygon_coordinates' => 'nullable|array',
            'is_active' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $deliveryArea = $branch->deliveryAreas()->create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة منطقة التوصيل بنجاح',
            'data' => $deliveryArea
        ], 201);
    }


    public function updateDeliveryArea(Request $request, DeliveryArea $deliveryArea)
    {
        if (!Auth::user()->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح بهذا الإجراء'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'area_name' => 'sometimes|string|max:100',
            'city' => 'sometimes|string|max:100',
            'district' => 'nullable|string|max:100',
            'delivery_fee' => 'sometimes|numeric|min:0',
            'min_order_amount' => 'sometimes|numeric|min:0',
            'estimated_delivery_time' => 'sometimes|integer|min:15',
            'polygon_coordinates' => 'nullable|array',
            'is_active' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $deliveryArea->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث منطقة التوصيل بنجاح',
            'data' => $deliveryArea
        ]);
    }

    public function deleteDeliveryArea(DeliveryArea $deliveryArea)
    {
        if (!Auth::user()->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح بهذا الإجراء'
            ], 403);
        }

        $deliveryArea->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف منطقة التوصيل بنجاح'
        ]);
    }


    public function getBranchDeliveryAreas(Branch $branch)
    {
        $deliveryAreas = $branch->deliveryAreas()
            ->active()
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'branch' => $branch,
                'delivery_areas' => $deliveryAreas
            ]
        ]);
    }

    public function findBranchesForLocation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required_with:longitude|numeric',
            'longitude' => 'required_with:latitude|numeric',
            'city' => 'required_without:latitude|string',
            'district' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $branches = Branch::active()
            ->with(['deliveryAreas' => function($query) use ($request) {
                $query->active();

                if ($request->city) {
                    $query->where('city', $request->city);
                }

                if ($request->district) {
                    $query->where('district', $request->district);
                }
            }])
            ->get()
            ->filter(function ($branch) use ($request) {
                if ($request->latitude && $request->longitude) {
                    $distance = $branch->calculateDistance(
                        $request->latitude,
                        $request->longitude
                    );
                    $branch->distance = round($distance, 2);
                    $branch->delivery_available = $distance <= $branch->delivery_radius_km;

                    return $branch->delivery_available;
                }

                return $branch->deliveryAreas->isNotEmpty();
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $branches
        ]);
    }
}
