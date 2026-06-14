<?php

namespace App\Livewire\Settings;

use App\Models\Account;
use App\Models\Configuration;
use App\Models\Country;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class Configurations extends Component
{
    public $payment_methods;

    public $default_payment_method_id;

    public $paymentMethods;

    public $country_id;

    public $countries;

    public function mount()
    {
        $this->payment_methods = Configuration::where('key', 'payment_methods')->value('value');
        $this->default_payment_method_id = Configuration::where('key', 'default_payment_method_id')->value('value') ?? 1;
        $this->country_id = Configuration::where('key', 'country_id')->value('value');
        $this->payment_methods = json_decode($this->payment_methods, 1);
        $this->paymentMethods = [];
        if ($this->payment_methods) {
            $this->paymentMethods = Account::whereIn('id', $this->payment_methods)->pluck('name', 'id')->toArray();
        }

        // Load countries for dropdown
        $this->countries = Country::orderBy('name')->pluck('name', 'id')->toArray();
    }

    public function dbView()
    {
        abort_unless(auth()->user()?->can('configuration.settings'), 403);
        Artisan::call('db:seed --class=View');
    }

    public function save()
    {
        abort_unless(auth()->user()?->can('configuration.settings'), 403);
        Configuration::updateOrCreate(['key' => 'default_payment_method_id'], ['value' => $this->default_payment_method_id]);
        Configuration::updateOrCreate(['key' => 'payment_methods'], ['value' => json_encode($this->payment_methods)]);
        $country = Country::find($this->country_id);
        if ($country) {
            Configuration::updateOrCreate(['key' => 'currency_code'], ['value' => $country->currency_code]);
            Configuration::updateOrCreate(['key' => 'currency_symbol'], ['value' => $country->currency_symbol]);
        }
        Configuration::updateOrCreate(['key' => 'country_id'], ['value' => $this->country_id]);
        Cache::forget('payment_methods');
        Cache::forget('country_id');
        Cache::forget('currency_code');
        Cache::forget('currency_symbol');
        $this->dispatch('success', ['message' => 'Updated Successfully']);
    }

    public function render()
    {
        return view('livewire.settings.configurations');
    }
}
