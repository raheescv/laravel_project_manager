<?php

namespace App\Livewire\Dashboard\Appointment;

use App\Models\AppointmentItem;
use Carbon\Carbon;
use Livewire\Component;

class UpcomingAppointments extends Component
{
    public $appointments = [];

    public function mount()
    {
        $this->loadAppointments();
    }

    public function loadAppointments()
    {
        $this->appointments = AppointmentItem::query()
            ->with([
                'appointment:id,account_id,start_time,end_time,color,status',
                'appointment.account:id,name',
                'service:id,name',
                'employee:id,name',
            ])
            ->whereHas('appointment', function ($query) {
                $query->pending()
                    ->where('start_time', '>=', Carbon::now())
                    ->where('start_time', '<=', Carbon::now()->addDays(7));
            })
            ->latest()
            ->limit(5)
            ->get();
    }

    public function render()
    {
        return view('livewire.dashboard.appointment.upcoming-appointments');
    }
}
