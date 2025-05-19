<?php

namespace App\Livewire\Dashboard\Appointment;

use App\Models\Appointment;
use Carbon\Carbon;
use Livewire\Component;

class AppointmentChart extends Component
{
    public $chartData = [];

    public $period = 'week';

    public function mount()
    {
        $this->loadChartData();
    }

    public function loadChartData()
    {
        $query = Appointment::query();

        // Set date range based on period
        if ($this->period === 'week') {
            $startDate = Carbon::now()->startOfWeek();
            $endDate = Carbon::now()->endOfWeek();
        } elseif ($this->period === 'month') {
            $startDate = Carbon::now()->startOfMonth();
            $endDate = Carbon::now()->endOfMonth();
        }

        // Get appointments grouped by date and status
        $appointments = $query->whereBetween('date', [$startDate, $endDate])
            ->selectRaw('DATE(date) as date, status, COUNT(*) as count')
            ->groupBy('date', 'status')
            ->orderBy('date')
            ->get();

        // Prepare data for Chart.js
        $dates = [];
        $completed = [];
        $pending = [];
        $cancelled = [];

        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');
            $dates[] = $currentDate->format('M d');

            $completed[] = $appointments->where('date', $dateStr)->where('status', 'completed')->first()->count ?? 0;
            $pending[] = $appointments->where('date', $dateStr)->where('status', 'pending')->first()->count ?? 0;
            $cancelled[] = $appointments->where('date', $dateStr)->where('status', 'cancelled')->first()->count ?? 0;

            $currentDate->addDay();
        }

        $this->chartData = [
            'labels' => $dates,
            'datasets' => [
                ['backgroundColor' => '#28a745', 'borderColor' => '#28a745', 'data' => $completed, 'label' => 'Completed'],
                ['backgroundColor' => '#ffc107', 'borderColor' => '#ffc107', 'data' => $pending,   'label' => 'Pending'],
                ['backgroundColor' => '#dc3545', 'borderColor' => '#dc3545', 'data' => $cancelled, 'label' => 'Cancelled'],
            ],
        ];
    }

    public function changePeriod($period)
    {
        $this->period = $period;
        $this->loadChartData();
        $this->dispatch('reLoadChartData', $this->chartData);
    }

    public function render()
    {
        return view('livewire.dashboard.appointment.appointment-chart');
    }
}
