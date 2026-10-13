<?php

namespace App\Services\Dashboard\Sources;

use App\Enums\ProjectContract\ContractStatus;
use App\Enums\ProjectContract\SecurityDirection;
use App\Models\ProjectContract;
use App\Models\ProjectContractApproval;
use App\Models\ProjectContractPayment;
use App\Models\ProjectContractPaymentTerm;
use App\Models\ProjectContractSecurity;
use App\Services\Dashboard\Blocks;
use App\Services\Dashboard\DashboardSource;
use App\Services\Dashboard\WidgetDefinition;
use App\Support\ProjectContract\ApprovalGate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Agreement Module on the home dashboard: what is committed to vendors, what
 * has been paid, what is overdue or waiting for approval, and the security held.
 * Money figures count approved contracts only (active or completed).
 */
class ProjectContractSource extends DashboardSource
{
    protected const SCOPES = ['year' => 'This year', 'all' => 'All time'];

    public function widgets(): array
    {
        $permission = ['project contract.dashboard'];

        return [
            new WidgetDefinition('contract-kpis', 'Agreements', 'Project contracts', 'Committed, paid and still owed to vendors', 'file-text-o', 'kpi', 12, 6, $permission, self::SCOPES, 'year'),
            new WidgetDefinition('contract-attention', 'Agreements', 'Needs attention', 'Overdue terms and approvals waiting', 'exclamation-triangle', 'kpi', 6, 6, $permission),
            new WidgetDefinition('contract-vendors', 'Agreements', 'Vendor payment progress', 'Paid against committed, per vendor', 'truck', 'meter', 6, 4, $permission, self::SCOPES, 'year'),
            new WidgetDefinition('contract-due', 'Agreements', 'Upcoming due terms', 'Unpaid terms due in the next 60 days, and overdue', 'clock-o', 'table', 6, 6, $permission),
            new WidgetDefinition('contract-ending', 'Agreements', 'Contracts ending soon', 'Active contracts ending in the next 90 days', 'calendar', 'table', 6, 6, $permission),
            new WidgetDefinition('contract-securities', 'Agreements', 'Expiring security', 'Guarantees that lapse soon or before the contract ends', 'exclamation-circle', 'table', 6, 6, $permission),
        ];
    }

    public function data(string $key, ?string $option, array $params = []): array
    {
        $link = $this->link('Open contracts', 'project-contract::index', 'project contract.view');

        return match ($key) {
            'contract-attention' => ['blocks' => $this->attention(), 'link' => $this->link('Pending payments', 'project-contract::report::pending', 'project contract.report')],
            'contract-vendors' => ['blocks' => [$this->vendors($option)], 'link' => $link],
            'contract-due' => ['blocks' => [$this->dueTerms()], 'link' => $this->link('Pending payments', 'project-contract::report::pending', 'project contract.report')],
            'contract-ending' => ['blocks' => [$this->ending()], 'link' => $link],
            'contract-securities' => ['blocks' => [$this->securities()], 'link' => $link],
            default => ['blocks' => $this->kpis($option), 'link' => $link],
        };
    }

