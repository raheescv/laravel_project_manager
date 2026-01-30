<div>
    <form wire:submit.prevent="save">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h3 class="m-t-none m-b"> {{ $name ?? '' }} : Purchase Payment Form</h3>
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
                                    <label class="form-label text-muted fw-semibold small mb-2" for="date">
                                        <i class="demo-psi-calendar-4 me-1"></i> Date
                                    </label>
                                    {{ html()->date('date')->class('form-control form-control-sm')->id('date')->required(true)->attribute('wire:model', 'payment.date') }}
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="form-label text-muted fw-semibold small mb-2" for="amount">
                                        <i class="demo-pli-money me-1"></i> Amount
                                    </label>
                                    {{ html()->number('amount')->class('form-control form-control-sm number')->id('amount')->attribute('step', 'any')->required(true)->attribute('wire:model.live', 'payment.amount') }}
                                </div>
                            </div>
                            <div class="col-md-4" wire:ignore>
                                <div class="form-group">
                                    <label class="form-label text-muted fw-semibold small mb-2" for="payment_method_id">
                                        <i class="demo-pli-credit-card-2 me-1"></i> Payment Mode
                                    </label>
                                    {{ html()->select('payment_method_id', $paymentMethods)->value($default_payment_method_id)->class('select-payment_method_id-list')->id('payment_method_id')->placeholder('Select Payment Method') }}
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="col-md-12"> <br>
                                    <button type="submit" class="btn btn-sm btn-success" name="button">
                                        <i class="demo-pli-check me-1"></i> Save
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="form-label text-muted fw-semibold small mb-2" for="remarks">
                                        <i class="demo-pli-file-edit me-1"></i> Remarks
                                    </label>
                                    {{ html()->text('remarks')->class('form-control form-control-sm')->id('remarks')->attribute('wire:model', 'payment.remarks') }}
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
                                            <input type="checkbox" class="form-check-input" wire:model.live="checkAll" id="checkAll">
                                            <label class="form-check-label" for="checkAll"></label>
                                        </div>
                                        <span>ID</span>
                                    </div>
                                </th>
                                <th>Invoice No</th>
                                <th class="text-nowrap text-end">Purchase Amount</th>
                                <th class="text-nowrap text-end">Discount</th>
                                <th class="text-nowrap text-end">Grand Total</th>
                                <th class="text-nowrap text-end">Paid</th>
                                <th class="text-nowrap text-end">Balance</th>
                                <th class="text-nowrap text-end">Discount</th>
                                <th class="text-nowrap text-end">Payment</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($data as $item)
                                <tr wire:key="item-{{ $item->id }}">
                                    @php
                                        $vendorPayment = $vendor_purchases[$item->id];
                                    @endphp
                                    <td class="ps-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="form-check mb-0">
                                                <input type="checkbox" class="form-check-input" wire:model="vendor_purchases.{{ $item->id }}.selected" wire:change="selectAction({{ $item->id }})"
                                                    value="{{ $item->id }}">
                                            </div>
                                            <span class="text-muted">#{{ $item->id }}</span>
                                        </div>
                                    </td>
                                    <td class="text-nowrap">
                                        <a href="{{ route('purchase::edit', $item->id) }}" class="text-primary fw-semibold text-decoration-none">
                                            {{ $item->invoice_no }}
                                        </a>
                                    </td>
                                    <td>
                                        <div class="text-end fw-medium">{{ currency($item->total) }}</div>
                                    </td>
                                    <td>
                                        <div class="text-end text-danger fw-medium">
                                            {{ $item->other_discount != 0 ? '-' : '' }}{{ $item->other_discount != 0 ? currency($item->other_discount) : '_' }}</div>
                                    </td>
                                    <td>
                                        <div class="text-end fw-bold text-primary">{{ currency($item->grand_total) }}</div>
                                    </td>
                                    <td>
                                        <div class="text-end text-success fw-semibold">{{ currency($item->paid) }}</div>
                                    </td>
                                    <td>
                                        <div class="text-end text-danger fw-semibold">{{ $item->balance != 0 ? currency($item->balance) : '_' }}</div>
                                    </td>
                                    @php
                                        $disabled = '';
                                        if (!$vendor_purchases[$item->id]['selected']) {
                                            $disabled = 'disabled';
                                        }
                                    @endphp
                                    <td>
                                        {{ html()->number('discount')->value($vendorPayment['discount'])->class('form-control form-control-sm number select_on_focus')->attribute('step', 'any')->attribute('max', $item['balance'] - $vendorPayment['payment'])->attribute($disabled)->attribute('wire:model.live', 'vendor_purchases.' . $item['id'] . '.discount') }}
                                    </td>
                                    <td>
                                        {{ html()->number('payment')->value($vendorPayment['payment'])->class('form-control form-control-sm number select_on_focus')->attribute('step', 'any')->attribute('max', $item['balance'] - $vendorPayment['discount'])->attribute($disabled)->attribute('wire:model.live', 'vendor_purchases.' . $item['id'] . '.payment') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-group-divider">
                            <tr class="bg-light">
                                <th class="ps-3"><strong>TOTALS</strong></th>
                                <th></th>
                                <th>
                                    <div class="text-end fw-bold">{{ currency($total['total']) }}</div>
                                </th>
                                <th>
                                    <div class="text-end text-danger fw-bold">-{{ currency($total['other_discount']) }}</div>
                                </th>
                                <th>
                                    <div class="text-end fw-bold text-primary">{{ currency($total['grand_total']) }}</div>
                                </th>
                                <th>
                                    <div class="text-end text-success fw-bold">{{ currency($total['paid']) }}</div>
                                </th>
                                <th>
                                    <div class="text-end text-danger fw-bold">{{ currency($total['balance']) }}</div>
                                </th>
                                <th>
                                    <div class="text-end text-danger fw-bold">-{{ currency(array_sum(array_column($vendor_purchases, 'discount'))) }}</div>
                                </th>
                                <th>
                                    <div class="text-end text-success fw-bold">{{ currency(array_sum(array_column($vendor_purchases, 'payment'))) }}</div>
                                </th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </form>
    @push('scripts')
        <script>
            $('#payment_method_id').on('change', function(e) {
                const value = $(this).val() || null;
                const text = $(this).text() || null;
                @this.set('payment.payment_method_id', value);
                $('#payment').select();
            });
        </script>
    @endpush
</div>
