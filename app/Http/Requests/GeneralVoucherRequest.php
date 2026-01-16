<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GeneralVoucherRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'date' => ['required', 'date'],
            'branch_id' => ['required', 'integer'],
            'source' => ['required', 'string'],
            'person_name' => ['nullable', 'string', 'max:100'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'remarks' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'entries' => ['required', 'array', 'min:2'],
            'entries.*.account_id' => ['required', 'integer'],
            'entries.*.debit' => ['required', 'numeric', 'min:0'],
            'entries.*.credit' => ['required', 'numeric', 'min:0'],
            'entries.*.description' => ['nullable', 'string', 'max:255'],
            'entries.*.person_name' => ['nullable', 'string', 'max:100'],
        ];

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'date.required' => 'The Date field is required.',
            'date.date' => 'The Date must be a valid date.',
            'branch_id.required' => 'The Branch field is required.',
            'entries.required' => 'At least two journal entries are required.',
            'entries.min' => 'At least two journal entries are required.',
            'entries.*.account_id.required' => 'The Account field is required for all entries.',
            'entries.*.debit.required' => 'The Debit field is required.',
            'entries.*.debit.numeric' => 'The Debit must be a number.',
            'entries.*.debit.min' => 'The Debit must be at least 0.',
            'entries.*.credit.required' => 'The Credit field is required.',
            'entries.*.credit.numeric' => 'The Credit must be a number.',
            'entries.*.credit.min' => 'The Credit must be at least 0.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $entries = $this->input('entries', []);

            // Validate each entry cannot have both debit and credit > 0
            foreach ($entries as $index => $entry) {
                if (($entry['debit'] ?? 0) > 0 && ($entry['credit'] ?? 0) > 0) {
                    $validator->errors()->add(
                        "entries.{$index}",
                        "Entry #" . ($index + 1) . " cannot have both debit and credit amounts."
                    );
                }
            }

            // Validate total debits equal total credits
            $totalDebits = array_sum(array_column($entries, 'debit'));
            $totalCredits = array_sum(array_column($entries, 'credit'));

            if (abs($totalDebits - $totalCredits) > 0.01) {
                $validator->errors()->add(
                    'entries',
                    'Total debits must equal total credits.'
                );
            }

            // Validate at least one entry with debit > 0 and one with credit > 0
            $hasDebit = false;
            $hasCredit = false;
            foreach ($entries as $entry) {
                if (($entry['debit'] ?? 0) > 0) {
                    $hasDebit = true;
                }
                if (($entry['credit'] ?? 0) > 0) {
                    $hasCredit = true;
                }
            }

            if (!$hasDebit || !$hasCredit) {
                $validator->errors()->add(
                    'entries',
                    'At least one entry must have a debit amount and one must have a credit amount.'
                );
            }
        });
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // If data comes as 'journals' object, merge it to root level
        if ($this->has('journals')) {
            $journals = $this->input('journals');
            $this->merge([
                'date' => $journals['date'] ?? null,
                'branch_id' => $journals['branch_id'] ?? null,
                'source' => $journals['source'] ?? 'General Voucher',
                'person_name' => $journals['person_name'] ?? null,
                'reference_number' => $journals['reference_number'] ?? null,
                'remarks' => $journals['remarks'] ?? null,
                'description' => $journals['description'] ?? null,
            ]);
        }
    }
}
