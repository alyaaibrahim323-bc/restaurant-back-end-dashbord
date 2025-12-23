<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'guest_uuid',
        'total',
        'delivery_fee',
        'status',
        'address_id',
        'payment_id',
        'branch_id',
        'delivery_area_id',
        'subtotal',
        'estimated_delivery_time',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'in process';
    const STATUS_SHIPPED = 'shipped';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_CANCELLED = 'cancelled';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function address()
    {
        return $this->belongsTo(Address::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function trackingHistory()
    {
        return $this->hasMany(OrderTracking::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class, 'payment_id'); // ⬅️ استخدام Payment بحرف كبير
    }

    public function tracking()
    {
        return $this->hasMany(OrderTracking::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }


    public function hasProduct($productId)
    {
        return $this->orderItems()->where('product_id', $productId)->exists();
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }


    public function deliveryArea()
    {
        return $this->belongsTo(DeliveryArea::class);
    }

    public function appliedOffer()
    {
        return $this->belongsTo(Offer::class, 'applied_offer_id');
    }


    public function getFinalTotalAttribute()
    {
        return ($this->subtotal + $this->shipping_cost) - $this->discount_amount;
    }

    public function updateDeliveryInfo(DeliveryArea $deliveryArea)
    {
        $this->update([
            'delivery_area_id' => $deliveryArea->id,
            'delivery_fee' => $deliveryArea->delivery_fee,
            'estimated_delivery_time' => $deliveryArea->estimated_delivery_time,
            'branch_id' => $deliveryArea->branch_id
        ]);
    }


    public function getDeliveryInfoAttribute()
    {
        if ($this->deliveryArea) {
            return [
                'area_name' => $this->deliveryArea->area_name,
                'delivery_fee' => $this->deliveryArea->delivery_fee,
                'estimated_time' => $this->deliveryArea->estimated_delivery_time,
                'branch' => $this->deliveryArea->branch->name ?? null
            ];
        }

        return [
            'area_name' => $this->address->city ?? 'غير محدد',
            'delivery_fee' => $this->shipping_cost,
            'estimated_time' => '2-3 أيام',
            'branch' => 'الفرع الرئيسي'
        ];
    }
}
