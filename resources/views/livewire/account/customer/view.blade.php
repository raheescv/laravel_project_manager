<div>
    <div class="modal-header">
        <h1 class="modal-title fs-5">Customer Details</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <div class="modal-body">
        <div class="row mb-2">
            <div class="table-responsive">
                <table class="table table-striped align-middle table-sm table-bordered">
                    <tr>
                        <th>Name</th>
                        <td>{{ $accounts['name'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <th>Mobile</th>
                        <td>{{ $accounts['mobile'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <th>Email</th>
                        <td>{{ $accounts['email'] ?? '' }}</td>
                    </tr>
                </table>
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
                    {{-- <li class="nav-item" role="presentation">
                        <button class="nav-link px-3" data-bs-toggle="tab" data-bs-target="#tab-Notes" type="button" role="tab" aria-controls="contact" aria-selected="false" tabindex="-1">
                            Notes
                        </button>
                    </li> --}}
                </ul>
                <div class="tab-content">
                    <div id="tab-Sales" class="tab-pane fade active show" role="tabpanel" aria-labelledby="home-tab">
                        @if ($total_sales)
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="card text-bg-info mb-3 mb-xl-3">
                                        <div class="card-body py-3 d-flex align-items-stretch">
                                            <div class="flex-grow-1 ms-3">
                                                <h5 class="h2 mb-0">{{ currency($total_sales['grand_total']) }}</h5>
                                                <p class="mb-0">Total Sale</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card text-bg-success mb-3 mb-xl-3">
                                        <div class="card-body py-3 d-flex align-items-stretch">
                                            <div class="flex-grow-1 ms-3">
                                                <h5 class="h2 mb-0">{{ currency($total_sales['paid']) }}</h5>
                                                <p class="mb-0">Paid</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card text-bg-warning mb-3 mb-xl-3">
                                        <div class="card-body py-3 d-flex align-items-stretch">
                                            <div class="flex-grow-1 ms-3">
                                                <h5 class="h2 mb-0">{{ currency($total_sales['balance']) }}</h5>
                                                <p class="mb-0">Balance</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
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
                                    @foreach ($sales as $sale)
                                        <tr>
                                            <td>{{ $sale->id }}</td>
                                            <td>{{ systemDate($sale->date) }}</td>
                                            <td>{{ $sale->invoice_no }}</td>
                                            <td class="text-end">{{ currency($sale->grand_total) }}</td>
                                            <td class="text-end">{{ currency($sale->paid) }}</td>
                                            <td class="text-end">{{ currency($sale->balance) }}</td>
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
                                                        <td>{{ $sale_item->product?->name }}</td>
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
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
    </div>
</div>
