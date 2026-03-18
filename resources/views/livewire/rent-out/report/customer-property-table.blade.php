<div>
    {{-- ═══ Filter Section ═══ --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-3" wire:ignore>
                    <label class="form-label fw-medium">
                        <i class="fa fa-user text-primary me-1 small"></i> Customer
                    </label>
                    {{ html()->select('filterCustomer', [])->value('')->class('select-customer_id-list border-secondary-subtle shadow-sm')->id('cp_filterCustomer')->placeholder('Search Customer Name')->attribute('wire:model', 'filterCustomer') }}
                </div>
                <div class="col-md-2" wire:ignore>
                    <label class="form-label fw-medium">
                        <i class="fa fa-folder-open text-primary me-1 small"></i> Group
                    </label>
                    {{ html()->select('filterGroup', [])->value('')->class('select-property_group_id-list border-secondary-subtle shadow-sm')->id('cp_filterGroup')->placeholder('Search Here')->attribute('wire:model', 'filterGroup') }}
                </div>
                <div class="col-md-2" wire:ignore>
                    <label class="form-label fw-medium">
                        <i class="fa fa-building text-primary me-1 small"></i> Building
                    </label>
                    {{ html()->select('filterBuilding', [])->value('')->class('select-property_building_id-list border-secondary-subtle shadow-sm')->id('cp_filterBuilding')->placeholder('Search Here')->attribute('wire:model', 'filterBuilding')->attribute('data-group-select', '#cp_filterGroup') }}
                </div>
                <div class="col-md-3" wire:ignore>
                    <label class="form-label fw-medium">
                        <i class="fa fa-home text-primary me-1 small"></i> Property No/Unit
                    </label>
                    {{ html()->select('filterProperty', [])->value('')->class('select-property_id-list border-secondary-subtle shadow-sm')->id('cp_filterProperty')->placeholder('Search Here')->attribute('wire:model', 'filterProperty')->attribute('data-building-select', '#cp_filterBuilding')->attribute('data-group-select', '#cp_filterGroup') }}
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button wire:click="fetch" class="btn btn-primary shadow-sm">
                        <i class="fa fa-search me-1"></i> Fetch
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══ Report Results ═══ --}}
    @if($fetched)
        @forelse($rentOuts as $rentOut)
            <div class="card shadow-sm mb-4">
                <div class="card-body p-0">
                    {{-- ═══ Top Section: Payments Summary + Property Details ═══ --}}
                    <div class="row g-0">
                        {{-- Left: Rentout Payments Summary --}}
                        <div class="col-md-6 p-4 border-end">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="fw-bold mb-0">Rentout - Payments</h5>
                                <span class="text-muted">All payments: <strong>{{ number_format($rentOut->rentOutTransactions->sum('credit'), 2) }}</strong></span>
                            </div>

                            @php
                                $totalRentPayments = $rentOut->rentOutTransactions->where('group', 'rent')->sum('credit');
                                $utilitiesPayments = $rentOut->rentOutTransactions->where('group', 'utility')->sum('credit');
                                $totalRentToPay = $rentOut->total ?? 0;
                                $rentProgress = $totalRentToPay > 0 ? min(100, ($totalRentPayments / $totalRentToPay) * 100) : 0;
                                $totalUtilityToPay = $rentOut->utilityTerms->sum('amount');
                                $utilityProgress = $totalUtilityToPay > 0 ? min(100, ($utilitiesPayments / $totalUtilityToPay) * 100) : 0;
                            @endphp

                            <div class="mb-4">
                                <h3 class="mb-1">{{ number_format($totalRentPayments, 2) }}</h3>
                                <p class="text-muted small mb-1">Total rent payments in period</p>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-success" style="width: {{ $rentProgress }}%"></div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <h3 class="mb-1">{{ number_format($utilitiesPayments, 2) }}</h3>
                                <p class="text-muted small mb-1">Utilities payments</p>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-success" style="width: {{ $utilityProgress }}%"></div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                                <small class="text-muted">
                                    <i class="fa fa-info-circle me-1"></i>
                                    Analysis of rentouts: The value has been changed over time.
                                </small>
                                <small class="text-muted">
                                    <i class="fa fa-clock-o me-1"></i>
                                    Update on {{ $rentOut->updated_at?->format('d-m-Y h:i:s A') }}
                                </small>
                            </div>
                        </div>

                        {{-- Right: Property Details Card --}}
                        <div class="col-md-6 p-4">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <h5 class="mb-0">
                                    <a href="{{ route('property::rent::view', $rentOut->id) }}" class="text-primary text-decoration-none">
                                        Owner Unit /{{ $rentOut->customer?->name }} {{ $rentOut->property?->number }}-{{ $rentOut->building?->name }}
                                    </a>
                                </h5>
                                @if($rentOut->status)
                                    <span class="badge bg-{{ $rentOut->status->color() }} text-uppercase">{{ $rentOut->status->label() }}</span>
                                @endif
                            </div>

                            <div class="row g-3">
                                <div class="col-4">
                                    <small class="text-muted d-block">Group</small>
                                    <strong>{{ $rentOut->group?->name ?? '-' }}</strong>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted d-block">Building</small>
                                    <strong>{{ $rentOut->building?->name ?? '-' }}</strong>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted d-block">Type</small>
                                    <strong>{{ $rentOut->type?->name ?? '-' }}</strong>
                                </div>
                            </div>
                            <hr class="my-2">
                            <div class="row g-3">
                                <div class="col-4">
                                    <small class="text-muted d-block">Property No/Unit</small>
                                    <strong>{{ $rentOut->property?->number ?? '-' }}</strong>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted d-block">Start Date</small>
                                    <strong>{{ $rentOut->start_date?->format('d-m-Y') }}</strong>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted d-block">End Date</small>
                                    <strong>{{ $rentOut->end_date?->format('d-m-Y') }}</strong>
                                </div>
                            </div>
                            <hr class="my-2">
                            <div class="row g-3">
                                <div class="col-4">
                                    <small class="text-muted d-block">Rent</small>
                                    <strong>{{ number_format($rentOut->rent, 2) }}</strong>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted d-block">No of Paid Month(s)</small>
                                    @php
                                        $paidMonths = $rentOut->rent > 0 ? floor($rentOut->total_paid / $rentOut->rent) : 0;
                                    @endphp
                                    <strong>{{ $paidMonths }}</strong>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted d-block">Management Fee</small>
                                    <strong>{{ number_format($rentOut->management_fee, 2) }}</strong>
                                </div>
                            </div>
                            <hr class="my-2">
                            <div class="row g-3">
                                <div class="col-4">
                                    <small class="text-muted d-block">Total Rent To Pay</small>
                                    <strong>{{ number_format($rentOut->total, 2) }}</strong>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted d-block">Total Current Rent</small>
                                    <strong>{{ number_format($rentOut->total_current_rent, 2) }}</strong>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted d-block">Total Paid</small>
                                    <strong>{{ number_format($rentOut->total_paid, 2) }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ═══ Payments Table ═══ --}}
                    <div class="border-top p-4">
                        <h5 class="fw-bold mb-3">Payments</h5>
                        <div class="table-responsive">
                            <table class="table table-hover table-sm align-middle border mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="fw-semibold">#</th>
                                        <th class="fw-semibold">Date</th>
                                        <th class="fw-semibold">Payment Mode</th>
                                        <th class="fw-semibold">Remarks</th>
                                        <th class="fw-semibold text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $payments = $rentOut->rentOutTransactions->where('credit', '>', 0)->sortByDesc('date');
                                    @endphp
                                    <tr class="fw-bold bg-light">
                                        <td>{{ $payments->count() }}</td>
                                        <td colspan="3"></td>
                                        <td class="text-end">{{ number_format($payments->sum('credit'), 2) }}</td>
                                    </tr>
                                    @forelse($payments as $index => $payment)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $payment->date?->format('d-m-Y') }}</td>
                                            <td>{{ strtoupper($payment->source ?? '-') }}</td>
                                            <td>{{ $payment->remark }}</td>
                                            <td class="text-end">{{ number_format($payment->credit, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-3">No payments found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- ═══ Bottom Section: Payment Terms, Cheque List, Utility Terms ═══ --}}
                    <div class="border-top p-4">
                        <div class="row g-4">
                            {{-- Payment Terms --}}
                            <div class="col-md-4">
                                <h5 class="fw-bold mb-3">Payment Terms</h5>
                                <div class="table-responsive">
                                    <table class="table table-hover table-sm align-middle border mb-0">
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="fw-semibold">#</th>
                                                <th class="fw-semibold">Date</th>
                                                <th class="fw-semibold text-end">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php $paymentTerms = $rentOut->paymentTerms->sortBy('due_date'); @endphp
                                            <tr class="fw-bold bg-light">
                                                <td>{{ $paymentTerms->count() }}</td>
                                                <td></td>
                                                <td class="text-end">{{ number_format($paymentTerms->sum('total'), 2) }}</td>
                                            </tr>
                                            @forelse($paymentTerms as $term)
                                                <tr class="{{ $term->status === 'paid' ? 'table-success' : '' }}">
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td>{{ $term->due_date?->format('d-m-Y') }}</td>
                                                    <td class="text-end">{{ number_format($term->total, 2) }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted py-2">No terms</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            {{-- Cheque List --}}
                            <div class="col-md-4">
                                <h5 class="fw-bold mb-3">Cheque List</h5>
                                <div class="table-responsive">
                                    <table class="table table-hover table-sm align-middle border mb-0">
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="fw-semibold">#</th>
                                                <th class="fw-semibold">Date</th>
                                                <th class="fw-semibold">Bank Name</th>
                                                <th class="fw-semibold">Cheque No</th>
                                                <th class="fw-semibold">Status</th>
                                                <th class="fw-semibold text-end">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php $cheques = $rentOut->cheques->sortBy('date'); @endphp
                                            <tr class="fw-bold bg-light">
                                                <td>{{ $cheques->count() }}</td>
                                                <td colspan="4"></td>
                                                <td class="text-end">{{ number_format($cheques->sum('amount'), 2) }}</td>
                                            </tr>
                                            @forelse($cheques as $cheque)
                                                <tr class="{{ $cheque->status?->value === 'cleared' ? 'table-success' : '' }}">
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td>{{ $cheque->date?->format('d-m-Y') }}</td>
                                                    <td>{{ $cheque->bank_name }}</td>
                                                    <td>{{ $cheque->cheque_no }}</td>
                                                    <td>{{ $cheque->status?->label() }}</td>
                                                    <td class="text-end">{{ number_format($cheque->amount, 2) }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="text-center text-muted py-2">No cheques</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            {{-- Utility Terms --}}
                            <div class="col-md-4">
                                <h5 class="fw-bold mb-3">Utility Terms</h5>
                                <div class="table-responsive">
                                    <table class="table table-hover table-sm align-middle border mb-0">
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="fw-semibold">#</th>
                                                <th class="fw-semibold">Date</th>
                                                <th class="fw-semibold">Utility</th>
                                                <th class="fw-semibold text-end">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php $utilityTerms = $rentOut->utilityTerms->sortBy('date'); @endphp
                                            <tr class="fw-bold bg-light">
                                                <td>{{ $utilityTerms->count() }}</td>
                                                <td colspan="2"></td>
                                                <td class="text-end">{{ number_format($utilityTerms->sum('amount'), 2) }}</td>
                                            </tr>
                                            @forelse($utilityTerms as $utilityTerm)
                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td>{{ $utilityTerm->date?->format('d-m-Y') }}</td>
                                                    <td>{{ $utilityTerm->utility?->name ?? '-' }}</td>
                                                    <td class="text-end">{{ number_format($utilityTerm->amount, 2) }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted py-2">No utility terms</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="card shadow-sm">
                <div class="card-body text-center py-5 text-muted">
                    <i class="fa fa-inbox fa-2x mb-2 d-block opacity-50"></i>
                    No rentout records found. Try adjusting your filters.
                </div>
            </div>
        @endforelse
    @else
        <div class="card shadow-sm">
            <div class="card-body text-center py-5 text-muted">
                <i class="fa fa-filter fa-2x mb-2 d-block opacity-50"></i>
                Select filters and click <strong>Fetch</strong> to view the report.
            </div>
        </div>
    @endif

    @push('scripts')
        <script>
            $(document).ready(function() {
                function clearAndReload(id) {
                    var el = document.getElementById(id);
                    if (el && el.tomSelect) {
                        el.tomSelect.clear();
                        el.tomSelect.clearOptions();
                        el.tomSelect.load('');
                    }
                }

                $('#cp_filterCustomer').on('change', function(e) {
                    @this.set('filterCustomer', $(this).val() || '');
                });
                $('#cp_filterGroup').on('change', function(e) {
                    @this.set('filterGroup', $(this).val() || '');
                    clearAndReload('cp_filterBuilding');
                    clearAndReload('cp_filterProperty');
                    @this.set('filterBuilding', '');
                    @this.set('filterProperty', '');
                });
                $('#cp_filterBuilding').on('change', function(e) {
                    @this.set('filterBuilding', $(this).val() || '');
                    clearAndReload('cp_filterProperty');
                    @this.set('filterProperty', '');
                });
                $('#cp_filterType').on('change', function(e) {
                    @this.set('filterType', $(this).val() || '');
                });
                $('#cp_filterProperty').on('change', function(e) {
                    @this.set('filterProperty', $(this).val() || '');
                });
            });
        </script>
    @endpush
</div>
