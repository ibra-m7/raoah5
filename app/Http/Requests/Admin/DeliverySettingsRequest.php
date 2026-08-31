<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class DeliverySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'delivery_enabled' => ['nullable', 'boolean'],
            'delivery_first_order_free' => ['nullable', 'boolean'],
            'delivery_hide_subtitle' => ['nullable', 'boolean'],
            'delivery_notes_enabled' => ['nullable', 'boolean'],
            'delivery_general_note' => ['nullable', 'string', 'max:500'],
            'delivery_store_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'delivery_store_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'delivery_store_address' => ['nullable', 'string', 'max:255'],
            'delivery_max_km' => ['nullable', 'numeric', 'min:0', 'max:5000'],
            'delivery_fallback_fee' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'pickup_enabled' => ['nullable', 'boolean'],
            'free_shipping_threshold' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return [
            'delivery_store_lat' => 'خط عرض المتجر',
            'delivery_store_lng' => 'خط طول المتجر',
            'delivery_store_address' => 'عنوان المتجر',
            'delivery_max_km' => 'الحد الأقصى للتوصيل',
            'delivery_fallback_fee' => 'الرسوم الاحتياطية',
            'free_shipping_threshold' => 'حد التوصيل المجاني للطلب',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'delivery_enabled' => $this->boolean('delivery_enabled'),
            'delivery_first_order_free' => $this->boolean('delivery_first_order_free'),
            'delivery_hide_subtitle' => $this->boolean('delivery_hide_subtitle'),
            'delivery_notes_enabled' => $this->boolean('delivery_notes_enabled'),
            'pickup_enabled' => $this->boolean('pickup_enabled'),
            'delivery_general_note' => $this->filled('delivery_general_note')
                ? trim((string) $this->input('delivery_general_note'))
                : '',
            'delivery_store_lat' => $this->filled('delivery_store_lat') ? $this->input('delivery_store_lat') : null,
            'delivery_store_lng' => $this->filled('delivery_store_lng') ? $this->input('delivery_store_lng') : null,
            'delivery_max_km' => $this->filled('delivery_max_km') ? $this->input('delivery_max_km') : null,
        ]);
    }
}
