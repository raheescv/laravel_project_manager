<div>
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="card-title mb-0">Products Wise</h5>
                        <select wire:model.live="productPerPage" class="form-select form-select-sm" style="width: 100px">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                    <div class="card-tools" wire:ignore>
                        <div class="input-group">
                            <span class="input-group-text bg-light">Product</span>
                            {{ html()->select('product_id', [])->value('')->class('select-product_id-list customer_item_table_change form-select')->id('product_id')->placeholder('All') }}
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead>
                                <tr class="bg-primary">
                                    <th class="text-white">Customer</th>
                                    <th class="text-white">Product</th>
                                    <th class="text-white text-end">Quantity</th>
                                    <th class="text-white text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($products as $item)
                                    <tr>
                                        <td>{{ $item->customer }}</td>
                                        <td>{{ $item->product }}</td>
                                        <td class="text-end">{{ number_format($item->total_quantity) }}</td>
                                        <td class="text-end">{{ currency($item->total_amount) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="fw-bold bg-light">
                                <th colspan="2" class="text-end">Total:</th>
                                <th class="text-end">{{ currency($productQuantity) }}</th>
                                <th class="text-end">{{ currency($productAmount) }}</th>
                            </tfoot>
                        </table>
                    </div>
                    <div class="mt-3">
                        {{ $products->links() }}
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="mt-4 w-100" wire:ignore>
                        <div id="productPieChart" style="min-width:100%; width:100%; min-height:600px; height:calc(100vh - 400px); background: #f8f9fa; border-radius: 8px; padding: 15px;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="card-title mb-0">Employee Wise</h5>
                        <select wire:model.live="employeePerPage" class="form-select form-select-sm" style="width: 100px">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                    <div class="card-tools" wire:ignore>
                        <div class="input-group">
                            <span class="input-group-text bg-light">Employee</span>
                            {{ html()->select('employee_id', [])->value('')->class('select-employee_id-list customer_item_table_change form-select')->id('employee_id')->placeholder('All') }}
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead>
                                <tr class="bg-primary">
                                    <th class="text-white">Customer</th>
                                    <th class="text-white">Employee</th>
                                    <th class="text-white text-end">Quantity</th>
                                    <th class="text-white text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($employees as $item)
                                    <tr>
                                        <td>{{ $item->customer }}</td>
                                        <td>{{ $item->employee }}</td>
                                        <td class="text-end">{{ number_format($item->total_quantity) }}</td>
                                        <td class="text-end">{{ currency($item->total_amount) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="fw-bold bg-light">
                                <th colspan="2" class="text-end">Total:</th>
                                <th class="text-end">{{ currency($employeeQuantity) }}</th>
                                <th class="text-end">{{ currency($employeeAmount) }}</th>
                            </tfoot>
                        </table>
                    </div>
                    <div class="mt-3">
                        {{ $employees->links() }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="mt-4 w-100" wire:ignore>
                        <div id="employeePieChart" style="min-width:100%; width:100%; min-height:600px; height:calc(100vh - 400px); background: #f8f9fa; border-radius: 8px; padding: 15px;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @push('scripts')
        <script>
            $(document).ready(function() {
                var data = {
                    from_date: $('#from_date').val(),
                    to_date: $('#to_date').val(),
                    customer_id: $('#customer_id').val() || null,
                    product_id: $('#product_id').val() || null,
                    employee_id: $('#employee_id').val(),
                    branch_id: $('#table_branch_id').val() || null,
                };
                Livewire.dispatch('customerItemsFilterChanged', data);
                $('.customer_item_table_change').on('change keyup', function() {
                    var data = {
                        from_date: $('#from_date').val(),
                        to_date: $('#to_date').val(),
                        customer_id: $('#customer_id').val() || null,
                        product_id: $('#product_id').val() || null,
                        employee_id: $('#employee_id').val(),
                        branch_id: $('#table_branch_id').val() || null,
                        nationality: $('#nationality').val(),
                    };
                    Livewire.dispatch('customerItemsFilterChanged', data);
                });

                window.addEventListener('updatePieChart', event => {
                    var data = event.detail[0];
                    var options = {
                        title: {
                            text: "Top 10 Items"
                        },
                        data: [{
                            type: "doughnut",
                            innerRadius: "50%",
                            showInLegend: true,
                            legendText: "{label}",
                            indexLabel: "{label}: {y}",
                            indexLabelFontSize: 12,
                            indexLabelFontFamily: "Helvetica Neue",
                            dataPoints: data.product
                        }],
                    };
                    $("#productPieChart").CanvasJSChart(options);

                    var employeeOptions = {
                        title: {
                            text: "Top 10 Employees"
                        },
                        data: [{
                            type: "doughnut",
                            innerRadius: "50%",
                            showInLegend: true,
                            legendText: "{label}",
                            indexLabel: "{label}: {y}",
                            indexLabelFontSize: 12,
                            indexLabelFontFamily: "Helvetica Neue",
                            dataPoints: data.employee
                        }],
                    };
                    $("#employeePieChart").CanvasJSChart(employeeOptions);
                });
            });
        </script>
    @endpush
</div>
