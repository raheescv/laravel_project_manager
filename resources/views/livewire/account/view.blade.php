<div>
    <div class="card-header">
        <div class="row">
            <div class="col-md-6 d-flex gap-1 align-items-center mb-3">
                <div class="btn-group">
                    <button class="btn btn-success btn-sm d-flex align-items-center" title="Export to Excel" data-bs-toggle="tooltip" wire:click="export()">
                        <i class="fa fa-file-excel-o me-md-1 fs-5"></i>
                        <span class="d-none d-md-inline">Export</span>
                    </button>
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
                <div class="col-md-4" wire:ignore>
                    <label for="filter_account_id">Account</label>
                    {{ html()->select('filter_account_id', [])->value('')->class('select-account_id-list')->id('filter_account_id')->placeholder('All Accounts') }}
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" id="excludeOpeningFromTotal" wire:model.live="excludeOpeningFromTotal">
                        <label class="form-check-label" for="excludeOpeningFromTotal">
                            <i class="fa fa-filter me-1 text-muted"></i>
                            Exclude Opening from Total
                        </label>
                    </div>
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
                    @if ($groupedChartData)
                        <li class="nav-item" role="presentation">
                            <button class="nav-link px-3" data-bs-toggle="tab" data-bs-target="#tab-groupedChart" type="button" role="tab" aria-controls="home" aria-selected="true">
                                Grouped List
                            </button>
                        </li>
                    @endif
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
                                        <th> # </th>
                                        <th> date </th>
                                        <th> Journal </th>
                                        <th> payee </th>
                                        <th> reference No </th>
                                        <th> description </th>
                                        <th width="8%" class="text-end"> debit </th>
                                        <th width="8%" class="text-end"> credit </th>
                                        <th width="8%" class="text-end"> balance </th>
                                    </tr>
                                    <tr class="bg-light">
                                        <td colspan="6" class="fw-bold"> <span class="pull-right">Opening Balance</span> </td>
                                        <td class="text-end fw-bold">{{ $openingBalance['debit'] != 0 ? currency($openingBalance['debit']) : '_' }}</td>
                                        <td class="text-end fw-bold">{{ $openingBalance['credit'] != 0 ? currency($openingBalance['credit']) : '_' }}</td>
                                        @php
                                            $balance = $openingBalance['debit'] - $openingBalance['credit'];
                                        @endphp
                                        <td class="text-end fw-bold"> {{ $balance != 0 ? currency($balance) : '_' }} </td>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $runningBalance = $openingBalance['debit'] - $openingBalance['credit'];
                                    @endphp
                                    @foreach ($data as $item)
                                        @php
                                            $runningBalance += $item->debit - $item->credit;
                                        @endphp
                                        <tr>
                                            <td> {{ $item->journal_id }} </td>
                                            <td>{{ systemDate($item->journal->date) }}</td>
                                            <td>
                                                @if ($item->counter_account_id)
                                                    <a target="_blank"
                                                        href="{{ route('account::view', $item->account_id) . '?from_date=' . $item->journal->date . '&to_date=' . $item->journal->date }}"
                                                        class="text-decoration-none">
                                                        {{ $item->counterAccount?->name }}
                                                    </a>
                                                @else
                                                    <a href="#" class="text-primary text-decoration-none cursor-pointer journal-entry-link" data-journal-id="{{ $item->journal_id }}"
                                                        onclick="if(typeof window.openJournalModal === 'function') { window.openJournalModal({{ $item->journal_id }}); } else { console.error('openJournalModal function not available'); alert('Modal function not loaded. Please refresh the page.'); } return false;">
                                                        {{ $item->journal->description }} | {{ $item->journal_remarks ?? ($item->journal->remarks ?? '') }}
                                                    </a>
                                                @endif
                                            </td>
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

                                                    @case('SalePayment')
                                                        <a href="{{ route('sale::view', $item->journal?->model_id) }}">{{ $item->description }}</a>
                                                    @break

                                                    @default
                                                        {{ $item->description }}
                                                @endswitch
                                            </td>
                                            <td class="text-end">{{ $item->debit != 0 ? currency($item->debit) : '_' }}</td>
                                            <td class="text-end">{{ $item->credit != 0 ? currency($item->credit) : '_' }}</td>
                                            <td class="text-end">{{ $runningBalance != 0 ? currency($runningBalance) : '_' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    @php
                                        // Calculate totals with or without opening balance based on exclude flag
                                        $totalDebit = $total['debit'] + $openingBalance['debit'];

                                        $totalCredit = $total['credit'] + $openingBalance['credit'];

                                        // Calculate final balance
                                        $finalBalance = $totalDebit - $totalCredit;

                                        // Format display values: show currency if not zero, otherwise show dash
                                        $displayTotalDebit = $totalDebit != 0 ? currency($totalDebit) : '_';
                                        $displayTotalCredit = $totalCredit != 0 ? currency($totalCredit) : '_';
                                        $displayBalanceDebit = $finalBalance > 0 ? currency($finalBalance) : '_';
                                        $displayBalanceCredit = $finalBalance < 0 ? currency(abs($finalBalance)) : '_';
                                    @endphp

                                    {{-- Total Row --}}
                                    <tr class="bg-light">
                                        <td colspan="6" class="fw-bold">
                                            <span class="pull-right">Total</span>
                                        </td>
                                        <th class="text-end">{{ $displayTotalDebit }}</th>
                                        <th class="text-end">{{ $displayTotalCredit }}</th>
                                        <td class="text-end fw-bold">_</td>
                                    </tr>

                                    {{-- Balance Row --}}
                                    <tr class="bg-light">
                                        <td colspan="6" class="fw-bold">
                                            <span class="pull-right">Balance</span>
                                        </td>
                                        <th class="text-end">{{ $displayBalanceDebit }}</th>
                                        <th class="text-end">{{ $displayBalanceCredit }}</th>
                                        <td class="text-end fw-bold">_</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        {{ $data->links() }}
                    </div>
                    @if ($groupedChartData)
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
                                                    <th class="text-white text-end">Balance</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($groupedChartData as $item)
                                                    <tr>
                                                        <td> <a href="{{ route('account::view', $item->account_id) }}">{{ $item->account->name }}</a> </td>
                                                        <td class="text-end">{{ currency($item->debit) }}</td>
                                                        <td class="text-end">{{ currency($item->credit) }}</td>
                                                        <td class="text-end">{{ currency($item->debit - $item->credit) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Journal Entries Modal Container -->
    <div id="JournalEntriesModalContainer"></div>

    @push('scripts')
        @vite('resources/js/journal-entries-modal.js')
        <script>
            // Ensure function is available immediately
            if (typeof window.openJournalModal === 'undefined') {
                window.openJournalModal = function(journalId) {
                    console.log('Fallback: Opening journal modal for ID:', journalId);
                    if (window.JournalEntriesModal && window.JournalEntriesModal.open) {
                        window.JournalEntriesModal.open(journalId);
                    } else {
                        console.error('JournalEntriesModal not loaded yet');
                    }
                };
            }
        </script>
        <script src="{{ asset('assets/vendors/chart.js/chart.umd.min.js') }}"></script>
        <script src="{{ asset('assets/vendors/chart.js/chartjs-plugin-datalabels@2.min.js') }}"></script>
        <script>
            $('#filter_account_id').change(function() {
                @this.set('filter.filter_account_id', $(this).val());
            });
            // Register the plugin to all charts
            Chart.register(ChartDataLabels);
            let lineChart = null;

            function createChart(chartData, labels) {
                const ctx = document.getElementById('lineChart').getContext('2d');

                // Ensure old chart is destroyed
                if (lineChart) {
                    lineChart.destroy();
                }

                lineChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                                label: 'Credit',
                                data: chartData.map(item => item.credit),
                                borderColor: 'rgb(75, 192, 192)',
                                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                                tension: 0.3,
                                fill: false
                            },
                            {
                                label: 'Debit',
                                data: chartData.map(item => item.debit),
                                borderColor: 'rgb(255, 99, 132)',
                                backgroundColor: 'rgba(255, 99, 132, 0.1)',
                                tension: 0.3,
                                fill: false
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            intersect: false,
                            mode: 'index'
                        },
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top'
                            },
                            title: {
                                display: true,
                                text: '{{ $account->name }}`s Monthly Overview',
                            },
                            datalabels: {
                                display: true,
                                color: 'black',
                                align: 'top',
                                formatter: function(value) {
                                    return value.toLocaleString();
                                }
                            }
                        },
                        scales: {
                            y: {
                                display: true,
                                beginAtZero: true,
                                grid: {
                                    display: true,
                                    color: 'rgba(0, 0, 0, 0.05)'
                                },
                                ticks: {
                                    display: true,
                                    callback: function(value) {
                                        return value.toLocaleString();
                                    }
                                }
                            },
                            x: {
                                display: true,
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    display: true
                                }
                            }
                        }
                    },
                    plugins: [ChartDataLabels]
                });

                return lineChart;
            }

            document.addEventListener('livewire:initialized', () => {
                let chartData = @json($lineChartData);
                let labels = chartData.map(item => item.month);
                createChart(chartData, labels);

                // Listen for chart view toggle
                @this.on('propertyUpdated', () => {
                    let chartData = @json($lineChartData);
                    let labels = chartData.map(item => item.month);
                    createChart(chartData, labels);
                });
            });
        </script>
        @include('components.select.accountSelect')
    @endpush
</div>
