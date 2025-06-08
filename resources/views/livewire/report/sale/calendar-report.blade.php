<div>
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <div class="d-flex align-items-center mb-4">
                <div>
                    <h5 class="mb-0">Sales Calendar View</h5>
                    <small class="text-muted">Track your daily sales in a calendar format</small>
                </div>
                <div class="ms-auto">
                    <!-- View Mode Buttons -->
                    <div class="btn-group" role="group">
                        <button type="button" wire:click="toggleViewMode" class="btn {{ $view_mode == 'calendar' ? 'btn-primary' : 'btn-outline-primary' }}">
                            <i class="fa fa-calendar me-1"></i> Calendar
                        </button>
                        <button type="button" wire:click="toggleViewMode" class="btn {{ $view_mode == 'heatmap' ? 'btn-primary' : 'btn-outline-primary' }}">
                            <i class="fa fa-th me-1"></i> Heatmap
                        </button>
                    </div>
                    <!-- Compare Toggle Button -->
                    <button type="button" wire:click="toggleComparison" class="btn {{ $compare_previous ? 'btn-info' : 'btn-outline-info' }} ms-2">
                        <i class="fa fa-chart-line me-1"></i> {{ $compare_previous ? 'Hide Comparison' : 'Compare Months' }}
                    </button>
                    <!-- Go to Today Button -->
                    <button type="button" wire:click="goToToday" class="btn btn-outline-secondary ms-2">
                        <i class="fa fa-calendar-day me-1"></i> Today
                    </button>
                </div>
            </div>

            <div class="row g-3 align-items-center">
                <div class="col-md-3" wire:ignore>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="fa fa-building text-success"></i>
                        </span>
                        {{ html()->select('branch_id', [session('branch_id') => session('branch_name')])->value(session('branch_id'))->class('select-assigned-branch_id-list border-start-0 ps-0')->id('branch_id')->attribute('style', 'width:80%')->placeholder('Select Branch') }}
                    </div>
                    <label class="form-label small text-muted mt-1">Branch</label>
                </div>
                <div class="col-md-3" wire:ignore>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="fa fa-credit-card text-success"></i>
                        </span>
                        {{ html()->select('payment_method_id')->class('select-payment_method_id-list')->id('payment_method_id')->attribute('style', 'width:80%')->placeholder('All Payment Methods') }}
                    </div>
                    <label class="form-label small text-muted mt-1">Payment Method</label>
                </div>
                <!-- Add Sale Type Filter -->
                <div class="col-md-3" wire:ignore>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="fa fa-tag text-success"></i>
                        </span>
                        {{ html()->select('sale_type', priceTypes())->class('form-control border-start-0 ps-0')->id('sale_type')->attribute('style', 'width:80%')->placeholder('All Sale Types') }}
                    </div>
                    <label class="form-label small text-muted mt-1">Sale Type</label>
                </div>
                <div class="col-md-3">
                    <div class="text-end">
                        <div class="btn-group">
                            <button wire:click="previousMonth" class="btn btn-outline-primary">
                                <i class="fa fa-chevron-left"></i>
                            </button>
                            <button class="btn btn-outline-primary" disabled>{{ $monthName }}</button>
                            <button wire:click="nextMonth" class="btn btn-outline-primary">
                                <i class="fa fa-chevron-right"></i>
                            </button>
                        </div>
                        <button class="btn btn-sm btn-light ms-2" onclick="window.print()">
                            <i class="fa fa-print me-1"></i> Print
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body p-4">
            <!-- Month summary -->
            <div class="mb-4 p-3 bg-light rounded-3">
                <div class="row">
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="stats-icon invoice-icon">
                                <i class="fa fa-file-text-o"></i>
                            </div>
                            <div class="stats-content">
                                <h3 class="stats-number">{{ number_format($monthlyCount) }}</h3>
                                <p class="stats-label">Invoices This Month</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="stats-icon total-icon">
                                <i class="fa fa-money"></i>
                            </div>
                            <div class="stats-content">
                                <h3 class="stats-number">{{ currency($monthlyTotal) }}</h3>
                                <p class="stats-label">Total Sales This Month</p>
                            </div>
                        </div>
                    </div>
                    <!-- Add Best Day and Average Daily Sales Stats -->
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="stats-icon best-day-icon">
                                <i class="fa fa-trophy"></i>
                            </div>
                            <div class="stats-content">
                                <h3 class="stats-number">{{ isset($bestDayInfo) ? currency($bestDayInfo['total']) : '-' }}</h3>
                                <p class="stats-label">Best Day: {{ isset($bestDayInfo) ? $bestDayInfo['date'] : '-' }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="stats-icon avg-icon">
                                <i class="fa fa-calculator"></i>
                            </div>
                            <div class="stats-content">
                                <h3 class="stats-number">{{ currency($avgDailySales ?? 0) }}</h3>
                                <p class="stats-label">Avg. Daily Sales</p>
                            </div>
                        </div>
                    </div>
                </div>

                @if ($compare_previous && isset($prevMonthCompare))
                    <div class="comparison-section mt-3">
                        <div class="comparison-header mb-2">
                            <span class="comparison-title">
                                <i class="fa fa-chart-line"></i>
                                Previous Month Comparison
                            </span>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <div class="comparison-card">
                                    <div class="comparison-icon prev-total-icon">
                                        <i class="fa fa-history"></i>
                                    </div>
                                    <div class="comparison-content">
                                        <h3 class="comparison-number">{{ currency($prevMonthCompare['total']) }}</h3>
                                        <p class="comparison-label">Previous Month Total</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="comparison-card">
                                    <div class="comparison-icon prev-count-icon">
                                        <i class="fa fa-file-o"></i>
                                    </div>
                                    <div class="comparison-content">
                                        <h3 class="comparison-number">{{ number_format($prevMonthCompare['count']) }}</h3>
                                        <p class="comparison-label">Previous Month Invoices</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="comparison-card">
                                    <div class="comparison-icon {{ $prevMonthCompare['percent_change'] > 0 ? 'change-positive-icon' : 'change-negative-icon' }}">
                                        <i class="fa {{ $prevMonthCompare['percent_change'] > 0 ? 'fa-arrow-up' : 'fa-arrow-down' }}"></i>
                                    </div>
                                    <div class="comparison-content">
                                        <h3 class="comparison-number {{ $prevMonthCompare['percent_change'] > 0 ? 'text-success' : 'text-danger' }}">
                                            {{ $prevMonthCompare['percent_change'] > 0 ? '+' : '' }}{{ $prevMonthCompare['percent_change'] }}%
                                        </h3>
                                        <p class="comparison-label">Change in Revenue</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Calendar View -->
            @if ($view_mode == 'calendar')
                <div class="calendar-container">
                    <table class="table table-bordered calendar-table">
                        <thead>
                            <tr>
                                <th class="text-center">Sun</th>
                                <th class="text-center">Mon</th>
                                <th class="text-center">Tue</th>
                                <th class="text-center">Wed</th>
                                <th class="text-center">Thu</th>
                                <th class="text-center">Fri</th>
                                <th class="text-center">Sat</th>
                                <th class="text-center weekly-summary-header">
                                    <i class="fa fa-calendar-week"></i> Week Total
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($weeks as $week)
                                <tr>
                                    @foreach ($week as $day)
                                        <td class="{{ !$day['day'] ? 'bg-light' : ($day['total'] > 0 ? 'has-sales' : '') }} text-center"
                                            @if ($day['day'] && $day['total'] > 0) wire:click="showDayDetails('{{ $day['date'] }}')"
                                                style="cursor: pointer;" @endif>
                                            @if ($day['day'])
                                                <div class="day-container">
                                                    <div class="day-number {{ $day['date'] == date('Y-m-d') ? 'bg-primary text-white' : '' }}">
                                                        {{ $day['day'] }}
                                                    </div>
                                                    <div class="day-content">
                                                        @if ($day['total'] > 0)
                                                            <span class="badge bg-success sales-count-badge">{{ $day['count'] }}</span>
                                                            <div class="sales-amount">
                                                                {{ currency($day['total']) }}
                                                            </div>

                                                            @if ($compare_previous && isset($day['change_percent']))
                                                                <span class="mini-badge {{ $day['change_percent'] > 0 ? 'positive' : 'negative' }}">
                                                                    {{ $day['change_percent'] > 0 ? '+' : '' }}{{ $day['change_percent'] }}%
                                                                </span>
                                                            @endif
                                                        @endif
                                                    </div>
                                                </div>
                                            @endif
                                        </td>
                                    @endforeach

                                    <!-- Weekly Summary Column -->
                                    <td class="text-center weekly-summary-cell">
                                        @php
                                            $weekTotal = 0;
                                            $weekCount = 0;
                                            foreach ($week as $day) {
                                                if (!is_null($day['day'])) {
                                                    $weekTotal += $day['total'];
                                                    $weekCount += $day['count'];
                                                }
                                            }
                                        @endphp
                                        <div class="weekly-summary-content">
                                            <div class="weekly-summary-amount">{{ currency($weekTotal) }}</div>
                                            <div class="weekly-summary-count">{{ $weekCount }} sales</div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @elseif($view_mode == 'heatmap')
                <!-- Heatmap View -->
                <div class="heatmap-container">
                    <div class="row mb-2">
                        <div class="col-12 text-end">
                            <div class="heatmap-legend">
                                <span class="legend-item">Low</span>
                                <span class="legend-color intensity-0"></span>
                                <span class="legend-color intensity-1"></span>
                                <span class="legend-color intensity-2"></span>
                                <span class="legend-color intensity-3"></span>
                                <span class="legend-color intensity-4"></span>
                                <span class="legend-item">High</span>
                            </div>
                        </div>
                    </div>

                    <table class="table table-bordered heatmap-table">
                        <thead>
                            <tr>
                                <th class="text-center">Sun</th>
                                <th class="text-center">Mon</th>
                                <th class="text-center">Tue</th>
                                <th class="text-center">Wed</th>
                                <th class="text-center">Thu</th>
                                <th class="text-center">Fri</th>
                                <th class="text-center">Sat</th>
                                <th class="text-center weekly-summary-header">
                                    <i class="fa fa-calendar-week"></i> Week Total
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($weeks as $week)
                                <tr>
                                    @foreach ($week as $day)
                                        <td
                                            @if ($day['day'] && isset($day['intensity'])) class="heatmap-day"
                                                style="background-color: {{ $day['intensity'] > 0 ? 'rgba(25, 135, 84, ' . $day['intensity'] . ')' : '' }}"
                                                wire:click="showDayDetails('{{ $day['date'] }}')"
                                            @else
                                                class="bg-light" @endif>
                                            @if ($day['day'])
                                                <div class="heatmap-day-content">
                                                    <div style="color:black" class="day-number {{ $day['date'] == date('Y-m-d') ? 'bg-primary ' : '' }}">
                                                        {{ $day['day'] }}
                                                    </div>

                                                    @if ($day['total'] > 0)
                                                        <div style="color:black" class="heatmap-amount">
                                                            {{ currency($day['total']) }}
                                                        </div>
                                                    @endif
                                                </div>
                                            @endif
                                        </td>
                                    @endforeach

                                    <!-- Weekly Summary Column for Heatmap -->
                                    <td class="weekly-summary-heatmap-cell">
                                        @php
                                            $weekTotal = 0;
                                            $weekCount = 0;
                                            foreach ($week as $day) {
                                                if (!is_null($day['day'])) {
                                                    $weekTotal += $day['total'];
                                                    $weekCount += $day['count'];
                                                }
                                            }
                                            // Calculate intensity relative to the highest value in the calendar
                                            $weekIntensity = $maxTotal > 0 ? min($weekTotal / $maxTotal, 1) : 0;
                                        @endphp
                                        <div class="weekly-summary-heatmap-content" style="background-color: {{ $weekIntensity > 0 ? 'rgba(25, 135, 84, ' . $weekIntensity . ')' : 'transparent' }}">
                                            <div class="weekly-summary-heatmap-amount">
                                                {{ currency($weekTotal) }}
                                            </div>
                                            <div class="weekly-summary-heatmap-count">
                                                {{ $weekCount }} sales
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <!-- Day Details Modal -->
    @if ($selected_day)
        <div class="modal show d-block" tabindex="-1" role="dialog" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Sales for {{ $day_details['date'] }}</h5>
                        <button type="button" class="btn-close" wire:click="closeDayDetails"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row text-center mb-4">
                            <div class="col-md-6">
                                <h4>{{ currency($day_details['total']) }}</h4>
                                <p class="text-muted mb-0">Total Sales</p>
                            </div>
                            <div class="col-md-6">
                                <h4>{{ $day_details['count'] }}</h4>
                                <p class="text-muted mb-0">Transactions</p>
                            </div>
                        </div>

                        @if (count($day_details['sales']) > 0)
                            <div class="table-responsive">
                                <table class="table table-sm table-striped">
                                    <thead>
                                        <tr>
                                            <th>Invoice #</th>
                                            <th>Customer</th>
                                            <th class="text-end">Amount</th>
                                            <th>Time</th>
                                            <th class="text-end">Items</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($day_details['sales'] as $sale)
                                            <tr>
                                                <td>{{ $sale->invoice_no }}</td>
                                                <td>{{ $sale->account->name }}</td>
                                                <td class="text-end">{{ currency($sale->grand_total) }}</td>
                                                <td>{{ \Carbon\Carbon::parse($sale->created_at)->format('h:i A') }}</td>
                                                <td class="text-end">{{ $sale->items->count() }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-4">
                                <p class="mb-0">No sales recorded for this day.</p>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-gradient" wire:click="closeDayDetails">
                            <i class="fa fa-times me-1"></i> Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @push('styles')
        <style>
            /* Calendar Container Styles */
            .calendar-container {
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.06);
                border-radius: 0.75rem;
                overflow: hidden;
                background: #f8f9fa;
                border: 1px solid rgba(0, 0, 0, 0.05);
            }

            .calendar-table {
                table-layout: fixed;
                margin-bottom: 0;
                border-collapse: collapse;
                background: #ffffff;
            }

            .calendar-table thead th {
                background: #4682B4;
                color: white;
                font-weight: 600;
                font-size: 0.8rem;
                padding: 8px 0;
                border: 1px solid #e9ecef;
            }

            .calendar-table td {
                padding: 0;
                border: 1px solid #e9ecef;
                height: 90px;
                transition: all 0.2s ease;
            }

            /* Day Styles */
            .day-container {
                height: 100%;
                position: relative;
                padding: 4px;
                background: white;
                transition: all 0.2s ease;
            }

            .day-number {
                position: absolute;
                top: 3px;
                right: 3px;
                width: 24px;
                height: 24px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: bold;
                font-size: 0.8rem;
                background: #f8f9fa;
                transition: all 0.2s ease;
            }

            .day-number.bg-primary {
                background: #4682B4 !important;
                font-weight: 700;
            }

            .day-content {
                padding-top: 22px;
                text-align: center;
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 4px;
            }

            .has-sales {
                background-color: rgba(255, 255, 255, 1);
                position: relative;
            }

            .has-sales:hover {
                background-color: #f8f9fa;
            }

            .has-sales .day-container::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                height: 2px;
                background: #4682B4;
            }

            .sales-count-badge {
                font-size: 0.7rem;
                padding: 2px 6px;
                background: #4682B4;
                border-radius: 10px;
            }

            .sales-amount {
                font-weight: bold;
                color: #4682B4;
                font-size: 0.85rem;
                margin: 2px 0;
            }

            .mini-badge {
                font-size: 0.65rem;
                padding: 1px 4px;
                border-radius: 3px;
                font-weight: 600;
                color: white;
            }

            .mini-badge.positive {
                background: #28a745;
            }

            .mini-badge.negative {
                background: #dc3545;
            }

            @keyframes shimmer {
                0% {
                    transform: translateX(-100%) rotate(45deg);
                }

                100% {
                    transform: translateX(100%) rotate(45deg);
                }
            }

            /* Heatmap Styles */
            .heatmap-container {
                padding: 8px;
                background: #f8f9fa;
                border-radius: 0.75rem;
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.06);
                border: 1px solid rgba(0, 0, 0, 0.05);
            }

            .heatmap-table {
                table-layout: fixed;
                border-collapse: collapse;
                background: #ffffff;
            }

            .heatmap-table thead th {
                background: #4682B4;
                color: white;
                font-weight: 600;
                font-size: 0.8rem;
                padding: 8px 0;
                border: 1px solid #e9ecef;
            }

            .heatmap-day {
                height: 90px;
                position: relative;
                cursor: pointer;
                transition: all 0.2s ease;
                border: 1px solid #e9ecef;
            }

            .heatmap-day:hover {
                opacity: 0.9;
            }

            .heatmap-day-content {
                height: 100%;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                color: #fff;
                padding: 4px;
            }

            .heatmap-amount {
                font-weight: bold;
                margin-top: 8px;
                font-size: 0.85rem;
                background: rgba(255, 255, 255, 0.3);
                padding: 3px 6px;
                border-radius: 3px;
                text-shadow: 0 1px 1px rgba(0, 0, 0, 0.2);
            }

            .heatmap-legend {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                padding: 5px 10px;
                background: #ffffff;
                border-radius: 15px;
                font-size: 11px;
                box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
                margin-bottom: 8px;
                border: 1px solid #e9ecef;
            }

            .legend-item {
                color: #4682B4;
                font-weight: 600;
                font-size: 0.75rem;
            }

            .legend-color {
                width: 20px;
                height: 12px;
                border-radius: 2px;
            }

            /* Enhanced heatmap colors */
            .intensity-0 {
                background: rgba(25, 135, 84, 0.1);
            }

            .intensity-1 {
                background: rgba(25, 135, 84, 0.3);
            }

            .intensity-2 {
                background: rgba(25, 135, 84, 0.5);
            }

            .intensity-3 {
                background: rgba(25, 135, 84, 0.7);
            }

            .intensity-4 {
                background: rgba(25, 135, 84, 0.9);
            }

            /* Summary Stats Cards */
            .mb-4.p-3.bg-light.rounded-3 {
                background: #f8f9fa !important;
                border: 1px solid #e9ecef;
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
                border-radius: 8px !important;
                padding: 1rem !important;
                margin-bottom: 1rem !important;
            }

            .stats-card {
                display: flex;
                align-items: center;
                padding: 10px;
                border-radius: 8px;
                background: white;
                border: 1px solid #e9ecef;
                box-shadow: 0 2px 5px rgba(0, 0, 0, 0.03);
                transition: all 0.2s ease;
            }

            .stats-card:hover {
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
                transform: translateY(-2px);
            }

            .stats-content {
                flex: 1;
                padding-left: 10px;
                text-align: left;
            }

            .stats-icon {
                width: 36px;
                height: 36px;
                border-radius: 8px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1rem;
                flex-shrink: 0;
                color: white;
            }

            .stats-icon.invoice-icon {
                background: #4682B4;
            }

            .stats-icon.total-icon {
                background: #20639B;
            }

            .stats-icon.best-day-icon {
                background: #3A5A40;
            }

            .stats-icon.avg-icon {
                background: #457B9D;
            }

            .stats-number {
                font-size: 1.1rem;
                font-weight: 700;
                color: #333;
                margin-bottom: 0.15rem;
            }

            .stats-label {
                font-size: 0.75rem;
                color: #6c757d;
                margin-bottom: 0;
                font-weight: 600;
            }

            /* Modal styling futuristic updates */
            .modal-content {
                border: none;
                border-radius: 8px;
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            }

            /* Comparison Section Styles */
            .comparison-section {
                border-top: 1px solid #e9ecef;
                padding-top: 12px;
            }

            .comparison-header {
                margin-bottom: 10px;
            }

            .comparison-title {
                font-size: 0.9rem;
                font-weight: 600;
                color: #4682B4;
            }

            .comparison-title i {
                margin-right: 5px;
            }

            .comparison-card {
                display: flex;
                align-items: center;
                padding: 10px;
                border-radius: 8px;
                background: #ffffff;
                border: 1px solid #e9ecef;
                box-shadow: 0 2px 5px rgba(0, 0, 0, 0.03);
                transition: all 0.2s ease;
                height: 100%;
            }

            .comparison-card:hover {
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
                transform: translateY(-2px);
            }

            .comparison-icon {
                width: 32px;
                height: 32px;
                border-radius: 6px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 0.9rem;
                flex-shrink: 0;
                color: white;
                margin-right: 10px;
            }

            .comparison-icon.prev-total-icon {
                background: #4682B4;
            }

            .comparison-icon.prev-count-icon {
                background: #5F9EA0;
            }

            .comparison-icon.change-positive-icon {
                background: #28a745;
            }

            .comparison-icon.change-negative-icon {
                background: #dc3545;
            }

            .comparison-content {
                flex: 1;
            }

            .comparison-number {
                font-size: 1rem;
                font-weight: 600;
                margin-bottom: 0.15rem;
            }

            .comparison-label {
                font-size: 0.7rem;
                color: #6c757d;
                margin-bottom: 0;
            }

            /* Weekly Summary Styles */
            .weekly-summary-header {
                background: #4682B4;
                color: white;
                font-weight: 600;
                width: 120px;
            }

            .weekly-summary-cell {
                background-color: #EBF5FB;
                vertical-align: middle;
                width: 120px;
                border-left: 2px solid #4682B4;
                box-shadow: inset 0 0 0 1px rgba(70, 130, 180, 0.2);
            }

            .weekly-summary-content {
                padding: 8px 4px;
            }

            .weekly-summary-label {
                font-size: 0.8rem;
                font-weight: 600;
                color: #4682B4;
                margin-bottom: 4px;
                text-transform: uppercase;
            }

            .weekly-summary-amount {
                font-weight: bold;
                font-size: 1rem;
                color: #2471A3;
                margin-bottom: 4px;
            }

            .weekly-summary-count {
                font-size: 0.8rem;
                color: #566573;
            }

            /* Weekly summary for heatmap */
            .weekly-summary-heatmap-cell {
                width: 120px;
                vertical-align: middle;
                border-left: 2px solid #4682B4;
                background-color: #f5f5f5;
            }

            .weekly-summary-heatmap-content {
                height: 100%;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 8px 4px;
                color: white;
                text-shadow: 0 1px 1px rgba(0, 0, 0, 0.2);
            }

            .weekly-summary-heatmap-label {
                font-size: 0.75rem;
                font-weight: 600;
                margin-bottom: 6px;
                background: rgba(255, 255, 255, 0.3);
                padding: 2px 8px;
                border-radius: 12px;
                display: inline-block;
                text-transform: uppercase;
            }

            .weekly-summary-heatmap-amount {
                font-weight: bold;
                font-size: 0.95rem;
                margin-bottom: 4px;
            }

            .weekly-summary-heatmap-count {
                font-size: 0.8rem;
                opacity: 0.9;
            }

            /* Custom color overrides */
            .text-success {
                color: #3AAFA9 !important;
            }

            .text-danger {
                color: #e76f51 !important;
            }

            /* Fallback for calendar-week icon if not available */
            .fa-calendar-week:before {
                content: "\f133";
                /* This is the calendar icon code which is widely available */
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            $(document).ready(function() {
                $('#branch_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('branch_id', value);
                });

                $('#payment_method_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('payment_method_id', value);
                });

                $('#sale_type').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('sale_type', value);
                });
            });
        </script>
    @endpush
</div>
