<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PickupSlotWindow extends Model
{
    protected $fillable = [
        'weekday',
        'start_time',
        'end_time',
        'interval_minutes',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'weekday' => 'integer',
            'interval_minutes' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('weekday')->orderBy('sort_order')->orderBy('start_time');
    }

    public function weekdayLabel(): string
    {
        return DeliverySlotWindow::weekdayNames()[$this->weekday] ?? '';
    }
}
