<div>
    <div class="card shadow-lg border-0">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0 text-white">Custom Payment</h4>
        </div>
        <div class="card-body p-4">
            <div class="text-center mb-4">
                <h2 class="fw-bold">Total Payable Amount</h2>
                <h1 class="display-4 text-primary fw-bolder">{{ currency($sales['grand_total'] ?? 0) }}</h1>
            </div>

            <hr class="my-4">

            <h5 class="mb-3">Add Payment</h5>
            <div class="row g-3 align-items-end mb-4">
                <div class="col-md-6">
                    <label for="modal_payment_method_id" class="form-label">Payment Method</label>
                    <div wire:ignore>
                        {{ html()->select('payment_method_id', $paymentMethods)->value($default_payment_method_id)->class('select-payment_method_id-list')->placeholder('Select Payment Method')->id('modal_payment_method_id') }}
                    </div>
                </div>
                <div class="col-md-4">
                    <label for="payment_amount" class="form-label">Amount</label>
                    {{ html()->number('amount')->value('')->class('form-control number select_on_focus')->attribute('step', 'any')->id('payment_amount')->attribute('wire:model.live', 'payment.amount')->placeholder('Enter amount') }}
                </div>
                <div class="col-md-2 text-end">
                    <button type="button" class="btn btn-success" wire:click="addPayment" @if (empty($payment['payment_method_id']) || empty($payment['amount'])) disabled @endif>
                        <i class="fa fa-plus"></i>
                    </button>
                </div>
            </div>

            @if (session()->has('payment_error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('payment_error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (!empty($payments))
                <h5 class="mb-3">Payment Summary</h5>
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle">
                        <thead class="table-light">
                            <tr>
                                <th scope="col" style="width: 60%;">Payment Method</th>
                                <th scope="col" class="text-end">Amount</th>
                                <th scope="col" class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($payments as $key => $item)
                                <tr>
                                    <td>{{ $item['name'] }}</td>
                                    <td class="text-end fw-semibold">{{ currency($item['amount']) }}</td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger" wire:click="removePayment('{{ $key }}')"
                                            wire:confirm="Are you sure you want to remove this payment?">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <hr class="my-4">
            @endif

            <div class="row justify-content-end">
                <div class="col-md-6 col-lg-5">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <h5 class="mb-0 text-success">Total Paid:</h5>
                            <h5 class="mb-0 fw-bold text-success">{{ currency($sales['paid'] ?? 0) }}</h5>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <h5 class="mb-0 text-danger">Balance Due:</h5>
                            <h5 class="mb-0 fw-bold text-danger">{{ currency($sales['balance'] ?? 0) }}</h5>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="card-footer bg-light text-end p-3">
            <button type="submit" wire:click="save" class="btn btn-primary btn-lg px-5">
                <i class="fa fa-save me-2"></i>Save Payment
            </button>
        </div>
    </div>

    @push('scripts')
        <script>
            $(document).ready(function() {
                $('#modal_payment_method_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('payment.payment_method_id', value);
                    $('#payment_amount').focus().select();
                });
                Livewire.on('paymentAdded', () => {
                    $('#payment_amount').focus().select();
                });
            });
        </script>
    @endpush
</div>
