<?php

namespace App\Livewire\Settings\Country;

use App\Actions\Settings\Country\CreateAction;
use App\Actions\Settings\Country\UpdateAction;
use App\Models\Country;
use Livewire\Component;

class Page extends Component
{
    public $table_id;

    public $name;

    public $countries = [];

    public $status = true;

    protected $listeners = [
        'Country-Page-Create-Component' => 'create',
        'Country-Page-Update-Component' => 'edit',
    ];

    public function create()
    {
        $this->mount();
        $this->dispatch('ToggleCountryModal');
    }

    public function edit($id)
    {
        $this->mount($id);
        $this->dispatch('ToggleCountryModal');
    }

    public function mount($table_id = null)
    {
        $this->table_id = $table_id;
        if ($this->table_id) {
            $this->countries = Country::findOrFail($table_id)->toArray();
        } else {
            $this->countries = [
                'name' => '',
                'code' => '',
                'phone_code' => '',
                'status' => true,
            ];
        }
    }

    public function rules()
    {
        return [
            'countries.name' => "required|string|max:255|unique:countries,name,{$this->table_id}",
            'countries.code' => "required|string|max:3|unique:countries,code,{$this->table_id}",
            'countries.phone_code' => 'nullable|string|max:10',
            'countries.status' => 'boolean',
        ];
    }

    protected $messages = [
        'countries.name.required' => 'The name field is required',
        'countries.name.unique' => 'The name is already Registered',
        'countries.code.required' => 'The code field is required',
        'countries.code.unique' => 'The code is already Registered',
    ];

    public function save($close = false)
    {
        $this->validate();
        try {
            if (! $this->table_id) {
                $response = (new CreateAction())->execute($this->countries);
            } else {
                $response = (new UpdateAction())->execute($this->countries, $this->table_id);
            }
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            $this->dispatch('success', ['message' => $response['message']]);
            $this->mount($this->table_id);
            if (! $close) {
                $this->dispatch('ToggleCountryModal');
            } else {
                $this->mount();
            }
            $this->dispatch('RefreshCountryTable');
        } catch (\Throwable $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.settings.country.page');
    }
}
