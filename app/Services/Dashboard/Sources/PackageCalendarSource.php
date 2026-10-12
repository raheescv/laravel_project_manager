<?php

namespace App\Services\Dashboard\Sources;

use App\Models\PackageItem;
use App\Services\Dashboard\DashboardSource;
use App\Services\Dashboard\WidgetDefinition;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Package visits on a day / week / month / year calendar. An item sits on its
 * rescheduled date when it has one. Ported from the Livewire
 * `package.package-calendar` component (which stays, for the package pages);
 * unlike it, only the visible range is loaded.
 */
class PackageCalendarSource extends DashboardSource
{
    /** A day with several statuses shows the most urgent one. */
    protected const PRECEDENCE = ['pending', 'rescheduled', 'visited'];

    public function widgets(): array
    {
        return [
            new WidgetDefinition('package-calendar', 'Packages', 'Package calendar', 'Scheduled package visits', 'calendar-o', 'cal', 12, 8, ['package.dashboard package calendar'], ['day' => 'Day', 'week' => 'Week', 'month' => 'Month', 'year' => 'Year'], 'month'),
        ];
    }

    public function data(string $key, ?string $option, array $params = []): array
    {
        $date = $this->parseDate($params['date'] ?? null);

        $calendar = match ($option) {
            'day' => $this->day($date),
            'week' => $this->week($date),
            'year' => $this->year($date),
            default => $this->month($date),
        };

        return [
            'blocks' => [['type' => 'calendar'] + $calendar],
            'link' => $this->link('Open packages', 'package::index', 'package.view'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function day(CarbonInterface $date): array
    {
        $items = $this->itemsBetween($date->copy()->startOfDay(), $date->copy()->endOfDay());

        return [
            'view' => 'day',
            'title' => $date->format('l, j F Y'),
            'prev' => $date->copy()->subDay()->toDateString(),
            'next' => $date->copy()->addDay()->toDateString(),
            'items' => $items->get($date->toDateString(), collect())->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function week(CarbonInterface $date): array
    {
        $start = $date->copy()->startOfWeek();
        $end = $start->copy()->endOfWeek();
        $items = $this->itemsBetween($start, $end);

        $days = [];
        for ($day = $start->copy(); $day <= $end; $day->addDay()) {
            $days[] = [
                'date' => $day->toDateString(),
                'label' => $day->format('D j'),
                'today' => $day->isToday(),
                'items' => $items->get($day->toDateString(), collect())->values()->all(),
            ];
        }

        return [
            'view' => 'week',
            'title' => $start->format('j M').' – '.$end->format('j M Y'),
            'prev' => $start->copy()->subWeek()->toDateString(),
            'next' => $start->copy()->addWeek()->toDateString(),
            'days' => $days,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function month(CarbonInterface $date): array
    {
        $start = $date->copy()->startOfMonth();
        $items = $this->itemsBetween($start, $start->copy()->endOfMonth());

        return [
            'view' => 'month',
            'title' => $start->format('F Y'),
            'prev' => $start->copy()->subMonth()->toDateString(),
            'next' => $start->copy()->addMonth()->toDateString(),
        ] + $this->monthGrid($start, $items);
    }

    /**
     * @return array<string, mixed>
     */
    protected function year(CarbonInterface $date): array
    {
        $start = $date->copy()->startOfYear();
        $items = $this->itemsBetween($start, $start->copy()->endOfYear());

        $months = [];
        for ($month = $start->copy(); $month->year === $start->year; $month->addMonth()) {
            $months[] = ['name' => $month->format('M')] + $this->monthGrid($month->copy(), $items);
        }

        return [
            'view' => 'year',
            'title' => (string) $start->year,
            'prev' => $start->copy()->subYear()->toDateString(),
            'next' => $start->copy()->addYear()->toDateString(),
            'months' => $months,
        ];
    }

    /**
     * @param  Collection<string, Collection<int, array<string, mixed>>>  $items
     * @return array{blanks: int, days: list<array{date: string, day: int, today: bool, count: int, status: ?string, rescheduled: bool}>}
     */
    protected function monthGrid(CarbonInterface $start, Collection $items): array
    {
        $days = [];
        for ($day = $start->copy(); $day->month === $start->month; $day->addDay()) {
            $dayItems = $items->get($day->toDateString(), collect());
            $statuses = $dayItems->pluck('status');
            $days[] = [
                'date' => $day->toDateString(),
                'day' => $day->day,
                'today' => $day->isToday(),
                'count' => $dayItems->count(),
                'status' => collect(self::PRECEDENCE)->first(fn (string $status): bool => $statuses->contains($status)),
                'rescheduled' => $dayItems->contains('rescheduled', true),
            ];
        }

        return ['blanks' => $start->dayOfWeek, 'days' => $days];
    }

    /**
     * Items keyed by the date they show on.
     *
     * @return Collection<string, Collection<int, array<string, mixed>>>
     */
    protected function itemsBetween(CarbonInterface $from, CarbonInterface $to): Collection
    {
        return PackageItem::query()
            ->with(['package:id,package_category_id,account_id', 'package.account:id,name', 'package.packageCategory:id,name'])
            ->where(function ($query) use ($from, $to): void {
                $query->whereBetween('rescheduled_date', [$from->toDateString(), $to->toDateString()])
                    ->orWhere(fn ($query) => $query->whereNull('rescheduled_date')->whereBetween('date', [$from->toDateString(), $to->toDateString()]));
            })
            ->get()
            ->map(fn (PackageItem $item): array => [
                'id' => $item->id,
                'date' => Carbon::parse($item->rescheduled_date ?: $item->date)->toDateString(),
                'status' => $item->status,
                'name' => $item->package?->packageCategory?->name ?? 'Package',
                'account' => $item->package?->account?->name ?? '—',
                'notes' => $item->notes,
                'rescheduled' => ! empty($item->rescheduled_date),
            ])
            ->groupBy('date');
    }

    protected function parseDate(mixed $value): CarbonInterface
    {
        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return Carbon::parse($value);
        }

        return today();
    }
}
