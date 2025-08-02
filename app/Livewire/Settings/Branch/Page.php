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

    public $branches;

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
            $name = '';
            $code = '';
            if (! app()->isProduction()) {
                $name = $faker->name;
                $code = $faker->hexcolor;
            }
            $this->branches = [
                'code' => $code,
                'name' => $name,
                'location' => '',
                'mobile' => '',
                'moq_sync' => false,
            ];
        } else {
            $branch = Branch::find($this->table_id);
            $this->branches = $branch->toArray();
            // Ensure moq_sync is a boolean
            $this->branches['moq_sync'] = (bool) $this->branches['moq_sync'];
        }
    }

    protected function rules()
    {
        return [
            'branches.name' => ['required', 'unique:branches,name,'.$this->table_id],
            'branches.code' => ['required', 'unique:branches,code,'.$this->table_id],
        ];
    }

    protected $messages = [
        'branches.name.required' => 'The name field is required',
        'branches.name.unique' => 'The name is already Registered',
        'branches.code.required' => 'The code field is required',
        'branches.code.unique' => 'The code is already Registered',
    ];

    public function save($close = false)
    {
        $this->validate();
        try {
            if (! $this->table_id) {
                $response = (new CreateAction())->execute($this->branches);
            } else {
                $response = (new UpdateAction())->execute($this->branches, $this->table_id);
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
