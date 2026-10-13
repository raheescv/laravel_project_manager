<?php

namespace App\Http\Requests\ProjectContract;

use Illuminate\Foundation\Http\FormRequest;

class DocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'files' => ['required', 'array', 'min:1', 'max:10'],
            'files.*' => ['file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx,csv'],
            'document_type' => ['nullable', 'string', 'max:60'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'files.required' => 'Choose at least one file.',
            'files.max' => 'Upload at most 10 files at a time.',
            'files.*.max' => 'Each file must be 10 MB or smaller.',
            'files.*.mimes' => 'Upload PDF, image, Word or Excel files.',
        ];
    }
}
