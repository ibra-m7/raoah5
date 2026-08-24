<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DeliverySlotWindow extends Model
{
    protected $fillable = [
        'weekday',
        'start_time',
        'end_time',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'weekday' => 'integer',
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
        return self::weekdayNames()[$this->weekday] ?? '';
    }

    /**
     * @return array<int, string>
     */
    public static function weekdayNames(): array
    {
        return [
            0 => 'الأحد',
            1 => 'الاثنين',
            2 => 'الثلاثاء',
            3 => 'الأربعاء',
            4 => 'الخميس',
            5 => 'الجمعة',
            6 => 'السبت',
        ];
    }
}
