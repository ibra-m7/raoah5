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
            'content_type' => $this->content_type,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'title_color' => $this->title_color,
            'subtitle_color' => $this->subtitle_color,
            'background_color' => $this->background_color,
            'background_image_url' => $this->background_image_url
                ? \App\Support\Media::url($this->background_image_url)
                : null,
            'auto_scroll_cards' => (bool) $this->auto_scroll_cards,
            'show_title_icon' => (bool) $this->show_title_icon,
            'emphasize_subtitle' => (bool) $this->emphasize_subtitle,
            'products' => ProductResource::collection($this->whenLoaded('products')),
            'bundles' => BundleResource::collection($this->whenLoaded('bundles')),
        ];
    }
}
