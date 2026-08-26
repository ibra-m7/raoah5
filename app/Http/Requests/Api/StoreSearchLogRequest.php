<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreSearchLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'query' => ['required', 'string', 'max:191'],
            'matched_product_id' => ['nullable', 'integer', 'min:1'],
            'results_count' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'source' => ['nullable', 'string', 'max:32'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'query' => trim((string) $this->input('query', '')),
            'source' => $this->filled('source') ? trim((string) $this->input('source')) : 'app',
        ]);
    }
}
