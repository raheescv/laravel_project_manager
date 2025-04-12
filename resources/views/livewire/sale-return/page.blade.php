<div>
    @push('styles')
        <link rel="stylesheet" href="{{ asset('assets/pos/pos.css?v=2') }}">
    @endpush
    <div class="main-wrapper">
        <div class="page-wrapper pos-pg-wrapper ms-0">
            <div class="content pos-design p-0">
                <form wire:submit="submit">
                    <div class="row align-items-start pos-wrapper">
                        <div class="col-md-12 col-lg-7">
                            <div class="pos-categories tabs_wrapper">
                                <div class="pos-products">
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <div style="width:49% !important" wire:ignore>
                                            <b>Customer <span class="pull-right"> <i id="viewCustomer" class="fa pointer fa-eye"></i> </span></b>
                                            <div class="input-group mb-3">
                                                <div wire:ignore class="parent-container">
                                                    {{ html()->select('account_id', $accounts)->value($sale_returns['account_id'])->class('select-customer_id')->id('account_id')->placeholder('Select Customer') }}
                                                </div>
                                            </div>
                                        </div>

                                        <div style="width:49% !important" wire:ignore>
                                            <b>Invoices</b>
                                            <div class="input-group mb-3">
                                                <div wire:ignore class="parent-container">
                                                    {{ html()->select('sale_id', [])->value($sale_id)->class('select-customer_sales-list')->id('sale_id')->placeholder('Select Invoice') }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <div class="input-group">
                                            <input type="search" style="padding: 5px !important" class="form-control form-control-sm w-50" wire:model.live="product_key"
                                                placeholder="Search Products/Service">
                                        </div>
                                    </div>
                                    <div class="tabs_container" style="height: 80vh; overflow: auto;  overflow-x: hidden; padding-right: 10px;">
                                        <div class="tab_content active" data-tab="all">
                                            <livewire:sale-return.product-list :sale_id="$sale_id" :product_key="$product_key" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12 col-lg-5 ps-0">
                            <aside class="product-order-list">
                                @if (false)
                                    <div class="head d-flex align-items-center justify-content-between w-100">
                                        <span>Transaction ID : {{ $sale_returns['id'] ?? '' }}</span>
                                    </div>
                                @endif
                                <div class="product-added block-section">
                                    <div class="d-flex align-items-center justify-content-between">
                                        @php
                                            $total_quantity = collect($items)->sum('quantity');
                                        @endphp
                                        <h6 class="d-flex align-items-center mb-0">
                                            Product Added <span class="count">{{ $total_quantity }}</span>
                                            &nbsp;
                                        </h6>
                                        @if ($total_quantity)
                                            <i class="fa fa-eye d-flex align-items-center pointer" wire:click="viewItems()">
                                                &nbsp; View all
                                            </i>
                                            <i class="fa fa-close d-flex align-items-center text-danger pointer" wire:confirm="Are you sure to delete this?" wire:click="deleteAllItems()">
                                                &nbsp; Clear all
                                            </i>
                                        @endif
                                    </div>
                                    <div class="product-wrap">
                                        @foreach ($items as $item)
                                            <div class="product-list d-flex align-items-center justify-content-between @if ($loop->index % 2 != 0) bg-custom-gray @endif">
                                                <div class="d-flex align-items-center product-info">
                                                    <div class="info">
                                                        <h6> {{ $item['name'] }} </h6>
                                                        <p>{{ currency($item['total']) }}</p>
                                                    </div>
                                                </div>
                                                <div class="qty-item text-center">
                                                    <i class="fa fa-minus-circle dec d-flex justify-content-center align-items-center" wire:click="modifyQuantity('{{ $item['key'] }}','minus')"></i>

                                                    {{ html()->text('quantity')->value($item['quantity'])->attribute('min', 1)->class('form-control text-center')->attribute('wire:model.live', 'items.' . $item['key'] . '.quantity') }}

                                                    <i class="fa fa-plus-circle inc d-flex justify-content-center align-items-center" wire:click="modifyQuantity('{{ $item['key'] }}','plus')"></i>
                                                </div>
                                                <div class="d-flex align-items-center action">
                                                    <i wire:click="editItem('{{ $item['key'] }}')" class="fa fa-edit pointer me-2"></i>
                                                    <i wire:confirm="Are you sure to delete this?" wire:click="removeItem('{{ $item['key'] }}')" class="fa fa-trash pointer"></i>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="block-section">
                                    <div class="row">
                                        <div class="col-12 col-sm-12 mb-3">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <b>Reference No</b>
                                                    {{ html()->text('reference_no')->value('')->class('form-control  select_on_focus')->style('padding:5px')->attribute('wire:model', 'sale_returns.reference_no') }}
                                                </div>
                                                <div class="col-md-6">
                                                    <b>Discount</b>
                                                    {{ html()->text('other_discount')->value('')->class('form-control number select_on_focus')->style('padding:5px')->attribute('wire:model.lazy', 'sale_returns.other_discount') }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="order-total">
                                        <table class="table table-responsive table-borderless">
                                            <tr>
                                                <td>Sub Total</td>
                                                <td class="text-end">{{ currency($sale_returns['total']) }}</td>
                                            </tr>
                                            @php
                                                $discount = $sale_returns['total'] ? round(($sale_returns['other_discount'] / $sale_returns['total']) * 100, 2) : 0;
                                            @endphp
                                            <tr>
                                                <td class="danger">Discount ({{ $discount }}%)</td>
                                                <td class="danger text-end">{{ currency($sale_returns['other_discount']) }}</td>
                                            </tr>
                                            <tr>
                                                <td>Total</td>
                                                <td class="text-end">{{ currency($sale_returns['grand_total']) }}</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                                <div class="block-section payment-method">
                                    {{-- <h6 class="d-flex justify-content-between align-items-center">
                                        <span>Payment Method</span>
                                        <label for="send_to_whatsapp" class="form-check-label text-end">
                                            {{ html()->checkbox('send_to_whatsapp')->value('')->class('form-check-input')->attribute('wire:model.live', 'send_to_whatsapp') }}
                                            Send Invoice To Whatsapp
                                        </label>
                                    </h6> --}}

                                    <div class="row d-flex align-items-center justify-content-center methods">
                                        <div class="col-md-6 col-lg-4 item">
                                            <div class="default-cover">
                                                <a href="#" class="@if ($payment_method_name == 'cash') active @endif" wire:click="selectPaymentMethod('cash')">
                                                    <img src="{{ asset('assets/img/cash-pay.svg') }}" alt="Payment Method">
                                                    <span> Cash </span>
                                                </a>
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-lg-4 item">
                                            <div class="default-cover">
                                                <a href="#" class="@if ($payment_method_name == 'card') active @endif" wire:click="selectPaymentMethod('card')">
                                                    <img src="{{ asset('assets/img/card-pay.svg') }}" alt="Payment Method">
                                                    <span> Debit Card </span>
                                                </a>
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-lg-4 item">
                                            <div class="default-cover">
                                                <a href="#" class="@if ($payment_method_name == 'custom') active @endif" wire:click="selectPaymentMethod('custom')">
                                                    <img src="{{ asset('assets/img/custom-pay.svg') }}" alt="Payment Method">
                                                    <span>Custom Pay</span>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="btn-row d-sm-flex align-items-center justify-content-between">
                                    <button type="submit" wire:confirm="Are you sure to submit this?" class="btn btn-success btn-icon flex-fill">Submit</button>
                                </div>
                            </aside>
                        </div>
                        <div class="col-md-12 col-lg-4 ps-0">
                            @if ($this->getErrorBag()->count())
                                <ol>
                                    <?php foreach ($this->getErrorBag()->toArray() as $value): ?>
                                    <li style="color:red">* {{ $value[0] }}</li>
                                    <?php endforeach; ?>
                                </ol>
                            @endif
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @push('scripts')
        <script src="{{ asset('assets/pos/feather.min.js') }}"></script>
        <script src="{{ asset('assets/pos/script.js') }}"></script>
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
                        confirmButtonText: "Save",
                        confirmButtonColor: '#24447f',
                        cancelButtonColor: '#d33',
                    }).then((result) => {
                        if (result.isConfirmed) {
                            @this.call('save', 'completed');
                        }
                    });
                });
            });
            $('#addCustomer').click(function() {
                Livewire.dispatch("Customer-Page-Create-Component");
            });
            $('#viewCustomer').click(function() {
                Livewire.dispatch("Customer-View-Component", {
                    'account_id': $('#account_id').val()
                });
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
                @this.set('sale_returns.account_id', data['id']);
            });
            window.addEventListener('ResetSelectBox', event => {
                var tomSelectInstance = document.querySelector('#account_id').tomselect;
                tomSelectInstance.addItem("{{ $sale_returns['account_id'] }}");

                var tomSelectInstance = document.querySelector('#sale_id').tomselect;
                tomSelectInstance.clear();
            });
        </script>
        <script>
            $(document).ready(function() {
                $('#account_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('sale_returns.account_id', value);
                    document.querySelector('#sale_id').tomselect.open();
                });
                $('#sale_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('sale_id', value);
                });
                window.addEventListener('OpenCustomerDropBox', event => {
                    document.querySelector('#account_id').tomselect.open();
                });
            });
        </script>
    @endpush
</div>
