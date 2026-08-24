<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class DeliverySlotWindowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'weekday' => ['required', 'integer', 'min:0', 'max:6'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'weekday' => 'اليوم',
            'start_time' => 'من',
            'end_time' => 'إلى',
        ];
    }

    protected function prepareForValidation(): void
    {
        $start = $this->normalizeTime($this->input('start_time'));
        $end = $this->normalizeTime($this->input('end_time'));

        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'start_time' => $start,
            'end_time' => $end,
        ]);
    }

    private function normalizeTime(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $value) === 1) {
            return substr($value, 0, 5);
        }

        return $value;
    }
}
