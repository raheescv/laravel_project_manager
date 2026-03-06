<div>
    @push('styles')
        <link rel="stylesheet" href="{{ asset('assets/pos/pos.css?v=2') }}">
        <style>
            .hover-lift {
                transition: transform 0.2s ease, box-shadow 0.2s ease;
            }

            .hover-lift:hover {
                transform: translateY(-1px);
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            }

            .payment-option a {
                transition: all 0.3s ease;
                border: 1px solid #e5e7eb;
                text-decoration: none;
                color: inherit;
            }

            .payment-option a:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }

            .payment-option a.active {
                background: var(--bs-primary-bg-subtle);
                border-color: var(--bs-primary);
                color: var(--bs-primary);
            }

            .payment-option img {
                transition: transform 0.2s ease;
            }

            .payment-option a:hover img {
                transform: scale(1.05);
            }

            .cart-badge {
                background: #4f46e5;
                color: white;
                padding: 0.5rem 1rem;
                border-radius: 9999px;
                font-weight: 600;
            }

            .action-btn {
                width: 40px;
                height: 40px;
                border-radius: 8px;
                display: flex;
                align-items: center;
                justify-content: center;
                border: none;
                background: #f3f4f6;
                color: #374151;
                transition: all 0.2s ease;
            }

            .action-btn:hover {
                background: #e5e7eb;
            }

            .action-btn.view-btn:hover {
                background: #dbeafe;
                color: #2563eb;
            }

            .action-btn.delete-btn:hover {
                background: #fee2e2;
                color: #dc2626;
            }

            .product-wrap {
                max-height: calc(100vh - 500px);
                overflow-y: auto;
            }

            .product-list {
                padding: 1rem;
                border-bottom: 1px solid #e5e7eb;
            }

            .bg-custom-gray {
                background-color: #f9fafb;
            }

            .qty-item {
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }

            .qty-item .form-control {
                width: 60px;
                text-align: center;
                padding: 0.25rem;
            }

            .qty-item i {
                cursor: pointer;
                font-size: 1.25rem;
                color: #6b7280;
            }

            .qty-item i:hover {
                color: #4f46e5;
            }
        </style>
    @endpush
    <div class="main-wrapper">
        <div class="page-wrapper pos-pg-wrapper ms-0">
            <div class="content pos-design p-0">
                <form wire:submit="submit">
                    <div class="row align-items-start pos-wrapper">
                        <div class="col-md-12 col-lg-7">
                            <div class="pos-categories tabs_wrapper">
                                <div class="pos-products">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <div style="width:49% !important" wire:ignore>
                                            <div class="form-group">
                                                <label class="mb-2 d-flex align-items-center">
                                                    <i class="fa fa-user me-2 text-primary"></i>
                                                    <span class="fw-semibold">Customer <i id="viewCustomer" class="fa pointer fa-eye ms-2"></i></span>
                                                </label>
                                                <div class="input-group mb-3">
                                                    <div wire:ignore class="parent-container w-100">
                                                        {{ html()->select('account_id', $accounts)->value($sale_returns['account_id'])->class('select-customer_id form-select')->id('account_id')->placeholder('Select Customer') }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div style="width:49% !important" wire:ignore>
                                            <div class="form-group">
                                                <label class="mb-2 d-flex align-items-center">
                                                    <i class="fa fa-file-text me-2 text-primary"></i>
                                                    <span class="fw-semibold">Invoices</span>
                                                </label>
                                                <div class="input-group mb-3">
                                                    <div wire:ignore class="parent-container w-100">
                                                        {{ html()->select('sale_id', [])->value($sale_id)->class('select-customer_sales-list form-select')->id('sale_id')->placeholder('Select Invoice') }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="search-section mb-1">
                                        <div class="d-flex gap-2">
                                            <div class="search-box flex-grow-1">
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light border-end-0">
                                                        <i class="fa fa-search"></i>
                                                    </span>
                                                    <input type="search" class="form-control border-start-0" wire:model.live="product_key" placeholder="Search Products/Service"
                                                        style="border-radius: 0 4px 4px 0;">
                                                </div>
                                            </div>
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
                                    <div class="cart-summary mb-3">
                                        <div class="d-flex align-items-center justify-content-between bg-white rounded-lg p-3 shadow-sm">
                                            <div class="d-flex align-items-center gap-3">
                                                @php
                                                    $total_quantity = collect($items)->sum('quantity');
                                                @endphp
                                                <span class="cart-badge">{{ $total_quantity }}</span>
                                                <div>
                                                    <h6 class="mb-0 text-dark">Cart Items</h6>
                                                    <small class="text-muted">{{ $total_quantity }} items in cart</small>
                                                </div>
                                            </div>
                                            @if ($total_quantity)
                                                <div class="action-group d-flex gap-2">
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
                                                        <h6>{{ $item['name'] }} <small class="text-muted">({{ $item['unit_name'] ?? '' }})</small></h6>
                                                        <p class="text-success">{{ currency($item['total']) }}</p>
                                                    </div>
                                                </div>
                                                <div class="qty-item text-center">
                                                    <i class="fa fa-minus-circle dec hover-lift" wire:click="modifyQuantity('{{ $item['key'] }}','minus')"></i>

                                                    {{ html()->text('quantity')->value($item['quantity'])->attribute('min', 1)->class('form-control text-center')->attribute('wire:model.live', 'items.' . $item['key'] . '.quantity') }}

                                                    <i class="fa fa-plus-circle inc hover-lift" wire:click="modifyQuantity('{{ $item['key'] }}','plus')"></i>
                                                </div>
                                                <div class="d-flex align-items-center action">
                                                    <i wire:click="editItem('{{ $item['key'] }}')" class="fa fa-edit pointer me-2 text-primary hover-lift"></i>
                                                    <i wire:confirm="Are you sure to delete this?" wire:click="removeItem('{{ $item['key'] }}')"
                                                        class="fa fa-trash pointer text-danger hover-lift"></i>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="block-section">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="fw-bold mb-2">
                                                    <i class="fa fa-file-text-o me-2"></i>Reference No
                                                </label>
                                                {{ html()->text('reference_no')->value('')->class('form-control form-control-sm')->attribute('wire:model', 'sale_returns.reference_no')->placeholder('Enter reference number') }}
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="fw-bold mb-2">
                                                    <i class="fa fa-tag me-2"></i>Discount Amount
                                                </label>
                                                {{ html()->text('other_discount')->value('')->class('form-control form-control-sm number')->attribute('wire:model.lazy', 'sale_returns.other_discount')->placeholder('Enter discount amount') }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="order-total mt-4">
                                        <table class="table table-borderless mb-0">
                                            <tr>
                                                <td class="text-muted">Sub Total</td>
                                                <td class="text-end fw-bold">{{ currency($sale_returns['total']) }}</td>
                                            </tr>
                                            @php
                                                $discount = $sale_returns['total'] ? round(($sale_returns['other_discount'] / $sale_returns['total']) * 100, 2) : 0;
                                            @endphp
                                            <tr>
                                                <td class="text-danger">Discount ({{ $discount }}%)</td>
                                                <td class="text-end text-danger">-{{ currency($sale_returns['other_discount']) }}</td>
                                            </tr>
                                            <tr class="border-top">
                                                <td class="fw-bold">Total</td>
                                                <td class="text-end fw-bold text-success">{{ currency($sale_returns['grand_total']) }}</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>

                                <div class="block-section payment-method">
                                    <h6 class="d-flex justify-content-between align-items-center">
                                        <span>Payment Method</span>
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
                                        <button type="submit" wire:confirm="Are you sure to submit this?" class="btn btn-primary flex-fill hover-lift">
                                            <i class="fa fa-check-circle me-2"></i>
                                            Submit Return
                                        </button>
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
    @push('scripts')
        <script src="{{ https_asset('assets/pos/feather.min.js') }}"></script>
        <script src="{{ https_asset('assets/pos/script.js') }}"></script>
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
