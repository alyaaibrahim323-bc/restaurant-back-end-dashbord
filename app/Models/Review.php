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

    /**
     * العلاقة مع المستخدم
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * العلاقة مع المنتج
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * العلاقة مع الطلب
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * نطاق للاستعلام عن التقييمات المعتمدة فقط
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    /**
     * نطاق للاستعلام عن التقييمات الموثقة فقط (من عملاء حقيقيين)
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * نطاق للاستعلام عن التقييمات حسب التصنيف
     */
    public function scopeRating($query, $rating)
    {
        return $query->where('rating', $rating);
    }

    /**
     * التحقق من أن المستخدم قد طلب هذا المنتج بالفعل
     */
    public static function userHasOrderedProduct($userId, $productId)
    {
        return Order::where('user_id', $userId)
            ->whereHas('orderItems', function($query) use ($productId) {
                $query->where('product_id', $productId);
            })
            ->where('status', 'delivered') // فقط الطلبات المسلمة
            ->exists();
    }

    /**
     * التحقق من إمكانية المستخدم لإضافة تقييم
     */
    public static function canUserReview($userId, $productId)
{
    // التحقق فقط إذا كان المستخدم قد قيم المنتج مسبقاً
    $hasReviewed = self::where('user_id', $userId)
        ->where('product_id', $productId)
        ->exists();

    return !$hasReviewed;
}
}
