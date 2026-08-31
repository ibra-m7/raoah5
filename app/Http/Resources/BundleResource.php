<?php

namespace App\Http\Resources;

use App\Support\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\ProductBundle */
class BundleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'summary' => $this->summary ?? '',
            'description' => $this->description ?? '',
            'image_url' => Media::url($this->image_url) ?? '',
            'discount_percent' => (float) $this->discount_percent,
            'bundle_price' => (float) $this->bundle_price,
            'original_price' => (float) $this->original_price,
            'item_count' => (int) $this->item_count,
            'is_available' => (bool) $this->is_available,
            'items' => $this->itemsPayload(),
        ];
    }

    /**
     * @return list<array{product: array<string, mixed>, quantity: int}>
     */
    private function itemsPayload(): array
    {
        if (! $this->relationLoaded('items')) {
            return [];
        }

        return $this->items
            ->map(function ($item) {
                return [
                    'product' => (new ProductResource($item->product))->resolve(),
                    'quantity' => max(1, (int) $item->quantity),
                ];
            })
            ->values()
            ->all();
    }
}
