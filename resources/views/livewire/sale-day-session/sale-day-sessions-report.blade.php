<div>
    <div class="card shadow-sm border-0">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <i class="fa fa-chart-bar me-2"></i>
                    <h5 class="mb-0">Day Sessions Report</h5>
                </div>
                <div class="text-end">
                    <small class="text-light">
                        <i class="fa fa-clock-o me-1"></i>Last Updated: {{ now()->format('Y-m-d H:i') }}
                    </small>
                </div>
            </div>
        </div>
        <div class="card-body" style="background-color: #fafafa;">
            <!-- Filters -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header">
                    <h6 class="mb-0" style="color: #495057;">
                        <i class="fa fa-filter me-2" style="color: #6c757d;"></i>Filter Options
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="mb-2">
                                <label for="branchId" class="form-label">
                                    <i class="fa fa-building me-2" style="color: #4a6fa5;"></i>Branch
                                </label>
                                <select wire:model.live="branchId" id="branchId" class="form-select">
                                    <option value="">All Branches</option>
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-2">
                                <label for="dateFrom" class="form-label">
                                    <i class="fa fa-calendar-o me-2" style="color: #5a9fd4;"></i>Date From
                                </label>
                                <input type="date" wire:model.live="dateFrom" id="dateFrom" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-2">
                                <label for="dateTo" class="form-label">
                                    <i class="fa fa-calendar-o me-2" style="color: #5a9fd4;"></i>Date To
                                </label>
                                <input type="date" wire:model.live="dateTo" id="dateTo" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-2">
                                <label for="status" class="form-label">
                                    <i class="fa fa-info-circle me-2" style="color: #b8860b;"></i>Status
                                </label>
                                <select wire:model.live="status" id="status" class="form-select">
                                    <option value="">All Status</option>
                                    <option value="open">Open</option>
                                    <option value="closed">Closed</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Stats -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                        <div class="card-header border-0" style="background: transparent;">
                            <h6 class="mb-0" style="color: #495057;">
                                <i class="fa fa-dashboard me-2" style="color: #6c757d;"></i>Summary Statistics
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-4">
                                <div class="col-md-3">
                                    <div class="d-flex align-items-center p-3 rounded" style="background-color: white; border-left: 4px solid #6c757d; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                        <div class="me-3">
                                            <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: #6c757d;">
                                                <i class="fa fa-list" style="color: white; font-size: 20px;"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="small text-muted mb-1 fw-medium">Total Sessions</div>
                                            <div class="h4 mb-0 fw-bold" style="color: #495057;">
                                                {{ number_format($summary['total_sessions']) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="d-flex align-items-center p-3 rounded" style="background-color: white; border-left: 4px solid #28a745; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                        <div class="me-3">
                                            <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: #28a745;">
                                                <i class="fa fa-unlock" style="color: white; font-size: 20px;"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="small text-muted mb-1 fw-medium">Open Sessions</div>
                                            <div class="h4 mb-0 fw-bold" style="color: #28a745;">
                                                {{ number_format($summary['open_sessions']) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="d-flex align-items-center p-3 rounded" style="background-color: white; border-left: 4px solid #5a9fd4; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                        <div class="me-3">
                                            <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: #5a9fd4;">
                                                <i class="fa fa-lock" style="color: white; font-size: 20px;"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="small text-muted mb-1 fw-medium">Closed Sessions</div>
                                            <div class="h4 mb-0 fw-bold" style="color: #5a9fd4;">
                                                {{ number_format($summary['closed_sessions']) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="d-flex align-items-center p-3 rounded" style="background-color: white; border-left: 4px solid #4a6fa5; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                        <div class="me-3">
                                            <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: #4a6fa5;">
                                                <i class="fa fa-shopping-cart" style="color: white; font-size: 20px;"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="small text-muted mb-1 fw-medium">Total Sales</div>
                                            <div class="h4 mb-0 fw-bold" style="color: #4a6fa5;">
                                                {{ number_format($summary['total_sales']) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row g-4 mt-2">
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center p-3 rounded" style="background-color: white; border-left: 4px solid #b8860b; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                        <div class="me-3">
                                            <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: #b8860b;">
                                                <i class="fa fa-money" style="color: white; font-size: 20px;"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="small text-muted mb-1 fw-medium">Total Sales Amount</div>
                                            <div class="h4 mb-0 fw-bold" style="color: #b8860b;">
                                                {{ number_format($summary['total_sales_amount'], 2) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center p-3 rounded"
                                        style="background-color: white; border-left: 4px solid {{ $summary['total_difference'] < 0 ? '#dc3545' : ($summary['total_difference'] > 0 ? '#28a745' : '#6c757d') }}; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                        <div class="me-3">
                                            <div class="rounded-circle d-flex align-items-center justify-content-center"
                                                style="width: 50px; height: 50px; background-color: {{ $summary['total_difference'] < 0 ? '#dc3545' : ($summary['total_difference'] > 0 ? '#28a745' : '#6c757d') }};">
                                                @if ($summary['total_difference'] > 0)
                                                    <i class="fa fa-arrow-up" style="color: white; font-size: 20px;"></i>
                                                @elseif ($summary['total_difference'] < 0)
                                                    <i class="fa fa-arrow-down" style="color: white; font-size: 20px;"></i>
                                                @else
                                                    <i class="fa fa-calculator" style="color: white; font-size: 20px;"></i>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="small text-muted mb-1 fw-medium">Total Cash Difference</div>
                                            <div class="h4 mb-0 fw-bold"
                                                style="color: {{ $summary['total_difference'] < 0 ? '#dc3545' : ($summary['total_difference'] > 0 ? '#28a745' : '#6c757d') }};">
                                                {{ number_format($summary['total_difference'], 2) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sessions Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-header" style="background-color: #f8f9fa; border-bottom: 1px solid #e9ecef;">
                    <h6 class="mb-0" style="color: #495057;">
                        <i class="fa fa-table me-2" style="color: #6c757d;"></i>Day Sessions Data
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead style="background-color: #f8f9fa;">
                                <tr>
                                    <th wire:click="sortBy('id')" style="cursor: pointer; border-bottom: 2px solid #e9ecef;">
                                        <i class="fa fa-hashtag me-1" style="color: #6c757d;"></i>ID
                                        @if ($sortField === 'id')
                                            @if ($sortDirection === 'asc')
                                                <i class="fa fa-caret-up" style="color: #4a6fa5;"></i>
                                            @else
                                                <i class="fa fa-caret-down" style="color: #4a6fa5;"></i>
                                            @endif
                                        @endif
                                    </th>
                                    <th style="border-bottom: 2px solid #e9ecef;">
                                        <i class="fa fa-building me-1" style="color: #6c757d;"></i>Branch
                                    </th>
                                    <th wire:click="sortBy('opened_at')" style="cursor: pointer; border-bottom: 2px solid #e9ecef;">
                                        <i class="fa fa-calendar-o me-1" style="color: #6c757d;"></i>Opened At
                                        @if ($sortField === 'opened_at')
                                            @if ($sortDirection === 'asc')
                                                <i class="fa fa-caret-up" style="color: #4a6fa5;"></i>
                                            @else
                                                <i class="fa fa-caret-down" style="color: #4a6fa5;"></i>
                                            @endif
                                        @endif
                                    </th>
                                    <th style="border-bottom: 2px solid #e9ecef;">
                                        <i class="fa fa-user me-1" style="color: #6c757d;"></i>Opened By
                                    </th>
                                    <th style="border-bottom: 2px solid #e9ecef;">
                                        <i class="fa fa-info-circle me-1" style="color: #6c757d;"></i>Status
                                    </th>
                                    <th style="border-bottom: 2px solid #e9ecef;" class="text-end">
                                        <i class="fa fa-shopping-cart me-1" style="color: #6c757d;"></i>Sales Count
                                    </th>
                                    <th style="border-bottom: 2px solid #e9ecef;" class="text-end">
                                        <i class="fa fa-money me-1" style="color: #6c757d;"></i>Sales Amount
                                    </th>
                                    <th wire:click="sortBy('difference_amount')" style="cursor: pointer; border-bottom: 2px solid #e9ecef;" class="text-end">
                                        <i class="fa fa-calculator me-1" style="color: #6c757d;"></i>Difference
                                        @if ($sortField === 'difference_amount')
                                            @if ($sortDirection === 'asc')
                                                <i class="fa fa-caret-up" style="color: #4a6fa5;"></i>
                                            @else
                                                <i class="fa fa-caret-down" style="color: #4a6fa5;"></i>
                                            @endif
                                        @endif
                                    </th>
                                    <th style="border-bottom: 2px solid #e9ecef;">
                                        <i class="fa fa-cogs me-1" style="color: #6c757d;"></i>Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($sessions as $session)
                                    <tr>
                                        <td class="fw-bold" style="color: #495057;">{{ $session->id }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="rounded-circle p-1 me-2" style="width: 8px; height: 8px; background-color: #4a6fa5;"></div>
                                                <strong>{{ $session->branch->name }}</strong>
                                            </div>
                                        </td>
                                        <td> {{ systemDateTime($session->opened_at) }} </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="fa fa-user-circle text-muted me-2"></i>
                                                {{ $session->opener->name ?? 'Unknown' }}
                                            </div>
                                        </td>
                                        <td>
                                            @if ($session->status == 'open')
                                                <span class="badge d-inline-flex align-items-center" style="background-color: #28a745; color: white;">
                                                    <i class="fa fa-circle me-1" style="font-size: 6px;"></i>Open
                                                </span>
                                            @else
                                                <span class="badge d-inline-flex align-items-center" style="background-color: #6c757d; color: white;">
                                                    <i class="fa fa-check-circle me-1" style="font-size: 10px;"></i>Closed
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <span class="badge" style="background-color: #4a6fa5; color: white;">
                                                {{ $session->sales_count }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fw-bold" style="color: #b8860b;">
                                                {{ number_format($session->sales_sum_paid ?? 0, 2) }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            @if ($session->status == 'closed')
                                                <span class="fw-bold @if ($session->difference_amount < 0) text-danger @elseif($session->difference_amount > 0) text-success @else text-muted @endif">
                                                    @if ($session->difference_amount != 0)
                                                        <i class="fa fa-{{ $session->difference_amount > 0 ? 'arrow-up' : 'arrow-down' }} me-1"></i>
                                                    @endif
                                                    {{ number_format($session->difference_amount, 2) }}
                                                </span>
                                            @else
                                                <span class="text-muted">--</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('sale::day-session', $session->id) }}" class="btn btn-sm shadow-sm"
                                                style="background-color: #5a9fd4; border-color: #5a9fd4; color: white;" title="View session details">
                                                <i class="fa fa-eye"></i> Details
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach

                                @if ($sessions->count() === 0)
                                    <tr>
                                        <td colspan="9" class="text-center py-4" style="color: #6c757d;">
                                            <i class="fa fa-info-circle me-2"></i>No day sessions found matching the criteria.
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div>
                {{ $sessions->links() }}
            </div>
        </div>
    </div>
</div>
