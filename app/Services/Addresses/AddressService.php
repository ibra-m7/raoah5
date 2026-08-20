<?php

namespace App\Services\Addresses;

use App\Http\Resources\AddressResource;
use App\Http\Resources\UserResource;
use App\Models\Address;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AddressService
{
    public function listFor(User $user): Collection
    {
        return $user->addresses()
            ->withCount('orders')
            ->orderByDesc('is_default')
            ->latest('id')
            ->get();
    }

    public function create(User $user, array $data): Address
    {
        return DB::transaction(function () use ($user, $data) {
            $payload = $this->payload($user, $data);
            $makeDefault = (bool) ($payload['is_default'] ?? true) || $user->addresses()->doesntExist();

            if ($makeDefault) {
                $user->addresses()->update(['is_default' => false]);
                $payload['is_default'] = true;
            }

            return $user->addresses()->create($payload)->loadCount('orders');
        });
    }

    public function update(User $user, Address $address, array $data): Address
    {
        $this->assertOwner($user, $address);

        return DB::transaction(function () use ($user, $address, $data) {
            if (array_key_exists('is_default', $data) && $data['is_default']) {
                $user->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
                $data['is_default'] = true;
            }

            $address->update($data);

            if ($user->addresses()->where('is_default', true)->doesntExist()) {
                $address->update(['is_default' => true]);
            }

            return $address->fresh()->loadCount('orders');
        });
    }

    public function delete(User $user, Address $address): void
    {
        $this->assertOwner($user, $address);

        if ($user->addresses()->count() <= 1) {
            throw ValidationException::withMessages([
                'address' => 'يجب الإبقاء على عنوان توصيل واحد على الأقل.',
            ]);
        }

        DB::transaction(function () use ($user, $address) {
            $wasDefault = $address->is_default;
            $address->delete();

            if ($wasDefault) {
                $user->addresses()->latest('id')->first()?->update(['is_default' => true]);
            }
        });
    }

    public function findFor(User $user, int|string $id): Address
    {
        $address = $user->addresses()->withCount('orders')->whereKey($id)->first();
        if ($address === null) {
            throw new NotFoundHttpException('العنوان غير موجود.');
        }

        return $address;
    }

    /**
     * @return array<string, mixed>
     */
    public function userPayload(User $user): array
    {
        $user->load([
            'addresses' => fn ($q) => $q->withCount('orders')->orderByDesc('is_default')->latest('id'),
        ]);

        return (new UserResource($user))->resolve();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function collectionPayload(User $user): array
    {
        return AddressResource::collection($this->listFor($user))->resolve();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function payload(User $user, array $data): array
    {
        $phone = $user->phone ?: '';

        return [
            'label' => filled($data['label'] ?? null) ? $data['label'] : 'المنزل',
            'recipient_name' => $user->needsName() ? 'عميل' : $user->name,
            'phone' => $phone,
            'city' => filled($data['city'] ?? null) ? $data['city'] : 'السعودية',
            'district' => $data['district'] ?? null,
            'street' => $data['street'] ?? null,
            'details' => $data['details'] ?? null,
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'is_default' => array_key_exists('is_default', $data) ? (bool) $data['is_default'] : true,
        ];
    }

    private function assertOwner(User $user, Address $address): void
    {
        if ((int) $address->user_id !== (int) $user->id) {
            throw new NotFoundHttpException('العنوان غير موجود.');
        }
    }
}
