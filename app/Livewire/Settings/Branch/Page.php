<?php

namespace App\Livewire\Settings\Branch;

use App\Actions\Settings\Branch\CreateAction;
use App\Actions\Settings\Branch\UpdateAction;
use App\Models\Branch;
use Faker\Factory;
use Livewire\Component;

class Page extends Component
{
    protected $listeners = [
        'Branch-Page-Create-Component' => 'create',
        'Branch-Page-Update-Component' => 'edit',
    ];

    public $inventories;

    public $table_id;

    public function create()
    {
        $this->mount();
        $this->dispatch('ToggleBranchModal');
    }

    public function edit($id)
    {
        $this->mount($id);
        $this->dispatch('ToggleBranchModal');
    }

    public function mount($table_id = null)
    {
        $this->table_id = $table_id;
        if (! $this->table_id) {
            $faker = Factory::create();
            $this->inventories = [
                'code' => $code,
                'name' => $name,
                'location' => '',
            ];
        } else {
            $branch = Branch::find($this->table_id);
            $this->inventories = $branch->toArray();
        }
    }

    protected function rules()
    {
        return [
            'inventories.name' => ['required', 'unique:inventories,name,'.$this->table_id],
            'inventories.code' => ['required', 'unique:inventories,code,'.$this->table_id],
        ];
    }

    protected $messages = [
        'inventories.name.required' => 'The name field is required',
        'inventories.name.unique' => 'The name is already Registered',
        'inventories.code.required' => 'The code field is required',
        'inventories.code.unique' => 'The code is already Registered',
    ];

    public function save($close = false)
    {
        $this->validate();
        try {
            if (! $this->table_id) {
                $response = (new CreateAction)->execute($this->inventories);
            } else {
                $response = (new UpdateAction)->execute($this->inventories, $this->table_id);
            }
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            $this->dispatch('success', ['message' => $response['message']]);
            $this->mount($this->table_id);
            if (! $close) {
                $this->dispatch('ToggleBranchModal');
            } else {
                $this->mount();
            }
            $this->dispatch('RefreshBranchTable');
        } catch (\Throwable $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.settings.branch.page');
    }
}
