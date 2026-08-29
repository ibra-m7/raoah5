<?php

namespace App\Http\Resources;

use App\Support\Media;
use App\Support\StoreSettings;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Product */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $images = $this->whenLoaded('images', fn () => $this->images, collect());
        $primary = $this->relationLoaded('primaryImage')
            ? $this->primaryImage
            : $images->firstWhere('is_primary', true) ?? $images->first();

        $urls = $images
            ->reject(fn ($image) => Media::isMissingLocal($image->url))
            ->map(fn ($image) => Media::url($image->url))
            ->filter()
            ->values();
        $imageUrl = Media::isMissingLocal($primary?->url) ? null : Media::url($primary?->url);
        $imageUrl = $imageUrl ?: $urls->first() ?: StoreSettings::fallbackProductImageUrl();

        return [
            'id' => (string) $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description ?? '',
            'price' => (float) $this->price,
            'discount_price' => $this->discount_price !== null ? (float) $this->discount_price : null,
            'effective_price' => (float) $this->effective_price,
            'has_discount' => (bool) $this->has_discount,
            'image_url' => $imageUrl ?? '',
            'image_urls' => $urls,
            'category_id' => (string) $this->category_id,
            'root_category_id' => (string) ($this->category?->parent_id ?: $this->category_id),
            'category_name' => $this->category?->name,
            'stock' => (int) $this->stock,
            'piece_count' => $this->piece_count !== null ? (int) $this->piece_count : null,
            'weight_label' => $this->weight_label ?: '',
            'quantity_label' => trim((string) ($this->quantity_label ?? '')),
            'sold_count' => StoreSettings::marketingSoldCountFor($this->resource),
            'rating' => (float) $this->rating,
            'review_count' => (int) $this->review_count,
            'benefits' => $this->benefits ?? [],
            'keywords' => $this->keywords ?? [],
            'usage_instructions' => $this->usage_instructions ?? '',
            'is_featured' => (bool) $this->is_featured,
            'is_available' => (bool) $this->is_available,
            'complementary' => ProductResource::collection($this->whenLoaded('complementaryProducts')),
            'gift_product' => $this->giftProductPayload(),
        ];
    }

    /**
     * @return array{id: string, name: string, image_url: string, is_available: bool}|null
     */
    private function giftProductPayload(): ?array
    {
        if (! $this->relationLoaded('giftProducts')) {
            return null;
        }

        $gift = $this->giftProducts->first();
        if ($gift === null || ! $gift->is_active || $gift->stock <= 0) {
            return null;
        }

        $primary = $gift->relationLoaded('primaryImage') ? $gift->primaryImage : null;
        $giftImageUrl = Media::isMissingLocal($primary?->url) ? null : Media::url($primary?->url);
        $giftImageUrl = $giftImageUrl ?: StoreSettings::fallbackProductImageUrl();

        return [
            'id' => (string) $gift->id,
            'name' => $gift->name,
            'image_url' => $giftImageUrl ?? '',
            'is_available' => true,
        ];
    }
}
