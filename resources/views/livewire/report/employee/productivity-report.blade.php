<div class="container-fluid p-4 bg-light">
    <!-- Page Title -->
    <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 rounded-3 shadow-sm">
        <div>
            <h2 class="text-primary mb-0 d-flex align-items-center fw-bold">
                <i class="fa fa-chart-line me-2"></i>Employee Productivity Analysis
            </h2>
            <p class="text-muted small mb-0 mt-1">Report for {{ now()->format('F Y') }}</p>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-primary d-flex align-items-center rounded-pill px-3">
                <i class="fa fa-print me-2"></i>Print
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-header bg-white border-0">
            <h5 class="card-title mb-0 fw-semibold"><i class="fa fa-filter me-2 text-primary"></i>Filter Options</h5>
        </div>
        <div class="card-body bg-light bg-opacity-50 rounded-bottom">
            <div class="row g-4">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-secondary mb-2">From Date</label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="fa fa-calendar text-primary"></i>
                        </span>
                        <input type="date" wire:model.live="fromDate" class="form-control bg-white border-start-0 ps-0">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-secondary mb-2">To Date</label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="fa fa-calendar text-primary"></i>
                        </span>
                        <input type="date" wire:model.live="toDate" class="form-control bg-white border-start-0 ps-0">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-secondary mb-2">Rows Per Page</label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="fa fa-list text-primary"></i>
                        </span>
                        <select wire:model.live="perPage" class="form-select bg-white border-start-0 ps-0">
                            <option value="10">10 rows</option>
                            <option value="25">25 rows</option>
                            <option value="50">50 rows</option>
                            <option value="100">100 rows</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Stats -->
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small mb-1">Total Sales</div>
                            <h3 class="mb-0 text-primary">{{ number_format($totalSales ?? 0, 2) }}</h3>
                        </div>
                        <div class="bg-primary bg-opacity-10 p-3 rounded-3">
                            <i class="fa fa-dollar text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small mb-1">Total Transactions</div>
                            <h3 class="mb-0 text-success">{{ number_format($totalTransactions ?? 0) }}</h3>
                        </div>
                        <div class="bg-success bg-opacity-10 p-3 rounded-3">
                            <i class="fa fa-shopping-cart text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small mb-1">Items Sold</div>
                            <h3 class="mb-0 text-info">{{ number_format($totalItems ?? 0) }}</h3>
                        </div>
                        <div class="bg-info bg-opacity-10 p-3 rounded-3">
                            <i class="fa fa-cube text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small mb-1">Avg Transaction</div>
                            <h3 class="mb-0 text-warning">{{ number_format($avgTransaction ?? 0, 2) }}</h3>
                        </div>
                        <div class="bg-warning bg-opacity-10 p-3 rounded-3">
                            <i class="fa fa-calculator text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Table -->
    <div class="card mb-4 border-0 shadow-sm bg-white">
        <div class="card-header bg-white border-bottom-0">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 fw-semibold d-flex align-items-center">
                    <span class="bg-primary bg-opacity-10 p-2 rounded-3 me-2">
                        <i class="fa fa-calculator text-info"></i>
                    </span>
                    Performance Matrix
                </h5>
                <div class="badge bg-primary bg-opacity-10 px-3 py-2 rounded-pill text-primary">
                    <i class="fa fa-clock me-1"></i>Real-time Data
                </div>
            </div>
        </div>
        <div class="card-body pt-3">
            <div class="table-responsive">
                <table class="table table-sm  table-hover align-middle border">
                    <thead>
                        <tr>
                            <th wire:click="sortBy('name')" style="cursor: pointer" class="border-end bg-light">
                                <div class="d-flex align-items-center">
                                    <span class="bg-primary bg-opacity-10 p-1 rounded-2 me-2">
                                        <i class="fa fa-user text-primary"></i>
                                    </span>
                                    <span class="text-primary fw-semibold">Employee Profile</span>
                                    @if ($sortField === 'name')
                                        <i class="fa fa-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-2 text-primary"></i>
                                    @endif
                                </div>
                            </th>
                            <th class="text-end border-end bg-light" wire:click="sortBy('total_transactions')" style="cursor: pointer">
                                <div class="d-flex align-items-center justify-content-end">
                                    <span class="bg-success bg-opacity-10 p-1 rounded-2 me-2">
                                        <i class="fa fa-exchange text-success"></i>
                                    </span>
                                    <span class="text-success fw-semibold">Transactions</span>
                                    @if ($sortField === 'total_transactions')
                                        @if ($sortDirection === 'asc')
                                            <i class="fa fa-arrow-up"></i>
                                        @else
                                            <i class="fa fa-arrow-down"></i>
                                        @endif
                                    @endif
                                </div>
                            </th>
                            <th class="text-end border-end bg-light" wire:click="sortBy('total_sales')" style="cursor: pointer">
                                <div class="d-flex align-items-center justify-content-end">
                                    <span class="bg-info bg-opacity-10 p-1 rounded-2 me-2">
                                        <i class="fa fa-dollar text-info"></i>
                                    </span>
                                    <span class="text-info fw-semibold">Revenue</span>
                                    @if ($sortField === 'total_sales')
                                        <i class="fa fa-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-2 text-success"></i>
                                    @endif
                                </div>
                            </th>
                            <th class="text-end border-end bg-light" wire:click="sortBy('items_sold')" style="cursor: pointer">
                                <div class="d-flex align-items-center justify-content-end">
                                    <span class="bg-warning bg-opacity-10 p-1 rounded-2 me-2">
                                        <i class="fa fa-cube text-warning"></i>
                                    </span>
                                    <span class="text-warning fw-semibold">Units Sold</span>
                                    @if ($sortField === 'items_sold')
                                        <i class="fa fa-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-2 text-warning"></i>
                                    @endif
                                </div>
                            </th>
                            <th class="text-end bg-light" wire:click="sortBy('avg_transaction_value')" style="cursor: pointer">
                                <div class="d-flex align-items-center justify-content-end">
                                    <span class="bg-danger bg-opacity-10 p-1 rounded-2 me-2">
                                        <i class="fa fa-calculator text-danger"></i>
                                    </span>
                                    <span class="text-danger fw-semibold">Avg Value</span>
                                    @if ($sortField === 'avg_transaction_value')
                                        <i class="fa fa-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-2 text-danger"></i>
                                    @endif
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        @foreach ($employees as $employee)
                            <tr class="border-bottom border-secondary">
                                <td class="border-end border-secondary">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary bg-opacity-10 p-2 rounded-circle me-2">
                                            <i class="fa fa-user text-primary"></i>
                                        </div>
                                        <div>
                                            <div class="fw-semibold">{{ $employee['name'] }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-end border-end border-secondary">
                                    <div class="fw-semibold">{{ currency($employee['total_transactions'], 0) }}</div>
                                </td>
                                <td class="text-end border-end border-secondary">
                                    <div class="fw-semibold text-success">{{ currency($employee['total_sales'], 2) }}</div>
                                </td>
                                <td class="text-end border-end border-secondary">
                                    <div class="fw-semibold text-info">{{ currency($employee['items_sold'], 0) }}</div>
                                </td>
                                <td class="text-end">
                                    <div class="fw-semibold text-warning">{{ currency($employee['avg_transaction_value'], 2) }}</div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Sale Averages -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-header bg-white border-bottom-0">
            <h5 class="card-title mb-0 fw-semibold d-flex align-items-center">
                <span class="bg-primary bg-opacity-10 p-2 rounded-3 me-2">
                    <i class="fa fa-calculator text-primary"></i>
                </span>
                Sales Performance Analytics
            </h5>
        </div>
        <div class="card-body pt-0">
            <div class="alert alert-primary bg-primary bg-opacity-10 border-0 rounded-3 d-flex align-items-center mb-4">
                <i class="fa fa-info-circle fs-4 me-3 text-primary"></i>
                <div class="text-primary">These metrics represent the average performance across all employees.</div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover border">
                    <thead class="bg-light">
                        <tr>
                            <th class="text-end border-end">
                                <div class="d-flex align-items-center justify-content-end">
                                    <span class="bg-primary bg-opacity-10 p-1 rounded-2 me-2">
                                        <i class="fa fa-bar-chart text-primary"></i>
                                    </span>
                                    <span class="text-primary fw-semibold">Avg Sales</span>
                                </div>
                            </th>
                            <th class="text-end border-end">
                                <div class="d-flex align-items-center justify-content-end">
                                    <span class="bg-success bg-opacity-10 p-1 rounded-2 me-2">
                                        <i class="fa fa-exchange text-success"></i>
                                    </span>
                                    <span class="text-success fw-semibold">Avg Transactions</span>
                                </div>
                            </th>
                            <th class="text-end border-end">
                                <div class="d-flex align-items-center justify-content-end">
                                    <span class="bg-info bg-opacity-10 p-1 rounded-2 me-2">
                                        <i class="fa fa-cube text-info"></i>
                                    </span>
                                    <span class="text-info fw-semibold">Avg Items</span>
                                </div>
                            </th>
                            <th class="text-end">
                                <div class="d-flex align-items-center justify-content-end">
                                    <span class="bg-warning bg-opacity-10 p-1 rounded-2 me-2">
                                        <i class="fa fa-calculator text-warning"></i>
                                    </span>
                                    <span class="text-warning fw-semibold">Avg Transaction Value</span>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="text-end">{{ currency($departmentAverages['avg_sales'], 2) }}</td>
                            <td class="text-end">{{ currency($departmentAverages['avg_transactions'], 1) }}</td>
                            <td class="text-end">{{ currency($departmentAverages['avg_items'], 1) }}</td>
                            <td class="text-end">{{ currency($departmentAverages['avg_transaction_value'], 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Top Categories -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom-0">
            <h5 class="card-title mb-0 fw-semibold">
                <i class="fa fa-trophy me-2 text-primary"></i>Top Selling Categories by Employee
            </h5>
        </div>
        <div class="card-body pt-0">
            <div class="row g-4">
                @foreach ($employees as $employee)
                    @if (isset($topCategories[$employee['id']]))
                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100 border shadow-sm">
                                <div class="card-header py-3 bg-gradient">
                                    <h6 class="mb-0 fw-semibold d-flex align-items-center">
                                        <span class="bg-primary bg-opacity-10 p-2 rounded-circle me-2">
                                            <i class="fa fa-user"></i>
                                        </span>
                                        {{ $employee['name'] }}
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead class="table-light">
                                                <tr>
                                                    <th><i class="fa fa-tag me-1"></i>Category</th>
                                                    <th class="text-end"><i class="fa fa-cube me-1"></i>Items</th>
                                                    <th class="text-end"><i class="fa fa-dollar me-1"></i>Sales</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($topCategories[$employee['id']]->take(3) as $category)
                                                    <tr class="border-bottom">
                                                        <td class="border-end">{{ $category->category }}</td>
                                                        <td class="text-end border-end text-info">{{ currency($category->count, 0) }}</td>
                                                        <td class="text-end text-success">{{ currency($category->total, 2) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
</div>
