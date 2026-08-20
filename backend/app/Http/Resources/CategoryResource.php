<?php

namespace App\Http\Resources;

use App\Support\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Category */
class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'parent_id' => $this->parent_id ? (string) $this->parent_id : null,
            'name' => $this->name,
            'slug' => $this->slug,
            'icon_url' => Media::url($this->icon_url) ?? '',
            'image_url' => Media::url($this->image_url) ?? '',
            'color' => $this->color,
            'sort_order' => (int) $this->sort_order,
            'products_count' => (int) ($this->products_count ?? 0),
            'children' => CategoryResource::collection($this->whenLoaded('children')),
        ];
    }
}
