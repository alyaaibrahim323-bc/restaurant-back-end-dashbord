<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
   public function toArray($request)
{
    return [
        'id' => $this->id,
        'rating' => $this->rating,
        'comment' => $this->comment,
        'verified_label' => $this->is_verified ? 'عميل موثوق' : 'مراجعة عامة',
        'created_at' => $this->created_at->diffForHumans(),
        'user' => [
            'id' => $this->user->id,
            'name' => $this->user->name,
            'avatar' => $this->user->avatar_url ?? null,
        ]
    ];
}
}
