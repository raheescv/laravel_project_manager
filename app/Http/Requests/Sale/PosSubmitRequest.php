<?php

namespace App\Http\Requests\Sale;

use App\Models\Account;
use App\Models\Configuration;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The Vue POS submit (POST /pos/submit).
 *
 * A completed sale must name how it was paid: 'credit' explicitly, 'custom' with
 * at least one tender, or one of the tenant's payment-method accounts. A missing
 * or unknown id is refused here instead of falling through to an unpaid sale —
 * a null Card id (e.g. from a stale account slug cache) used to save as credit.
 */
class PosSubmitRequest extends FormRequest
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
        $isCompleted = $this->input('status') === 'completed';
        $paymentMethod = $this->input('payment_method');
        $isCustom = $paymentMethod === 'custom';
        $isAccountTender = $isCompleted && ! in_array($paymentMethod, ['credit', 'custom'], true);

        $rules = [
            'status' => ['required', 'string'],
            'payment_method' => ['required_if:status,completed', Rule::when($isAccountTender, [Rule::in($this->paymentMethodIds())])],
            'custom_payment_data.payments' => Rule::when($isCompleted && $isCustom, ['required', 'array', 'min:1']),
            'custom_payment_data.payments.*.payment_method_id' => Rule::when($isCompleted && $isCustom, ['required', Rule::in($this->paymentMethodIds())]),
        ];

        // Without day sessions the sale takes the date the cashier picked, so
        // that date has to be real and not in the future.
        if (posSkipsDaySession()) {
            $rules['date'] = ['required', 'date_format:Y-m-d', 'before_or_equal:today'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date.before_or_equal' => 'The sale date cannot be in the future.',
            'payment_method.required_if' => 'Choose how this sale was paid, or pick Credit.',
            'payment_method.in' => 'The selected payment method is not set up for this account. Reload the POS and choose it again.',
            'custom_payment_data.payments.required' => 'Add at least one payment, or pick Credit.',
            'custom_payment_data.payments.min' => 'Add at least one payment, or pick Credit.',
            'custom_payment_data.payments.*.payment_method_id.required' => 'Every payment needs a payment method.',
            'custom_payment_data.payments.*.payment_method_id.in' => 'A payment uses a method that is not set up for this account. Reload the POS and choose it again.',
        ];
    }

    /**
     * The tenant's configured payment methods plus its locked Cash / Card
     * accounts, read from the database rather than the slug cache.
     *
     * @return array<int, int>
     */
    protected function paymentMethodIds(): array
    {
        $configured = json_decode((string) Configuration::where('key', 'payment_methods')->value('value'), true) ?: [];
        $cashAndCard = Account::query()->where('is_locked', 1)->whereIn('slug', ['cash', 'card'])->pluck('id')->all();

        return array_values(array_unique(array_map('intval', [...$configured, ...$cashAndCard])));
    }
}
