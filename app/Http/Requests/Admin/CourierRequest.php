<?php

namespace App\Http\Requests\Admin;

use App\Models\Courier;
use App\Support\Phone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CourierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $courier = $this->route('courier');
        $id = $courier instanceof Courier ? $courier->id : null;
        $passwordRules = $id
            ? ['nullable', 'string', 'min:6', 'max:80']
            : ['required', 'string', 'min:6', 'max:80'];

        return [
            'name' => ['required', 'string', 'max:80'],
            'phone' => ['required', 'string', Rule::unique('couriers', 'phone')->ignore($id)],
            'password' => $passwordRules,
            'is_active' => ['nullable', 'boolean'],
            'handles_delivery' => ['nullable', 'boolean'],
            'handles_pickup' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $handlesDelivery = $this->boolean('handles_delivery');
            $handlesPickup = $this->boolean('handles_pickup');
            if (! $handlesDelivery && ! $handlesPickup) {
                $validator->errors()->add('handles_delivery', 'حدد نوعاً واحداً على الأقل من الطلبات.');
            }
        });
    }

    public function attributes(): array
    {
        return [
            'name' => 'الاسم',
            'phone' => 'رقم الجوال',
            'password' => 'كلمة المرور',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'أدخل رقم جوال صالح.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $phone = Phone::normalize((string) $this->input('phone', ''));

        $this->merge([
            'phone' => $phone,
            'is_active' => $this->boolean('is_active'),
            'handles_delivery' => $this->boolean('handles_delivery'),
            'handles_pickup' => $this->boolean('handles_pickup'),
            'password' => $this->filled('password') ? $this->input('password') : null,
        ]);
    }
}
