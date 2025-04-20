<div>
    <div class="card-header">
        <div class="row">
            <div class="col-md-6 d-flex gap-1 align-items-center mb-3">
                <div class="btn-group">
                    @can('account.delete')
                        <button class="btn btn-icon btn-outline-light" title="To delete the selected items" wire:click="delete()" wire:confirm="Are you sure you want to delete the selected items?">
                            <i class="demo-pli-recycling fs-5"></i>
                        </button>
                    @endcan
                </div>
            </div>
            <div class="col-md-6 d-flex gap-1 align-items-center justify-content-md-end mb-3">
                <div class="form-group">
                    <select wire:model.live="limit" class="form-control">
                        <option value="10">10</option>
                        <option value="100">100</option>
                        <option value="500">500</option>
                    </select>
                </div>
                <div class="form-group">
                    <input type="text" wire:model.live="filter.search" autofocus placeholder="Search..." class="form-control" autocomplete="off">
                </div>
            </div>
        </div>
        <hr>
        <div class="col-lg-12">
            <div class="row">
                <div class="col-md-2">
                    <label for="from_date">From Date</label>
                    {{ html()->date('from_date')->value('')->class('form-control')->id('from_date')->attribute('wire:model.live', 'filter.from_date') }}
                </div>
                <div class="col-md-2">
                    <label for="to_date">To Date</label>
                    {{ html()->date('to_date')->value('')->class('form-control')->id('to_date')->attribute('wire:model.live', 'filter.to_date') }}
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="tab-base">
                <ul class="nav nav-underline nav-component border-bottom" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link px-3 active" data-bs-toggle="tab" data-bs-target="#tab-List" type="button" role="tab" aria-controls="home" aria-selected="true">
                            List
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link px-3" data-bs-toggle="tab" data-bs-target="#tab-groupedChart" type="button" role="tab" aria-controls="home" aria-selected="true">
                            Grouped List
                        </button>
                    </li>
                </ul>
                <div class="tab-content">
                    <div id="tab-List" class="tab-pane fade active show" role="tabpanel">
                        <div style="height: 300px" wire:ignore>
                            <canvas id="lineChart"></canvas>
                        </div>
                        <hr>
                        <div class="table-responsive">
                            <table class="table table-striped table-sm">
                                <thead>
                                    <tr class="text-capitalize">
                                        <th>
                                            <input type="checkbox" wire:model.live="selectAll" />
                                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="id" label="id" />
                                        </th>
                                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="date" label="date" /> </th>
                                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="account_name" label="account name" /> </th>
                                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="person_name" label="payee" /> </th>
                                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="reference_number" label="reference No" /> </th>
                                        <th> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="description" label="description" /> </th>
                                        <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="debit" label="debit" /> </th>
                                        <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="credit" label="credit" /> </th>
                                        <th class="text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="balance" label="balance" /> </th>
                                    </tr>
                                    <tr>
                                        <th class="text-end" colspan="6">Total</th>
                                        <th class="text-end">{{ currency($total['debit']) }}</th>
                                        <th class="text-end">{{ currency($total['credit']) }}</th>
                                        <th class="text-end">{{ currency($total['debit'] - $total['credit']) }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($data as $item)
                                        <tr>
                                            <td>
                                                <input type="checkbox" value="{{ $item->journal_id }}" wire:model.live="selected" />
                                                {{ $item->journal_id }}
                                            </td>
                                            <td>{{ systemDate($item->date) }}</td>
                                            <td>{{ $item->account_name }}</td>
                                            <td>{{ $item->person_name }}</td>
                                            <td>{{ $item->reference_number }}</td>
                                            <td>
                                                @switch($item->model)
                                                    @case('Sale')
                                                        <a href="{{ route('sale::view', $item->model_id) }}">{{ $item->description }}</a>
                                                    @break

                                                    @case('SaleReturn')
                                                        <a href="{{ route('sale_return::view', $item->model_id) }}">{{ $item->description }}</a>
                                                    @break

                                                    @default
                                                        {{ $item->description }}
                                                @endswitch
                                            </td>
                                            <td class="text-end">{{ currency($item->debit) }}</td>
                                            <td class="text-end">{{ currency($item->credit) }}</td>
                                            <td class="text-end">{{ currency($item->balance) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        {{ $data->links() }}
                    </div>
                    <div id="tab-groupedChart" class="tab-pane fade active show" role="tabpanel">
                        <div class="row">
                            <div class="col-md-12">
                                <h3>Statistics</h3>
                                <div class="table-responsive">
                                    <table class="table table-striped table-sm">
                                        <thead>
                                            <tr class="bg-primary">
                                                <th class="text-white">Account Head</th>
                                                <th class="text-white text-end">Debit</th>
                                                <th class="text-white text-end">Credit</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($groupedChartData as $item)
                                                <tr>
                                                    <td> <a href="{{ route('account::view', $item->account_id) }}">{{ $item->account_name }}</a> </td>
                                                    <td class="text-end">{{ currency($item->debit) }}</td>
                                                    <td class="text-end">{{ currency($item->credit) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @push('scripts')
        <script src="{{ asset('assets/vendors/chart.js/chart.umd.min.js') }}"></script>
        <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>

        <script>
            const _body = getComputedStyle(document.body);
            let primaryColor, headingsColor, mutedColorRGB, dangerColor, infoColor, warningColor, successColor, lineChart, gridColor, gridMainColor;

            let updateColorVars = () => {
                // primaryColor = window.getComputedStyle(document.querySelector(".page-title")).color; // "red" //`rgba( ${_body.getPropertyValue( "--bs-primary-color-rgb" )}, .75)`;
                primaryColor = _body.getPropertyValue("--bs-primary");
                // headingsColor = window.getComputedStyle(document.querySelector(".text-body-emphasis")).color; //_body.getPropertyValue( "--bs-primary-color" );
                headingsColor = _body.getPropertyValue("--bs-primary");
                mutedColorRGB = `rgba( ${_body.getPropertyValue( "--bs-secondary-color-rgb" )}, .5)`;
                dangerColor = _body.getPropertyValue("--bs-danger");
                infoColor = _body.getPropertyValue("--bs-info");
                warningColor = _body.getPropertyValue("--bs-warning");
                successColor = _body.getPropertyValue("--bs-success");
                gridColor = mutedColorRGB.replace(/^(.*,)(.*)\)/g, "$1 .075)");
                gridMainColor = mutedColorRGB.replace(/^(.*,)(.*)\)/g, "$1 .575)");
                return;
            }

            const getGridYColor = function(context) {
                if (context.index > 0) {
                    return gridColor;
                } else if (context.index == 0) {
                    return gridMainColor;
                }
            }

            const getGridXColor = function(context) {
                if (context.index > 0) {
                    return "transparent";
                } else if (context.index == 0) {
                    return gridMainColor;
                }
            }

            document.addEventListener("DOMContentLoaded", () => {
                updateColorVars();
                const lineData2 = [{
                        'elapsed': 'Jan 1',
                        'value': 18
                    }, {
                        'elapsed': 'Jan 2',
                        'value': 24
                    }, {
                        'elapsed': 'Jan 3',
                        'value': 9
                    }, {
                        'elapsed': 'Jan 4',
                        'value': 12
                    }, {
                        'elapsed': 'Jan 5',
                        'value': 13
                    }, {
                        'elapsed': 'Jan 6',
                        'value': 22
                    }, {
                        'elapsed': 'Jan 7',
                        'value': 11
                    }, {
                        'elapsed': 'Jan 8',
                        'value': 26
                    }, {
                        'elapsed': 'Jan 9',
                        'value': 12
                    }, {
                        'elapsed': 'Jan 10',
                        'value': 19
                    },
                    {
                        'elapsed': 'Jan 11',
                        'value': 18
                    }, {
                        'elapsed': 'Jan 12',
                        'value': 24
                    }, {
                        'elapsed': 'Jan 13',
                        'value': 9
                    }, {
                        'elapsed': 'Jan 14',
                        'value': 12
                    }, {
                        'elapsed': 'Jan 15',
                        'value': 13
                    }, {
                        'elapsed': 'Jan 16',
                        'value': 22
                    }, {
                        'elapsed': 'Jan 17',
                        'value': 11
                    }, {
                        'elapsed': 'Jan 18',
                        'value': 26
                    }, {
                        'elapsed': 'Jan 19',
                        'value': 12
                    }, {
                        'elapsed': 'Jan 20',
                        'value': 19
                    }
                ];
                const lineData = @js($lineChartData);
                lineChart = new Chart(document.getElementById('lineChart'), {
                    type: 'line',
                    data: {
                        datasets: [{
                                label: 'Credit Chart',
                                data: lineData,
                                borderWidth: 1.75,
                                borderColor: infoColor,
                                backgroundColor: infoColor,
                                parsing: {
                                    xAxisKey: 'month',
                                    yAxisKey: 'credit'
                                }
                            },
                            {
                                label: 'Debit Chart',
                                data: lineData,
                                borderWidth: 1.75,
                                borderColor: primaryColor,
                                backgroundColor: primaryColor,
                                parsing: {
                                    xAxisKey: 'month',
                                    yAxisKey: 'debit'
                                }
                            }
                        ]
                    },
                    options: {
                        plugins: {
                            title: {
                                display: true,
                                color: headingsColor,
                                text: '{{ ucFirst($account?->name) }}`s Monthly Overview'
                            },
                            legend: {
                                display: true,
                                labels: {
                                    color: headingsColor,
                                    boxWidth: 10,
                                }
                            }
                        },
                        // Tooltip mode
                        interaction: {
                            intersect: false,
                        },
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                grid: {
                                    color: getGridYColor,
                                    lineWidth: 2
                                },
                                // suggestedMax: 300000,
                                ticks: {
                                    font: {
                                        size: 11
                                    },
                                    color: headingsColor,
                                    beginAtZero: false,
                                    stepSize: 5
                                }
                            },
                            x: {
                                grid: {
                                    color: getGridXColor,
                                    lineWidth: 2
                                },
                                ticks: {
                                    font: {
                                        size: 12
                                    },
                                    color: headingsColor,
                                    autoSkip: true,
                                    maxRotation: 0,
                                    minRotation: 0,
                                    maxTicksLimit: 9
                                }
                            }
                        },

                        elements: {
                            // Dot width
                            point: {
                                radius: 1,
                                hoverRadius: 5
                            },
                            // Smooth lines
                            line: {
                                tension: 0.4
                            }
                        }
                    }
                });
            });
            const updateDashboardChart = function() {
                updateColorVars();
                lineChart.data.datasets[0].borderColor = primaryColor;
                lineChart.data.datasets[0].backgroundColor = primaryColor;
                lineChart.options.plugins.title.color = headingsColor;
                lineChart.options.plugins.legend.labels.color = primaryColor;
                lineChart.options.scales.y.grid.color = getGridYColor;
                lineChart.options.scales.x.grid.color = getGridXColor;
                lineChart.options.scales.y.ticks.color = headingsColor;
                lineChart.options.scales.x.ticks.color = headingsColor;
                lineChart.update();
            };
            ["change.nf.colormode", "scheme-changed", "theme-changed"].forEach(ev => document.addEventListener(ev, updateDashboardChart))
        </script>
    @endpush
</div>
