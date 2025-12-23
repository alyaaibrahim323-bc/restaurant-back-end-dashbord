<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'sku',
        'price',
        'stock',
        'image'
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function optionValues(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductOptionValue::class,
            'variant_option_values',
            'variant_id',
            'option_value_id'
        );
    }

    public function getStockStatusAttribute()
    {
        if ($this->stock <= 0) return 'out_of_stock';
        if ($this->stock <= 5) return 'low_stock';
        return 'in_stock';
    }
public function getOptionValuesSummaryAttribute()
{
    return $this->optionValues
        ->load('option')
        ->map(fn ($ov) => $ov->option->name.': '.$ov->value)
        ->join(', ');
}
}
