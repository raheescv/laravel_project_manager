<div>
    <div class="col-md-12 mb-4">
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-body p-4">
                <form wire:submit="submit">
                    <!-- Customer & Basic Info Section -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-3">
                            <div class="card h-100 border-0 shadow-sm rounded-3 bg-body-tertiary">
                                <div class="card-body p-3">
                                    <div class="mb-3" wire:ignore>
                                        <label for="account_id" class="form-label fw-semibold text-primary"><i class="fa fa-user me-2"></i>Customer</label>
                                        <div class="ts-wrapper-container position-relative">
                                            <div class="input-group">
                                                {{ html()->select('account_id', $accounts)->value($sales['account_id'])->class('select-customer_id')->id('account_id')->attribute('style', 'width:100%')->placeholder('Select Customer') }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="table-responsive mt-2">
                                        <table class="table table-sm m-0">
                                            <tr class="bg-light">
                                                <th><i class="fa fa-balance-scale me-1"></i> Balance</th>
                                                <th class="text-end">{{ currency($account_balance ?? 0) }}</th>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            @if ($sales['account_id'] == 3)
                                <div class="card h-100 border-0 shadow-sm rounded-3 bg-body-tertiary">
                                    <div class="card-body p-3">
                                        <label for="customer_name" class="form-label fw-semibold text-primary"><i class="fa fa-address-card me-2"></i>Customer Details</label>
                                        <div class="input-group mb-3">
                                            <span class="input-group-text"><i class="fa fa-user"></i></span>
                                            {{ html()->input('customer_name')->value('')->class('form-control')->placeholder('Enter Customer Name')->id('customer_name')->attribute('wire:model', 'sales.customer_name') }}
                                        </div>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fa fa-phone"></i></span>
                                            {{ html()->input('customer_mobile')->value('')->class('form-control')->placeholder('Enter Customer Mobile')->attribute('wire:model', 'sales.customer_mobile') }}
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="col-md-3">
                            <div class="card h-100 border-0 shadow-sm rounded-3 bg-body-tertiary">
                                <div class="card-body p-3">
                                    <label for="reference_no" class="form-label fw-semibold text-primary"><i class="fa fa-tag me-2"></i>Reference & Type</label>
                                    <div class="input-group mb-3">
                                        <span class="input-group-text"><i class="fa fa-tag"></i></span>
                                        {{ html()->input('reference_no')->value('')->class('form-control')->placeholder('Enter Reference No')->attribute('wire:model', 'sales.reference_no') }}
                                    </div>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fa fa-list"></i></span>
                                        <div class="flex-grow-1 ts-wrapper-container">
                                            {{ html()->select('sale_type', priceTypes())->class('form-select')->id('sale_type')->attribute('wire:model.live', 'sales.sale_type')->required(true)->attribute('style', 'width:100%')->placeholder('Select Sale Type') }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="card h-100 border-0 shadow-sm rounded-3 bg-body-tertiary">
                                <div class="card-body p-3">
                                    <label class="form-label fw-semibold text-primary"><i class="fa fa-calendar me-2"></i>Dates</label>
                                    <div class="mb-3">
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fa fa-shopping-cart"></i></span>
                                            {{ html()->date('date')->value('')->class('form-control')->attribute('wire:model', 'sales.date') }}
                                        </div>
                                        <small class="text-muted ps-2 mt-1 d-block">Sale Date</small>
                                    </div>
                                    <div>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fa fa-clock-o"></i></span>
                                            {{ html()->date('due_date')->value('')->class('form-control')->attribute('wire:model', 'sales.due_date') }}
                                        </div>
                                        <small class="text-muted ps-2 mt-1 d-block">Due Date</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Item Selection Section -->
                    <div class="card shadow-sm border-0 rounded-3 mb-4">
                        <div class="card-header bg-primary text-white py-3">
                            <h5 class="card-title mb-0 text-white">
                                <i class="fa fa-cart-plus me-2"></i>
                                Item Information
                            </h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="row mb-4">
                                <div class="col-md-4">
                                    <div wire:ignore>
                                        <div class="ts-wrapper-container position-relative">
                                            <div class="input-group">
                                                <span class="input-group-text bg-secondary text-white"><i class="fa fa-user"></i></span>
                                                <div class="flex-grow-1 ts-wrapper-container">
                                                    {{ html()->select('employee_id', $employees)->value($employee_id)->class('select-employee_id-list')->id('employee_id')->attribute('style', 'width:100%')->placeholder('Select Employee') }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div wire:ignore>
                                        <div class="ts-wrapper-container position-relative">
                                            <div class="input-group">
                                                <span class="input-group-text bg-secondary text-white"><i class="fa fa-cube"></i></span>
                                                <div class="flex-grow-1 ts-wrapper-container">
                                                    {{ html()->select('inventory_id', [])->value('')->class('select-inventory-product_id-list')->id('inventory_id')->attribute('style', 'width:100%')->placeholder('Select Product') }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover align-middle table-striped border rounded-3 overflow-hidden shadow-sm">
                                    <thead class="bg-secondary text-white">
                                        <tr>
                                            <th class="py-3"><i class="fa fa-hashtag me-1"></i> SL No</th>
                                            <th width="20%" class="py-3"><i class="fa fa-cube me-1"></i> Product</th>
                                            <th class="text-end py-3"><i class="fa fa-tag me-1"></i> Unit Price</th>
                                            <th class="text-end py-3"><i class="fa fa-cubes me-1"></i> Quantity</th>
                                            <th class="text-end py-3"><i class="fa fa-tag me-1"></i> Discount</th>
                                            <th class="text-end py-3"><i class="fa fa-calculator me-1"></i> Tax %</th>
                                            <th class="text-end py-3"><i class="fa fa-money me-1"></i> Total</th>
                                            @if ($sales['other_discount'] > 0)
                                                <th class="text-end py-3"><i class="fa fa-calculator me-1"></i> Effective Total</th>
                                            @endif
                                            <th class="py-3"><i class="fa fa-cogs me-1"></i> Action</th>
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
                                                <th colspan="9" class="bg-info text-white py-2"><i class="fa fa-user-circle me-1"></i> {{ $first['employee_name'] }}</th>
                                            </tr>
                                            @foreach ($groupedItems as $item)
                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td>{{ $item['name'] }}</td>
                                                    <td>
                                                        <div class="input-group input-group-sm">
                                                            <span class="input-group-text bg-primary text-white px-1"><i class="fa fa-tag"></i></span>
                                                            {{ html()->number('unit_price')->value($item['unit_price'])->class('form-control text-end px-1')->attribute('wire:model.lazy', 'items.' . $item['key'] . '.unit_price') }}
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="input-group input-group-sm">
                                                            <span class="input-group-text bg-success text-white px-1"><i class="fa fa-cubes"></i></span>
                                                            {{ html()->number('quantity')->value($item['quantity'])->attribute('min', 1)->class('form-control text-end px-1')->attribute('step', 'any')->attribute('wire:model.lazy', 'items.' . $item['key'] . '.quantity') }}
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="input-group input-group-sm">
                                                            <span class="input-group-text bg-warning text-white px-1"><i class="fa fa-tag"></i></span>
                                                            {{ html()->number('discount')->value($item['discount'])->class('form-control text-end px-1')->attribute('wire:model.lazy', 'items.' . $item['key'] . '.discount') }}
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="input-group input-group-sm">
                                                            <span class="input-group-text bg-info text-white px-1"><i class="fa fa-calculator"></i></span>
                                                            {{ html()->number('tax')->value($item['tax'])->attribute('max', '50')->class('form-control text-end px-1')->attribute('wire:model.lazy', 'items.' . $item['key'] . '.tax') }}
                                                        </div>
                                                    </td>
                                                    <td class="text-end fw-bold">{{ currency($item['total']) }}</td>
                                                    @if ($sales['other_discount'] > 0)
                                                        <td class="text-end fw-bold">{{ currency($item['effective_total']) }}</td>
                                                    @endif
                                                    <td>
                                                        <button type="button" wire:click="removeItem('{{ $item['key'] }}')" wire:confirm="Are your sure?"
                                                            class="btn btn-sm btn-danger rounded-circle">
                                                            <i class="fa fa-trash"></i>
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
                                        <tr class="fw-bold bg-secondary text-white">
                                            <th colspan="3" class="text-end py-3"><i class="fa fa-calculator me-1"></i> Grand Total</th>
                                            <th class="text-end py-3">{{ currency($items->sum('quantity'), 3) }}</th>
                                            <th class="text-end py-3">{{ currency($items->sum('discount')) }}</th>
                                            <th class="text-end py-3">{{ currency($items->sum('tax_amount')) }}</th>
                                            <th class="text-end py-3 fs-5">{{ currency($items->sum('total')) }}</th>
                                            @if ($sales['other_discount'] > 0)
                                                <th class="text-end py-3 fs-5">{{ currency($items->sum('effective_total')) }}</th>
                                            @endif
                                            <th class="py-3"></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Totals & Payment Section -->
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="card shadow-sm h-100 border-0 rounded-3">
                                <div class="card-header bg-success text-white py-3">
                                    <h5 class="card-title mb-0 text-white"><i class="fa fa-list-alt me-2"></i>Order Summary</h5>
                                </div>
                                <div class="card-body p-4">
                                    <div class="table-responsive">
                                        <table class="table table-hover rounded-3 overflow-hidden">
                                            <tr class="bg-light">
                                                <th width="50%" class="py-3"><i class="fa fa-calculator me-1"></i> Gross Total</th>
                                                <td class="text-end fw-bold fs-5 py-3">
                                                    {{ currency($sales['gross_amount']) }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="py-3"><i class="fa fa-shopping-cart me-1"></i> Sale Total</th>
                                                <td class="text-end fw-bold fs-5 py-3">
                                                    {{ currency($sales['total']) }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="py-3"><i class="fa fa-tag me-1"></i> Other Discount</th>
                                                <td class="py-3">
                                                    <div class="input-group">
                                                        <span class="input-group-text bg-warning text-white"><i class="fa fa-tag"></i></span>
                                                        {{ html()->number('other_discount')->value('')->class('form-control text-end')->attribute('wire:model.lazy', 'sales.other_discount') }}
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="py-3"><i class="fa fa-truck me-1"></i> Freight</th>
                                                <td class="py-3">
                                                    <div class="input-group">
                                                        <span class="input-group-text bg-info text-white"><i class="fa fa-truck"></i></span>
                                                        {{ html()->number('freight')->value('')->class('form-control text-end')->attribute('wire:model.lazy', 'sales.freight') }}
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>

                                    <div class="mt-4">
                                        <div class="card border-0 shadow-sm rounded-3">
                                            <div class="card-header bg-secondary text-white">
                                                <h6 class="mb-0"><i class="fa fa-map-marker me-1"></i> Delivery Address</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="input-group">
                                                    <span class="input-group-text bg-secondary text-white"><i class="fa fa-location-arrow"></i></span>
                                                    {{ html()->textarea('address')->value('')->class('form-control')->rows(6)->attribute('wire:model.live', 'sales.address')->placeholder('Enter delivery address here...') }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card shadow-sm h-100 border-0 rounded-3">
                                <div class="card-header bg-info text-white py-3">
                                    <h5 class="card-title mb-0 text-white"><i class="fa fa-credit-card me-2"></i>Payment Details</h5>
                                </div>
                                <div class="card-body p-4">
                                    <div class="alert alert-info border-0 shadow-sm rounded-3 mb-4">
                                        <div class="text-center py-2">
                                            <h4 class="alert-heading text-info"><i class="fa fa-money me-2"></i>Total Payable Amount</h4>
                                            <div class="fs-1 fw-bold text-primary">{{ currency($sales['grand_total']) }}</div>
                                        </div>
                                    </div>
                                    <table class="table table-hover rounded-3 border">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="py-3 " width="50%"><i class="fa fa-credit-card me-1"></i> Payment Method</th>
                                                <th class="text-end py-3 "><i class="fa fa-money me-1"></i> Amount</th>
                                                <th width="10%" class="py-3 "> Action</th>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <div wire:ignore>
                                                        <div class="position-relative">
                                                            <div class="input-group">
                                                                <span class="input-group-text"><i class="fa fa-credit-card"></i></span>
                                                                <div class="flex-grow-1">
                                                                    {{ html()->select('payment_method_id', $paymentMethods)->value($default_payment_method_id)->class('select-payment_method_id-list')->id('payment_method_id')->placeholder('Select Payment Method')->attribute('style', 'width:100%') }}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="fa fa-money"></i></span>
                                                        {{ html()->number('amount')->value('')->class('form-control text-end')->attribute('step', 'any')->id('payment')->attribute('wire:model.live', 'payment.amount') }}
                                                    </div>
                                                </td>
                                                <td>
                                                    <button type="button" wire:click="addPayment" class="btn btn-primary w-100 btn-lg">
                                                        <i class="fa fa-plus"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            </tbody>
                                        <tbody>
                                            @if (count($payments))
                                                @foreach ($payments as $key => $item)
                                                    <tr>
                                                        <td>{{ $item['name'] }}</td>
                                                        <td class="text-end">{{ currency($item['amount']) }}</td>
                                                        <td>
                                                            <button type="button" wire:click="removePayment('{{ $key }}')" wire:confirm="Are your sure?"
                                                                class="btn btn-sm btn-danger rounded-circle">
                                                                <i class="fa fa-trash"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @else
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted py-4">
                                                        <i class="fa fa-info-circle me-1"></i> No payments added yet.
                                                    </td>
                                                </tr>
                                            @endif
                                        </tbody>
                                    </table>

                                    @if ($this->getErrorBag()->count())
                                        <div class="alert alert-danger mt-3 rounded-3 border-0 shadow-sm">
                                            <h5 class="mb-2 fw-bold"><i class="fa fa-exclamation-triangle me-1"></i> Validation Errors</h5>
                                            <ul class="mb-0 list-group list-group-flush">
                                                @foreach ($this->getErrorBag()->toArray() as $error)
                                                    <li class="list-group-item bg-transparent border-0 py-1"><i class="fa fa-times-circle me-1 text-danger"></i> {{ $error[0] }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif

                                    <div class="row mt-4">
                                        <div class="col-md-6">
                                            <div class="card border-0 shadow-sm rounded-3 mb-3">
                                                <div class="card-header bg-secondary text-white">
                                                    <h6 class="mb-0"><i class="fa fa-history me-1"></i> Transaction History</h6>
                                                </div>
                                                <div class="card-body p-3">
                                                    <div class="table-responsive">
                                                        <table class="table table-sm table-hover mb-0">
                                                            <tbody>
                                                                @isset($sales['created_user']['name'])
                                                                    <tr>
                                                                        <td><span class="badge bg-success"><i class="fa fa-user-plus me-1"></i> Created</span></td>
                                                                        <td><strong>{{ $sales['created_user']['name'] }}</strong></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td><span class="badge bg-info"><i class="fa fa-pencil me-1"></i> Updated</span></td>
                                                                        <td><strong>{{ $sales['updated_user']['name'] ?? 'N/A' }}</strong></td>
                                                                    </tr>
                                                                @endisset
                                                                @isset($sales['cancelled_user']['name'])
                                                                    <tr>
                                                                        <td><span class="badge bg-danger"><i class="fa fa-ban me-1"></i> Cancelled</span></td>
                                                                        <td><strong>{{ $sales['cancelled_user']['name'] ?? '' }}</strong></td>
                                                                    </tr>
                                                                @endisset
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="card border-0 shadow-sm rounded-3">
                                                <div class="card-header bg-primary text-white">
                                                    <h6 class="mb-0 text-white"><i class="fa fa-money me-1"></i> Payment Summary</h6>
                                                </div>
                                                <div class="card-body p-3">
                                                    <ul class="list-group list-group-flush">
                                                        <li class="list-group-item d-flex justify-content-between align-items-center px-3 py-3 bg-light rounded mb-2">
                                                            <div class="fw-bold"><i class="fa fa-check-circle text-success me-1"></i> Total Paid:</div>
                                                            <span class="fs-5 badge bg-success px-3 py-2">{{ currency($sales['paid']) }}</span>
                                                        </li>
                                                        <li class="list-group-item d-flex justify-content-between align-items-center px-3 py-3 bg-light rounded mb-2">
                                                            <div class="fw-bold"><i class="fa fa-exclamation-circle text-danger me-1"></i> Balance:</div>
                                                            <span class="fs-5 badge bg-danger px-3 py-2">{{ currency($sales['balance']) }}</span>
                                                        </li>
                                                        <li class="list-group-item px-3 py-3 bg-light rounded mb-2">
                                                            <div class="form-check">
                                                                <label class="form-check-label">
                                                                    {{ html()->checkbox('send_to_whatsapp')->class('form-check-input')->attribute('wire:model.live', 'send_to_whatsapp') }}
                                                                    <span class="text-success"><i class="fa fa-whatsapp me-1"></i> Send Invoice To WhatsApp</span>
                                                                </label>
                                                            </div>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>

                                            <div class="d-flex justify-content-end gap-2 mt-4">
                                                @if ($sales['status'] != 'completed')
                                                    <button type="button" wire:click='save("draft")' class="btn btn-lg btn-outline-secondary shadow-sm text-nowrap">
                                                        <i class="fa fa-save me-1"></i> Save as Draft
                                                    </button>
                                                    <button type="submit" wire:confirm="Are you sure to submit this?" class="btn btn-lg btn-primary shadow text-nowrap">
                                                        <i class="fa fa-print me-1"></i> Submit & Print
                                                    </button>
                                                @else
                                                    <button type="submit" wire:confirm="Are you sure to update this?" class="btn btn-lg btn-warning shadow text-nowrap">
                                                        <i class="fa fa-save me-1"></i> Update & <i class="fa fa-print me-1"></i> Print
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
                            <th class="text-start"><i class="fa fa-money me-1"></i> <strong>Grand Total</strong></td>
                            <td class="text-end">${data.grand_total}</td>
                        </tr>
                        <tr>
                            <th class="text-start"><i class="fa fa-credit-card me-1"></i> <strong>Payment Methods</strong></td>
                            <td class="text-end">${data.payment_methods}</td>
                        </tr>
                        <tr>
                            <th class="text-start"><i class="fa fa-check-circle me-1"></i> <strong>Paid</strong></td>
                            <td class="text-end">${data.paid}</td>
                        </tr>
                        <tr>
                            <th class="text-start"><i class="fa fa-exclamation-circle me-1"></i> <strong>Balance</strong></td>
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
