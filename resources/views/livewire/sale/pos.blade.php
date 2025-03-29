<div>
    @push('styles')
        <link rel="stylesheet" href="{{ asset('assets/pos/pos.css?v=2') }}">
    @endpush
    <div class="main-wrapper">
        <div class="page-wrapper pos-pg-wrapper ms-0">
            <div class="content pos-design p-0">
                <form wire:submit="submit">
                    <div class="row align-items-start pos-wrapper">
                        <div class="col-md-12 col-lg-2" style="height: 90vh; overflow: auto;  overflow-x: hidden;">
                            <button type="button" class="side-button @if ($category_id == 'favorite') favorite @endif" wire:click="categorySelect('favorite')">
                                Favorite
                            </button>
                            <button type="button" class="side-button @if ($category_id == '') active @endif" wire:click="categorySelect('')">
                                All
                            </button>
                            @foreach ($categories as $item)
                                <button type="button" class="side-button @if ($item['id'] == $category_id) active @endif" wire:click="categorySelect({{ $item['id'] }})">
                                    {{ $item['name'] }}({{ $item['products_count'] }})
                                </button>
                            @endforeach
                        </div>
                        <div class="col-md-12 col-lg-6">
                            <div class="pos-categories tabs_wrapper">
                                <div class="pos-products">
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <div style="width:70% !important" wire:ignore>
                                            <b>Employee</b>
                                            {{ html()->select('employee_id', $employees ?? [])->value($employee_id ?? '')->class('select-employee_id-list')->id('employee_id')->attribute('style', 'width:100%')->placeholder('Select Employee') }}
                                        </div>
                                        <div style="width:29% !important">
                                            <b>Sale Type</b>
                                            {{ html()->select('sale_type', priceTypes())->class('form-control')->id('sale_type')->attribute('wire:model.live', 'sales.sale_type')->required(true)->placeholder('Select Sale Type') }}
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <div class="input-group">
                                            <input type="search" style="padding: 5px !important" class="form-control form-control-sm w-25" wire:model.live="barcode_key" placeholder="Scan Barcode">
                                            <input type="search" style="padding: 5px !important" class="form-control form-control-sm w-50" wire:model.live="product_key"
                                                placeholder="Search Products/Service">
                                            <button type="button" class="btn btn-sm btn-info w-25" id="viewDraftedSales">View Draft</button>
                                        </div>
                                    </div>
                                    <div class="tabs_container" style="height: 80vh; overflow: auto;  overflow-x: hidden; padding-right: 10px;">
                                        <div class="tab_content active" data-tab="all">
                                            <livewire:sale.product-list :sale_type="$sales['sale_type']" :category_id="$category_id" :product_key="$product_key" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12 col-lg-4 ps-0">
                            <aside class="product-order-list">
                                @if (false)
                                    <div class="head d-flex align-items-center justify-content-between w-100">
                                        <span>Transaction ID : {{ $sales['id'] ?? '' }}</span>
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
                                                <div class="d-flex align-items-center product-info" data-bs-toggle="modal" data-bs-target="#products">
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
                                        <div class="col-12 col-sm-12">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <b>Customer <span class="pull-right"> <i class="fa fa-eye"></i> </span></b>
                                                    <div class="input-group mb-3">
                                                        <div wire:ignore class="parent-container">
                                                            {{ html()->select('account_id', $accounts)->value($sales['account_id'])->class('select-customer_id')->id('account_id')->placeholder('Select Customer') }}
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <b>Customer Mobile</b>
                                                    <div class="input-group mb-3">
                                                        {{ html()->text('customer_mobile')->value('')->class('form-control select_on_focus')->style('padding:5px')->attribute('wire:model', 'sales.customer_mobile')->id('customer_mobile')->placeholder('Mobile No') }}
                                                        &nbsp;&nbsp;<i class="fa fa-2x fa-user-plus pointer" id="addCustomer"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 col-sm-12 mb-3">
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <b>Discount</b>
                                                    {{ html()->text('other_discount')->value('')->class('form-control number select_on_focus')->style('padding:5px')->attribute('wire:model.lazy', 'sales.other_discount') }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="order-total">
                                        <table class="table table-responsive table-borderless">
                                            <tr>
                                                <td>Sub Total</td>
                                                <td class="text-end">{{ currency($sales['total']) }}</td>
                                            </tr>
                                            @php
                                                $discount = $sales['total'] ? round(($sales['other_discount'] / $sales['total']) * 100, 2) : 0;
                                            @endphp
                                            <tr>
                                                <td class="danger">Discount ({{ $discount }}%)</td>
                                                <td class="danger text-end">{{ currency($sales['other_discount']) }}</td>
                                            </tr>
                                            <tr>
                                                <td>Total</td>
                                                <td class="text-end">{{ currency($sales['grand_total']) }}</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                                <div class="block-section payment-method">
                                    <h6 class="d-flex justify-content-between align-items-center">
                                        <span>Payment Method</span>
                                        <label for="send_to_whatsapp" class="form-check-label text-end">
                                            {{ html()->checkbox('send_to_whatsapp')->value('')->class('form-check-input')->attribute('wire:model.live', 'send_to_whatsapp') }}
                                            Send Invoice To Whatsapp
                                        </label>
                                    </h6>

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
                                    <button type="button" wire:click='save("draft")' class="btn btn-info btn-icon flex-fill">Draft</button>
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
            $('#addCustomer').click(function() {
                Livewire.dispatch("Customer-Page-Create-Component", {
                    'mobile': $('#customer_mobile').val()
                });
            });
            window.addEventListener('AddToCustomerSelectBox', event => {
                var data = event.detail[0];
                @this.set('sales.account_id', data['id']);
            });
            $('#viewDraftedSales').click(function() {
                Livewire.dispatch("Sale-View-DraftTable-Component");
            });
        </script>
        <script>
            $(document).ready(function() {
                document.querySelector('#employee_id').tomselect.open();
                $('#employee_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('employee_id', value);
                });
                window.addEventListener('OpenEmployeeDropBox', event => {
                    document.querySelector('#employee_id').tomselect.open();
                });
            });
        </script>
    @endpush
</div>
