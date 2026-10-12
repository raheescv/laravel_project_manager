<?php

namespace App\Services\Dashboard;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Route;

/**
 * One area of the business (sales, maintenance, leads, …) feeding one or more
 * dashboard widgets. A source declares its widgets and builds each one's
 * payload: a list of display blocks (see {@see Blocks}) plus an optional link.
 */
abstract class DashboardSource
{
    protected const PERIODS = ['today' => 'Today', 'week' => 'Week', 'month' => 'Month', 'all' => 'All'];

    /**
     * @return list<WidgetDefinition>
     */
    abstract public function widgets(): array;

    /**
     * @param  array<string, mixed>  $params
     * @return array{blocks: list<array<string, mixed>>, link?: array{label: string, url: string}|null}
     */
    abstract public function data(string $key, ?string $option, array $params = []): array;

    /**
     * @return array{0: CarbonInterface, 1: CarbonInterface}|null
     */
    protected function periodRange(?string $period): ?array
    {
        return match ($period) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'week' => [now()->startOfWeek(), now()->endOfWeek()],
            'month' => [now()->startOfMonth(), now()->endOfMonth()],
            default => null,
        };
    }

    /**
     * A footer link, only when the route exists and the user may open it.
     *
     * @param  array<string, mixed>  $parameters
     * @return array{label: string, url: string}|null
     */
    protected function link(string $label, string $route, string $permission, array $parameters = []): ?array
    {
        if (! Route::has($route) || ! auth()->user()?->can($permission)) {
            return null;
        }

        return ['label' => $label, 'url' => route($route, $parameters)];
    }

    protected function percentage(float|int $part, float|int $whole, int $precision = 1): float
    {
        return $whole > 0 ? round(($part / $whole) * 100, $precision) : 0.0;
    }

    protected function rateTone(float $rate, float $good = 80, float $fair = 60): string
    {
        return $rate >= $good ? 'ok' : ($rate >= $fair ? 'warn' : 'bad');
    }
}
