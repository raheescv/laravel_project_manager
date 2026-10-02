<?php

namespace App\Http\Requests\Ticket;

use App\Models\Ticket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TicketRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(array_keys(Ticket::statuses()))],
            'group' => ['nullable', 'string', 'max:100'],
            'files' => ['nullable', 'array', 'max:10'],
            'files.*' => ['file', 'mimes:jpg,jpeg,png,gif,webp,mp4,mov,avi,wmv,mkv,pdf,doc,docx,xls,xlsx', 'max:51200'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Give the ticket a title.',
            'files.max' => 'Attach at most 10 files at a time.',
            'files.*.mimes' => 'Attachments must be images, videos, PDFs or Office files.',
            'files.*.max' => 'Each attachment must be 50 MB or smaller.',
        ];
    }
}
