<div class="monthly-sale-report-ui">
    @php
        $periodLabel = (\DateTime::createFromFormat('!m', $from_month)?->format('M') ?? $from_month) . ' ' . $from_year . ' - ' . (\DateTime::createFromFormat('!m', $to_month)?->format('M') ?? $to_month) . ' ' . $to_year;
        $monthsCount = count($data);
        $avgMonthlySale = $monthsCount > 0 ? (($total['net_sale'] ?? 0) / $monthsCount) : 0;
        $collectionRate = ($total['net_sale'] ?? 0) > 0 ? ((($total['paid_total'] ?? 0) / ($total['net_sale'] ?? 1)) * 100) : 0;
        $years = array_combine(range(date('Y'), date('Y') - 10), range(date('Y'), date('Y') - 10));
    @endphp

    <section class="monthly-filter-shell">
        <div class="monthly-filter-glow monthly-filter-glow-one"></div>
        <div class="monthly-filter-glow monthly-filter-glow-two"></div>
        <div class="monthly-filter-content">
            <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-start gap-3">
                <div class="monthly-filter-heading">
                    <span class="monthly-eyebrow"><i class="fa fa-line-chart"></i> Revenue intelligence</span>
                    <h2>Monthly sales performance</h2>
                    <p>Compare revenue, collections, credit, and payment channels across any period.</p>
                </div>
                <div class="monthly-period-pill ">
                    <span class="text-white">Showing period</span>
                    <strong class="text-white">{{ $periodLabel }}</strong>
                </div>
            </div>

            <div class="monthly-quick-ranges" aria-label="Quick date ranges">
                <span class="monthly-quick-label">Quick range</span>
                <button type="button" class="quick-range-btn quick-range-btn-active" data-range="this_month">This month</button>
                <button type="button" class="quick-range-btn" data-range="last_3_months">Last 3 months</button>
                <button type="button" class="quick-range-btn" data-range="this_year">This year</button>
            </div>

            <form wire:submit="fetchReport" class="monthly-filter-form">
                <div class="monthly-filter-grid">
                    <label class="monthly-field">
                        <span>From year</span>
                        <div class="monthly-control">
                            <i class="fa fa-calendar-o"></i>
                            {{ html()->select('filter_from_year', $years)->value($filter_from_year)->id('filter_from_year')->attribute('wire:model', 'filter_from_year') }}
                        </div>
                    </label>
                    <label class="monthly-field">
                        <span>From month</span>
                        <div class="monthly-control">
                            <i class="fa fa-calendar-check-o"></i>
                            {{ html()->select('filter_from_month', $months)->value($filter_from_month)->id('filter_from_month')->attribute('wire:model', 'filter_from_month') }}
                        </div>
                    </label>
                    <label class="monthly-field">
                        <span>To year</span>
                        <div class="monthly-control">
                            <i class="fa fa-calendar-o"></i>
                            {{ html()->select('filter_to_year', $years)->value($filter_to_year)->id('filter_to_year')->attribute('wire:model', 'filter_to_year') }}
                        </div>
                    </label>
                    <label class="monthly-field">
                        <span>To month</span>
                        <div class="monthly-control">
                            <i class="fa fa-calendar-check-o"></i>
                            {{ html()->select('filter_to_month', $months)->value($filter_to_month)->id('filter_to_month')->attribute('wire:model', 'filter_to_month') }}
                        </div>
                    </label>
                    <div class="monthly-field monthly-branch-field" wire:ignore>
                        <span>Branch</span>
                        <div class="monthly-control monthly-branch-control">
                            <i class="fa fa-building-o"></i>
                            {{ html()->select('filter_branch_id', [session('branch_id') => session('branch_name')])->value($filter_branch_id)->class('select-assigned-branch_id-list')->id('branch_id')->attribute('style', 'width:100%')->placeholder('All branches') }}
                        </div>
                    </div>
                    <button type="submit" class="monthly-submit-btn" data-testid="monthly-report-submit" wire:loading.attr="disabled" wire:target="fetchReport">
                        <span wire:loading.remove wire:target="fetchReport"><i class="fa fa-arrow-right"></i> View report</span>
                        <span wire:loading wire:target="fetchReport"><i class="fa fa-circle-o-notch fa-spin"></i> Fetching</span>
                    </button>
                </div>

                @error('filter_to_month')
                    <div class="monthly-filter-error"><i class="fa fa-exclamation-circle"></i> {{ $message }}</div>
                @enderror
            </form>
        </div>
    </section>

    <div class="row g-3 monthly-kpi-grid" wire:loading.class="monthly-results-loading" wire:target="fetchReport">
        <div class="col-6 col-lg-3">
            <div class="monthly-kpi-card monthly-kpi-indigo">
                <div class="monthly-kpi-icon"><i class="fa fa-line-chart"></i></div>
                <div>
                    <span>Net sales</span>
                    <strong>{{ currency($total['net_sale'] ?? 0) }}</strong>
                    <small>{{ $monthsCount }} month period</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="monthly-kpi-card monthly-kpi-emerald">
                <div class="monthly-kpi-icon"><i class="fa fa-check-circle-o"></i></div>
                <div>
                    <span>Total collected</span>
                    <strong>{{ currency($total['paid_total'] ?? 0) }}</strong>
                    <small>{{ number_format($collectionRate, 1) }}% collection rate</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="monthly-kpi-card monthly-kpi-amber">
                <div class="monthly-kpi-icon"><i class="fa fa-clock-o"></i></div>
                <div>
                    <span>Credit exposure</span>
                    <strong>{{ currency($total['credit'] ?? 0) }}</strong>
                    <small>Outstanding balance</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="monthly-kpi-card monthly-kpi-cyan">
                <div class="monthly-kpi-icon"><i class="fa fa-bar-chart"></i></div>
                <div>
                    <span>Monthly average</span>
                    <strong>{{ currency($avgMonthlySale) }}</strong>
                    <small>{{ currency($total['discount'] ?? 0) }} discounts</small>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 monthly-chart-card" wire:loading.class="monthly-results-loading" wire:target="fetchReport">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-start gap-3 monthly-chart-head">
            <div>
                <span class="monthly-section-kicker">Performance overview</span>
                <h5>Sales trend &amp; payment mix</h5>
                <p>Monthly revenue movement with collection-channel detail.</p>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-3 monthly-chart-legend">
                <span class="monthly-legend-item"><span class="monthly-legend-swatch monthly-legend-bar-gross"></span>Gross Sales</span>
                <span class="monthly-legend-item"><span class="monthly-legend-swatch monthly-legend-bar-net"></span>Net Sale</span>
                <span class="monthly-legend-item"><span class="monthly-legend-swatch monthly-legend-line-paid"></span>Paid</span>
                <span class="monthly-legend-item"><span class="monthly-legend-swatch monthly-legend-line-credit"></span>Credit</span>
                <span class="monthly-legend-item"><span class="monthly-legend-swatch monthly-legend-line-cash"></span>Cash</span>
                <span class="monthly-legend-item"><span class="monthly-legend-swatch monthly-legend-line-card"></span>Card</span>
                <span class="monthly-legend-item"><span class="monthly-legend-swatch monthly-legend-line-discount"></span>Discount</span>
            </div>
        </div>
        <div class="card-body monthly-chart-body">
            @if (count($data))
                <div class="monthly-chart-frame">
                    <canvas id="monthlySaleChart" data-chart='@json($chartData)'></canvas>
                </div>
            @else
                <div class="text-center py-5 text-muted">
                    <i class="fa fa-bar-chart fa-2x mb-2 d-block"></i>
                    No data available to plot for the selected period
                </div>
            @endif
        </div>
    </div>

    <div class="card border-0 monthly-table-card" wire:loading.class="monthly-results-loading" wire:target="fetchReport">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-3 monthly-table-title">
            <div>
                <span class="monthly-section-kicker">Detailed ledger</span>
                <h5>Monthly breakdown</h5>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="monthly-row-count"><i class="fa fa-calendar"></i> {{ $monthsCount }} months</span>
                @can('product.export')
                    <button type="button" class="monthly-export-btn" wire:click="exportExcel" wire:loading.attr="disabled" wire:target="exportExcel">
                        <i class="demo-pli-file-excel"></i> Export
                    </button>
                @endcan
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive monthly-table-wrap">
                <table class="table align-middle mb-0 monthly-data-table">
                    <thead class="text-nowrap">
                        <tr class="text-capitalize">
                            <th class="border-bottom px-3 py-3">Month</th>
                            <th class="border-bottom px-3 py-3 text-end">Gross Sales</th>
                            <th class="border-bottom px-3 py-3 text-end">Discount</th>
                            <th class="border-bottom px-3 py-3 text-end">Net Sale</th>
                            <th class="border-bottom px-3 py-3 text-end">Paid (Total)</th>
                            <th class="border-bottom px-3 py-3 text-end">Credit</th>
                            <th class="border-bottom px-3 py-3 text-end">Card</th>
                            <th class="border-bottom px-3 py-3 text-end">Cash</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data as $item)
                            <tr wire:key="monthly-sale-{{ $item['month'] }}">
                                <td class="px-3 py-3"><span class="monthly-month-cell"><i class="fa fa-calendar-o"></i>{{ $item['month_name'] }}</span></td>
                                <td class="px-3 py-3 text-end monthly-money">{{ currency($item['gross_sales']) }}</td>
                                <td class="px-3 py-3 text-end"><span class="monthly-negative">{{ currency($item['discount']) }}</span></td>
                                <td class="px-3 py-3 text-end"><span class="monthly-net">{{ currency($item['net_sale']) }}</span></td>
                                <td class="px-3 py-3 text-end"><span class="monthly-paid">{{ currency($item['paid_total']) }}</span></td>
                                <td class="px-3 py-3 text-end"><span class="monthly-credit">{{ currency($item['credit']) }}</span></td>
                                <td class="px-3 py-3 text-end"><span class="monthly-channel monthly-channel-card">{{ currency($item['card']) }}</span></td>
                                <td class="px-3 py-3 text-end"><span class="monthly-channel monthly-channel-cash">{{ currency($item['cash']) }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="fa fa-inbox fa-2x mb-2 d-block"></i>
                                    No data available for the selected period
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <th class="px-3 py-3">Total</th>
                            <th class="px-3 py-3 text-end fw-semibold">{{ currency($total['gross_sales'] ?? 0) }}</th>
                            <th class="px-3 py-3 text-end text-danger fw-semibold">{{ currency($total['discount'] ?? 0) }}</th>
                            <th class="px-3 py-3 text-end fw-semibold text-primary">{{ currency($total['net_sale'] ?? 0) }}</th>
                            <th class="px-3 py-3 text-end fw-semibold text-success">{{ currency($total['paid_total'] ?? 0) }}</th>
                            <th class="px-3 py-3 text-end fw-semibold text-warning">{{ currency($total['credit'] ?? 0) }}</th>
                            <th class="px-3 py-3 text-end fw-semibold text-info">{{ currency($total['card'] ?? 0) }}</th>
                            <th class="px-3 py-3 text-end fw-semibold text-success">{{ currency($total['cash'] ?? 0) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            $(document).ready(function() {
                $('#branch_id').on('change', function() {
                    const value = $(this).val() || null;
                    @this.set('filter_branch_id', value, false);
                });

                $(document).on('click', '.quick-range-btn', function() {
                    $('.quick-range-btn').removeClass('quick-range-btn-active');
                    $(this).addClass('quick-range-btn-active');

                    const range = $(this).data('range');
                    const now = new Date();
                    const currentYear = now.getFullYear();
                    const currentMonth = String(now.getMonth() + 1).padStart(2, '0');

                    function setFilterValue(id, property, value) {
                        document.getElementById(id).value = value;
                        @this.set(property, value, false);
                    }

                    if (range === 'this_month') {
                        setFilterValue('filter_from_year', 'filter_from_year', String(currentYear));
                        setFilterValue('filter_to_year', 'filter_to_year', String(currentYear));
                        setFilterValue('filter_from_month', 'filter_from_month', currentMonth);
                        setFilterValue('filter_to_month', 'filter_to_month', currentMonth);
                        return;
                    }

                    if (range === 'last_3_months') {
                        const start = new Date(currentYear, now.getMonth() - 2, 1);
                        const startYear = String(start.getFullYear());
                        const startMonth = String(start.getMonth() + 1).padStart(2, '0');
                        setFilterValue('filter_from_year', 'filter_from_year', startYear);
                        setFilterValue('filter_to_year', 'filter_to_year', String(currentYear));
                        setFilterValue('filter_from_month', 'filter_from_month', startMonth);
                        setFilterValue('filter_to_month', 'filter_to_month', currentMonth);
                        return;
                    }

                    if (range === 'this_year') {
                        setFilterValue('filter_from_year', 'filter_from_year', String(currentYear));
                        setFilterValue('filter_to_year', 'filter_to_year', String(currentYear));
                        setFilterValue('filter_from_month', 'filter_from_month', '01');
                        setFilterValue('filter_to_month', 'filter_to_month', '12');
                    }
                });
            });
        </script>

        <script>
            (function() {
                window.monthlySaleReport = window.monthlySaleReport || {
                    chart: null,
                    listenersRegistered: false,
                    hookRegistered: false,
                };

                const CANVAS_ID = 'monthlySaleChart';
                const fmt = new Intl.NumberFormat('en-US', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0,
                });
                const fmtCompact = new Intl.NumberFormat('en-US', {
                    notation: 'compact',
                    maximumFractionDigits: 1,
                });
                const barValueLabelsPlugin = {
                    id: 'monthlyBarValueLabels',
                    afterDatasetsDraw: function(chart) {
                        const labelAngle = chart.width < 640 || chart.data.labels.length > 6 ? -60 : -35;

                        chart.data.datasets.forEach(function(dataset, datasetIndex) {
                            if (dataset.type !== 'bar' || !chart.isDatasetVisible(datasetIndex)) return;

                            const meta = chart.getDatasetMeta(datasetIndex);
                            meta.data.forEach(function(bar, dataIndex) {
                                const value = Number(dataset.data[dataIndex] || 0);
                                if (value === 0) return;

                                chart.ctx.save();
                                chart.ctx.translate(bar.x, bar.y - 8);
                                chart.ctx.rotate(labelAngle * Math.PI / 180);
                                chart.ctx.fillStyle = dataset.label === 'Net Sale' ? '#4338ca' : '#64748b';
                                chart.ctx.font = '700 ' + (chart.width < 640 ? 9 : 10) + "px 'Inter', system-ui, sans-serif";
                                chart.ctx.textAlign = 'left';
                                chart.ctx.textBaseline = 'bottom';
                                chart.ctx.fillText(fmtCompact.format(value), 0, 0);
                                chart.ctx.restore();
                            });
                        });
                    }
                };

                function getCanvasChartData() {
                    const canvas = document.getElementById(CANVAS_ID);
                    if (!canvas || !canvas.dataset.chart) return null;
                    try {
                        return JSON.parse(canvas.dataset.chart);
                    } catch (e) {
                        return null;
                    }
                }

                function makeGradient(ctx, area, from, to) {
                    const g = ctx.createLinearGradient(0, area.top, 0, area.bottom);
                    g.addColorStop(0, from);
                    g.addColorStop(1, to);
                    return g;
                }

                function buildDatasets(data, chartArea, ctx) {
                    const netGradient = chartArea ?
                        makeGradient(ctx, chartArea, 'rgba(79, 70, 229, 0.95)', 'rgba(79, 70, 229, 0.55)') :
                        'rgba(79, 70, 229, 0.85)';
                    const grossGradient = chartArea ?
                        makeGradient(ctx, chartArea, 'rgba(148, 163, 184, 0.55)', 'rgba(148, 163, 184, 0.25)') :
                        'rgba(148, 163, 184, 0.4)';

                    function line(label, values, color, dash) {
                        return {
                            type: 'line',
                            label: label,
                            data: values,
                            borderColor: color,
                            backgroundColor: color,
                            borderWidth: 2.5,
                            borderDash: dash || [],
                            tension: 0.4,
                            pointRadius: 2.5,
                            pointHoverRadius: 6,
                            pointBackgroundColor: '#ffffff',
                            pointBorderColor: color,
                            pointBorderWidth: 2,
                            yAxisID: 'y',
                        };
                    }

                    return [{
                            type: 'bar',
                            label: 'Gross Sales',
                            data: data.gross_sales,
                            backgroundColor: grossGradient,
                            borderRadius: {
                                topLeft: 6,
                                topRight: 6
                            },
                            borderSkipped: false,
                            categoryPercentage: 0.7,
                            barPercentage: 0.9,
                            order: 9,
                            yAxisID: 'y',
                        },
                        {
                            type: 'bar',
                            label: 'Net Sale',
                            data: data.net_sale,
                            backgroundColor: netGradient,
                            borderRadius: {
                                topLeft: 6,
                                topRight: 6
                            },
                            borderSkipped: false,
                            categoryPercentage: 0.7,
                            barPercentage: 0.9,
                            order: 8,
                            yAxisID: 'y',
                        },
                        Object.assign(line('Paid', data.paid_total, '#059669'), {
                            order: 1
                        }),
                        Object.assign(line('Credit', data.credit, '#f59e0b', [5, 4]), {
                            order: 2
                        }),
                        Object.assign(line('Cash', data.cash, '#8b5cf6'), {
                            order: 3
                        }),
                        Object.assign(line('Card', data.card, '#0ea5e9'), {
                            order: 4
                        }),
                        Object.assign(line('Discount', data.discount, '#ef4444', [3, 3]), {
                            order: 5
                        }),
                    ];
                }

                function buildConfig(data) {
                    return {
                        data: {
                            labels: data.labels,
                            datasets: buildDatasets(data, null, null),
                        },
                        plugins: [barValueLabelsPlugin],
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: {
                                intersect: false,
                                mode: 'index',
                            },
                            layout: {
                                padding: {
                                    top: 28,
                                    right: 8,
                                    bottom: 0,
                                    left: 0
                                }
                            },
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    backgroundColor: 'rgba(255, 255, 255, 0.98)',
                                    titleColor: '#0f172a',
                                    bodyColor: '#334155',
                                    borderColor: '#e2e8f0',
                                    borderWidth: 1,
                                    padding: {
                                        top: 10,
                                        right: 14,
                                        bottom: 10,
                                        left: 14
                                    },
                                    cornerRadius: 10,
                                    usePointStyle: true,
                                    boxPadding: 6,
                                    titleFont: {
                                        size: 13,
                                        weight: '600',
                                        family: "'Inter', system-ui, sans-serif"
                                    },
                                    bodyFont: {
                                        size: 12.5,
                                        weight: '500',
                                        family: "'Inter', system-ui, sans-serif"
                                    },
                                    callbacks: {
                                        label: function(context) {
                                            return ' ' + context.dataset.label + ': ' + fmt.format(context.parsed.y || 0);
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    grace: '12%',
                                    border: {
                                        display: false
                                    },
                                    grid: {
                                        color: 'rgba(148, 163, 184, 0.16)',
                                        drawTicks: false,
                                    },
                                    ticks: {
                                        padding: 10,
                                        color: '#64748b',
                                        maxTicksLimit: 6,
                                        font: {
                                            size: 11,
                                            weight: '500',
                                            family: "'Inter', system-ui, sans-serif"
                                        },
                                        callback: function(value) {
                                            return fmtCompact.format(value);
                                        }
                                    }
                                },
                                x: {
                                    border: {
                                        display: false
                                    },
                                    grid: {
                                        display: false
                                    },
                                    ticks: {
                                        color: '#64748b',
                                        padding: 8,
                                        maxRotation: 0,
                                        autoSkip: true,
                                        maxTicksLimit: 12,
                                        font: {
                                            size: 11,
                                            weight: '500',
                                            family: "'Inter', system-ui, sans-serif"
                                        }
                                    }
                                }
                            }
                        }
                    };
                }

                function destroyChart() {
                    const canvas = document.getElementById(CANVAS_ID);
                    const attached = canvas && window.Chart ? Chart.getChart(canvas) : null;
                    if (attached) attached.destroy();
                    if (window.monthlySaleReport.chart && window.monthlySaleReport.chart !== attached) {
                        window.monthlySaleReport.chart.destroy();
                    }
                    window.monthlySaleReport.chart = null;
                }

                function renderChart() {
                    if (!window.Chart) return;
                    const canvas = document.getElementById(CANVAS_ID);
                    if (!canvas) {
                        destroyChart();
                        return;
                    }
                    const data = getCanvasChartData();
                    if (!data || !Array.isArray(data.labels)) {
                        destroyChart();
                        return;
                    }

                    destroyChart();
                    Chart.getChart(canvas)?.destroy();
                    const chart = new Chart(canvas.getContext('2d'), buildConfig(data));
                    if (chart.chartArea) {
                        chart.data.datasets = buildDatasets(data, chart.chartArea, chart.ctx);
                        chart.update('none');
                    }
                    window.monthlySaleReport.chart = chart;
                    observeResize(canvas.parentElement);
                    // The canvas often mounts before its container has its final width
                    // (grid/flex layout, Livewire morph). Force a resize once layout settles.
                    forceResize(chart);
                }

                function forceResize(chart) {
                    [0, 60, 200].forEach(function(delay) {
                        window.setTimeout(function() {
                            if (chart && chart.canvas && chart.canvas.isConnected) {
                                chart.resize();
                            }
                        }, delay);
                    });
                    window.requestAnimationFrame(function() {
                        if (chart && chart.canvas && chart.canvas.isConnected) {
                            chart.resize();
                        }
                    });
                }

                function observeResize(frame) {
                    if (!frame || !window.ResizeObserver || window.monthlySaleReport.resizeObserver) return;
                    const ro = new ResizeObserver(function() {
                        const chart = window.monthlySaleReport.chart;
                        if (chart && chart.canvas && chart.canvas.isConnected) {
                            chart.resize();
                        }
                    });
                    ro.observe(frame);
                    window.monthlySaleReport.resizeObserver = ro;
                }

                function scheduleRender() {
                    window.requestAnimationFrame(renderChart);
                }

                function registerHooks() {
                    if (window.monthlySaleReport.hookRegistered || !window.Livewire) return;
                    window.monthlySaleReport.hookRegistered = true;
                    Livewire.hook('morph.updated', scheduleRender);
                    Livewire.hook('morph.removed', function() {
                        if (!document.getElementById(CANVAS_ID)) destroyChart();
                    });
                }

                window.addEventListener('resize', function() {
                    const chart = window.monthlySaleReport.chart;
                    if (chart && chart.canvas && chart.canvas.isConnected) {
                        chart.resize();
                    }
                });

                document.addEventListener('DOMContentLoaded', function() {
                    registerHooks();
                    scheduleRender();
                });
                document.addEventListener('livewire:initialized', function() {
                    registerHooks();
                    scheduleRender();
                });

                registerHooks();
                scheduleRender();
            })();
        </script>
    @endpush

    @push('styles')
        <style>
            .monthly-sale-report-ui {
                --monthly-ink: #172033;
                --monthly-muted: #6b7280;
                --monthly-border: #e7eaf0;
                color: var(--monthly-ink);
            }

            .monthly-sale-report-ui .monthly-filter-shell {
                position: relative;
                overflow: hidden;
                border-radius: 22px;
                background: linear-gradient(135deg, #111a2e 0%, #182440 58%, #132a34 100%);
                box-shadow: 0 20px 50px rgba(18, 26, 47, .18);
            }

            .monthly-sale-report-ui .monthly-filter-glow {
                position: absolute;
                border-radius: 999px;
                pointer-events: none;
                filter: blur(2px);
            }

            .monthly-sale-report-ui .monthly-filter-glow-one {
                width: 320px;
                height: 320px;
                top: -230px;
                right: 8%;
                background: rgba(99, 102, 241, .28);
            }

            .monthly-sale-report-ui .monthly-filter-glow-two {
                width: 220px;
                height: 220px;
                right: -120px;
                bottom: -150px;
                background: rgba(16, 185, 129, .18);
            }

            .monthly-sale-report-ui .monthly-filter-content {
                position: relative;
                z-index: 1;
                padding: 28px;
            }

            .monthly-sale-report-ui .monthly-eyebrow,
            .monthly-sale-report-ui .monthly-section-kicker {
                display: inline-flex;
                align-items: center;
                gap: 7px;
                color: #818cf8;
                font-size: .69rem;
                font-weight: 800;
                letter-spacing: .13em;
                text-transform: uppercase;
            }

            .monthly-sale-report-ui .monthly-filter-heading h2 {
                margin: 8px 0 5px;
                color: #fff;
                font-size: clamp(1.35rem, 2.3vw, 1.9rem);
                font-weight: 750;
                letter-spacing: -.035em;
            }

            .monthly-sale-report-ui .monthly-filter-heading p,
            .monthly-sale-report-ui .monthly-chart-head p {
                margin: 0;
                color: #9faac0;
                font-size: .84rem;
            }

            .monthly-sale-report-ui .monthly-period-pill {
                display: grid;
                gap: 3px;
                min-width: 225px;
                padding: 11px 15px;
                border: 1px solid rgba(255, 255, 255, .1);
                border-radius: 12px;
                background: rgba(255, 255, 255, .06);
                color: #fff;
                backdrop-filter: blur(12px);
            }

            .monthly-sale-report-ui .monthly-period-pill span {
                color: #8f9ab0;
                font-size: .65rem;
                font-weight: 700;
                letter-spacing: .09em;
                text-transform: uppercase;
            }

            .monthly-sale-report-ui .monthly-period-pill strong {
                font-size: .85rem;
            }

            .monthly-sale-report-ui .monthly-quick-ranges {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 7px;
                margin-top: 22px;
                padding-bottom: 14px;
                border-bottom: 1px solid rgba(255, 255, 255, .09);
            }

            .monthly-sale-report-ui .monthly-quick-label {
                margin-right: 3px;
                color: #78849b;
                font-size: .69rem;
                font-weight: 700;
                text-transform: uppercase;
            }

            .monthly-sale-report-ui .quick-range-btn {
                padding: 6px 11px;
                border: 1px solid transparent;
                border-radius: 8px;
                background: transparent;
                color: #abb5c8;
                font-size: .76rem;
                font-weight: 650;
                transition: .2s ease;
            }

            .monthly-sale-report-ui .quick-range-btn:hover,
            .monthly-sale-report-ui .quick-range-btn.quick-range-btn-active {
                border-color: rgba(255, 255, 255, .12);
                background: rgba(255, 255, 255, .1);
                color: #fff;
            }

            .monthly-sale-report-ui .monthly-filter-form {
                margin-top: 16px;
            }

            .monthly-sale-report-ui .monthly-filter-grid {
                display: grid;
                grid-template-columns: repeat(4, minmax(110px, .72fr)) minmax(210px, 1.35fr) minmax(145px, .75fr);
                gap: 10px;
                align-items: end;
            }

            .monthly-sale-report-ui .monthly-field {
                display: grid;
                gap: 7px;
                min-width: 0;
            }

            .monthly-sale-report-ui .monthly-field > span {
                color: #aab4c6;
                font-size: .69rem;
                font-weight: 700;
                letter-spacing: .035em;
            }

            .monthly-sale-report-ui .monthly-control {
                display: flex;
                align-items: center;
                min-height: 45px;
                padding: 0 11px;
                border: 1px solid rgba(255, 255, 255, .12);
                border-radius: 11px;
                background: rgba(255, 255, 255, .075);
                transition: border-color .2s, background .2s, box-shadow .2s;
            }

            .monthly-sale-report-ui .monthly-control:focus-within {
                border-color: #818cf8;
                background: rgba(255, 255, 255, .1);
                box-shadow: 0 0 0 3px rgba(99, 102, 241, .15);
            }

            .monthly-sale-report-ui .monthly-control > i {
                flex: 0 0 18px;
                color: #818cf8;
            }

            .monthly-sale-report-ui .monthly-control select {
                width: 100%;
                min-width: 0;
                border: 0 !important;
                outline: 0;
                background: transparent;
                box-shadow: none !important;
                color: #f8fafc;
                font-size: .82rem;
                font-weight: 650;
            }

            .monthly-sale-report-ui .monthly-branch-control .ts-wrapper {
                flex: 1 1 auto;
                min-width: 0;
            }

            .monthly-sale-report-ui .monthly-branch-control .ts-wrapper .ts-control,
            .monthly-sale-report-ui .monthly-branch-control .ts-wrapper.input-active .ts-control {
                min-height: auto;
                padding: 0 3px !important;
                border: 0 !important;
                background: transparent !important;
                box-shadow: none !important;
                color: #f8fafc !important;
            }

            .monthly-sale-report-ui .monthly-branch-control .ts-control input,
            .monthly-sale-report-ui .monthly-branch-control .ts-control .item {
                border: 0 !important;
                background: transparent !important;
                box-shadow: none !important;
                color: #f8fafc !important;
                -webkit-text-fill-color: #f8fafc;
                font-size: .82rem;
                font-weight: 650;
            }

            .monthly-sale-report-ui .monthly-branch-control .ts-control .item {
                padding: 0 !important;
            }

            .monthly-sale-report-ui .monthly-branch-control .ts-control input::placeholder {
                color: #aab4c6 !important;
                -webkit-text-fill-color: #aab4c6;
                opacity: 1;
            }

            .monthly-sale-report-ui .monthly-submit-btn {
                min-height: 45px;
                border: 0;
                border-radius: 11px;
                background: linear-gradient(135deg, #6366f1, #4f46e5);
                box-shadow: 0 9px 22px rgba(79, 70, 229, .35);
                color: #fff;
                font-size: .82rem;
                font-weight: 750;
                transition: transform .2s, box-shadow .2s;
            }

            .monthly-sale-report-ui .monthly-submit-btn:hover {
                transform: translateY(-1px);
                box-shadow: 0 12px 26px rgba(79, 70, 229, .45);
            }

            .monthly-sale-report-ui .monthly-submit-btn i {
                margin-right: 6px;
            }

            .monthly-sale-report-ui .monthly-filter-error {
                margin-top: 10px;
                color: #fecaca;
                font-size: .76rem;
            }

            .monthly-sale-report-ui .monthly-kpi-grid {
                margin-top: 2px;
            }

            .monthly-sale-report-ui .monthly-kpi-card {
                display: flex;
                align-items: center;
                gap: 14px;
                height: 100%;
                min-height: 116px;
                padding: 18px;
                border: 1px solid var(--monthly-border);
                border-radius: 16px;
                background: #fff;
                box-shadow: 0 8px 25px rgba(24, 32, 51, .055);
            }

            .monthly-sale-report-ui .monthly-kpi-icon {
                display: grid;
                flex: 0 0 42px;
                width: 42px;
                height: 42px;
                place-items: center;
                border-radius: 12px;
                font-size: 1rem;
            }

            .monthly-sale-report-ui .monthly-kpi-card > div:last-child {
                display: grid;
                min-width: 0;
                gap: 3px;
            }

            .monthly-sale-report-ui .monthly-kpi-card span,
            .monthly-sale-report-ui .monthly-kpi-card small {
                color: #7b8496;
                font-size: .72rem;
            }

            .monthly-sale-report-ui .monthly-kpi-card strong {
                overflow: hidden;
                color: #172033;
                font-size: clamp(1rem, 1.7vw, 1.3rem);
                font-weight: 760;
                letter-spacing: -.025em;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .monthly-sale-report-ui .monthly-kpi-indigo .monthly-kpi-icon { background: #eef2ff; color: #4f46e5; }
            .monthly-sale-report-ui .monthly-kpi-emerald .monthly-kpi-icon { background: #ecfdf5; color: #059669; }
            .monthly-sale-report-ui .monthly-kpi-amber .monthly-kpi-icon { background: #fffbeb; color: #d97706; }
            .monthly-sale-report-ui .monthly-kpi-cyan .monthly-kpi-icon { background: #ecfeff; color: #0891b2; }

            .monthly-sale-report-ui .monthly-chart-card {
                margin-top: 18px;
                border: 1px solid var(--monthly-border) !important;
                border-radius: 18px;
                overflow: hidden;
                box-shadow: 0 10px 30px rgba(24, 32, 51, .055);
            }

            .monthly-sale-report-ui .monthly-chart-head {
                padding: 20px 22px;
                border-bottom: 1px solid #eef0f4;
                background: #fff;
            }

            .monthly-sale-report-ui .monthly-chart-head h5,
            .monthly-sale-report-ui .monthly-table-title h5 {
                margin: 4px 0 2px;
                color: #1c2538;
                font-size: .98rem;
                font-weight: 760;
            }

            .monthly-sale-report-ui .monthly-chart-body {
                padding: 12px 18px 18px;
            }

            .monthly-sale-report-ui .monthly-chart-frame {
                position: relative;
                width: 100%;
                height: 340px;
            }

            @media (max-width: 575.98px) {
                .monthly-sale-report-ui .monthly-chart-frame {
                    height: 280px;
                }
            }

            .monthly-sale-report-ui .monthly-chart-legend {
                max-width: 520px;
                font-size: 0.72rem;
                color: #64748b;
            }

            .monthly-sale-report-ui .monthly-legend-item {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                font-weight: 500;
            }

            .monthly-sale-report-ui .monthly-legend-swatch {
                display: inline-block;
                width: 14px;
                height: 10px;
                border-radius: 3px;
            }

            .monthly-sale-report-ui .monthly-legend-bar-net {
                background: #4f46e5;
            }

            .monthly-sale-report-ui .monthly-legend-bar-gross {
                background: #94a3b8;
            }

            .monthly-sale-report-ui .monthly-legend-line-paid {
                width: 16px;
                height: 3px;
                border-radius: 2px;
                background: #059669;
            }

            .monthly-sale-report-ui .monthly-legend-line-credit {
                width: 16px;
                height: 3px;
                border-radius: 2px;
                background: repeating-linear-gradient(to right, #f59e0b 0, #f59e0b 5px, transparent 5px, transparent 9px);
            }

            .monthly-sale-report-ui .monthly-legend-line-cash {
                width: 16px;
                height: 3px;
                border-radius: 2px;
                background: #8b5cf6;
            }

            .monthly-sale-report-ui .monthly-legend-line-card {
                width: 16px;
                height: 3px;
                border-radius: 2px;
                background: #0ea5e9;
            }

            .monthly-sale-report-ui .monthly-legend-line-discount {
                width: 16px;
                height: 3px;
                border-radius: 2px;
                background: repeating-linear-gradient(to right, #ef4444 0, #ef4444 3px, transparent 3px, transparent 6px);
            }

            .monthly-sale-report-ui .monthly-table-card {
                margin-top: 18px;
                border: 1px solid var(--monthly-border) !important;
                border-radius: 18px;
                overflow: hidden;
                box-shadow: 0 10px 30px rgba(24, 32, 51, .055);
            }

            .monthly-sale-report-ui .monthly-table-title {
                padding: 18px 22px;
                border-bottom: 1px solid #eef0f4;
                background: #fff;
            }

            .monthly-sale-report-ui .monthly-row-count,
            .monthly-sale-report-ui .monthly-export-btn {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                min-height: 34px;
                padding: 0 11px;
                border: 1px solid #e6e9ef;
                border-radius: 9px;
                background: #f8fafc;
                color: #667085;
                font-size: .74rem;
                font-weight: 700;
            }

            .monthly-sale-report-ui .monthly-export-btn {
                border-color: #d1fae5;
                background: #ecfdf5;
                color: #047857;
            }

            .monthly-sale-report-ui .monthly-table-wrap {
                max-height: 520px;
            }

            .monthly-sale-report-ui .monthly-data-table thead th {
                position: sticky;
                z-index: 2;
                top: 0;
                padding-top: 12px !important;
                padding-bottom: 12px !important;
                border-color: #eaedf2;
                background: #f8fafc;
                color: #768096;
                font-size: .67rem;
                font-weight: 800;
                letter-spacing: .055em;
                text-transform: uppercase !important;
            }

            .monthly-sale-report-ui .monthly-data-table tbody td {
                border-color: #f0f2f5;
                color: #4b5565;
                font-size: .77rem;
            }

            .monthly-sale-report-ui .monthly-data-table tbody tr:hover td {
                background: #fafbff;
            }

            .monthly-sale-report-ui .monthly-month-cell {
                display: inline-flex;
                align-items: center;
                gap: 9px;
                color: #293249;
                font-weight: 750;
            }

            .monthly-sale-report-ui .monthly-month-cell i {
                display: grid;
                width: 28px;
                height: 28px;
                place-items: center;
                border-radius: 8px;
                background: #f1f5f9;
                color: #64748b;
            }

            .monthly-sale-report-ui .monthly-money { color: #344054; font-weight: 650; }
            .monthly-sale-report-ui .monthly-negative { color: #dc2626; }
            .monthly-sale-report-ui .monthly-net { color: #4f46e5; font-weight: 800; }
            .monthly-sale-report-ui .monthly-paid { color: #047857; font-weight: 750; }
            .monthly-sale-report-ui .monthly-credit { color: #b45309; font-weight: 700; }

            .monthly-sale-report-ui .monthly-channel {
                display: inline-block;
                min-width: 72px;
                padding: 5px 7px;
                border-radius: 7px;
                font-weight: 700;
            }

            .monthly-sale-report-ui .monthly-channel-card { background: #eff6ff; color: #2563eb; }
            .monthly-sale-report-ui .monthly-channel-cash { background: #f0fdf4; color: #15803d; }

            .monthly-sale-report-ui .monthly-data-table tfoot th {
                border-top: 1px solid #dfe3ea;
                background: #f8fafc;
                color: #344054;
                font-size: .76rem;
            }

            .monthly-sale-report-ui .monthly-results-loading {
                opacity: .55;
                transition: opacity .2s;
            }

            @media (max-width: 1199.98px) {
                .monthly-sale-report-ui .monthly-filter-grid {
                    grid-template-columns: repeat(4, minmax(110px, 1fr));
                }

                .monthly-sale-report-ui .monthly-branch-field {
                    grid-column: span 3;
                }
            }

            @media (max-width: 767.98px) {
                .monthly-sale-report-ui .monthly-filter-content {
                    padding: 21px;
                }

                .monthly-sale-report-ui .monthly-filter-grid {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }

                .monthly-sale-report-ui .monthly-branch-field {
                    grid-column: span 2;
                }

                .monthly-sale-report-ui .monthly-submit-btn {
                    grid-column: span 2;
                }

                .monthly-sale-report-ui .monthly-period-pill {
                    min-width: 0;
                }

                .monthly-sale-report-ui .monthly-kpi-card {
                    align-items: flex-start;
                    gap: 10px;
                    padding: 14px;
                }
            }
        </style>
    @endpush
</div>
