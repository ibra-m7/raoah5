<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SearchSmartSuggestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phrase' => ['required', 'string', 'max:80'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'phrase' => 'الاقتراح',
            'sort_order' => 'الترتيب',
            'is_active' => 'التفعيل',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'phrase' => $this->filled('phrase') ? trim((string) $this->input('phrase')) : '',
            'sort_order' => $this->filled('sort_order') ? $this->input('sort_order') : 0,
            'form' => 'smart',
            'editing_id' => $this->input('editing_id'),
        ]);
    }
}
