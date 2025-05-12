<div>
    @push('styles')
        <link rel="stylesheet" href="{{ asset('assets/pos/pos.css?v=3') }}">
        <style>
            .category-sidebar {
                padding: 10px;
                background: #f8f9fa;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            }

            .category-button {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 12px 15px;
                margin: 5px 0;
                border-radius: 6px;
                background: white;
                border: 1px solid #e0e0e0;
                cursor: pointer;
                transition: all 0.3s ease;
            }

            .category-button:hover {
                background: #eef2ff;
                transform: translateX(3px);
            }

            .category-button.active {
                background: #4f46e5;
                color: white;
            }

            .category-button.active-favorite {
                background: #ffd700;
                color: #333;
            }

            .badge {
                background: #e5e7eb;
                color: #374151;
                padding: 2px 8px;
                border-radius: 12px;
                font-size: 0.8em;
            }

            .category-button.active .badge {
                background: rgba(255, 255, 255, 0.2);
                color: white;
            }

            .hover-scale {
                transition: transform 0.2s ease;
            }

            .hover-scale:hover {
                transform: scale(1.02);
            }

            .action-buttons {
                box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.05);
                padding: 15px;
                background: white;
                border-radius: 0 0 8px 8px;
            }
        </style>
    @endpush
    <div class="main-wrapper">
        <div class="page-wrapper pos-pg-wrapper ms-0">
            <div class="content pos-design p-0">
                <form wire:submit="submit">
                    <div class="row align-items-start pos-wrapper">
                        <div class="col-md-12 col-lg-2" style="height: 90vh; overflow: auto; overflow-x: hidden;">
                            <div class="category-sidebar">
                                <div class="category-button @if ($category_id == 'favorite') active-favorite @endif" wire:click="categorySelect('favorite')">
                                    <i class="fa fa-star"></i>
                                    <span>Favorite</span>
                                </div>

                                <div class="category-button @if ($category_id == '') active @endif" wire:click="categorySelect('')">
                                    <i class="fa fa-th-large"></i>
                                    <span>All Products</span>
                                </div>

                                @foreach ($categories as $item)
                                    <div class="category-button @if ($item['id'] == $category_id) active @endif" wire:click="categorySelect({{ $item['id'] }})">
                                        <span>{{ $item['name'] }}</span>
                                        <span class="badge">{{ $item['products_count'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="col-md-12 col-lg-6">
                            <div class="pos-categories tabs_wrapper">
                                <div class="pos-products">
                                    <div class="sales-header p-3 bg-white rounded-lg shadow-sm mb-4">
                                        <div class="row g-3">
                                            <div class="col-lg-8">
                                                <div class="form-group" wire:ignore>
                                                    <label class="mb-2 d-flex align-items-center">
                                                        <i class="fa fa-user me-2 text-primary"></i>
                                                        <span class="fw-semibold">Select Employee</span>
                                                    </label>
                                                    {{ html()->select('employee_id', $employees ?? [])->value($employee_id ?? '')->class('select-employee_id-list')->id('employee_id')->attribute('style', 'width:100%')->placeholder('Choose an employee...') }}
                                                </div>
                                            </div>
                                            <div class="col-lg-4">
                                                <div class="form-group">
                                                    <label class="mb-2 d-flex align-items-center">
                                                        <i class="fa fa-tags me-2 text-primary"></i>
                                                        <span class="fw-semibold">Sale Type</span>
                                                    </label>
                                                    {{ html()->select('sale_type', priceTypes())->class('form-select form-select-sm border-0 shadow-sm')->id('sale_type')->attribute('wire:model.live', 'sales.sale_type')->required(true)->placeholder('Select type...') }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="search-section mb-4">
                                        <div class="d-flex gap-2">
                                            <div class="search-box flex-grow-2">
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light border-end-0">
                                                        <i class="fa fa-barcode"></i>
                                                    </span>
                                                    <input type="search" class="form-control border-start-0" wire:model.live="barcode_key" placeholder="Scan Barcode"
                                                        style="border-radius: 0 4px 4px 0;">
                                                </div>
                                            </div>

                                            <div class="search-box flex-grow-1">
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light border-end-0">
                                                        <i class="fa fa-search"></i>
                                                    </span>
                                                    <input type="search" class="form-control border-start-0" wire:model.live="product_key" placeholder="Search Products/Services"
                                                        style="border-radius: 0 4px 4px 0;">
                                                </div>
                                            </div>

                                            <button type="button" id="viewDraftedSales" class="btn btn-primary d-flex align-items-center gap-2" style="min-width: 120px;">
                                                <i class="fa fa-file-alt"></i>
                                                <span>View Draft</span>
                                            </button>
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
                                    <div class="cart-summary mb-3">
                                        @php
                                            $total_quantity = collect($items)->sum('quantity');
                                        @endphp
                                        <div class="d-flex align-items-center justify-content-between bg-white rounded-lg p-3 shadow-sm">
                                            <!-- Cart Info -->
                                            <div class="d-flex align-items-center gap-3">
                                                <span class="cart-badge">{{ $total_quantity }}</span>
                                                <div>
                                                    <h6 class="mb-0 text-dark">Cart Items</h6>
                                                    <small class="text-muted">{{ $total_quantity }} items in cart</small>
                                                </div>
                                            </div>
                                            <!-- Action Buttons -->
                                            @if ($total_quantity)
                                                <div class="action-group d-flex gap-2">
                                                    @can('sale.combo offer')
                                                        <div class="d-flex flex-column align-items-center">
                                                            <button type='button' wire:click="manageComboOffer()" class="action-btn package-btn" title="Manage Combo Offer">
                                                                <i class="fa fa-cube"></i>
                                                            </button>
                                                            <small>Combo Offer</small>
                                                        </div>
                                                    @endcan
                                                    <div class="d-flex flex-column align-items-center">
                                                        <button type='button' wire:click="viewItems()" class="action-btn view-btn" title="View Items">
                                                            <i class="fa fa-list"></i>
                                                        </button>
                                                        <small>View</small>
                                                    </div>
                                                    <div class="d-flex flex-column align-items-center">
                                                        <button type='button' wire:confirm="Are you sure to delete this?" wire:click="deleteAllItems()" class="action-btn delete-btn"
                                                            title="Delete All Items">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                        <small>Delete</small>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
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
                                    <div class="customer-info-section mb-4">
                                        <div class="row g-3">
                                            <!-- Customer Selection -->
                                            <div class="col-md-6">
                                                <div class="form-group" wire:ignore>
                                                    <label class="d-flex justify-content-between align-items-center mb-2">
                                                        <span class="fw-bold">Customer</span>
                                                        <i id="viewCustomer" class="fa fa-eye pointer hover-opacity" title="View Customer Details"></i>
                                                    </label>
                                                    {{ html()->select('account_id', $accounts)->value($sales['account_id'])->class('select-customer_id')->id('account_id')->placeholder('Select Customer') }}
                                                </div>
                                            </div>

                                            <!-- Customer Mobile -->
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="fw-bold mb-2">Customer Mobile</label>
                                                    <div class="input-group">
                                                        {{ html()->text('customer_mobile')->class('form-control border-start-0 select_on_focus')->attribute('wire:model', 'sales.customer_mobile')->id('customer_mobile')->placeholder('Mobile number') }}
                                                        <button type="button" id="addCustomer" class="btn btn-light border" title="Add New Customer">
                                                            <i class="fa fa-user-plus"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Discount -->
                                            <div class="col-12">
                                                <div class="form-group">
                                                    <label class="fw-bold mb-2">Discount Amount</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text bg-light border-end-0">
                                                            <i class="fa fa-tag text-muted"></i>
                                                        </span>
                                                        {{ html()->text('other_discount')->class('form-control border-start-0 number select_on_focus')->attribute('wire:model.lazy', 'sales.other_discount')->placeholder('Enter discount amount') }}
                                                    </div>
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
                                <div class="action-buttons mt-4">
                                    <div class="d-flex gap-3">
                                        @can('sale.feedback')
                                            <button type="button" wire:click="openFeedback" class="btn btn-outline-primary d-flex align-items-center gap-2" style="min-width: 130px;">
                                                <i class="fa fa-comment"></i>
                                                <span>Feedback</span>
                                            </button>
                                        @endcan
                                        <div class="d-flex flex-fill gap-3">
                                            <button type="button" wire:click='save("draft")' class="btn btn-secondary flex-fill hover-scale">
                                                <i class="fa fa-save me-2"></i>
                                                Draft
                                            </button>
                                            <button type="submit" wire:confirm="Are you sure to submit this?" class="btn btn-primary flex-fill hover-scale">
                                                <i class="fa fa-check-circle me-2"></i>
                                                Submit
                                            </button>
                                        </div>
                                    </div>
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
    <x-sale.combo-offer-modal :id="$sales['id'] ?? ''" />
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
                        showDenyButton: true,
                        denyButtonText: `Save & New`,
                        confirmButtonText: "Save & Print",
                        confirmButtonColor: '#24447f',
                        cancelButtonColor: '#d33',
                        denyButtonColor: '#0db7f0',
                    }).then((result) => {
                        if (result.isConfirmed) {
                            @this.call('save', 'completed', true);
                        } else if (result.isDenied) {
                            @this.call('save', 'completed', false);
                        }
                    });
                });
            });
            $('#addCustomer').click(function() {
                Livewire.dispatch("Customer-Page-Create-Component", {
                    'mobile': $('#customer_mobile').val()
                });
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
                $('#account_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('sales.account_id', value);
                });
                window.addEventListener('OpenEmployeeDropBox', event => {
                    document.querySelector('#employee_id').tomselect.open();
                });
            });
        </script>
    @endpush
</div>
