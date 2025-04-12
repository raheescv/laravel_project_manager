<div>
    <div class="card shadow-lg">
        <div class="card-body">
            <div class="text-center">
                <h1 class="h3">Total payable amount: {{ currency($sale_returns['grand_total'] ?? '') }}</h1>
            </div>
            <div class="row">
                <table class="table table-striped align-middle table-sm">
                    <thead>
                        <tr>
                            <th width="60%">Payment Method</th>
                            <th class="text-end">Amount</th>
                            <th>Action</th>
                        </tr>
                        <tr>
                            <th>
                                <div wire:ignore>
                                    {{ html()->select('payment_method_id', $paymentMethods)->value($default_payment_method_id)->class('select-payment_method_id-list')->placeholder('Select Payment Method')->id('modal_payment_method_id') }}
                                </div>
                            </th>
                            <th>
                                {{ html()->number('amount')->value('')->class('form-control form-control-sm number select_on_focus')->attribute('step', 'any')->id('payment')->attribute('wire:model.live', 'payment.amount') }}
                            </th>
                            <th>
                                <i class="fa fa-3x fa-plus-square pointer" wire:click="addPayment"></i>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($payments as $key => $item)
                            <tr>
                                <td>{{ $item['name'] }}</td>
                                <td class="text-end">{{ currency($item['amount']) }}</td>
                                <td>
                                    <i wire:click="removePayment('{{ $key }}')" wire:confirm="Are your sure?" class="demo-pli-recycling fs-5 me-2 pointer"></i>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="row">
                    <ul class="list-group list-group-borderless">
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                            <div class="me-5 mb-0 h5">Total Paid:</div>
                            <span class="fw-semibold" style="color:#1EB706;">{{ currency($sale_returns['paid']) }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                            <div class="me-5 mb-0 h5">Balance:</div>
                            <span class="text-danger fw-semibold">{{ currency($sale_returns['balance']) }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="submit" wire:click="save" class="btn btn-success">Save</button>
        </div>
    </div>
    @push('scripts')
        <script>
            $('#modal_payment_method_id').on('change', function(e) {
                const value = $(this).val() || null;
                @this.set('payment.payment_method_id', value);
                $('#payment').select();
            });
        </script>
    @endpush
</div>
