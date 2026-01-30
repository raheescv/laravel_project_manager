<div>
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <div class="d-flex align-items-center mb-4">
                <div>
                    <h5 class="mb-0">Daily Sales Insights</h5>
                    <small class="text-muted">Track your daily sales performance and trends</small>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-3">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="fa fa-calendar text-success"></i>
                        </span>
                        {{ html()->date('from_date')->value('')->class('form-control border-start-0 ps-0')->id('from_date')->attribute('wire:model.live', 'from_date') }}
                    </div>
                    <label class="form-label small text-muted mt-1">From Date</label>
                </div>
                <div class="col-md-3">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="fa fa-calendar text-success"></i>
                        </span>
                        {{ html()->date('to_date')->value('')->class('form-control border-start-0 ps-0')->id('to_date')->attribute('wire:model.live', 'to_date') }}
                    </div>
                    <label class="form-label small text-muted mt-1">To Date</label>
                </div>
                <div class="col-md-4" wire:ignore>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="fa fa-building text-success"></i>
                        </span>
                        {{ html()->select('branch_id', [session('branch_id') => session('branch_name')])->value(session('branch_id'))->class('select-assigned-branch_id-list border-start-0 ps-0')->id('branch_id')->attribute('style', 'width:80%')->placeholder('Select Branch') }}
                    </div>
                    <label class="form-label small text-muted mt-1">Branch</label>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-body p-4">
            <div wire:ignore>
                <canvas id="salesChart" height="300"></canvas>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header bg-white p-3 border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="mb-0 text-muted">Detailed Sales Data</h6>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-light" onclick="window.print()">
                        <i class="fa fa-print me-1"></i> Print
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr class="text-capitalize">
                            <th class="border-bottom px-3 py-3">
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="date" label="Date" />
                            </th>
                            <th class="border-bottom px-3 py-3">
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="branches.name" label="Branch" />
                            </th>
                            <th class="border-bottom px-3 py-3 text-end">
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="net_sales" label="Net Sales" />
                            </th>
                            <th class="border-bottom px-3 py-3 text-end">
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="no_of_invoices" label="Invoices" />
                            </th>
                            <th class="border-bottom px-3 py-3 text-end">
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="sales_discount" label="Discount" />
                            </th>
                            <th class="border-bottom px-3 py-3 text-end">
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="total_sales" label="Total" />
                            </th>
                            <th class="border-bottom px-3 py-3 text-end">
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="credit" label="Credit" />
                            </th>
                            <th class="border-bottom px-3 py-3 text-end">
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="paid" label="Paid" />
                            </th>
                            <th class="border-bottom px-3 py-3 text-end">
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="item_total" label="Items" />
                            </th>
                            @foreach ($paymentMethods as $name)
                                <th class="border-bottom px-3 py-3 text-end">
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="paid" label="{{ $name }}" />
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($data as $item)
                            <tr>
                                <td class="px-3 py-2">
                                    <span class="text-dark">{{ systemDate($item['date']) }}</span>
                                </td>
                                <td class="px-3 py-2">
                                    <span class="text-primary">{{ $item['branch_name'] }}</span>
                                </td>
                                <td class="px-3 py-2 text-end">
                                    <span class="fw-medium">{{ currency($item['net_sales']) }}</span>
                                </td>
                                <td class="px-3 py-2 text-end">
                                    <span class="badge bg-light text-dark">{{ $item['no_of_invoices'] }}</span>
                                </td>
                                <td class="px-3 py-2 text-end text-danger">
                                    {{ currency($item['sales_discount']) }}
                                </td>
                                <td class="px-3 py-2 text-end fw-bold">
                                    {{ currency($item['total_sales']) }}
                                </td>
                                <td class="px-3 py-2 text-end text-warning">
                                    {{ currency($item['credit']) }}
                                </td>
                                <td class="px-3 py-2 text-end text-success">
                                    {{ currency($item['paid']) }}
                                </td>
                                <td class="px-3 py-2 text-end">
                                    {{ currency($item['item_total']) }}
                                </td>
                                @foreach ($paymentMethods as $name)
                                    <td class="px-3 py-2 text-end">
                                        {{ currency($item[$name]) }}
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-light">
                        <tr>
                            <th class="px-3 py-3 text-end" colspan="2">Total</th>
                            <th class="px-3 py-3 text-end fw-bold">{{ currency($total['net_sales']) }}</th>
                            <th class="px-3 py-3 text-end">
                                <span class="badge bg-primary">{{ $total['no_of_invoices'] }}</span>
                            </th>
                            <th class="px-3 py-3 text-end text-danger fw-bold">{{ currency($total['sales_discount']) }}</th>
                            <th class="px-3 py-3 text-end fw-bold">{{ currency($total['total_sales']) }}</th>
                            <th class="px-3 py-3 text-end text-warning fw-bold">{{ currency($total['credit']) }}</th>
                            <th class="px-3 py-3 text-end text-success fw-bold">{{ currency($total['paid']) }}</th>
                            <th class="px-3 py-3 text-end fw-bold">{{ currency($total['item_total']) }}</th>
                            @foreach ($paymentMethods as $name)
                                <th class="px-3 py-3 text-end fw-bold">{{ currency($total[$name]) }}</th>
                            @endforeach
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        @push('scripts')
            <script src="{{ asset('assets/vendors/chart.js/chart.umd.min.js') }}"></script>
            <script src="{{ asset('assets/vendors/chart.js/chartjs-plugin-datalabels@2.min.js') }}"></script>
            <script>
                Chart.register(ChartDataLabels);
                $(document).ready(function() {
                    $('#branch_id').on('change', function(e) {
                        const value = $(this).val() || null;
                        @this.set('branch_id', value);
                    });
                });

                let salesChart;
                window.addEventListener('updateChart', event => {
                    const ctx = document.getElementById('salesChart');

                    if (salesChart) {
                        salesChart.destroy();
                    }

                    const chartSeriesData = event.detail[0];
                    if (!Array.isArray(chartSeriesData) || !chartSeriesData.length) {
                        console.warn('No chart data available');
                        return;
                    }

                    // Define beautiful gradient colors for bars
                    const colorPalette = [
                        ['rgba(116, 185, 255, 0.9)', 'rgba(85, 171, 255, 0.9)'], // Azure Blue
                        ['rgba(255, 171, 145, 0.9)', 'rgba(255, 143, 108, 0.9)'], // Coral
                        ['rgba(108, 198, 177, 0.9)', 'rgba(78, 188, 162, 0.9)'], // Mint
                        ['rgba(198, 162, 255, 0.9)', 'rgba(177, 132, 255, 0.9)'], // Lavender
                        ['rgba(255, 201, 107, 0.9)', 'rgba(255, 189, 74, 0.9)'], // Golden
                        ['rgba(255, 145, 190, 0.9)', 'rgba(255, 112, 170, 0.9)'], // Pink
                        ['rgba(106, 217, 145, 0.9)', 'rgba(77, 208, 124, 0.9)'], // Emerald
                        ['rgba(255, 170, 170, 0.9)', 'rgba(255, 140, 140, 0.9)'] // Salmon
                    ];

                    const datasets = chartSeriesData.map((series, index) => {
                        let backgroundColor;
                        try {
                            if (ctx.getContext('2d')) {
                                const gradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 300);
                                gradient.addColorStop(0, colorPalette[index % colorPalette.length][0]);
                                gradient.addColorStop(1, colorPalette[index % colorPalette.length][1]);
                                backgroundColor = gradient;
                            } else {
                                backgroundColor = colorPalette[index % colorPalette.length][0];
                            }
                        } catch (e) {
                            backgroundColor = colorPalette[index % colorPalette.length][0];
                        }

                        return {
                            label: series.name,
                            data: series.dataPoints.map(dp => dp.y),
                            backgroundColor,
                            borderWidth: 0,
                            borderRadius: 6,
                            borderSkipped: false,
                            hoverBackgroundColor: colorPalette[index % colorPalette.length][0],
                        };
                    });

                    // Get all unique dates across all series
                    const allDates = [...new Set(
                        chartSeriesData.flatMap(series =>
                            series.dataPoints.map(dp => dp.x)
                        )
                    )];

                    const labels = allDates.map(timestamp =>
                        new Date(timestamp).toLocaleDateString('en-US', {
                            day: '2-digit',
                            month: 'short'
                        })
                    )
                    salesChart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels,
                            datasets
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'top',
                                    labels: {
                                        font: {
                                            family: getComputedStyle(document.body).getPropertyValue('--bs-font-sans-serif')
                                        }
                                    }
                                },
                                tooltip: {
                                    callbacks: {
                                        afterTitle: function(tooltipItems) {
                                            const dataIndex = tooltipItems[0].dataIndex;
                                            const originalData = chartSeriesData[tooltipItems[0].datasetIndex].dataPoints[dataIndex];
                                            return [
                                                `Net Sales:  ${originalData.net_sales.toFixed(2)}`,
                                                `Discount:  ${originalData.sales_discount.toFixed(2)}`,
                                                `Invoices: ${originalData.invoices}`
                                            ];
                                        }
                                    }
                                },
                                datalabels: {
                                    anchor: 'end',
                                    align: 'top',
                                    formatter: function(value) {
                                        return value.toLocaleString();
                                    },
                                    font: {
                                        weight: 'bold',
                                        size: 11,
                                        family: getComputedStyle(document.body).getPropertyValue('--bs-font-sans-serif')
                                    },
                                    padding: 4
                                }
                            },
                            scales: {
                                x: {
                                    grid: {
                                        display: false
                                    },
                                    ticks: {
                                        font: {
                                            family: getComputedStyle(document.body).getPropertyValue('--bs-font-sans-serif')
                                        }
                                    }
                                },
                                y: {
                                    beginAtZero: true,
                                    grid: {
                                        borderDash: [2, 2]
                                    },
                                    ticks: {
                                        font: {
                                            family: getComputedStyle(document.body).getPropertyValue('--bs-font-sans-serif')
                                        },
                                        callback: function(value) {
                                            return value.toLocaleString();
                                        }
                                    }
                                }
                            }
                        }
                    });
                });
            </script>
        @endpush
    </div>
</div>
