<?php

namespace App\Livewire\Settings;

use App\Models\Account;
use App\Models\Configuration;
use App\Models\User;
use App\Support\Payment\QPaySettings;
use Exception;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * Settings → Student Cards → QPay: the school's QCB EZ-Connect merchant for
 * parents' online top-ups. The secret key is stored encrypted and never rendered.
 */
class QPayPayments extends Component
{
    public bool $enabled = false;

    public string $environment = 'staging';

    public string $bank_id = '';

    public string $merchant_id = '';

    /** A newly typed secret key. Left blank, the saved key is kept. */
    public string $secret_key = '';

    public string $payment_account_id = '';

    public string $user_id = '';

    public ?string $saved_key_hint = null;

    public function mount(): void
    {
        $settings = QPaySettings::current();
        $this->enabled = $settings->enabled;
        $this->environment = $settings->environment;
        $this->bank_id = (string) $settings->bankId;
        $this->merchant_id = (string) $settings->merchantId;
        $this->payment_account_id = (string) ($settings->paymentAccountId ?? '');
        $this->user_id = (string) ($settings->userId ?? '');
        $this->saved_key_hint = QPaySettings::hint($settings->secretKey);
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->can('student settings.edit'), 403);

        try {
            $secret = trim($this->secret_key);
            $saved = QPaySettings::current()->secretKey;

            if ($this->payment_account_id !== '' && ! $this->paymentAccounts()->contains('id', (int) $this->payment_account_id)) {
                throw new Exception('Choose one of the configured payment methods.');
            }
            if ($this->user_id !== '' && ! User::query()->whereKey((int) $this->user_id)->exists()) {
                throw new Exception('Choose a valid user.');
            }
            if ($this->enabled) {
                // Worded after the labels on screen, so the missing field can be found.
                foreach ([
                    'bank_id' => 'Enter the Bank ID',
                    'merchant_id' => 'Enter the Merchant ID',
                    'payment_account_id' => 'Choose an account under "Debit card top-ups are paid into"',
                    'user_id' => 'Choose a user under "Record top-ups as"',
                ] as $field => $ask) {
                    if (trim($this->{$field}) === '') {
                        throw new Exception("{$ask} to switch on QPay top-ups.");
                    }
                }
                if ($secret === '' && ! $saved) {
                    throw new Exception('Enter the QPay secret key to switch on top-ups.');
                }
            }

            DB::beginTransaction();
            Configuration::updateOrCreate(['key' => QPaySettings::KEY], ['value' => json_encode([
                'enabled' => $this->enabled,
                'environment' => $this->environment === 'production' ? 'production' : 'staging',
                'bank_id' => trim($this->bank_id) ?: null,
                'merchant_id' => trim($this->merchant_id) ?: null,
                'payment_account_id' => $this->payment_account_id !== '' ? (int) $this->payment_account_id : null,
                'user_id' => $this->user_id !== '' ? (int) $this->user_id : null,
            ])]);
            if ($secret !== '') {
                Configuration::updateOrCreate(['key' => QPaySettings::SECRET_KEY], ['value' => QPaySettings::encryptSecret($secret)]);
            }
            DB::commit();

            $this->secret_key = '';
            $this->saved_key_hint = QPaySettings::hint($secret !== '' ? $secret : $saved);
            $this->dispatch('success', ['message' => 'QPay settings saved']);
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->dispatch('error', ['message' => $th->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.settings.q-pay-payments', [
            'paymentAccounts' => $this->paymentAccounts(),
            'users' => User::query()->where('is_active', 1)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    private function paymentAccounts()
    {
        return Account::query()
            ->whereIn('id', tenant_cache('payment_methods', []) ?: [])
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
