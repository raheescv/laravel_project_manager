<div>
    <div class="col-md-12 mb-4">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <form wire:submit="submit">
                    <!-- Customer & Basic Info Section -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-3">
                            <div class="card h-100 border">
                                <div class="card-body">
                                    <div class="mb-3" wire:ignore>
                                        <label for="account_id" class="form-label fw-semibold">Customer</label>
                                        {{ html()->select('account_id', $accounts)->value($sales['account_id'])->class('select-customer_id')->id('account_id')->placeholder('Select Customer') }}
                                    </div>
                                    <div class="table-responsive mt-2">
                                        <table class="table table-sm m-0">
                                            <tr class="bg-light">
                                                <th>Balance</th>
                                                <th class="text-end">{{ currency($account_balance ?? 0) }}</th>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            @if ($sales['account_id'] == 3)
                                <div class="card h-100 border">
                                    <div class="card-body">
                                        <label for="customer_name" class="form-label fw-semibold">Customer Details</label>
                                        {{ html()->input('customer_name')->value('')->class('form-control mb-3')->placeholder('Enter Customer Name')->id('customer_name')->attribute('wire:model', 'sales.customer_name') }}
                                        {{ html()->input('customer_mobile')->value('')->class('form-control')->placeholder('Enter Customer Mobile')->attribute('wire:model', 'sales.customer_mobile') }}
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="col-md-3">
                            <div class="card h-100 border">
                                <div class="card-body">
                                    <label for="reference_no" class="form-label fw-semibold">Reference & Type</label>
                                    {{ html()->input('reference_no')->value('')->class('form-control mb-3')->placeholder('Enter Reference No')->attribute('wire:model', 'sales.reference_no') }}
                                    {{ html()->select('sale_type', priceTypes())->class('form-select')->id('sale_type')->attribute('wire:model.live', 'sales.sale_type')->required(true)->placeholder('Select Sale Type') }}
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="card h-100 border">
                                <div class="card-body">
                                    <label class="form-label fw-semibold">Dates</label>
                                    <div class="mb-3">
                                        {{ html()->date('date')->value('')->class('form-control')->attribute('wire:model', 'sales.date') }}
                                        <small class="text-muted">Sale Date</small>
                                    </div>
                                    <div>
                                        {{ html()->date('due_date')->value('')->class('form-control')->attribute('wire:model', 'sales.due_date') }}
                                        <small class="text-muted">Due Date</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Item Selection Section -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-light py-3">
                            <h5 class="card-title mb-0">
                                <i class="demo-psi-cart me-2"></i>
                                Item Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-4">
                                <div class="col-md-4">
                                    <div class="form-group" wire:ignore>
                                        {{ html()->select('employee_id', $employees)->value($employee_id)->class('select-employee_id-list')->id('employee_id')->attribute('style', 'width:100%')->placeholder('Select Employee') }}
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="form-group" wire:ignore>
                                        {{ html()->select('inventory_id', [])->value('')->class('select-inventory-product_id-list')->id('inventory_id')->attribute('style', 'width:100%')->placeholder('Select Product') }}
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover align-middle table-striped">
                                    <thead class="bg-light">
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
                                            <th>Action</th>
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
                                                <th colspan="9" class="bg-light">{{ $first['employee_name'] }}</th>
                                            </tr>
                                            @foreach ($groupedItems as $item)
                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td>{{ $item['name'] }}</td>
                                                    <td>
                                                        {{ html()->number('unit_price')->value($item['unit_price'])->class('form-control form-control-sm text-end px-1')->attribute('wire:model.live', 'items.' . $item['key'] . '.unit_price') }}
                                                    </td>
                                                    <td>
                                                        {{ html()->number('quantity')->value($item['quantity'])->attribute('min', 1)->class('form-control form-control-sm text-end px-1')->attribute('step', 'any')->attribute('wire:model.live', 'items.' . $item['key'] . '.quantity') }}
                                                    </td>
                                                    <td>
                                                        {{ html()->number('discount')->value($item['discount'])->class('form-control form-control-sm text-end px-1')->attribute('wire:model.live', 'items.' . $item['key'] . '.discount') }}
                                                    </td>
                                                    <td>
                                                        {{ html()->number('tax')->value($item['tax'])->attribute('max', '50')->class('form-control form-control-sm text-end px-1')->attribute('wire:model.live', 'items.' . $item['key'] . '.tax') }}
                                                    </td>
                                                    <td class="text-end fw-bold">{{ currency($item['total']) }}</td>
                                                    @if ($sales['other_discount'] > 0)
                                                        <td class="text-end fw-bold">{{ currency($item['effective_total']) }}</td>
                                                    @endif
                                                    <td>
                                                        <button type="button" wire:click="removeItem('{{ $item['key'] }}')" wire:confirm="Are your sure?" class="btn btn-xs btn-outline-danger">
                                                            <i class="demo-pli-recycling"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endforeach
                                    </tbody>
                                    <tfoot class="table-group-divider">
                                        @php
                                            $items = collect($items);
                                        @endphp
                                        <tr class="fw-bold">
                                            <th colspan="3" class="text-end">Total</th>
                                            <th class="text-end">{{ currency($items->sum('quantity')) }}</th>
                                            <th class="text-end">{{ currency($items->sum('discount')) }}</th>
                                            <th class="text-end">{{ currency($items->sum('tax_amount')) }}</th>
                                            <th class="text-end">{{ currency($items->sum('total')) }}</th>
                                            @if ($sales['other_discount'] > 0)
                                                <th class="text-end">{{ currency($items->sum('effective_total')) }}</th>
                                            @endif
                                            <th></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Totals & Payment Section -->
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="card shadow-sm h-100">
                                <div class="card-header bg-light py-3">
                                    <h5 class="card-title mb-0">Order Summary</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-borderless">
                                            <tr>
                                                <th width="50%">Gross Total</th>
                                                <td class="text-end">
                                                    {{ currency($sales['gross_amount']) }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Sale Total</th>
                                                <td class="text-end">
                                                    {{ currency($sales['total']) }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Other Discount</th>
                                                <td>
                                                    {{ html()->number('other_discount')->value('')->class('form-control form-control-sm text-end')->attribute('wire:model.lazy', 'sales.other_discount') }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Freight</th>
                                                <td>
                                                    {{ html()->number('freight')->value('')->class('form-control form-control-sm text-end')->attribute('wire:model.lazy', 'sales.freight') }}
                                                </td>
                                            </tr>
                                        </table>
                                    </div>

                                    <div class="mt-3">
                                        <label for="address" class="form-label fw-semibold">Delivery Address</label>
                                        {{ html()->textarea('address')->value('')->class('form-control')->rows(8)->attribute('wire:model.live', 'sales.address') }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card shadow-sm h-100">
                                <div class="card-header bg-light py-3">
                                    <h5 class="card-title mb-0">Payment Details</h5>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-info mb-4">
                                        <div class="text-center">
                                            <h4 class="alert-heading">Total Payable Amount</h4>
                                            <div class="fs-2 fw-bold">{{ currency($sales['grand_total']) }}</div>
                                        </div>
                                    </div>

                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Payment Method</th>
                                                <th class="text-end">Amount</th>
                                                <th width="10%">Action</th>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <div wire:ignore>
                                                        {{ html()->select('payment_method_id', $paymentMethods)->value($default_payment_method_id)->class('select-payment_method_id-list')->id('payment_method_id')->placeholder('Select Payment Method') }}
                                                    </div>
                                                </td>
                                                <td>
                                                    {{ html()->number('amount')->value('')->class('form-control text-end')->attribute('step', 'any')->id('payment')->attribute('wire:model.live', 'payment.amount') }}
                                                </td>
                                                <td>
                                                    <button type="button" wire:click="addPayment" class="btn btn-primary w-100">
                                                        <i class="demo-psi-add"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($payments as $key => $item)
                                                <tr>
                                                    <td>{{ $item['name'] }}</td>
                                                    <td class="text-end">{{ currency($item['amount']) }}</td>
                                                    <td>
                                                        <button type="button" wire:click="removePayment('{{ $key }}')" wire:confirm="Are your sure?"
                                                            class="btn btn-xs btn-outline-danger">
                                                            <i class="demo-pli-recycling"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>

                                    @if ($this->getErrorBag()->count())
                                        <div class="alert alert-danger mt-3">
                                            <ul class="mb-0">
                                                @foreach ($this->getErrorBag()->toArray() as $error)
                                                    <li>{{ $error[0] }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif

                                    <div class="row mt-4">
                                        <div class="col-md-6">
                                            <div class="table-responsive">
                                                <table class="table table-sm table-borderless">
                                                    <tbody class="text-muted">
                                                        @isset($sales['created_user']['name'])
                                                            <tr>
                                                                <td>Created By:</td>
                                                                <td><strong>{{ $sales['created_user']['name'] }}</strong></td>
                                                            </tr>
                                                            <tr>
                                                                <td>Updated By:</td>
                                                                <td><strong>{{ $sales['updated_user']['name'] ?? '' }}</strong></td>
                                                            </tr>
                                                        @endisset
                                                        @isset($sales['cancelled_user']['name'])
                                                            <tr>
                                                                <td>Cancelled By:</td>
                                                                <td><strong>{{ $sales['cancelled_user']['name'] ?? '' }}</strong></td>
                                                            </tr>
                                                        @endisset
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <ul class="list-group list-group-flush">
                                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                                    <div class="fw-semibold">Total Paid:</div>
                                                    <span class="fs-5 text-success">{{ currency($sales['paid']) }}</span>
                                                </li>
                                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                                    <div class="fw-semibold">Balance:</div>
                                                    <span class="fs-5 text-danger">{{ currency($sales['balance']) }}</span>
                                                </li>
                                                <li class="list-group-item px-0">
                                                    <div class="form-check">
                                                        <label class="form-check-label">
                                                            {{ html()->checkbox('send_to_whatsapp')->class('form-check-input')->attribute('wire:model.live', 'send_to_whatsapp') }}
                                                            Send Invoice To WhatsApp
                                                        </label>
                                                    </div>
                                                </li>
                                            </ul>

                                            <div class="d-flex justify-content-end gap-2 mt-4">
                                                @if ($sales['status'] != 'completed')
                                                    <button type="button" wire:click='save("draft")' class="btn btn-secondary">
                                                        <i class="demo-psi-file me-1"></i> Save as Draft
                                                    </button>
                                                    <button type="submit" wire:confirm="Are you sure to submit this?" class="btn btn-primary">
                                                        <i class="demo-psi-printer me-1"></i> Submit & Print
                                                    </button>
                                                @else
                                                    <button type="submit" wire:confirm="Are you sure to update this?" class="btn btn-warning">
                                                        <i class="demo-psi-printer me-1"></i> Update & Print
                                                    </button>
                                                @endif
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
                // to open the dropdown by on load
                if (employee_id) {
                    document.querySelector('#inventory_id').tomselect.open();
                } else {
                    document.querySelector('#employee_id').tomselect.open();
                }
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
