<div>
    <div class="card mb-3">
        <div class="card-header">
            <div class="col-lg-12">
                <div class="row">
                    <div class="col-md-2">
                        <label for="from_date" class="text-white">From Date</label>
                        {{ html()->date('from_date')->value('')->class('form-control')->id('from_date')->attribute('wire:model.live', 'from_date') }}
                    </div>
                    <div class="col-md-2">
                        <label for="to_date" class="text-white">To Date</label>
                        {{ html()->date('to_date')->value('')->class('form-control')->id('to_date')->attribute('wire:model.live', 'to_date') }}
                    </div>
                    <div class="col-md-4" wire:ignore>
                        <label for="branch_id" class="text-white">Branch</label>
                        {{ html()->select('branch_id', [session('branch_id') => session('branch_name')])->value(session('branch_id'))->class('select-assigned-branch_id-list')->id('branch_id')->placeholder('Branch') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12 mb-3">
            <div class="row">
                <div class="col-md-6" id="saleAndSaleReturnArea">
                    <div class="row">
                        <div class="card bg-gradient-primary  mb-3">
                            <div class="card-body">
                                <h5 class="card-title">Sales Overview</h5>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <h6 class="mb-0">Total Sales</h6>
                                        <p class="h4 mb-0">{{ currency($totalSales) }}</p>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">Total Sales Returns</h6>
                                        <p class="h4 mb-0">{{ currency($totalSalesReturn) }}</p>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0">No. of Sales</h6>
                                        <p class="h4 mb-0">{{ $noOfSales }}</p>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">No. of Sales Returns</h6>
                                        <p class="h4 mb-0">{{ $noOfSalesReturns }}</p>
                                    </div>
                                </div>
                                <div class="progress mt-3" style="height: 10px;">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: {{ $noOfSales > 0 ? (($noOfSales - $noOfSalesReturns) / $noOfSales) * 100 : 0 }}%;"
                                        aria-valuenow="{{ $noOfSales - $noOfSalesReturns }}" aria-valuemin="0" aria-valuemax="{{ $noOfSales }}"></div>
                                </div>
                                <p class=" mt-2">Sales vs Returns Ratio</p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="row">
                            <div class="col-6">
                                <div class="card mb-3 mb-xl-3">
                                    <div class="card-body py-3">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="flex-shrink-0">
                                                <i class="d-flex align-items-center justify-content-center pli-money display-5"></i>
                                            </div>
                                            <div class="flex-grow-1 ms-4">
                                                <h5 class="h2 mb-0">{{ currency($netSales) }}</h5>
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
                                                <h5 class="h2 mb-0">{{ currency($saleDiscount) }}</h5>
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
                                                <h5 class="h2 mb-0">{{ currency($itemTotal) }}</h5>
                                                <p class="text-white text-opacity-75 mb-0">Item Total</p>
                                            </div>
                                        </div>
                                        <div class="progress progress-md mb-2">
                                            <div class="progress-bar bg-white" role="progressbar" style="width: {{ $itemTotal > 0 ? 100 : 0 }}%;" aria-valuenow="{{ $itemTotal }}"
                                                aria-valuemin="0" aria-valuemax="{{ $itemTotal }}">
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
                                                <h5 class="h2 mb-0">{{ currency($serviceSale) }}</h5>
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
                                                <h5 class="h2 mb-0">{{ currency($productSale) }}</h5>
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
                </div>
                <div class="col-md-6">
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
                    <h6 class="card-title mb-0">Employee Sales</h6>
                </div>
                <div class="card-body p-2">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle" id="employeeDataTable">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Employee</th>
                                    <th class="text-end">Quantity</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($employees as $item)
                                    <tr>
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
                                    <td class="text-end fw-bold">{{ currency($employees->sum('quantity')) }}</td>
                                    <td class="text-end fw-bold">{{ currency($employees->sum('total')) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">Product/Service Sales</h6>
                </div>
                <div class="card-body p-2">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle" id="productDataTable">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Product / Service</th>
                                    <th class="text-end">Quantity</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($products as $item)
                                    <tr>
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
                                    <td class="text-end fw-bold">{{ currency($products->sum('quantity')) }}</td>
                                    <td class="text-end fw-bold">{{ currency($products->sum('total')) }}</td>
                                </tr>
                            </tfoot>
                        </table>
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
                    @this.set('branch_id', value);
                });

                employeeDataTable = new DataTable('#employeeDataTable', {
                    paging: false, // Disable paging
                    scrollY: '400px',
                    scrollCollapse: true,
                    order: [
                        [3, 'desc']
                    ],
                    dom: '<"d-flex justify-content-between align-items-center mb-3"<"d-flex"B><"ml-auto"f>>t', // Add margin-bottom to the container
                    buttons: [{
                            extend: 'excelHtml5',
                            text: 'Excel',
                            title: 'Employee Sales',
                            className: 'btn btn-primary btn-sm'
                        },
                        {
                            extend: 'pdfHtml5',
                            text: 'PDF',
                            title: 'Employee Sales',
                            className: 'btn btn-secondary btn-sm',
                            customize: function(doc) {
                                // Set table width to 100%
                                doc.content[1].table.widths = ['10%', '50%', '20%', '20%']; // Product column set to 50%
                                // Align headers to match table alignment
                                doc.styles.tableHeader = {
                                    alignment: 'left', // Center align headers by default
                                    bold: true,
                                    fontSize: 12,
                                    color: 'black'
                                };

                                // Ensure "Quantity" and "Total" columns are right-aligned in the body
                                doc.content[1].table.body.forEach(function(row, rowIndex) {
                                    if (rowIndex === 0) {
                                        row[0].alignment = 'right';
                                        row[2].alignment = 'right';
                                        row[3].alignment = 'right';
                                    } else {
                                        row[0].alignment = 'right';
                                        row[2].alignment = 'right';
                                        row[3].alignment = 'right';
                                    }
                                });
                            }
                        }
                    ]
                });

                productDataTable = new DataTable('#productDataTable', {
                    scrollY: '400px',
                    scrollCollapse: true,
                    order: [
                        [3, 'desc']
                    ],
                    dom: '<"d-flex justify-content-between align-items-center mb-3"<"d-flex"B><"ml-auto"f>>t', // Add margin-bottom to the container
                    buttons: [{
                            extend: 'excelHtml5',
                            text: 'Excel',
                            title: 'Product Sales',
                            className: 'btn btn-primary btn-sm'
                        },
                        {
                            extend: 'pdfHtml5',
                            text: 'PDF',
                            title: 'Product Sales',
                            className: 'btn btn-secondary btn-sm',
                            customize: function(doc) {
                                // Set specific column widths
                                doc.content[1].table.widths = ['10%', '50%', '20%', '20%']; // Product column set to 50%

                                // Align headers to match table alignment
                                doc.styles.tableHeader = {
                                    alignment: 'center', // Center align headers by default
                                    bold: true,
                                    fontSize: 12,
                                    color: 'black'
                                };

                                // Ensure "Quantity" and "Total" columns are right-aligned in the body
                                doc.content[1].table.body.forEach(function(row, rowIndex) {
                                    if (rowIndex === 0) {
                                        row[0].alignment = 'right'; // # header
                                        row[2].alignment = 'right'; // Quantity header
                                        row[3].alignment = 'right'; // Total header
                                    } else {
                                        row[0].alignment = 'right'; // # body
                                        row[2].alignment = 'right'; // Quantity body
                                        row[3].alignment = 'right'; // Total body
                                    }
                                });
                            }
                        }
                    ]
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
        <script src="{{ asset('assets/vendors/dataTable/bootstrap.bundle.min.js') }}"></script>
        <script src="{{ asset('assets/vendors/dataTable/pdfmake.min.js') }}"></script>
        <script src="{{ asset('assets/vendors/dataTable/vfs_fonts.js') }}"></script>
        <script src="{{ asset('assets/vendors/dataTable/datatables.min.js') }}"></script>
        <script type="text/javascript" src="https://cdn.canvasjs.com/jquery.canvasjs.min.js"></script>
    @endpush
    @push('styles')
        <link rel="stylesheet" href="{{ asset('assets/vendors/dataTable/datatables.min.css') }}">
    @endpush
</div>
