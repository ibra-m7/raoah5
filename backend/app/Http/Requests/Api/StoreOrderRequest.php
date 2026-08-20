<?php

namespace App\Http\Requests\Api;

use App\Support\StoreSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'payment_method' => [
                'nullable',
                'string',
                'max:40',
                Rule::in(StoreSettings::activePaymentSlugs() ?: ['cash']),
            ],
            'notes' => ['nullable', 'string', 'max:500'],
            'coupon_code' => ['nullable', 'string', 'max:40'],
            'fulfillment_type' => ['nullable', 'string', Rule::in(['now', 'scheduled'])],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
            'address_id' => [
                'nullable',
                'integer',
                Rule::exists('addresses', 'id')->where(
                    fn ($query) => $query->where('user_id', $this->user()?->id),
                ),
            ],
            'shipping_name' => ['nullable', 'string', 'max:120'],
            'shipping_phone' => ['nullable', 'string', 'max:20'],
            'shipping_city' => ['nullable', 'string', 'max:80'],
            'shipping_district' => ['nullable', 'string', 'max:80'],
            'shipping_street' => ['nullable', 'string', 'max:160'],
            'shipping_details' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return [
            'items' => 'المنتجات',
            'items.*.product_id' => 'المنتج',
            'items.*.quantity' => 'الكمية',
            'coupon_code' => 'كود الخصم',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('coupon_code')) {
            $this->merge([
                'coupon_code' => strtoupper(preg_replace('/\s+/', '', (string) $this->input('coupon_code')) ?? ''),
            ]);
        }
    }
}
