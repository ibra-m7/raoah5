<?php

namespace App\Models;

use App\Enums\DeliveryPerkReward;
use App\Enums\DeliveryPerkTrigger;
use App\Support\AppStrings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DeliveryPerk extends Model
{
    protected $fillable = [
        'name',
        'trigger_type',
        'min_orders',
        'reward_type',
        'reward_value',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'trigger_type' => DeliveryPerkTrigger::class,
            'reward_type' => DeliveryPerkReward::class,
            'reward_value' => 'decimal:2',
            'min_orders' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function matches(int $completedOrders, int $upcomingOrderNumber): bool
    {
        $threshold = max(1, $this->min_orders);

        return match ($this->trigger_type) {
            DeliveryPerkTrigger::MinOrders => $completedOrders >= $threshold,
            DeliveryPerkTrigger::EveryNth => $upcomingOrderNumber > 0 && $upcomingOrderNumber % $threshold === 0,
        };
    }

    public function triggerLabel(): string
    {
        $n = max(1, $this->min_orders);

        return match ($this->trigger_type) {
            DeliveryPerkTrigger::MinOrders => 'بعد إكمال '.$n.' طلبات',
            DeliveryPerkTrigger::EveryNth => 'كل '.$n.' طلبات',
        };
    }

    public function rewardLabel(): string
    {
        $value = rtrim(rtrim(number_format((float) $this->reward_value, 2), '0'), '.');

        return match ($this->reward_type) {
            DeliveryPerkReward::Free => 'توصيل مجاني',
            DeliveryPerkReward::Percent => 'خصم '.$value.'٪ على التوصيل',
            DeliveryPerkReward::Amount => 'خصم '.$value.' '.AppStrings::CURRENCY.' من التوصيل',
        };
    }

    public function applyToFee(float $fee): float
    {
        $next = match ($this->reward_type) {
            DeliveryPerkReward::Free => 0.0,
            DeliveryPerkReward::Percent => $fee * (1 - min(100, max(0, (float) $this->reward_value)) / 100),
            DeliveryPerkReward::Amount => $fee - (float) $this->reward_value,
        };

        return round(max(0, $next), 2);
    }
}
