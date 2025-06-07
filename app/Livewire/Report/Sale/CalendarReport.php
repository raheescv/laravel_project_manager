<?php

namespace App\Livewire\Report\Sale;

use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class CalendarReport extends Component
{
    public $branch_id = '';

    public $month;

    public $year;

    public $payment_method_id = '';

    public $sale_type = '';

    public $view_mode = 'calendar'; // calendar or heatmap

    public $compare_previous = false;

    public $selected_day = null;

    public $day_details = [];

    protected function rules()
    {
        return [
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000|max:2100',
        ];
    }

    public function mount()
    {
        $this->branch_id = session('branch_id');
        $this->month = date('m');
        $this->year = date('Y');
    }

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
    }

    public function previousMonth()
    {
        if ($this->month == 1) {
            $this->month = 12;
            $this->year--;
        } else {
            $this->month--;
        }
    }

    public function nextMonth()
    {
        if ($this->month == 12) {
            $this->month = 1;
            $this->year++;
        } else {
            $this->month++;
        }
    }

    public function getCurrentMonthDays()
    {
        $date = Carbon::createFromDate($this->year, $this->month, 1);
        $daysInMonth = $date->daysInMonth;

        // Get the days in the month including empty days at start/end to fill the calendar grid
        $firstDayOfWeek = $date->copy()->firstOfMonth()->dayOfWeek;

        // Adjust for week starting on Sunday (0)
        // If week starts on Monday, use: ($firstDayOfWeek - 1 + 7) % 7
        $emptyDaysAtStart = $firstDayOfWeek;

        $days = collect();

        // Add empty days at the start
        for ($i = 0; $i < $emptyDaysAtStart; $i++) {
            $days->push(['day' => null, 'date' => null, 'total' => 0, 'count' => 0]);
        }

        // Add actual days with date values
        for ($i = 1; $i <= $daysInMonth; $i++) {
            $days->push(['day' => $i, 'date' => $this->year.'-'.str_pad($this->month, 2, '0', STR_PAD_LEFT).'-'.str_pad($i, 2, '0', STR_PAD_LEFT), 'total' => 0, 'count' => 0]);
        }

        // Calculate empty days at end to complete last week row
        $totalCells = ceil($days->count() / 7) * 7;
        $emptyDaysAtEnd = $totalCells - $days->count();

        // Add empty days at end
        for ($i = 0; $i < $emptyDaysAtEnd; $i++) {
            $days->push(['day' => null, 'date' => null, 'total' => 0, 'count' => 0]);
        }

        return $days;
    }

    public function showDayDetails($date)
    {
        $this->selected_day = $date;

        // Get sales for selected day
        $sales = Sale::query()
            ->where('date', $date)
            ->when($this->branch_id, fn ($q) => $q->where('branch_id', $this->branch_id))
            ->where('status', 'completed')
            ->when($this->payment_method_id, function ($query) {
                return $query->whereRaw('FIND_IN_SET(?, payment_method_ids)', [$this->payment_method_id]);
            })
            ->when($this->sale_type, fn ($q) => $q->where('sale_type', $this->sale_type))
            ->with(['account:id,name,mobile', 'items.product:id,name'])
            ->select(['id', 'invoice_no', 'account_id', 'grand_total', 'created_at'])
            ->orderBy('created_at', 'desc')
            ->limit(15)
            ->get();

        $this->day_details = [
            'date' => Carbon::parse($date)->format('D, M d, Y'),
            'sales' => $sales,
            'total' => $sales->sum('grand_total'),
            'count' => $sales->count(),
        ];
    }

    public function closeDayDetails()
    {
        $this->selected_day = null;
        $this->day_details = [];
    }

    public function toggleViewMode()
    {
        $this->view_mode = $this->view_mode === 'calendar' ? 'heatmap' : 'calendar';
    }

    public function toggleComparison()
    {
        $this->compare_previous = ! $this->compare_previous;
    }

    public function goToToday()
    {
        $this->month = date('m');
        $this->year = date('Y');
    }

    public function render()
    {
        // Get days grid for calendar
        $days = $this->getCurrentMonthDays();

        // Extract the actual dates (non-empty days)
        $datesInMonth = $days->filter(function ($day) {
            return ! is_null($day['date']);
        })->pluck('date')->toArray();

        // Base query
        $baseQuery = Sale::query()
            ->where('status', 'completed')
            ->when($this->branch_id, fn ($q) => $q->where('branch_id', $this->branch_id))
            ->when($this->payment_method_id, function ($query) {
                return $query->whereRaw('FIND_IN_SET(?, payment_method_ids)', [$this->payment_method_id]);
            })
            ->when($this->sale_type, fn ($q) => $q->where('sale_type', $this->sale_type));

        // Query for sales data for this month
        $salesData = (clone $baseQuery)
            ->whereIn('date', $datesInMonth)
            ->select(
                'date',
                DB::raw('SUM(grand_total) as total'),
                DB::raw('COUNT(id) as count')
            )
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        // Get comparison data for previous month if enabled
        $prevMonthData = collect();
        if ($this->compare_previous) {
            $prevMonth = Carbon::createFromDate($this->year, $this->month, 1)->subMonth();
            $prevMonthDays = collect();

            // Get last month's days
            for ($i = 1; $i <= $prevMonth->daysInMonth; $i++) {
                $date = $prevMonth->format('Y-m-').str_pad($i, 2, '0', STR_PAD_LEFT);
                $prevMonthDays->push($date);
            }

            $prevMonthData = (clone $baseQuery)
                ->whereIn('date', $prevMonthDays)
                ->select(
                    'date',
                    DB::raw('SUM(grand_total) as total'),
                    DB::raw('COUNT(id) as count')
                )
                ->groupBy('date')
                ->get()
                ->keyBy(function ($item) {
                    // Convert to current month's date (for comparison)
                    $day = Carbon::parse($item->date)->day;

                    return $this->year.'-'.str_pad($this->month, 2, '0', STR_PAD_LEFT).'-'.str_pad($day, 2, '0', STR_PAD_LEFT);
                });
        }

        // Inject the sales data into our days collection
        $calendarData = $days->map(function ($day) use ($salesData, $prevMonthData) {
            if (! is_null($day['date']) && isset($salesData[$day['date']])) {
                $day['total'] = $salesData[$day['date']]->total;
                $day['count'] = $salesData[$day['date']]->count;
                // For heatmap intensity calculation
                $day['has_data'] = true;
            }

            // Add comparison data
            if ($this->compare_previous && ! is_null($day['date'])) {
                if (isset($prevMonthData[$day['date']])) {
                    $day['prev_total'] = $prevMonthData[$day['date']]->total;
                    $day['prev_count'] = $prevMonthData[$day['date']]->count;
                    $day['change_percent'] = $day['total'] > 0 && $day['prev_total'] > 0 ?
                        round((($day['total'] - $day['prev_total']) / $day['prev_total']) * 100, 1) : null;
                } else {
                    $day['prev_total'] = 0;
                    $day['prev_count'] = 0;
                    $day['change_percent'] = null;
                }
            }

            return $day;
        });

        // For heatmap calculations - find highest value for scaling
        $maxTotal = $calendarData->max('total') ?: 1;
        $calendarData = $calendarData->map(function ($day) use ($maxTotal) {
            if (! is_null($day['date'])) {
                $day['intensity'] = $day['total'] > 0 ? ($day['total'] / $maxTotal) : 0;
            }

            return $day;
        });

        // Calculate monthly totals and stats
        $monthlyTotal = $salesData->sum('total');
        $monthlyCount = $salesData->sum('count');

        // Get day with highest sales
        $bestDay = $salesData->sortByDesc('total')->first();
        $bestDayInfo = $bestDay ? [
            'date' => Carbon::parse($bestDay->date)->format('M d'),
            'total' => $bestDay->total,
            'count' => $bestDay->count,
        ] : null;

        // Calculate average daily sales (only for days with sales)
        $salesDaysCount = $salesData->count();
        $avgDailySales = $salesDaysCount > 0 ? $monthlyTotal / $salesDaysCount : 0;

        // Previous month comparison
        $prevMonthTotal = 0;
        $prevMonthCompare = null;

        if ($this->compare_previous && $prevMonthData->count() > 0) {
            $prevMonthTotal = $prevMonthData->sum('total');
            $prevMonthCompare = [
                'total' => $prevMonthTotal,
                'count' => $prevMonthData->sum('count'),
                'percent_change' => $prevMonthTotal > 0 ?
                    round((($monthlyTotal - $prevMonthTotal) / $prevMonthTotal) * 100, 1) : null,
            ];
        }

        // Format days into weeks for the calendar view
        $weeks = $calendarData->chunk(7);

        // Get month name
        $monthName = Carbon::createFromDate($this->year, $this->month, 1)->format('F Y');

        return view('livewire.report.sale.calendar-report', [
            'weeks' => $weeks,
            'monthName' => $monthName,
            'monthlyTotal' => $monthlyTotal,
            'monthlyCount' => $monthlyCount,
            'bestDayInfo' => $bestDayInfo,
            'avgDailySales' => $avgDailySales,
            'prevMonthCompare' => $prevMonthCompare ?? null,
            'maxTotal' => $maxTotal,
        ]);
    }
}
