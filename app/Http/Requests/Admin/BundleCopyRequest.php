<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class BundleCopyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'section_title' => ['nullable', 'string', 'max:255'],
            'product_names' => ['nullable', 'array', 'max:20'],
            'product_names.*' => ['string', 'max:120'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'اسم السلة',
        ];
    }

    protected function prepareForValidation(): void
    {
        $names = $this->input('product_names', []);
        if (is_string($names)) {
            $names = preg_split('/\r\n|\r|\n|,|،/', $names) ?: [];
        }

        $cleaned = [];
        foreach ((array) $names as $name) {
            $text = trim((string) $name);
            if ($text !== '') {
                $cleaned[] = mb_substr($text, 0, 120);
            }
        }

        $this->merge([
            'product_names' => array_values(array_unique(array_slice($cleaned, 0, 20))),
            'section_title' => trim((string) $this->input('section_title', '')) ?: null,
        ]);
    }
}
