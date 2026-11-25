<div>
    {{-- KPIs --}}
    <div class="row g-2 mb-3">
        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="text-muted small text-uppercase fw-semibold" style="letter-spacing:.04em;font-size:.7rem;">Waiting on me</div>
                    <div class="h5 mb-0 fw-bold text-primary">{{ number_format($kpis['waiting']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="text-muted small text-uppercase fw-semibold" style="letter-spacing:.04em;font-size:.7rem;">Approved, Unpaid</div>
                    <div class="h5 mb-0 fw-bold text-danger">{{ number_format($kpis['unpaid'], 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="text-muted small text-uppercase fw-semibold" style="letter-spacing:.04em;font-size:.7rem;">Paid</div>
                    <div class="h5 mb-0 fw-bold text-success">{{ number_format($kpis['paid'], 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="text-muted small text-uppercase fw-semibold" style="letter-spacing:.04em;font-size:.7rem;">Total Commission</div>
                    <div class="h5 mb-0 fw-bold">{{ number_format($kpis['total'], 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-light py-3">
            <div class="d-flex flex-wrap gap-1 mb-2">
                @foreach ($statusTabs as $value => $label)
                    <button type="button" class="btn btn-sm {{ $filterStatus === $value ? 'btn-primary' : 'btn-outline-secondary' }}"
                        wire:click="$set('filterStatus', '{{ $value }}')">{{ $label }}</button>
                @endforeach
            </div>
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="form-label mb-0 text-muted small fw-semibold">From</label>
                    <input type="date" class="form-control form-control-sm" wire:model.live="dateFrom">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label mb-0 text-muted small fw-semibold">To</label>
                    <input type="date" class="form-control form-control-sm" wire:model.live="dateTo">
                </div>
                <div class="col-12 col-md-3" wire:ignore wire:key="commissions_filterAgent">
                    <label class="form-label mb-0 text-muted small fw-semibold">Salesman</label>
                    {{ html()->select('agentId', [])->value('')->class('select-employee_id-list')->id('commissions_filterAgent')->placeholder('All salesmen') }}
                </div>
                <div class="col-12 col-md">
                    <label class="form-label mb-0 text-muted small fw-semibold">Search</label>
                    <input type="text" class="form-control form-control-sm" wire:model.live.debounce.300ms="search" placeholder="Salesman / customer / unit / invoice no...">
                </div>
                <div class="col-auto">
                    <select wire:model.live="limit" class="form-select form-select-sm" style="width:auto;">
                        <option value="20">20</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="button" class="btn btn-sm btn-primary" wire:click="approveSelected" @disabled(! count($selected))
                        wire:confirm="Approve the selected commissions at your approval step?">
                        <i class="fa fa-check me-1"></i> Approve selected ({{ count($selected) }})
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr class="small text-muted">
                            <th class="ps-3" style="width:30px;">
                                @if (count($approvableIds))
                                    <input type="checkbox" class="form-check-input" title="Select all waiting on me"
                                        wire:click="toggleSelectAll"
                                        @checked(! array_diff($approvableIds, $selected))>
                                @endif
                            </th>
                            <th>Date</th>
                            <th>Agreement</th>
                            <th>Salesman</th>
                            <th class="d-none d-md-table-cell">Property</th>
                            <th class="d-none d-md-table-cell">Customer</th>
                            <th class="text-end d-none d-lg-table-cell">Contract</th>
                            <th class="text-end">%</th>
                            <th class="text-end">Commission</th>
                            <th>Status</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data as $commission)
                            @php $rentOut = $commission->rentOut; @endphp
                            <tr wire:key="commission-row-{{ $commission->id }}">
                                <td class="ps-3">
                                    @if ($this::nextStageFor($commission))
                                        <input type="checkbox" class="form-check-input" value="{{ $commission->id }}" wire:model.live="selected">
                                    @endif
                                </td>
                                <td class="small text-nowrap">{{ $commission->date?->format('d-m-Y') }}</td>
                                <td class="small text-nowrap">
                                    <a href="{{ route('property::sale::view', $commission->rent_out_id) }}?tab=commission" class="text-decoration-none">{{ $rentOut?->agreement_no }}</a>
                                </td>
                                <td class="small text-nowrap">{{ $commission->agent?->name }}</td>
                                <td class="small d-none d-md-table-cell">{{ $rentOut?->property?->building?->name }}{{ $rentOut?->property ? ' · ' . $rentOut->property->number : '' }}</td>
                                <td class="small d-none d-md-table-cell">{{ $rentOut?->customer?->name }}</td>
                                <td class="small text-end d-none d-lg-table-cell">{{ number_format($commission->contract_amount, 2) }}</td>
                                <td class="small text-end">{{ $commission->commission_type === 'percentage' ? number_format($commission->commission_percentage, 2) : '-' }}</td>
                                <td class="small text-end fw-bold">{{ number_format($commission->amount, 2) }}</td>
                                <td class="text-nowrap">
                                    <span class="badge bg-{{ $commission->status->color() }} bg-opacity-75 small" @if ($commission->rejection_reason) title="{{ $commission->rejection_reason }}" @endif>{{ $commission->status->label() }}</span>
                                    @if ($commission->invoice_no)
                                        <div class="text-muted" style="font-size: .65rem;">{{ $commission->invoice_no }}</div>
                                    @endif
                                </td>
                                <td class="text-center text-nowrap">
                                    @include('livewire.rent-out.partials.commission-actions', ['commission' => $commission, 'canReject' => $canReject, 'canEdit' => false])
                                    @if ($commission->invoice_no)
                                        <a href="{{ route('print::rentout::commission', ['kind' => 'invoice', 'id' => $commission->id]) }}" target="_blank"
                                            class="btn btn-sm btn-link text-secondary p-0 me-2" title="Print Commission Invoice"><i class="fa fa-file-text-o"></i></a>
                                    @endif
                                    @if ($commission->payment_journal_id)
                                        <a href="{{ route('print::rentout::commission', ['kind' => 'voucher', 'id' => $commission->id]) }}" target="_blank"
                                            class="btn btn-sm btn-link text-secondary p-0" title="Print Payment Voucher"><i class="fa fa-print"></i></a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center py-4 text-muted small">
                                    <i class="fa fa-inbox me-1"></i> No commissions for the selected filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($data->hasPages())
            <div class="card-footer bg-white">{{ $data->links() }}</div>
        @endif
    </div>
    @push('scripts')
        <script>
            $(document).ready(function() {
                $('#commissions_filterAgent').on('change', function() {
                    @this.set('agentId', $(this).val() || '');
                });
            });
        </script>
    @endpush
</div>
