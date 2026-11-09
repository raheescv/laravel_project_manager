<?php

namespace App\Http\Requests\V1\Parent;

use App\Models\StudentDetail;
use App\Services\TenantService;
use App\Support\Student\StudentSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The public registration form. The registration number is checked against
 * existing students so a parent can correct a typing mistake straight away; a QID
 * is not, because that would let anyone test whose QID is on file.
 */
class StoreRegistrationRequest extends FormRequest
{
    public const MAX_STUDENTS = 10;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $settings = StudentSettings::current();
        $tenantId = app(TenantService::class)->getCurrentTenantId();

        return [
            'school' => ['required', 'string', Rule::in($settings->registrationSchools)],
            'parent.name' => ['required', 'string', 'min:2', 'max:100'],
            'parent.qid' => ['required', 'digits:11'],
            'parent.phone' => ['required', 'digits:8'],
            'parent.email' => ['required', 'email', 'max:150'],
            'students' => ['required', 'array', 'min:1', 'max:'.self::MAX_STUDENTS],
            'students.*.name' => ['required', 'string', 'min:2', 'max:100'],
            'students.*.qid' => ['required', 'digits:11', 'distinct'],
            'students.*.class' => ['required', 'string', Rule::in($settings->registrationClasses)],
            'students.*.div' => ['required', 'string', Rule::in($settings->registrationDivisions)],
            'students.*.reg' => [
                'required', 'string', 'max:30', 'distinct:ignore_case',
                Rule::unique(StudentDetail::class, 'admission_no')->where('tenant_id', $tenantId),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'school.*' => 'Please choose a school.',
            'parent.name.*' => 'Enter your full name.',
            'parent.qid.*' => 'A QID has 11 digits.',
            'parent.phone.*' => 'Enter your 8-digit mobile number.',
            'parent.email.*' => 'Enter a full email, like name@example.com.',
            'students.required' => 'Add at least one student.',
            'students.max' => 'Please send up to '.self::MAX_STUDENTS.' students on one form.',
            'students.*.name.*' => "Enter your child's full name.",
            'students.*.qid.distinct' => 'You already added a child with this QID.',
            'students.*.qid.*' => 'A QID has 11 digits.',
            'students.*.class.*' => 'Choose a class.',
            'students.*.div.*' => 'Choose a division.',
            'students.*.reg.distinct' => 'You already added a child with this number.',
            'students.*.reg.unique' => 'This number is already registered. Please check it, or contact the school office.',
            'students.*.reg.*' => 'Enter the registration number.',
        ];
    }
}
