<?php

namespace App\Livewire\Settings\TailoringMeasurementOption;

use App\Actions\Settings\TailoringMeasurementOption\CreateAction;
use App\Actions\Settings\TailoringMeasurementOption\UpdateAction;
use App\Models\TailoringMeasurementOption;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Page extends Component
{
    protected $listeners = [
        'TailoringMeasurementOption-Page-Create-Component' => 'create',
        'TailoringMeasurementOption-Page-Update-Component' => 'edit',
    ];

    public $options;

    public $table_id;

    public function create()
    {
        $this->mount();
        $this->options = [
            'option_type' => '',
            'value' => '',
        ];
        $this->dispatch('ToggleTailoringMeasurementOptionModal');
    }

    public function edit($id)
    {
        $tableId = is_array($id) ? ($id['id'] ?? $id) : $id;
        $this->mount($tableId);
        $this->dispatch('ToggleTailoringMeasurementOptionModal');
    }

    public function mount($table_id = null)
    {
        $this->table_id = $table_id;
        if (! $this->table_id) {
            $this->options = [
                'option_type' => '',
                'value' => '',
            ];
        } else {
            $option = TailoringMeasurementOption::find($this->table_id);
            $this->options = $option->toArray();
        }
    }

    protected function rules()
    {
        $optionType = $this->options['option_type'] ?? null;

        return [
            'options.option_type' => ['required', Rule::in(array_keys(TailoringMeasurementOption::OPTION_TYPES))],
            'options.value' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tailoring_measurement_options', 'value')
                    ->where('tenant_id', TailoringMeasurementOption::getCurrentTenantId())
                    ->where('option_type', $optionType)
                    ->ignore($this->table_id),
            ],
        ];
    }

    protected $messages = [
        'options.option_type.required' => 'Please select an option type.',
        'options.value.required' => 'The value field is required.',
        'options.value.unique' => 'This value already exists for the selected option type.',
        'options.value.max' => 'The value must not be greater than 255 characters.',
    ];

    public function save($close = false)
    {
        $this->validate();
        try {
            if (! $this->table_id) {
                $response = (new CreateAction())->execute($this->options);
            } else {
                $response = (new UpdateAction())->execute($this->options, $this->table_id);
            }
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            $this->dispatch('success', ['message' => $response['message']]);
            $this->mount($this->table_id);
            if (! $close) {
                $this->dispatch('ToggleTailoringMeasurementOptionModal');
            } else {
                $this->mount();
            }
            $this->dispatch('RefreshTailoringMeasurementOptionTable');
        } catch (\Throwable $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.settings.tailoring-measurement-option.page');
    }
}
