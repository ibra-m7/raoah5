<?php

namespace App\Http\Resources;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Services\Couriers\CourierLedgerService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Courier */
class CourierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $delivered = $this->relationLoaded('deliveredOrders')
            ? $this->deliveredOrders->count()
            : $this->deliveredOrders()->count();
        $summary = app(CourierLedgerService::class)->summary($this->resource);

        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'phone_display' => $this->phoneDisplay(),
            'is_active' => (bool) $this->is_active,
            'is_online' => (bool) $this->is_online,
            'delivered_count' => $delivered,
            'payment_method' => PaymentMethod::Cash->value,
            'payment_method_label' => PaymentMethod::Cash->label(),
            'available_count' => $this->availableCount(),
            'cod_collected' => $summary['collected'],
            'settled_total' => $summary['settled'],
            'owes' => $summary['owes'],
            'owed' => $summary['owed'],
        ];
    }

    private function availableCount(): int
    {
        if (! $this->canReceiveOrders()) {
            return 0;
        }

        return \App\Models\Order::query()
            ->whereNull('courier_id')
            ->whereIn('status', [OrderStatus::Pending, OrderStatus::Preparing])
            ->count();
    }
}
