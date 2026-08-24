<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HomeSection extends Model
{
    protected $fillable = [
        'key',
        'title',
        'subtitle',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'home_section_products')
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

    /**
     * @return array<string, array{label: string}>
     */
    public static function displayStyles(): array
    {
        return [
            'best_prices' => ['label' => 'أسعار مميزة'],
            'most_requested' => ['label' => 'الأكثر طلباً'],
            'fresh_groceries' => ['label' => 'منتجات طازجة'],
            'general' => ['label' => 'قسم عادي'],
        ];
    }

    public function displayStyle(): string
    {
        $known = ['best_prices', 'most_requested', 'fresh_groceries'];

        return in_array((string) $this->key, $known, true) ? (string) $this->key : 'general';
    }

    public function styleLabel(): string
    {
        $style = $this->displayStyle();

        return self::displayStyles()[$style]['label'] ?? 'قسم عادي';
    }
}
