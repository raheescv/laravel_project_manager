<div>
    <div class="card-header bg-white p-4">
        <!-- Header Actions -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center gap-3">
                <div class="btn-group">
                    @can('expense.create')
                        <button class="btn btn-primary btn-sm d-inline-flex align-items-center gap-2" id="PageAdd">
                            <i class="demo-psi-add fs-5"></i>
                            Add New
                        </button>
                    @endcan
                    @can('expense.export')
                        <button class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-2" wire:click="export()">
                            <i class="demo-pli-file-excel fs-5"></i>
                            Export
                        </button>
                    @endcan
                    @can('expense.delete')
                        <button class="btn btn-outline-danger btn-sm d-inline-flex align-items-center gap-2" wire:click="delete()" wire:confirm="Are you sure you want to delete the selected items?">
                            <i class="demo-pli-recycling fs-5"></i>
                            Delete
                        </button>
                    @endcan
                </div>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <div class="input-group input-group-sm" style="width: 120px;">
                    <select wire:model.live="limit" class="form-select border-start-0">
                        <option value="10">10 rows</option>
                        <option value="100">100 rows</option>
                        <option value="500">500 rows</option>
                    </select>
                </div>
                <div class="input-group input-group-sm" style="width: 200px;">
                    <span class="input-group-text bg-light border-end-0">
                        <i class="fa fa-search"></i>
                    </span>
                    <input type="text" wire:model.live="filter.search" class="form-control border-start-0" placeholder="Search expenses..." autofocus>
                </div>
                @can('expense.import')
                    <button class="btn btn-outline-success btn-sm d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#ExpenseImportModal">
                        <i class="demo-pli-download-from-cloud fs-5"></i>
                        Import
                    </button>
                @endcan
            </div>
        </div>

        <!-- Filter Section -->
        <div class="bg-light rounded-3 border p-3 mt-3">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="form-label text-muted small fw-semibold mb-2">
                            <i class="fa fa-calendar me-1"></i> From Date
                        </label>
                        {{ html()->date('from_date')->value('')->class('form-control form-control-sm')->id('from_date')->attribute('wire:model.live', 'filter.from_date') }}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="form-label text-muted small fw-semibold mb-2">
                            <i class="fa fa-calendar me-1"></i> To Date
                        </label>
                        {{ html()->date('to_date')->value('')->class('form-control form-control-sm')->id('to_date')->attribute('wire:model.live', 'filter.to_date') }}
                    </div>
                </div>
                <div class="col-md-6" wire:ignore>
                    <div class="form-group">
                        <label class="form-label text-muted small fw-semibold mb-2">
                            <i class="fa fa-university me-1"></i> Account
                        </label>
                        {{ html()->select('account_id', [])->value('')->class('select-account_id')->attribute('account_type', 'expense')->id('account_id')->attribute('wire:model', 'filter.account_id')->placeholder('Select Account') }}
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="border-top bg-body-tertiary px-4 py-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <i class="fa fa-pie-chart text-primary"></i>
                <h6 class="mb-0 fw-semibold">Top {{ $chart['limit'] }} Expense Heads</h6>
                <span class="badge bg-secondary-subtle text-secondary-emphasis fw-normal">
                    {{ $chart['accounts'] }} {{ \Illuminate\Support\Str::plural('account', $chart['accounts']) }} in range
                </span>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="text-muted small">
                    Total <span class="fw-semibold text-body">{{ currency($chart['total']) }}</span>
                </span>
                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#expenseTopAccountsChart" aria-expanded="true" aria-controls="expenseTopAccountsChart">
                    <i class="fa fa-chevron-up"></i>
                </button>
            </div>
        </div>

        <div class="collapse show" id="expenseTopAccountsChart">
            @if (count($chart['slices']))
                <div class="row g-4 align-items-center mt-0">
                    <div class="col-lg-5">
                        <div wire:ignore style="position: relative; height: 300px;">
                            <canvas id="expenseTopAccountsCanvas"></canvas>
                        </div>
                    </div>
                    <div class="col-lg-7">
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr class="text-muted small">
                                        <th class="border-0 ps-0 fw-normal">Account Head</th>
                                        <th class="border-0 text-end fw-normal">Amount</th>
                                        <th class="border-0 text-end pe-0 fw-normal" style="width: 70px;">Share</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($chart['slices'] as $index => $slice)
                                        <tr>
                                            <td class="ps-0">
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="expense-chart-swatch rounded-1 flex-shrink-0" data-slot="{{ $index }}" @if ($slice['other']) data-other="1" @endif style="width: 10px; height: 10px; display: inline-block;"></span>
                                                    <span class="text-truncate" style="max-width: 320px;" title="{{ $slice['label'] }}">{{ $slice['label'] }}</span>
                                                </div>
                                            </td>
                                            <td class="text-end fw-medium" style="font-variant-numeric: tabular-nums;">{{ currency($slice['value']) }}</td>
                                            <td class="text-end pe-0 text-muted" style="font-variant-numeric: tabular-nums;">{{ number_format($slice['percent'], 1) }}%</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @else
                <div class="text-center text-muted py-4">
                    <i class="fa fa-pie-chart fs-3 d-block mb-2"></i>
                    <p class="mb-0">No expenses in the selected range</p>
                </div>
            @endif
        </div>

        <div id="expenseTopAccountsData" class="d-none" wire:key="expense-top-accounts-data" data-chart="{{ json_encode(['labels' => array_column($chart['slices'], 'label'), 'values' => array_column($chart['slices'], 'value'), 'others' => array_column($chart['slices'], 'other'), 'decimals' => currency_decimals()]) }}"></div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 border">
                <thead class="bg-light">
                    <tr class="text-capitalize">
                        <th class="border-bottom py-3">
                            <div class="d-flex align-items-center gap-2">
                                <div class="form-check mb-0">
                                    <input type="checkbox" class="form-check-input" wire:model.live="selectAll" id="selectAll">
                                    <label class="form-check-label" for="selectAll"></label>
                                </div>
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="journal_id" label="ID" />
                            </div>
                        </th>
                        <th class="border-bottom py-3">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa fa-calendar text-primary"></i>
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="date" label="Date" />
                            </div>
                        </th>
                        <th class="border-bottom py-3">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa fa-university text-primary"></i>
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="account_name" label="Account" />
                            </div>
                        </th>
                        <th class="border-bottom py-3">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa fa-user text-primary"></i>
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="person_name" label="Payee" />
                            </div>
                        </th>
                        <th class="border-bottom py-3">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa fa-tag text-primary"></i>
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="reference_number" label="Reference" />
                            </div>
                        </th>
                        <th class="border-bottom py-3">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa fa-align-left text-primary"></i>
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="description" label="Description" />
                            </div>
                        </th>
                        <th class="border-bottom py-3 text-end">
                            <div class="d-flex align-items-center justify-content-end gap-2">
                                <i class="fa fa-arrow-up text-danger"></i>
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="debit" label="Debit" />
                            </div>
                        </th>
                        <th class="border-bottom py-3 text-end">
                            <div class="d-flex align-items-center justify-content-end gap-2">
                                <i class="fa fa-arrow-down text-success"></i>
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="credit" label="Credit" />
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data as $item)
                        <tr>
                            <td class="align-middle">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="form-check mb-0">
                                        <input type="checkbox" class="form-check-input" value="{{ $item->journal_id }}" wire:model.live="selected">
                                    </div>
                                    <span class="text-muted">#{{ $item->journal_id }}</span>
                                </div>
                            </td>
                            <td class="align-middle">
                                <span class="badge bg-light text-dark">
                                    <i class="fa fa-calendar me-1"></i>
                                    {{ systemDate($item->date) }}
                                </span>
                            </td>
                            <td class="align-middle">
                                <a href="{{ route('account::view', $item->account_id) }}" class="text-decoration-none">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fa fa-university text-primary"></i>
                                        <span class="fw-medium">{{ $item->account_name }}</span>
                                    </div>
                                </a>
                            </td>
                            <td class="align-middle">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fa fa-user text-muted"></i>
                                    <span>{{ $item->person_name }}</span>
                                </div>
                            </td>
                            <td class="align-middle">
                                <code class="bg-light px-2 py-1 rounded">{{ $item->reference_number }}</code>
                            </td>
                            <td class="align-middle">
                                @switch($item->model)
                                    @case('Sale')
                                        <a href="{{ route('sale::view', $item->model_id) }}" class="text-decoration-none">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="fa fa-shopping-cart text-success"></i>
                                                <span>{{ $item->description }}</span>
                                            </div>
                                        </a>
                                    @break

                                    @case('SaleReturn')
                                        <a href="{{ route('sale_return::view', $item->model_id) }}" class="text-decoration-none">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="fa fa-undo text-warning"></i>
                                                <span>{{ $item->description }}</span>
                                            </div>
                                        </a>
                                    @break

                                    @default
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="fa fa-file-alt text-muted"></i>
                                            <span>{{ $item->description }}</span>
                                        </div>
                                @endswitch
                            </td>
                            <td class="text-end align-middle fw-medium text-danger">{{ currency($item->debit) }}</td>
                            <td class="text-end align-middle fw-medium text-success">{{ currency($item->credit) }}</td>
                        </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="fa fa-receipt fs-2 mb-2"></i>
                                    <p class="mb-0">No expenses found</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-group-divider bg-light">
                        <tr>
                            <th colspan="6" class="text-end py-3">
                                <span class="fw-bold">Total</span>
                            </th>
                            <th class="text-end py-3">
                                <span class="fw-bold text-danger">{{ currency($total['debit']) }}</span>
                            </th>
                            <th class="text-end py-3">
                                <span class="fw-bold text-success">{{ currency($total['credit']) }}</span>
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            {{ $data->links() }}
        </div>
    @push('scripts')
        <script src="{{ asset('assets/vendors/chart.js/chart.umd.min.js') }}"></script>
        <script src="{{ asset('assets/vendors/chart.js/chartjs-plugin-datalabels@2.min.js') }}"></script>
        <script>
            (function() {
                // Categorical palette: fixed slot order, validated for colour-vision deficiency
                // in both themes. The tail slice ("Other") is always neutral grey, never a slot.
                const PALETTE = {
                    light: ['#2a78d6', '#eb6834', '#1baf7a', '#eda100', '#e87ba4', '#008300', '#4a3aa7', '#e34948', '#0b8fa8', '#a0522d'],
                    dark: ['#3987e5', '#d95926', '#199e70', '#c98500', '#d55181', '#008300', '#9085e9', '#e66767', '#0f9ab5', '#b06636'],
                };
                const OTHER_COLOR = '#898781';
                const LABEL_MIN_PERCENT = 5;

                let chart = null;

                function isDark() {
                    return document.documentElement.getAttribute('data-bs-theme') === 'dark';
                }

                function cssVar(name, fallback) {
                    const value = getComputedStyle(document.body).getPropertyValue(name).trim();
                    return value || fallback;
                }

                function sliceColors(others) {
                    const palette = isDark() ? PALETTE.dark : PALETTE.light;
                    return others.map(function(isOther, index) {
                        return isOther ? OTHER_COLOR : palette[index % palette.length];
                    });
                }

                function readableInk(hex) {
                    const value = hex.replace('#', '');
                    const r = parseInt(value.substring(0, 2), 16);
                    const g = parseInt(value.substring(2, 4), 16);
                    const b = parseInt(value.substring(4, 6), 16);
                    const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
                    return luminance > 0.6 ? '#0b0b0b' : '#ffffff';
                }

                function readData() {
                    const holder = document.getElementById('expenseTopAccountsData');
                    if (!holder) return null;
                    try {
                        return JSON.parse(holder.dataset.chart || 'null');
                    } catch (error) {
                        return null;
                    }
                }

                function formatAmount(value, decimals) {
                    return Number(value).toLocaleString(undefined, {
                        minimumFractionDigits: decimals,
                        maximumFractionDigits: decimals,
                    });
                }

                function paintSwatches(colors) {
                    document.querySelectorAll('.expense-chart-swatch').forEach(function(node) {
                        const slot = parseInt(node.dataset.slot, 10);
                        node.style.backgroundColor = colors[slot] || OTHER_COLOR;
                    });
                }

                function destroyChart() {
                    if (chart) {
                        chart.destroy();
                        chart = null;
                    }
                }

                function buildConfig(data, colors) {
                    const decimals = data.decimals ?? 2;
                    const total = data.values.reduce(function(sum, value) {
                        return sum + Number(value);
                    }, 0);

                    return {
                        type: 'pie',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                data: data.values,
                                backgroundColor: colors,
                                borderColor: cssVar('--bs-tertiary-bg', '#f8f9fa'),
                                borderWidth: 2,
                                hoverOffset: 6,
                            }],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            layout: {
                                padding: 8
                            },
                            plugins: {
                                legend: {
                                    display: false
                                },
                                datalabels: {
                                    // Label selectively: a number on every slice is unreadable.
                                    display: function(context) {
                                        const value = Number(context.dataset.data[context.dataIndex]);
                                        return total > 0 && (value / total * 100) >= LABEL_MIN_PERCENT;
                                    },
                                    color: function(context) {
                                        return readableInk(colors[context.dataIndex] || OTHER_COLOR);
                                    },
                                    font: {
                                        size: 11,
                                        weight: 'bold'
                                    },
                                    formatter: function(value) {
                                        return total > 0 ? (value / total * 100).toFixed(1) + '%' : '';
                                    },
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            const value = Number(context.raw);
                                            const share = total > 0 ? (value / total * 100).toFixed(1) : '0.0';
                                            return ' ' + formatAmount(value, decimals) + ' (' + share + '%)';
                                        },
                                    },
                                },
                            },
                        },
                    };
                }

                function renderChart() {
                    const data = readData();
                    if (!data || !window.Chart) return;

                    const colors = sliceColors(data.others || []);
                    paintSwatches(colors);

                    const canvas = document.getElementById('expenseTopAccountsCanvas');
                    if (!canvas) {
                        destroyChart();
                        return;
                    }

                    if (!chart || chart.canvas !== canvas) {
                        destroyChart();
                        Chart.getChart(canvas)?.destroy();
                        chart = new Chart(canvas.getContext('2d'), buildConfig(data, colors));
                        return;
                    }

                    const config = buildConfig(data, colors);
                    chart.data = config.data;
                    chart.options = config.options;
                    chart.update();
                }

                function scheduleRender() {
                    window.requestAnimationFrame(renderChart);
                }

                document.addEventListener('DOMContentLoaded', function() {
                    if (window.Chart && window.ChartDataLabels) {
                        Chart.register(ChartDataLabels);
                    }
                    scheduleRender();

                    const panel = document.getElementById('expenseTopAccountsChart');
                    if (panel) {
                        panel.addEventListener('shown.bs.collapse', function() {
                            if (chart) chart.resize();
                        });
                        ['show.bs.collapse', 'hide.bs.collapse'].forEach(function(name) {
                            panel.addEventListener(name, function(event) {
                                const icon = document.querySelector('[data-bs-target="#expenseTopAccountsChart"] i');
                                if (!icon) return;
                                const open = event.type === 'show.bs.collapse';
                                icon.classList.toggle('fa-chevron-up', open);
                                icon.classList.toggle('fa-chevron-down', !open);
                            });
                        });
                    }
                });

                document.addEventListener('livewire:initialized', function() {
                    Livewire.hook('morph.updated', scheduleRender);
                    Livewire.hook('morph.removed', function() {
                        if (!document.getElementById('expenseTopAccountsCanvas')) {
                            destroyChart();
                        }
                    });
                });

                // Follow the light/dark switcher without a page reload.
                document.addEventListener('change.nf.colormode', scheduleRender);
            })();
        </script>
    @endpush

        @push('scripts')
            <script>
                $(document).ready(function() {
                    $(document).on('click', '.edit', function() {
                        Livewire.dispatch("Expense-Page-Update-Component", {
                            id: $(this).attr('table_id')
                        });
                    });
                    $('#account_id').on('change', function(e) {
                        const value = $(this).val() || null;
                        @this.set('filter.account_id', value);
                    });
                    $('#PageAdd').click(function() {
                        Livewire.dispatch("Expense-Page-Create-Component");
                    });
                    window.addEventListener('RefreshExpenseTable', event => {
                        Livewire.dispatch("Expense-Refresh-Component");
                    });
                });
            </script>
        @endpush
    </div>
