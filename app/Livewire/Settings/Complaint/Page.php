<?php

namespace App\Livewire\Settings\Complaint;

use App\Actions\Settings\Complaint\CreateAction;
use App\Actions\Settings\Complaint\UpdateAction;
use App\Models\Complaint;
use Faker\Factory;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Page extends Component
{
    protected $listeners = [
        'Complaint-Page-Create-Component' => 'create',
        'Complaint-Page-Update-Component' => 'edit',
    ];

    public $formData;

    public $table_id;

    public function create()
    {
        $this->mount();
        $this->dispatch('ToggleComplaintModal');
    }

    public function edit($id)
    {
        $this->mount($id);
        $this->dispatch('ToggleComplaintModal');
    }

    public function mount($table_id = null)
    {
        $this->table_id = $table_id;
        if (! $this->table_id) {
            $faker = Factory::create();
            $name = '';
            if (! app()->isProduction()) {
                $name = $faker->word;
            }
            $this->formData = [
                'complaint_category_id' => '',
                'name' => $name,
                'arabic_name' => '',
                'description' => '',
            ];
        } else {
            $item = Complaint::find($this->table_id);
            $this->formData = $item->toArray();
        }
    }

    protected function rules()
    {
        return [
            'formData.name' => Rule::unique('complaints', 'name')->where('tenant_id', session('tenant_id'))->ignore($this->table_id)->whereNull('deleted_at'),
            'formData.complaint_category_id' => 'required',
        ];
    }

    protected $messages = [
        'formData.name.required' => 'The name field is required',
        'formData.name.unique' => 'The name is already registered',
        'formData.complaint_category_id.required' => 'The category field is required',
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
                $this->dispatch('ToggleComplaintModal');
            } else {
                $this->mount();
            }
            $this->dispatch('RefreshComplaintTable');
        } catch (\Throwable $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.settings.complaint.page');
    }
}
