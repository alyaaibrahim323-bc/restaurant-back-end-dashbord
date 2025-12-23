<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class VariantResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'price' => $this->price,
            'stock' => $this->stock,
            'stock_status' => $this->stock_status,
            'image' => $this->image,
            'options' => $this->optionValues->map(function ($value) {
                return [
                    'option_id' => $value->option_id,
                    'option_name' => $value->option->name,
                    'value' => $value->value
                ];
            })
        ];
    }
}
