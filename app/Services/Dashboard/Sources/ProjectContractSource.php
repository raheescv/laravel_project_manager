<?php

namespace App\Services\Dashboard\Sources;

use App\Enums\ProjectContract\ContractStatus;
use App\Enums\ProjectContract\SecurityDirection;
use App\Models\ProjectContract;
use App\Models\ProjectContractApproval;
use App\Models\ProjectContractPayment;
use App\Models\ProjectContractPaymentTerm;
use App\Models\ProjectContractSecurity;
use App\Services\Dashboard\DashboardSource;
use App\Services\Dashboard\WidgetDefinition;
use App\Support\ProjectContract\ApprovalGate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

/**
 * Agreement Module on the home dashboard: one full-width widget drawn by its
 * own block (`contract-dashboard`) in the design approved in
 * docs/project-contract-preview.html — money cards, approvals and rates, the
 * overdue alert, statistics, vendor progress and the tabbed tables.
 * Money figures count approved contracts only (active or completed).
 */
class ProjectContractSource extends DashboardSource
{
    protected const SCOPES = ['year' => 'This Year', 'all' => 'All Time'];

    /** Rows the tabbed tables receive; the block filters them in the browser. */
    protected const TABLE_LIMIT = 100;

    public function widgets(): array
    {
        return [
            new WidgetDefinition('contract-dashboard', 'Agreements', 'Project Contract Dashboard', 'Vendor commitments, payments and security at a glance', 'briefcase', 'kpi', 12, 12, ['project contract.dashboard'], self::SCOPES, 'year'),
        ];
    }

    public function data(string $key, ?string $option, array $params = []): array
    {
        return ['blocks' => [['type' => 'contract-dashboard'] + $this->dashboard($option ?? 'year')]];
    }

    /** Approved contracts, limited to those running this year when asked. */
    protected function committed(string $scope): Builder
    {
        return ProjectContract::query()
            ->whereIn('status', [ContractStatus::Active, ContractStatus::Completed])
            ->when($scope === 'year', fn (Builder $query) => $this->inYear($query));
    }

    protected function inYear(Builder $query): Builder
    {
        return $query->where('start_date', '<=', today()->endOfYear())->where('end_date', '>=', today()->startOfYear());
    }

    /**
     * @return array<string, mixed>
     */
    protected function dashboard(string $scope): array
    {
        $contractIds = $this->committed($scope)->pluck('id');
        $committed = (float) $this->committed($scope)->sum('contract_amount');
        $paid = (float) ProjectContractPayment::whereIn('project_contract_id', $contractIds)->where('status', ProjectContractPayment::STATUS_POSTED)->sum('amount');
        $scheduled = (float) ProjectContractPaymentTerm::whereIn('project_contract_id', $contractIds)->sum('amount');
        $balance = round($committed - $paid, 2);

        $overdue = ProjectContractPaymentTerm::query()
            ->whereIn('project_contract_id', $contractIds)
            ->whereHas('contract', fn (Builder $query) => $query->where('status', ContractStatus::Active))
            ->where('balance', '>', 0)
            ->where('due_date', '<', today());
        $overdueAmount = (float) (clone $overdue)->sum('balance');

        $securities = ProjectContractSecurity::with(['settlements', 'contract:id,end_date'])->whereIn('project_contract_id', $contractIds)->get();
        $all = ProjectContract::query()->when($scope === 'year', fn (Builder $query) => $this->inYear($query));

        return [
            'cards' => [
                'committed' => round($committed, 2),
                'paid' => round($paid, 2),
                'balance' => $balance,
                'security_held' => round($securities->filter(fn (ProjectContractSecurity $security): bool => $security->direction === SecurityDirection::Received)->sum(fn (ProjectContractSecurity $security): float => $security->heldAmount()), 2),
                'expiring_securities' => $securities->filter(fn (ProjectContractSecurity $security): bool => $security->expiresSoon())->count(),
                'pending_approval' => (clone $all)->where('status', ContractStatus::PendingApproval)->count(),
                'waiting_for_me' => $this->waitingForUser(),
                'payment_rate' => $this->percentage($paid, $committed),
                'scheduled_rate' => min($this->percentage($scheduled, $committed), 100.0),
            ],
            'overdue' => [
                'count' => (clone $overdue)->count(),
                'amount' => round($overdueAmount, 2),
                'rate' => $this->percentage($overdueAmount, $balance),
            ],
            'stats' => [
                'total' => (clone $all)->count(),
                'active' => (clone $all)->where('status', ContractStatus::Active)->count(),
                'pending_approval' => (clone $all)->where('status', ContractStatus::PendingApproval)->count(),
                'ending_soon' => ProjectContract::where('status', ContractStatus::Active)->whereBetween('end_date', [today(), today()->addDays(60)])->count(),
            ],
            'vendors' => $this->vendors($scope),
            'tables' => $this->tables(),
            'today' => today()->toDateString(),
            'urls' => $this->urls(),
        ];
    }

