<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'title_ar',
        'description',
        'description_ar',
        'promo_code',
        'image',
        'color',
        'discount_type',
        'discount_value',
        'is_active',
        'valid_until',
        'usage_limit',
        'used_count'
    ];
    
    protected $attributes = [
        'discount_value' => 0, // ⭐ قيمة افتراضية
        'is_active' => true,
        'used_count' => 0
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'valid_until' => 'date',
        'discount_value' => 'decimal:2',
        'usage_limit' => 'integer',
        'used_count' => 'integer'
    ];

    /**
     * العلاقة مع المستخدمين الذين استخدموا العرض
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_offers')
                    ->withPivot('used_at', 'order_id', 'discount_amount')
                    ->withTimestamps();
    }

    /**
     * التحقق من أن العرض متاح للاستخدام
     */
    public function getIsAvailableAttribute()
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->valid_until && $this->valid_until->isPast()) {
            return false;
        }

        if ($this->usage_limit && $this->used_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    /**
     * زيادة عداد الاستخدام
     */
    public function incrementUsage()
    {
        $this->increment('used_count');
    }

    /**
     * الحصول على العروض النشطة فقط
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where(function($q) {
                        $q->whereNull('valid_until')
                          ->orWhere('valid_until', '>=', now());
                    })
                    ->where(function($q) {
                        $q->whereNull('usage_limit')
                          ->orWhereRaw('used_count < usage_limit');
                    });
    }

    /**
     * البحث بالبرومو كود
     */
    public function scopeByPromoCode($query, $promoCode)
    {
        return $query->where('promo_code', $promoCode);
    }

    /**
     * الحصول على URL الصورة
     */
    public function getImageUrlAttribute()
    {
        if ($this->image) {
            return asset('storage/' . $this->image);
        }
        
        return asset('images/default-offer.png');
    }

    /**
     * الحصول على وصف الخصم
     */
   public function getDiscountDescriptionAttribute()
    {
        $discountValue = $this->discount_value ?? 0;
        
        switch ($this->discount_type) {
            case 'percentage':
                return "خصم {$discountValue}%";
            case 'fixed':
                return "خصم {$discountValue} جنيه";
            case 'free_delivery':
                return "توصيل مجاني";
            default:
                return $this->title;
        }
    }

    /**
     * تطبيق الخصم على المبلغ - مع معالجة القيم null
     */
  public function applyDiscount($subtotal, $deliveryFee = 0)
{
    switch ($this->discount_type) {
        case 'percentage':
            return ($this->discount_value / 100) * $subtotal;
            
        case 'fixed':
            return min($this->discount_value, $subtotal);
            
        case 'free_delivery':
            return $deliveryFee;
            
        default:
            return 0;
    }
}
}