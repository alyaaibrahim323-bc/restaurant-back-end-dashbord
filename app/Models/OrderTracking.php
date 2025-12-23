<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderTracking extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'status',
        'notes'
    ];

    // علاقة الطلب
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
