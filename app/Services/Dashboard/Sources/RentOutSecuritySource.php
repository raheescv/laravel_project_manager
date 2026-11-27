<?php

namespace App\Services\Dashboard\Sources;

use App\Enums\RentOut\AgreementType;
use App\Enums\RentOut\SecurityStatus;
use App\Models\RentOutSecurity;
use App\Services\Dashboard\Blocks;
use App\Services\Dashboard\DashboardSource;
use App\Services\Dashboard\WidgetDefinition;

/**
 * Security deposits by where the money stands: held, still awaited, overdue
 * and handed back. The old system's "rentout security · dashboard overview".
 */
class RentOutSecuritySource extends DashboardSource
{
    public function widgets(): array
    {
        return [
            new WidgetDefinition('security-overview', 'Rent out', 'Security deposits', 'Held, awaited and returned', 'shield', 'kpi', 12, 6, ['rent out security.dashboard overview'], ['rental' => 'Rental', 'lease' => 'Sale'], 'rental'),
        ];
    }

    public function data(string $key, ?string $option, array $params = []): array
    {
        $type = $option === 'lease' ? AgreementType::Lease : AgreementType::Rental;
        $securities = RentOutSecurity::whereHas('rentOut', fn ($query) => $query->where('agreement_type', $type));

        $held = (clone $securities)->whereIn('status', [SecurityStatus::Deposited, SecurityStatus::Paid]);
        $awaited = (clone $securities)->where('status', SecurityStatus::Submitted);
        $overdue = (clone $securities)->where('status', SecurityStatus::Overdue);
        $returned = (clone $securities)->whereIn('status', [SecurityStatus::Returned, SecurityStatus::PaidReleased]);
        $prefix = $type === AgreementType::Lease ? 'rent out lease' : 'rent out';

        return [
            'blocks' => [Blocks::kpis([
                Blocks::kpi('Held', (float) (clone $held)->sum('amount'), 'shield', 'acc', 'money', (clone $held)->count().' deposits'),
                Blocks::kpi('Awaited', (float) (clone $awaited)->sum('amount'), 'clock-o', 'warn', 'money', (clone $awaited)->count().' deposits'),
                Blocks::kpi('Overdue', (float) (clone $overdue)->sum('amount'), 'exclamation-circle', 'bad', 'money', (clone $overdue)->count().' deposits'),
                Blocks::kpi('Returned', (float) (clone $returned)->sum('amount'), 'undo', 'info', 'money', (clone $returned)->count().' deposits'),
            ])],
            'link' => $this->link('Open security report', 'property::report::security', $prefix.'.security report', [$type->value]),
        ];
    }
}
