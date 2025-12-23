<?php
namespace App\Services;

use App\Models\DeliveryArea;
use App\Models\Address;
use App\Models\Branch;
use Illuminate\Support\Facades\Log;

class DeliveryService
{
    /**
     * العثور على مناطق التوصيل المناسبة لعنوان معين
     */
    public function findDeliveryAreasForAddress(Address $address, $branchId = null)
    {
        try {
            $query = DeliveryArea::with('branch')
                ->active()
                ->when($branchId, function($q) use ($branchId) {
                    $q->where('branch_id', $branchId);
                });

            // إذا كانت هناك إحداثيات، البحث بالإحداثيات أولاً
            if ($address->latitude && $address->longitude) {
                $areas = $query->get()
                    ->filter(function ($area) use ($address) {
                        if (!empty($area->polygon_coordinates)) {
                            return $area->containsLocation($address->latitude, $address->longitude);
                        }
                        return $this->matchesByCityAndDistrict($area, $address);
                    })
                    ->sortBy('delivery_fee')
                    ->values();
            } else {
                // البحث بالمدينة والمنطقة فقط
                $areas = $query->where(function($q) use ($address) {
                    $q->where('city', 'like', "%{$address->city}%")
                      ->orWhere('district', 'like', "%{$address->state}%");
                })->get()->sortBy('delivery_fee')->values();
            }

            return $areas;

        } catch (\Exception $e) {
            Log::error('Delivery area search failed: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * مطابقة المدينة والمنطقة
     */
    private function matchesByCityAndDistrict(DeliveryArea $area, Address $address)
    {
        $cityMatch = empty($area->city) ||
                    strtolower($area->city) === strtolower($address->city);

        $districtMatch = empty($area->district) ||
                        strtolower($area->district) === strtolower($address->state);

        return $cityMatch && $districtMatch;
    }

    /**
     * التحقق من إمكانية التوصيل للطلب
     */
    public function validateOrderDelivery(Address $address, $orderAmount, $branchId = null)
    {
        $deliveryAreas = $this->findDeliveryAreasForAddress($address, $branchId);

        if ($deliveryAreas->isEmpty()) {
            return [
                'success' => false,
                'message' => 'التوصيل غير متاح لهذا الموقع'
            ];
        }

        $area = $deliveryAreas->first();

        if (!$area->meetsMinimumOrder($orderAmount)) {
            return [
                'success' => false,
                'message' => 'الحد الأدنى للطلب في هذه المنطقة هو ' . $area->min_order_amount . ' جنيه',
                'min_order_amount' => $area->min_order_amount
            ];
        }

        return [
            'success' => true,
            'delivery_area' => $area,
            'delivery_fee' => $area->delivery_fee,
            'estimated_delivery_time' => $area->estimated_delivery_time,
            'total_amount' => $orderAmount + $area->delivery_fee
        ];
    }

    /**
     * حساب أفضل خيار توصيل متاح
     */
    public function getBestDeliveryOption($latitude, $longitude, $city, $district, $orderAmount, $branchId = null)
    {
        try {
            $query = DeliveryArea::with('branch')
                ->active()
                ->when($branchId, function($q) use ($branchId) {
                    $q->where('branch_id', $branchId);
                });

            $areas = $query->get()
                ->filter(function ($area) use ($latitude, $longitude, $city, $district) {
                    if ($latitude && $longitude && !empty($area->polygon_coordinates)) {
                        return $area->containsLocation($latitude, $longitude);
                    }

                    // Fallback to city/district matching
                    $cityMatch = empty($area->city) || str_contains(strtolower($area->city), strtolower($city));
                    $districtMatch = empty($area->district) || str_contains(strtolower($area->district), strtolower($district));

                    return $cityMatch && $districtMatch;
                })
                ->sortBy('delivery_fee')
                ->values();

            if ($areas->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'لا توجد خيارات توصيل متاحة'
                ];
            }

            $bestArea = $areas->first();

            // التحقق من الحد الأدنى للطلب
            if (!$bestArea->meetsMinimumOrder($orderAmount)) {
                return [
                    'success' => false,
                    'message' => 'الحد الأدنى للطلب في هذه المنطقة هو ' . $bestArea->min_order_amount . ' جنيه',
                    'min_order_amount' => $bestArea->min_order_amount
                ];
            }

            return [
                'success' => true,
                'delivery_area' => $bestArea,
                'delivery_fee' => $bestArea->delivery_fee,
                'estimated_delivery_time' => $bestArea->estimated_delivery_time,
                'alternative_options' => $areas->slice(1)->values(),
                'total_options' => $areas->count()
            ];

        } catch (\Exception $e) {
            Log::error('Best delivery option calculation failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'فشل في حساب خيارات التوصيل'
            ];
        }
    }
}
