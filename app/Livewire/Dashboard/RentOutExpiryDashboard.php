<?php

namespace App\Livewire\Dashboard;

use App\Enums\RentOut\RentOutStatus;
use App\Models\RentOut;
use Livewire\Component;

class RentOutExpiryDashboard extends Component
{
    public array $upcomingEndDates = [];
    public array $expiredRentOuts = [];

    public int $upcomingCount = 0;
    public int $expiredCount = 0;
    public int $expiringThisMonth = 0;
    public int $expiringNext30Days = 0;

    public function mount(): void
    {
        $this->loadData();
    }

    private function loadData(): void
    {
        // Upcoming end dates (occupied, ending within next 90 days)
        $upcomingQuery = RentOut::with(['property', 'building', 'customer', 'group'])
            ->where('status', RentOutStatus::Occupied)
            ->whereBetween('end_date', [now(), now()->addDays(90)])
            ->orderBy('end_date');

        $this->upcomingCount = (clone $upcomingQuery)->count();
        $this->expiringThisMonth = RentOut::where('status', RentOutStatus::Occupied)
            ->whereBetween('end_date', [now(), now()->endOfMonth()])
            ->count();
        $this->expiringNext30Days = RentOut::where('status', RentOutStatus::Occupied)
            ->whereBetween('end_date', [now(), now()->addDays(30)])
            ->count();

        $this->upcomingEndDates = $upcomingQuery->limit(10)->get()->map(function ($r) {
            $daysLeft = now()->diffInDays($r->end_date, false);

            return [
                'id' => $r->id,
                'agreement_no' => $r->agreement_no,
                'property' => $r->property?->number ?? '-',
                'building' => $r->building?->name ?? '-',
                'group' => $r->group?->name ?? '-',
                'customer' => $r->customer?->name ?? '-',
                'end_date' => $r->end_date?->format('d M Y'),
                'days_left' => (int) $daysLeft,
                'urgency' => $daysLeft <= 7 ? 'danger' : ($daysLeft <= 30 ? 'warning' : 'info'),
                'rent' => $r->rent,
            ];
        })->toArray();

        // Expired rent outs
        $expiredQuery = RentOut::with(['property', 'building', 'customer', 'group'])
            ->where('status', RentOutStatus::Expired)
            ->orderByDesc('end_date');

        $this->expiredCount = (clone $expiredQuery)->count();

        $this->expiredRentOuts = $expiredQuery->limit(10)->get()->map(function ($r) {
            $daysExpired = $r->end_date ? now()->diffInDays($r->end_date) : 0;

            return [
                'id' => $r->id,
                'agreement_no' => $r->agreement_no,
                'property' => $r->property?->number ?? '-',
                'building' => $r->building?->name ?? '-',
                'group' => $r->group?->name ?? '-',
                'customer' => $r->customer?->name ?? '-',
                'end_date' => $r->end_date?->format('d M Y'),
                'days_expired' => (int) $daysExpired,
                'rent' => $r->rent,
            ];
        })->toArray();
    }

    public function render()
    {
        return view('livewire.dashboard.rent-out-expiry-dashboard');
    }
}
