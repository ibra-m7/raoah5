<?php

namespace Tests\Unit;

use App\Enums\DeliveryPerkReward;
use App\Enums\DeliveryPerkTrigger;
use App\Models\DeliveryPerk;
use PHPUnit\Framework\TestCase;

class DeliveryPerkMatchTest extends TestCase
{
    public function test_min_orders_starts_after_threshold(): void
    {
        $perk = new DeliveryPerk([
            'trigger_type' => DeliveryPerkTrigger::MinOrders,
            'min_orders' => 4,
            'reward_type' => DeliveryPerkReward::Free,
        ]);

        $this->assertFalse($perk->matches(3, 4));
        $this->assertTrue($perk->matches(4, 5));
        $this->assertTrue($perk->matches(10, 11));
    }

    public function test_every_nth_order(): void
    {
        $perk = new DeliveryPerk([
            'trigger_type' => DeliveryPerkTrigger::EveryNth,
            'min_orders' => 5,
            'reward_type' => DeliveryPerkReward::Percent,
            'reward_value' => 50,
        ]);

        $this->assertFalse($perk->matches(3, 4));
        $this->assertTrue($perk->matches(4, 5));
        $this->assertTrue($perk->matches(9, 10));
        $this->assertSame(6.0, $perk->applyToFee(12));
    }
}
