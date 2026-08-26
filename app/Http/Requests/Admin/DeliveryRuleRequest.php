<?php

namespace App\Http\Requests\Admin;

use App\Enums\DeliveryPerKmMode;
use App\Enums\DeliveryPricingType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeliveryRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $type = $this->input('pricing_type');

        return [
            'name' => ['required', 'string', 'max:80'],
            'min_km' => ['required', 'numeric', 'min:0', 'max:5000'],
            'max_km' => ['nullable', 'numeric', 'min:0', 'max:5000', 'gt:min_km'],
            'pricing_type' => ['required', Rule::enum(DeliveryPricingType::class)],
            'amount' => [
                Rule::requiredIf(in_array($type, [DeliveryPricingType::Flat->value, DeliveryPricingType::PerKm->value], true)),
                'nullable',
                'numeric',
                'min:0',
                'max:9999',
            ],
            'per_km_mode' => ['nullable', Rule::enum(DeliveryPerKmMode::class)],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
            'note' => ['nullable', 'string', 'max:500'],
            'note_enabled' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'اسم الشريحة',
            'min_km' => 'من (كم)',
            'max_km' => 'إلى (كم)',
            'pricing_type' => 'نوع التسعير',
            'amount' => 'السعر',
            'per_km_mode' => 'طريقة حساب الكيلومتر',
            'note' => 'ملاحظة الشريحة',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'note_enabled' => $this->boolean('note_enabled'),
            'note' => $this->filled('note') ? trim((string) $this->input('note')) : null,
            'max_km' => $this->filled('max_km') ? $this->input('max_km') : null,
            'sort_order' => $this->filled('sort_order') ? $this->input('sort_order') : 0,
            'amount' => $this->input('pricing_type') === DeliveryPricingType::Free->value
                ? 0
                : $this->input('amount'),
            'per_km_mode' => $this->input('per_km_mode') ?: DeliveryPerKmMode::Entire->value,
        ]);
    }
}
