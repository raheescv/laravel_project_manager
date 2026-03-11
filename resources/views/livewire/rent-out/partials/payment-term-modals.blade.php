{{-- Single Payment Term Modal --}}
@if($showSingleTermModal)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">{{ $editingTermId ? 'Edit' : 'Single' }} Payment Term</h5>
                    <button type="button" class="btn-close" wire:click="$set('showSingleTermModal', false)"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Date *</label>
                            <input type="date" class="form-control" wire:model="singleTerm.due_date">
                            @error('singleTerm.due_date') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Label</label>
                            <select class="form-select" wire:model="singleTerm.label">
                                <option value="">Select Any</option>
                                @foreach(paymentTermLabels() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ $isRental ? 'Rent' : 'Amount' }} *</label>
                            <input type="number" class="form-control" wire:model="singleTerm.amount" step="0.01">
                            @error('singleTerm.amount') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Discount</label>
                            <input type="number" class="form-control" wire:model="singleTerm.discount" step="0.01">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Remark</label>
                            <textarea class="form-control" wire:model="singleTerm.remarks" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="$set('showSingleTermModal', false)">Close</button>
                    <button type="button" class="btn btn-success" wire:click="saveSingleTerm">
                        <i class="fa fa-check me-1"></i> Save
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif

{{-- Multiple Payment Term Modal --}}
@if($showMultipleTermModal)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Multiple Payment Term</h5>
                    <button type="button" class="btn-close" wire:click="$set('showMultipleTermModal', false)"></button>
                </div>
                <div class="modal-body">
                    {{-- Agreement Info --}}
                    <table class="table table-bordered table-sm mb-3">
                        <tbody>
                            <tr><td class="fw-bold" style="width:40%;">{{ $isRental ? 'RENT' : 'SALE PRICE' }}</td><td class="text-end">{{ number_format($rentOut->rent, 2) }}</td></tr>
                            <tr><td class="fw-bold">NO OF TERMS</td><td class="text-end">{{ $rentOut->no_of_terms }}</td></tr>
                            <tr><td class="fw-bold">PAYMENT FREQUENCY</td><td class="text-end">{{ strtoupper($rentOut->payment_frequency) }}</td></tr>
                            <tr><td class="fw-bold">START DATE</td><td class="text-end">{{ $rentOut->start_date?->format('d-m-Y') }}</td></tr>
                            <tr><td class="fw-bold">END DATE</td><td class="text-end">{{ $rentOut->end_date?->format('d-m-Y') }} <em class="text-muted">GENERATE DATES ONLY UP TO THIS DATE</em></td></tr>
                        </tbody>
                    </table>

                    {{-- Form Fields --}}
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">From Date *</label>
                            <input type="date" class="form-control" wire:model.live="multipleTermFromDate">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">No Of Terms</label>
                            <input type="number" class="form-control" wire:model.live="multipleTermNoOfTerms">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">{{ $isRental ? 'Rent' : 'Amount' }} *</label>
                            <input type="number" class="form-control" wire:model.live="multipleTermRent" step="0.01">
                        </div>
                    </div>

                    {{-- Generated Terms Preview --}}
                    @if(count($multipleTermList))
                        <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                            <table class="table table-bordered table-sm table-striped">
                                <thead class="table-dark sticky-top">
                                    <tr>
                                        <th>#</th>
                                        <th>Date</th>
                                        <th class="text-end">{{ $isRental ? 'Rent' : 'Amount' }}</th>
                                        <th class="text-end">Discount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($multipleTermList as $index => $item)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ date('d-m-Y', strtotime($item['date'])) }}</td>
                                            <td class="text-end">{{ number_format($item['rent'], 2) }}</td>
                                            <td class="text-end">{{ number_format($item['discount'] ?? 0, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center text-muted py-3">No terms to generate.</div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="$set('showMultipleTermModal', false)">
                        <i class="fa fa-times me-1"></i> Close
                    </button>
                    <button type="button" class="btn btn-success" wire:click="saveMultipleTerms" @if(!count($multipleTermList)) disabled @endif>
                        <i class="fa fa-check me-1"></i> Save
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif

{{-- Pay Selected Modal --}}
@if($showPaySelectedModal)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Payment Terms & their Cheques <small class="text-muted">if any</small></h5>
                    <button type="button" class="btn-close" wire:click="$set('showPaySelectedModal', false)"></button>
                </div>
                <div class="modal-body">
                    <h6 class="fw-bold mb-3">Non Cheque Table</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Date</label>
                            <input type="date" class="form-control" wire:model="payDate">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Payment Mode</label>
                            <select class="form-select" wire:model="payPaymentMode">
                                @foreach(paymentModeOptions() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Remark</label>
                            <input type="text" class="form-control" wire:model="payRemark" placeholder="Enter any Remark here">
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Date</th>
                                    <th>Customer</th>
                                    <th>Property</th>
                                    <th class="text-end">Balance</th>
                                    <th>Payment Mode</th>
                                    <th class="text-end">Amount</th>
                                    <th>Remark</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($cashTerms as $index => $ct)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $ct['date'] }}</td>
                                        <td>{{ $ct['customer'] }}</td>
                                        <td>{{ $ct['property'] }}</td>
                                        <td class="text-end">{{ number_format($ct['balance'], 2) }}</td>
                                        <td>
                                            <select class="form-select form-select-sm" wire:model="cashTerms.{{ $index }}.payment_mode">
                                                @foreach(paymentModeOptions() as $value => $label)
                                                    <option value="{{ $value }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm text-end" wire:model="cashTerms.{{ $index }}.amount" step="0.01" style="width:100px;">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm" wire:model="cashTerms.{{ $index }}.remark">
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="8" class="text-center text-muted py-3">No pending payment terms selected</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="$set('showPaySelectedModal', false)">Close</button>
                    <button type="button" class="btn btn-success" wire:click="submitPayment">
                        <i class="fa fa-check me-1"></i> Submit
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif
