<div>
    <div class="customer-details-hero mb-4">
        <div class="card border-0 shadow-lg overflow-hidden">
            <!-- Decorative Background -->
            <div class="position-absolute top-0 start-0 w-100 h-100 opacity-25">
                <div class="bg-gradient-primary h-100 w-100"></div>
                <div class="position-absolute top-0 end-0 w-50 h-100 bg-gradient-secondary opacity-50"></div>
            </div>

            <div class="card-body position-relative" id="customer-details" style="padding: 2rem;">
                <!-- Edit Button - Premium positioning -->
                <div class="position-absolute top-0 end-0 me-4 mt-3" style="z-index: 10;">
                    <button type="button" id="CustomerEdit" class="btn btn-premium btn-sm d-flex align-items-center gap-2 shadow-lg hover-lift rounded-pill px-4 py-2" data-bs-toggle="tooltip"
                        data-bs-placement="left" title="Edit Customer Details">
                        <i class="fa fa-edit"></i>
                        <span class="d-none d-lg-inline fw-semibold">Edit Customer</span>
                    </button>
                </div>

                <div class="row align-items-center g-4">
                    <!-- Customer Profile Section -->
                    <div class="col-lg-6">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 me-4">
                                <div class="position-relative customer-avatar">
                                    <div class="avatar-ring"></div>
                                    <img class="img-fluid rounded-circle shadow-lg" src="{{ asset('assets/img/profile-photos/1.png') }}" alt="Profile Picture"
                                        style="width: 80px; height: 80px; object-fit: cover; border: 4px solid rgba(255,255,255,0.9);">

                                    <!-- Status Indicator -->
                                    <div class="position-absolute bottom-0 end-0 bg-success rounded-circle border-3 border-white shadow-sm pulse-animation" style="width: 22px; height: 22px;"
                                        data-bs-toggle="tooltip" title="Active Customer">
                                        <i class="fa fa-check text-white" style="font-size: 8px; line-height: 22px;"></i>
                                    </div>
                                </div>
                            </div>

                            <div class="flex-grow-1">
                                <div class="customer-info">
                                    <h2 class="customer-name mb-2 text-dark fw-bold display-6">
                                        {{ $accounts['name'] ?? 'Customer Name' }}
                                    </h2>
                                    <div class="customer-meta d-flex flex-wrap gap-3 align-items-center">
                                        <span class="badge bg-light text-dark px-3 py-2 rounded-pill shadow-sm">
                                            <i class="fa fa-id-card me-2 text-primary"></i>
                                            ID: #{{ $accounts['id'] ?? '000' }}
                                        </span>
                                        @php
                                            $customer_type = $accounts['customer_type']['name'] ?? '';
                                        @endphp
                                        @if ($customer_type)
                                            <span class="badge bg-success bg-gradient px-3 py-2 rounded-pill shadow-sm">
                                                <i class="fa fa-star me-2"></i>
                                                {{ $customer_type }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Information Cards -->
                    <div class="col-lg-6">
                        <div class="contact-cards">
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <div class="contact-card text-center p-4 rounded-4 shadow-sm h-100 hover-card">
                                        <div class="contact-icon mb-3">
                                            <div class="icon-circle bg-primary bg-gradient d-inline-flex align-items-center justify-content-center rounded-circle shadow">
                                                <i class="fa fa-mobile text-white fs-5"></i>
                                            </div>
                                        </div>
                                        <div class="contact-info">
                                            <h6 class="text-muted mb-2 fw-semibold">Mobile Number</h6>
                                            <div class="fw-bold text-dark fs-6">{{ $accounts['mobile'] ?: 'Not provided' }}</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-sm-6">
                                    <div class="contact-card text-center p-4 rounded-4 shadow-sm h-100 hover-card">
                                        <div class="contact-icon mb-3">
                                            <div class="icon-circle bg-success bg-gradient d-inline-flex align-items-center justify-content-center rounded-circle shadow">
                                                <i class="fa fa-envelope text-white fs-5"></i>
                                            </div>
                                        </div>
                                        <div class="contact-info">
                                            <h6 class="text-muted mb-2 fw-semibold">Email Address</h6>
                                            <div class="fw-bold text-dark fs-6 text-truncate">{{ $accounts['email'] ?: 'Not provided' }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="tab-base">
            <ul class="nav nav-underline nav-component border-bottom" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link px-3 @if ($selected_tab === 'Sales') active @endif" data-bs-toggle="tab" data-bs-target="#tab-Sales" type="button" role="tab" aria-controls="home"
                        aria-selected="true" wire:click="$set('selected_tab', 'Sales')">Sales</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link px-3 @if ($selected_tab === 'SaleReturn') active @endif" data-bs-toggle="tab" data-bs-target="#tab-SaleReturn" type="button" role="tab"
                        aria-controls="home" aria-selected="true" wire:click="$set('selected_tab', 'SaleReturn')">
                        Sales Return
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link px-3 @if ($selected_tab === 'SaleItems') active @endif" data-bs-toggle="tab" data-bs-target="#tab-SaleItems" type="button" role="tab"
                        aria-controls="home" aria-selected="true" wire:click="$set('selected_tab', 'SaleItems')">
                        Sale Items
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link px-3 @if ($selected_tab === 'SaleProductSummary') active @endif" data-bs-toggle="tab" data-bs-target="#tab-SaleProductSummary" type="button" role="tab"
                        aria-controls="profile" aria-selected="false" tabindex="-1" wire:click="$set('selected_tab', 'SaleProductSummary')">Sale Item Summary</button>
                </li>
                @can('account note.view')
                    <li class="nav-item" role="presentation">
                        <button class="nav-link px-3" data-bs-toggle="tab" data-bs-target="#tab-Notes" type="button" role="tab" aria-controls="contact" aria-selected="false" tabindex="-1">
                            Notes
                        </button>
                    </li>
                @endcan
            </ul>
            <div class="tab-content">
                <div id="tab-Sales" class="tab-pane fade @if ($selected_tab === 'Sales') active show @endif" role="tabpanel" aria-labelledby="home-tab">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card bg-info text-white mb-3 mb-xl-3">
                                <div class="card-body py-3">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="flex-shrink-0">
                                            <i class="d-flex align-items-center justify-content-center demo-pli-add-cart display-5"></i>
                                        </div>
                                        <div class="flex-grow-1 ms-4">
                                            <h5 class="h2 mb-0">{{ currency($total_sales?->grand_total) }}</h5>
                                            <p class="text-white text-opacity-75 mb-0">Total Sale</p>
                                        </div>
                                    </div>
                                    @php
                                        $percentage = $total_sales?->grand_total ? round(($total_sales?->paid / $total_sales?->grand_total) * 100) : 0;
                                    @endphp
                                    <div class="progress progress-md mb-2">
                                        <div class="progress-bar bg-white" role="progressbar" style="width: {{ $percentage }}%;" aria-valuenow="{{ $percentage }}" aria-valuemin="0"
                                            aria-valuemax="100">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-success text-white mb-3 mb-xl-3">
                                <div class="card-body py-3">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="flex-shrink-0">
                                            <i class="d-flex align-items-center justify-content-center pli-money-2 display-5"></i>
                                        </div>
                                        <div class="flex-grow-1 ms-4">
                                            <h5 class="h2 mb-0">{{ currency($total_sales?->paid) }}</h5>
                                            <p class="text-white text-opacity-75 mb-0">Paid</p>
                                        </div>
                                    </div>
                                    <div class="progress progress-md mb-2">
                                        <div class="progress-bar bg-white" role="progressbar" style="width: {{ $percentage }}%;" aria-valuenow="{{ $percentage }}" aria-valuemin="0"
                                            aria-valuemax="100">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-warning text-white mb-3 mb-xl-3">
                                <div class="card-body py-3">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="flex-shrink-0">
                                            <i class="d-flex align-items-center justify-content-center pli-money display-5"></i>
                                        </div>
                                        <div class="flex-grow-1 ms-4">
                                            <h5 class="h2 mb-0">{{ currency($total_sales?->balance) }}</h5>
                                            <p class="text-white text-opacity-75 mb-0">Outstanding</p>
                                        </div>
                                    </div>
                                    <div class="progress progress-md mb-2">
                                        @php
                                            $percentage = $total_sales?->grand_total ? round(($total_sales?->balance / $total_sales?->grand_total) * 100) : 0;
                                        @endphp
                                        <div class="progress-bar bg-white" role="progressbar" style="width: {{ $percentage }}%;" aria-valuenow="{{ $percentage }}" aria-valuemin="0"
                                            aria-valuemax="100">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <b><label for="from_date">From Date</label></b>
                            {{ html()->date('from_date')->value('')->class('form-control')->attribute('wire:model.live', 'sale_from_date') }}
                        </div>
                        <div class="col-md-3">
                            <b><label for="to_date">To Date</label></b>
                            {{ html()->date('to_date')->value('')->class('form-control')->attribute('wire:model.live', 'sale_to_date') }}
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <b><label for="Limit">Limit</label></b>
                                <select wire:model.live="sale_limit" class="form-control">
                                    <option value="10">10</option>
                                    <option value="100">100</option>
                                    <option value="500">500</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <table class="table table-striped table-sm table-bordered">
                        <thead>
                            <tr class="bg-primary">
                                <th class="text-white text-end">SL No</th>
                                <th class="text-white">Date</th>
                                <th class="text-white">Invoice No</th>
                                <th class="text-white text-end">Grand Total</th>
                                <th class="text-white text-end">Paid</th>
                                <th class="text-white text-end">Balance</th>
                                <th class="text-white">Rating</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if ($sales)
                                @foreach ($sales as $value)
                                    <tr>
                                        <td class="text-end">{{ $value->id }}</td>
                                        <td>{{ systemDate($value->date) }}</td>
                                        <td><a href="{{ route('sale::view', $value->id) }}">{{ $value->invoice_no }}</a> </td>
                                        <td class="text-end">{{ currency($value->grand_total) }}</td>
                                        <td class="text-end">{{ currency($value->paid) }}</td>
                                        <td class="text-end">{{ currency($value->balance) }}</td>
                                        <td>
                                            <div class="d-flex align-items-center mb-2">
                                                <div class="stars">
                                                    @for ($i = 1; $i <= 5; $i++)
                                                        <i class="fa fa-star fs-5 {{ $value->rating >= $i ? 'text-warning' : 'text-muted' }}"></i>
                                                    @endfor
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    @if ($value->feedback)
                                        <tr>
                                            <td></td>
                                            <td colspan="6">{{ $value->feedback }}</td>
                                        </tr>
                                    @endif
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
                <div id="tab-SaleReturn" class="tab-pane fade @if ($selected_tab === 'SaleReturn') active show @endif" role="tabpanel" aria-labelledby="home-tab">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card bg-info text-white mb-3 mb-xl-3">
                                <div class="card-body py-3">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="flex-shrink-0">
                                            <i class="d-flex align-items-center justify-content-center demo-pli-add-cart display-5"></i>
                                        </div>
                                        <div class="flex-grow-1 ms-4">
                                            <h5 class="h2 mb-0">{{ currency($total_sale_returns?->grand_total) }}</h5>
                                            <p class="text-white text-opacity-75 mb-0">Total Sales Return</p>
                                        </div>
                                    </div>
                                    @php
                                        $percentage = $total_sale_returns?->grand_total ? round(($total_sale_returns?->paid / $total_sale_returns?->grand_total) * 100) : 0;
                                    @endphp
                                    <div class="progress progress-md mb-2">
                                        <div class="progress-bar bg-white" role="progressbar" style="width: {{ $percentage }}%;" aria-valuenow="{{ $percentage }}" aria-valuemin="0"
                                            aria-valuemax="100">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-success text-white mb-3 mb-xl-3">
                                <div class="card-body py-3">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="flex-shrink-0">
                                            <i class="d-flex align-items-center justify-content-center pli-money-2 display-5"></i>
                                        </div>
                                        <div class="flex-grow-1 ms-4">
                                            <h5 class="h2 mb-0">{{ currency($total_sale_returns?->paid) }}</h5>
                                            <p class="text-white text-opacity-75 mb-0">Paid</p>
                                        </div>
                                    </div>
                                    <div class="progress progress-md mb-2">
                                        <div class="progress-bar bg-white" role="progressbar" style="width: {{ $percentage }}%;" aria-valuenow="{{ $percentage }}" aria-valuemin="0"
                                            aria-valuemax="100">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-warning text-white mb-3 mb-xl-3">
                                <div class="card-body py-3">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="flex-shrink-0">
                                            <i class="d-flex align-items-center justify-content-center pli-money display-5"></i>
                                        </div>
                                        <div class="flex-grow-1 ms-4">
                                            <h5 class="h2 mb-0">{{ currency($total_sale_returns?->balance) }}</h5>
                                            <p class="text-white text-opacity-75 mb-0">Pending</p>
                                        </div>
                                    </div>
                                    <div class="progress progress-md mb-2">
                                        @php
                                            $percentage = $total_sale_returns?->grand_total ? round(($total_sale_returns?->balance / $total_sale_returns?->grand_total) * 100) : 0;
                                        @endphp
                                        <div class="progress-bar bg-white" role="progressbar" style="width: {{ $percentage }}%;" aria-valuenow="{{ $percentage }}" aria-valuemin="0"
                                            aria-valuemax="100">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <b><label for="from_date">From Date</label></b>
                            {{ html()->date('from_date')->value('')->class('form-control')->attribute('wire:model.live', 'sale_return_from_date') }}
                        </div>
                        <div class="col-md-3">
                            <b><label for="to_date">To Date</label></b>
                            {{ html()->date('to_date')->value('')->class('form-control')->attribute('wire:model.live', 'sale_return_to_date') }}
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <b><label for="Limit">Limit</label></b>
                                <select wire:model.live="sale_return_limit" class="form-control">
                                    <option value="10">10</option>
                                    <option value="100">100</option>
                                    <option value="500">500</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <table class="table table-striped table-sm table-bordered">
                        <thead>
                            <tr class="bg-primary">
                                <th class="text-white text-end">SL No</th>
                                <th class="text-white">Date</th>
                                <th class="text-white">Reference No</th>
                                <th class="text-white text-end">Grand Total</th>
                                <th class="text-white text-end">Paid</th>
                                <th class="text-white text-end">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if ($sale_returns)
                                @foreach ($sale_returns as $value)
                                    <tr>
                                        <td>{{ $value->id }}</td>
                                        <td>{{ systemDate($value->date) }}</td>
                                        <td><a href="{{ route('sale_return::view', $value->id) }}">{{ $value->reference_no ? $value->reference_no : $value->id }}</a> </td>
                                        <td class="text-end">{{ currency($value->grand_total) }}</td>
                                        <td class="text-end">{{ currency($value->paid) }}</td>
                                        <td class="text-end">{{ currency($value->balance) }}</td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
                <div id="tab-SaleProductSummary" class="tab-pane fade @if ($selected_tab === 'SaleProductSummary') active show @endif" role="tabpanel" aria-labelledby="profile-tab">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="table-responsive">
                                <p>Grouped Item Summary</p>
                                <table class="table table-striped table-sm table-bordered">
                                    <thead>
                                        <tr class="bg-primary">
                                            <th class="text-white"> Name</th>
                                            <th class="text-white text-end">Count</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if ($item_count)
                                            @foreach ($item_count as $sale_item)
                                                <tr>
                                                    <td> <a href="{{ route('inventory::product::view', $sale_item->product_id) }}">{{ $sale_item->product?->name }}</a> </td>
                                                    <td class="text-end">{{ $sale_item->count }}</td>
                                                </tr>
                                            @endforeach
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="tab-SaleItems" class="tab-pane fade @if ($selected_tab === 'SaleItems') active show @endif" role="tabpanel" aria-labelledby="profile-tab">
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <b><label for="from_date">From Date</label></b>
                            {{ html()->date('from_date')->value('')->class('form-control')->attribute('wire:model.live', 'sale_item_from_date') }}
                        </div>
                        <div class="col-md-3">
                            <b><label for="to_date">To Date</label></b>
                            {{ html()->date('to_date')->value('')->class('form-control')->attribute('wire:model.live', 'sale_item_to_date') }}
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <b><label for="Limit">Limit</label></b>
                                <select wire:model.live="sale_item_limit" class="form-control">
                                    <option value="10">10</option>
                                    <option value="100">100</option>
                                    <option value="500">500</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="table-responsive">
                                <p>Sale Item Details</p>
                                <table class="table table-striped table-sm table-bordered">
                                    <thead>
                                        <tr class="text-capitalize bg-primary">
                                            <th class="text-white">id</th>
                                            <th class="text-white">date</th>
                                            <th class="text-white">invoice</th>
                                            <th class="text-white">employee</th>
                                            <th class="text-white">product</th>
                                            <th class="text-white text-end">quantity</th>
                                            <th class="text-white text-end">total</th>
                                            <th class="text-white text-end">effective</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($sale_items as $item)
                                            <tr>
                                                <td>{{ $item->id }}</td>
                                                <td>{{ systemDate($item->sale?->date) }}</td>
                                                <td> <a href="{{ route('sale::view', $item->sale_id) }}">{{ $item->sale?->invoice_no }}</a> </td>
                                                <td>{{ $item->employee?->name }}</td>
                                                <td>{{ $item->product?->name }}</td>
                                                <td class="text-end">{{ currency($item->quantity) }}</td>
                                                <td class="text-end">{{ currency($item->total) }}</td>
                                                <td class="text-end">{{ currency($item->effective_total) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="tab-Notes" class="tab-pane fade @if ($selected_tab === 'Notes') active show @endif" role="tabpanel" aria-labelledby="contact-tab">
                    @livewire('account.note.table', ['account_id' => $account_id])
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            $(document).ready(function() {
                $('#CustomerEdit').click(function() {
                    Livewire.dispatch("Customer-Page-Update-Component", {
                        id: "{{ $accounts['id'] }}"
                    });
                });
            });
        </script>
    @endpush
    @push('styles')
        <style>
            .hover-lift {
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                position: relative;
                z-index: 1;
            }

            .hover-lift:hover {
                transform: translateY(-3px) scale(1.02);
                box-shadow: 0 8px 25px rgba(0, 123, 255, 0.4) !important;
            }

            .hover-lift:active {
                transform: translateY(-1px) scale(0.98);
            }

            #CustomerEdit {
                border: none;
                background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
                border-radius: 25px;
                font-weight: 600;
                letter-spacing: 0.5px;
                padding: 8px 16px;
                box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
                position: relative;
                overflow: hidden;
            }

            #CustomerEdit::before {
                content: '';
                position: absolute;
                top: 0;
                left: -100%;
                width: 100%;
                height: 100%;
                background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
                transition: left 0.5s;
            }

            #CustomerEdit:hover::before {
                left: 100%;
            }

            #CustomerEdit:hover {
                background: linear-gradient(135deg, #0056b3 0%, #004085 100%);
                transform: translateY(-3px);
                box-shadow: 0 8px 25px rgba(0, 123, 255, 0.4);
            }

            #CustomerEdit i {
                font-size: 0.9rem;
                transition: transform 0.3s ease;
            }

            #CustomerEdit:hover i {
                transform: rotate(15deg);
            }

            .card {
                transition: all 0.3s ease;
                border-radius: 15px;
            }

            .card:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1) !important;
            }

            .bg-light {
                background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%) !important;
                transition: all 0.3s ease;
            }

            .bg-light:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            }

            /* Customer Details Hero Section */
            .customer-details-hero .card {
                border-radius: 20px;
                overflow: hidden;
                background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            }

            .bg-gradient-primary {
                background: linear-gradient(45deg, rgba(0, 123, 255, 0.1), rgba(0, 86, 179, 0.05));
            }

            .bg-gradient-secondary {
                background: linear-gradient(-45deg, rgba(40, 167, 69, 0.08), rgba(25, 135, 84, 0.05));
            }

            /* Premium Button Styles */
            .btn-premium {
                background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%);
                border: none;
                color: white;
                font-weight: 600;
                letter-spacing: 0.5px;
                box-shadow: 0 6px 20px rgba(111, 66, 193, 0.4);
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }

            .btn-premium:hover {
                background: linear-gradient(135deg, #5a2d91 0%, #d63384 100%);
                transform: translateY(-3px);
                box-shadow: 0 10px 30px rgba(111, 66, 193, 0.5);
                color: white;
            }

            /* Customer Avatar */
            .customer-avatar {
                position: relative;
            }

            .avatar-ring {
                position: absolute;
                top: -8px;
                left: -8px;
                right: -8px;
                bottom: -8px;
                border: 2px solid transparent;
                border-radius: 50%;
                background: linear-gradient(45deg, #007bff, #28a745, #ffc107, #dc3545);
                background-size: 400% 400%;
                animation: gradient-rotate 3s ease infinite;
                z-index: -1;
            }

            @keyframes gradient-rotate {

                0%,
                100% {
                    background-position: 0% 50%;
                }

                50% {
                    background-position: 100% 50%;
                }
            }

            /* Pulse Animation for Status */
            .pulse-animation {
                animation: pulse 2s infinite;
            }

            @keyframes pulse {
                0% {
                    box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7);
                }

                70% {
                    box-shadow: 0 0 0 10px rgba(40, 167, 69, 0);
                }

                100% {
                    box-shadow: 0 0 0 0 rgba(40, 167, 69, 0);
                }
            }

            /* Customer Name Typography */
            .customer-name {
                background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
                text-shadow: none;
            }

            /* Contact Cards */
            .contact-card {
                background: rgba(255, 255, 255, 0.9);
                border: 1px solid rgba(255, 255, 255, 0.2);
                backdrop-filter: blur(10px);
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }

            .hover-card:hover {
                transform: translateY(-8px) scale(1.02);
                box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
                background: rgba(255, 255, 255, 0.95);
            }

            /* Icon Circles */
            .icon-circle {
                width: 50px;
                height: 50px;
                transition: all 0.3s ease;
            }

            .contact-card:hover .icon-circle {
                transform: scale(1.1) rotate(5deg);
                box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            }

            /* Badge Enhancements */
            .badge {
                font-size: 0.75rem;
                font-weight: 600;
                transition: all 0.3s ease;
            }

            .badge:hover {
                transform: scale(1.05);
            }

            /* Responsive Enhancements */
            @media (max-width: 768px) {
                .customer-details-hero .card-body {
                    padding: 1.5rem !important;
                }

                .customer-name {
                    font-size: 1.75rem !important;
                }

                .contact-cards .col-sm-6 {
                    margin-bottom: 1rem;
                }
            }

            /* Verification Status */
            .text-success {
                animation: fadeInUp 0.5s ease-out;
            }

            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(10px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
        </style>
    @endpush
</div>
