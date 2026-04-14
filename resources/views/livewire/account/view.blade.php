<div>
    {{-- Loading Bar --}}
    <div wire:loading.delay class="position-fixed top-0 start-0 w-100" style="z-index: 1060; height: 3px;">
        <div class="bg-primary h-100 av-loading-bar"></div>
    </div>

    @php
        $openingBal = $openingBalance['debit'] - $openingBalance['credit'];
        $totalDebit = $total['debit'] + $openingBalance['debit'];
        $totalCredit = $total['credit'] + $openingBalance['credit'];
        $finalBalance = $totalDebit - $totalCredit;
    @endphp

    {{-- Quick Stats --}}
    <div class="row g-3 mb-4">
        @php
            $stats = [
                ['label' => 'Total Debit', 'value' => $totalDebit, 'color' => 'danger', 'icon' => 'pli-arrow-up-2'],
                ['label' => 'Total Credit', 'value' => $totalCredit, 'color' => 'success', 'icon' => 'pli-arrow-down-2'],
                ['label' => 'Balance', 'value' => abs($finalBalance), 'color' => 'primary', 'icon' => 'pli-financial', 'suffix' => $finalBalance > 0 ? 'Dr' : ($finalBalance < 0 ? 'Cr' : '')],
                ['label' => 'Transactions', 'value' => $data->total(), 'color' => 'info', 'icon' => 'pli-file-edit', 'format' => 'number'],
            ];
        @endphp
        @foreach ($stats as $stat)
            <div class="col-lg-3 col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body py-3 d-flex align-items-center">
                        <div class="rounded-circle d-flex align-items-center justify-content-center me-3 bg-{{ $stat['color'] }} bg-opacity-10" style="width: 42px; height: 42px; min-width: 42px;">
                            <i class="{{ $stat['icon'] }} fs-5 text-{{ $stat['color'] }}"></i>
                        </div>
                        <div>
                            <div class="small text-muted">{{ $stat['label'] }}</div>
                            <div class="fs-6 fw-bold">
                                @if (($stat['format'] ?? '') === 'number')
                                    {{ number_format($stat['value']) }}
                                @else
                                    {{ currency($stat['value']) }}
                                    @if (!empty($stat['suffix']))
                                        <span class="small text-muted fw-normal">{{ $stat['suffix'] }}</span>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Chart --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-2">
            <div style="height: 250px" wire:ignore>
                <canvas id="lineChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Ledger Table --}}
    <div class="card border-0 shadow-sm">
        {{-- Filters --}}
        <div class="card-header bg-white border-bottom py-3">
            <div class="row g-2 align-items-end">
                <div class="col-lg-2 col-md-3">
                    <label class="form-label small text-muted mb-1">From</label>
                    {{ html()->date('from_date')->value('')->class('form-control form-control-sm')->id('from_date')->attribute('wire:model.live', 'filter.from_date') }}
                </div>
                <div class="col-lg-2 col-md-3">
                    <label class="form-label small text-muted mb-1">To</label>
                    {{ html()->date('to_date')->value('')->class('form-control form-control-sm')->id('to_date')->attribute('wire:model.live', 'filter.to_date') }}
                </div>
                <div class="col-lg-3 col-md-4" wire:ignore>
                    <label class="form-label small text-muted mb-1">Counter Account</label>
                    {{ html()->select('filter_account_id', [])->value('')->class('select-account_id-list')->id('filter_account_id')->placeholder('All Accounts') }}
                </div>
                <div class="col-lg-2 col-md-3">
                    <label class="form-label small text-muted mb-1">Search</label>
                    <input type="text" wire:model.live.debounce.300ms="filter.search" placeholder="Search..." class="form-control form-control-sm" autocomplete="off">
                </div>
                <div class="col-lg-3 col-md-12 d-flex align-items-end justify-content-between gap-2">
                    <div class="d-flex align-items-center gap-3">
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" id="excludeOpeningFromTotal" wire:model.live="excludeOpeningFromTotal">
                            <label class="form-check-label small" for="excludeOpeningFromTotal">Excl. Opening</label>
                        </div>
                        <select wire:model.live="limit" class="form-select form-select-sm" style="width: auto;">
                            <option value="10">10</option>
                            <option value="100">100</option>
                            <option value="500">500</option>
                        </select>
                    </div>
                    <button class="btn btn-sm btn-success" wire:click="export()" title="Export Excel">
                        <i class="pli-file-excel me-1"></i>Excel
                    </button>
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.8125rem;">
                    <thead>
                        <tr class="bg-light">
                            <th class="border-0 py-2 ps-3" style="width: 4%;">#</th>
                            <th class="border-0 py-2" style="width: 9%;">Date</th>
                            <th class="border-0 py-2" style="width: 20%;">Account</th>
                            <th class="border-0 py-2" style="width: 12%;">Payee</th>
                            <th class="border-0 py-2" style="width: 8%;">Ref No</th>
                            <th class="border-0 py-2" style="width: 23%;">Description</th>
                            <th class="border-0 py-2 text-end" style="width: 8%;">Debit</th>
                            <th class="border-0 py-2 text-end" style="width: 8%;">Credit</th>
                            <th class="border-0 py-2 text-end pe-3" style="width: 8%;">Balance</th>
                        </tr>
                    </thead>

                    {{-- Opening Balance Row --}}
                    <thead>
                        <tr class="bg-light bg-opacity-50" style="border-left: 3px solid var(--bs-primary);">
                            <td colspan="6" class="py-2 ps-3 fw-bold small">Opening Balance</td>
                            <td class="text-end py-2 fw-bold small text-nowrap">{{ $openingBalance['debit'] != 0 ? currency($openingBalance['debit']) : '-' }}</td>
                            <td class="text-end py-2 fw-bold small text-nowrap">{{ $openingBalance['credit'] != 0 ? currency($openingBalance['credit']) : '-' }}</td>
                            <td class="text-end pe-3 py-2 fw-bold small text-nowrap">{{ $openingBal != 0 ? currency($openingBal) : '-' }}</td>
                        </tr>
                    </thead>

                    <tbody>
                        @php $runningBalance = $openingBal; @endphp
                        @forelse ($data as $item)
                            @php $runningBalance += $item->debit - $item->credit; @endphp
                            <tr>
                                <td class="py-1 ps-3 text-muted small">{{ $item->journal_id }}</td>
                                <td class="py-1 text-nowrap small">{{ systemDate($item->journal->date) }}</td>
                                <td class="py-1 small">
                                    @if ($item->counter_account_id)
                                        <a href="{{ route('account::view', $item->counter_account_id) }}?from_date={{ $item->journal->date }}&to_date={{ $item->journal->date }}"
                                            target="_blank" class="text-decoration-none">
                                            {{ $item->counterAccount?->name }}
                                        </a>
                                    @else
                                        <a href="#" class="text-primary text-decoration-none journal-entry-link"
                                            data-journal-id="{{ $item->journal_id }}"
                                            onclick="if(typeof window.openJournalModal === 'function') { window.openJournalModal({{ $item->journal_id }}); } return false;">
                                            {{ $item->journal->description }}
                                            @if ($item->journal_remarks ?? ($item->journal->remarks ?? ''))
                                                <span class="text-muted">| {{ $item->journal_remarks ?? $item->journal->remarks }}</span>
                                            @endif
                                        </a>
                                    @endif
                                </td>
                                <td class="py-1 small text-muted">{{ $item->person_name }}</td>
                                <td class="py-1 small text-muted">{{ $item->reference_number }}</td>
                                <td class="py-1 small">
                                    @switch($item->model)
                                        @case('Sale')
                                            <a href="{{ route('sale::view', $item->model_id) }}" class="text-decoration-none">{{ $item->description }}</a>
                                            @break
                                        @case('SaleReturn')
                                            <a href="{{ route('sale_return::view', $item->model_id) }}" class="text-decoration-none">{{ $item->description }}</a>
                                            @break
                                        @case('SalePayment')
                                            <a href="{{ route('sale::view', $item->journal?->model_id) }}" class="text-decoration-none">{{ $item->description }}</a>
                                            @break
                                        @default
                                            {{ $item->description }}
                                    @endswitch
                                </td>
                                <td class="text-end py-1 text-nowrap small {{ $item->debit > 0 ? 'text-danger' : '' }}">{{ $item->debit != 0 ? currency($item->debit) : '-' }}</td>
                                <td class="text-end py-1 text-nowrap small {{ $item->credit > 0 ? 'text-success' : '' }}">{{ $item->credit != 0 ? currency($item->credit) : '-' }}</td>
                                <td class="text-end pe-3 py-1 text-nowrap small fw-medium">{{ $runningBalance != 0 ? currency($runningBalance) : '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4 fst-italic">No transactions found for this period</td>
                            </tr>
                        @endforelse
                    </tbody>

                    {{-- Totals --}}
                    <tfoot>
                        <tr class="bg-dark bg-opacity-10" style="border-top: 2px solid #333;">
                            <td colspan="6" class="py-2 ps-3 fw-bold">Total</td>
                            <td class="text-end py-2 fw-bold text-nowrap">{{ $totalDebit != 0 ? currency($totalDebit) : '-' }}</td>
                            <td class="text-end py-2 fw-bold text-nowrap">{{ $totalCredit != 0 ? currency($totalCredit) : '-' }}</td>
                            <td class="text-end pe-3 py-2 fw-bold text-nowrap">-</td>
                        </tr>
                        <tr class="bg-primary bg-opacity-10">
                            <td colspan="6" class="py-2 ps-3 fw-bold">Balance</td>
                            <td class="text-end py-2 fw-bold text-nowrap">{{ $finalBalance > 0 ? currency($finalBalance) : '-' }}</td>
                            <td class="text-end py-2 fw-bold text-nowrap">{{ $finalBalance < 0 ? currency(abs($finalBalance)) : '-' }}</td>
                            <td class="text-end pe-3 py-2 fw-bold text-nowrap">-</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Pagination --}}
        @if ($data->hasPages())
            <div class="card-footer bg-white border-top py-2">
                {{ $data->links() }}
            </div>
        @endif
    </div>

    {{-- Grouped Chart Tab --}}
    @if ($groupedChartData && count($groupedChartData) > 0)
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold">Counter Account Summary</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.8125rem;">
                        <thead>
                            <tr class="bg-light">
                                <th class="border-0 py-2 ps-3">Account</th>
                                <th class="border-0 py-2 text-end">Debit</th>
                                <th class="border-0 py-2 text-end">Credit</th>
                                <th class="border-0 py-2 text-end pe-3">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($groupedChartData as $item)
                                <tr>
                                    <td class="py-1 ps-3">
                                        <a href="{{ route('account::view', $item->account_id) }}" class="text-decoration-none">{{ $item->account->name }}</a>
                                    </td>
                                    <td class="text-end py-1 text-nowrap">{{ currency($item->debit) }}</td>
                                    <td class="text-end py-1 text-nowrap">{{ currency($item->credit) }}</td>
                                    <td class="text-end pe-3 py-1 text-nowrap fw-medium">{{ currency($item->debit - $item->credit) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- Journal Entries Modal Container --}}
    <div id="JournalEntriesModalContainer"></div>

    @push('scripts')
        @vite('resources/js/journal-entries-modal.js')
        <script>
            if (typeof window.openJournalModal === 'undefined') {
                window.openJournalModal = function(journalId) {
                    if (window.JournalEntriesModal && window.JournalEntriesModal.open) {
                        window.JournalEntriesModal.open(journalId);
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

            Chart.register(ChartDataLabels);
            let lineChart = null;

            function createChart(chartData, labels) {
                const ctx = document.getElementById('lineChart').getContext('2d');
                if (lineChart) lineChart.destroy();

                lineChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Debit',
                            data: chartData.map(item => item.debit),
                            borderColor: 'rgb(220, 53, 69)',
                            backgroundColor: 'rgba(220, 53, 69, 0.05)',
                            tension: 0.3,
                            fill: true,
                            borderWidth: 2,
                            pointRadius: 3
                        }, {
                            label: 'Credit',
                            data: chartData.map(item => item.credit),
                            borderColor: 'rgb(25, 135, 84)',
                            backgroundColor: 'rgba(25, 135, 84, 0.05)',
                            tension: 0.3,
                            fill: true,
                            borderWidth: 2,
                            pointRadius: 3
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { intersect: false, mode: 'index' },
                        plugins: {
                            legend: { display: true, position: 'top', labels: { boxWidth: 12, padding: 15 } },
                            title: { display: false },
                            datalabels: {
                                display: function(context) {
                                    return context.dataset.data[context.dataIndex] > 0;
                                },
                                color: '#666',
                                font: { size: 10 },
                                align: 'top',
                                formatter: function(value) { return value.toLocaleString(); }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: { color: 'rgba(0,0,0,0.04)' },
                                ticks: { callback: function(v) { return v.toLocaleString(); } }
                            },
                            x: { grid: { display: false } }
                        }
                    },
                    plugins: [ChartDataLabels]
                });
            }

            document.addEventListener('livewire:initialized', () => {
                let chartData = @json($lineChartData);
                createChart(chartData, chartData.map(item => item.month));

                @this.on('propertyUpdated', () => {
                    let chartData = @json($lineChartData);
                    createChart(chartData, chartData.map(item => item.month));
                });
            });
        </script>
        @include('components.select.accountSelect')
    @endpush

    <style>
        .av-loading-bar { animation: av-loading 1.5s ease-in-out infinite; }
        @keyframes av-loading {
            0% { width: 0; margin-left: 0; }
            50% { width: 60%; margin-left: 20%; }
            100% { width: 0; margin-left: 100%; }
        }
    </style>
</div>
