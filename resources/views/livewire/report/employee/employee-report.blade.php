<div>
    <div class="card mb-3">
        <div class="card-header">
            <div class="card-tools">
                <div class="row g-2 mb-3">
                    <div class="col-md-3">
                        <b><label for="from_date">From Date</label></b>
                        {{ html()->date('from_date')->value('')->class('form-control')->id('from_date')->attribute('wire:model.live', 'from_date') }}
                    </div>
                    <div class="col-md-3">
                        <b><label for="to_date">To Date</label></b>
                        {{ html()->date('to_date')->value('')->class('form-control')->id('to_date')->attribute('wire:model.live', 'to_date') }}
                    </div>
                    <div class="col-md-3" wire:ignore>
                        <b><label for="branch_id">Branch</label></b>
                        {{ html()->select('branch_id', [])->value('')->class('select-branch_id-list')->id('branch_id')->attribute('wire:model', 'branch_id')->placeholder('All') }}
                    </div>
                    <div class="col-md-3" wire:ignore>
                        <b><label for="employee_id">Employee</label></b>
                        {{ html()->select('employee_id', [])->value('')->class('select-employee_id-list')->id('employee_id')->attribute('wire:model', 'employee_id')->placeholder('All') }}
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6" wire:ignore>
                        <b><label for="product_id">Product</label></b>
                        {{ html()->select('product_id', [])->value('')->class('select-product_id-list')->id('product_id')->attribute('wire:model', 'product_id')->placeholder('All') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Sales Details</h5>
                        <select wire:model.live="perPage" class="form-select form-select-sm" style="width: 100px">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr class="bg-primary">
                                    <th class="text-white">Employee</th>
                                    <th class="text-white">Item</th>
                                    <th class="text-white text-end">Count</th>
                                    <th class="text-white text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($items as $item)
                                    <tr>
                                        <td>{{ $item->employee }}</td>
                                        <td>{{ $item->product }}</td>
                                        <td class="text-end">{{ number_format($item->total_quantity) }}</td>
                                        <td class="text-end">{{ currency($item->total_amount) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        {{ $items->links() }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="mt-4" wire:ignore>
                        <div id="employeeChart" style="min-width:100%; width:100%; min-height:400px; height:calc(100vh - 400px); background: #f8f9fa; border-radius: 8px; padding: 15px;"></div>
                    </div>
                </div>
            </div>
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">Summary</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr class="bg-light">
                                    <th>Employee</th>
                                    <th class="text-end">Count</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($summary as $item)
                                    <tr>
                                        <td>{{ $item->employee }}</td>
                                        <td class="text-end">{{ number_format($item->total_quantity) }}</td>
                                        <td class="text-end">{{ currency($item->total_amount) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            $(document).ready(function() {
                // Initial chart render
                Livewire.dispatch('employeeReportFilterChanged');

                $('#branch_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('branch_id', value);
                });
                $('#employee_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('employee_id', value);
                });
                $('#product_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('product_id', value);
                });
                window.addEventListener('updatePieChart', event => {
                    var options = {
                        title: {
                            text: "Employee Sales Distribution"
                        },
                        data: [{
                            type: "doughnut",
                            innerRadius: "50%",
                            showInLegend: true,
                            legendText: "{label}",
                            indexLabel: "{label}: {y}",
                            indexLabelFontSize: 12,
                            indexLabelFontFamily: "Helvetica Neue",
                            dataPoints: event.detail[0].summary
                        }]
                    };

                    $("#employeeChart").CanvasJSChart(options);
                });
            });
        </script>
    @endpush
</div>
