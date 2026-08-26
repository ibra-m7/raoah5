<?php

namespace App\Models;

use App\Enums\DeliveryPerKmMode;
use App\Enums\DeliveryPricingType;
use App\Support\AppStrings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DeliveryRule extends Model
{
    protected $fillable = [
        'name',
        'min_km',
        'max_km',
        'pricing_type',
        'amount',
        'per_km_mode',
        'sort_order',
        'is_active',
        'note',
        'note_enabled',
    ];

    protected function casts(): array
    {
        return [
            'min_km' => 'decimal:2',
            'max_km' => 'decimal:2',
            'amount' => 'decimal:2',
            'pricing_type' => DeliveryPricingType::class,
            'per_km_mode' => DeliveryPerKmMode::class,
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'note_enabled' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function matches(float $distanceKm): bool
    {
        if ($distanceKm < (float) $this->min_km) {
            return false;
        }

        if ($this->max_km === null) {
            return true;
        }

        return $distanceKm < (float) $this->max_km;
    }

    public function rangeLabel(): string
    {
        $from = rtrim(rtrim(number_format((float) $this->min_km, 2), '0'), '.');
        if ($this->max_km === null) {
            return 'من '.$from.' كم فأكثر';
        }
        $to = rtrim(rtrim(number_format((float) $this->max_km, 2), '0'), '.');

        return 'من '.$from.' إلى '.$to.' كم';
    }

    public function priceLabel(): string
    {
        return match ($this->pricing_type) {
            DeliveryPricingType::Free => 'مجاني',
            DeliveryPricingType::Flat => number_format((float) $this->amount, 2).' '.AppStrings::CURRENCY,
            DeliveryPricingType::PerKm => number_format((float) $this->amount, 2).' '.AppStrings::CURRENCY.' / كم — '.$this->per_km_mode->label(),
        };
    }
}
