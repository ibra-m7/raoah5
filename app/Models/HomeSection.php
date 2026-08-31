<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HomeSection extends Model
{
    public const CONTENT_PRODUCTS = 'products';

    public const CONTENT_BUNDLES = 'bundles';

    protected $fillable = [
        'key',
        'content_type',
        'title',
        'subtitle',
        'title_color',
        'subtitle_color',
        'background_color',
        'background_image_url',
        'sort_order',
        'is_active',
        'auto_scroll_cards',
        'show_title_icon',
        'emphasize_subtitle',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'auto_scroll_cards' => 'boolean',
            'show_title_icon' => 'boolean',
            'emphasize_subtitle' => 'boolean',
        ];
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'home_section_products')
            ->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }

    public function bundles(): BelongsToMany
    {
        return $this->belongsToMany(ProductBundle::class, 'home_section_bundles', 'home_section_id', 'bundle_id')
            ->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }

    public function homeSectionProducts(): HasMany
    {
        return $this->hasMany(HomeSectionProduct::class)->orderBy('sort_order');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function showsBundles(): bool
    {
        return $this->content_type === self::CONTENT_BUNDLES;
    }

    public function contentTypeLabel(): string
    {
        return $this->showsBundles() ? 'سلات' : 'منتجات';
    }
}
