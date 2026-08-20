<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Address */
class AddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'details' => $this->details,
            'city' => $this->city,
            'district' => $this->district,
            'street' => $this->street,
            'latitude' => $this->latitude !== null ? (float) $this->latitude : null,
            'longitude' => $this->longitude !== null ? (float) $this->longitude : null,
            'is_default' => (bool) $this->is_default,
            'line' => $this->displayLine(),
            'orders_count' => (int) ($this->orders_count ?? 0),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
