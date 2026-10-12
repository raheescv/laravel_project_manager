<?php

namespace App\Services\Dashboard\Sources;

use App\Models\Appointment;
use App\Models\AppointmentItem;
use App\Services\Dashboard\Blocks;
use App\Services\Dashboard\DashboardSource;
use App\Services\Dashboard\WidgetDefinition;
use Carbon\Carbon;

/**
 * Appointment counts per day by status, and the next pending appointments.
 * Ported from the Livewire `dashboard.appointment.*` components.
 */
class AppointmentSource extends DashboardSource
{
    public function widgets(): array
    {
        return [
            new WidgetDefinition('appointment-chart', 'Appointments', 'Appointments', 'Completed · pending · cancelled', 'calendar', 'chart', 8, 6, ['appointment.dashboard'], ['week' => 'Week', 'month' => 'Month'], 'week'),
            new WidgetDefinition('upcoming-appointments', 'Appointments', 'Upcoming appointments', 'Pending · next 7 days', 'clock-o', 'list', 4, 4, ['appointment.dashboard']),
        ];
    }

    public function data(string $key, ?string $option, array $params = []): array
    {
        return $key === 'upcoming-appointments' ? $this->upcoming() : $this->chart((string) $option);
    }

    /**
     * @return array{blocks: list<array<string, mixed>>, link: array{label: string, url: string}|null}
     */
    protected function chart(string $period): array
    {
        [$start, $end] = $period === 'month'
            ? [now()->startOfMonth(), now()->endOfMonth()]
            : [now()->startOfWeek(), now()->endOfWeek()];

        $counts = Appointment::query()
            ->whereBetween('date', [$start, $end])
            ->selectRaw('DATE(date) as day, status, COUNT(*) as total')
            ->groupBy('day', 'status')
            ->get()
            ->groupBy('day')
            ->map(fn ($rows) => $rows->pluck('total', 'status'));

        $labels = $completed = $pending = $cancelled = [];
        for ($day = $start->copy(); $day <= $end; $day->addDay()) {
            $date = $day->toDateString();
            $labels[] = $day->format('M d');
            $completed[] = (int) ($counts[$date]['completed'] ?? 0);
            $pending[] = (int) ($counts[$date]['pending'] ?? 0);
            $cancelled[] = (int) ($counts[$date]['cancelled'] ?? 0);
        }

        return [
            'blocks' => [Blocks::bars($labels, [
                Blocks::series('Completed', 'ok', $completed),
                Blocks::series('Pending', 'warn', $pending),
                Blocks::series('Cancelled', 'bad', $cancelled),
            ], 'number', 190, legend: true)],
            'link' => $this->link('Open appointments', 'appointment::index', 'appointment.view'),
        ];
    }

    /**
     * @return array{blocks: list<array<string, mixed>>, link: array{label: string, url: string}|null}
     */
    protected function upcoming(): array
    {
        $items = AppointmentItem::query()
            ->with(['appointment:id,account_id,start_time,end_time,color,status', 'appointment.account:id,name', 'service:id,name', 'employee:id,name'])
            ->whereHas('appointment', function ($query): void {
                // @phpstan-ignore method.notFound (Larastan types whereHas closures as Builder<Model>; this is Builder<Appointment>::scopePending)
                $query->pending()->where('start_time', '>=', now())->where('start_time', '<=', now()->addDays(7));
            })
            ->latest()
            ->limit(5)
            ->get();

        $rows = $items->map(function (AppointmentItem $item): array {
            $start = Carbon::parse($item->appointment?->start_time);

            return [
                'icon' => 'calendar',
                'tone' => $start->isToday() ? 'warn' : 'acc',
                'title' => $item->service?->name ?? 'Appointment',
                'sub' => trim('with '.($item->employee?->name ?? '—').' · '.($item->appointment?->account?->name ?? ''), ' ·'),
                'badge' => ['text' => $start->isToday() ? 'Today '.$start->format('H:i') : $start->diffForHumans(), 'tone' => $start->isToday() ? 'warn' : 'slate'],
            ];
        })->all();

        return [
            'blocks' => [Blocks::list($rows, 'No upcoming appointments.')],
            'link' => $this->link('All appointments', 'appointment::list', 'appointment.view'),
        ];
    }
}
