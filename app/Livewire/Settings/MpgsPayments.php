<?php

namespace App\Livewire\Settings;

use App\Models\Account;
use App\Models\Configuration;
use App\Models\User;
use App\Services\Payment\MpgsClient;
use App\Services\Payment\MpgsException;
use App\Support\Payment\MpgsSettings;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;

/**
 * Settings → Student Cards → Credit card top-ups: the school's Mastercard Gateway
 * (MPGS) merchant, beside the QPay block that takes debit cards. Each has its own
 * switch, credentials, bank account and recording user, and the parent portal
 * offers each card type only when its block is switched on and complete.
 *
 * The API password is stored encrypted and never rendered.
 */
class MpgsPayments extends Component
{
    public bool $enabled = false;

    public string $gateway_url = MpgsSettings::DEFAULT_GATEWAY_URL;

    public string $merchant_id = '';

    /** A newly typed API password. Left blank, the saved one is kept. */
    public string $api_password = '';

    public string $merchant_name = '';

    public string $payment_account_id = '';

    public string $user_id = '';

    public ?string $saved_password_hint = null;

    public function mount(): void
    {
        $settings = MpgsSettings::current();
        $this->enabled = $settings->enabled;
        $this->gateway_url = $settings->gatewayUrl;
        $this->merchant_id = (string) $settings->merchantId;
        $this->merchant_name = (string) ($settings->merchantName ?: mb_substr(tenant_cache('company_name', '') ?: config('app.name'), 0, 40));
        $this->payment_account_id = (string) ($settings->paymentAccountId ?? '');
        $this->user_id = (string) ($settings->userId ?? '');
        $this->saved_password_hint = MpgsSettings::hint($settings->apiPassword);
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->can('student settings.edit'), 403);

        try {
            $password = trim($this->api_password);
            $saved = MpgsSettings::current()->apiPassword;
            $url = MpgsSettings::normaliseUrl($this->gateway_url);

            if (! $url) {
                throw new Exception('Enter the gateway address as an https:// link, e.g. '.MpgsSettings::DEFAULT_GATEWAY_URL.'.');
            }
            if (trim($this->merchant_id) !== '' && ! preg_match('/^[A-Za-z0-9_-]{1,40}$/', trim($this->merchant_id))) {
                throw new Exception('The Merchant ID has only letters, digits, - and _.');
            }
            if ($this->payment_account_id !== '' && ! $this->paymentAccounts()->contains('id', (int) $this->payment_account_id)) {
                throw new Exception('Choose one of the configured payment methods.');
            }
            if ($this->user_id !== '' && ! User::query()->whereKey((int) $this->user_id)->exists()) {
                throw new Exception('Choose a valid user.');
            }
            if ($this->enabled) {
                foreach (['merchant_id' => 'the Merchant ID', 'merchant_name' => 'the name shown on the payment page', 'payment_account_id' => 'the account credit card top-ups are paid into', 'user_id' => 'the user top-ups are recorded under'] as $field => $label) {
                    if (trim($this->{$field}) === '') {
                        throw new Exception("Enter {$label} to switch on credit card top-ups.");
                    }
                }
                if ($password === '' && ! $saved) {
                    throw new Exception('Enter the API password to switch on credit card top-ups.');
                }
            }

            DB::transaction(function () use ($url, $password): void {
                Configuration::updateOrCreate(['key' => MpgsSettings::KEY], ['value' => json_encode([
                    'enabled' => $this->enabled,
                    'gateway_url' => $url,
                    'merchant_id' => trim($this->merchant_id) ?: null,
                    'merchant_name' => mb_substr(trim($this->merchant_name), 0, 40) ?: null,
                    'payment_account_id' => $this->payment_account_id !== '' ? (int) $this->payment_account_id : null,
                    'user_id' => $this->user_id !== '' ? (int) $this->user_id : null,
                ])]);
                if ($password !== '') {
                    Configuration::updateOrCreate(['key' => MpgsSettings::PASSWORD_KEY], ['value' => MpgsSettings::encryptSecret($password)]);
                }
            });

            $this->gateway_url = $url;
            $this->api_password = '';
            $this->saved_password_hint = MpgsSettings::hint($password !== '' ? $password : $saved);
            $this->dispatch('success', ['message' => 'Credit card settings saved']);
        } catch (\Throwable $th) {
            $this->dispatch('error', ['message' => $th->getMessage()]);
        }
    }

    /**
     * Sign in to the gateway with the SAVED Merchant ID and password: ask for an
     * order that cannot exist. "No such order" means the credentials work; a 401
     * means they do not. Nothing is created at the gateway.
     */
    public function testConnection(): void
    {
        abort_unless(auth()->user()?->can('student settings.edit'), 403);

        $settings = MpgsSettings::current();
        if (! $settings->merchantId || ! $settings->apiPassword) {
            $this->dispatch('error', ['message' => 'Save the Merchant ID and API password first.']);

            return;
        }

        try {
            (new MpgsClient($settings))->retrieveOrder('CONNTEST'.Str::upper(Str::random(12)));
            $this->dispatch('success', ['message' => 'Connected to the gateway as '.$settings->merchantId.($settings->isTest() ? ' (test merchant — no real cards are charged).' : ' (LIVE merchant).')]);
        } catch (MpgsException $e) {
            $this->dispatch('error', ['message' => 'The gateway said: '.$e->getMessage()]);
        } catch (\Throwable $th) {
            report($th);
            $this->dispatch('error', ['message' => 'Could not reach the gateway: '.$th->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.settings.mpgs-payments', [
            'paymentAccounts' => $this->paymentAccounts(),
            'users' => User::query()->where('is_active', 1)->orderBy('name')->get(['id', 'name']),
            'isTest' => str_starts_with(strtoupper(trim($this->merchant_id)), 'TEST'),
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
