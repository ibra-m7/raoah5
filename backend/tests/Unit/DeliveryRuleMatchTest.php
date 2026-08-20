<?php

namespace Tests\Unit;

use App\Models\DeliveryRule;
use PHPUnit\Framework\TestCase;

class DeliveryRuleMatchTest extends TestCase
{
    public function test_closed_range_excludes_max(): void
    {
        $rule = new DeliveryRule(['min_km' => 0, 'max_km' => 10]);

        $this->assertTrue($rule->matches(0));
        $this->assertTrue($rule->matches(9.99));
        $this->assertFalse($rule->matches(10));
    }

    public function test_open_ended_range_includes_min(): void
    {
        $rule = new DeliveryRule(['min_km' => 10, 'max_km' => null]);

        $this->assertFalse($rule->matches(9.99));
        $this->assertTrue($rule->matches(10));
        $this->assertTrue($rule->matches(42));
    }
}
