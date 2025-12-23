<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'order_id',
        'rating',
        'comment',
        'is_approved'

    ];

    protected $casts = [
        'is_approved' => 'boolean',
        'is_verified' => 'boolean',
        'rating' => 'integer'
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }


    public function order()
    {
        return $this->belongsTo(Order::class);
    }


    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }


    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeRating($query, $rating)
    {
        return $query->where('rating', $rating);
    }


    public static function userHasOrderedProduct($userId, $productId)
    {
        return Order::where('user_id', $userId)
            ->whereHas('orderItems', function($query) use ($productId) {
                $query->where('product_id', $productId);
            })
            ->where('status', 'delivered')
            ->exists();
    }


    public static function canUserReview($userId, $productId)
{
    $hasReviewed = self::where('user_id', $userId)
        ->where('product_id', $productId)
        ->exists();

    return !$hasReviewed;
}
}
