<?php

namespace App\Http\Controllers\ProjectContract;

use App\Enums\ProjectContract\ContractStatus;
use App\Http\Controllers\Controller;
use App\Models\ProjectContract;
use App\Models\ProjectContractPayment;
use App\Models\ProjectContractPaymentTerm;
use App\Traits\ApiResponseTrait;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** The Pending and Paid payment reports. */
class ContractInsightController extends Controller
{
    use ApiResponseTrait;

    /** Rows a report sends at most; the summary always covers everything matched. */
    public const REPORT_LIMIT = 1000;

    /** Contracts whose money counts: approved ones, live or finished. */
    private function committed(): Builder
    {
        return ProjectContract::query()->whereIn('status', [ContractStatus::Active, ContractStatus::Completed]);
    }

    public function pending(Request $request): JsonResponse
    {
        [$rows, $summary] = $this->pendingData($request);

        return $this->sendSuccess(['rows' => $rows->take(self::REPORT_LIMIT)->values(), 'summary' => $summary, 'truncated' => $rows->count() > self::REPORT_LIMIT], 'Report loaded.');
    }

    public function paid(Request $request): JsonResponse
    {
        [$rows, $summary] = $this->paidData($request);

        return $this->sendSuccess(['rows' => $rows->take(self::REPORT_LIMIT)->values(), 'summary' => $summary, 'truncated' => $rows->count() > self::REPORT_LIMIT], 'Report loaded.');
    }

    /**
     * @return array{0: Collection<int, array<string, mixed>>, 1: array<string, mixed>}
     */
    private function pendingData(Request $request): array
    {
        $today = CarbonImmutable::today();
        $terms = ProjectContractPaymentTerm::query()
            ->with('contract.vendor:id,name')
            ->whereHas('contract', fn (Builder $query) => $this->contractFilter($query->whereIn('status', [ContractStatus::Active, ContractStatus::Completed]), $request))
            ->where('balance', '>', 0)
            ->when($request->input('from_date'), fn (Builder $query, string $date) => $query->where('due_date', '>=', $date))
            ->when($request->input('to_date'), fn (Builder $query, string $date) => $query->where('due_date', '<=', $date))
            ->orderBy('due_date')
            ->get();

        $bucket = function (ProjectContractPaymentTerm $term) use ($today): string {
            if ($term->due_date->gte($today)) {
                return $term->due_date->lte($today->addDays(30)) ? 'soon' : 'later';
            }

            return 'overdue';
        };

        $all = $terms->map(fn (ProjectContractPaymentTerm $term): array => [
            'contract_id' => $term->project_contract_id,
            'reference_no' => $term->contract->reference_no,
            'project_name' => $term->contract->project_name,
            'vendor' => $term->contract->vendor?->name,
            'label' => $term->label,
            'due_date' => $term->due_date->toDateString(),
            'days' => (int) $today->diffInDays($term->due_date, false),
            'amount' => (float) $term->amount,
            'paid' => (float) $term->paid,
            'balance' => (float) $term->balance,
            'state' => $bucket($term),
        ]);

        $sum = fn (string $state): float => round((float) $all->where('state', $state)->sum('balance'), 2);
        $ageing = ['current' => 0.0, 'd30' => 0.0, 'd60' => 0.0, 'd90' => 0.0, 'over90' => 0.0];
        foreach ($all as $row) {
            $late = -$row['days'];
            $key = match (true) {
                $late <= 0 => 'current',
                $late <= 30 => 'd30',
                $late <= 60 => 'd60',
                $late <= 90 => 'd90',
                default => 'over90',
            };
            $ageing[$key] = round($ageing[$key] + $row['balance'], 2);
        }

        $summary = [
            'total' => round((float) $all->sum('balance'), 2),
            'total_count' => $all->count(),
            'contracts' => $all->pluck('contract_id')->unique()->count(),
            'overdue' => $sum('overdue'),
            'overdue_count' => $all->where('state', 'overdue')->count(),
            'soon' => $sum('soon'),
            'soon_count' => $all->where('state', 'soon')->count(),
            'later' => $sum('later'),
            'later_count' => $all->where('state', 'later')->count(),
            'ageing' => $ageing,
        ];

        $state = $request->input('state');
        $rows = in_array($state, ['overdue', 'soon', 'later'], true) ? $all->where('state', $state)->values() : $all;

        return [$rows, $summary];
    }