    /** Approved contracts, limited to those running this year when asked. */
    protected function committed(?string $scope): Builder
    {
        return ProjectContract::query()
            ->whereIn('status', [ContractStatus::Active, ContractStatus::Completed])
            ->when($scope === 'year', fn (Builder $query) => $query
                ->where('start_date', '<=', today()->endOfYear())
                ->where('end_date', '>=', today()->startOfYear()));
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function kpis(?string $scope): array
    {
        $contracts = $this->committed($scope)->get(['id', 'contract_amount']);
        $committed = (float) $contracts->sum('contract_amount');
        $paid = (float) ProjectContractPayment::whereIn('project_contract_id', $contracts->pluck('id'))->where('status', ProjectContractPayment::STATUS_POSTED)->sum('amount');
        $scheduled = (float) ProjectContractPaymentTerm::whereIn('project_contract_id', $contracts->pluck('id'))->sum('amount');
        $held = ProjectContractSecurity::with('settlements')
            ->whereIn('project_contract_id', $contracts->pluck('id'))
            ->where('direction', SecurityDirection::Received)
            ->get()
            ->sum(fn (ProjectContractSecurity $security): float => $security->heldAmount());

        $paidRate = $this->percentage($paid, $committed);

        return [
            Blocks::kpis([
                Blocks::kpi('Committed', $committed, 'file-text-o', 'acc', 'money', $contracts->count().' approved contracts'),
                Blocks::kpi('Paid', $paid, 'check-circle', 'ok', 'money', $paidRate.'% of committed'),
                Blocks::kpi('Balance', round($committed - $paid, 2), 'clock-o', 'warn', 'money', round(100 - $paidRate, 1).'% remaining'),
                Blocks::kpi('Security held', round($held, 2), 'shield', 'info', 'money', 'received from vendors'),
            ]),
            Blocks::split(
                [Blocks::progress('Payment rate', $paidRate, $this->rateTone($paidRate, 70, 40), number_format($paid, 2).' of '.number_format($committed, 2))],
                [Blocks::progress('Scheduled into terms', min($this->percentage($scheduled, $committed), 100), 'info', number_format($scheduled, 2).' in payment terms')],
            ),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function attention(): array
    {
        $overdue = ProjectContractPaymentTerm::query()
            ->whereHas('contract', fn (Builder $query) => $query->where('status', ContractStatus::Active))
            ->where('balance', '>', 0)
            ->where('due_date', '<', today());
        $overdueAmount = (float) (clone $overdue)->sum('balance');
        $pending = ProjectContract::where('status', ContractStatus::PendingApproval)->count();

        $blocks = [Blocks::kpis([
            Blocks::kpi('Overdue terms', (clone $overdue)->count(), 'clock-o', 'bad'),
            Blocks::kpi('Overdue amount', round($overdueAmount, 2), 'money', 'bad', 'money'),
            Blocks::kpi('Pending approval', $pending, 'gavel', 'warn', 'number', $this->waitingForUser().' waiting for you'),
            Blocks::kpi('Ending in 60 days', ProjectContract::where('status', ContractStatus::Active)->whereBetween('end_date', [today(), today()->addDays(60)])->count(), 'calendar', 'info'),
        ])];

        if ($overdueAmount > 0) {
            array_unshift($blocks, Blocks::alert('QAR '.number_format($overdueAmount, 2).' is overdue to vendors.'));
        }

        return $blocks;
    }

    /** Submissions sitting at a level the signed-in user may approve now. */
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
     * @return array<string, mixed>
     */
    protected function vendors(?string $scope): array
    {
        $rows = $this->committed($scope)
            ->with('vendor:id,name')
            ->withSum(['payments as paid_total' => fn (Builder $query) => $query->where('status', ProjectContractPayment::STATUS_POSTED)], 'amount')
            ->get()
            ->groupBy('account_id')
            ->map(function (Collection $contracts): array {
                $committed = (float) $contracts->sum('contract_amount');
                $paid = (float) $contracts->sum('paid_total');
                $rate = $this->percentage($paid, $committed);
                $count = $contracts->count();

                return [
                    'label' => $contracts->first()->vendor?->name ?? 'Unknown vendor',
                    'text' => number_format($paid, 0).' / '.number_format($committed, 0).' · '.$count.' '.($count === 1 ? 'contract' : 'contracts'),
                    'badge' => ['text' => $rate.'%', 'tone' => $this->rateTone($rate, 80, 40)],
                    'percent' => min($rate, 100),
                    'tone' => $this->rateTone($rate, 80, 40),
                    'committed' => $committed,
                ];
            })
            ->sortByDesc('committed')
            ->take(8)
            ->map(fn (array $row): array => array_diff_key($row, ['committed' => true]))
            ->values()
            ->all();

        return Blocks::meters($rows, 'No approved contracts yet.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function dueTerms(): array
    {
        $rows = ProjectContractPaymentTerm::query()
            ->with('contract.vendor:id,name')
            ->whereHas('contract', fn (Builder $query) => $query->where('status', ContractStatus::Active))
            ->where('balance', '>', 0)
            ->where('due_date', '<=', today()->addDays(60))
            ->orderBy('due_date')
            ->limit(10)
            ->get()
            ->map(function (ProjectContractPaymentTerm $term): array {
                $days = (int) today()->diffInDays($term->due_date, false);

                return [
                    $term->contract->reference_no,
                    $term->label,
                    $term->contract->vendor?->name ?? '-',
                    (float) $term->balance,
                    $term->due_date->format('d M Y'),
                    Blocks::badge($days < 0 ? (-$days).'d late' : $days.'d', $days < 0 ? 'bad' : ($days <= 7 ? 'warn' : 'info')),
                ];
            })->all();

        return Blocks::table([
            ['label' => 'Contract', 'strong' => true],
            ['label' => 'Term'],
            ['label' => 'Vendor'],
            ['label' => 'Balance', 'align' => 'end', 'format' => 'money'],
            ['label' => 'Due'],
            ['label' => 'When', 'align' => 'end'],
        ], $rows, 'Nothing due in the next 60 days.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function ending(): array
    {
        $rows = ProjectContract::query()
            ->with('vendor:id,name')
            ->where('status', ContractStatus::Active)
            ->whereBetween('end_date', [today(), today()->addDays(90)])
            ->orderBy('end_date')
            ->limit(10)
            ->get()
            ->map(function (ProjectContract $contract): array {
                $days = (int) today()->diffInDays($contract->end_date, false);

                return [$contract->reference_no, $contract->project_name, $contract->vendor?->name ?? '-', (float) $contract->contract_amount, $contract->end_date->format('d M Y'), Blocks::badge($days.'d', $days <= 14 ? 'bad' : ($days <= 30 ? 'warn' : 'info'))];
            })->all();

        return Blocks::table([
            ['label' => 'Contract', 'strong' => true],
            ['label' => 'Project'],
            ['label' => 'Vendor'],
            ['label' => 'Amount', 'align' => 'end', 'format' => 'money'],
            ['label' => 'Ends'],
            ['label' => 'Left', 'align' => 'end'],
        ], $rows, 'No contract ends in the next 90 days.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function securities(): array
    {
        $rows = ProjectContractSecurity::query()
            ->with(['contract.vendor:id,name', 'settlements'])
            ->whereHas('contract', fn (Builder $query) => $query->whereIn('status', [ContractStatus::Active, ContractStatus::PendingApproval]))
            ->whereNotNull('valid_until')
            ->orderBy('valid_until')
            ->limit(50)
            ->get()
            ->filter(fn (ProjectContractSecurity $security): bool => $security->expiresSoon())
            ->take(10)
            ->map(function (ProjectContractSecurity $security): array {
                $days = (int) today()->diffInDays($security->valid_until, false);

                return [
                    $security->contract->reference_no,
                    $security->purpose->label(),
                    $security->contract->vendor?->name ?? '-',
                    $security->heldAmount(),
                    $security->valid_until->format('d M Y'),
                    Blocks::badge($days < 0 ? 'Expired' : $days.'d', $days <= 14 ? 'bad' : 'warn'),
                ];
            })->values()->all();

        return Blocks::table([
            ['label' => 'Contract', 'strong' => true],
            ['label' => 'Security'],
            ['label' => 'Vendor'],
            ['label' => 'Held', 'align' => 'end', 'format' => 'money'],
            ['label' => 'Valid until'],
            ['label' => 'Left', 'align' => 'end'],
        ], $rows, 'No guarantee needs renewing.');
    }
}
