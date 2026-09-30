<?php

namespace App\Livewire\Sale;

use App\Actions\V1\Storefront\RefundCheckoutAction;
use App\Actions\V1\Storefront\SyncCheckoutAction;
use App\Actions\V1\Storefront\SyncRefundAction;
use App\Exceptions\StorefrontCheckoutException;
use App\Exports\OnlinePaymentsExport;
use App\Livewire\Concerns\HasReportPeriod;
use App\Models\StorefrontCheckout;
use App\Services\Payment\TapException;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Every storefront checkout paid (or attempted) through Tap.
 *
 * Reads storefront_checkouts rather than sales on purpose: a payment that failed,
 * is still pending or was captured but could not become a sale ("review") never
 * reaches the sales list — and review rows are money taken for an order nobody
 * has recorded yet.
 */
class OnlinePayments extends Component
{
    use HasReportPeriod;
    use WithPagination;

    private const PERMISSION = 'sale.online payments';

    private const REFUND_PERMISSION = 'sale.online payments refund';

    private const SORTABLE = ['storefront_checkouts.id', 'storefront_checkouts.amount', 'storefront_checkouts.status', 'storefront_checkouts.customer_name'];

    public string $search = '';

    /** '' · pending · paid · failed · review */
    public string $status = '';

    /** '' · pickup · delivery */
    public string $fulfilment = '';

    public ?string $from_date = null;

    public ?string $to_date = null;

    public int $perPage = 25;

    /** The checkout open in the details popup. */
    public ?int $detailId = null;

    public string $sortField = 'storefront_checkouts.id';

    public string $sortDirection = 'desc';

    protected $paginationTheme = 'bootstrap';

    public function mount(): void
    {
        $this->from_date = date('Y-m-01');
        $this->to_date = date('Y-m-d');
    }

    public function updated(string $key): void
    {
        if ($key !== 'perPage') {
            $this->resetPage();
        }
    }

