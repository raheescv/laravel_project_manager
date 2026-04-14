<?php

namespace App\Livewire\Property\TenantDetail;

use App\Actions\TenantDetail\CreateAction;
use App\Actions\TenantDetail\UpdateAction;
use App\Models\TenantDetail;
use Faker\Factory;
use Livewire\Component;

class Page extends Component
{
    protected $listeners = [
        'TenantDetail-Page-Create-Component' => 'create',
        'TenantDetail-Page-Update-Component' => 'edit',
    ];

    public $formData;

    public $table_id;

    public function create()
    {
        $this->mount();
        $this->dispatch('ToggleTenantDetailModal');
    }

    public function edit($id)
    {
        $this->mount($id);
        $this->dispatch('ToggleTenantDetailModal');
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
            $this->formData = [
                'property_id' => '',
                'name' => $name,
                'mobile' => '',
                'email' => '',
                'emirates_id' => '',
                'passport_no' => '',
                'nationality' => '',
                'address' => '',
            ];
        } else {
            $item = TenantDetail::find($this->table_id);
            $this->formData = $item->toArray();
        }
    }

    protected function rules()
    {
        return [
            'formData.property_id' => 'required',
            'formData.name' => 'required|string|max:255',
        ];
    }

    protected $messages = [
        'formData.property_id.required' => 'The property field is required',
        'formData.name.required' => 'The name field is required',
    ];

    public function save($close = false)
    {
        $this->validate();
        try {
            if (! $this->table_id) {
                $response = (new CreateAction())->execute($this->formData);
            } else {
                $response = (new UpdateAction())->execute($this->formData, $this->table_id);
            }
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            $message = $response['message'];
            $this->dispatch('success', ['message' => $message]);
            $this->mount($this->table_id);
            if (! $close) {
                $this->dispatch('ToggleTenantDetailModal');
            } else {
                $this->mount();
            }
            $this->dispatch('RefreshTenantDetailTable');
        } catch (\Throwable $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.property.tenant-detail.page');
    }
}
