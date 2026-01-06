<?php

namespace App\Livewire\Package;

use App\Models\PackageItem;
use Carbon\Carbon;
use Livewire\Component;

class PackageCalendar extends Component
{
    public $package_id = null;

    public $viewMode = 'year'; // day, week, month, year

    public $selectedYear;

    public $selectedMonth;

    public $selectedDate;

    public $selectedMonthInput; // For month input field (YYYY-MM format)

    public $calendarData = [];

    protected $listeners = [
        'Refresh-PackageCalendar-Component' => '$refresh',
    ];

    public function mount($package_id = null)
    {
        $this->package_id = $package_id;
        $this->selectedYear = date('Y');
        $this->selectedMonth = date('m');
        $this->selectedMonthInput = date('Y-m');
        $this->selectedDate = date('Y-m-d');
        $this->loadCalendarData();
    }

    public function updatedPackageId()
    {
        $this->loadCalendarData();
    }

    public function updatedViewMode()
    {
        $this->loadCalendarData();
    }

    public function updatedSelectedYear()
    {
        $this->loadCalendarData();
    }

    public function updatedSelectedMonthInput()
    {
        // Handle month input format (YYYY-MM)
        if ($this->selectedMonthInput && strpos($this->selectedMonthInput, '-') !== false) {
            $parts = explode('-', $this->selectedMonthInput);
            if (count($parts) === 2) {
                $this->selectedYear = (int) $parts[0];
                $this->selectedMonth = (int) $parts[1];
            }
        }
        $this->loadCalendarData();
    }

    public function updatedSelectedMonth()
    {
        $this->selectedMonthInput = $this->selectedYear.'-'.str_pad($this->selectedMonth, 2, '0', STR_PAD_LEFT);
        $this->loadCalendarData();
    }

    public function updatedSelectedDate()
    {
        if ($this->selectedDate) {
            $date = Carbon::parse($this->selectedDate);
            $this->selectedYear = $date->year;
            $this->selectedMonth = $date->format('m');
        }
        $this->loadCalendarData();
    }

    public function setViewMode($mode)
    {
        $this->viewMode = $mode;

        // Update selected date/month/year based on current date when switching views
        $now = Carbon::now();
        if ($mode === 'day' || $mode === 'week') {
            $this->selectedDate = $now->format('Y-m-d');
        }
        if ($mode === 'month' || $mode === 'year') {
            $this->selectedMonth = (int) $now->format('m');
            $this->selectedYear = (int) $now->format('Y');
            $this->selectedMonthInput = $now->format('Y-m');
        }

        $this->loadCalendarData();
    }

    // Year navigation
    public function previousYear()
    {
        $this->selectedYear--;
        $this->loadCalendarData();
    }

    public function nextYear()
    {
        $this->selectedYear++;
        $this->loadCalendarData();
    }

    public function goToCurrentYear()
    {
        $this->selectedYear = date('Y');
        $this->selectedMonth = date('m');
        $this->selectedDate = date('Y-m-d');
        $this->loadCalendarData();
    }

    // Month navigation
    public function previousMonth()
    {
        $date = Carbon::create($this->selectedYear, $this->selectedMonth, 1);
        $date->subMonth();
        $this->selectedYear = $date->year;
        $this->selectedMonth = $date->month;
        $this->loadCalendarData();
    }

    public function nextMonth()
    {
        $date = Carbon::create($this->selectedYear, $this->selectedMonth, 1);
        $date->addMonth();
        $this->selectedYear = $date->year;
        $this->selectedMonth = $date->month;
        $this->loadCalendarData();
    }

    // Week navigation
    public function previousWeek()
    {
        $date = Carbon::parse($this->selectedDate);
        $date->subWeek();
        $this->selectedDate = $date->format('Y-m-d');
        $this->selectedYear = $date->year;
        $this->selectedMonth = $date->month;
        $this->loadCalendarData();
    }

    public function nextWeek()
    {
        $date = Carbon::parse($this->selectedDate);
        $date->addWeek();
        $this->selectedDate = $date->format('Y-m-d');
        $this->selectedYear = $date->year;
        $this->selectedMonth = $date->month;
        $this->loadCalendarData();
    }

