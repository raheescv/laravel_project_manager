<?php

namespace App\Http\Requests\Ticket;

use App\Models\Ticket;
use App\Support\Ticket\TicketImportSheet;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TicketImportRequest extends FormRequest
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
        $rules = [
            'token' => ['required', 'string', 'uuid'],
            'mappings' => ['required', 'array'],
            'options.default_status' => ['nullable', Rule::in(array_keys(Ticket::statuses()))],
            'options.duplicates' => ['nullable', Rule::in(['skip', 'import'])],
        ];
        foreach (array_keys(TicketImportSheet::fields()) as $field) {
            $rules["mappings.{$field}"] = ['nullable', 'integer', 'min:0'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'token.required' => 'Upload the sheet again — the previous upload has expired.',
        ];
    }
}
