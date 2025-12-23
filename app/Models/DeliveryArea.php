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


    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForLocation($query, $lat, $lng, $city = null, $district = null)
    {

        if ($city) $query->where('city', 'like', "%$city%");
        if ($district) $query->where('district', 'like', "%$district%");

        $radius = 10;
        $query->whereRaw("6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))) < ?", [$lat, $lng, $lat, $radius]);
        return $query;
    }


    public function meetsMinimumOrder($orderAmount)
    {
        return $orderAmount >= ($this->min_order_amount ?? 0);
    }


    public function containsLocation($lat, $lng)
    {

        if (empty($this->polygon_coordinates)) return true;
        $points = json_decode($this->polygon_coordinates, true);
        return false;
    }
}
