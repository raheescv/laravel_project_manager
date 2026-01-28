<?php

namespace App\Livewire\Settings;

use App\Models\WorkingDay as WorkingDayModel;
use Livewire\Component;

class WorkingDay extends Component
{
    public $days = [];

    public function mount()
    {
        $this->days = WorkingDayModel::orderBy('order_no')->get()->map(function ($day) {
            return [
                'id' => $day->id,
                'day_name' => $day->day_name,
                'is_working' => (bool) $day->is_working,
            ];
        })->toArray();
    }

    public function updateSettings()
    {
        foreach ($this->days as $dayData) {
            WorkingDayModel::where('id', $dayData['id'])->update([
                'is_working' => $dayData['is_working'],
            ]);
        }

        $this->dispatch('success', ['message' => 'Settings Updated Successfully']);
    }

    public function render()
    {
        return view('livewire.settings.working-day');
    }
}
