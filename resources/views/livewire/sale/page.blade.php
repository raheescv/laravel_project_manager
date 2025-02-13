<div>
    <div class="col-md-12 mb-3">
        <div class="card h-100">
            <div class="card-body">
                <form wire:submit="submit">
                    <div class="row">
                        <div class="col-md-3">
                            @if ($sales['status'] == 'draft')
                                <div class="row">
                                    <div wire:ignore>
                                        <label for="account_id">Customer</label>
                                        {{ html()->select('account_id', $accounts)->value($sales['account_id'])->class('select-customer_id')->id('account_id')->placeholder('Select Customer') }}
                                    </div>
                                </div>
                            @else
                                <div class="row">
                                    <label for="account_id">Customer</label>
                                    {{ html()->input('account_name')->class('form-control')->disabled(true)->attribute('wire:model', 'sales.account.name') }}
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
                        <div class="col-md-3">
                            @if ($sales['account_id'] == 3)
                                <label for="customer_name">Customer Name</label>
                                @if ($sales['status'] == 'draft')
                                    {{ html()->input('customer_name')->value('')->class('form-control')->placeholder('Enter Customer Name')->id('customer_name')->attribute('wire:model', 'sales.customer_name') }}
                                @else
                                    {{ html()->input('customer_name')->value('')->class('form-control')->disabled(true)->id('customer_name')->attribute('wire:model', 'sales.customer_name') }}
                                @endif
                            @endif
                        </div>
                        <div class="col-md-2">
                            @if ($sales['account_id'] == 3)
                                <label for="customer_mobile">Customer Mobile</label>
                                @if ($sales['status'] == 'draft')
                                    {{ html()->input('customer_mobile')->value('')->class('form-control')->placeholder('Enter Customer Mobile')->attribute('wire:model', 'sales.customer_mobile') }}
                                @else
                                    {{ html()->input('customer_mobile')->value('')->class('form-control')->disabled(true)->attribute('wire:model', 'sales.customer_mobile') }}
                                @endif
                            @endif
                        </div>
                        <div class="col-md-2">
                            <label for="reference_no">Reference No</label>
                            @if ($sales['status'] == 'draft')
                                {{ html()->input('reference_no')->value('')->class('form-control')->placeholder('Enter Reference No')->attribute('wire:model', 'sales.reference_no') }}
                            @else
                                {{ html()->input('reference_no')->value('')->class('form-control')->disabled(true)->attribute('wire:model', 'sales.reference_no') }}
                            @endif
                            <div class="row my-2 p-1">
                                <label for="sale_type">Sale Type</label>
                                {{ html()->select('sale_type', priceTypes())->class('form-control')->id('sale_type')->attribute('wire:model.live', 'sales.sale_type')->required(true)->placeholder('Select Sale Type') }}
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="row">
                                <label for="date">Date</label>
                                @if ($sales['status'] == 'draft')
                                    {{ html()->date('date')->value('')->class('form-control')->attribute('wire:model', 'sales.date') }}
                                @else
                                    {{ html()->date('date')->value('')->class('form-control')->disabled(true)->attribute('wire:model', 'sales.date') }}
                                @endif
                            </div>
                            <div class="row my-2">
                                <label for="date">Due Date</label>
                                @if ($sales['status'] == 'draft')
                                    {{ html()->date('due_date')->value('')->class('form-control')->attribute('wire:model', 'sales.due_date') }}
                                @else
                                    {{ html()->date('due_date')->value('')->class('form-control')->disabled(true)->attribute('wire:model', 'sales.due_date') }}
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">ITEM INFO </h5>
                                    @if ($sales['status'] == 'draft')
                                        <div class="row mb-3">
                                            <div class="col-md-4">
                                                <div class="searchbox input-group" wire:ignore>
                                                    {{ html()->select('employee_id', $employees)->value($employee_id)->class('select-employee_id-list')->id('employee_id')->attribute('style', 'width:100%')->placeholder('Select Employee') }}
                                                </div>
                                            </div>
                                            <div class="col-md-8">
                                                <div class="searchbox input-group" wire:ignore>
                                                    {{ html()->select('inventory_id', [])->value('')->class('select-inventory-product_id-list')->id('inventory_id')->attribute('style', 'width:100%')->placeholder('Select Product') }}
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
                                                    @if ($sales['other_discount'] > 0)
                                                        <th class="text-end">Effective Total</th>
                                                    @endif
                                                    @if ($sales['status'] == 'draft')
                                                        <th>Action </th>
                                                    @endif
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php
                                                    $result = [];
                                                    foreach ($items as $key => $value) {
                                                        [$parent, $sub] = explode('-', $key);
                                                        if (!isset($result[$parent])) {
                                                            $result[$parent] = [];
                                                        }
                                                        $result[$parent][$sub] = $value;
                                                    }
                                                    $data = $result;
                                                @endphp
                                                @foreach ($data as $employee_id => $groupedItems)
                                                    <tr>
                                                        @php
                                                            $first = array_values($groupedItems)[0];
                                                        @endphp
                                                        <th colspan="8">{{ $first['employee_name'] }}</th>
                                                    </tr>
                                                    @foreach ($groupedItems as $item)
                                                        <tr>
                                                            <td>{{ $loop->iteration }}</td>
                                                            <td>{{ $item['name'] }}</td>
                                                            @if ($sales['status'] == 'draft')
                                                                <td>
                                                                    {{ html()->number('unit_price')->value($item['unit_price'])->class('input-xs number select_on_focus')->attribute('style', 'width:100%')->attribute('wire:model.live', 'items.' . $item['key'] . '.unit_price') }}
                                                                </td>
                                                                <td>
                                                                    {{ html()->number('quantity')->value($item['quantity'])->attribute('min', 1)->class('input-xs number select_on_focus')->attribute('style', 'width:100%')->attribute('step', 'any')->attribute('wire:model.live', 'items.' . $item['key'] . '.quantity') }}
                                                                </td>
                                                                <td>
                                                                    {{ html()->number('discount')->value($item['discount'])->class('input-xs number select_on_focus')->attribute('style', 'width:100%')->attribute('wire:model.live', 'items.' . $item['key'] . '.discount') }}
                                                                </td>
                                                                <td>
                                                                    {{ html()->number('tax')->value($item['tax'])->attribute('max', '50')->class('input-xs number select_on_focus')->attribute('style', 'width:100%')->attribute('wire:model.live', 'items.' . $item['key'] . '.tax') }}
                                                                </td>
                                                            @else
                                                                <td class="text-end">{{ currency($item['unit_price']) }}</td>
                                                                <td class="text-end">{{ currency($item['quantity']) }}</td>
                                                                <td class="text-end">{{ currency($item['discount']) }}</td>
                                                                <td class="text-end">{{ currency($item['tax']) }}</td>
                                                            @endif
                                                            <td class="text-end"> {{ currency($item['total']) }} </td>
                                                            @if ($sales['other_discount'] > 0)
                                                                <td class="text-end"> {{ currency($item['effective_total']) }} </td>
                                                            @endif
                                                            @if ($sales['status'] == 'draft')
                                                                <td>
                                                                    <i wire:click="removeItem('{{ $item['key'] }}')" wire:confirm="Are your sure?" class="demo-pli-recycling fs-5 me-2 pointer"></i>
                                                                </td>
                                                            @endif
                                                        </tr>
                                                    @endforeach
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
                                                    @if ($sales['other_discount'] > 0)
                                                        <th class="text-end"><b>{{ currency($items->sum('effective_total')) }}</b></th>
                                                    @endif
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
                                                            @if ($sales['status'] == 'draft')
                                                                <tr>
                                                                    <th>Gross Total</th>
                                                                    <td>
                                                                        {{ html()->number('gross_amount')->value('')->class('form-control number select_on_focus')->attribute('disabled')->attribute('wire:model', 'sales.gross_amount') }}
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <th>Sale Total</th>
                                                                    <td>
                                                                        {{ html()->number('total')->value('')->class('form-control number select_on_focus')->attribute('disabled')->attribute('wire:model', 'sales.total') }}
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <th>Other Discount</th>
                                                                    <th>
                                                                        {{ html()->number('other_discount')->value('')->class('form-control number select_on_focus')->attribute('wire:model.lazy', 'sales.other_discount') }}
                                                                    </th>
                                                                </tr>
                                                                <tr>
                                                                    <th>Freight</th>
                                                                    <th>{{ html()->number('freight')->value('')->class('form-control number select_on_focus')->attribute('wire:model.lazy', 'sales.freight') }}
                                                                    </th>
                                                                </tr>
                                                            @else
                                                                <tr>
                                                                    <th>Gross Total</th>
                                                                    <td class="text-end">{{ currency($sales['gross_amount']) }}</td>
                                                                </tr>
                                                                <tr>
                                                                    <th>Sale Total</th>
                                                                    <td class="text-end">{{ currency($sales['total']) }}</td>
                                                                </tr>
                                                                <tr>
                                                                    <th>Other Discount</th>
                                                                    <th class="text-end">{{ currency($sales['other_discount']) }}</th>
                                                                </tr>
                                                                <tr>
                                                                    <th>Freight</th>
                                                                    <th class="text-end">{{ currency($sales['freight']) }}</th>
                                                                </tr>
                                                            @endif
                                                        </thead>
                                                    </table>
                                                </div>
                                                <div class="row g-1">
                                                    <div class="col-md-12">
                                                        <label for="address" class="form-label">Address</label>
                                                        @if ($sales['status'] == 'draft')
                                                            {{ html()->textarea('address')->value('')->class('form-control')->rows(3)->attribute('wire:model.live', 'sales.address') }}
                                                        @else
                                                            {{ html()->textarea('address')->value('')->class('form-control')->rows(3)->disabled(true)->attribute('wire:model.live', 'sales.address') }}
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="row">
                                                    <div class="d-grid">
                                                        <span class="btn btn-outline-primary"> Net Total Amount : {{ currency($sales['grand_total']) }}</span>
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
                                        <h1 class="h3">Total payable amount: {{ currency($sales['grand_total']) }}</h1>
                                    </div>
                                    <div class="row">
                                        <table class="table table-striped align-middle table-sm">
                                            <thead>
                                                <tr>
                                                    <th width="60%">Payment Method</th>
                                                    <th class="text-end">Amount</th>
                                                    @if ($sales['status'] == 'draft')
                                                        <th>Action</th>
                                                    @endif
                                                </tr>
                                                @if ($sales['status'] == 'draft')
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
                                                        @if ($sales['status'] == 'draft')
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
                                                        <?php foreach ($this->getErrorBag()->toArray() as $key => $value): ?>
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
                                                            @isset($sales['created_user']['name'])
                                                                <tr>
                                                                    <td>Created By: <b>{{ $sales['created_user']['name'] }}</b> </td>
                                                                </tr>
                                                                <tr>
                                                                    <td>Updated By: <b>{{ $sales['updated_user']['name'] ?? '' }}</b> </td>
                                                                </tr>
                                                            @endisset
                                                            @isset($sales['cancelled_user']['name'])
                                                                <tr>
                                                                    <td>Cancelled By: <b>{{ $sales['cancelled_user']['name'] ?? '' }}</b> </td>
                                                                </tr>
                                                            @endisset
                                                        </thead>
                                                    </table>
                                                </div>
                                                <div class="right-section w-50">
                                                    <ul class="list-group list-group-borderless">
                                                        <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                                                            <div class="me-5 mb-0 h5">Total Paid:</div>
                                                            <span class="fw-semibold" style="color:#1EB706;">{{ currency($sales['paid']) }}</span>
                                                        </li>
                                                        <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                                                            <div class="me-5 mb-0 h5">Balance:</div>
                                                            <span class="text-danger fw-semibold">{{ currency($sales['balance']) }}</span>
                                                        </li>
                                                        @if ($sales['status'] == 'draft')
                                                            <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                                                                <div class="form-check">
                                                                    <label for="send_to_whatsapp" class="form-check-label">
                                                                        {{ html()->checkbox('send_to_whatsapp')->value('')->class('form-check-input')->attribute('wire:model.live', 'send_to_whatsapp') }}
                                                                        Send Invoice To Whatsapp
                                                                    </label>
                                                                </div>
                                                            </li>
                                                        @endif
                                                    </ul>

                                                    <hr>

                                                    <div class="d-flex justify-content-end gap-2 my-4 d-print-none">
                                                        @if ($sales['status'] == 'draft')
                                                            <button type="button" wire:click='save("draft")' class="btn btn-primary">Draft</button>
                                                            <button type="submit" wire:confirm="Are you sure to submit this?" class="btn btn-success">Submit & Print</button>
                                                        @else
                                                            @if ($sales['status'] != 'cancelled')
                                                                <a target="_blank" href="{{ route('print::sale::invoice', $sales['id']) }}" type="button" class="btn btn-success">Print</a>
                                                                @can('sale.cancel')
                                                                    <button type="button" wire:click='save("cancelled")' wire:confirm="Are you sure to cancel this?" class="btn btn-danger btn-sm">
                                                                        Cancel
                                                                    </button>
                                                                @endcan
                                                                @can('sale.cancel')
                                                                    <button type="button" wire:click='sendToWhatsapp' class="btn btn-info btn-sm">
                                                                        Whatsapp
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
                            <th colspan="2" class="text-center">${data.customer}</th>
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
                let employee_id = "{{ $employee_id }}";
                @if ($sales['status'] == 'draft')
                    // to open the dropdown by on load
                    if (employee_id) {
                        document.querySelector('#inventory_id').tomselect.open();
                    } else {
                        document.querySelector('#employee_id').tomselect.open();
                    }
                @endif
                $('#account_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('sales.account_id', value);
                    if (value == 3) {
                        $('#customer_name').select();
                    } else {
                        if (employee_id) {
                            document.querySelector('#inventory_id').tomselect.open();
                        } else {
                            document.querySelector('#employee_id').tomselect.open();
                        }
                    }
                });
                $('#inventory_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('inventory_id', value);
                });
                $('#employee_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('employee_id', value);
                    document.querySelector('#inventory_id').tomselect.open();
                });
                $('#payment_method_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('payment.payment_method_id', value);
                    $('#payment').select();
                });
                window.addEventListener('OpenProductBox', event => {
                    @this.set('inventory_id', null);
                    document.querySelector('#inventory_id').tomselect.open();
                });
                window.addEventListener('ResetSelectBox', event => {
                    var tomSelectInstance = document.querySelector('#account_id').tomselect;
                    tomSelectInstance.addItem("{{ $sales['account_id'] }}");

                    var tomSelectInstance = document.querySelector('#payment_method_id').tomselect;
                    tomSelectInstance.addItem("{{ $payment['payment_method_id'] }}");

                    var tomSelectInstance = document.querySelector('#inventory_id').tomselect;
                    tomSelectInstance.clear();
                    document.querySelector('#inventory_id').tomselect.open();
                });
                window.addEventListener('AddToCustomerSelectBox', event => {
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
