<div>
    <div class="col-md-12 mb-3">
        <div class="card h-100">
            <div class="card-body">
                <form wire:submit="submit">
                    <div class="row">
                        <div class="col-md-3">
                            @if ($purchases['status'] == 'draft')
                                <div class="row">
                                    <div wire:ignore>
                                        <label for="account_id">Vendor</label>
                                        {{ html()->select('account_id', $accounts)->value($purchases['account_id'])->class('select-vendor_id')->id('account_id')->placeholder('Select Vendor') }}
                                    </div>
                                </div>
                            @else
                                <div class="row">
                                    <label for="account_id">Vendor</label>
                                    {{ html()->input('account_name')->class('form-control')->disabled(true)->attribute('wire:model', 'purchases.account.name') }}
                                </div>
                            @endif
                            <div class="row my-2">
                                <div class="table-responsive">
                                    <table class="table table-striped align-middle table-sm">
                                        <tr>
                                            <th>Balance</th>
                                            <th class="text-end">{{ currency($account_balance ?? 0) }}</th>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5">

                        </div>
                        <div class="col-md-2">
                            <label for="invoice_no">Invoice No *</label>
                            @if ($purchases['status'] == 'draft')
                                {{ html()->input('invoice_no')->value('')->class('form-control')->required(true)->placeholder('Enter Invoice No')->attribute('wire:model', 'purchases.invoice_no') }}
                            @else
                                {{ html()->input('invoice_no')->value('')->class('form-control')->disabled(true)->attribute('wire:model', 'purchases.invoice_no') }}
                            @endif
                        </div>
                        <div class="col-md-2">
                            <div class="row">
                                <label for="date">Date</label>
                                @if ($purchases['status'] == 'draft')
                                    {{ html()->date('date')->value('')->class('form-control')->attribute('wire:model', 'purchases.date') }}
                                @else
                                    {{ html()->date('date')->value('')->class('form-control')->disabled(true)->attribute('wire:model', 'purchases.date') }}
                                @endif
                            </div>
                            <div class="row my-2">
                                <label for="date">Delivery Date</label>
                                @if ($purchases['status'] == 'draft')
                                    {{ html()->date('delivery_date')->value('')->class('form-control')->attribute('wire:model', 'purchases.delivery_date') }}
                                @else
                                    {{ html()->date('delivery_date')->value('')->class('form-control')->disabled(true)->attribute('wire:model', 'purchases.delivery_date') }}
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">ITEM INFO </h5>
                                    @if ($purchases['status'] == 'draft')
                                        <div class="row mb-3">
                                            <div class="col-md-2">
                                            </div>
                                            <div class="col-md-8">
                                                <div class="searchbox input-group" wire:ignore>
                                                    {{ html()->select('product_id', [])->value('')->class('select-product_id-list')->id('product_id')->attribute('style', 'width:100%')->placeholder('Select Product') }}
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                    <div class="table-responsive">
                                        <table class="table table-striped align-middle table-sm table-bordered">
                                            <thead style="background: #f8f8f8">
                                                <tr>
                                                    <th>SL No</th>
                                                    <th width="20%">Product</th>
                                                    <th class="text-end">Unit Price</th>
                                                    <th class="text-end">Quantity</th>
                                                    <th class="text-end">Discount</th>
                                                    <th class="text-end">Tax %</th>
                                                    <th class="text-end">Total</th>
                                                    @if ($purchases['status'] == 'draft')
                                                        <th>Action </th>
                                                    @endif
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($items as $item)
                                                    <tr>
                                                        <td>{{ $loop->iteration }}</td>
                                                        <td>{{ $item['name'] }}</td>
                                                        @if ($purchases['status'] == 'draft')
                                                            <td>
                                                                {{ html()->number('unit_price')->value($item['unit_price'])->class('input-xs number select_on_focus transparent_border_input')->attribute('style', 'width:100%')->attribute('wire:model.live', 'items.' . $item['key'] . '.unit_price') }}
                                                            </td>
                                                            <td>
                                                                {{ html()->number('quantity')->value($item['quantity'])->attribute('min', 1)->class('input-xs number select_on_focus transparent_border_input')->attribute('style', 'width:100%')->attribute('step', 'any')->attribute('wire:model.live', 'items.' . $item['key'] . '.quantity') }}
                                                            </td>
                                                            <td>
                                                                {{ html()->number('discount')->value($item['discount'])->class('input-xs number select_on_focus transparent_border_input')->attribute('style', 'width:100%')->attribute('wire:model.live', 'items.' . $item['key'] . '.discount') }}
                                                            </td>
                                                            <td>
                                                                {{ html()->number('tax')->value($item['tax'])->attribute('max', '50')->class('input-xs number select_on_focus transparent_border_input')->attribute('style', 'width:100%')->attribute('wire:model.live', 'items.' . $item['key'] . '.tax') }}
                                                            </td>
                                                            <td class="text-end"> {{ currency($item['total']) }} </td>
                                                        @else
                                                            <td class="text-end">{{ currency($item['unit_price']) }}</td>
                                                            <td class="text-end">{{ currency($item['quantity']) }}</td>
                                                            <td class="text-end">{{ currency($item['discount']) }}</td>
                                                            <td class="text-end">{{ currency($item['tax']) }}</td>
                                                            <td class="text-end"> {{ currency($item['total']) }} </td>
                                                        @endif
                                                        @if ($purchases['status'] == 'draft')
                                                            <td>
                                                                <i wire:click="removeItem('{{ $item['key'] }}')" wire:confirm="Are your sure?" class="demo-pli-recycling fs-5 me-2 pointer"></i>
                                                            </td>
                                                        @endif
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot>
                                                @php
                                                    $items = collect($items);
                                                @endphp
                                                <tr>
                                                    <th colspan="3" class="text-end">Total</th>
                                                    <th class="text-end"><b>{{ currency($items->sum('quantity')) }}</b></th>
                                                    <th class="text-end"><b>{{ currency($items->sum('discount')) }}</b></th>
                                                    <th class="text-end"><b>{{ currency($items->sum('tax_amount')) }}</b></th>
                                                    <th class="text-end"><b>{{ currency($items->sum('total')) }}</b></th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card shadow-lg">
                                <div class="card-body">
                                    <div class="col-md-12">
                                        <div class="row g-3">
                                            <div class="col-md-12">
                                                <div class="table-responsive">
                                                    <table class="table table-striped  table-sm table-bordered">
                                                        <thead>
                                                            @if ($purchases['status'] == 'draft')
                                                                <tr>
                                                                    <th>Gross Total</th>
                                                                    <td>
                                                                        {{ html()->number('gross_amount')->value('')->class('form-control number select_on_focus')->attribute('disabled')->attribute('wire:model', 'purchases.gross_amount') }}
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <th>purchase Total</th>
                                                                    <td>
                                                                        {{ html()->number('total')->value('')->class('form-control number select_on_focus')->attribute('disabled')->attribute('wire:model', 'purchases.total') }}
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <th>Other Discount</th>
                                                                    <th>
                                                                        {{ html()->number('other_discount')->value('')->class('form-control number select_on_focus')->attribute('wire:model.live', 'purchases.other_discount') }}
                                                                    </th>
                                                                </tr>
                                                                <tr>
                                                                    <th>Freight</th>
                                                                    <th>{{ html()->number('freight')->value('')->class('form-control number select_on_focus')->attribute('wire:model.live', 'purchases.freight') }}
                                                                    </th>
                                                                </tr>
                                                            @else
                                                                <tr>
                                                                    <th>Gross Total</th>
                                                                    <td class="text-end">{{ currency($purchases['gross_amount']) }}</td>
                                                                </tr>
                                                                <tr>
                                                                    <th>purchase Total</th>
                                                                    <td class="text-end">{{ currency($purchases['total']) }}</td>
                                                                </tr>
                                                                <tr>
                                                                    <th>Other Discount</th>
                                                                    <th class="text-end">{{ currency($purchases['other_discount']) }}</th>
                                                                </tr>
                                                                <tr>
                                                                    <th>Freight</th>
                                                                    <th class="text-end">{{ currency($purchases['freight']) }}</th>
                                                                </tr>
                                                            @endif
                                                        </thead>
                                                    </table>
                                                </div>
                                                <div class="row g-1">
                                                    <div class="col-md-12">
                                                        <label for="address" class="form-label">Address</label>
                                                        @if ($purchases['status'] == 'draft')
                                                            {{ html()->textarea('address')->value('')->class('form-control')->rows(3)->attribute('wire:model.live', 'purchases.address') }}
                                                        @else
                                                            {{ html()->textarea('address')->value('')->class('form-control')->rows(3)->disabled(true)->attribute('wire:model.live', 'purchases.address') }}
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="row">
                                                    <div class="d-grid">
                                                        <span class="btn btn-outline-primary"> Net Total Amount : {{ currency($purchases['grand_total']) }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card shadow-lg">
                                <div class="card-body">
                                    <div class="text-center">
                                        <h1 class="h3">Total payable amount: {{ currency($purchases['grand_total']) }}</h1>
                                    </div>
                                    <div class="row">
                                        <table class="table table-striped align-middle table-sm">
                                            <thead>
                                                <tr>
                                                    <th width="60%">Payment Method</th>
                                                    <th class="text-end">Amount</th>
                                                    @if ($purchases['status'] == 'draft')
                                                        <th>Action</th>
                                                    @endif
                                                </tr>
                                                @if ($purchases['status'] == 'draft')
                                                    <tr>
                                                        <th>
                                                            <div wire:ignore>
                                                                {{ html()->select('payment_method_id', $paymentMethods)->value($default_payment_method_id)->class('select-payment_method_id-list')->id('payment_method_id')->placeholder('Select Payment Method') }}
                                                            </div>
                                                        </th>
                                                        <th>
                                                            {{ html()->number('amount')->value('')->class('form-control number select_on_focus')->attribute('step', 'any')->id('payment')->attribute('wire:model.live', 'payment.amount') }}
                                                        </th>
                                                        <th>
                                                            <button type="button" wire:click="addPayment" class="btn btn-primary hstack gap-2 align-self-center">
                                                                <i class="demo-psi-add fs-5"></i>
                                                            </button>
                                                        </th>
                                                    </tr>
                                                @endif
                                            </thead>
                                            <tbody>
                                                @foreach ($payments as $key => $item)
                                                    <tr>
                                                        <td>{{ $item['name'] }}</td>
                                                        <td class="text-end">{{ currency($item['amount']) }}</td>
                                                        @if ($purchases['status'] == 'draft')
                                                            <td>
                                                                <i wire:click="removePayment('{{ $key }}')" wire:confirm="Are your sure?" class="demo-pli-recycling fs-5 me-2 pointer"></i>
                                                            </td>
                                                        @endif
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                        <div class="row">
                                            <div class="col-md-12">
                                                @if ($this->getErrorBag()->count())
                                                    <ol>
                                                        <?php foreach ($this->getErrorBag()->toArray() as $value): ?>
                                                        <li style="color:red">* {{ $value[0] }}</li>
                                                        <?php endforeach; ?>
                                                    </ol>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="d-flex">
                                                <div class="left-section w-50 me-3">
                                                    <table class="table table-sm table-bordered table-striped">
                                                        <thead>
                                                            @isset($purchases['created_user']['name'])
                                                                <tr>
                                                                    <td>Created By: <b>{{ $purchases['created_user']['name'] }}</b> </td>
                                                                </tr>
                                                                <tr>
                                                                    <td>Updated By: <b>{{ $purchases['updated_user']['name'] ?? '' }}</b> </td>
                                                                </tr>
                                                            @endisset
                                                            @isset($purchases['cancelled_user']['name'])
                                                                <tr>
                                                                    <th>Cancelled By: <b>{{ $purchases['cancelled_user']['name'] ?? '' }}</b> </th>
                                                                </tr>
                                                            @endisset
                                                        </thead>
                                                    </table>
                                                </div>
                                                <div class="right-section w-50">
                                                    <ul class="list-group list-group-borderless">
                                                        <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                                                            <div class="me-5 mb-0 h5">Total Paid:</div>
                                                            <span class="fw-semibold" style="color:#1EB706;">{{ currency($purchases['paid']) }}</span>
                                                        </li>
                                                        <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                                                            <div class="me-5 mb-0 h5">Balance:</div>
                                                            <span class="text-danger fw-semibold">{{ currency($purchases['balance']) }}</span>
                                                        </li>
                                                    </ul>

                                                    <hr>

                                                    <div class="d-flex justify-content-end gap-2 my-4 d-print-none">
                                                        @if ($purchases['status'] == 'draft')
                                                            <button type="button" wire:click='save("draft")' class="btn btn-primary">Draft</button>
                                                            <button type="submit" wire:confirm="Are you sure to submit this?" class="btn btn-success">Submit & Print</button>
                                                        @else
                                                            @if ($purchases['status'] != 'cancelled')
                                                                @can('purchase.cancel')
                                                                    <button type="button" wire:click='save("cancelled")' wire:confirm="Are you sure to cancel this?" class="btn btn-danger btn-sm">
                                                                        Cancel
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
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
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
                @if ($purchases['status'] == 'draft')
                    //to open the vendor dropdown
                    document.querySelector('#account_id').tomselect.open();
                @endif
                $('#account_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('purchases.account_id', value);
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
                    var tomSelectInstance = document.querySelector('#account_id').tomselect;
                    tomSelectInstance.addItem("{{ $purchases['account_id'] }}");

                    var tomSelectInstance = document.querySelector('#payment_method_id').tomselect;
                    tomSelectInstance.addItem("{{ $payment['payment_method_id'] }}");

                    var tomSelectInstance = document.querySelector('#product_id').tomselect;
                    tomSelectInstance.clear();
                    document.querySelector('#product_id').tomselect.open();
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
