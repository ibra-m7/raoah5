<?php

namespace App\Http\Requests\Admin;

use App\Enums\PromoType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $editing = $this->route('product');

        return [
            'promo_type' => ['required', Rule::enum(PromoType::class)],
            'product_id' => [$editing ? 'nullable' : 'required_without:product_ids', 'integer', 'exists:products,id'],
            'product_ids' => [$editing ? 'nullable' : 'required_without:product_id', 'array', 'min:1'],
            'product_ids.*' => ['integer', 'exists:products,id'],
            'mode' => ['required', Rule::in(['price', 'percent'])],
            'discount_price' => ['required_if:mode,price', 'nullable', 'numeric', 'min:0.01'],
            'percent' => ['required_if:mode,percent', 'nullable', 'numeric', 'min:1', 'max:99'],
            'is_featured' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'promo_type' => 'النوع',
            'product_id' => 'المنتج',
            'product_ids' => 'المنتجات',
            'mode' => 'طريقة التخفيض',
            'discount_price' => 'السعر بعد التخفيض',
            'percent' => 'نسبة الخصم',
        ];
    }

    protected function prepareForValidation(): void
    {
        $ids = $this->input('product_ids', []);
        if (! is_array($ids)) {
            $ids = $ids ? [$ids] : [];
        }
        if ($this->filled('product_id')) {
            $ids[] = $this->input('product_id');
        }

        $this->merge([
            'is_featured' => $this->boolean('is_featured'),
            'promo_type' => PromoType::fromRequest($this->input('promo_type'))->value,
            'mode' => $this->input('mode', 'price'),
            'product_ids' => array_values(array_unique(array_filter($ids))),
        ]);
    }
}
