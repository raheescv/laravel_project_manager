<div>
    <div class="main-wrapper">
        <div class="page-wrapper pos-pg-wrapper">
            <div class="content pos-design">
                <form wire:submit="submit">
                    <div class="row g-0 g-md-3">
                        <!-- Category  -->
                        <div class="col-12 col-lg-2">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-header bg-primary bg-gradient py-2 px-3">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <h6 class="card-title text-white mb-0">
                                            <i class="fa fa-th-large me-2"></i>
                                            Categories
                                        </h6>
                                        <span class="badge bg-white bg-opacity-25 text-white">{{ $categoryCount }}</span>
                                    </div>
                                </div>
                                <div class="card-body p-0">
                                    <div class="list-group list-group-flush" style="max-height: calc(100vh - 10rem); overflow-y: auto;">
                                        <button type="button"
                                            class="list-group-item list-group-item-action d-flex align-items-center py-2 px-3 border-0 {{ $category_id == 'favorite' ? 'active bg-warning text-white' : '' }}"
                                            wire:click="categorySelect('favorite')">
                                            <i class="fa fa-star me-2 {{ $category_id == 'favorite' ? 'text-white' : 'text-warning' }}"></i>
                                            <span class="flex-grow-1 text-truncate">Favorite</span>
                                        </button>

                                        <button type="button"
                                            class="list-group-item list-group-item-action d-flex align-items-center py-2 px-3 border-0 {{ $category_id == '' ? 'active bg-primary text-white' : '' }}"
                                            wire:click="categorySelect('')">
                                            <i class="fa fa-th-large me-2 {{ $category_id == '' ? 'text-white' : 'text-primary' }}"></i>
                                            <span class="flex-grow-1 text-truncate">All Products</span>
                                        </button>

                                        @foreach ($categories as $item)
                                            <button type="button"
                                                class="list-group-item list-group-item-action d-flex align-items-center py-2 px-3 border-0 {{ $item['id'] == $category_id ? 'active bg-primary text-white' : '' }}"
                                                wire:click="categorySelect({{ $item['id'] }})" wire:key="category-{{ $item['id'] }}">
                                                <i class="fa fa-tag me-2 {{ $item['id'] == $category_id ? 'text-white' : 'text-primary' }}"></i>
                                                <span class="flex-grow-1 text-truncate">{{ $item['name'] }}</span>
                                                <span class="badge {{ $item['id'] == $category_id ? 'bg-white bg-opacity-25 text-white' : 'bg-light text-primary' }} rounded-pill ms-2">
                                                    {{ $item['products_count'] }}
                                                </span>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Product Grid -->
                        <div class="col-12 col-lg-6">
                            <div class="pos-products">
                                <div class="sales-header">
                                    <div class="row g-2">
                                        <div class="col-lg-8">
                                            <div class="form-group" wire:ignore>
                                                <label class="form-label d-flex align-items-center mb-1">
                                                    <i class="fa fa-user me-1 text-primary"></i>
                                                    <span class="fw-semibold small">Employee</span>
                                                </label>
                                                {{ html()->select('employee_id', $employees ?? [])->value($employee_id ?? '')->class('select-employee_id-list')->id('employee_id')->attribute('style', 'width:100%')->placeholder('Select employee...') }}
                                            </div>
                                        </div>
                                        <div class="col-lg-4">
                                            <div class="form-group">
                                                <label class="form-label d-flex align-items-center mb-1">
                                                    <i class="fa fa-tags me-1 text-primary"></i>
                                                    <span class="fw-semibold small">Sale Type</span>
                                                </label>
                                                {{ html()->select('sale_type', priceTypes())->class('form-select')->id('sale_type')->attribute('wire:model.live', 'sales.sale_type')->required(true)->placeholder('Select type...') }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                @include('livewire.sale.search-section')

                                <div class="tabs_container" style="height: calc(100vh - 20rem); overflow: auto;">
                                    <div class="tab_content active" data-tab="all">
                                        <livewire:sale.product-list :sale_type="$sales['sale_type']" :category_id="$category_id" :product_key="$product_key" wire:loading.delay />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Cart Section -->
                        <div class="col-12 col-lg-4">
                            <div class="product-order-list">
                                <div class="cart-summary">
                                    @php
                                        $total_quantity = collect($items)->sum('quantity');
                                    @endphp
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="cart-badge">{{ $total_quantity }}</span>
                                            <div>
                                                <h6 class="mb-0">Cart Items</h6>
                                                <small class="text-muted">{{ $total_quantity }} items in cart</small>
                                            </div>
                                        </div>
                                        @if ($total_quantity)
                                            <div class="action-group d-flex gap-2">
                                                @can('sale.combo offer')
                                                    <div class="d-flex flex-column align-items-center">
                                                        <button type='button' wire:click="manageComboOffer()" class="action-btn package-btn" title="Manage Combo Offer">
                                                            <i class="fa fa-cube"></i>
                                                        </button>
                                                        <small>Combo</small>
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
                                    @forelse ($items as $item)
                                        <div class="product-list" wire:key="cartitem-{{ $item['key'] }}">
                                            <div class="product-info">
                                                <h6>{{ $item['name'] }}</h6>
                                                <p>{{ currency($item['total']) }}</p>
                                            </div>
                                            <div class="qty-item">
                                                <i class="fa fa-minus-circle" wire:click="modifyQuantity('{{ $item['key'] }}','minus')" title="Decrease Quantity"></i>
                                                {{ html()->text('quantity')->value($item['quantity'])->attribute('min', 1)->class('form-control')->attribute('wire:model.live', 'items.' . $item['key'] . '.quantity') }}
                                                <i class="fa fa-plus-circle" wire:click="modifyQuantity('{{ $item['key'] }}','plus')" title="Increase Quantity"></i>
                                            </div>
                                            <div class="action-group">
                                                <button type="button" wire:click="editItem('{{ $item['key'] }}')" class="action-btn" title="Edit Item">
                                                    <i class="fa fa-edit"></i>
                                                </button>
                                                <button type="button" wire:confirm="Are you sure to delete this?" wire:click="removeItem('{{ $item['key'] }}')" class="action-btn delete-btn"
                                                    title="Delete Item">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="text-center py-5">
                                            <i class="fa fa-shopping-cart fa-3x text-muted mb-3"></i>
                                            <p class="text-muted mb-0">Your cart is empty</p>
                                        </div>
                                    @endforelse
                                </div>

                                <div class="customer-info-section">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="form-group" wire:ignore>
                                                <label class="form-label d-flex justify-content-between align-items-center">
                                                    <span class="d-flex align-items-center gap-2">
                                                        <i class="fa fa-user"></i>
                                                        <span>Customer</span>
                                                    </span>
                                                    <i id="viewCustomer" class="fa fa-eye pointer hover-opacity" title="View Customer Details"></i>
                                                </label>
                                                {{ html()->select('account_id', $accounts)->value($sales['account_id'])->class('select-customer_id')->id('account_id')->placeholder('Select Customer') }}
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label">
                                                    <i class="fa fa-phone"></i>
                                                    <span>Customer Mobile</span>
                                                </label>
                                                <div class="input-group">
                                                    {{ html()->text('customer_mobile')->class('form-control')->attribute('wire:model', 'sales.customer_mobile')->id('customer_mobile')->placeholder('Mobile number') }}
                                                    <button type="button" id="addCustomer" class="btn btn-light" title="Add New Customer">
                                                        <i class="fa fa-user-plus"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <div class="form-group mb-0">
                                                <label class="form-label">
                                                    <i class="fa fa-tag"></i>
                                                    <span>Discount Amount</span>
                                                </label>
                                                <div class="input-group">
                                                    <span class="input-group-text">
                                                        <i class="fa fa-calculator"></i>
                                                    </span>
                                                    {{ html()->text('other_discount')->class('form-control number')->attribute('wire:model.lazy', 'sales.other_discount')->placeholder('Enter discount amount') }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="order-total">
                                    <table class="table table-borderless mb-0">
                                        <tr>
                                            <td class="fw-medium">
                                                <i class="fa fa-shopping-cart me-2"></i>
                                                Sub Total
                                            </td>
                                            <td class="text-end fw-medium">{{ currency($sales['total']) }}</td>
                                        </tr>
                                        @php
                                            $discount = $sales['total'] ? round(($sales['other_discount'] / $sales['total']) * 100, 2) : 0;
                                        @endphp
                                        <tr>
                                            <td class="text-danger">
                                                <i class="fa fa-tag me-2"></i>
                                                Discount ({{ $discount }}%)
                                            </td>
                                            <td class="text-end text-danger">{{ currency($sales['other_discount']) }}</td>
                                        </tr>
                                        <tr class="border-top">
                                            <td class="fw-bold">
                                                <i class="fa fa-money me-2"></i>
                                                Total
                                            </td>
                                            <td class="text-end fw-bold">{{ currency($sales['grand_total']) }}</td>
                                        </tr>
                                    </table>
                                </div>

                                <div class="payment-method">
                                    <h6>
                                        <span class="d-flex align-items-center gap-2">
                                            <i class="fa fa-credit-card"></i>
                                            <span>Payment Method</span>
                                        </span>
                                        <div class="form-check">
                                            {{ html()->checkbox('send_to_whatsapp')->value('')->class('form-check-input')->attribute('wire:model.live', 'send_to_whatsapp')->id('send_to_whatsapp') }}
                                            <label for="send_to_whatsapp" class="form-check-label d-flex align-items-center gap-2">
                                                <i class="fa fa-whatsapp"></i>
                                                <span>Send Invoice To Whatsapp</span>
                                            </label>
                                        </div>
                                    </h6>

                                    <div class="row g-2 methods">
                                        <div class="col-md-4">
                                            <div class="default-cover">
                                                <a href="javascript:void(0)" class="@if ($payment_method_name == 'cash') active @endif" wire:click.prevent="selectPaymentMethod('cash')">
                                                    <img src="{{ asset('assets/img/cash-pay.svg') }}" alt="Cash Payment" loading="lazy">
                                                    <span>Cash</span>
                                                </a>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="default-cover">
                                                <a href="javascript:void(0)" class="@if ($payment_method_name == 'card') active @endif" wire:click.prevent="selectPaymentMethod('card')">
                                                    <img src="{{ asset('assets/img/card-pay.svg') }}" alt="Card Payment" loading="lazy">
                                                    <span>Debit Card</span>
                                                </a>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="default-cover">
                                                <a href="javascript:void(0)" class="@if ($payment_method_name == 'custom') active @endif" wire:click.prevent="selectPaymentMethod('custom')">
                                                    <img src="{{ asset('assets/img/custom-pay.svg') }}" alt="Custom Payment" loading="lazy">
                                                    <span>Custom Pay</span>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="action-buttons">
                                    <div class="d-flex gap-3">
                                        @can('sale.feedback')
                                            <button type="button" wire:click="openFeedback" class="btn btn-outline-primary d-flex align-items-center gap-2">
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
                            </div>
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
        <x-sale.show-confirmation />

        <script>
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
