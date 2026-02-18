<?php

namespace App\Livewire\Settings;

use App\Models\Unit;
use App\Models\UniversalUnitConversion;
use Livewire\Component;

class UniversalUomConfiguration extends Component
{
    public $base_unit_id = '';

    public $sub_unit_id = '';

    public $conversion_factor = '';

    public function mount(): void
    {
        //
    }

    public function getConversionsProperty()
    {
        return UniversalUnitConversion::with(['baseUnit', 'subUnit'])
            ->orderBy('base_unit_id')
            ->orderBy('sub_unit_id')
            ->get();
    }

    public function getUnitsListProperty()
    {
        return Unit::orderBy('name')->get()->mapWithKeys(fn ($u) => [$u->id => "{$u->name} ({$u->code})"]);
    }

    public function addConversion(): void
    {
        $this->validate([
            'base_unit_id' => ['required', 'exists:units,id'],
            'sub_unit_id' => ['required', 'exists:units,id', 'different:base_unit_id'],
            'conversion_factor' => ['required', 'numeric', 'min:0.0001'],
        ]);

        $exists = UniversalUnitConversion::where('base_unit_id', $this->base_unit_id)
            ->where('sub_unit_id', $this->sub_unit_id)
            ->exists();

        if ($exists) {
            $this->addError('sub_unit_id', __('This base unit + sub unit combination already exists.'));

            return;
        }

        UniversalUnitConversion::create([
            'base_unit_id' => $this->base_unit_id,
            'sub_unit_id' => $this->sub_unit_id,
            'conversion_factor' => $this->conversion_factor,
        ]);

        $this->reset(['base_unit_id', 'sub_unit_id', 'conversion_factor']);
        $this->dispatch('success', ['message' => __('Universal UOM conversion added.')]);
    }

    public function removeConversion(int $id): void
    {
        $row = UniversalUnitConversion::find($id);
        if ($row) {
            $row->delete();
            $this->dispatch('success', ['message' => __('Universal UOM conversion removed.')]);
        }
    }

    public function render()
    {
        return view('livewire.settings.universal-uom-configuration');
    }
}
