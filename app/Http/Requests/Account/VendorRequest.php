<?php

namespace App\Http\Requests\Account;

use App\Enums\Account\VendorType;
use App\Models\Account;
use App\Services\TenantService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VendorRequest extends FormRequest
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
        $vendorId = $this->route('id');

        return [
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique(Account::class, 'name')
                    ->where('tenant_id', app(TenantService::class)->getCurrentTenantId())
                    ->where('model', 'Vendor')
                    ->whereNull('deleted_at')
                    ->ignore($vendorId),
            ],
            'alias_name' => ['nullable', 'string', 'max:100'],
            'vendor_type' => ['nullable', Rule::enum(VendorType::class)],
            'credit_period_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'cr_number' => ['nullable', 'string', 'max:50'],
            'tax_no' => ['nullable', 'string', 'max:50'],
            'mobile' => ['required', 'string', 'max:15'],
            'whatsapp_mobile' => ['nullable', 'string', 'max:15'],
            'emergency_contact_no' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:50'],
            'po_box' => ['nullable', 'string', 'max:30'],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'place' => ['nullable', 'string', 'max:100'],
            'id_no' => ['nullable', 'string', 'max:50'],
            'id_expiry_date' => ['nullable', 'date'],
            'passport_no' => ['nullable', 'string', 'max:50'],
            'occupation' => ['nullable', 'string', 'max:100'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'company' => ['nullable', 'string', 'max:150'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Enter the vendor name.',
            'name.unique' => 'A vendor with this name already exists.',
            'mobile.required' => 'Enter the vendor mobile number.',
            'email.email' => 'Enter a valid email address.',
        ];
    }
}