    // Day navigation
    public function previousDay()
    {
        $date = Carbon::parse($this->selectedDate);
        $date->subDay();
        $this->selectedDate = $date->format('Y-m-d');
        $this->selectedYear = $date->year;
        $this->selectedMonth = $date->month;
        $this->loadCalendarData();
    }

    public function nextDay()
    {
        $date = Carbon::parse($this->selectedDate);
        $date->addDay();
        $this->selectedDate = $date->format('Y-m-d');
        $this->selectedYear = $date->year;
        $this->selectedMonth = $date->month;
        $this->loadCalendarData();
    }

    public function loadCalendarData()
    {
        $this->calendarData = [];

        // Get package items - use rescheduled_date if available, otherwise use date
        $query = PackageItem::query()
            ->with(['package:id,package_category_id,account_id', 'package.account:id,name', 'package.packageCategory:id,name']);

        if ($this->package_id) {
            $query->where('package_id', $this->package_id);
        }

        $items = $query->get();

        // Create a map of dates with item information
        $datesMap = [];
        foreach ($items as $item) {
            // Use rescheduled_date if available, otherwise use date
            $displayDate = $item->rescheduled_date ?: $item->date;
            $carbonDate = Carbon::parse($displayDate);
            $dateKey = $carbonDate->format('Y-m-d');

            if (! isset($datesMap[$dateKey])) {
                $datesMap[$dateKey] = [];
            }

            $datesMap[$dateKey][] = [
                'id' => $item->id,
                'date' => $item->rescheduled_date ?: $item->date,
                'rescheduled_date' => null,
                'display_date' => $displayDate,
                'status' => $item->status,
                'notes' => $item->notes,
                'package_id' => $item->package_id,
                'package_name' => $item->package && $item->package->packageCategory ? $item->package->packageCategory->name : 'N/A',
                'account_name' => $item->package && $item->package->account ? $item->package->account->name : 'N/A',
                'is_rescheduled' => ! empty($item->rescheduled_date),
            ];
        }

        switch ($this->viewMode) {
            case 'day':
                $this->loadDayView($datesMap);
                break;
            case 'week':
                $this->loadWeekView($datesMap);
                break;
            case 'month':
                $this->loadMonthView($datesMap);
                break;
            case 'year':
            default:
                $this->loadYearView($datesMap);
                break;
        }
    }

    private function loadDayView($datesMap)
    {
        $date = Carbon::parse($this->selectedDate);
        $dateKey = $date->format('Y-m-d');
        $items = $datesMap[$dateKey] ?? [];

        $this->calendarData = [
            'view' => 'day',
            'date' => $dateKey,
            'dateFormatted' => $date->format('l, F j, Y'),
            'items' => $items,
            'isToday' => $dateKey === date('Y-m-d'),
        ];
    }

    private function loadWeekView($datesMap)
    {
        $startDate = Carbon::parse($this->selectedDate)->startOfWeek();
        $endDate = $startDate->copy()->endOfWeek();

        $days = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $dateKey = $currentDate->format('Y-m-d');
            $items = $datesMap[$dateKey] ?? [];

            $days[] = [
                'date' => $dateKey,
                'day' => $currentDate->day,
                'dayName' => $currentDate->format('D'),
                'dayFullName' => $currentDate->format('l'),
                'hasItems' => ! empty($items),
                'items' => $items,
                'isToday' => $dateKey === date('Y-m-d'),
            ];

            $currentDate->addDay();
        }

