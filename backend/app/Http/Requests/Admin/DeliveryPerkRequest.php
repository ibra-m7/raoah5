<?php

namespace App\Http\Requests\Admin;

use App\Enums\DeliveryPerkReward;
use App\Enums\DeliveryPerkTrigger;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeliveryPerkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $reward = $this->input('reward_type');

        return [
            'name' => ['required', 'string', 'max:80'],
            'trigger_type' => ['required', Rule::enum(DeliveryPerkTrigger::class)],
            'min_orders' => ['required', 'integer', 'min:1', 'max:999'],
            'reward_type' => ['required', Rule::enum(DeliveryPerkReward::class)],
            'reward_value' => [
                Rule::requiredIf(in_array($reward, [DeliveryPerkReward::Percent->value, DeliveryPerkReward::Amount->value], true)),
                'nullable',
                'numeric',
                'min:0',
                'max:9999',
            ],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'اسم العرض',
            'trigger_type' => 'شرط التفعيل',
            'min_orders' => 'عدد الطلبات',
            'reward_type' => 'نوع الخصم',
            'reward_value' => 'قيمة الخصم',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'sort_order' => $this->filled('sort_order') ? $this->input('sort_order') : 0,
            'reward_value' => $this->input('reward_type') === DeliveryPerkReward::Free->value
                ? 0
                : $this->input('reward_value'),
        ]);
    }
}
