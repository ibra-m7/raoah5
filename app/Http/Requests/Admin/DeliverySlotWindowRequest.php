<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeliverySlotWindowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        return [
            'weekday' => [
                Rule::requiredIf($isUpdate || ! $this->filled('weekdays')),
                'nullable',
                'integer',
                'min:0',
                'max:6',
            ],
            'weekdays' => [
                Rule::requiredIf(! $isUpdate && ! $this->filled('weekday')),
                'nullable',
                'array',
                'min:1',
            ],
            'weekdays.*' => ['integer', 'min:0', 'max:6', 'distinct'],
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
            'weekdays' => 'الأيام',
            'weekdays.*' => 'اليوم',
            'start_time' => 'من',
            'end_time' => 'إلى',
        ];
    }

    /**
     * @return list<int>
     */
    public function weekdays(): array
    {
        if ($this->filled('weekdays')) {
            return array_values(array_unique(array_map('intval', $this->input('weekdays', []))));
        }

        return [(int) $this->input('weekday')];
    }

    protected function prepareForValidation(): void
    {
        $start = $this->normalizeTime($this->input('start_time'));
        $end = $this->normalizeTime($this->input('end_time'));

        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'start_time' => $start,
            'end_time' => $end,
            'sort_order' => $this->input('sort_order', 0),
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
