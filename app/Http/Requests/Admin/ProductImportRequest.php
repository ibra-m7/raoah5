<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ProductImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:10240'],
            'images_zip' => ['nullable', 'file', 'max:51200'],
        ];
    }

    public function attributes(): array
    {
        return [
            'file' => 'ملف الاستيراد',
            'images_zip' => 'ملف صور ZIP',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $file = $this->file('file');
            if ($file) {
                $ext = strtolower($file->getClientOriginalExtension());
                if (! in_array($ext, ['xls', 'xlsx', 'csv', 'txt', 'xml'], true)) {
                    $validator->errors()->add('file', 'الصيغة المسموحة: xls أو xlsx أو csv.');
                }
            }

            $zip = $this->file('images_zip');
            if ($zip) {
                $ext = strtolower($zip->getClientOriginalExtension());
                if ($ext !== 'zip') {
                    $validator->errors()->add('images_zip', 'ملف الصور يجب أن يكون بصيغة zip.');
                }
            }
        });
    }
}
