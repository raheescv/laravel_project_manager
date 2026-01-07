<div>
     <form wire:submit.prevent="save">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h3 class="m-t-none m-b"> {{ $name ?? '' }} : Booking Receipt Form</h3>
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
                        <label>Date</label>
                        {{ html()->date('date')
                            ->class('form-control')
                            ->required(true)
                            ->attribute('wire:model', 'payment.date') }}
                    </div>

                    <div class="col-md-3">
                        <label>Amount</label>
                        {{ html()->number('amount')
                            ->class('form-control number')
                            ->attribute('step','any')
                            ->required(true)
                            ->attribute('wire:model.defer','payment.amount') }}
                    </div>

                    <div class="col-md-3" wire:ignore>
                        <label>Payment Mode</label>
                        {{ html()->select('payment_method_id',$paymentMethods)
                            ->value($default_payment_method_id)
                            ->class('select-payment_method_id-list')
                            ->id('payment_method_id')
                            ->placeholder('Select Payment Method') }}
                    </div>

                    <div class="col-md-3 mt-4">
                        <button type="submit" class="btn btn-success">Save</button>
                    </div>
                </div>

                <div class="row mt-2">
                    <div class="col-md-12">
                        <label>Remarks</label>
                        {{ html()->text('remarks')
                            ->class('form-control')
                            ->attribute('wire:model.defer','payment.remarks') }}
                    </div>
                </div>

                <div class="table-responsive mt-3">
                    <table class="table table-bordered table-sm table-striped">
                        <thead>
                            <tr>
                                <th>
                                    <input type="checkbox" wire:model="checkAll">
                                </th>
                                <th>Invoice</th>
                                <th class="text-end">Sale</th>
                                <th class="text-end">Disc</th>
                                <th class="text-end">Total</th>
                                <th class="text-end">Paid</th>
                                <th class="text-end">Balance</th>
                                <th class="text-end">Discount</th>
                                <th class="text-end">Payment</th>
                            </tr>
                        </thead>

                        <tbody>
                        @foreach($data as $item)
                            @php $cp = $customer_sales[$item->id]; @endphp
                            <tr wire:key="sale-{{ $item->id }}">
                                <td>
                                    <input type="checkbox"
                                        wire:model="customer_sales.{{ $item->id }}.selected"
                                        wire:change="selectAction({{ $item->id }})">
                                </td>

                                <td>
                                    <a href="{{ route('sale::view_booking',$item->id) }}">
                                        {{ $item->invoice_no }}
                                    </a>
                                </td>

                                <td class="text-end">{{ currency($item->total) }}</td>
                                <td class="text-end">{{ currency($item->other_discount) }}</td>
                                <td class="text-end">{{ currency($item->grand_total) }}</td>
                                <td class="text-end">{{ currency($item->paid) }}</td>
                                <td class="text-end">{{ currency($item->balance) }}</td>

                                @php $disabled = !$cp['selected'] ? 'disabled' : ''; @endphp

                                <td>
                                    {{ html()->number('discount')
                                        ->class('input-xs number')
                                        ->attribute('step','any')
                                        ->attribute('style','width:100%')
                                        ->attribute($disabled)
                                        ->attribute('wire:model.defer',"customer_sales.$item->id.discount") }}
                                </td>

                                <td>
                                    {{ html()->number('payment')
                                        ->class('input-xs number')
                                        ->attribute('step','any')
                                        ->attribute('style','width:100%')
                                        ->attribute($disabled)
                                        ->attribute('wire:model.defer',"customer_sales.$item->id.payment") }}
                                </td>
                            </tr>
                        @endforeach
                        </tbody>

                        <tfoot>
                            <tr>
                                <th colspan="2"></th>
                                <th class="text-end">{{ currency($total['total']) }}</th>
                                <th class="text-end">{{ currency($total['other_discount']) }}</th>
                                <th class="text-end">{{ currency($total['grand_total']) }}</th>
                                <th class="text-end">{{ currency($total['paid']) }}</th>
                                <th class="text-end">{{ currency($total['balance']) }}</th>
                                <th class="text-end">{{ currency(array_sum(array_column($customer_sales,'discount'))) }}</th>
                                <th class="text-end">{{ currency(array_sum(array_column($customer_sales,'payment'))) }}</th>
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
