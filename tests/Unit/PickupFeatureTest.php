<?php

namespace Tests\Unit;

use App\Enums\OrderMethod;
use App\Models\Courier;
use PHPUnit\Framework\TestCase;

class PickupFeatureTest extends TestCase
{
    public function test_courier_handles_delivery_only(): void
    {
        $courier = new Courier([
            'handles_delivery' => true,
            'handles_pickup' => false,
        ]);

        $this->assertTrue($courier->handlesOrderMethod(OrderMethod::Delivery->value));
        $this->assertFalse($courier->handlesOrderMethod(OrderMethod::Pickup->value));
    }

    public function test_courier_handles_pickup_only(): void
    {
        $courier = new Courier([
            'handles_delivery' => false,
            'handles_pickup' => true,
        ]);

        $this->assertTrue($courier->handlesOrderMethod(OrderMethod::Pickup->value));
        $this->assertFalse($courier->handlesOrderMethod(OrderMethod::Delivery->value));
    }

    public function test_courier_handles_both_order_methods(): void
    {
        $courier = new Courier([
            'handles_delivery' => true,
            'handles_pickup' => true,
        ]);

        $this->assertTrue($courier->handlesOrderMethod(OrderMethod::Pickup->value));
        $this->assertTrue($courier->handlesOrderMethod(OrderMethod::Delivery->value));
    }

    public function test_order_method_enum_labels(): void
    {
        $this->assertSame('توصيل', OrderMethod::Delivery->label());
        $this->assertSame('استلام من المركز', OrderMethod::Pickup->label());
        $this->assertTrue(OrderMethod::Pickup->isPickup());
        $this->assertFalse(OrderMethod::Delivery->isPickup());
    }
}
