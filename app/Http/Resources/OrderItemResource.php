<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\OrderItem */
class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'product_id' => $this->product_id ? (string) $this->product_id : null,
            'name' => $this->product_name,
            'image_url' => $this->product_image,
            'unit_price' => (float) $this->unit_price,
            'quantity' => (int) $this->quantity,
            'line_total' => (float) $this->line_total,
            'is_gift' => (bool) $this->is_gift,
            'gift_for_product_id' => $this->gift_for_product_id
                ? (string) $this->gift_for_product_id
                : null,
        ];
    }
}
