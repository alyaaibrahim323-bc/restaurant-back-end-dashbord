<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'quantity' => $this->quantity,
            'price' => $this->price,
                        'product' => new ProductResource($this->whenLoaded('product')),

            // نقرأ الـ options من JSON
            'selected_options' => collect($this->options ?? [])->map(function ($option) {
                return [
                    'option_id' => $option['option_id'] ?? null,
                    'option_value_id' => $option['option_value_id'] ?? null,
                    'name' => $option['name'] ?? null,
                    'value' => $option['value'] ?? null,
                    'price_modifier' => $option['price_modifier'] ?? 0,
                ];
            }),
            'total_price' => $this->price * $this->quantity
        ];
    }
}

