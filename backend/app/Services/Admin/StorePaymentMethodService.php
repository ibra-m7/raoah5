<?php

namespace App\Services\Admin;

use App\Models\Order;
use App\Models\StorePaymentMethod;
use App\Support\Constants;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class StorePaymentMethodService
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return StorePaymentMethod::query()
            ->when($filters['q'] ?? null, function ($query, $search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('label', 'like', '%'.$search.'%')
                        ->orWhere('slug', 'like', '%'.$search.'%');
                });
            })
            ->when(($filters['status'] ?? '') === 'active', fn ($query) => $query->where('is_active', true))
            ->when(($filters['status'] ?? '') === 'inactive', fn ($query) => $query->where('is_active', false))
            ->ordered()
            ->paginate(Constants::DEFAULT_PAGE_SIZE)
            ->withQueryString();
    }

    public function create(array $data): StorePaymentMethod
    {
        return StorePaymentMethod::query()->create($this->payload($data));
    }

    public function update(StorePaymentMethod $method, array $data): StorePaymentMethod
    {
        $method->update($this->payload($data, $method));

        return $method;
    }

    public function delete(StorePaymentMethod $method): void
    {
        if (Order::query()->where('payment_method', $method->slug)->exists()) {
            throw ValidationException::withMessages([
                'slug' => 'لا يمكن حذف طريقة دفع مستخدمة في طلبات سابقة. أخفِها من التطبيق بدلاً من الحذف.',
            ]);
        }

        $method->delete();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function iconOptions(): array
    {
        return [
            ['value' => 'bi-cash-coin', 'label' => 'كاش'],
            ['value' => 'bi-credit-card-2-front', 'label' => 'مدى / بطاقة'],
            ['value' => 'bi-credit-card', 'label' => 'فيزا / ماستركارد'],
            ['value' => 'bi-phone', 'label' => 'Apple Pay / جوال'],
            ['value' => 'bi-wallet2', 'label' => 'محفظة / STC Pay'],
            ['value' => 'bi-bank', 'label' => 'تحويل بنكي'],
            ['value' => 'bi-qr-code', 'label' => 'رمز QR'],
        ];
    }

    private function payload(array $data, ?StorePaymentMethod $method = null): array
    {
        $slug = strtolower(trim((string) ($data['slug'] ?? $method?->slug ?? '')));
        $slug = preg_replace('/[^a-z0-9_]/', '_', $slug) ?: 'method';

        return [
            'slug' => $slug,
            'label' => $data['label'],
            'hint' => $data['hint'] ?? null,
            'icon' => $data['icon'] ?? 'bi-credit-card',
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => (bool) ($data['is_active'] ?? false),
        ];
    }
}
