<?php

namespace App\Livewire\Dashboard;

use App\Enums\RentOut\AgreementType;
use App\Enums\RentOut\ChequeStatus;
use App\Enums\RentOut\RentOutStatus;
use App\Enums\RentOut\SecurityStatus;
use App\Models\RentOut;
use App\Models\RentOutCheque;
use App\Models\RentOutPaymentTerm;
use App\Models\RentOutSecurity;
use Livewire\Component;

class PropertyFinancialDashboard extends Component
{
    public float $collection = 0;
    public float $paid = 0;
    public float $pending = 0;
    public float $overdueAmount = 0;
    public int $overdueCount = 0;

    public float $clearedCheques = 0;
    public float $unclearedCheques = 0;
    public int $clearedCount = 0;
    public int $unclearedCount = 0;

    public float $totalSecurity = 0;
    public int $securityCount = 0;

    public string $agreementType = 'rental';

    public function mount(string $agreementType = 'rental'): void
    {
        $this->agreementType = $agreementType;
        $this->loadDashboardData();
    }

    public function toggleAgreementType(): void
    {
        $this->agreementType = $this->agreementType === 'rental' ? 'lease' : 'rental';
        $this->loadDashboardData();
    }

    private function loadDashboardData(): void
    {
        $agreementEnum = $this->agreementType === 'rental' ? AgreementType::Rental : AgreementType::Lease;

        // Current month payment terms
        $terms = RentOutPaymentTerm::whereHas('rentOut', function ($q) use ($agreementEnum) {
            $q->where('agreement_type', $agreementEnum)
                ->where('status', RentOutStatus::Occupied);
        })->whereBetween('due_date', [now()->startOfMonth(), now()->endOfMonth()]);

        $this->collection = (clone $terms)->sum('total');
        $this->paid = (clone $terms)->sum('paid');
        $this->pending = $this->collection - $this->paid;

        // Overdue payments
        $overdueQuery = RentOutPaymentTerm::whereHas('rentOut', function ($q) use ($agreementEnum) {
            $q->where('agreement_type', $agreementEnum)
                ->where('status', RentOutStatus::Occupied);
        })->where('status', 'pending')->where('due_date', '<', now());

        $this->overdueAmount = $overdueQuery->sum('balance');
        $this->overdueCount = $overdueQuery->count();

        // Cheque management
        $chequeQuery = RentOutCheque::whereHas('rentOut', function ($q) use ($agreementEnum) {
            $q->where('agreement_type', $agreementEnum)
                ->where('status', RentOutStatus::Occupied);
        });

        $this->clearedCheques = (clone $chequeQuery)->where('status', ChequeStatus::Cleared)->sum('amount');
        $this->clearedCount = (clone $chequeQuery)->where('status', ChequeStatus::Cleared)->count();
        $this->unclearedCheques = (clone $chequeQuery)->where('status', ChequeStatus::Uncleared)->sum('amount');
        $this->unclearedCount = (clone $chequeQuery)->where('status', ChequeStatus::Uncleared)->count();

        // Security deposits
        $securityQuery = RentOutSecurity::whereHas('rentOut', function ($q) use ($agreementEnum) {
            $q->where('agreement_type', $agreementEnum)
                ->where('status', RentOutStatus::Occupied);
        });

        $this->totalSecurity = $securityQuery->sum('amount');
        $this->securityCount = $securityQuery->count();
    }

    public function render()
    {
        return view('livewire.dashboard.property-financial-dashboard');
    }
}
