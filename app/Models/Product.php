<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;


class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'description',
        'price',
        'discount_price',
        'category_id',
        'images',
        'is_active',
        'description_ar'
    ];

    protected $casts = [
        'images' => 'array',
        'is_active' => 'boolean',
        'price' => 'float',
    'discount_price' => 'float',
    ];

    protected $appends = [
        'final_price',
        'min_price',
        'max_price',
        'price_range',
        'total_stock',
        'is_available',
        'in_stock'
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(ProductOption::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->with('optionValues');
    }

    public function optionValues(): HasManyThrough
    {
        return $this->hasManyThrough(
            ProductOptionValue::class,
            ProductOption::class,
            'product_id',
            'option_id',
            'id',
            'id'
        );
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getFinalPriceAttribute(): float
    {
        if ($this->variants->isNotEmpty()) {
            return $this->min_price;
        }

        return $this->discount_price ?? $this->price;
    }

    public function getMinPriceAttribute(): float
    {
        $minVariantPrice = $this->variants->min('price');

        if ($minVariantPrice === null) {
            return $this->price;
        }

        return min($minVariantPrice, $this->price);
    }

    public function getMaxPriceAttribute(): float
    {
        $maxVariantPrice = $this->variants->max('price');

        if ($maxVariantPrice === null) {
            return $this->price;
        }

        return max($maxVariantPrice, $this->price);
    }

    public function getPriceRangeAttribute(): string
    {
        if ($this->min_price === $this->max_price) {
            return number_format($this->min_price, 2) . ' ج.م';
        }

        return number_format($this->min_price, 2) . ' - ' . number_format($this->max_price, 2) . ' ج.م';
    }

    public function getTotalStockAttribute()
{
    if ($this->variants()->exists()) {
        return $this->variants->sum('stock');
    }
    return $this->stock;
}

    public function getIsAvailableAttribute(): bool
    {
        return $this->is_active && ($this->total_stock > 0);
    }

    public function getInStockAttribute(): bool
    {
        if ($this->variants->isNotEmpty()) {
            return $this->variants->where('stock', '>', 0)->isNotEmpty();
        }

        return $this->stock > 0;
    }

    protected static function booted()
    {
        static::saved(function ($product) {
        if ($product->variants()->exists()) {
            $product->stock = $product->variants->sum('stock');
            $product->saveQuietly();
        }
    });
        static::creating(function ($product) {
    $product->uuid = Str::uuid();

    if (empty($product->slug)) {
        $product->slug = Str::slug($product->name);
    }

    if ($product->stock === null) {
        $product->stock = 0;
    }
});


        static::updating(function ($product) {
            $product->slug = Str::slug($product->name);
        });

        static::deleting(function ($product) {
            $product->variants()->delete();
            $product->options()->delete();
        });
    }

public function reviews()
{
    return $this->hasMany(Review::class);
}

public function approvedReviews()
{
    return $this->hasMany(Review::class)->where('is_approved', true);
}

public function verifiedReviews()
{
    return $this->hasMany(Review::class)
                ->where('is_approved', true)
                ->where('is_verified', true);
}


public function getAverageRatingAttribute()
{
    return $this->approvedReviews()->avg('rating') ?: 0;
}

public function getReviewsCountAttribute()
{
    return $this->approvedReviews()->count();
}


public function getRatingDistributionAttribute()
{
    $distribution = [];
    for ($i = 1; $i <= 5; $i++) {
        $distribution[$i] = $this->approvedReviews()->where('rating', $i)->count();
    }
    return $distribution;
}

public function getRatingPercentagesAttribute()
{
    $total = $this->reviews_count;
    if ($total === 0) {
        return [];
    }

    $percentages = [];
    foreach ($this->rating_distribution as $rating => $count) {
        $percentages[$rating] = round(($count / $total) * 100, 2);
    }

    return $percentages;
}
}
