<?php

namespace App\Services\Dashboard\Sources;

use App\Enums\RentOut\RentOutStatus;
use App\Models\RentOut;
use App\Services\Dashboard\Blocks;
use App\Services\Dashboard\DashboardSource;
use App\Services\Dashboard\WidgetDefinition;

/**
 * Agreements ending soon and agreements that have expired.
 * Ported from the Livewire `dashboard.rent-out-expiry-dashboard` component.
 */
class RentOutExpirySource extends DashboardSource
{
    public function widgets(): array
    {
        $permission = ['property dashboard.rent out expiry overview'];

        return [
            new WidgetDefinition('rent-kpis', 'Rent out', 'Lease expiry', 'Occupied agreements ending soon', 'calendar', 'kpi', 12, 6, $permission),
            new WidgetDefinition('rent-agreements', 'Rent out', 'Agreement end dates', 'Ending in the next 90 days or recently ended', 'calendar', 'table', 12, 6, $permission, ['upcoming' => 'Upcoming', 'expired' => 'Expired'], 'upcoming'),
        ];
    }

    public function data(string $key, ?string $option, array $params = []): array
    {
        return match ($key) {
            'rent-agreements' => ['blocks' => [$option === 'expired' ? $this->expired() : $this->upcoming()]],
            default => ['blocks' => [$this->counts()], 'link' => $this->link('Open rent out', 'property::rent::index', 'rent out.view')],
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function counts(): array
    {
        $occupied = RentOut::where('status', RentOutStatus::Occupied);

        return Blocks::kpis([
            Blocks::kpi('Expiring · 90 days', (clone $occupied)->whereBetween('end_date', [now(), now()->addDays(90)])->count(), 'calendar'),
            Blocks::kpi('This month', (clone $occupied)->whereBetween('end_date', [now(), now()->endOfMonth()])->count(), 'calendar-o', 'warn'),
            Blocks::kpi('Next 30 days', (clone $occupied)->whereBetween('end_date', [now(), now()->addDays(30)])->count(), 'clock-o', 'info'),
            Blocks::kpi('Expired', RentOut::where('status', RentOutStatus::Expired)->count(), 'exclamation-circle', 'bad'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function upcoming(): array
    {
        $rows = RentOut::with(['property', 'customer'])
            ->where('status', RentOutStatus::Occupied)
            ->whereBetween('end_date', [now(), now()->addDays(90)])
            ->orderBy('end_date')
            ->limit(10)
            ->get()
            ->map(function (RentOut $rentOut): array {
                $days = (int) now()->diffInDays($rentOut->end_date, false);

                return [...$this->row($rentOut), Blocks::badge($days.'d', $days <= 7 ? 'bad' : ($days <= 30 ? 'warn' : 'info'))];
            })->all();

        return Blocks::table($this->columns('Left'), $rows, 'Nothing ends in the next 90 days.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function expired(): array
    {
        $rows = RentOut::with(['property', 'customer'])
            ->where('status', RentOutStatus::Expired)
            ->orderByDesc('end_date')
            ->limit(10)
            ->get()
            ->map(fn (RentOut $rentOut): array => [...$this->row($rentOut), Blocks::badge((int) now()->diffInDays($rentOut->end_date, true).'d', 'bad')])
            ->all();

        return Blocks::table($this->columns('Expired'), $rows, 'No expired agreements.');
    }

    /**
     * @return list<mixed>
     */
    protected function row(RentOut $rentOut): array
    {
        return [$rentOut->agreement_no, $rentOut->property->number ?? '-', $rentOut->customer->name ?? '-', (float) $rentOut->rent, $rentOut->end_date->format('d M Y')];
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function columns(string $days): array
    {
        return [
            ['label' => 'Agreement', 'strong' => true],
            ['label' => 'Property'],
            ['label' => 'Customer'],
            ['label' => 'Rent', 'align' => 'end', 'format' => 'money'],
            ['label' => 'End date'],
            ['label' => $days, 'align' => 'end'],
        ];
    }
}
