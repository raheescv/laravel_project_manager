<div>
    <div class="row mb-2">
        <div class="col-md-6">
            <div class="d-flex align-items-center position-relative hv-grow-parent hv-outline-parent">
                <div class="flex-shrink-0">
                    <img class="hv-gc hv-oc img-lg rounded-circle" src="{{ asset('assets/img/profile-photos/1.png') }}" alt="Profile Picture" loading="lazy">
                </div>
                <div class="flex-grow-1 ms-3">
                    <a href="{{ route('account::view', $accounts['id'] ?? '') }}" class="d-block stretched-link h5 link-offset-2-hover text-decoration-none link-underline-hover mb-0">
                        {{ $accounts['name'] ?? '' }}
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="d-flex align-items-center position-relative hv-grow-parent hv-outline-parent">
                <div class="flex-grow-1 ms-3">
                    <ul class="list-group list-group-borderless">
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                            <div class="me-5 mb-0 h5">Mobile</div>
                            <span class="ms-auto h5 mb-0">{{ $accounts['mobile'] ?? '' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                            <div class="me-5 mb-0 h5">Email</div>
                            <span class="ms-auto h5 mb-0">{{ $accounts['email'] ?? '' }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="tab-base">
            <ul class="nav nav-underline nav-component border-bottom" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link px-3 active" data-bs-toggle="tab" data-bs-target="#tab-Sales" type="button" role="tab" aria-controls="home" aria-selected="true">Sales</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link px-3" data-bs-toggle="tab" data-bs-target="#tab-sale_items" type="button" role="tab" aria-controls="profile" aria-selected="false"
                        tabindex="-1">Products</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link px-3" data-bs-toggle="tab" data-bs-target="#tab-SaleReturn" type="button" role="tab" aria-controls="home" aria-selected="true">
                        Sales Return
                    </button>
                </li>
                {{-- <li class="nav-item" role="presentation">
                        <button class="nav-link px-3" data-bs-toggle="tab" data-bs-target="#tab-Notes" type="button" role="tab" aria-controls="contact" aria-selected="false" tabindex="-1">
                            Notes
                        </button>
                    </li> --}}
            </ul>
            <div class="tab-content">
                <div id="tab-Sales" class="tab-pane fade active show" role="tabpanel" aria-labelledby="home-tab">
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
                    <p>Last 20 Sales List</p>
                    <table class="table table-striped table-sm table-bordered">
                        <thead>
                            <tr class="bg-primary">
                                <th class="text-white text-end">SL No</th>
                                <th class="text-white">Date</th>
                                <th class="text-white">Invoice No</th>
                                <th class="text-white text-end">Grand Total</th>
                                <th class="text-white text-end">Paid</th>
                                <th class="text-white text-end">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if ($sales)
                                @foreach ($sales as $value)
                                    <tr>
                                        <td>{{ $value->id }}</td>
                                        <td>{{ systemDate($value->date) }}</td>
                                        <td><a href="{{ route('sale::view', $value->id) }}">{{ $value->invoice_no }}</a> </td>
                                        <td class="text-end">{{ currency($value->grand_total) }}</td>
                                        <td class="text-end">{{ currency($value->paid) }}</td>
                                        <td class="text-end">{{ currency($value->balance) }}</td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
                <div id="tab-SaleReturn" class="tab-pane fade" role="tabpanel" aria-labelledby="home-tab">
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
                    <p>Last 20 Sales List</p>
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
                <div id="tab-sale_items" class="tab-pane fade" role="tabpanel" aria-labelledby="profile-tab">
                    <div class="row">
                        <div class="col-md-6">
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
                        <div class="col-md-6">
                            <div class="table-responsive">
                                <p>Last 20 Items</p>
                                <table class="table table-striped table-sm table-bordered">
                                    <thead>
                                        <tr class="bg-primary">
                                            <th class="text-white"> Date</th>
                                            <th class="text-white"> Name</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if ($sale_items)
                                            @foreach ($sale_items as $sale_item)
                                                <tr>
                                                    <td>{{ systemDate($sale_item->sale?->date) }}</td>
                                                    <td>{{ $sale_item->product?->name }}</td>
                                                </tr>
                                            @endforeach
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="tab-Notes" class="tab-pane fade" role="tabpanel" aria-labelledby="contact-tab">
                    <h5>Contact tab</h5>
                    <p class="mb-0">The quick, brown fox jumps over a lazy dog. DJs flock by when MTV ax quiz prog. Junk MTV quiz graced by fox whelps. Bawds jog, flick quartz, vex nymphs.
                        Waltz, bad nymph, for quick jigs vex! Fox nymphs grab quick-jived waltz. Brick quiz whangs jumpy veldt fox.</p>
                </div>
            </div>
        </div>
    </div>
</div>
