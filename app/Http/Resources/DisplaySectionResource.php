<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\DisplaySection */
class DisplaySectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'emoji' => $this->emoji,
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
        ];
    }
}
