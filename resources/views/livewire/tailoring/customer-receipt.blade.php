<div>
    <form wire:submit.prevent="save">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h3 class="m-t-none m-b">{{ $display_name ?: 'Customer' }} : Tailoring Receipt</h3>
                @if ($this->getErrorBag()->count())
                    <ol>
                        @foreach ($this->getErrorBag()->toArray() as $value)
                            <li style="color:red">* {{ $value[0] }}</li>
                        @endforeach
                    </ol>
                @endif
            </div>
            <div class="modal-body">
                <div class="bg-light rounded-3 border shadow-sm mb-3">
                    <div class="p-3">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="form-label text-muted fw-semibold small mb-2" for="tailoring_receipt_date">
                                        <i class="demo-psi-calendar-4 me-1"></i> Date
                                    </label>
                                    <input type="date" wire:model="payment.date" id="tailoring_receipt_date" class="form-control form-control-sm" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="form-label text-muted fw-semibold small mb-2" for="tailoring_receipt_amount">
                                        <i class="demo-pli-money me-1"></i> Amount
                                    </label>
                                    <input type="number" step="any" wire:model.lazy="payment.amount" id="tailoring_receipt_amount" class="form-control form-control-sm number" required>
                                </div>
                            </div>
                            <div class="col-md-4" wire:ignore>
                                <div class="form-group">
                                    <label class="form-label text-muted fw-semibold small mb-2" for="tailoring_receipt_payment_method_id">
                                        <i class="demo-pli-credit-card-2 me-1"></i> Payment Mode
                                    </label>
                                    {{ html()->select('payment_method_id', $paymentMethods)->value($default_payment_method_id)->class('select-payment_method_id-list')->id('tailoring_receipt_payment_method_id')->placeholder('Select Payment Method') }}
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="col-md-12"><br>
                                    <button type="submit" class="btn btn-sm btn-success" name="button">
                                        <i class="demo-pli-check me-1"></i> Save
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="form-label text-muted fw-semibold small mb-2" for="tailoring_receipt_remarks">
                                        <i class="demo-pli-file-edit me-1"></i> Remarks
                                    </label>
                                    <input type="text" wire:model="payment.remarks" id="tailoring_receipt_remarks" class="form-control form-control-sm">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-sm align-middle mb-0 border-bottom">
                        <thead class="bg-light text-nowrap">
                            <tr>
                                <th class="ps-3">
                                    <div class="d-flex align-items-center">
                                        <div class="form-check me-2">
                                            <input type="checkbox" class="form-check-input" wire:model.live="checkAll" id="tailoring_checkAll">
                                            <label class="form-check-label" for="tailoring_checkAll"></label>
                                        </div>
                                        <span>ID</span>
                                    </div>
                                </th>
                                <th>Order No</th>
                                <th class="text-nowrap text-end">Amount</th>
                                <th class="text-nowrap text-end">Discount</th>
                                <th class="text-nowrap text-end">Grand Total</th>
                                <th class="text-nowrap text-end">Paid</th>
                                <th class="text-nowrap text-end">Balance</th>
                                <th class="text-nowrap text-end">Payment</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($data as $item)
                                <tr wire:key="order-{{ $item->id }}">
                                    @php
                                        $row = $order_orders[$item->id] ?? ['balance' => 0, 'payment' => 0, 'selected' => false];
                                        $disabled = $row['selected'] ? '' : 'disabled';
                                    @endphp
                                    <td class="ps-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="form-check mb-0">
                                                <input type="checkbox" class="form-check-input" wire:model="order_orders.{{ $item->id }}.selected" wire:change="selectAction({{ $item->id }})" value="1">
                                            </div>
                                            <span class="text-muted">#{{ $item->id }}</span>
                                        </div>
                                    </td>
                                    <td class="text-nowrap">
                                        <a href="{{ route('tailoring::order::show', $item->id) }}" class="text-primary fw-semibold text-decoration-none">
                                            {{ $item->order_no }}
                                        </a>
                                    </td>
                                    <td><div class="text-end fw-medium">{{ currency($item->total) }}</div></td>
                                    <td><div class="text-end text-danger fw-medium">{{ $item->other_discount != 0 ? '-' . currency($item->other_discount) : '_' }}</div></td>
                                    <td><div class="text-end fw-bold text-primary">{{ currency($item->grand_total) }}</div></td>
                                    <td><div class="text-end text-success fw-semibold">{{ currency($item->paid) }}</div></td>
                                    <td><div class="text-end text-danger fw-semibold">{{ $item->balance != 0 ? currency($item->balance) : '_' }}</div></td>
                                    <td>
                                        <input type="number" step="any" class="form-control form-control-sm number" min="0" max="{{ $row['balance'] }}"
                                            wire:model.live="order_orders.{{ $item->id }}.payment" {{ $disabled }}>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">No orders with balance for this customer.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if (count($data) > 0)
                            <tfoot class="table-group-divider">
                                <tr class="bg-light">
                                    <th class="ps-3"><strong>TOTALS</strong></th>
                                    <th></th>
                                    <th><div class="text-end fw-bold">{{ currency($total['total']) }}</div></th>
                                    <th><div class="text-end text-danger fw-bold">-{{ currency($total['other_discount']) }}</div></th>
                                    <th><div class="text-end fw-bold text-primary">{{ currency($total['grand_total']) }}</div></th>
                                    <th><div class="text-end text-success fw-bold">{{ currency($total['paid']) }}</div></th>
                                    <th><div class="text-end text-danger fw-bold">{{ currency($total['balance']) }}</div></th>
                                    <th><div class="text-end text-success fw-bold">{{ currency(collect($order_orders)->sum('payment')) }}</div></th>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </form>
    @push('scripts')
        <script>
            $('#tailoring_receipt_payment_method_id').on('change', function() {
                const value = $(this).val() || null;
                @this.set('payment.payment_method_id', value);
            });

            window.addEventListener('print-tailoring-customer-receipt', event => {
                const data = event.detail[0];
                const params = new URLSearchParams({
                    customer_name: data.customer_name || '',
                    payment_date: data.payment_date || '',
                    payment_method_id: data.payment_method || '',
                    total_amount: data.total_amount || 0,
                    receipt_data: JSON.stringify(data.receipt_data || []),
                    payment_ids: JSON.stringify(data.payment_ids || [])
                });
                const printUrl = '{{ route("print::tailoring::customer-receipt") }}?' + params.toString();
                const printWindow = window.open(printUrl, '_blank', 'width=300,height=600');
                if (printWindow) printWindow.focus();
            });
        </script>
    @endpush
</div>
