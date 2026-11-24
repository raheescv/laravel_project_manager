<div>
    {{-- KPIs --}}
    <div class="row g-2 mb-3">
        <div class="col-12 col-sm-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="text-muted small text-uppercase fw-semibold" style="letter-spacing:.04em;font-size:.7rem;">Total Commission</div>
                    <div class="h5 mb-0 fw-bold">{{ number_format($totals['total'], 2) }}</div>
                    <div class="small text-muted">{{ number_format($totals['count']) }} commissions</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="text-muted small text-uppercase fw-semibold" style="letter-spacing:.04em;font-size:.7rem;">Paid</div>
                    <div class="h5 mb-0 fw-bold text-success">{{ number_format($totals['paid'], 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="text-muted small text-uppercase fw-semibold" style="letter-spacing:.04em;font-size:.7rem;">Unpaid</div>
                    <div class="h5 mb-0 fw-bold text-danger">{{ number_format($totals['unpaid'], 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-light py-3">
            <div class="row g-2 align-items-end">
                <div class="col-auto">
                    <button wire:click="download" class="btn btn-success btn-sm d-inline-flex align-items-center gap-1 shadow-sm">
                        <i class="fa fa-file-excel-o"></i> <span>Export Excel</span>
                    </button>
                </div>
                <div class="col-auto">
                    <div class="btn-group btn-group-sm shadow-sm" role="group">
                        @foreach (['' => 'All', 'unpaid' => 'Unpaid', 'paid' => 'Paid'] as $value => $label)
                            <button type="button" class="btn {{ $paid === $value ? 'btn-primary' : 'btn-outline-secondary' }}"
                                wire:click="$set('paid', '{{ $value }}')">{{ $label }}</button>
                        @endforeach
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label mb-0 text-muted small fw-semibold">From</label>
                    <input type="date" class="form-control form-control-sm" wire:model.live="dateFrom">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label mb-0 text-muted small fw-semibold">To</label>
                    <input type="date" class="form-control form-control-sm" wire:model.live="dateTo">
                </div>
                <div class="col-12 col-md-2" wire:ignore wire:key="commissionReport_filterAgent">
                    <label class="form-label mb-0 text-muted small fw-semibold">Salesman</label>
                    {{ html()->select('agentId', [])->value('')->class('select-employee_id-list')->id('commissionReport_filterAgent')->placeholder('All salesmen') }}
                </div>
                <div class="col-12 col-md">
                    <label class="form-label mb-0 text-muted small fw-semibold">Search</label>
                    <input type="text" class="form-control form-control-sm" wire:model.live.debounce.300ms="search" placeholder="Salesman / tenant / unit...">
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr class="small text-muted">
                            <th class="ps-3">Agent Name</th>
                            <th>Property</th>
                            <th>Contract No.</th>
                            <th>Tenant</th>
                            <th class="text-end">Commission %</th>
                            <th class="text-end">Commission Amount</th>
                            <th class="pe-3">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($groups as $agentName => $rows)
                            @foreach ($rows as $commission)
                                @php $rentOut = $commission->rentOut; @endphp
                                <tr wire:key="commission-report-{{ $commission->id }}">
                                    <td class="ps-3 small">{{ $agentName }}</td>
                                    <td class="small">{{ $rentOut?->property?->building?->name }}{{ $rentOut?->property ? ' · ' . $rentOut->property->number : '' }}</td>
                                    <td class="small text-nowrap">
                                        <a href="{{ route('property::sale::view', $commission->rent_out_id) }}?tab=commission" class="text-decoration-none">{{ $rentOut?->agreement_no }}</a>
                                    </td>
                                    <td class="small">{{ $rentOut?->customer?->name }}</td>
                                    <td class="small text-end">{{ $commission->commission_type === 'percentage' ? number_format($commission->commission_percentage, 2) : 'Fixed' }}</td>
                                    <td class="small text-end">{{ number_format($commission->amount, 2) }}</td>
                                    <td class="pe-3">
                                        @if ($commission->status === \App\Enums\RentOut\CommissionStatus::Paid)
                                            <span class="badge bg-success bg-opacity-75 small">Paid</span>
                                        @else
                                            <span class="badge bg-danger bg-opacity-75 small">Unpaid</span>
                                            <span class="text-muted" style="font-size:.65rem;">{{ $commission->status->label() }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            <tr class="table-light">
                                <td colspan="5" class="text-end small fw-semibold">{{ $agentName }} — subtotal</td>
                                <td class="text-end small fw-bold">{{ number_format($rows->sum('amount'), 2) }}</td>
                                <td></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted small">
                                    <i class="fa fa-inbox me-1"></i> No commissions for the selected filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($groups->isNotEmpty())
                        <tfoot class="table-light fw-bold border-top">
                            <tr>
                                <td colspan="5" class="text-end">Total</td>
                                <td class="text-end">{{ number_format($totals['total'], 2) }}</td>
                                <td class="small text-muted fw-normal pe-3">Paid {{ number_format($totals['paid'], 2) }} · Unpaid {{ number_format($totals['unpaid'], 2) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
    @push('scripts')
        <script>
            $(document).ready(function() {
                $('#commissionReport_filterAgent').on('change', function() {
                    @this.set('agentId', $(this).val() || '');
                });
            });
        </script>
    @endpush
</div>
