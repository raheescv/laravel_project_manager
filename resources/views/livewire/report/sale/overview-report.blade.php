<div>
    <div class="card mb-3">
        <div class="card-header">
            <div class="col-lg-12">
                <div class="row">
                    <div class="col-md-2">
                        <label for="from_date">From Date</label>
                        {{ html()->date('from_date')->value('')->class('form-control')->id('from_date')->attribute('wire:model.live', 'fromDate') }}
                    </div>
                    <div class="col-md-2">
                        <label for="to_date">To Date</label>
                        {{ html()->date('to_date')->value('')->class('form-control')->id('to_date')->attribute('wire:model.live', 'toDate') }}
                    </div>
                    <div class="col-md-4" wire:ignore>
                        <label for="branch_id">Branch</label>
                        {{ html()->select('branch_id', [session('branch_id') => session('branch_name')])->value(session('branch_id'))->class('select-assigned-branch_id-list')->id('branch_id')->placeholder('Branch') }}
                    </div>
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
                            <div class="card bg-blue-500 bg-gradient border-0">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <i class="fa fa-money fa-2x text-white"></i>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="text-white mb-1">Net Sales</h6>
                                            <h4 class="mb-0 text-white">{{ currency($netSales, 0) }}</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-purple-500 bg-gradient border-0">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <i class="fa fa-tag fa-2x text-white"></i>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="text-white mb-1">Discounts</h6>
                                            <h4 class="mb-0 text-white">{{ currency($saleDiscount, 0) }}</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="card bg-pink-500 bg-gradient border-0">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <i class="fa fa-cubes fa-2x text-white"></i>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="text-white mb-1">Total Item</h6>
                                            <h4 class="mb-0 text-white">{{ currency($itemTotal, 0) }}</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-indigo-500 bg-gradient border-0">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <i class="fa fa-shopping-cart fa-2x text-white"></i>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="text-white mb-1">Products</h6>
                                            <h4 class="mb-0 text-white">{{ currency($productSale, 0) }}</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-cyan-500 bg-gradient border-0">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <i class="fa fa-star fa-2x text-white"></i>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="text-white mb-1">Services</h6>
                                            <h4 class="mb-0 text-white">{{ currency($serviceSale, 0) }}</h4>
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

                    <!-- Sales vs Returns Payment Stats -->
                    <div class="row g-3 mb-4">
                        <div class="col-6">
                            <div class="text-center p-3 rounded bg-success bg-opacity-10">
                                <div class="h4 fw-bold text-success mb-1">{{ currency($totalPayment) }}</div>
                                <div class="text-muted small">Sales Payments</div>
                                <div class="text-success small">{{ $salePayments->sum('transaction_count') }} transactions</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center p-3 rounded bg-warning bg-opacity-10">
                                <div class="h4 fw-bold text-warning mb-1">{{ currency($saleReturnPayments->sum('total')) }}</div>
                                <div class="text-muted small">Returns Payments</div>
                                <div class="text-warning small">{{ $saleReturnPayments->sum('transaction_count') }} returns</div>
                            </div>
                        </div>
                    </div>

                    <!-- Net Payment -->
                    <div class="text-center mb-4">
                        @php
                            $netPayment = $totalPayment - $saleReturnPayments->sum('total');
                        @endphp
                        <div class="display-6 fw-bold mb-1 {{ $netPayment >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ currency($netPayment) }}
                        </div>
                        <div class="text-muted">Net Payments</div>
                        <div class="small text-muted">
                            {{ $salePayments->sum('transaction_count') + $saleReturnPayments->sum('transaction_count') }} total transactions
                        </div>
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

                    <!-- Payment Methods Summary -->
                    <div class="mb-3">
                        <h6 class="mb-2">Payment Methods</h6>
                        <div class="row g-2">
                            @php
                                $topPaymentMethods = $salePayments->take(3);
                            @endphp
                            @foreach($topPaymentMethods as $payment)
                                <div class="col-12">
                                    <div class="d-flex justify-content-between align-items-center p-2 rounded bg-light">
                                        <div class="d-flex align-items-center">
                                            <div class="payment-method-icon me-2">
                                                @switch(strtolower($payment->payment_method))
                                                    @case('cash')
                                                        <i class="fa fa-money text-success"></i>
                                                        @break
                                                    @case('card')
                                                        <i class="fa fa-credit-card text-primary"></i>
                                                        @break
                                                    @case('bank')
                                                        <i class="fa fa-university text-info"></i>
                                                        @break
                                                    @case('mobile money')
                                                        <i class="fa fa-mobile text-warning"></i>
                                                        @break
                                                    @default
                                                        <i class="fa fa-credit-card text-secondary"></i>
                                                @endswitch
                                            </div>
                                            <span class="small fw-medium">{{ $payment->payment_method }}</span>
                                        </div>
                                        <div class="text-end">
                                            <div class="fw-bold">{{ currency($payment->total) }}</div>
                                            <small class="text-muted">{{ $payment->transaction_count }} txns</small>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Payment Methods Chart -->
                    <div wire:ignore>
                        <div id="chartContainer" style="height: 200px; width: 100%;"></div>
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

    <!-- Payment Method Wise Summary -->
    <div class="row g-4 mt-4">
        <!-- Sales Payment Methods -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0 fw-bold">
                            <i class="fa fa-credit-card text-success me-2"></i>
                            Sales Payment Methods
                        </h6>
                        <span class="badge bg-success rounded-pill">{{ $salePayments->count() }} Methods</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-3">#</th>
                                    <th>Payment Method</th>
                                    <th class="text-center">Transactions</th>
                                    <th class="text-end pe-3">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($salePayments as $index => $payment)
                                    <tr wire:key="sale-payment-{{ $index }}">
                                        <td class="ps-3">{{ $index + 1 }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="payment-method-icon me-2">
                                                    @switch(strtolower($payment->payment_method))
                                                        @case('cash')
                                                            <i class="fa fa-money text-success"></i>
                                                            @break
                                                        @case('card')
                                                            <i class="fa fa-credit-card text-primary"></i>
                                                            @break
                                                        @case('bank')
                                                            <i class="fa fa-university text-info"></i>
                                                            @break
                                                        @case('mobile money')
                                                            <i class="fa fa-mobile text-warning"></i>
                                                            @break
                                                        @default
                                                            <i class="fa fa-credit-card text-secondary"></i>
                                                    @endswitch
                                                </div>
                                                <span class="fw-medium">{{ $payment->payment_method }}</span>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-dark">{{ $payment->transaction_count }}</span>
                                        </td>
                                        <td class="text-end pe-3 fw-bold">{{ currency($payment->total) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">
                                            <i class="fa fa-info-circle me-2"></i>
                                            No payment data available
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot class="bg-light">
                                <tr>
                                    <td colspan="2" class="ps-3 fw-bold">Total</td>
                                    <td class="text-center fw-bold">{{ $salePayments->sum('transaction_count') }}</td>
                                    <td class="text-end pe-3 fw-bold">{{ currency($salePayments->sum('total')) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sale Returns Payment Methods -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0 fw-bold">
                            <i class="fa fa-undo text-warning me-2"></i>
                            Returns Payment Methods
                        </h6>
                        <span class="badge bg-warning rounded-pill">{{ $saleReturnPayments->count() }} Methods</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-3">#</th>
                                    <th>Payment Method</th>
                                    <th class="text-center">Returns</th>
                                    <th class="text-end pe-3">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($saleReturnPayments as $index => $payment)
                                    <tr wire:key="return-payment-{{ $index }}">
                                        <td class="ps-3">{{ $index + 1 }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="payment-method-icon me-2">
                                                    @switch(strtolower($payment->payment_method))
                                                        @case('cash')
                                                            <i class="fa fa-money text-success"></i>
                                                            @break
                                                        @case('card')
                                                            <i class="fa fa-credit-card text-primary"></i>
                                                            @break
                                                        @case('bank')
                                                            <i class="fa fa-university text-info"></i>
                                                            @break
                                                        @case('mobile money')
                                                            <i class="fa fa-mobile text-warning"></i>
                                                            @break
                                                        @default
                                                            <i class="fa fa-credit-card text-secondary"></i>
                                                    @endswitch
                                                </div>
                                                <span class="fw-medium">{{ $payment->payment_method }}</span>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-dark">{{ $payment->transaction_count }}</span>
                                        </td>
                                        <td class="text-end pe-3 fw-bold text-danger">{{ currency($payment->total) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">
                                            <i class="fa fa-info-circle me-2"></i>
                                            No return payment data available
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot class="bg-light">
                                <tr>
                                    <td colspan="2" class="ps-3 fw-bold">Total</td>
                                    <td class="text-center fw-bold">{{ $saleReturnPayments->sum('transaction_count') }}</td>
                                    <td class="text-end pe-3 fw-bold text-danger">{{ currency($saleReturnPayments->sum('total')) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Method Summary Cards -->
    <div class="row g-4 mt-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent py-3">
                    <h6 class="card-title mb-0 fw-bold">
                        <i class="fa fa-chart-pie text-primary me-2"></i>
                        Payment Method Summary
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        @php
                            $allPaymentMethods = $salePayments->pluck('payment_method')->merge($saleReturnPayments->pluck('payment_method'))->unique();
                        @endphp

                        @foreach($allPaymentMethods as $method)
                            @php
                                $saleAmount = $salePayments->where('payment_method', $method)->first()?->total ?? 0;
                                $returnAmount = $saleReturnPayments->where('payment_method', $method)->first()?->total ?? 0;
                                $netAmount = $saleAmount - $returnAmount;
                                $saleCount = $salePayments->where('payment_method', $method)->first()?->transaction_count ?? 0;
                                $returnCount = $saleReturnPayments->where('payment_method', $method)->first()?->transaction_count ?? 0;
                            @endphp

                            <div class="col-md-4 col-lg-3">
                                <div class="card border-0 bg-light h-100">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="payment-method-icon me-2">
                                                @switch(strtolower($method))
                                                    @case('cash')
                                                        <i class="fa fa-money fa-lg text-success"></i>
                                                        @break
                                                    @case('card')
                                                        <i class="fa fa-credit-card fa-lg text-primary"></i>
                                                        @break
                                                    @case('bank')
                                                        <i class="fa fa-university fa-lg text-info"></i>
                                                        @break
                                                    @case('mobile money')
                                                        <i class="fa fa-mobile fa-lg text-warning"></i>
                                                        @break
                                                    @default
                                                        <i class="fa fa-credit-card fa-lg text-secondary"></i>
                                                @endswitch
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-bold">{{ $method }}</h6>
                                                <small class="text-muted">{{ $saleCount + $returnCount }} transactions</small>
                                            </div>
                                        </div>

                                        <div class="row g-2 text-center">
                                            <div class="col-4">
                                                <div class="bg-success bg-opacity-10 rounded p-2">
                                                    <div class="fw-bold text-success">{{ currency($saleAmount) }}</div>
                                                    <small class="text-muted">Sales</small>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="bg-warning bg-opacity-10 rounded p-2">
                                                    <div class="fw-bold text-warning">{{ currency($returnAmount) }}</div>
                                                    <small class="text-muted">Returns</small>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="bg-primary bg-opacity-10 rounded p-2">
                                                    <div class="fw-bold {{ $netAmount >= 0 ? 'text-success' : 'text-danger' }}">
                                                        {{ currency($netAmount) }}
                                                    </div>
                                                    <small class="text-muted">Net</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
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

    @push('styles')
        <style>
            .payment-method-icon {
                width: 32px;
                height: 32px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
                background: rgba(0,0,0,0.05);
            }

            .card.bg-light:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                transition: all 0.3s ease;
            }

            .bg-success.bg-opacity-10 {
                background-color: rgba(25, 135, 84, 0.1) !important;
            }

            .bg-warning.bg-opacity-10 {
                background-color: rgba(255, 193, 7, 0.1) !important;
            }

            .bg-primary.bg-opacity-10 {
                background-color: rgba(13, 110, 253, 0.1) !important;
            }

            .table-hover tbody tr:hover {
                background-color: rgba(0,0,0,0.02);
            }

            .badge.bg-light {
                background-color: #f8f9fa !important;
                color: #6c757d !important;
            }
        </style>
    @endpush
</div>
