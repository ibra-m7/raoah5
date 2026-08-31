<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductBundle extends Model
{
    protected $table = 'product_bundles';

    protected $fillable = [
        'name',
        'slug',
        'summary',
        'description',
        'image_url',
        'discount_percent',
        'bundle_price',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'discount_percent' => 'decimal:2',
            'bundle_price' => 'decimal:2',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(BundleItem::class, 'bundle_id')->orderBy('sort_order');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'bundle_items', 'bundle_id', 'product_id')
            ->withPivot('quantity', 'sort_order')
            ->orderByPivot('sort_order');
    }

    public function homeSections(): BelongsToMany
    {
        return $this->belongsToMany(HomeSection::class, 'home_section_bundles')
            ->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function computeOriginalPrice(): float
    {
        $items = $this->relationLoaded('items')
            ? $this->items
            : $this->items()->with('product')->get();

        $total = 0.0;
        foreach ($items as $item) {
            $product = $item->product;
            if ($product === null) {
                continue;
            }
            $total += (float) $product->effective_price * max(1, (int) $item->quantity);
        }

        return round($total, 2);
    }

    public function computeItemCount(): int
    {
        $items = $this->relationLoaded('items') ? $this->items : $this->items()->get();

        return (int) $items->sum(fn (BundleItem $item) => max(1, (int) $item->quantity));
    }

    public function computeIsAvailable(): bool
    {
        $items = $this->relationLoaded('items')
            ? $this->items
            : $this->items()->with('product')->get();

        if ($items->isEmpty()) {
            return false;
        }

        foreach ($items as $item) {
            $product = $item->product;
            if ($product === null || ! $product->is_available) {
                return false;
            }
            if ((int) $product->stock < max(1, (int) $item->quantity)) {
                return false;
            }
        }

        return true;
    }

    protected function originalPrice(): Attribute
    {
        return Attribute::get(fn () => $this->computeOriginalPrice());
    }

    protected function itemCount(): Attribute
    {
        return Attribute::get(fn () => $this->computeItemCount());
    }

    protected function isAvailable(): Attribute
    {
        return Attribute::get(fn () => $this->computeIsAvailable());
    }
}
