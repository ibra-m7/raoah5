<?php

namespace App\Http\Resources;

use App\Support\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Banner */
class BannerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'title' => $this->title,
            'show_title' => (bool) $this->show_title,
            'subtitle' => $this->subtitle,
            'image_url' => Media::url($this->image_url) ?? '',
            'link_type' => $this->link_type?->value ?? 'none',
            'link_id' => $this->link_id ? (string) $this->link_id : null,
            'link_url' => $this->link_url,
        ];
    }
}