    public function sortBy(string $field): void
    {
        if (! in_array($field, self::SORTABLE, true)) {
            return;
        }
        $this->sortDirection = $this->sortField === $field && $this->sortDirection === 'asc' ? 'desc' : 'asc';
        $this->sortField = $field;
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'status', 'fulfilment']);
        $this->setRange('this_month');
    }

    /**
     * Ask Tap where a pending or review checkout stands. A captured charge is
     * recorded as its sale right here, exactly as the storefront would have.
     */
    public function check(int $id): void
    {
        abort_unless(auth()->user()?->can(self::PERMISSION), 403);

        $checkout = StorefrontCheckout::query()->findOrFail($id);

        try {
            $checkout = (new SyncCheckoutAction())->execute($checkout);
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('error', ['message' => 'Could not reach Tap: '.$e->getMessage()]);

            return;
        }

        $message = match ($checkout->status) {
            StorefrontCheckout::STATUS_PAID => 'Paid — sale '.($checkout->sale?->invoice_no ?? '').' recorded.',
            StorefrontCheckout::STATUS_FAILED => 'Tap reports the payment was not completed.',
            StorefrontCheckout::STATUS_REVIEW => 'Still needs review: '.$checkout->failure_reason,
            default => 'The customer has not finished paying yet.',
        };
        $this->dispatch($checkout->status === StorefrontCheckout::STATUS_REVIEW ? 'error' : 'success', ['message' => $message]);
    }

    /**
     * Send the whole captured amount back to the customer through Tap. Once Tap
     * reports it REFUNDED the checkout is marked refunded and its sale cancelled.
     */
    public function refund(int $id, string $reason = ''): void
    {
        abort_unless(auth()->user()?->can(self::REFUND_PERMISSION), 403);

        $checkout = StorefrontCheckout::query()->findOrFail($id);

        try {
            $checkout = (new RefundCheckoutAction())->execute($checkout, $reason, (int) auth()->id());
        } catch (StorefrontCheckoutException|TapException $e) {
            $this->dispatch('error', ['message' => 'Refund not sent: '.$e->getMessage()]);

            return;
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('error', ['message' => 'Refund not sent: '.$e->getMessage()]);

            return;
        }

        $this->dispatch(...$this->refundOutcome($checkout));
    }

    /** Ask Tap where a pending refund stands. */
    public function checkRefund(int $id): void
    {
        abort_unless(auth()->user()?->can(self::PERMISSION), 403);

        $checkout = StorefrontCheckout::query()->findOrFail($id);

        try {
            $checkout = (new SyncRefundAction())->execute($checkout);
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('error', ['message' => 'Could not reach Tap: '.$e->getMessage()]);

            return;
        }

        $this->dispatch(...$this->refundOutcome($checkout));
    }

    /** @return array{0: string, 1: array{message: string}} */
    private function refundOutcome(StorefrontCheckout $checkout): array
    {
        return match (true) {
            $checkout->status === StorefrontCheckout::STATUS_REFUNDED && $checkout->failure_reason !== null => ['error', ['message' => $checkout->failure_reason]],
            $checkout->status === StorefrontCheckout::STATUS_REFUNDED => ['success', ['message' => 'Refunded'.($checkout->sale ? ' — sale '.$checkout->sale->invoice_no.' cancelled.' : '.')]],
            $checkout->refundFailed() => ['error', ['message' => 'Tap did not refund: '.(data_get($checkout->refund_response, 'response.message') ?: $checkout->refund_status)]],
            default => ['success', ['message' => 'Refund sent — Tap reports it '.$checkout->refund_status.'. It will be marked refunded once Tap completes it.']],
        };
    }

    public function showDetails(int $id): void
    {
        abort_unless(auth()->user()?->can(self::PERMISSION), 403);

        $this->detailId = StorefrontCheckout::query()->whereKey($id)->value('id');
    }

    public function closeDetails(): void
    {
        $this->detailId = null;
    }

    public function export(): BinaryFileResponse
    {
        abort_unless(auth()->user()?->can(self::PERMISSION), 403);

        return Excel::download(new OnlinePaymentsExport($this->filters()), 'online_payments_'.now()->timestamp.'.xlsx');
    }

    /**
     * @return array{search: string, status: string, fulfilment: string, from_date: ?string, to_date: ?string, sort_field: string, sort_direction: string}
     */
    public function filters(): array
    {
        return [
            'search' => $this->search,
            'status' => in_array($this->status, self::statuses(), true) ? $this->status : '',
            'fulfilment' => in_array($this->fulfilment, ['pickup', 'delivery'], true) ? $this->fulfilment : '',
            'from_date' => $this->from_date,
            'to_date' => $this->to_date,
            'sort_field' => in_array($this->sortField, self::SORTABLE, true) ? $this->sortField : 'storefront_checkouts.id',
            'sort_direction' => $this->sortDirection === 'asc' ? 'asc' : 'desc',
        ];
    }

    /** @return list<string> */
    public static function statuses(): array
    {
        return [
            StorefrontCheckout::STATUS_PENDING,
            StorefrontCheckout::STATUS_PAID,
            StorefrontCheckout::STATUS_FAILED,
            StorefrontCheckout::STATUS_REVIEW,
            StorefrontCheckout::STATUS_REFUNDED,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<StorefrontCheckout>
     */
    public static function filteredQuery(array $filters): Builder
    {
        return StorefrontCheckout::query()
            ->with(['branch:id,name', 'sale:id,invoice_no,status'])
            ->when(trim((string) ($filters['search'] ?? '')), function (Builder $q, string $value): void {
                $q->where(function (Builder $inner) use ($value): void {
                    $inner->where('storefront_checkouts.customer_name', 'like', "%{$value}%")
                        ->orWhere('storefront_checkouts.customer_mobile', 'like', "%{$value}%")
                        ->orWhere('storefront_checkouts.customer_email', 'like', "%{$value}%")
                        ->orWhere('storefront_checkouts.gateway_charge_id', 'like', "%{$value}%")
                        ->orWhereHas('sale', fn (Builder $sale) => $sale->where('invoice_no', 'like', "%{$value}%"));
                });
            })
            ->when($filters['status'] ?? '', fn (Builder $q, string $value) => $q->where('storefront_checkouts.status', $value))
            ->when($filters['fulfilment'] ?? '', fn (Builder $q, string $value) => $q->where('storefront_checkouts.fulfilment', $value))
            ->when($filters['from_date'] ?? '', fn (Builder $q, string $value) => $q->whereDate('storefront_checkouts.created_at', '>=', $value))
            ->when($filters['to_date'] ?? '', fn (Builder $q, string $value) => $q->whereDate('storefront_checkouts.created_at', '<=', $value))
            ->orderBy($filters['sort_field'] ?? 'storefront_checkouts.id', $filters['sort_direction'] ?? 'desc');
    }

    public function render(): View
    {
        $base = fn (): Builder => self::filteredQuery($this->filters())->reorder()->setEagerLoads([]);
        $count = fn (string $status): int => $base()->where('storefront_checkouts.status', $status)->count();

        return view('livewire.sale.online-payments', [
            'rows' => self::filteredQuery($this->filters())->paginate($this->perPage),
            'ranges' => self::RANGES,
            'activeRange' => $this->currentRange(),
            'totals' => [
                'collected' => (float) $base()->whereIn('storefront_checkouts.status', [StorefrontCheckout::STATUS_PAID, StorefrontCheckout::STATUS_REVIEW])->sum('storefront_checkouts.amount'),
                'paid' => $count(StorefrontCheckout::STATUS_PAID),
                'pending' => $count(StorefrontCheckout::STATUS_PENDING),
                'failed' => $count(StorefrontCheckout::STATUS_FAILED),
                'review' => $count(StorefrontCheckout::STATUS_REVIEW),
                'refunded' => $count(StorefrontCheckout::STATUS_REFUNDED),
                'refunded_amount' => (float) $base()->where('storefront_checkouts.status', StorefrontCheckout::STATUS_REFUNDED)->sum('storefront_checkouts.amount'),
            ],
            'detail' => $this->detailId
                ? StorefrontCheckout::query()->with(['branch:id,name', 'sale:id,invoice_no,status', 'refundRequestedBy:id,name'])->find($this->detailId)
                : null,
            'canRefund' => (bool) auth()->user()?->can(self::REFUND_PERMISSION),
        ]);
    }
}
