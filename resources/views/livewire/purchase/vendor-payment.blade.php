<div>
    <form wire:submit.prevent="save">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h3 class="m-t-none m-b"> {{ $name ?? '' }} : Purchase Payment Form</h3>
                @if ($this->getErrorBag()->count())
                    <ol>
                        <?php foreach ($this->getErrorBag()->toArray() as $value): ?>
                        <li style="color:red">* {{ $value[0] }}</li>
                        <?php endforeach; ?>
                    </ol>
                @endif
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="date">Date</label>
                            {{ html()->date('date')->class('form-control')->required(true)->attribute('wire:model', 'payment.date') }}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="amount">Amount</label>
                            {{ html()->number('amount')->class('form-control number')->attribute('step', 'any')->required(true)->attribute('wire:model.live', 'payment.amount') }}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group" wire:ignore>
                            <label for="payment_method_id">Payment Mode</label>
                            {{ html()->select('payment_method_id', $paymentMethods)->value($default_payment_method_id)->class('select-payment_method_id-list')->id('payment_method_id')->placeholder('Select Payment Method') }}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group"> <br>
                            <button type="submit" class="btn btn-success" name="button">Save</button>
                        </div>
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="remarks">Remarks</label>
                            {{ html()->text('remarks')->class('form-control')->attribute('wire:model', 'payment.remarks') }}
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="table-responsive">
                        <div class="dataTables_wrapper form-inline dt-bootstrap">
                            <table class="table table-bordered table-sm table-hover table-striped dataTable">
                                <thead>
                                    <tr>
                                        <th>
                                            <input type="checkbox" wire:model.live="checkAll">
                                            <span class="pull-right">ID</span>
                                        </th>
                                        <th>Invoice No</th>
                                        <th class="text-right">Purchase Amount</th>
                                        <th class="text-right">Discount</th>
                                        <th class="text-right">Grand Total</th>
                                        <th class="text-right">Paid</th>
                                        <th class="text-right">Balance</th>
                                        <th class="text-right">Discount</th>
                                        <th class="text-right">Payment</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($data as $item)
                                        <tr>
                                            @php
                                                $vendorPayment = $vendor_purchases[$item->id];
                                            @endphp
                                            <td class="text-end">
                                                <input type="checkbox" wire:model="vendor_purchases.{{ $item->id }}.selected" wire:change="selectAction({{ $item->id }})"
                                                    value="{{ $item->id }}">
                                            </td>
                                            <td class="text-end"> <a href="{{ route('purchase::edit', $item->id) }}">{{ $item->invoice_no }}</a> </td>
                                            <td class="text-end">{{ currency($item->total) }}</td>
                                            <td class="text-end">{{ currency($item->other_discount) }}</td>
                                            <td class="text-end">{{ currency($item->grand_total) }}</td>
                                            <td class="text-end">{{ currency($item->paid) }}</td>
                                            <td class="text-end">{{ currency($item->balance) }}</td>
                                            @php
                                                $disabled = '';
                                                if (!$vendor_purchases[$item->id]['selected']) {
                                                    $disabled = 'disabled';
                                                }
                                            @endphp
                                            <td>
                                                {{ html()->number('discount')->value(0)->class('input-xs number select_on_focus')->attribute('step', 'any')->attribute('style', 'width:100%')->attribute('max', $item['balance'] - $vendorPayment['payment'])->attribute($disabled)->attribute('wire:model.live', 'vendor_purchases.' . $item['id'] . '.discount') }}
                                            </td>
                                            <td>
                                                {{ html()->number('payment')->value($item['payment'])->class('input-xs number select_on_focus')->attribute('step', 'any')->attribute('style', 'width:100%')->attribute('max', $item['balance'] - $vendorPayment['discount'])->attribute($disabled)->attribute('wire:model.live', 'vendor_purchases.' . $item['id'] . '.payment') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th></th>
                                        <th></th>
                                        <th class="text-end">{{ currency($total['total']) }}</th>
                                        <th class="text-end">{{ currency($total['other_discount']) }}</th>
                                        <th class="text-end">{{ currency($total['grand_total']) }}</th>
                                        <th class="text-end">{{ currency($total['paid']) }}</th>
                                        <th class="text-end">{{ currency($total['balance']) }}</th>
                                        <th class="text-end">{{ currency(array_sum(array_column($vendor_purchases, 'discount'))) }}</th>
                                        <th class="text-end">{{ currency(array_sum(array_column($vendor_purchases, 'payment'))) }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
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
