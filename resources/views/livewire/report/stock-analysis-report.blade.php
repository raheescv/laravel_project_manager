<div class="card shadow-sm">
    <!-- Report Type Switcher (sticky top) -->
    <div class="report-type-bar position-sticky top-0 bg-white border-bottom shadow-sm py-3 px-3 mb-3" style="z-index: 1020;">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted text-uppercase fw-bold small me-2"><i class="fa fa-layer-group me-1"></i>Report Type</span>
                <div class="btn-group shadow-sm" role="group" aria-label="Report Type">
                    <button type="button"
                        wire:click="$set('report_type', 'top_moving')"
                        class="btn {{ $report_type === 'top_moving' ? 'btn-danger' : 'btn-outline-danger' }} px-4 fw-bold">
                        <i class="fa fa-fire me-1"></i> Top Moving
                    </button>
                    <button type="button"
                        wire:click="$set('report_type', 'non_moving')"
                        x-on:click="window.stockAnalysisReport?.destroyChart?.()"
                        class="btn {{ $report_type === 'non_moving' ? 'btn-warning' : 'btn-outline-warning' }} px-4 fw-bold">
                        <i class="fa fa-box me-1"></i> Non-Moving
                    </button>
                </div>
            </div>
            <div class="form-check form-switch fs-6">
                <input class="form-check-input" type="checkbox" role="switch" id="groupByCodeSwitch" wire:model.live="group_by_code">
                <label class="form-check-label fw-bold" for="groupByCodeSwitch">
                    <i class="fa fa-object-group me-1 text-primary"></i>Group by Code
                </label>
            </div>
        </div>
    </div>
    <div class="card-body">
        <!-- Filters -->
        <div class="row mb-4">
            <div class="col-12 mb-3">
                <h6 class="text-muted text-uppercase"><i class="fa fa-filter me-2"></i>Filters</h6>
                <hr class="mt-2">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Branch</label>
                <select wire:model.live="branch_id" class="form-select shadow-sm">
                    <option value=""><i class="fa fa-building"></i> All Branches</option>
                    @foreach ($branches as $id => $name)
                        <option value="{{ $id }}">🏢 {{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Product Search</label>
                <div class="input-group shadow-sm">
                    <span class="input-group-text"><i class="fa fa-search"></i></span>
                    <input type="search" wire:model.live.debounce.400ms="product_search" class="form-control" placeholder="Size, SKU, barcode, name">
                </div>
            </div>
            <div class="col-md-3" wire:ignore>
                <label class="form-label fw-bold">Category</label>
                <div class="input-group shadow-sm">
                    <span class="input-group-text"><i class="fa fa-tags"></i></span>
                    {{ html()->select('main_category_id', [])->value('')->class('select-category_id-parent')->id('main_category_id')->attribute('style', 'width:80%')->placeholder('All Categories') }}
                </div>
            </div>
            <div class="col-md-3" wire:ignore>
                <label class="form-label fw-bold">Brand</label>
                <div class="input-group shadow-sm">
                    <span class="input-group-text"><i class="fa fa-copyright"></i></span>
                    {{ html()->select('brand_id', [])->value('')->class('select-brand_id-list')->id('brand_id')->attribute('style', 'width:80%')->placeholder('All Brands') }}
                </div>
            </div>
            <div class="row mb-4 p-2">
                @if ($report_type === 'top_moving')
                    <div class="col-md-2">
                        <label class="form-label fw-bold">From Date</label>
                        <div class="input-group shadow-sm">
                            <span class="input-group-text"><i class="fa fa-calendar"></i></span>
                            <input type="date" wire:model.live="from_date" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">To Date</label>
                        <div class="input-group shadow-sm">
                            <span class="input-group-text"><i class="fa fa-calendar"></i></span>
                            <input type="date" wire:model.live="to_date" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Top Items</label>
                        <div class="input-group shadow-sm">
                            <span class="input-group-text"><i class="fa fa-list-ol"></i></span>
                            <input type="number" wire:model.live="limit" class="form-control" min="5" max="20">
                        </div>
                    </div>
                @else
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Non-Moving Days Threshold</label>
                        <div class="input-group shadow-sm">
                            <span class="input-group-text"><i class="fa fa-clock"></i></span>
                            <input type="number" wire:model.live="days_threshold" class="form-control" min="1">
                            <span class="input-group-text bg-light">Days</span>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Chart Section for Top Moving Products -->
        @if ($report_type === 'top_moving' && $chartData)
            <div class="row mb-4"
                wire:key="stock-analysis-chart-{{ $report_type }}-{{ $branch_id ?: 'all' }}-{{ md5($product_search) }}-{{ $main_category_id ?: 'all' }}-{{ $sub_category_id ?: 'all' }}-{{ $brand_id ?: 'all' }}-{{ $from_date }}-{{ $to_date }}-{{ $limit }}-{{ $group_by_code ? 'g' : 'ng' }}">
                <div class="col-12 mb-3">
                    <h6 class="text-muted text-uppercase"><i class="fa fa-chart-pie me-2"></i>Distribution Chart</h6>
                    <hr class="mt-2">
                </div>
                <div class="col-md-8 mx-auto">
                    <div class="card shadow-sm border-0 bg-light">
                        <div class="card-body p-4">
                            <canvas id="productChart" data-chart='@json($chartData)' style="height: 400px;"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Data Table -->
        <div class="row mb-3">
            <div class="col-12">
                <h6 class="text-muted text-uppercase"><i class="fa fa-table me-2"></i>Detailed Data</h6>
                <hr class="mt-2">
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-sm table-striped align-middle">
                <thead class="table-light">
                    <tr>
                        @unless ($group_by_code)
                            <th class="border-0">Product Name</th>
                        @endunless
                        <th class="border-0">Code</th>
                        <th class="border-0">Main Category</th>
                        <th class="border-0">Brand</th>
                        <th class="border-0">Size</th>
                        @unless ($group_by_code)
                            <th class="border-0">Branch</th>
                        @endunless
                        @if ($report_type === 'non_moving')
                            <th class="text-end">Current Stock</th>
                            <th class="text-end">Stock Value</th>
                            <th>Last Movement</th>
                            <th class="text-end">Days Without Movement</th>
                        @else
                            <th class="text-end">Total Out</th>
                            <th class="text-end">Total In</th>
                            <th class="text-end">Net Movement</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($products as $product)
                        <tr>
                            @unless ($group_by_code)
                                <td> <a href="{{ route('inventory::product::view', $product->id) }}">{{ $product->name }}</a> </td>
                            @endunless
                            <td>{{ $product->code }}</td>
                            <td>{{ $product->main_category_name ?? $product->mainCategory?->name }}</td>
                            <td>{{ $product->brand_name ?? $product->brand?->name }}</td>
                            <td>{{ $product->size }}</td>
                            @unless ($group_by_code)
                                <td>
                                    {{ $product->branch_name }}
                                    @if ($product->branch_code ?? null)
                                        <span class="text-muted">({{ $product->branch_code }})</span>
                                    @endif
                                </td>
                            @endunless
                            @if ($report_type === 'non_moving')
                                <td class="text-end fw-bold">{{ number_format($product->quantity) }}</td>
                                <td class="text-end">
                                    <span class="text-success fw-bold">{{ number_format($product->stock_value, 2) }}</span>
                                </td>
                                <td>
                                    @if ($product->last_movement)
                                        <span class="badge bg-info">{{ systemDate($product->last_movement) }}</span>
                                    @else
                                        <span class="badge bg-warning">Never</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if ($product->last_movement)
                                        <span
                                            class="badge {{ Carbon\Carbon::parse($product->last_movement)->diffInDays(now()) > $days_threshold ? 'bg-danger' : 'bg-success' }}">
                                            {{ round(Carbon\Carbon::parse($product->last_movement)->diffInDays(now())) }} days
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">N/A</span>
                                    @endif
                                </td>
                            @else
                                <td class="text-end">
                                    <span class="text-primary fw-bold">{{ number_format($product->total_quantity_out) }}</span>
                                </td>
                                <td class="text-end">
                                    <span class="text-info fw-bold">{{ number_format($product->total_quantity_in) }}</span>
                                </td>
                                <td class="text-end">
                                    <span
                                        class="badge fs-6 {{ $product->total_quantity_out - $product->total_quantity_in > 0 ? 'bg-success' : 'bg-danger' }}">
                                        {{ number_format($product->total_quantity_out - $product->total_quantity_in, 2) }}
                                    </span>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($report_type === 'non_moving')
            <div class="mt-4 d-flex justify-content-center">
                {{ $products->links() }}
            </div>
        @endif
    </div>
    @push('scripts')
        <script>
            (function() {
                if (window.Chart && window.ChartDataLabels) {
                    Chart.register(ChartDataLabels);
                }

                window.stockAnalysisReport = window.stockAnalysisReport || {
                    chart: null,
                    listenersRegistered: false,
                    hookRegistered: false
                };

                const chartConfig = {
                    type: 'pie',
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: {
                            duration: 500
                        },
                        plugins: {
                            legend: {
                                position: 'right',
                                labels: {
                                    padding: 20,
                                    generateLabels: function(chart) {
                                        const data = chart.data;
                                        if (!data.datasets[0].data) return [];
                                        const total = data.datasets[0].data.reduce((a, b) => a + b, 0);
                                        return data.labels.map((label, i) => {
                                            const value = data.datasets[0].data[i];
                                            const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                            return {
                                                text: `${label} (${percentage}%)`,
                                                fillStyle: data.datasets[0].backgroundColor[i],
                                                hidden: isNaN(value),
                                                lineCap: 'round',
                                                lineDash: [],
                                                lineDashOffset: 0,
                                                lineJoin: 'round',
                                                lineWidth: 1,
                                                strokeStyle: '#fff',
                                                pointStyle: 'circle',
                                                rotation: 0
                                            };
                                        });
                                    }
                                }
                            },
                            title: {
                                display: true,
                                text: 'Top Moving Products Distribution',
                                font: {
                                    size: 16,
                                    weight: 'bold'
                                },
                                padding: {
                                    top: 10,
                                    bottom: 30
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const value = context.raw;
                                        const label = context.label;
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                        return `${label}: ${value.toLocaleString()} (${percentage}%)`;
                                    }
                                }
                            },
                            datalabels: {
                                color: '#fff',
                                textShadow: '0 0 3px #000',
                                font: {
                                    weight: 'bold',
                                    size: 14
                                },
                                formatter: function(value, context) {
                                    if (!value) return '';
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                    return percentage > 5 ? `${percentage}%` : '';
                                }
                            }
                        }
                    },
                    plugins: window.ChartDataLabels ? [ChartDataLabels] : []
                };

                function destroyChart() {
                    const canvas = document.getElementById('productChart');
                    const attachedChart = canvas && window.Chart ? Chart.getChart(canvas) : null;

                    if (attachedChart) {
                        attachedChart.destroy();
                    }

                    if (window.stockAnalysisReport.chart && window.stockAnalysisReport.chart !== attachedChart) {
                        window.stockAnalysisReport.chart.destroy();
                    }

                    window.stockAnalysisReport.chart = null;
                }

                function normalizeChartData(payload) {
                    return payload?.chartData || payload?.[0]?.chartData || payload?.[0] || payload;
                }

                function getCanvasChartData() {
                    const canvas = document.getElementById('productChart');
                    if (!canvas?.dataset.chart) return null;

                    try {
                        return JSON.parse(canvas.dataset.chart);
                    } catch (error) {
                        return null;
                    }
                }

                function updateChart(payload) {
                    const newData = normalizeChartData(payload) || getCanvasChartData();
                    if (!newData || !newData.datasets || !newData.datasets[0].data) return;
                    if (!window.Chart) return;

                    const canvas = document.getElementById('productChart');
                    if (!canvas) {
                        destroyChart();
                        return;
                    }

                    if (
                        !window.stockAnalysisReport.chart ||
                        window.stockAnalysisReport.chart.canvas !== canvas
                    ) {
                        destroyChart();
                        Chart.getChart(canvas)?.destroy();
                        window.stockAnalysisReport.chart = new Chart(canvas.getContext('2d'), {
                            ...chartConfig,
                            data: newData
                        });

                        return;
                    }

                    window.stockAnalysisReport.chart.data = newData;
                    window.stockAnalysisReport.chart.update('none');
                }

                function initChartIfNeeded() {
                    window.requestAnimationFrame(function() {
                        updateChart();
                    });
                }

                function registerListeners() {
                    if (window.stockAnalysisReport.listenersRegistered || !window.Livewire) return;

                    window.stockAnalysisReport.listenersRegistered = true;
                    Livewire.on('stock-analysis-chart-updated', updateChart);
                    Livewire.on('stock-analysis-chart-cleared', destroyChart);
                }

                function registerLivewireHook() {
                    if (window.stockAnalysisReport.hookRegistered || !window.Livewire) return;

                    window.stockAnalysisReport.hookRegistered = true;
                    Livewire.hook('morph.updated', function() {
                        initChartIfNeeded();
                    });
                    Livewire.hook('morph.removed', function() {
                        if (!document.getElementById('productChart')) {
                            destroyChart();
                        }
                    });
                }

                document.addEventListener('DOMContentLoaded', function() {
                    registerListeners();
                    registerLivewireHook();
                    initChartIfNeeded();
                });
                document.addEventListener('livewire:initialized', function() {
                    registerListeners();
                    registerLivewireHook();
                    initChartIfNeeded();
                });

                registerListeners();
                registerLivewireHook();
                window.stockAnalysisReport.destroyChart = destroyChart;
            })();
        </script>
        <script>
            $(document).ready(function() {
                $('#main_category_id').on('change', function() {
                    const value = $(this).val() || null;
                    @this.set('main_category_id', value);

                    const subCategory = document.querySelector('#sub_category_id')?.tomselect;
                    if (subCategory) {
                        subCategory.clear();
                        subCategory.clearOptions();
                    }

                    @this.set('sub_category_id', null);
                });

                $('#sub_category_id').on('change', function() {
                    const value = $(this).val() || null;
                    @this.set('sub_category_id', value);
                });

                $('#brand_id').on('change', function() {
                    const value = $(this).val() || null;
                    @this.set('brand_id', value);
                });
            });
        </script>
    @endpush
</div>