        $this->calendarData = [
            'view' => 'week',
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d'),
            'weekRange' => $startDate->format('M j').' - '.$endDate->format('M j, Y'),
            'days' => $days,
        ];
    }

    private function loadMonthView($datesMap)
    {
        $firstDayOfMonth = Carbon::create($this->selectedYear, $this->selectedMonth, 1);
        $daysInMonth = $firstDayOfMonth->daysInMonth;
        $startDayOfWeek = $firstDayOfMonth->dayOfWeek; // 0 = Sunday, 6 = Saturday
        $monthName = $firstDayOfMonth->format('M');
        $monthFullName = $firstDayOfMonth->format('F');

        // Build calendar grid
        $weeks = [];
        $currentWeek = [];

        // Add empty cells for days before the first day of the month
        for ($i = 0; $i < $startDayOfWeek; $i++) {
            $currentWeek[] = null;
        }

        // Add days of the month
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $dateKey = Carbon::create($this->selectedYear, $this->selectedMonth, $day)->format('Y-m-d');
            $hasItems = isset($datesMap[$dateKey]);
            $dayItems = $hasItems ? $datesMap[$dateKey] : [];

            $currentWeek[] = [
                'day' => $day,
                'hasItems' => $hasItems,
                'items' => $dayItems,
                'date' => $dateKey,
                'isToday' => $dateKey === date('Y-m-d'),
            ];

            if (count($currentWeek) == 7) {
                $weeks[] = $currentWeek;
                $currentWeek = [];
            }
        }

        // Fill remaining days in the last week
        while (count($currentWeek) < 7 && count($currentWeek) > 0) {
            $currentWeek[] = null;
        }
        if (! empty($currentWeek)) {
            $weeks[] = $currentWeek;
        }

        $this->calendarData = [
            'view' => 'month',
            'year' => $this->selectedYear,
            'month' => $this->selectedMonth,
            'monthName' => $monthName,
            'monthFull' => $monthFullName,
            'weeks' => $weeks,
        ];
    }

    private function loadYearView($datesMap)
    {
        $yearData = [
            'view' => 'year',
            'year' => $this->selectedYear,
            'months' => [],
        ];

        // Generate all 12 months for the year
        for ($month = 1; $month <= 12; $month++) {
            $firstDayOfMonth = Carbon::create($this->selectedYear, $month, 1);
            $daysInMonth = $firstDayOfMonth->daysInMonth;
            $startDayOfWeek = $firstDayOfMonth->dayOfWeek; // 0 = Sunday, 6 = Saturday
            $monthName = $firstDayOfMonth->format('M');
            $monthFullName = $firstDayOfMonth->format('F');

            // Build calendar grid
            $weeks = [];
            $currentWeek = [];

            // Add empty cells for days before the first day of the month
            for ($i = 0; $i < $startDayOfWeek; $i++) {
                $currentWeek[] = null;
            }

            // Add days of the month
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $dateKey = Carbon::create($this->selectedYear, $month, $day)->format('Y-m-d');
                $hasItems = isset($datesMap[$dateKey]);
                $dayItems = $hasItems ? $datesMap[$dateKey] : [];

                $currentWeek[] = [
                    'day' => $day,
                    'hasItems' => $hasItems,
                    'items' => $dayItems,
                    'date' => $dateKey,
                    'isToday' => $dateKey === date('Y-m-d'),
                ];

                if (count($currentWeek) == 7) {
                    $weeks[] = $currentWeek;
                    $currentWeek = [];
                }
            }

            // Fill remaining days in the last week
            while (count($currentWeek) < 7 && count($currentWeek) > 0) {
                $currentWeek[] = null;
            }
            if (! empty($currentWeek)) {
                $weeks[] = $currentWeek;
            }

            $yearData['months'][] = [
                'month' => $monthName,
                'monthFull' => $monthFullName,
                'monthNum' => $month,
                'weeks' => $weeks,
            ];
        }

        $this->calendarData = $yearData;
    }

    public function getFormattedDate()
    {
        switch ($this->viewMode) {
            case 'day':
                return Carbon::parse($this->selectedDate)->format('l, F j, Y');
            case 'week':
                $startDate = Carbon::parse($this->selectedDate)->startOfWeek();
                $endDate = $startDate->copy()->endOfWeek();

                return $startDate->format('M j').' - '.$endDate->format('M j, Y');
            case 'month':
                return Carbon::create($this->selectedYear, $this->selectedMonth, 1)->format('F Y');
            case 'year':
            default:
                return $this->selectedYear;
        }
    }

    public function isTodayActive()
    {
        $today = Carbon::now()->format('Y-m-d');
        switch ($this->viewMode) {
            case 'day':
                return $this->selectedDate === $today;
            case 'week':
                $startDate = Carbon::parse($this->selectedDate)->startOfWeek();
                $endDate = $startDate->copy()->endOfWeek();

                return $today >= $startDate->format('Y-m-d') && $today <= $endDate->format('Y-m-d');
            case 'month':
                return $this->selectedYear == date('Y') && $this->selectedMonth == date('m');
            case 'year':
            default:
                return $this->selectedYear == date('Y');
        }
    }

    public function render()
    {
        return view('livewire.package.package-calendar');
    }
}
