<?php

namespace App\Livewire\RentOut\Rent;

use App\Enums\RentOut\AgreementType;
use App\Enums\RentOut\PaymentMode;
use App\Livewire\RentOut\Concerns\HasRentOutReportFilters;
use App\Models\RentOutTransaction;
use App\Support\RentOutConfig;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class PaymentHistoryTable extends Component
{
    use HasRentOutReportFilters, WithPagination;

    protected $paginationTheme = 'bootstrap';

    public string $agreementType = 'rental';

    public $filterPaymentMode = '';

    public function mount(string $agreementType = 'rental'): void
    {
        $this->agreementType = $agreementType;
        $this->sortField = 'date';
    }

    public function getConfigProperty(): RentOutConfig
    {
        return RentOutConfig::make($this->agreementType);
    }

    public function getDefaultColumns(): array
    {
        return [
            'date',
            'voucher',
            'customer',
            'group',
            'building',
            'property',
            'payment_mode',
            'cheque_no',
            'bank',
            'category',
            'amount',
            'remark',
        ];
    }

    protected function getSelectableIds(): array
    {
        return $this->buildQuery()->limit(2000)->pluck('rent_out_transactions.id')->toArray();
    }

    protected function buildQuery(): Builder
    {
        $agreementTypeEnum = AgreementType::from($this->agreementType);

        return RentOutTransaction::query()
            ->with([
                'rentOut.customer',
                'rentOut.property',
                'rentOut.building',
                'rentOut.group',
                'rentOut.salesman',
            ])
            ->whereHas('rentOut', fn ($q) => $q->where('agreement_type', $agreementTypeEnum))
            ->where('credit', '>', 0) // Only receipts (money received from customers)
            ->tap(fn ($q) => $this->applyRentOutFilters($q))
            ->tap(fn ($q) => $this->applyDateFilter($q, 'date'))
            ->when($this->search, function ($q, $value) {
                $q->where(function ($q) use ($value) {
                    $q->where('voucher_no', 'like', "%{$value}%")
                        ->orWhere('cheque_no', 'like', "%{$value}%")
                        ->orWhere('bank_name', 'like', "%{$value}%")
                        ->orWhere('remark', 'like', "%{$value}%")
                        ->orWhereHas('rentOut.customer', fn ($c) => $c->where('name', 'like', "%{$value}%"))
                        ->orWhereHas('rentOut.property', fn ($p) => $p->where('number', 'like', "%{$value}%"));
                });
            })
            ->when($this->filterPaymentMode, fn ($q, $v) => $q->where('payment_type', $v))
            ->orderBy(
                in_array($this->sortField, ['id', 'date', 'paid_date', 'credit', 'voucher_no'])
                    ? 'rent_out_transactions.'.$this->sortField
                    : 'rent_out_transactions.date',
                $this->sortDirection
            );
    }

    public function getStatisticsProperty(): array
    {
        $base = (clone $this->buildQuery());

        $totalReceived = (clone $base)->sum('credit');
        $totalCount = (clone $base)->count();
        $byMode = (clone $base)
            ->selectRaw('payment_type, SUM(credit) as total')
            ->groupBy('payment_type')
            ->pluck('total', 'payment_type')
            ->toArray();

        return [
            'total_received' => $totalReceived,
            'total_count' => $totalCount,
            'by_mode' => $byMode,
        ];
    }

    public function resetFilters(): void
    {
        $this->filterPaymentMode = '';
        parent::resetFilters();
    }

    public function render()
    {
        return view('livewire.rent-out.rent.payment-history-table', [
            'data' => $this->buildQuery()->paginate($this->limit),
            'config' => $this->config,
            'statistics' => $this->statistics,
            'paymentModes' => PaymentMode::cases(),
            ...$this->getFilterData(),
        ]);
    }
}
