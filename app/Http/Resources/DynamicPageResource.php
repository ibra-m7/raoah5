<?php

namespace App\Http\Resources;

use App\Support\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\DynamicPage */
class DynamicPageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'title' => $this->title,
            'show_title' => (bool) $this->show_title,
            'banner_image_url' => Media::url($this->banner_image_url) ?? '',
            'appbar_image_url' => Media::url($this->appbar_image_url) ?? '',
            'placement' => $this->placement?->value ?? 'none',
            'products' => ProductResource::collection($this->whenLoaded('products')),
        ];
    }
}
