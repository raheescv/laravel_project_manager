<div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        @php
            $totalPaid = collect($payments)->sum('amount');
        @endphp
        <div></div>
        <button class="btn btn-success btn-sm" wire:click="openModal">
            <i class="demo-psi-add me-2"></i>Add Payment
        </button>
    </div>

    @if (count($payments) > 0)
        <div class="table-responsive">
            <table class="table table-striped table-hover table-sm">
                <thead>
                    <tr>
                        <th width="20%">Date</th>
                        <th width="25%">Payment Method</th>
                        <th width="20%" class="text-end">Amount</th>
                        <th width="35%" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($payments as $index => $payment)
                        <tr>
                            <td>{{ systemDate($payment['date']) }}</td>
                            <td>
                                @if (isset($payment['payment_method']) && $payment['payment_method'])
                                    <i class="demo-psi-credit-card me-2"></i>{{ $payment['payment_method']['name'] ?? 'N/A' }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-end fw-bold">{{ currency($payment['amount']) }}</td>
                            <td class="text-end">
                                <button class="btn btn-xs btn-outline-primary" wire:click="openModal({{ $payment['id'] }})" title="Edit">
                                    <i class="demo-psi-pencil"></i>
                                </button>
                                <button class="btn btn-xs btn-outline-danger" wire:click="delete({{ $payment['id'] }})" wire:confirm="Are you sure you want to delete this payment?" title="Delete">
                                    <i class="demo-pli-recycling"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="fw-bold">
                        <td colspan="2" class="text-end">Total:</td>
                        <td class="text-end">{{ currency($totalPaid) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @else
        <div class="text-center py-5">
            <i class="demo-psi-wallet fs-1 text-muted mb-3 d-block"></i>
            <p class="text-muted">No payments added yet. Click "Add Payment" to get started.</p>
        </div>
    @endif

    <!-- Modal -->
    @if ($showModal)
        <div class="modal show d-block" style="background-color: rgba(0,0,0,0.5);" wire:click="closeModal">
            <div class="modal-dialog" wire:click.stop>
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ $editingId ? 'Edit Payment' : 'Add Payment' }}</h5>
                        <button type="button" class="btn-close" wire:click="closeModal"></button>
                    </div>
                    <form wire:submit.prevent="save">
                        <div class="modal-body">
                            <div class="mb-3" wire:ignore>
                                <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                                {{ html()->select('payment_method_id', $paymentMethods)->class('form-control')->id('payment_method_id')->attribute('wire:model.live', 'payment.payment_method_id')->required(true)->attribute('style', 'width:100%')->placeholder('Select Payment Method') }}
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label">Amount <span class="text-danger">*</span></label>
                                    <input type="number" wire:model="payment.amount" class="form-control" step="0.01" min="0.01">
                                    @error('payment.amount')
                                        <span class="text-danger small">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Date <span class="text-danger">*</span></label>
                                    <input type="date" wire:model="payment.date" class="form-control">
                                    @error('payment.date')
                                        <span class="text-danger small">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" wire:click="closeModal">Cancel</button>
                            <button type="submit" class="btn btn-success">Save Payment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
