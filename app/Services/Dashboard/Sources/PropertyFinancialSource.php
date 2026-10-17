<?php

namespace App\Services\Dashboard\Sources;

use App\Enums\RentOut\AgreementType;
use App\Enums\RentOut\ChequeStatus;
use App\Enums\RentOut\RentOutStatus;
use App\Models\RentOutCheque;
use App\Models\RentOutPaymentTerm;
use App\Models\RentOutSecurity;
use App\Services\Dashboard\Blocks;
use App\Services\Dashboard\DashboardSource;
use App\Services\Dashboard\WidgetDefinition;

/**
 * This month's expected vs collected rent, overdue terms, cheques and
 * security deposits for occupied agreements, sale (lease) or rental.
 * Ported from the Livewire `dashboard.property-financial-dashboard` component.
 */
class PropertyFinancialSource extends DashboardSource
{
    public function widgets(): array
    {
        return [
            new WidgetDefinition('property-financial', 'Property', 'Collections', 'Expected vs collected this month', 'bank', 'kpi', 12, 8, ['property dashboard.financial overview'], ['lease' => 'Sale', 'rental' => 'Rental'], 'lease'),
        ];
    }

    public function data(string $key, ?string $option, array $params = []): array
    {
        $type = $option === 'lease' ? AgreementType::Lease : AgreementType::Rental;
        $occupied = fn ($query) => $query->where('agreement_type', $type)->where('status', RentOutStatus::Occupied);

        $terms = RentOutPaymentTerm::whereHas('rentOut', $occupied)->whereBetween('due_date', [now()->startOfMonth(), now()->endOfMonth()]);
        $expected = (float) (clone $terms)->sum('total');
        $collected = (float) (clone $terms)->sum('paid');

        $overdue = RentOutPaymentTerm::whereHas('rentOut', $occupied)->where('status', 'pending')->where('due_date', '<', now());
        $overdueAmount = (float) (clone $overdue)->sum('balance');
        $overdueCount = (clone $overdue)->count();

        $cheques = RentOutCheque::whereHas('rentOut', $occupied);
        $cleared = (float) (clone $cheques)->where('status', ChequeStatus::Cleared)->sum('amount');
        $clearedCount = (clone $cheques)->where('status', ChequeStatus::Cleared)->count();
        $uncleared = (float) (clone $cheques)->where('status', ChequeStatus::Uncleared)->sum('amount');
        $unclearedCount = (clone $cheques)->where('status', ChequeStatus::Uncleared)->count();
        $clearedShare = $this->percentage($cleared, $cleared + $uncleared);

        $securities = RentOutSecurity::whereHas('rentOut', $occupied);
        $month = now()->format('M Y');
        $rate = $this->percentage($collected, $expected);

        return ['blocks' => [
            Blocks::kpis([
                Blocks::kpi("Expected · {$month}", $expected, 'calendar', 'acc', 'money'),
                Blocks::kpi('Collected', $collected, 'check', 'ok', 'money'),
                Blocks::kpi('Pending', $expected - $collected, 'clock-o', 'bad', 'money'),
            ]),
            Blocks::progress('Collection progress · '.now()->format('F'), $rate, $this->rateTone($rate)),
            Blocks::summaryCards([
                [
                    'title' => 'Overdue payments', 'icon' => 'exclamation-circle', 'tone' => 'bad',
                    'boxes' => [['value' => $overdueAmount, 'format' => 'money', 'label' => 'Total overdue amount', 'tone' => 'bad']],
                    'badge' => ['text' => $overdueCount.' overdue '.str('payment')->plural($overdueCount), 'tone' => 'bad'],
                ],
                [
                    'title' => 'Cheque management', 'icon' => 'money', 'tone' => 'info',
                    'boxes' => [
                        ['value' => $cleared, 'format' => 'money', 'label' => "Cleared ({$clearedCount})", 'tone' => 'ok'],
                        ['value' => $uncleared, 'format' => 'money', 'label' => "Uncleared ({$unclearedCount})", 'tone' => 'warn'],
                    ],
                    'stack' => [['percent' => $clearedShare, 'tone' => 'ok'], ['percent' => ($cleared + $uncleared) > 0 ? 100 - $clearedShare : 0, 'tone' => 'warn']],
                ],
                [
                    'title' => 'Security deposits', 'icon' => 'shield', 'tone' => 'acc',
                    'boxes' => [['value' => (float) (clone $securities)->sum('amount'), 'format' => 'money', 'label' => 'Total security held', 'tone' => 'acc']],
                    'badge' => ['text' => (clone $securities)->count().' active deposits', 'tone' => 'acc'],
                ],
            ]),
        ]];
    }
}
