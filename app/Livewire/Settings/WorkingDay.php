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
        // TODO(C7): unmapped (candidate: 'configuration.settings') — no dedicated 'working day' permission in config/permissions.php; tab currently hidden (@if(false)) and component rendered outside any @can in settings/index.blade.
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
