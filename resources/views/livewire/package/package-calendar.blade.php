<div>
    <style>
        .package-calendar-container {
            background: #ffffff;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            padding: 1.5rem;
        }

        .calendar-header {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: #ffffff;
            padding: 1.5rem 2rem;
            border-radius: 1rem 1rem 0 0;
            margin: -1.5rem -1.5rem 1.5rem -1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            min-height: 80px;
        }

        .calendar-header-date {
            font-size: 1.75rem;
            font-weight: 600;
            color: #ffffff;
            margin: 0;
            letter-spacing: -0.02em;
        }

        .calendar-header-controls {
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }

        .calendar-nav-group {
            display: flex;
            gap: 0.25rem;
            background: rgba(255, 255, 255, 0.2);
            padding: 0.375rem;
            border-radius: 0.75rem;
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .calendar-nav-btn {
            background: transparent;
            border: none;
            color: #ffffff;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
            font-weight: 500;
            font-size: 0.875rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.375rem;
            min-width: 40px;
        }

        .calendar-nav-btn:hover {
            background: rgba(255, 255, 255, 0.15);
        }

        .calendar-nav-btn.active {
            background: rgba(255, 255, 255, 0.3);
            font-weight: 600;
        }

        .calendar-nav-btn i {
            font-size: 0.875rem;
        }

        .calendar-year-section {
            margin-bottom: 2rem;
        }

        .calendar-year-title {
            text-align: center;
            font-size: 1.75rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: linear-gradient(to right, #f8fafc, #f1f5f9);
            border-radius: 0.5rem;
        }

        .calendar-month-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .calendar-month-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            padding: 0.75rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: all 0.2s ease;
        }

        .calendar-month-card:hover {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .calendar-month-header {
            text-align: center;
            font-weight: 600;
            color: #4f46e5;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
            padding: 0.5rem;
            background: #f8fafc;
            border-radius: 0.375rem;
        }

        .calendar-table {
            width: 100%;
            font-size: 0.7rem;
            margin: 0;
        }

        .calendar-table thead th {
            background-color: #e7f3ff;
            color: #1e293b;
            font-weight: 600;
            text-align: center;
            padding: 0.25rem;
            font-size: 0.65rem;
            border: 1px solid #dee2e6;
        }

        .calendar-table tbody td {
            padding: 0.25rem;
            text-align: center;
            border: 1px solid #dee2e6;
            vertical-align: middle;
            position: relative;
        }

        .calendar-day-empty {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
        }

        .calendar-day {
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .calendar-day:hover {
            background-color: #f1f5f9 !important;
            transform: scale(1.05);
        }

        .calendar-day-has-items {
            background-color: #dbeafe;
            border-color: #3b82f6;
            font-weight: 600;
            color: #1e40af;
        }

        .calendar-day-today {
            background-color: #dbeafe !important;
            border-color: #3b82f6 !important;
            border-width: 2px !important;
            color: #1e40af !important;
            font-weight: 700;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
        }

        .calendar-day-rescheduled {
            background-color: #fce7f3 !important;
            border-color: #ec4899 !important;
        }

        .calendar-day-visited {
            background-color: #d1fae5 !important;
            border-color: #10b981 !important;
        }

        .calendar-day-pending {
            background-color: #e0e7ff !important;
            border-color: #6366f1 !important;
        }

        .day-number {
            display: inline-block;
            min-width: 1.5rem;
            padding: 0.125rem 0.25rem;
            border-radius: 0.25rem;
        }

        .item-count-badge {
            position: absolute;
            top: 2px;
            right: 2px;
            background: #ef4444;
            color: #ffffff;
            border-radius: 50%;
            width: 1rem;
            height: 1rem;
            font-size: 0.6rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }

        .item-details-popover {
            position: absolute;
            z-index: 1000;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            padding: 1rem;
            min-width: 250px;
            max-width: 350px;
            display: none;
        }

        .item-details-popover.show {
            display: block;
        }

        .item-detail-item {
            padding: 0.5rem;
            border-bottom: 1px solid #f1f5f9;
        }

        .item-detail-item:last-child {
            border-bottom: none;
        }

        .status-badge {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.65rem;
            font-weight: 500;
            display: inline-block;
        }

        .status-visited {
            background-color: #d1fae5;
            color: #059669;
        }

        .status-rescheduled {
            background-color: #fef3c7;
            color: #d97706;
        }

        .status-pending {
            background-color: #e0e7ff;
            color: #4f46e5;
        }

        .rescheduled-indicator {
            font-size: 0.6rem;
            color: #f59e0b;
            font-weight: 600;
        }

        .view-mode-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .view-mode-btn {
            padding: 0.5rem 1.25rem;
            border: none;
            background: rgba(255, 255, 255, 0.15);
            color: #ffffff;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
            font-weight: 500;
            font-size: 0.875rem;
            cursor: pointer;
        }

        .view-mode-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .view-mode-btn.active {
            background: rgba(255, 255, 255, 0.3);
            font-weight: 600;
        }

        .day-view-container {
            padding: 1.5rem;
            background: #f8fafc;
            border-radius: 0.5rem;
        }

        .day-view-header {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 1rem;
        }

        .day-view-items {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .day-view-item-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            padding: 1rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .week-view-container {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 0.5rem;
        }

        .week-day-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            padding: 0.75rem;
            min-height: 200px;
        }

        .week-day-header {
            font-weight: 600;
            color: #4f46e5;
            margin-bottom: 0.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .week-day-items {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .week-day-item {
            padding: 0.5rem;
            background: #f8fafc;
            border-radius: 0.25rem;
            font-size: 0.75rem;
        }

        .month-view-container {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            padding: 1rem;
        }

        .month-view-header {
            text-align: center;
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 1rem;
        }

        /* Legend Styling */
        .legend-container {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 1.25rem 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .legend-header {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .legend-header i {
            color: #6366f1;
            font-size: 1.1rem;
        }

        .legend-title {
            font-size: 0.875rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .legend-items {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .legend-color {
            display: inline-block;
            width: 24px;
            height: 24px;
            border-radius: 0.375rem;
            border: 1px solid rgba(0, 0, 0, 0.1);
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .legend-color.legend-has-items {
            background-color: #dbeafe;
            border-color: #3b82f6;
        }

        .legend-color.legend-visited {
            background-color: #d1fae5;
            border-color: #10b981;
        }

        .legend-color.legend-rescheduled {
            background-color: #fce7f3;
            border-color: #ec4899;
        }

        .legend-color.legend-pending {
            background-color: #e0e7ff;
            border-color: #6366f1;
        }

        .legend-color.legend-today {
            background-color: #dbeafe;
            border-color: #3b82f6;
            border-width: 2px;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
        }

        .legend-label {
            font-size: 0.875rem;
            color: #475569;
            font-weight: 500;
        }
    </style>

    <div class="package-calendar-container">
        <div class="calendar-header">
            <!-- Date Display on Left -->
            <div class="calendar-header-date">
                {{ $this->getFormattedDate() }}
            </div>

            <!-- Controls on Right -->
            <div class="calendar-header-controls">
                <!-- Navigation Buttons Group -->
                <div class="calendar-nav-group">
                    @if($viewMode === 'day')
                        <button class="calendar-nav-btn" wire:click="previousDay" title="Previous Day">
                            <i class="demo-psi-arrow-left-2"></i>
                        </button>
                        <button class="calendar-nav-btn {{ $this->isTodayActive() ? 'active' : '' }}" wire:click="goToCurrentYear" title="Today">
                            Today
                        </button>
                        <button class="calendar-nav-btn" wire:click="nextDay" title="Next Day">
                            <i class="demo-psi-arrow-right-2"></i>
                        </button>
                    @elseif($viewMode === 'week')
                        <button class="calendar-nav-btn" wire:click="previousWeek" title="Previous Week">
                            <i class="demo-psi-arrow-left-2"></i>
                        </button>
                        <button class="calendar-nav-btn {{ $this->isTodayActive() ? 'active' : '' }}" wire:click="goToCurrentYear" title="This Week">
                            Today
                        </button>
                        <button class="calendar-nav-btn" wire:click="nextWeek" title="Next Week">
                            <i class="demo-psi-arrow-right-2"></i>
                        </button>
                    @elseif($viewMode === 'month')
                        <button class="calendar-nav-btn" wire:click="previousMonth" title="Previous Month">
                            <i class="demo-psi-arrow-left-2"></i>
                        </button>
                        <button class="calendar-nav-btn {{ $this->isTodayActive() ? 'active' : '' }}" wire:click="goToCurrentYear" title="Current Month">
                            Today
                        </button>
                        <button class="calendar-nav-btn" wire:click="nextMonth" title="Next Month">
                            <i class="demo-psi-arrow-right-2"></i>
                        </button>
                    @else
                        <button class="calendar-nav-btn" wire:click="previousYear" title="Previous Year">
                            <i class="demo-psi-arrow-left-2"></i>
                        </button>
                        <button class="calendar-nav-btn {{ $this->isTodayActive() ? 'active' : '' }}" wire:click="goToCurrentYear" title="Current Year">
                            Today
                        </button>
                        <button class="calendar-nav-btn" wire:click="nextYear" title="Next Year">
                            <i class="demo-psi-arrow-right-2"></i>
                        </button>
                    @endif
                </div>

                <!-- View Mode Buttons -->
                <div class="view-mode-buttons">
                    <button class="view-mode-btn {{ $viewMode === 'day' ? 'active' : '' }}" wire:click="setViewMode('day')" title="Day View">
                        Day
                    </button>
                    <button class="view-mode-btn {{ $viewMode === 'week' ? 'active' : '' }}" wire:click="setViewMode('week')" title="Week View">
                        Week
                    </button>
                    <button class="view-mode-btn {{ $viewMode === 'month' ? 'active' : '' }}" wire:click="setViewMode('month')" title="Month View">
                        Month
                    </button>
                    <button class="view-mode-btn {{ $viewMode === 'year' ? 'active' : '' }}" wire:click="setViewMode('year')" title="Year View">
                        Year
                    </button>
                </div>
            </div>
        </div>

        <!-- Hidden Date/Month/Year Pickers -->
        <div class="d-none">
            @if($viewMode === 'day')
                <input type="date" wire:model.live="selectedDate">
            @elseif($viewMode === 'week')
                <input type="date" wire:model.live="selectedDate">
            @elseif($viewMode === 'month')
                <input type="month" wire:model.live="selectedMonthInput">
            @else
                <input type="number" wire:model.live="selectedYear" min="2000" max="2100">
            @endif
        </div>

        @if(!empty($calendarData))
            @if($viewMode === 'day')
                <!-- Day View -->
                <div class="day-view-container">
                    <div class="day-view-header">
                        <i class="demo-psi-calendar-day me-2"></i>{{ $calendarData['dateFormatted'] }}
                        @if($calendarData['isToday'])
                            <span class="badge bg-warning text-dark ms-2">Today</span>
                        @endif
                    </div>
                    @if(count($calendarData['items']) > 0)
                        <div class="day-view-items">
                            @foreach($calendarData['items'] as $item)
                                <div class="day-view-item-card">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h6 class="mb-1">{{ $item['package_name'] }}</h6>
                                            <small class="text-muted">{{ $item['account_name'] }}</small>
                                        </div>
                                        <span class="status-badge status-{{ $item['status'] }}">
                                            {{ ucfirst($item['status']) }}
                                        </span>
                                    </div>
                                    @if($item['is_rescheduled'])
                                        <div class="mb-2">
                                            <small class="text-warning">
                                                <i class="demo-psi-arrow-right-2"></i>
                                                Rescheduled from {{ $item['date'] }} to {{ $item['rescheduled_date'] }}
                                            </small>
                                        </div>
                                    @endif
                                    @if($item['notes'])
                                        <div class="mt-2">
                                            <small class="text-muted">{{ $item['notes'] }}</small>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="demo-psi-information" style="font-size: 3rem; color: #cbd5e1;"></i>
                            <p class="text-muted mt-3">No package items for this day</p>
                        </div>
                    @endif
                </div>
            @elseif($viewMode === 'week')
                <!-- Week View -->
                <div class="mb-3">
                    <h5 class="text-center">{{ $calendarData['weekRange'] }}</h5>
                </div>
                <div class="week-view-container">
                    @foreach($calendarData['days'] as $day)
                        <div class="week-day-card {{ $day['isToday'] ? 'calendar-day-today' : '' }}">
                            <div class="week-day-header">
                                <div class="fw-bold">{{ $day['dayName'] }}</div>
                                <div class="small">{{ $day['day'] }}</div>
                            </div>
                            @if($day['hasItems'])
                                <div class="week-day-items">
                                    @foreach($day['items'] as $item)
                                        <div class="week-day-item">
                                            <div class="fw-semibold small">{{ $item['package_name'] }}</div>
                                            <div class="text-muted" style="font-size: 0.65rem;">{{ $item['account_name'] }}</div>
                                            <span class="status-badge status-{{ $item['status'] }}" style="font-size: 0.6rem;">
                                                {{ ucfirst($item['status']) }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center text-muted small mt-3">No items</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @elseif($viewMode === 'month')
                <!-- Month View -->
                <div class="month-view-container">
                    <div class="month-view-header">
                        <i class="demo-psi-calendar-month me-2"></i>{{ $calendarData['monthFull'] }} {{ $calendarData['year'] }}
                    </div>
                    <table class="calendar-table table-bordered">
                        <thead>
                            <tr>
                                <th>S</th>
                                <th>M</th>
                                <th>T</th>
                                <th>W</th>
                                <th>T</th>
                                <th>F</th>
                                <th>S</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($calendarData['weeks'] as $week)
                                <tr>
                                    @foreach($week as $day)
                                        @if($day === null)
                                            <td class="calendar-day-empty"></td>
                                        @else
                                            @php
                                                $dayClasses = ['calendar-day'];
                                                $itemCount = count($day['items']);
                                                $hasItems = $day['hasItems'];

                                                if ($day['isToday']) {
                                                    $dayClasses[] = 'calendar-day-today';
                                                }

                                                if ($hasItems) {
                                                    $dayClasses[] = 'calendar-day-has-items';

                                                    // Add status-based classes
                                                    $statuses = collect($day['items'])->pluck('status')->unique();
                                                    if ($statuses->contains('visited')) {
                                                        $dayClasses[] = 'calendar-day-visited';
                                                    }
                                                    if ($statuses->contains('rescheduled')) {
                                                        $dayClasses[] = 'calendar-day-rescheduled';
                                                    }
                                                    if ($statuses->contains('pending')) {
                                                        $dayClasses[] = 'calendar-day-pending';
                                                    }
                                                }
                                            @endphp
                                            <td class="{{ implode(' ', $dayClasses) }}"
                                                @if($hasItems)
                                                    wire:click="$dispatch('show-package-item-details', { date: '{{ $day['date'] }}', items: @js($day['items']) })"
                                                    title="{{ $itemCount }} item(s) on {{ $day['date'] }}"
                                                @endif>
                                                <div class="day-number">
                                                    {{ $day['day'] }}
                                                </div>
                                                @if($hasItems && $itemCount > 1)
                                                    <span class="item-count-badge">{{ $itemCount }}</span>
                                                @endif
                                                @if($hasItems)
                                                    @php
                                                        $hasRescheduled = collect($day['items'])->contains(function($item) {
                                                            return !empty($item['rescheduled_date']);
                                                        });
                                                    @endphp
                                                    @if($hasRescheduled)
                                                        <div class="rescheduled-indicator" title="Has rescheduled items">↻</div>
                                                    @endif
                                                @endif
                                            </td>
                                        @endif
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <!-- Year View -->
                <div class="calendar-year-section">
                    <div class="calendar-year-title">
                        <i class="demo-psi-calendar-4 me-2"></i>{{ $calendarData['year'] }}
                    </div>

                    <div class="calendar-month-grid">
                        @foreach($calendarData['months'] as $month)
                            <div class="calendar-month-card">
                                <div class="calendar-month-header">
                                    {{ $month['monthFull'] }} {{ $calendarData['year'] }}
                                </div>
                                <table class="calendar-table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>S</th>
                                            <th>M</th>
                                            <th>T</th>
                                            <th>W</th>
                                            <th>T</th>
                                            <th>F</th>
                                            <th>S</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($month['weeks'] as $week)
                                            <tr>
                                                @foreach($week as $day)
                                                    @if($day === null)
                                                        <td class="calendar-day-empty"></td>
                                                    @else
                                                        @php
                                                            $dayClasses = ['calendar-day'];
                                                            $itemCount = count($day['items']);
                                                            $hasItems = $day['hasItems'];

                                                            if ($day['isToday']) {
                                                                $dayClasses[] = 'calendar-day-today';
                                                            }

                                                            if ($hasItems) {
                                                                $dayClasses[] = 'calendar-day-has-items';

                                                                // Add status-based classes
                                                                $statuses = collect($day['items'])->pluck('status')->unique();
                                                                if ($statuses->contains('visited')) {
                                                                    $dayClasses[] = 'calendar-day-visited';
                                                                }
                                                                if ($statuses->contains('rescheduled')) {
                                                                    $dayClasses[] = 'calendar-day-rescheduled';
                                                                }
                                                                if ($statuses->contains('pending')) {
                                                                    $dayClasses[] = 'calendar-day-pending';
                                                                }
                                                            }
                                                        @endphp
                                                        <td class="{{ implode(' ', $dayClasses) }}"
                                                            @if($hasItems)
                                                                wire:click="$dispatch('show-package-item-details', { date: '{{ $day['date'] }}', items: @js($day['items']) })"
                                                                title="{{ $itemCount }} item(s) on {{ $day['date'] }}"
                                                            @endif>
                                                            <div class="day-number">
                                                                {{ $day['day'] }}
                                                            </div>
                                                            @if($hasItems && $itemCount > 1)
                                                                <span class="item-count-badge">{{ $itemCount }}</span>
                                                            @endif
                                                            @if($hasItems)
                                                                @php
                                                                    $hasRescheduled = collect($day['items'])->contains(function($item) {
                                                                        return !empty($item['rescheduled_date']);
                                                                    });
                                                                @endphp
                                                                @if($hasRescheduled)
                                                                    <div class="rescheduled-indicator" title="Has rescheduled items">↻</div>
                                                                @endif
                                                            @endif
                                                        </td>
                                                    @endif
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="mt-4 legend-container">
                <div class="legend-header">
                    <i class="demo-psi-information me-2"></i>
                    <span class="legend-title">Legend</span>
                </div>
                <div class="legend-items">
                    <div class="legend-item">
                        <span class="legend-color legend-has-items"></span>
                        <span class="legend-label">Has Items</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-color legend-visited"></span>
                        <span class="legend-label">Visited</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-color legend-rescheduled"></span>
                        <span class="legend-label">Rescheduled</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-color legend-pending"></span>
                        <span class="legend-label">Pending</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-color legend-today"></span>
                        <span class="legend-label">Today</span>
                    </div>
                </div>
            </div>
        @else
            <div class="text-center py-5">
                <i class="demo-psi-calendar-4" style="font-size: 3rem; color: #cbd5e1;"></i>
                <p class="text-muted mt-3">
                    @if($viewMode === 'day')
                        No package items found for this day
                    @elseif($viewMode === 'week')
                        No package items found for this week
                    @elseif($viewMode === 'month')
                        No package items found for this month
                    @else
                        No package items found for {{ $selectedYear }}
                    @endif
                </p>
            </div>
        @endif
    </div>

    @push('scripts')
        <script>
            document.addEventListener('livewire:init', () => {
                Livewire.on('show-package-item-details', (data) => {
                    // You can implement a modal or popover here to show item details
                    console.log('Package items for date:', data.date, data.items);
                    // Example: Show a toast or modal with item details
                    if (data.items && data.items.length > 0) {
                        let message = `Package Items for ${data.date}:\n\n`;
                        data.items.forEach((item, index) => {
                            message += `${index + 1}. ${item.package_name} - ${item.account_name}\n`;
                            message += `   Status: ${item.status}\n`;
                            if (item.is_rescheduled) {
                                message += `   Rescheduled from: ${item.date} to ${item.rescheduled_date}\n`;
                            }
                            if (item.notes) {
                                message += `   Notes: ${item.notes}\n`;
                            }
                            message += '\n';
                        });
                        alert(message);
                    }
                });
            });
        </script>
    @endpush
</div>

