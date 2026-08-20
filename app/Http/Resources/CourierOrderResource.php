<?php

namespace App\Http\Resources;

use App\Enums\OrderStatus;
use App\Models\Courier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Order */
class CourierOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $courierId = $request->user()?->id;
        $mine = $this->courier_id !== null && (int) $this->courier_id === (int) $courierId;
        $address = $this->whenLoaded('address') ? $this->address : null;

        return [
            'id' => (string) $this->id,
            'order_number' => $this->displayNumber(),
            'status' => $this->status?->value,
            'status_label' => $this->status === OrderStatus::Pending && $this->courier_id === null
                ? 'طلب جديد'
                : $this->status?->label(),
            'subtotal' => (float) $this->subtotal,
            'shipping_fee' => (float) $this->shipping_fee,
            'total' => (float) $this->total,
            'payment_method' => $this->paymentMethodSlug(),
            'payment_method_label' => $this->paymentMethodLabel(),
            'payment_status' => $this->payment_status?->value,
            'payment_status_label' => $this->payment_status?->label(),
            'shipping_name' => $this->shipping_name,
            'shipping_phone' => $this->shipping_phone,
            'shipping_city' => $this->shipping_city,
            'shipping_district' => $this->shipping_district,
            'shipping_street' => $this->shipping_street,
            'shipping_details' => $this->shipping_details,
            'notes' => $this->notes,
            'delivery_label' => $this->delivery_label,
            'maps_url' => $this->mapsUrl(),
            'latitude' => $address?->latitude !== null ? (float) $address->latitude : null,
            'longitude' => $address?->longitude !== null ? (float) $address->longitude : null,
            'items_count' => $this->whenLoaded('items') ? $this->items->count() : $this->items()->count(),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'assigned_to_me' => $mine,
            'can_accept' => $this->courier_id === null
                && in_array($this->status, [OrderStatus::Pending, OrderStatus::Preparing], true)
                && $request->user() instanceof Courier
                && $request->user()->canReceiveOrders(),
            'can_pickup' => $mine && $this->status === OrderStatus::Preparing,
            'can_deliver' => $mine && $this->status === OrderStatus::OnTheWay,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
