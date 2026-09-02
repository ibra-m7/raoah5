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
            'phone_country' => ['required', 'string', Rule::in(Phone::allowedCountryCodes())],
            'phone' => ['required', 'string', 'max:16'],
            'password' => $passwordRules,
            'is_active' => ['nullable', 'boolean'],
            'handles_delivery' => ['nullable', 'boolean'],
            'handles_pickup' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $courier = $this->route('courier');
            $id = $courier instanceof Courier ? $courier->id : null;
            $normalized = $this->input('phone_normalized');

            if ($normalized === null && $this->filled('phone')) {
                $validator->errors()->add('phone', 'الرقم غير صالح للدولة المختارة.');
            }

            if ($normalized) {
                $exists = Courier::query()
                    ->where('phone', $normalized)
                    ->when($id, fn ($query) => $query->where('id', '!=', $id))
                    ->exists();
                if ($exists) {
                    $validator->errors()->add('phone', 'رقم الجوال مستخدم مسبقاً.');
                }
            }

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
        $country = (string) $this->input('phone_country', Phone::countryCode());
        $national = trim((string) $this->input('phone', ''));
        $phone = Phone::combineGcc($country, $national);

        $this->merge([
            'phone_country' => $country,
            'phone' => $national,
            'phone_normalized' => $phone,
            'is_active' => $this->boolean('is_active'),
            'handles_delivery' => $this->boolean('handles_delivery'),
            'handles_pickup' => $this->boolean('handles_pickup'),
            'password' => $this->filled('password') ? $this->input('password') : null,
        ]);
    }

    public function normalizedPhone(): ?string
    {
        return $this->input('phone_normalized');
    }
}
