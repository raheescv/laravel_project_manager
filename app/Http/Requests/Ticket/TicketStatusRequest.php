<?php

namespace App\Http\Requests\Ticket;

use App\Services\TenantService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TicketStatusRequest extends FormRequest
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
        $creating = $this->isMethod('post');

        return [
            'name' => [
                $creating ? 'required' : 'sometimes', 'string', 'max:50',
                Rule::unique('ticket_statuses', 'name')
                    ->where('tenant_id', app(TenantService::class)->getCurrentTenantId())
                    ->ignore($this->route('statusId')),
            ],
            'color' => [$creating ? 'required' : 'sometimes', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'icon' => [$creating ? 'required' : 'sometimes', 'string', 'max:40', 'regex:/^fa-[a-z0-9-]+$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Give the column a name.',
            'name.unique' => 'There is already a column with this name.',
            'color.regex' => 'Pick a colour from the palette.',
            'icon.regex' => 'Pick an icon from the list.',
        ];
    }
}
