<?php
// app/Models/Branch.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
        'latitude',
        'longitude',
        'is_active',
        'delivery_radius_km',
        'delivery_fee_base',
        'opening_hours'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'delivery_radius_km' => 'decimal:2',
        'delivery_fee_base' => 'decimal:2',
        'opening_hours' => 'array'
    ];

    /**
     * العلاقة مع مناطق التوصيل
     */
    public function deliveryAreas()
    {
        return $this->hasMany(DeliveryArea::class);
    }

    /**
     * العلاقة مع الطلبات
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * الحصول على الفروع النشطة
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * البحث عن الفروع ضمن نطاق معين
     */
    public function scopeWithinRadius($query, $lat, $lng, $radius = 50)
    {
        return $query->whereRaw("
            (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) <= ?
        ", [$lat, $lng, $lat, $radius]);
    }

    /**
     * حساب المسافة بين الفرع وموقع العميل
     */
    public function calculateDistance($customerLat, $customerLng)
    {
        if (!$this->latitude || !$this->longitude) {
            return null;
        }

        $earthRadius = 6371; // كيلومتر

        $latDiff = deg2rad($customerLat - $this->latitude);
        $lngDiff = deg2rad($customerLng - $this->longitude);

        $a = sin($latDiff / 2) * sin($latDiff / 2) +
             cos(deg2rad($this->latitude)) * cos(deg2rad($customerLat)) *
             sin($lngDiff / 2) * sin($lngDiff / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * التحقق إذا كان الموقع ضمن نطاق التوصيل
     */
    public function isWithinDeliveryRange($lat, $lng)
    {
        $distance = $this->calculateDistance($lat, $lng);
        return $distance !== null && $distance <= $this->delivery_radius_km;
    }

    /**
     * الحصول على المناطق النشطة
     */
    public function getActiveAreas()
    {
        return $this->deliveryAreas()->active()->get();
    }
}