    /**
     * @return array{0: Collection<int, array<string, mixed>>, 1: array<string, mixed>}
     */
    private function paidData(Request $request): array
    {
        $payments = ProjectContractPayment::query()
            ->with(['contract.vendor:id,name', 'paymentMethod:id,name,is_cheque', 'allocations.term:id,label'])
            ->where('status', ProjectContractPayment::STATUS_POSTED)
            ->whereHas('contract', fn (Builder $query) => $this->contractFilter($query, $request))
            ->when($request->input('from_date'), fn (Builder $query, string $date) => $query->where('date', '>=', $date))
            ->when($request->input('to_date'), fn (Builder $query, string $date) => $query->where('date', '<=', $date))
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        $all = $payments->map(fn (ProjectContractPayment $payment): array => [
            'id' => $payment->id,
            'contract_id' => $payment->project_contract_id,
            'date' => $payment->date->toDateString(),
            'voucher_no' => $payment->voucher_no,
            'journal_id' => $payment->journal_id,
            'reference_no' => $payment->contract->reference_no,
            'project_name' => $payment->contract->project_name,
            'vendor' => $payment->contract->vendor?->name,
            'terms' => $payment->allocations->map(fn ($allocation): string => $allocation->term?->label ?? '')->filter()->implode(', '),
            'method_id' => $payment->payment_method_id,
            'method' => $payment->paymentMethod?->name,
            'reference' => trim(implode(' · ', array_filter([$payment->cheque_no ? '#'.$payment->cheque_no : null, $payment->bank_name, $payment->reference]))),
            'amount' => (float) $payment->amount,
        ]);

        $summary = [
            'total' => round((float) $all->sum('amount'), 2),
            'count' => $all->count(),
            'contracts' => $all->pluck('contract_id')->unique()->count(),
            'methods' => $all->groupBy('method_id')->map(fn (Collection $rows): array => [
                'id' => $rows->first()['method_id'],
                'name' => $rows->first()['method'] ?? 'Unknown',
                'amount' => round((float) $rows->sum('amount'), 2),
                'count' => $rows->count(),
            ])->sortByDesc('amount')->values(),
            'months' => $all->groupBy(fn (array $row): string => substr($row['date'], 0, 7))->map(fn (Collection $rows, string $month): array => [
                'month' => $month,
                'amount' => round((float) $rows->sum('amount'), 2),
            ])->values(),
        ];

        $method = $request->integer('method_id');
        $rows = $method ? $all->where('method_id', $method)->values() : $all;

        return [$rows, $summary];
    }

    private function contractFilter(Builder $query, Request $request): Builder
    {
        return $query
            ->when($request->input('account_id'), fn (Builder $query, $vendor) => $query->where('account_id', $vendor))
            ->when($request->input('contract_id'), fn (Builder $query, $contract) => $query->whereKey($contract))
            ->when($request->input('branch_id'), fn (Builder $query, $branch) => $query->where('branch_id', $branch));
    }

    /** CSV of the report as filtered, for Excel. */
    public function export(Request $request, string $report): StreamedResponse
    {
        abort_unless(in_array($report, ['pending', 'paid'], true), 404);
        [$rows] = $report === 'pending' ? $this->pendingData($request) : $this->paidData($request);

        $headers = $report === 'pending'
            ? ['Due date', 'Contract', 'Project', 'Vendor', 'Term', 'Amount', 'Paid', 'Balance', 'Status']
            : ['Date', 'Voucher', 'Contract', 'Project', 'Vendor', 'Terms', 'Method', 'Reference', 'Amount'];
        $line = $report === 'pending'
            ? fn (array $row): array => [$row['due_date'], $row['reference_no'], $row['project_name'], $row['vendor'], $row['label'], $row['amount'], $row['paid'], $row['balance'], ucfirst($row['state'])]
            : fn (array $row): array => [$row['date'], $row['voucher_no'], $row['reference_no'], $row['project_name'], $row['vendor'], $row['terms'], $row['method'], $row['reference'], $row['amount']];

        return response()->streamDownload(function () use ($rows, $headers, $line): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                fputcsv($out, $line($row));
            }
            fclose($out);
        }, "project-contract-{$report}-payments-".today()->toDateString().'.csv', ['Content-Type' => 'text/csv']);
    }
}
