<div>
    <div class="card-header">
        <div class="row" hidden>
            <div class="col-md-6 d-flex gap-1 align-items-center mb-3">
                <div class="btn-group">
                    {{-- <button class="btn btn-icon btn-outline-light" title="To export the items as excel" wire:click="export()"><i class="demo-pli-file-excel fs-5"></i></button> --}}
                </div>
            </div>
        </div>
        {{-- <hr> --}}
        <div class="col-lg-12">
            <div class="row">
                <div class="col-md-2">
                    <label for="from_date">From Date</label>
                    {{ html()->date('from_date')->value('')->class('form-control')->id('from_date')->attribute('wire:model.live', 'from_date') }}
                </div>
                <div class="col-md-2">
                    <label for="to_date">To Date</label>
                    {{ html()->date('to_date')->value('')->class('form-control')->id('to_date')->attribute('wire:model.live', 'to_date') }}
                </div>
                <div class="col-md-4" wire:ignore>
                    <label for="branch_id">Branch</label>
                    {{ html()->select('branch_id', [session('branch_id') => session('branch_name')])->value(session('branch_id'))->class('select-assigned-branch_id-list')->id('branch_id')->placeholder('Branch') }}
                </div>
            </div>
        </div>
    </div>
    <div class="card mt-3">
        <div class="card-body">
            <div wire:ignore>
                <canvas id="salesChart" height="300"></canvas>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-sm">
                <thead>
                    <tr class="text-capitalize">
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="date" label="date" /> </th>
                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="branches.name" label="branch" /> </th>
                        <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="net_sales" label="net sales" /> </th>
                        <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="no_of_invoices" label="no of invoices" /> </th>
                        <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="sales_discount" label="sales discount" /> </th>
                        <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="total_sales" label="total sales" /> </th>
                        <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="credit" label="credit" /> </th>
                        <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="paid" label="paid" /> </th>
                        <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="item_total" label="item total" /> </th>
                        @foreach ($paymentMethods as $name)
                            <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="paid" label="{{ $name }}" /> </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data as $item)
                        <tr>
                            <td>{{ systemDate($item['date']) }}</td>
                            <td>{{ $item['branch_name'] }}</td>
                            <td class="text-end">{{ currency($item['net_sales']) }}</td>
                            <td class="text-end">{{ $item['no_of_invoices'] }}</td>
                            <td class="text-end">{{ currency($item['sales_discount']) }}</td>
                            <td class="text-end">{{ currency($item['total_sales']) }}</td>
                            <td class="text-end">{{ currency($item['credit']) }}</td>
                            <td class="text-end">{{ currency($item['paid']) }}</td>
                            <td class="text-end">{{ currency($item['item_total']) }}</td>
                            @foreach ($paymentMethods as $name)
                                <td class="text-end">{{ currency($item[$name]) }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th class="text-end" colspan="2">Total</th>
                        <th class="text-end">{{ currency($total['net_sales']) }}</th>
                        <th class="text-end">{{ $total['no_of_invoices'] }}</th>
                        <th class="text-end">{{ currency($total['sales_discount']) }}</th>
                        <th class="text-end">{{ currency($total['total_sales']) }}</th>
                        <th class="text-end">{{ currency($total['credit']) }}</th>
                        <th class="text-end">{{ currency($total['paid']) }}</th>
                        <th class="text-end">{{ currency($total['item_total']) }}</th>
                        @foreach ($paymentMethods as $name)
                            <th class="text-end">{{ currency($total[$name]) }}</th>
                        @endforeach
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @push('scripts')
        <script src="{{ asset('assets/vendors/chart.js/chart.umd.min.js') }}"></script>
        <script>
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
