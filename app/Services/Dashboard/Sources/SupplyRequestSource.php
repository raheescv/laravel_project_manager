<?php

namespace App\Services\Dashboard\Sources;

use App\Enums\SupplyRequest\SupplyRequestStatus;
use App\Models\SupplyRequest;
use App\Services\Dashboard\Blocks;
use App\Services\Dashboard\DashboardSource;
use App\Services\Dashboard\WidgetDefinition;

/**
 * Supply requests by status, their value, and the latest requests.
 * Ported from the Livewire `dashboard.supply-request-dashboard` component.
 */
class SupplyRequestSource extends DashboardSource
{
    public function widgets(): array
    {
        $permission = ['property dashboard.supply request overview'];

        return [
            new WidgetDefinition('supply-status', 'Supply', 'Supply requests', 'By status', 'truck', 'kpi', 12, 6, $permission),
            new WidgetDefinition('supply-summary', 'Supply', 'Financial summary', 'Supply request value', 'dollar', 'kpi', 4, 4, $permission),
            new WidgetDefinition('supply-recent', 'Supply', 'Recent supply requests', 'Last 5 requests', 'file-text-o', 'table', 8, 6, $permission),
        ];
    }

    public function data(string $key, ?string $option, array $params = []): array
    {
        $link = $this->link('Open supply requests', 'supply-request::index', 'supply request.view');

        return match ($key) {
            'supply-summary' => ['blocks' => $this->summary()],
            'supply-recent' => ['blocks' => [$this->recent()], 'link' => $link],
            default => ['blocks' => [$this->status()], 'link' => $link],
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function status(): array
    {
        $counts = SupplyRequest::query()->toBase()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $count = fn (SupplyRequestStatus $status): int => (int) ($counts[$status->value] ?? 0);

        return Blocks::kpis([
            Blocks::kpi('Requirement', $count(SupplyRequestStatus::REQUIREMENT), 'file-text-o', 'slate'),
            Blocks::kpi('Approved', $count(SupplyRequestStatus::APPROVED), 'check', 'info'),
            Blocks::kpi('Final approved', $count(SupplyRequestStatus::FINAL_APPROVED), 'check-circle', 'acc'),
            Blocks::kpi('Collected', $count(SupplyRequestStatus::COLLECTED), 'truck', 'c2'),
            Blocks::kpi('Completed', $count(SupplyRequestStatus::COMPLETED), 'flag', 'ok'),
            Blocks::kpi('Rejected', $count(SupplyRequestStatus::REJECTED), 'times', 'bad'),
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function summary(): array
    {
        $total = SupplyRequest::count();
        $completed = SupplyRequest::where('status', SupplyRequestStatus::COMPLETED)->count();
        $completedValue = (float) SupplyRequest::where('status', SupplyRequestStatus::COMPLETED)->sum('grand_total');
        $rate = $this->percentage($completed, $total);

        return [
            Blocks::highlight('Total supply value', (float) SupplyRequest::sum('grand_total')),
            Blocks::facts([
                ['label' => 'Completed value', 'value' => $completedValue, 'format' => 'money', 'tone' => 'ok'],
                ['label' => 'Total requests', 'value' => $total, 'format' => 'number'],
                ['label' => 'Completion rate', 'value' => $rate, 'format' => 'percent', 'tone' => 'acc'],
            ]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function recent(): array
    {
        $rows = SupplyRequest::with('property')->latest('date')->limit(5)->get()->map(fn (SupplyRequest $request): array => [
            $request->order_no,
            $request->date ? date('d M Y', strtotime((string) $request->date)) : '-',
            $request->property->number ?? '-',
            ucfirst($request->type ?? '-'),
            (float) $request->grand_total,
            Blocks::badge($request->status->label(), MaintenanceSource::TONES[$request->status->color()] ?? 'slate'),
        ])->all();

        return Blocks::table([
            ['label' => 'Order #', 'strong' => true],
            ['label' => 'Date'],
            ['label' => 'Property'],
            ['label' => 'Type'],
            ['label' => 'Amount', 'align' => 'end', 'format' => 'money'],
            ['label' => 'Status'],
        ], $rows, 'No supply requests yet.');
    }
}
