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
    <div class="row">
        <div class="col-md-12 mb-3">
            <div class="row">
                <div class="col-md-7" id="saleAndSaleReturnArea">
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Sales Overview</h5>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h6 class="mb-0">Total Sales</h6>
                                    <p class="h3 mb-0">{{ currency($totalSales) }}</p>
                                </div>
                                <div>
                                    <h6 class="mb-0">Total Sales Returns</h6>
                                    <p class="h3 mb-0 pull-right">{{ currency($totalSalesReturn) }}</p>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">No. of Sales</h6>
                                    <p class="h4 mb-0">{{ $noOfSales }}</p>
                                </div>
                                <div>
                                    <h6 class="mb-0">No. of Sales Returns</h6>
                                    <p class="h4 mb-0 pull-right">{{ $noOfSalesReturns }}</p>
                                </div>
                            </div>
                            <div class="progress mt-3" style="height: 10px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: {{ $noOfSales > 0 ? (($noOfSales - $noOfSalesReturns) / $noOfSales) * 100 : 0 }}%;"
                                    aria-valuenow="{{ $noOfSales - $noOfSalesReturns }}" aria-valuemin="0" aria-valuemax="{{ $noOfSales }}"></div>
                            </div>
                            <p class="mt-2">Sales vs Returns Ratio</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="card mb-3 mb-xl-3">
                                <div class="card-body py-3">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="flex-shrink-0">
                                            <i class="d-flex align-items-center justify-content-center pli-money display-5"></i>
                                        </div>
                                        <div class="flex-grow-1 ms-4">
                                            <h5 class="h2 mb-0">{{ currency($netSales, 0) }}</h5>
                                            <p class=" text-opacity-75 mb-0">Net Sales</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="card mb-3 mb-xl-3">
                                <div class="card-body py-3">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="flex-shrink-0">
                                            <i class="d-flex align-items-center justify-content-center pli-money display-5"></i>
                                        </div>
                                        <div class="flex-grow-1 ms-4">
                                            <h5 class="h2 mb-0">{{ currency($saleDiscount, 0) }}</h5>
                                            <p class=" text-opacity-75 mb-0">Sales Discount</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row" id="itemTotalArea">
                        <div class="col-md-4">
                            <div class="card bg-info text-white mb-3 mb-xl-3">
                                <div class="card-body py-3">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="flex-shrink-0">
                                            <i class="d-flex align-items-center justify-content-center pli-shop display-5"></i> <!-- Changed for Item Total -->
                                        </div>
                                        <div class="flex-grow-1 ms-4">
                                            <h5 class="h2 mb-0">{{ currency($itemTotal, 0) }}</h5>
                                            <p class="text-white text-opacity-75 mb-0">Item Total</p>
                                        </div>
                                    </div>
                                    <div class="progress progress-md mb-2">
                                        <div class="progress-bar bg-white" role="progressbar" style="width: {{ $itemTotal > 0 ? 100 : 0 }}%;" aria-valuenow="{{ $itemTotal }}" aria-valuemin="0"
                                            aria-valuemax="{{ $itemTotal }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-success text-white mb-3 mb-xl-3">
                                <div class="card-body py-3">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="flex-shrink-0">
                                            <i class="d-flex align-items-center justify-content-center pli-reverbnation display-5"></i> <!-- Better icon for Service -->
                                        </div>
                                        <div class="flex-grow-1 ms-4">
                                            <h5 class="h2 mb-0">{{ currency($serviceSale, 0) }}</h5>
                                            <p class="text-white text-opacity-75 mb-0">Service</p>
                                        </div>
                                    </div>
                                    <div class="progress progress-md mb-2">
                                        <div class="progress-bar bg-white" role="progressbar" style="width: {{ $itemTotal > 0 ? ($serviceSale / $itemTotal) * 100 : 0 }}%;"
                                            aria-valuenow="{{ $serviceSale }}" aria-valuemin="0" aria-valuemax="{{ $itemTotal }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-warning text-white mb-3 mb-xl-3">
                                <div class="card-body py-3">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="flex-shrink-0">
                                            <i class="d-flex align-items-center justify-content-center pli-full-cart display-5"></i> <!-- Better icon for Product -->
                                        </div>
                                        <div class="flex-grow-1 ms-4">
                                            <h5 class="h2 mb-0">{{ currency($productSale, 0) }}</h5>
                                            <p class="text-white text-opacity-75 mb-0">Product</p>
                                        </div>
                                    </div>
                                    <div class="progress progress-md mb-2">
                                        <div class="progress-bar bg-white" role="progressbar" style="width: {{ $itemTotal > 0 ? ($productSale / $itemTotal) * 100 : 0 }}%;"
                                            aria-valuenow="{{ $productSale }}" aria-valuemin="0" aria-valuemax="{{ $itemTotal }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="card">
                        <div class="card-body">
                            <div class="col-md-12">
                                <div class="card bg-info text-white mb-3 mb-xl-3">
                                    <div class="card-body py-3">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="flex-shrink-0">
                                                <i class="d-flex align-items-center justify-content-center pli-money-2 display-5"></i>
                                            </div>
                                            <div class="flex-grow-1 ms-4">
                                                <h5 class="h2 mb-0">{{ currency($totalPayment) }}</h5>
                                                <p class="text-white text-opacity-75 mb-0">Total Payment</p>
                                            </div>
                                        </div>
                                        <div class="progress progress-md mb-2">
                                            <div class="progress-bar bg-white" role="progressbar" style="width: {{ $totalPayment > 0 ? ($totalPayment / $totalSales) * 100 : 0 }}%;"
                                                aria-valuenow="{{ $totalPayment }}" aria-valuemin="0" aria-valuemax="{{ $totalSales }}">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div wire:ignore id="chartContainer" style="height: 370px; width: 100%;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0">Employee Sales</h6>
                        <div class="d-flex gap-2">
                            <select wire:model.live="employeePerPage" class="form-select form-select-sm" style="width: 80px">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                            <input type="search" wire:model.live.debounce.300ms="employeeSearch" class="form-control form-control-sm" placeholder="Search employee...">
                        </div>
                    </div>
                </div>
                <div class="card-body p-2">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th><x-sortable-header :direction="$employeeSortDirection" :sortField="$employeeSortField" field="employee" label="#" /></th>
                                    <th><x-sortable-header :direction="$employeeSortDirection" :sortField="$employeeSortField" field="employee.employee" label="Employee" /></th>
                                    <th class="text-end"><x-sortable-header :direction="$employeeSortDirection" :sortField="$employeeSortField" field="employee.quantity" label="Quantity" /></th>
                                    <th class="text-end"><x-sortable-header :direction="$employeeSortDirection" :sortField="$employeeSortField" field="employee.total" label="Total" /></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($employees as $item)
                                    <tr wire:key="emp-{{ $item->id }}">
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $item->employee }}</td>
                                        <td class="text-end">{{ currency($item->quantity) }}</td>
                                        <td class="text-end">{{ currency($item->total) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="2" class="text-end fw-bold">Total</td>
                                    <td class="text-end fw-bold">{{ currency($employeeQuantity) }}</td>
                                    <td class="text-end fw-bold">{{ currency($employeeTotal) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                        @if ($employees->hasPages())
                            <div class="d-flex justify-content-end mt-3">
                                {{ $employees->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0">Product/Service Sales</h6>
                        <div class="d-flex gap-2">
                            <select wire:model.live="productPerPage" class="form-select form-select-sm" style="width: 80px">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                            <input type="search" wire:model.live.debounce.300ms="productSearch" class="form-control form-control-sm" placeholder="Search product...">
                        </div>
                    </div>
                </div>
                <div class="card-body p-2">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th> <x-sortable-header :direction="$productSortDirection" :sortField="$productSortField" field="product.name" label="#" /> </th>
                                    <th> <x-sortable-header :direction="$productSortDirection" :sortField="$productSortField" field="product.name" label="Name" /> </th>
                                    <th class="text-end"> <x-sortable-header :direction="$productSortDirection" :sortField="$productSortField" field="product.quantity" label="quantity" /> </th>
                                    <th class="text-end"> <x-sortable-header :direction="$productSortDirection" :sortField="$productSortField" field="product.total" label="total" /> </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($products as $item)
                                    <tr wire:key="{{ $item->id }}">
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $item->product }}</td>
                                        <td class="text-end">{{ currency($item->quantity) }}</td>
                                        <td class="text-end">{{ currency($item->total) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="2" class="text-end fw-bold">Total</td>
                                    <td class="text-end fw-bold">{{ currency($totalProductQuantity) }}</td>
                                    <td class="text-end fw-bold">{{ currency($itemTotal) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                        @if ($products->hasPages())
                            <div class="d-flex justify-content-end mt-3">
                                {{ $products->links() }}
                            </div>
                        @endif
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
                        text: "Payment Method Overview"
                    },
                    data: [{
                        type: "pie",
                        startAngle: 45,
                        showInLegend: "true",
                        legendText: "{label}",
                        indexLabel: "{label} ({y})",
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
