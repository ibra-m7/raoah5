<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class PreviewCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'coupon_code' => ['required', 'string', 'max:40'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'address_id' => ['nullable', 'integer'],
        ];
    }

    public function attributes(): array
    {
        return [
            'coupon_code' => 'كود الخصم',
            'items' => 'المنتجات',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'coupon_code' => strtoupper(preg_replace('/\s+/', '', (string) $this->input('coupon_code')) ?? ''),
        ]);
    }
}
