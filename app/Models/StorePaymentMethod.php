<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class StorePaymentMethod extends Model
{
    protected $fillable = [
        'slug',
        'label',
        'hint',
        'icon',
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

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return array{id: string, label: string, hint: string, icon: string}
     */
    public function toCheckoutOption(): array
    {
        return [
            'id' => $this->slug,
            'label' => $this->label,
            'hint' => (string) ($this->hint ?? ''),
            'icon' => (string) ($this->icon ?: 'bi-credit-card'),
        ];
    }
}
