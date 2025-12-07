<?php

namespace App\Livewire\Account\GeneralVoucher;

use App\Models\Configuration;
use Livewire\Component;

class ColumnVisibility extends Component
{
    public $general_voucher_visible_column;

    public function mount()
    {
        $config = Configuration::where('key', 'general_voucher_visible_column')->value('value');
        $this->general_voucher_visible_column = $config ? json_decode($config, true) : $this->getDefaultColumns();
    }

    protected function getDefaultColumns()
    {
        return [
            'date' => true,
            'account' => true,
            'debit' => true,
            'credit' => true,
            'person_name' => true,
            'reference_number' => true,
            'description' => true,
            'remarks' => true,
            'created_by' => true,
        ];
    }

    public function toggleColumn($column)
    {
        $this->general_voucher_visible_column[$column] = ! $this->general_voucher_visible_column[$column];
        Configuration::updateOrCreate(['key' => 'general_voucher_visible_column'], ['value' => json_encode($this->general_voucher_visible_column)]);
    }

    public function render()
    {
        return view('livewire.account.general-voucher.column-visibility');
    }
}
