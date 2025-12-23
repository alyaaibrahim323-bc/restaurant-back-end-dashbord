<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'street',
        'city',
        'state',
        'country',
        'postal_code',
        'building_number',
        'apartment_number',
        'floor_number',
        'phone_number',
        'is_default',
        'latitude',
        'longitude'
    ];

    /**
     * دالة لإنشاء رابط الموقع الجغرافي تلقائياً
     *
     * @return string|null
     */
    public function getLocationUrlAttribute()
    {
        if ($this->latitude && $this->longitude) {
            return "https://www.google.com/maps/search/?api=1&query={$this->latitude},{$this->longitude}";
        }

        return null;
    }


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function orders() {
        return $this->hasMany(Order::class);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
