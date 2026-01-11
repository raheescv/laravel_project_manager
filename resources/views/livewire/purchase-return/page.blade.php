<div>
    <div class="container-fluid py-4">
        <form wire:submit="submit" class="needs-validation" novalidate>
            <!-- Header Section -->
            <div class="row g-4 mb-4">
                <!-- Vendor Selection -->
                <div class="col-12 col-md-6">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-light py-3">
                            <h5 class="card-title mb-0">
                                <i class="demo-psi-building me-2"></i>Vendor Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-7">
                                    @if ($purchase_returns['status'] == 'draft')
                                        <div wire:ignore>
                                            <label for="account_id" class="form-label">Select Vendor <span class="text-danger">*</span></label>
                                            {{ html()->select('account_id', $accounts)->value($purchase_returns['account_id'])->class('select-vendor_id')->id('account_id')->placeholder('Select Vendor') }}
                                        </div>
                                    @else
                                        <label for="account_id" class="form-label">Vendor Name</label>
                                        {{ html()->input('account_name')->class('form-control')->disabled(true)->attribute('wire:model', 'purchase_returns.account.name') }}
                                    @endif
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label">Current Balance</label>
                                    <div class="form-control bg-light {{ ($account_balance ?? 0) < 0 ? 'text-danger' : 'text-success' }} fw-bold text-end">
                                        {{ currency($account_balance ?? 0) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Return Details -->
                <div class="col-12 col-md-6">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-light py-3">
                            <h5 class="card-title mb-0">
                                <i class="demo-psi-file-text me-2"></i>Return Details
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="invoice_no" class="form-label">Invoice No <span class="text-danger">*</span></label>
                                    @if ($purchase_returns['status'] == 'draft')
                                        {{ html()->input('invoice_no')->value('')->class('form-control')->required(true)->placeholder('Enter Invoice No')->attribute('wire:model', 'purchase_returns.invoice_no') }}
                                    @else
                                        {{ html()->input('invoice_no')->value('')->class('form-control')->disabled(true)->attribute('wire:model', 'purchase_returns.invoice_no') }}
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    <label for="date" class="form-label">Return Date <span class="text-danger">*</span></label>
                                    @if ($purchase_returns['status'] == 'draft')
                                        {{ html()->date('date')->value('')->class('form-control')->attribute('wire:model', 'purchase_returns.date') }}
                                    @else
                                        {{ html()->date('date')->value('')->class('form-control')->disabled(true)->attribute('wire:model', 'purchase_returns.date') }}
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Items Section -->
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <div class="card shadow-lg border-0">
                        <div class="card-header bg-primary bg-gradient text-white py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0 fw-bold text-white">
                                    <i class="demo-psi-cart-2 me-2"></i>RETURN ITEMS
                                </h5>
                                @if ($purchase_returns['status'] == 'draft')
                                    <div class="col-md-10 d-flex gap-2">
                                        <div wire:ignore class="w-25">
                                            {{ html()->select('invoice_id', [])->value('')->class('select-vendor_purchase-list')->id('invoice_id')->attribute('style', 'width:100%')->placeholder('Select Purchase Invoice') }}
                                        </div>
                                        <div wire:ignore class="w-75">
                                            {{ html()->select('product_id', [])->value('')->class('select-product_id-list')->attribute('type', 'product')->attribute('source', 'purchase_return')->id('product_id')->attribute('style', 'width:100%')->placeholder('Select Product from Invoice') }}
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>#</th>
                                            <th style="width: 30%">Product</th>
                                            <th>Unit</th>
                                            <th class="text-end">Unit Price</th>
                                            <th class="text-end">Qty</th>
                                            <th class="text-end">Discount</th>
                                            <th class="text-end">Tax</th>
                                            <th class="text-end">Total</th>
                                            @if ($purchase_returns['status'] == 'draft')
                                                <th class="text-center" style="width: 80px">Action</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($items as $item)
                                            <tr>
                                                <td class="fw-medium">{{ $loop->iteration }}</td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="flex-grow-1">
                                                            <h6 class="mb-0">{{ $item['name'] }}</h6>
                                                            <small class="text-muted">Invoice: {{ $item['purchase_invoice_no'] }}</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="input-group input-group-sm">
                                                        <select class="form-select form-select-sm border-0 bg-light"
                                                            wire:model.live="items.{{ $item['key'] }}.unit_id">
                                                            @foreach ($item['units'] as $unit)
                                                                <option value="{{ $unit['id'] }}">{{ $unit['name'] }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </td>
                                                @if ($purchase_returns['status'] == 'draft')
                                                    <td>
                                                        <div class="input-group input-group-sm">
                                                            {{ html()->number('unit_price')->value($item['unit_price'])->class('form-control form-control-sm text-end border-0 bg-light')->attribute('step', 'any')->attribute('wire:model.lazy', 'items.' . $item['key'] . '.unit_price') }}
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="input-group input-group-sm">
                                                            {{ html()->number('quantity')->value($item['quantity'])->attribute('min', 1)->class('form-control form-control-sm text-end border-0 bg-light')->attribute('step', 'any')->attribute('wire:model.lazy', 'items.' . $item['key'] . '.quantity') }}
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="input-group input-group-sm">
                                                            {{ html()->number('discount')->value($item['discount'])->class('form-control form-control-sm text-end border-0 bg-light')->attribute('step', 'any')->attribute('wire:model.lazy', 'items.' . $item['key'] . '.discount') }}
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="input-group input-group-sm">
                                                            {{ html()->number('tax')->value($item['tax'])->class('form-control form-control-sm text-end border-0 bg-light')->attribute('step', 'any')->attribute('wire:model.lazy', 'items.' . $item['key'] . '.tax') }}
                                                        </div>
                                                    </td>
                                                    <td class="text-end fw-bold">{{ currency($item['total']) }}</td>
                                                    <td class="text-center">
                                                        <button type="button" wire:click="removeItem('{{ $item['key'] }}')" wire:confirm="Are your sure?"
                                                            class="btn btn-sm btn-icon btn-outline-danger rounded-circle" title="Remove Item">
                                                            <i class="demo-pli-recycling"></i>
                                                        </button>
                                                    </td>
                                                @else
                                                    <td>{{ collect($item['units'])->where('id', $item['unit_id'])->first()['name'] ?? '' }}</td>
                                                    <td class="text-end">{{ currency($item['unit_price']) }}</td>
                                                    <td class="text-end">{{ $item['quantity'] }}</td>
                                                    <td class="text-end">{{ currency($item['discount']) }}</td>
                                                    <td class="text-end">{{ currency($item['tax']) }}</td>
                                                    <td class="text-end fw-bold">{{ currency($item['total']) }}</td>
                                                @endif
                                            </tr>
                                        @endforeach
                                        @if (count($items) == 0)
                                            <tr>
                                                <td colspan="9" class="text-center py-4 text-muted">
                                                    <i class="demo-psi-cart-2 fs-1 mb-2 d-block"></i>
                                                    No items added yet
                                                </td>
                                            </tr>
                                        @endif
                                    </tbody>
                                    <tfoot class="table-group-divider">
                                        @php
                                            $items = collect($items);
                                        @endphp
                                        <tr class="bg-light">
                                            <th colspan="4" class="text-end py-3">Total</th>
                                            <th class="text-end py-3"><b>{{ $items->sum('quantity') }}</b></th>
                                            <th class="text-end py-3"><b>{{ $items->sum('discount') }}</b></th>
                                            <th class="text-end py-3"><b>{{ $items->sum('tax_amount') }}</b></th>
                                            <th class="text-end py-3"><b>{{ currency($items->sum('total')) }}</b></th>
                                            @if ($purchase_returns['status'] == 'draft')
                                                <th></th>
                                            @endif
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Section -->
            <div class="row g-4">

                <!-- Summary Section -->
                <div class="row g-4">
                    <!-- Left Column - Purchase Return Summary -->
                    <div class="col-12 col-md-6">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-gradient bg-primary text-white py-3">
                                <h5 class="card-title mb-0 d-flex align-items-center text-white">
                                    <i class="demo-psi-receipt-4 fs-4 me-2"></i>
                                    Purchase Return Summary
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-4">
                                    @if ($purchase_returns['status'] == 'draft')
                                        <!-- Amounts Section -->
                                        <div class="col-12">
                                            <div class="list-group">
                                                <div class="list-group-item list-group-item-action">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div class="fw-medium text-muted">Gross Total</div>
                                                        <div class="input-group input-group-sm w-50">
                                                            {{ html()->number('gross_amount')->value('')->class('form-control form-control-sm border-0 bg-light text-end')->attribute('disabled')->attribute('wire:model', 'purchase_returns.gross_amount') }}
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="list-group-item list-group-item-action">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div class="fw-medium text-muted">Purchase Total</div>
                                                        <div class="input-group input-group-sm w-50">
                                                            {{ html()->number('total')->value('')->class('form-control form-control-sm border-0 bg-light text-end')->attribute('disabled')->attribute('wire:model', 'purchase_returns.total') }}
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="list-group-item list-group-item-action">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div class="fw-medium text-muted">Other Discount</div>
                                                        <div class="input-group input-group-sm w-50">
                                                            {{ html()->number('other_discount')->value('')->class('form-control form-control-sm text-end')->attribute('wire:model.lazy', 'purchase_returns.other_discount') }}
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="list-group-item list-group-item-action">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div class="fw-medium text-muted">Freight Charges</div>
                                                        <div class="input-group input-group-sm w-50">
                                                            {{ html()->number('freight')->value('')->class('form-control form-control-sm text-end')->attribute('wire:model.lazy', 'purchase_returns.freight') }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <!-- Read-only Amounts -->
                                        <div class="col-12">
                                            <div class="list-group">
                                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                                    <div class="text-muted">Gross Total</div>
                                                    <div class="h6 mb-0">{{ currency($purchase_returns['gross_amount']) }}</div>
                                                </div>
                                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                                    <div class="text-muted">Purchase Total</div>
                                                    <div class="h6 mb-0">{{ currency($purchase_returns['total']) }}</div>
                                                </div>
                                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                                    <div class="text-muted">Other Discount</div>
                                                    <div class="h6 mb-0 text-danger">-{{ currency($purchase_returns['other_discount']) }}</div>
                                                </div>
                                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                                    <div class="text-muted">Freight Charges</div>
                                                    <div class="h6 mb-0">{{ currency($purchase_returns['freight']) }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    <!-- Shipping Reason -->
                                    <div class="col-12">
                                        <div class="card bg-light border-0">
                                            <div class="card-body">
                                                <h6 class="card-subtitle mb-3 text-muted">
                                                    <i class="demo-psi-map-marker-2 me-1"></i> Reason
                                                </h6>
                                                @if ($purchase_returns['status'] == 'draft')
                                                    {{ html()->textarea('reason')->value('')->class('form-control')->rows(3)->attribute('wire:model.live', 'purchase_returns.reason')->placeholder('Enter return reason...') }}
                                                @else
                                                    {{ html()->textarea('reason')->value('')->class('form-control bg-light')->rows(3)->disabled(true)->attribute('wire:model.live', 'purchase_returns.reason') }}
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column - Payment Details -->
                    <div class="col-12 col-md-6">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-gradient bg-primary text-white py-3">
                                <h5 class="card-title mb-0 d-flex align-items-center text-white">
                                    <i class="demo-psi-credit-card-2 fs-4 me-2"></i>Payment Details
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info border-0 text-center mb-4">
                                    <span class="d-block fs-6 text-muted mb-1">Total Payable Amount</span>
                                    <span class="h4 mb-0">{{ currency($purchase_returns['grand_total']) }}</span>
                                </div>
                                @if ($purchase_returns['status'] == 'draft')
                                    <div class="row g-3 mb-4">
                                        <div class="col-md-7">
                                            <div wire:ignore>
                                                <label class="form-label">Payment Method</label>
                                                {{ html()->select('payment_method_id', $paymentMethods)->value($default_payment_method_id)->class('select-payment_method_id-list')->id('payment_method_id')->placeholder('Select Payment Method') }}
                                            </div>
                                        </div>
                                        <div class="col-md-5">
                                            <label class="form-label">Amount</label>
                                            <div class="input-group">
                                                {{ html()->number('amount')->value('')->class('form-control text-end')->attribute('step', 'any')->id('payment')->attribute('wire:model.live', 'payment.amount')->placeholder('0.00') }}
                                                <button type="button" wire:click="addPayment" class="btn btn-primary">
                                                    <i class="demo-psi-add me-1"></i> Add
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered table-hover">
                                        <thead class="bg-light">
                                            <tr>
                                                <th style="width: 60%">Payment Method</th>
                                                <th class="text-end">Amount</th>
                                                @if ($purchase_returns['status'] == 'draft')
                                                    <th style="width: 80px">Action</th>
                                                @endif
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($payments ?? [] as $key => $item)
                                                <tr>
                                                    <td class="align-middle">{{ $item['name'] }}</td>
                                                    <td class="text-end align-middle">{{ currency($item['amount']) }}</td>
                                                    @if ($purchase_returns['status'] == 'draft')
                                                        <td class="text-center">
                                                            <button type="button" wire:click="removePayment('{{ $key }}')" wire:confirm="Are your sure?"
                                                                class="btn btn-sm btn-icon btn-outline-danger rounded-circle" title="Remove Payment">
                                                                <i class="demo-pli-recycling"></i>
                                                            </button>
                                                        </td>
                                                    @endif
                                                </tr>
                                            @endforeach
                                            @if (count($payments ?? []) == 0)
                                                <tr>
                                                    <td colspan="{{ $purchase_returns['status'] == 'draft' ? '3' : '2' }}" class="text-center py-4 text-muted">
                                                        <i class="demo-psi-credit-card-2 fs-1 mb-2 d-block"></i>
                                                        No payments added yet
                                                    </td>
                                                </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>

                                @if ($this->getErrorBag()->count())
                                    <div class="alert alert-danger border-0 mb-4">
                                        <ul class="mb-0 ps-3">
                                            @foreach ($this->getErrorBag()->toArray() as $value)
                                                <li>{{ $value[0] }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <div class="card bg-light border-0">
                                            <div class="card-body p-3">
                                                @isset($purchase_returns['created_user']['name'])
                                                    <p class="mb-1 small">Created By: <strong>{{ $purchase_returns['created_user']['name'] }}</strong></p>
                                                    <p class="mb-1 small">Updated By: <strong>{{ $purchase_returns['updated_user']['name'] ?? '' }}</strong></p>
                                                @endisset
                                                @isset($purchase_returns['cancelled_user']['name'])
                                                    <p class="mb-0 small text-danger">Cancelled By: <strong>{{ $purchase_returns['cancelled_user']['name'] ?? '' }}</strong></p>
                                                @endisset
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card bg-light border-0">
                                            <div class="card-body p-3">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <span class="small">Total Paid:</span>
                                                    <span class="h6 mb-0 text-success">{{ currency($purchase_returns['paid']) }}</span>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="small">Balance:</span>
                                                    <span class="h6 mb-0 text-danger">{{ currency($purchase_returns['balance']) }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2">
                                    @if ($purchase_returns['status'] == 'draft')
                                        <button type="button" wire:click='save("draft")' class="btn btn-light">
                                            <i class="demo-psi-file me-1"></i> Save Draft
                                        </button>
                                        <button type="submit" wire:confirm="Are you sure to submit this?" class="btn btn-primary">
                                            <i class="demo-psi-printer me-1"></i> Submit Return
                                        </button>
                                    @else
                                        @if ($purchase_returns['status'] != 'cancelled')
                                            @can('purchase.cancel')
                                                <button type="button" wire:click='save("cancelled")' wire:confirm="Are you sure to cancel this?" class="btn btn-danger">
                                                    <i class="demo-psi-cross me-1"></i> Cancel Purchase Return
                                                </button>
                                            @endcan
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                window.addEventListener('show-confirmation', function(event) {
                    const data = event.detail[0];
                    const message = `
                    <table class="table table-bordered table-striped">
                        <tr>
                            <th colspan="2" class="text-center">${data.vendor}</th>
                        </tr>
                        <tr>
                        <tr>
                            <th class="text-start"><strong>Grand Total</strong></td>
                            <td class="text-end">${data.grand_total}</td>
                        </tr>
                        <tr>
                            <th class="text-start"><strong>Payment Methods</strong></td>
                            <td class="text-end">${data.payment_methods}</td>
                        </tr>
                        <tr>
                            <th class="text-start"><strong>Paid</strong></td>
                            <td class="text-end">${data.paid}</td>
                        </tr>
                        <tr>
                            <th class="text-start"><strong>Balance</strong></td>
                            <td class="text-end">${data.balance}</td>
                        </tr>
                    </table>
                    `;
                    Swal.fire({
                        title: 'Are you sure?',
                        html: message,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, submit it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            @this.call('save');
                        }
                    });
                });
            });
        </script>
        <script>
            $(document).ready(function() {
                @if ($purchase_returns['status'] == 'draft')
                    //to open the vendor dropdown
                    document.querySelector('#account_id').tomselect.open();
                @endif
                $('#account_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('purchase_returns.account_id', value);
                    document.querySelector('#invoice_id').tomselect.open();
                });
                $('#invoice_id').on('change', function(e) {
                    document.querySelector('#product_id').tomselect.open();
                });
                $('#product_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('product_id', value);
                });
                $('#payment_method_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('payment.payment_method_id', value);
                    $('#payment').select();
                });
                window.addEventListener('OpenProductBox', event => {
                    @this.set('product_id', null);
                    document.querySelector('#product_id').tomselect.open();
                });
                window.addEventListener('ResetSelectBox', event => {
                    if (event.detail[0]['type'] != 'cancelled') {
                        var tomSelectInstance = document.querySelector('#account_id').tomselect;
                        tomSelectInstance.addItem("{{ $purchase_returns['account_id'] }}");

                        var tomSelectInstance = document.querySelector('#payment_method_id').tomselect;
                        tomSelectInstance.addItem("{{ $payment['payment_method_id'] }}");

                        var tomSelectInstance = document.querySelector('#product_id').tomselect;
                        tomSelectInstance.clear();
                        document.querySelector('#product_id').tomselect.open();
                    }
                });
                window.addEventListener('AddToVendorSelectBox', event => {
                    var data = event.detail[0];
                    var tomSelectInstance = document.querySelector('#account_id').tomselect;
                    if (data['name']) {
                        preselectedData = {
                            id: data['id'],
                            name: data['name'],
                            mobile: data['mobile'],
                        };
                        tomSelectInstance.addOption(preselectedData);
                    }
                    tomSelectInstance.addItem(data['id']);
                });
            });
        </script>
    @endpush
</div>
