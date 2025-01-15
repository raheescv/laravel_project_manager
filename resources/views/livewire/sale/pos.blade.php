<div>
    <div class="main-wrapper">
        <div class="page-wrapper pos-pg-wrapper ms-0">
            <div class="content pos-design p-0">
                <form wire:submit="submit">
                    <div class="btn-row d-sm-flex align-items-center">
                        <a href="#" class="btn btn-secondary mb-xs-3" data-bs-toggle="modal" data-bs-target="#orders">
                            <span class="me-1 d-flex align-items-center"> <i data-feather="shopping-cart" class="feather-16"></i> </span>
                            View Orders
                        </a>
                        <a href="#" class="btn btn-info">
                            <span class="me-1 d-flex align-items-center"> <i data-feather="rotate-cw" class="feather-16"></i> </span>
                            Reset
                        </a>
                    </div>
                    <div class="row align-items-start pos-wrapper">
                        <div class="col-md-12 col-lg-8">
                            <div class="pos-categories tabs_wrapper">
                                <h5>Categories</h5>
                                <p>Select From Below Categories</p>
                                <ul class="tabs owl-carousel pos-category" wire:ignore>
                                    <li id="all">
                                        <a href="#">
                                            <img src="{{ asset('assets/img/categories/category-06.png') }}" alt="Categories">
                                        </a>
                                        <h6><a href="#">All Categories</a></h6>
                                        <span>{{ $products->count() }} Items</span>
                                    </li>
                                    @foreach ($categories as $category)
                                        <li id="{{ $category->id }}">
                                            <a href="#">
                                                <img src="{{ asset('assets/img/categories/category-06.png') }}" alt="Categories">
                                            </a>
                                            <h6><a href="#">{{ $category->name }}</a></h6>
                                            <span>{{ $category->products->count() }} Items</span>
                                        </li>
                                    @endforeach
                                </ul>
                                <div class="pos-products">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="row">
                                            <h5 class="mb-3">Products</h5> <br>
                                            <input type="text" placeholder="Search product here.." wire:model.live="product_key" class="form-control mb-3" style="width:100%">
                                        </div>
                                    </div>
                                    <div class="tabs_container">
                                        <div class="tab_content active" data-tab="all">
                                            <div class="row">
                                                @foreach ($products as $key => $item)
                                                    <div class="col-sm-2 col-md-6 col-lg-3 col-xl-3 pe-2" wire:click="selectItem('{{ $item->id }}')">
                                                        <div class="product-info default-cover card">
                                                            <a href="#" class="img-bg">
                                                                <img src="{{ asset('assets/img/products/pos-product-01.png') }}" alt="Products">
                                                                <span><i data-feather="check" class="feather-16"></i></span>
                                                            </a>
                                                            <h6 class="cat-name"><a href="#">{{ $item->mainCategory->name }}</a></h6>
                                                            <h6 class="product-name"><a href="#">{{ $item->name }}</a></h6>
                                                            <div class="d-flex align-items-center justify-content-between price">
                                                                <span>47 Pcs</span>
                                                                <p>{{ currency($item->mrp) }}</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    @if (($key + 1) % 4 == 0 && !$loop->last)
                                                        {!! '</div> <div class="row mt-4">' !!}
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                        @foreach ($categories as $category)
                                            <div class="tab_content" data-tab="{{ $category->id }}">
                                                <div class="row">
                                                    @foreach ($category->products as $key => $item)
                                                        <div class="col-sm-2 col-md-6 col-lg-3 col-xl-3 pe-2" wire:click="selectItem('{{ $item->id }}')">
                                                            <div class="product-info default-cover card">
                                                                <a href="#" class="img-bg">
                                                                    <img src="{{ asset('assets/img/products/pos-product-01.png') }}" alt="Products">
                                                                    <span><i data-feather="check" class="feather-16"></i></span>
                                                                </a>
                                                                <h6 class="cat-name"><a href="#">{{ $item->mainCategory->name }}</a></h6>
                                                                <h6 class="product-name"><a href="#">{{ $item->name }}</a></h6>
                                                                <div class="d-flex align-items-center justify-content-between price">
                                                                    <span>47 Pcs</span>
                                                                    <p>{{ currency($item->mrp) }}</p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        @if (($key + 1) % 4 == 0 && !$loop->last)
                                                            {!! '</div> <div class="row mt-4">' !!}
                                                        @endif
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12 col-lg-4 ps-0">
                            <aside class="product-order-list">
                                <div class="head d-flex align-items-center justify-content-between w-100">
                                    <div>
                                        @isset($sales['id'])
                                            <span>Transaction ID : {{ $sales['invoice_no'] }}</span>
                                        @endisset
                                        <h5>Order List</h5>
                                    </div>
                                    <div class>
                                        <a class="confirm-text" href="#"><i data-feather="trash-2" class="feather-16 text-danger"></i></a>
                                        <a href="#" class="text-default"><i data-feather="more-vertical" class="feather-16"></i></a>
                                    </div>
                                </div>
                                <div class="customer-info block-section">
                                    <h6>Customer Information</h6>
                                    <div class="input-block d-flex align-items-center">
                                        <div class="flex-grow-1" wire:ignore>
                                            {{ html()->select('account_id', $accounts)->value($sales['account_id'])->class('select-customer_id')->id('account_id')->placeholder('Select Customer') }}
                                        </div>
                                    </div>
                                </div>
                                <div class="product-added block-section">
                                    <div class="head-text d-flex align-items-center justify-content-between">
                                        <h6 class="d-flex align-items-center mb-0">Product Added<span class="count">{{ count($items) }} </span></h6>
                                        <a href="#" wire:click="deleteAllItems()" class="d-flex align-items-center text-danger">
                                            <span class="me-1"><i data-feather="x" class="feather-16"></i></span>
                                            Clear all
                                        </a>
                                    </div>
                                    <div class="product-wrap">
                                        @foreach ($items as $item)
                                            <div class="product-list border-bottom py-3">
                                                <div class="row">
                                                    <div class="col-12">
                                                        <h6 class="fw-semibold text-dark mb-2 text-truncate" style="max-width: 100%;">
                                                            {{ $item['name'] }}
                                                        </h6>
                                                    </div>
                                                </div>
                                                <!-- Details Row -->
                                                <div class="row align-items-center">
                                                    <!-- Barcode and Price -->
                                                    <div class="col-md-4 col-sm-6">
                                                        <div class="d-flex justify-content-between">
                                                            <span class="text-success fw-bold">{{ currency($item['unit_price']) }}</span>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4 col-sm-6 d-flex justify-content-start align-items-center">
                                                        <input type="number" class="form-control text-center border-1 px-2" value="{{ $item['quantity'] }}" min="1" style="width: 60px;"
                                                            wire:model.live="items.{{ $item['key'] }}.quantity">
                                                    </div>
                                                    <div class="col-md-4 col-sm-12 d-flex justify-content-end align-items-center">
                                                        {{-- <span wire:click="removeItem('{{ $item['key'] }}')" wire:confirm="Are you sure?" title="Remove Product">
                                                        <i class="demo-psi-pencil fs-5 me-2 pointer"></i>
                                                    </span> --}}
                                                        <span wire:click="removeItem('{{ $item['key'] }}')" wire:confirm="Are you sure?" title="Remove Product">
                                                            <i class="demo-pli-recycling fs-5 me-2 pointer"></i>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="block-section">
                                    <div class="selling-info">
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="input-block">
                                                    <div class="row">
                                                        <div class="col-4"> <b>Discount</b> </div>
                                                        <div class="col-8">
                                                            {{ html()->number('other_discount')->value('')->class('form-control number select_on_focus')->attribute('wire:model.live', 'sales.other_discount') }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="order-total mt-4">
                                        <table class="table table-responsive table-borderless">
                                            <tr>
                                                <td>Sub Total</td>
                                                <td class="text-end">{{ currency($sales['total']) }}</td>
                                            </tr>
                                            <tr>
                                                <td class="danger">Discount (10%)</td>
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
                                    <h6>Payment Method</h6>
                                    <div class="row d-flex align-items-center justify-content-center methods">
                                    </div>
                                </div>
                                <div class="d-grid btn-block">
                                    <button class="btn btn-secondary">
                                        Grand Total : {{ currency($sales['grand_total']) }}
                                    </button>
                                </div>
                                <div class="d-grid btn-block">
                                    @if ($this->getErrorBag()->count())
                                        <ol>
                                            <?php foreach ($this->getErrorBag()->toArray() as $key => $value): ?>
                                            <li style="color:red">* {{ $value[0] }}</li>
                                            <?php endforeach; ?>
                                        </ol>
                                    @endif
                                </div>
                                <div class="d-sm-flex align-items-center justify-content-between">
                                    <button type="button" wire:click='save("draft")' style="width:100%" class="btn btn-primary">Draft</button>
                                    &nbsp;
                                    <button type="submit" wire:confirm="Are you sure to submit this?" style="width:100%" class="btn btn-success">Submit & Print</button>
                                </div>
                            </aside>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @push('styles')
        <link rel="stylesheet" href="{{ asset('assets/vendors/pos/style.css') }}">
        <link rel="stylesheet" href="{{ asset('assets/vendors/pos/owl.carousel.min.css') }}">
    @endpush
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                window.addEventListener('show-confirmation', function(event) {
                    const data = event.detail[0];
                    const grand_total = data.grand_total;
                    const paid = data.paid;
                    const balance = data.balance;
                    const message = `
                    <table class="table table-bordered table-striped">
                        <tr>
                            <th class="text-start"><strong>Grand Total</strong></td>
                            <td class="text-end">${grand_total}</td>
                        </tr>
                        <tr>
                            <th class="text-start"><strong>Paid</strong></td>
                            <td class="text-end">${paid}</td>
                        </tr>
                        <tr>
                            <th class="text-start"><strong>Balance</strong></td>
                            <td class="text-end">${balance}</td>
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
        <script src="{{ asset('assets/vendors/pos/owl.carousel.min.js') }}"></script>
        <script src="{{ asset('assets/vendors/pos/script.js') }}"></script>
    @endpush
</div>
