<div>
    {{-- Header --}}
    <div class="modal-header py-3 px-4 text-white border-0"
        style="background: linear-gradient(135deg, #2e7d56, #1a5c3a);">
        <h6 class="modal-title fw-bold mb-0">
            <i class="fa fa-bolt me-2"></i> Pay Selected Utility Terms
        </h6>
        <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal"></button>
    </div>

    {{-- Body --}}
    <div class="modal-body p-3 p-md-4">
        {{-- Payment Summary Card --}}
        <div class="card border-0 mb-3" style="background: linear-gradient(135deg, #f0f7f3, #e8f5ed);">
            <div class="card-body p-3">
                <div class="row g-2 g-md-3 align-items-end">
                    <div class="col-6 col-md-2">
                        <label class="form-label fw-semibold small mb-1">
                            <i class="fa fa-credit-card me-1 text-muted"></i> Payment Mode
                        </label>
                        <select class="form-select form-select-sm border-secondary-subtle shadow-sm"
                            wire:model="payPaymentMode" wire:change="applyModeToAll">
                            @foreach ($paymentMethods as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-8 col-md-8">
                        <label class="form-label fw-semibold small mb-1">
                            <i class="fa fa-comment-o me-1 text-muted"></i> Remark
                        </label>
                        <input type="text"
                            class="form-control form-control-sm border-secondary-subtle shadow-sm"
                            wire:model="payRemark" wire:change="applyRemarkToAll"
                            placeholder="Enter any Remark here">
                    </div>
                    <div class="col-4 col-md-2 text-end">
                        <div class="rounded-3 p-2 text-center"
                            style="background: rgba(46,125,86,.1); border: 1px solid rgba(46,125,86,.2);">
                            <div class="text-muted"
                                style="font-size: .65rem; text-transform: uppercase; letter-spacing: .05em;">
                                Paying</div>
                            <div class="fw-bold" style="color: #2e7d56; font-size: 1.1rem;">
                                {{ number_format($this->payingTotal, 2) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Desktop Table View --}}
        <div class="d-none d-md-block">
            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-hover table-sm mb-0 align-middle">
                    <thead class="bg-light text-muted sticky-top" style="z-index: 1;">
                        <tr class="text-capitalize small">
                            <th class="fw-semibold py-2 text-center" style="width:35px;">#</th>
                            <th class="fw-semibold py-2">Date</th>
                            <th class="fw-semibold py-2">Customer</th>
                            <th class="fw-semibold py-2">Property</th>
                            <th class="fw-semibold py-2 text-end">Balance</th>
                            <th class="fw-semibold py-2">Utility</th>
                            <th class="fw-semibold py-2" style="min-width:130px;">Payment Mode</th>
                            <th class="fw-semibold py-2 text-end" style="min-width:110px;">Amount</th>
                            <th class="fw-semibold py-2" style="min-width:140px;">Remark</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cashTerms as $index => $ct)
                            <tr>
                                <td class="text-center py-1 text-muted small">{{ $index + 1 }}</td>
                                <td class="py-1 small">{{ $ct['date'] }}</td>
                                <td class="py-1 small">{{ $ct['customer'] }}</td>
                                <td class="py-1 small fw-medium">{{ $ct['property'] }}</td>
                                <td class="py-1 text-end">
                                    <span class="fw-bold text-danger">{{ number_format($ct['balance'], 2) }}</span>
                                </td>
                                <td class="py-1 small">{{ $ct['utility'] }}</td>
                                <td class="py-1">
                                    <select class="form-select form-select-sm border-secondary-subtle"
                                        wire:model="cashTerms.{{ $index }}.payment_mode">
                                        @foreach ($paymentMethods as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="py-1">
                                    <input type="number"
                                        class="form-control form-control-sm text-end border-secondary-subtle fw-medium"
                                        wire:model="cashTerms.{{ $index }}.amount"
                                        max="{{ $ct['balance'] }}"
                                        step="0.01" style="color: #2e7d56;">
                                </td>
                                <td class="py-1">
                                    <input type="text"
                                        class="form-control form-control-sm border-secondary-subtle"
                                        wire:model="cashTerms.{{ $index }}.remark"
                                        placeholder="Remark here">
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    <i class="fa fa-inbox d-block fs-3 mb-2 opacity-50"></i>
                                    <span class="small">No pending utility terms selected</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if (count($cashTerms) > 0)
                        <tfoot class="table-light sticky-bottom">
                            <tr class="fw-bold small">
                                <td colspan="4" class="py-2 text-end">Total</td>
                                <td class="py-2 text-end text-danger">
                                    {{ number_format($this->balanceTotal, 2) }}
                                </td>
                                <td colspan="2" class="py-2"></td>
                                <td class="py-2 text-end" style="color: #2e7d56;">
                                    {{ number_format($this->payingTotal, 2) }}
                                </td>
                                <td class="py-2"></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>

        {{-- Mobile Card View --}}
        <div class="d-md-none" style="max-height: 400px; overflow-y: auto;">
            @forelse($cashTerms as $index => $ct)
                <div class="card border mb-2 shadow-sm">
                    <div class="card-body p-2">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge bg-secondary bg-opacity-10 text-dark small">
                                #{{ $index + 1 }} &bull; {{ $ct['date'] }}
                            </span>
                            <span class="fw-bold text-danger small">{{ number_format($ct['balance'], 2) }}</span>
                        </div>
                        <div class="small text-muted mb-2">
                            {{ $ct['customer'] }}
                            @if ($ct['property'])
                                <span class="ms-1 fw-medium text-dark">&bull; {{ $ct['property'] }}</span>
                            @endif
                            &bull; {{ $ct['utility'] }}
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label text-muted mb-0" style="font-size:.7rem;">Mode</label>
                                <select class="form-select form-select-sm border-secondary-subtle"
                                    wire:model="cashTerms.{{ $index }}.payment_mode">
                                    @foreach ($paymentMethods as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label text-muted mb-0" style="font-size:.7rem;">Amount</label>
                                <input type="number"
                                    class="form-control form-control-sm text-end border-secondary-subtle fw-medium"
                                    wire:model="cashTerms.{{ $index }}.amount" step="0.01"
                                    style="color: #2e7d56;">
                            </div>
                            <div class="col-12">
                                <label class="form-label text-muted mb-0" style="font-size:.7rem;">Remark</label>
                                <input type="text"
                                    class="form-control form-control-sm border-secondary-subtle"
                                    wire:model="cashTerms.{{ $index }}.remark"
                                    placeholder="Remark here">
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center text-muted py-4">
                    <i class="fa fa-inbox d-block fs-3 mb-2 opacity-50"></i>
                    <span class="small">No pending utility terms selected</span>
                </div>
            @endforelse
            @if (count($cashTerms) > 0)
                <div class="card border-0 bg-light mt-2">
                    <div class="card-body p-2 d-flex justify-content-between small fw-bold">
                        <span>Total Balance: <span class="text-danger">{{ number_format($this->balanceTotal, 2) }}</span></span>
                        <span>Paying: <span style="color:#2e7d56;">{{ number_format($this->payingTotal, 2) }}</span></span>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Footer --}}
    <div class="modal-footer py-2 px-3 px-md-4 border-top bg-light flex-wrap gap-2">
        @if (count($cashTerms) > 0)
            <span class="me-auto small text-muted">
                <i class="fa fa-info-circle me-1"></i>
                {{ count($cashTerms) }} term(s) selected
            </span>
        @endif
        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">
            <i class="fa fa-times me-1"></i> Close
        </button>
        <button type="button" class="btn btn-sm btn-success shadow-sm" wire:click="submit"
            @if ($saving || count($cashTerms) === 0) disabled @endif>
            <i class="fa fa-check me-1"></i>
            {{ $saving ? 'Submitting...' : 'Submit' }}
        </button>
    </div>
</div>