    /** Submissions at a level the signed-in user may approve now. */
    protected function waitingForUser(): int
    {
        $user = auth()->user();
        if (! $user?->can('project contract.approve')) {
            return 0;
        }

        return ProjectContractApproval::query()
            ->where('status', ProjectContractApproval::PENDING)
            ->whereHas('contract')
            ->get()
            ->groupBy(fn (ProjectContractApproval $approval): string => $approval->approvable_type.'#'.$approval->approvable_id)
            ->filter(fn (Collection $rows): bool => ApprovalGate::refusal($user, $rows->sortBy('level')->first()) === null)
            ->count();
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function vendors(string $scope): array
    {
        return $this->committed($scope)
            ->with('vendor:id,name')
            ->withSum(['payments as paid_total' => fn (Builder $query) => $query->where('status', ProjectContractPayment::STATUS_POSTED)], 'amount')
            ->get()
            ->groupBy('account_id')
            ->map(fn (Collection $contracts): array => [
                'id' => $contracts->first()->account_id,
                'name' => $contracts->first()->vendor?->name ?? 'Unknown vendor',
                'contracts' => $contracts->count(),
                'committed' => round((float) $contracts->sum('contract_amount'), 2),
                'paid' => round((float) $contracts->sum('paid_total'), 2),
            ])
            ->sortByDesc(fn (array $vendor): float => $vendor['committed'] > 0 ? $vendor['paid'] / $vendor['committed'] : 0)
            ->values()
            ->all();
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    protected function tables(): array
    {
        $withTotals = fn (Builder $query): Builder => $query->with('vendor:id,name')
            ->withSum('terms as scheduled_total', 'amount')
            ->withSum(['payments as paid_total' => fn (Builder $query) => $query->where('status', ProjectContractPayment::STATUS_POSTED)], 'amount');
        $row = fn (ProjectContract $contract): array => [
            'id' => $contract->id,
            'reference_no' => $contract->reference_no,
            'project_name' => $contract->project_name,
            'vendor' => $contract->vendor?->name,
            'start_date' => $contract->start_date->toDateString(),
            'end_date' => $contract->end_date->toDateString(),
            'contract_amount' => (float) $contract->contract_amount,
            'scheduled' => (float) ($contract->scheduled_total ?? 0),
            'paid' => (float) ($contract->paid_total ?? 0),
            'balance' => round((float) $contract->contract_amount - (float) ($contract->paid_total ?? 0), 2),
            'status' => $contract->status->value,
            'status_label' => $contract->status->label(),
        ];

        return [
            'ending' => $withTotals(ProjectContract::query()->whereIn('status', [ContractStatus::Active, ContractStatus::PendingApproval]))
                ->where('end_date', '>=', today())->orderBy('end_date')->limit(self::TABLE_LIMIT)->get()->map($row)->values()->all(),
            'expired' => $withTotals(ProjectContract::query()->where('status', ContractStatus::Active))
                ->where('end_date', '<', today())->orderByDesc('end_date')->limit(self::TABLE_LIMIT)->get()->map($row)->values()->all(),
            'due_terms' => ProjectContractPaymentTerm::query()
                ->with('contract.vendor:id,name')
                ->whereHas('contract', fn (Builder $query) => $query->where('status', ContractStatus::Active))
                ->where('balance', '>', 0)
                ->orderBy('due_date')->limit(self::TABLE_LIMIT)->get()
                ->map(fn (ProjectContractPaymentTerm $term): array => [
                    'contract_id' => $term->project_contract_id,
                    'reference_no' => $term->contract->reference_no,
                    'project_name' => $term->contract->project_name,
                    'vendor' => $term->contract->vendor?->name,
                    'label' => $term->label,
                    'due_date' => $term->due_date->toDateString(),
                    'amount' => (float) $term->amount,
                    'paid' => (float) $term->paid,
                    'balance' => (float) $term->balance,
                    'overdue' => $term->isOverdue(),
                ])->values()->all(),
            'securities' => ProjectContractSecurity::query()
                ->with(['contract.vendor:id,name', 'settlements'])
                ->whereHas('contract', fn (Builder $query) => $query->whereIn('status', [ContractStatus::Active, ContractStatus::PendingApproval]))
                ->whereNotNull('valid_until')
                ->orderBy('valid_until')->limit(self::TABLE_LIMIT)->get()
                ->filter(fn (ProjectContractSecurity $security): bool => $security->expiresSoon())
                ->map(fn (ProjectContractSecurity $security): array => [
                    'contract_id' => $security->project_contract_id,
                    'reference_no' => $security->contract->reference_no,
                    'project_name' => $security->contract->project_name,
                    'vendor' => $security->contract->vendor?->name,
                    'purpose' => $security->purpose->label(),
                    'reference' => trim(($security->bank_name ? $security->bank_name.' · ' : '').($security->reference_no ?? '')),
                    'valid_until' => $security->valid_until->toDateString(),
                    'contract_end' => $security->contract->end_date->toDateString(),
                    'amount' => $security->heldAmount(),
                ])->values()->all(),
        ];
    }

    /**
     * Links the block may follow; only those the user can open.
     *
     * @return array<string, string|null>
     */
    protected function urls(): array
    {
        $user = auth()->user();
        $to = fn (string $route, string $permission, array $parameters = []): ?string => Route::has($route) && $user?->can($permission) ? route($route, $parameters) : null;

        return [
            'list' => $to('project-contract::index', 'project contract.view'),
            'view' => $to('project-contract::view', 'project contract.view', ['id' => '__ID__']),
            'pending' => $to('project-contract::report::pending', 'project contract.report'),
            'paid' => $to('project-contract::report::paid', 'project contract.report'),
        ];
    }
}
