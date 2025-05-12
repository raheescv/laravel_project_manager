<div>
    <!-- Filters Bar -->
    <div class="card mb-2 border-0">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <label for="from_date" class="form-label text-muted small">From Date</label>
                    {{ html()->date('from_date')->value('')->class('form-control form-control-lg')->id('from_date')->attribute('wire:model.live', 'fromDate') }}
                </div>
                <div class="col-md-3">
                    <label for="to_date" class="form-label text-muted small">To Date</label>
                    {{ html()->date('to_date')->value('')->class('form-control form-control-lg')->id('to_date')->attribute('wire:model.live', 'toDate') }}
                </div>
                <div class="col-md-4" wire:ignore>
                    <label for="branch_id" class="form-label text-muted small">Branch</label>
                    {{ html()->select('branch_id', [session('branch_id') => session('branch_name')])->value(session('branch_id'))->class('form-select-lg select-assigned-branch_id-list')->id('branch_id')->placeholder('Select Branch') }}
                </div>
            </div>
        </div>
    </div>

    <!-- Main Stats Grid -->
    <div class="row g-4 mb-4">
        <!-- Sales Overview Card -->
        <div class="col-md-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title fw-bold mb-4">Sales Performance</h5>

                    <!-- Sales vs Returns Stats -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <div class="p-4 rounded-3 bg-light">
                                <div class="d-flex justify-content-between mb-2">
                                    <h6 class="text-muted mb-0">Total Sales</h6>
                                    <span class="badge bg-success rounded-pill">Active</span>
                                </div>
                                <h3 class="mb-0 fw-bold">{{ currency($totalSales) }}</h3>
                                <div class="text-muted small mt-2">{{ $noOfSales }} Transactions</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-4 rounded-3 bg-light">
                                <div class="d-flex justify-content-between mb-2">
                                    <h6 class="text-muted mb-0">Returns</h6>
                                    <span class="badge bg-warning rounded-pill">Monitor</span>
                                </div>
                                <h3 class="mb-0 fw-bold">{{ currency($totalSalesReturn) }}</h3>
                                <div class="text-muted small mt-2">{{ $noOfSalesReturns }} Returns</div>
                            </div>
                        </div>
                    </div>

                    <!-- Progress Section -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0">Sales Success Rate</h6>
                            <div class="text-muted small">{{ number_format($noOfSales > 0 ? (($noOfSales - $noOfSalesReturns) / $noOfSales) * 100 : 0, 1) }}%</div>
                        </div>
                        <div class="progress rounded-pill" style="height: 8px;">
                            <div class="progress-bar bg-gradient" role="progressbar" style="width: {{ $noOfSales > 0 ? (($noOfSales - $noOfSalesReturns) / $noOfSales) * 100 : 0 }}%;"
                                aria-valuenow="{{ $noOfSales - $noOfSalesReturns }}" aria-valuemin="0" aria-valuemax="{{ $noOfSales }}">
                            </div>
                        </div>
                    </div>

                    <!-- Financial Stats -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="card bg-primary bg-opacity-10 border-0">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <i class="fa fa-money fa-2x text-primary"></i>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="text-primary mb-1">Net Sales</h6>
                                            <h4 class="mb-0">{{ currency($netSales, 0) }}</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-warning bg-opacity-10 border-0">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <i class="fa fa-tag fa-2x text-warning"></i>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="text-warning mb-1">Discounts</h6>
                                            <h4 class="mb-0">{{ currency($saleDiscount, 0) }}</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="card bg-success bg-opacity-10 border-0">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <i class="fa fa-cubes fa-2x text-success"></i>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="text-success mb-1">Total Item</h6>
                                            <h4 class="mb-0">{{ currency($itemTotal, 0) }}</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-success bg-opacity-10 border-0">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <i class="fa fa-shopping-cart fa-2x text-success"></i>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="text-success mb-1">Products</h6>
                                            <h4 class="mb-0">{{ currency($productSale, 0) }}</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-info bg-opacity-10 border-0">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <i class="fa fa-star fa-2x text-info"></i>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="text-info mb-1">Services</h6>
                                            <h4 class="mb-0">{{ currency($serviceSale, 0) }}</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Stats -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title fw-bold mb-4">Payment Overview</h5>

                    <!-- Total Payment Stats -->
                    <div class="text-center mb-4">
                        <div class="display-6 fw-bold mb-1">{{ currency($totalPayment) }}</div>
                        <div class="text-muted">Total Payments Received</div>
                    </div>

                    <!-- Payment Progress -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0">Collection Rate</h6>
                            <div class="text-muted small">{{ number_format($totalPayment > 0 ? ($totalPayment / $totalSales) * 100 : 0, 1) }}%</div>
                        </div>
                        <div class="progress rounded-pill" style="height: 8px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $totalPayment > 0 ? ($totalPayment / $totalSales) * 100 : 0 }}%"
                                aria-valuenow="{{ $totalPayment }}" aria-valuemin="0" aria-valuemax="{{ $totalSales }}">
                            </div>
                        </div>
                    </div>

                    <!-- Payment Methods Chart -->
                    <div wire:ignore>
                        <div id="chartContainer" style="height: 250px; width: 100%;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sales Details Tables -->
    <div class="row g-4">
        <!-- Employee Sales Table -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0 fw-bold">Employee Performance</h6>
                        <div class="d-flex gap-2">
                            <select wire:model.live="employeePerPage" class="form-select form-select-sm" style="width: 80px">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                            </select>
                            <input type="search" wire:model.live.debounce.300ms="employeeSearch" class="form-control form-control-sm" placeholder="Search employee...">
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-3"><x-sortable-header :direction="$employeeSortDirection" :sortField="$employeeSortField" field="employee" label="#" /></th>
                                    <th><x-sortable-header :direction="$employeeSortDirection" :sortField="$employeeSortField" field="employee.employee" label="Employee" /></th>
                                    <th class="text-end"><x-sortable-header :direction="$employeeSortDirection" :sortField="$employeeSortField" field="employee.quantity" label="Quantity" /></th>
                                    <th class="text-end pe-3"><x-sortable-header :direction="$employeeSortDirection" :sortField="$employeeSortField" field="employee.total" label="Total" /></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($employees as $item)
                                    <tr wire:key="emp-{{ $item->id }}">
                                        <td class="ps-3">{{ $loop->iteration }}</td>
                                        <td>{{ $item->employee }}</td>
                                        <td class="text-end">{{ number_format($item->quantity) }}</td>
                                        <td class="text-end pe-3">{{ currency($item->total) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-light">
                                <tr>
                                    <td colspan="2" class="ps-3 fw-bold">Total</td>
                                    <td class="text-end fw-bold">{{ number_format($employeeQuantity) }}</td>
                                    <td class="text-end pe-3 fw-bold">{{ currency($employeeTotal) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    @if ($employees->hasPages())
                        <div class="p-3 border-top">
                            {{ $employees->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Product Sales Table -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0 fw-bold">Product Performance</h6>
                        <div class="d-flex gap-2">
                            <select wire:model.live="productPerPage" class="form-select form-select-sm" style="width: 80px">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                            </select>
                            <input type="search" wire:model.live.debounce.300ms="productSearch" class="form-control form-control-sm" placeholder="Search product...">
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-3"><x-sortable-header :direction="$productSortDirection" :sortField="$productSortField" field="product.name" label="#" /></th>
                                    <th><x-sortable-header :direction="$productSortDirection" :sortField="$productSortField" field="product.name" label="Name" /></th>
                                    <th class="text-end"><x-sortable-header :direction="$productSortDirection" :sortField="$productSortField" field="product.quantity" label="Quantity" /></th>
                                    <th class="text-end pe-3"><x-sortable-header :direction="$productSortDirection" :sortField="$productSortField" field="product.total" label="Total" /></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($products as $item)
                                    <tr wire:key="{{ $item->id }}">
                                        <td class="ps-3">{{ $loop->iteration }}</td>
                                        <td>{{ $item->product }}</td>
                                        <td class="text-end">{{ number_format($item->quantity) }}</td>
                                        <td class="text-end pe-3">{{ currency($item->total) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-light">
                                <tr>
                                    <td colspan="2" class="ps-3 fw-bold">Total</td>
                                    <td class="text-end fw-bold">{{ number_format($totalProductQuantity) }}</td>
                                    <td class="text-end pe-3 fw-bold">{{ currency($itemTotal) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    @if ($products->hasPages())
                        <div class="p-3 border-top">
                            {{ $products->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            $(document).ready(function() {
                $('#branch_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('branchId', value);
                });
            });
            window.addEventListener('updatePieChart', event => {
                var options = {
                    title: {
                        text: "Payment Methods"
                    },
                    data: [{
                        type: "doughnut",
                        startAngle: 45,
                        showInLegend: "true",
                        legendText: "{label}",
                        indexLabel: "{label}: {y}",
                        yValueFormatString: "#,##0.##",
                        dataPoints: event.detail[0],
                    }]
                };
                $("#chartContainer").CanvasJSChart(options);
            });
        </script>
        <script type="text/javascript" src="https://cdn.canvasjs.com/jquery.canvasjs.min.js"></script>
    @endpush
</div>
