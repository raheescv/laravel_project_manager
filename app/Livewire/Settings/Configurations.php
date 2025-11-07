<?php

namespace App\Livewire\Settings;

use App\Models\Account;
use App\Models\Branch;
use App\Models\Configuration;
use App\Models\Country;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class Configurations extends Component
{
    public $barcode_type;

    public $payment_methods;

    public $default_payment_method_id;

    public $default_product_type;

    public $default_purchase_branch_id;

    public $paymentMethods;

    public $branches;

    public $country_id;

    public $countries;

    public function mount()
    {
        $this->barcode_type = Configuration::where('key', 'barcode_type')->value('value');
        $this->payment_methods = Configuration::where('key', 'payment_methods')->value('value');
        $this->default_payment_method_id = Configuration::where('key', 'default_payment_method_id')->value('value') ?? 1;
        $this->default_product_type = Configuration::where('key', 'default_product_type')->value('value') ?? 'service';
        $default_purchase_branch_id_value = Configuration::where('key', 'default_purchase_branch_id')->value('value');
        $this->default_purchase_branch_id = $default_purchase_branch_id_value ? json_decode($default_purchase_branch_id_value, true) : [1];
        $this->country_id = Configuration::where('key', 'country_id')->value('value');
        $this->payment_methods = json_decode($this->payment_methods, 1);
        $this->paymentMethods = [];
        if ($this->payment_methods) {
            $this->paymentMethods = Account::whereIn('id', $this->payment_methods)->pluck('name', 'id')->toArray();
        }

        // Load branches for dropdown
        $this->branches = Branch::orderBy('name')->pluck('name', 'id')->toArray();

        // Load countries for dropdown
        $this->countries = Country::orderBy('name')->pluck('name', 'id')->toArray();
    }

    public function dbView()
    {
        Artisan::call('db:seed --class=View');
    }

    public function save()
    {
        Configuration::updateOrCreate(['key' => 'default_payment_method_id'], ['value' => $this->default_payment_method_id]);
        Configuration::updateOrCreate(['key' => 'barcode_type'], ['value' => $this->barcode_type]);
        Configuration::updateOrCreate(['key' => 'payment_methods'], ['value' => json_encode($this->payment_methods)]);
        Configuration::updateOrCreate(['key' => 'default_product_type'], ['value' => $this->default_product_type]);
        Configuration::updateOrCreate(['key' => 'default_purchase_branch_id'], ['value' => json_encode($this->default_purchase_branch_id)]);
        Configuration::updateOrCreate(['key' => 'country_id'], ['value' => $this->country_id]);
        Cache::forget('payment_methods');
        $this->dispatch('success', ['message' => 'Updated Successfully']);
    }

    public function render()
    {
        return view('livewire.settings.configurations');
    }
}
