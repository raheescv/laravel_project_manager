<?php

namespace App\Livewire\Dashboard;

use App\Enums\SupplyRequest\SupplyRequestStatus;
use App\Models\SupplyRequest;
use Livewire\Component;

class SupplyRequestDashboard extends Component
{
    public int $requirementCount = 0;
    public int $approvedCount = 0;
    public int $completedCount = 0;
    public int $rejectedCount = 0;
    public int $collectedCount = 0;
    public int $finalApprovedCount = 0;
    public int $totalCount = 0;

    public float $totalAmount = 0;
    public float $completedAmount = 0;

    public array $recentRequests = [];

    public function mount(): void
    {
        $this->loadData();
    }

    private function loadData(): void
    {
        $this->totalCount = SupplyRequest::count();
        $this->requirementCount = SupplyRequest::where('status', SupplyRequestStatus::REQUIREMENT)->count();
        $this->approvedCount = SupplyRequest::where('status', SupplyRequestStatus::APPROVED)->count();
        $this->completedCount = SupplyRequest::where('status', SupplyRequestStatus::COMPLETED)->count();
        $this->rejectedCount = SupplyRequest::where('status', SupplyRequestStatus::REJECTED)->count();
        $this->collectedCount = SupplyRequest::where('status', SupplyRequestStatus::COLLECTED)->count();
        $this->finalApprovedCount = SupplyRequest::where('status', SupplyRequestStatus::FINAL_APPROVED)->count();

        $this->totalAmount = SupplyRequest::sum('grand_total');
        $this->completedAmount = SupplyRequest::where('status', SupplyRequestStatus::COMPLETED)->sum('grand_total');

        // Recent supply requests
        $this->recentRequests = SupplyRequest::with(['property', 'creator'])
            ->latest('date')
            ->limit(5)
            ->get()
            ->map(fn ($sr) => [
                'id' => $sr->id,
                'order_no' => $sr->order_no,
                'date' => $sr->date ? date('d M Y', strtotime($sr->date)) : '-',
                'property' => $sr->property?->number ?? '-',
                'type' => ucfirst($sr->type ?? '-'),
                'amount' => $sr->grand_total,
                'status' => $sr->status?->label() ?? '-',
                'status_color' => $sr->status?->color() ?? 'secondary',
                'creator' => $sr->creator?->name ?? '-',
            ])->toArray();
    }

    public function render()
    {
        return view('livewire.dashboard.supply-request-dashboard');
    }
}
