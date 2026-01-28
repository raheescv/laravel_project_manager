<div>
    <!-- Filters Section -->
    <div class="card mb-3 shadow-sm">
        <div class="card-header bg-light py-3">
            <h5 class="card-title mb-0 d-flex align-items-center">
                <i class="fa fa-filter me-2 text-primary"></i>
                Filters
            </h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                    <div class="col-md-3">
                    <label for="from_date" class="form-label small fw-medium">
                        <i class="fa fa-calendar me-1 text-muted"></i>
                        From Date
                    </label>
                    {{ html()->date('from_date')->value('')->class('form-control border-secondary-subtle shadow-sm')->id('from_date')->attribute('wire:model.live', 'from_date') }}
                    </div>
                    <div class="col-md-3">
                    <label for="to_date" class="form-label small fw-medium">
                        <i class="fa fa-calendar me-1 text-muted"></i>
                        To Date
                    </label>
                    {{ html()->date('to_date')->value('')->class('form-control border-secondary-subtle shadow-sm')->id('to_date')->attribute('wire:model.live', 'to_date') }}
                    </div>
                    <div class="col-md-3" wire:ignore>
                    <label for="branch_id" class="form-label small fw-medium">
                        <i class="fa fa-building me-1 text-muted"></i>
                        Branch
                    </label>
                    {{ html()->select('branch_id', [])->value('')->class('select-branch_id-list border-secondary-subtle shadow-sm')->id('branch_id')->attribute('wire:model', 'branch_id')->placeholder('All Branches') }}
                    </div>
                    <div class="col-md-3" wire:ignore>
                    <label for="employee_id" class="form-label small fw-medium">
                        <i class="fa fa-user me-1 text-muted"></i>
                        Employee
                    </label>
                    {{ html()->select('employee_id', [])->value('')->class('select-employee_id-list border-secondary-subtle shadow-sm')->id('employee_id')->attribute('wire:model', 'employee_id')->placeholder('All Employees') }}
                </div>
                    <div class="col-md-6" wire:ignore>
                    <label for="product_id" class="form-label small fw-medium">
                        <i class="fa fa-box me-1 text-muted"></i>
                        Product
                    </label>
                    {{ html()->select('product_id', [])->value('')->class('select-product_id-list border-secondary-subtle shadow-sm')->id('product_id')->attribute('wire:model', 'product_id')->placeholder('All Products') }}
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <!-- Sales Details Table -->
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-light py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-semibold d-flex align-items-center">
                            <i class="fa fa-list me-2 text-primary"></i>
                            Sales Details
                        </h5>
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-success btn-sm d-flex align-items-center shadow-sm" title="Export to Excel" data-bs-toggle="tooltip" wire:click="export()">
                                <i class="fa fa-file-excel me-1"></i>
                                <span class="d-none d-md-inline">Export</span>
                            </button>
                            <select wire:model.live="perPage" class="form-select form-select-sm border-secondary-subtle shadow-sm" style="width: 120px">
                                <option value="10">10 per page</option>
                                <option value="25">25 per page</option>
                                <option value="50">50 per page</option>
                                <option value="100">100 per page</option>
                        </select>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="border-0">
                                        <i class="fa fa-user me-2 text-secondary small"></i>
                                        Employee
                                    </th>
                                    <th class="border-0">
                                        <i class="fa fa-box me-2 text-secondary small"></i>
                                        Product
                                    </th>
                                    <th class="border-0 text-end">
                                        <i class="fa fa-hashtag me-2 text-secondary small"></i>
                                        Quantity
                                    </th>
                                    <th class="border-0 text-end">
                                        <i class="fa fa-money me-2 text-secondary small"></i>
                                        Sale Amount
                                    </th>
                                    <th class="border-0 text-end">
                                        <i class="fa fa-undo me-2 text-secondary small"></i>
                                        Return Amount
                                    </th>
                                    <th class="border-0 text-end">
                                        <i class="fa fa-calculator me-2 text-secondary small"></i>
                                        Net Amount
                                    </th>
                                    <th class="border-0 text-end">
                                        <i class="fa fa-percent me-2 text-secondary small"></i>
                                        Commission %
                                    </th>
                                    <th class="border-0 text-end">
                                        <i class="fa fa-dollar me-2 text-secondary small"></i>
                                        Commission
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($items as $item)
                                    <tr>
                                        <td>
                                            <span class="fw-medium">{{ $item->employee }}</span>
                                        </td>
                                        <td>
                                            <span class="text-secondary">{{ $item->product }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="badge bg-secondary rounded-pill">{{ currency($item->total_quantity,3) }}</span>
                                        </td>
                                        <td class="text-end">
                                            <strong>{{ currency($item->total_amount) }}</strong>
                                        </td>
                                        <td class="text-end">
                                            @if (($item->return_amount ?? 0) > 0)
                                                <strong class="text-danger">-{{ currency($item->return_amount) }}</strong>
                                            @else
                                                <span class="text-muted small">-</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <strong class="text-primary">{{ currency($item->net_amount ?? $item->total_amount - ($item->return_amount ?? 0)) }}</strong>
                                        </td>
                                        <td class="text-end">
                                            @if ($item->commission_percentage > 0)
                                                <span class="badge bg-info">{{ number_format($item->commission_percentage, 2) }}%</span>
                                            @else
                                                <span class="text-muted small">-</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if (($item->total_commission ?? 0) > 0)
                                                <strong class="text-success">{{ currency($item->total_commission) }}</strong>
                                            @else
                                                <span class="text-muted small">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-5">
                                            <div class="text-muted">
                                                <i class="fa fa-inbox fs-1 d-block mb-3 opacity-50"></i>
                                                <h6 class="mb-2">No Sales Found</h6>
                                                <p class="mb-0 small">Try adjusting your filters</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if ($items->count() > 0)
                                <tfoot class="table-light">
                                    <tr class="fw-bold">
                                        <td colspan="2" class="text-end">Total (All Pages):</td>
                                        <td class="text-end">
                                            <span class="badge bg-primary">{{ currency($totals->total_quantity ?? 0,3) }}</span>
                                        </td>
                                        <td class="text-end">{{ currency($totals->total_amount ?? 0) }}</td>
                                        <td class="text-end text-danger">-{{ currency($totals->return_amount ?? 0) }}</td>
                                        <td class="text-end text-primary">{{ currency($totals->net_amount ?? 0) }}</td>
                                        <td></td>
                                        <td class="text-end text-success">{{ currency($totals->total_commission ?? 0) }}</td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                    @if ($items->hasPages())
                        <div class="card-footer bg-light py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="text-muted small">
                                    Showing {{ $items->firstItem() ?? 0 }} to {{ $items->lastItem() ?? 0 }} of {{ $items->total() }} entries
                                </div>
                                <div>
                        {{ $items->links() }}
                    </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <!-- Sidebar: Chart and Summary -->
        <div class="col-md-4">
            <!-- Chart Card -->
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-light py-3">
                    <h5 class="mb-0 fw-semibold d-flex align-items-center">
                        <i class="fa fa-pie-chart me-2 text-primary"></i>
                        Sales Distribution
                    </h5>
                </div>
                <div class="card-body">
                    <div wire:ignore>
                        <div id="employeeChart" style="min-width:100%; width:100%; min-height:400px; height:calc(100vh - 400px); background: #f8f9fa; border-radius: 8px; padding: 15px;"></div>
                    </div>
                </div>
            </div>

            <!-- Summary Card -->
            <div class="card shadow-sm">
                <div class="card-header bg-light py-3">
                    <h5 class="mb-0 fw-semibold d-flex align-items-center">
                        <i class="fa fa-users me-2 text-success"></i>
                        Top Employees Summary
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="border-0">
                                        <i class="fa fa-user me-2 text-secondary small"></i>
                                        Employee
                                    </th>
                                    <th class="border-0 text-end">
                                        <i class="fa fa-hashtag me-2 text-secondary small"></i>
                                        Count
                                    </th>
                                    <th class="border-0 text-end">
                                        <i class="fa fa-money me-2 text-secondary small"></i>
                                        Sale Amount
                                    </th>
                                    <th class="border-0 text-end">
                                        <i class="fa fa-undo me-2 text-secondary small"></i>
                                        Return
                                    </th>
                                    <th class="border-0 text-end">
                                        <i class="fa fa-calculator me-2 text-secondary small"></i>
                                        Net Amount
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($summary as $item)
                                    <tr>
                                        <td>
                                            <span class="fw-medium">{{ $item->employee }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="badge bg-secondary rounded-pill">{{ number_format($item->total_quantity) }}</span>
                                        </td>
                                        <td class="text-end">
                                            <strong>{{ currency($item->total_amount) }}</strong>
                                        </td>
                                        <td class="text-end">
                                            @if (($item->return_amount ?? 0) > 0)
                                                <span class="text-danger small">-{{ currency($item->return_amount) }}</span>
                                            @else
                                                <span class="text-muted small">-</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <strong class="text-primary">{{ currency($item->net_amount ?? $item->total_amount - ($item->return_amount ?? 0)) }}</strong>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted small">
                                            No data available
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if ($summary->count() > 0)
                                <tfoot class="table-light">
                                    <tr class="fw-bold">
                                        <td>Total:</td>
                                        <td class="text-end">
                                            <span class="badge bg-primary">{{ number_format($summary->sum('total_quantity')) }}</span>
                                        </td>
                                        <td class="text-end">{{ currency($summary->sum('total_amount')) }}</td>
                                        <td class="text-end text-danger">-{{ currency($summary->sum('return_amount')) }}</td>
                                        <td class="text-end text-primary">{{ currency($summary->sum('net_amount')) }}</td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            $(document).ready(function() {
                // Sync TomSelect values with Livewire
                function syncTomSelect(selector, property) {
                    $(selector).on('change', function(e) {
                        const value = $(this).val() || null;
                        @this.set(property, value);
                    });
                }

                // Initialize TomSelect sync
                syncTomSelect('#branch_id', 'branch_id');
                syncTomSelect('#employee_id', 'employee_id');
                syncTomSelect('#product_id', 'product_id');

                // Initial chart render
                Livewire.dispatch('employeeReportFilterChanged');

                // Update pie chart when data changes
                window.addEventListener('updatePieChart', event => {
                    const chartData = event.detail[0]?.summary || [];

                    const options = {
                        title: {
                            text: "Employee Sales Distribution",
                            fontSize: 16,
                            fontFamily: "Helvetica Neue"
                        },
                        data: [{
                            type: "doughnut",
                            innerRadius: "50%",
                            showInLegend: true,
                            legendText: "{label}",
                            indexLabel: "{label}: {y}",
                            indexLabelFontSize: 12,
                            indexLabelFontFamily: "Helvetica Neue",
                            dataPoints: chartData
                        }]
                    };

                    $("#employeeChart").CanvasJSChart(options);
                });
            });
        </script>
    @endpush
</div>
