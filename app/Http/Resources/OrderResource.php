<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Order */
class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'subtotal' => (float) $this->subtotal,
            'shipping_fee' => (float) $this->shipping_fee,
            'total' => (float) $this->total,
            'has_free_shipping' => (bool) $this->has_free_shipping,
            'payment_method' => $this->paymentMethodSlug(),
            'payment_method_label' => $this->paymentMethodLabel(),
            'payment_status' => $this->payment_status?->value,
            'payment_status_label' => $this->payment_status?->label(),
            'shipping_city' => $this->shipping_city,
            'shipping_details' => $this->shipping_details,
            'notes' => $this->notes,
            'coupon_code' => $this->coupon_code,
            'discount_amount' => (float) $this->discount_amount,
            'fulfillment_type' => $this->fulfillment_type,
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'can_cancel' => $this->canBeCancelledByCustomer(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'cancel_reason' => $this->cancel_reason,
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
