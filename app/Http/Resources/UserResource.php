<?php

namespace App\Http\Resources;

use App\Support\Phone;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $phone = $this->phone ? Phone::national($this->phone) : null;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $phone,
            'email' => $this->email,
            'avatar' => $this->avatar,
            'locale' => $this->locale,
            'needs_name' => $this->needsName(),
            'needs_location' => $this->needsLocation(),
            'notifications_enabled' => (bool) $this->notifications_enabled,
            'addresses' => AddressResource::collection($this->whenLoaded('addresses')),
            'default_address' => $this->when(
                $this->relationLoaded('addresses'),
                function () {
                    $default = $this->addresses->firstWhere('is_default', true)
                        ?? $this->addresses->first();

                    return $default ? (new AddressResource($default))->resolve() : null;
                },
            ),
        ];
    }
}
