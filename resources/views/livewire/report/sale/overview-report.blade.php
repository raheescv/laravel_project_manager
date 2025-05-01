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
        <div class="col-md-12">
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">Overview</h6>
                </div>
                <div class="card-body p-2">
                    <div class="table-responsive">
                        <table class="table table-borderless table-sm">
                            <tbody>
                                @foreach ($overview as $title => $value)
                                    <tr>
                                        <td class="fw-bold">{{ $title }}</td>
                                        <td class="text-end fw-bold">{{ currency($value) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
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
    @push('styles')
        <link rel="stylesheet" href="{{ asset('assets/vendors/dataTable/datatables.min.css') }}">
    @endpush
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
        </script>
        <script src="{{ asset('assets/vendors/dataTable/bootstrap.bundle.min.js') }}"></script>
        <script src="{{ asset('assets/vendors/dataTable/pdfmake.min.js') }}"></script>
        <script src="{{ asset('assets/vendors/dataTable/vfs_fonts.js') }}"></script>
        <script src="{{ asset('assets/vendors/dataTable/datatables.min.js') }}"></script>
    @endpush
</div>
