<?php

namespace App\Livewire\Settings;

use App\Models\Account;
use App\Models\Configuration;
use App\Models\Country;
use App\Support\TenantCache;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Configurations extends Component
{
    public $payment_methods;

    public $default_payment_method_id;

    public $paymentMethods;

    /** @var array<int, int|string|null> Account ids for POS buttons 1–4; buttons 3 and 4 are optional. */
    public $pos_payment_buttons = [null, null, null, null];

    /** @var array<int, string|null> Icon key (posPaymentButtonIcons()) per button; null picks one from the name. */
    public $pos_payment_button_icons = [null, null, null, null];

    /** @var array<int, string> */
    public $posButtonOptions = [];

    public $country_id;

    public $countries;

    public function mount()
    {
        $this->payment_methods = Configuration::where('key', 'payment_methods')->value('value');
        $this->default_payment_method_id = Configuration::where('key', 'default_payment_method_id')->value('value') ?? 1;
        $this->country_id = Configuration::where('key', 'country_id')->value('value');
        $this->payment_methods = json_decode($this->payment_methods, true);
        $this->paymentMethods = [];
        if ($this->payment_methods) {
            $this->paymentMethods = Account::whereIn('id', $this->payment_methods)->pluck('name', 'id')->toArray();
        }

        $stored = json_decode((string) Configuration::where('key', 'pos_payment_buttons')->value('value'), true) ?: [];
        $slugIds = Account::slugIdMap();
        $this->pos_payment_buttons = [
            $stored[0] ?? $slugIds['cash'] ?? null,
            $stored[1] ?? $slugIds['card'] ?? null,
            $stored[2] ?? null,
            $stored[3] ?? null,
        ];
        $icons = json_decode((string) Configuration::where('key', 'pos_payment_button_icons')->value('value'), true) ?: [];
        $this->pos_payment_button_icons = [$icons[0] ?? null, $icons[1] ?? null, $icons[2] ?? null, $icons[3] ?? null];
        $this->loadPosButtonOptions();

        // Load countries for dropdown
        $this->countries = Country::orderBy('name')->pluck('name', 'id')->toArray();
    }

    /**
     * The icon button [slot] shows: the one picked, else what "Auto" renders
     * for the account it holds (Cash / Card when left on the default).
     */
    public function buttonIcon(int $slot): string
    {
        $picked = $this->pos_payment_button_icons[$slot] ?? null;
        if ($picked && array_key_exists($picked, posPaymentButtonIcons())) {
            return $picked;
        }
        $id = $this->pos_payment_buttons[$slot] ?? null;
        $name = $id ? ($this->posButtonOptions[(int) $id] ?? '') : ['Cash', 'Card', '', ''][$slot];

        return posPaymentAutoIcon($name);
    }

    public function updatedPaymentMethods(): void
    {
        $this->loadPosButtonOptions();
    }

    /**
     * What a POS payment button may hold: the configured payment methods plus
     * the locked Cash and Card accounts the POS has always offered.
     */
    protected function loadPosButtonOptions(): void
    {
        $ids = array_map('intval', array_filter((array) ($this->payment_methods ?: [])));
        $this->posButtonOptions = Account::query()
            ->where(fn ($query) => $query->whereIn('id', $ids)->orWhere(fn ($query) => $query->where('is_locked', 1)->whereIn('slug', ['cash', 'card'])))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    public function dbView()
    {
        abort_unless(Auth::user()?->can('configuration.settings'), 403);
        Artisan::call('db:seed --class=View');
    }

    public function dbProcedure()
    {
        abort_unless(Auth::user()?->can('configuration.settings'), 403);
        try {
            Artisan::call('db:ensure-procedures');
            if (str_contains(Artisan::output(), '✗')) {
                $this->dispatch('error', ['message' => 'Procedure re-create failed. Please check the logs.']);

                return;
            }
            $this->dispatch('success', ['message' => 'Database procedures re-created successfully']);
        } catch (\Throwable $e) {
            $this->dispatch('error', ['message' => 'Procedure re-create failed: '.$e->getMessage()]);
        }
    }

    public function save()
    {
        abort_unless(Auth::user()?->can('configuration.settings'), 403);
        Configuration::updateOrCreate(['key' => 'default_payment_method_id'], ['value' => $this->default_payment_method_id]);
        Configuration::updateOrCreate(['key' => 'payment_methods'], ['value' => json_encode($this->payment_methods)]);
        $this->loadPosButtonOptions();
        $buttons = array_map(fn ($id) => isset($this->posButtonOptions[(int) $id]) ? (int) $id : null, array_slice(array_pad((array) $this->pos_payment_buttons, 4, null), 0, 4));
        Configuration::updateOrCreate(['key' => 'pos_payment_buttons'], ['value' => json_encode($buttons)]);
        $icons = array_map(fn ($icon) => array_key_exists((string) $icon, posPaymentButtonIcons()) ? $icon : null, array_slice(array_pad((array) $this->pos_payment_button_icons, 4, null), 0, 4));
        Configuration::updateOrCreate(['key' => 'pos_payment_button_icons'], ['value' => json_encode($icons)]);
        $country = Country::find($this->country_id);
        if ($country) {
            Configuration::updateOrCreate(['key' => 'currency_code'], ['value' => $country->currency_code]);
            Configuration::updateOrCreate(['key' => 'currency_symbol'], ['value' => $country->currency_symbol]);
        }
        Configuration::updateOrCreate(['key' => 'country_id'], ['value' => $this->country_id]);
        TenantCache::forget('payment_methods');
        TenantCache::forget('country_id');
        TenantCache::forget('currency_code');
        TenantCache::forget('currency_symbol');
        $this->dispatch('success', ['message' => 'Updated Successfully']);
    }

    public function render()
    {
        return view('livewire.settings.configurations');
    }
}
