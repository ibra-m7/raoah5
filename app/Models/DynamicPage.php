<?php

namespace App\Models;

use App\Enums\DynamicPagePlacement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class DynamicPage extends Model
{
    protected $fillable = [
        'title',
        'banner_image_url',
        'appbar_image_url',
        'sort_order',
        'is_active',
        'placement',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'placement' => DynamicPagePlacement::class,
        ];
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'dynamic_page_product')
            ->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}
