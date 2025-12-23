<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'        => $this->id,
            'name'      => $this->name,
            'slug'      => $this->slug,
            'description_ar'      => $this->description_ar,

            // 'parent_id' => $this->parent_id,
'image' => $this->image ? asset('storage/' . $this->image) : null,

            // 'children'  => CategoryResource::collection($this->whenLoaded('children')),
        ];
    }
}
