<?php

namespace App\Livewire\Concerns;

/**
 * The quick period shortcuts behind the report filter rail's chips.
 *
 * The using component must declare public `$from_date` and `$to_date` and use
 * Livewire's WithPagination.
 */
trait HasReportPeriod
{
    public const RANGES = ['this_month' => 'This month', 'last_month' => 'Last month', 'last_30' => 'Last 30 days'];

    public function setRange(string $key): void
    {
        if (! array_key_exists($key, self::RANGES)) {
            return;
        }

        [$this->from_date, $this->to_date] = $this->rangeDates($key);
        $this->resetPage();
    }

    /** Which quick range the chosen dates currently match, if any. */
    public function currentRange(): ?string
    {
        foreach (array_keys(self::RANGES) as $key) {
            if ([$this->from_date, $this->to_date] === $this->rangeDates($key)) {
                return $key;
            }
        }

        return null;
    }

    /** @return array{0: string, 1: string} */
    private function rangeDates(string $key): array
    {
        [$from, $to] = match ($key) {
            'last_month' => [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()],
            'last_30' => [now()->subDays(29), now()],
            default => [now()->startOfMonth(), now()],
        };

        return [$from->toDateString(), $to->toDateString()];
    }
}
