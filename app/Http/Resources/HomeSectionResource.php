<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\HomeSection */
class HomeSectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'key' => $this->key,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'background_color' => $this->background_color,
            'products' => ProductResource::collection($this->whenLoaded('products')),
        ];
    }
}
