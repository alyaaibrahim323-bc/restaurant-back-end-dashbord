<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryArea extends Model
{
    protected $fillable = ['area_name', 'delivery_fee', 'min_order_amount', 'estimated_delivery_time', 'branch_id', 'is_active', 'polygon_coordinates'];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    // Scope للـ active
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope للبحث بالموقع
    public function scopeForLocation($query, $lat, $lng, $city = null, $district = null)
    {
        // ابحث بالمدينة/المنطقة أولاً
        if ($city) $query->where('city', 'like', "%$city%");
        if ($district) $query->where('district', 'like', "%$district%");
        // أو ابحث بالإحداثيات (simple radius search)
        $radius = 10; // كم
        $query->whereRaw("6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))) < ?", [$lat, $lng, $lat, $radius]);
        return $query;
    }

    // Method للتحقق من الحد الأدنى
    public function meetsMinimumOrder($orderAmount)
    {
        return $orderAmount >= ($this->min_order_amount ?? 0);
    }

    // Method للتحقق إذا كان الموقع داخل الـ polygon (simple implementation)
    public function containsLocation($lat, $lng)
    {
        // استخدم library زي geophp أو simple point-in-polygon algorithm
        // هنا مثال بسيط (افترض إن polygon_coordinates JSON array of points)
        if (empty($this->polygon_coordinates)) return true;
        $points = json_decode($this->polygon_coordinates, true);
        // Implement ray-casting algorithm هنا (ابحث عن PHP point in polygon)
        return false; // placeholder
    }
}
