<?php

namespace App\Livewire\Settings;

use App\Models\Account;
use App\Models\Branch;
use App\Models\Configuration;
use App\Models\User;
use App\Support\Storefront\TapSettings;
use Exception;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * Settings → Online Payments: the tenant's Tap Payments account for storefront checkout.
 */
class OnlinePayments extends Component
{
    public bool $enabled = false;

    /** A newly typed secret key. Left blank, the saved key is kept. */
    public string $secret_key = '';

    public string $merchant_id = '';

    /** Payment-method account a paid online order is recorded against. */
    public string $payment_account_id = '';

    /** User the online sale is created by (and the employee on its lines). */
    public string $user_id = '';

    /** Branch delivery orders draw stock from; blank means collect in shop only. */
    public string $delivery_branch_id = '';

    /** Masked form of the saved key, e.g. sk_test_…y1x; null when none is saved. */
    public ?string $saved_key_hint = null;

    public bool $live_mode = false;

    public function mount(): void
    {
        $settings = TapSettings::current();

        $this->enabled = $settings->enabled;
        $this->merchant_id = (string) $settings->merchantId;
        $this->payment_account_id = (string) ($settings->paymentAccountId ?? '');
        $this->user_id = (string) ($settings->userId ?? '');
        $this->delivery_branch_id = (string) ($settings->deliveryBranchId ?? '');
        $this->showKey($settings->secretKey);
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->can('configuration.settings'), 403);

        try {
            $secret = trim($this->secret_key);
            $savedSecret = TapSettings::current()->secretKey;

            if ($secret !== '' && ! preg_match('/^sk_(test|live)_[A-Za-z0-9]+$/', $secret)) {
                throw new Exception('The secret key starts with sk_test_ or sk_live_. The public key (pk_…) is not needed.');
            }
            if ($this->payment_account_id !== '' && ! $this->paymentAccounts()->contains('id', (int) $this->payment_account_id)) {
                throw new Exception('Choose one of the configured payment methods.');
            }
            if ($this->user_id !== '' && ! User::query()->whereKey((int) $this->user_id)->exists()) {
                throw new Exception('Choose a valid user for online sales.');
            }
            if ($this->delivery_branch_id !== '' && ! Branch::query()->whereKey((int) $this->delivery_branch_id)->exists()) {
                throw new Exception('Choose a valid delivery branch.');
            }
            if ($this->enabled) {
                if ($secret === '' && ! $savedSecret) {
                    throw new Exception('Enter your Tap secret key to switch on online payments.');
                }
                if ($this->payment_account_id === '') {
                    throw new Exception('Choose the payment method online payments are recorded in.');
                }
                if ($this->user_id === '') {
                    throw new Exception('Choose the user online sales are recorded under.');
                }
            }

            DB::beginTransaction();
            Configuration::updateOrCreate(['key' => TapSettings::KEY], ['value' => json_encode([
                'enabled' => $this->enabled,
                'merchant_id' => trim($this->merchant_id) ?: null,
                'payment_account_id' => $this->payment_account_id !== '' ? (int) $this->payment_account_id : null,
                'user_id' => $this->user_id !== '' ? (int) $this->user_id : null,
                'delivery_branch_id' => $this->delivery_branch_id !== '' ? (int) $this->delivery_branch_id : null,
            ])]);
            if ($secret !== '') {
                Configuration::updateOrCreate(['key' => TapSettings::SECRET_KEY], ['value' => TapSettings::encryptSecret($secret)]);
            }
            DB::commit();

            $this->secret_key = '';
            $this->showKey($secret !== '' ? $secret : $savedSecret);
            $this->dispatch('success', ['message' => 'Online payment settings saved']);
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->dispatch('error', ['message' => $th->getMessage()]);
        }
    }

    /** Forget the saved key (e.g. after rotating it in Tap). Online payments switch off with it. */
    public function removeSecretKey(): void
    {
        abort_unless(auth()->user()?->can('configuration.settings'), 403);

        try {
            DB::beginTransaction();
            Configuration::where('key', TapSettings::SECRET_KEY)->delete();
            $config = json_decode((string) Configuration::where('key', TapSettings::KEY)->value('value'), true) ?: [];
            Configuration::updateOrCreate(['key' => TapSettings::KEY], ['value' => json_encode(['enabled' => false] + $config)]);
            DB::commit();

            $this->enabled = false;
            $this->showKey(null);
            $this->dispatch('success', ['message' => 'Tap secret key removed — online payments are off']);
        } catch (\Throwable $th) {
            DB::rollBack();
            $this->dispatch('error', ['message' => $th->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.settings.online-payments', [
            'paymentAccounts' => $this->paymentAccounts(),
            'users' => User::query()->where('is_active', 1)->orderBy('name')->get(['id', 'name']),
            'branches' => Branch::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    private function paymentAccounts()
    {
        return Account::query()
            ->whereIn('id', tenant_cache('payment_methods', []) ?: [])
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function showKey(?string $secret): void
    {
        $this->saved_key_hint = TapSettings::hint($secret);
        $this->live_mode = str_starts_with((string) $secret, 'sk_live_');
    }
}
