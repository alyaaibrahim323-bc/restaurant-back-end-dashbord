<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
'image' => $this->image
    ? (str_starts_with($this->image, 'http') ? $this->image : asset('storage/' . $this->image))
    : null,

'images' => is_array($this->images)
    ? collect($this->images)->map(fn($img) => str_starts_with($img, 'http') ? $img : asset('storage/' . $img))
    : [],

            'slug' => $this->slug,
            'description' => strip_tags($this->description),
            'description_ar' => strip_tags($this->description_ar),

            'base_price' => $this->price,
            'min_price' => $this->discount_price,
            'max_price' => $this->max_price,
            'is_active' => $this->is_active,
            'views' => $this->views,
            'category' => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug
            ],
            'options' => $this->options->map(function ($option) {
                return [
                    'id' => $option->id,
                    'name' => $option->name,
                    'name_ar' => $option->name_ar,

                    'required' => $option->required,
                    'values' => $option->values->map(function ($value) {
                        return [
                            'id' => $value->id,
                            'value' => $value->value,
                            'value_ar'=>$value->value_ar,
                            'price_modifier' => $value->price_modifier,
                            'image' => $value->image // ⬅️ هنا كان الخطأ
                    ? (str_starts_with($value->image, 'http') 
                        ? $value->image 
                        : asset('storage/' . $value->image))
                    : null,

                        ];
                    })
                ];
            }),
            'variants' => $this->variants->map(function ($variant) {
                return [
                    'id' => $variant->id,
                    'sku' => $variant->sku,
                    'price' => $variant->price,
                    'stock' => $variant->stock,
                    'stock_status' => $variant->stock_status,
                    'image' => $variant->image,
                    'options' => $variant->optionValues->map(function ($value) {
                        return $value->value;
                    })
                ];
            })

        ];
    }
}
