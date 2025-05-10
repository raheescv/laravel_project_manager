<?php

namespace App\Livewire\ComboOffer;

use App\Actions\ComboOffer\CreateAction;
use App\Actions\ComboOffer\UpdateAction;
use App\Models\ComboOffer;
use Faker\Factory;
use Livewire\Component;

class Page extends Component
{
    protected $listeners = [
        'ComboOffer-Page-Create-Component' => 'create',
        'ComboOffer-Page-Update-Component' => 'edit',
    ];

    public $combo_offers;

    public $parents;

    public $table_id;

    protected function validationAttributes()
    {
        return [
            'combo_offers.name' => 'name',
            'combo_offers.code' => 'code',
            'combo_offers.description' => 'description',
            'combo_offers.amount' => 'amount',
            'combo_offers.status' => 'status',
        ];
    }

    public function create()
    {
        $this->mount();
        $this->dispatch('ToggleComboOfferModal');
    }

    public function edit($id)
    {
        $this->mount($id);
        $this->dispatch('ToggleComboOfferModal');
    }

    public function mount($table_id = null)
    {
        $this->table_id = $table_id;
        if (! $this->table_id) {
            $faker = Factory::create();
            $name = '';
            if (! app()->isProduction()) {
                $name = $faker->name;
            }

            $this->combo_offers = [
                'name' => $name,
                'count' => 2,
                'description' => '',
                'amount' => '',
                'status' => 'active',
            ];

        } else {
            $comboOffer = ComboOffer::find($this->table_id);
            $this->combo_offers = $comboOffer->toArray();
        }
    }

    protected function rules()
    {
        return [
            'combo_offers.name' => ['required', 'string', 'max:255', 'unique:combo_offers,name,'.($this->table_id)],
            'combo_offers.description' => ['nullable', 'string'],
            'combo_offers.amount' => ['required', 'numeric', 'min:0'],
            'combo_offers.count' => ['required'],
            'combo_offers.status' => ['required'],
        ];
    }

    protected $messages = [
        'combo_offers.name.required' => 'The name field is required',
        'combo_offers.name.unique' => 'The name is already Registered',
        'combo_offers.amount.required' => 'The amount field is required',
        'combo_offers.count.required' => 'The count field is required',
        'combo_offers.status.required' => 'The status field is required',
    ];

    public function save($close = false)
    {
        $this->validate();
        try {
            if (! $this->table_id) {
                $response = (new CreateAction())->execute($this->combo_offers);
            } else {
                $response = (new UpdateAction())->execute($this->combo_offers, $this->table_id);
            }
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            $this->dispatch('success', ['message' => $response['message']]);
            $this->mount($this->table_id);
            if (! $close) {
                $this->dispatch('ToggleComboOfferModal');
            } else {
                $this->mount();
            }
            $this->dispatch('RefreshComboOfferTable');
        } catch (\Throwable $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.combo-offer.page');
    }
}
