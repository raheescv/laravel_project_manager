<div>
    <div class="col-md-12 mb-3">
        <div class="card">
            <div class="card-body">
                <!-- Invoice info -->
                <div class="d-md-flex">
                    <address class="mb-4 mb-md-0">
                        <h5 class="mb-2"> <a href="{{ route('account::customer::view', $sale->account_id) }}">{{ $sale->account?->name }}</a> </h5>
                        @if ($sale->customer_name)
                            <b>Customer Name :</b> {{ $sale->customer_name }} <br>
                        @endif
                        @if ($sale->customer_mobile)
                            <b>Mobile :</b> {{ $sale->customer_mobile }} <br>
                        @endif
                    </address>
                    <ul class="list-group list-group-borderless ms-auto">
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 pt-0 pb-1">
                            <div class="me-5 fw-semibold text-body-emphasis">Invoice #</div>
                            <span class="ms-auto text-info fw-bold">{{ $sale->invoice_no }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 pt-0 pb-1">
                            <div class="me-5 fw-semibold text-body-emphasis">Reference No</div>
                            <span class="ms-auto">{{ systemDate($sale->reference_no) }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 pt-0 pb-1">
                            <div class="me-5 fw-semibold text-body-emphasis">Order Status</div>
                            @php
                                $statusClasses = [
                                    'completed' => 'bg-success',
                                    'draft' => 'bg-info',
                                    'cancelled' => 'bg-warning',
                                ];
                            @endphp
                            @if (isset($statusClasses[$sale->status]))
                                <span class="badge {{ $statusClasses[$sale->status] }} rounded-pill">
                                    {{ ucFirst($sale->status) }}
                                </span>
                            @endif
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 pt-0 pb-1">
                            <div class="me-5 fw-semibold text-body-emphasis">Date</div>
                            <span class="ms-auto">{{ systemDate($sale->date) }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 pt-0 pb-1">
                            <div class="me-5 fw-semibold text-body-emphasis">Due Date</div>
                            <span class="ms-auto">{{ systemDate($sale->due_date) }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 pt-0 pb-1">
                            <div class="me-5 fw-semibold text-body-emphasis">Sale Type</div>
                            <span class="ms-auto">{{ ucFirst($sale->sale_type) }}</span>
                        </li>
                    </ul>
                </div>
                <!-- END : Invoice info -->

                <!-- Invoice table -->
                <h5 class="card-title">Items</h5>
                <div class="table-responsive">
                    <table class="table table-striped table-sm align-middle">
                        <thead>
                            <tr class="bg-primary">
                                <th class="text-white">SL No</th>
                                <th class="text-white" width="20%">Product/Service</th>
                                <th class="text-white text-end">Unit Price</th>
                                <th class="text-white text-end">Quantity</th>
                                <th class="text-white text-end">Discount</th>
                                <th class="text-white text-end">Tax %</th>
                                <th class="text-white text-end">Total</th>
                                @if ($sales['other_discount'] > 0)
                                    <th class="text-white text-end">Effective Total</th>
                                @endif
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
                                    <th colspan="8">{{ $first['employee_name'] }}</th>
                                </tr>
                                @foreach ($groupedItems as $item)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td> <a href="{{ route('inventory::product::view', $item['product_id']) }}">{{ $item['name'] }}</a> </td>
                                        <td class="text-end">{{ currency($item['unit_price']) }}</td>
                                        <td class="text-end">{{ currency($item['quantity']) }}</td>
                                        <td class="text-end">{{ currency($item['discount']) }}</td>
                                        <td class="text-end">{{ currency($item['tax_amount']) }} ({{ round($item['tax'], 2) }}%)</td>
                                        <td class="text-end"> {{ currency($item['total']) }} </td>
                                        @if ($sales['other_discount'] > 0)
                                            <td class="text-end"> {{ currency($item['effective_total']) }} </td>
                                        @endif
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                        <tfoot>
                            @php
                                $items = collect($items);
                            @endphp
                            <tr>
                                <th colspan="3" class="text-end">Total</th>
                                <th class="text-end"><b>{{ currency($items->sum('quantity')) }}</b></th>
                                <th class="text-end"><b>{{ currency($items->sum('discount')) }}</b></th>
                                <th class="text-end"><b>{{ currency($items->sum('tax_amount')) }}</b></th>
                                <th class="text-end"><b>{{ currency($items->sum('total')) }}</b></th>
                                @if ($sales['other_discount'] > 0)
                                    <th class="text-end"><b>{{ currency($items->sum('effective_total')) }}</b></th>
                                @endif
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <!-- END : Invoice table -->
                <div class="row">
                    <div class="col-12 col-md-5 mb-3 mb-md-0">
                        <h5 class="card-title">Payments</h5>
                        <div class="table-responsive">
                            <table class="table table-striped align-left table-sm w-100">
                                <thead>
                                    <tr class="bg-primary">
                                        <th class="text-white">Payment Method</th>
                                        <th class="text-white text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($payments as $key => $item)
                                        <tr>
                                            <td>{{ $item['name'] }}</td>
                                            <td class="text-end">{{ currency($item['amount']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-12 offset-md-3 col-md-4">
                        <ul class="list-group list-group-borderless">
                            <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                                <div class="me-5 mb-0 h5">Gross Total</div>
                                <span class="ms-auto h5 mb-0">{{ currency($sale->gross_amount) }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                                <div class="me-5 mb-0 h5">Sale Total</div>
                                <span class="ms-auto h5 mb-0">{{ currency($sale->total) }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                                <div class="me-5 mb-0 h5">Other Discount</div>
                                <span class="ms-auto h5 mb-0">{{ currency($sale->other_discount) }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                                <div class="me-5 mb-0 h5">Freight</div>
                                <span class="ms-auto h5 mb-0">{{ currency($sale->freight) }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                                <div class="me-5 mb-0 h5">Total Payable Amount</div>
                                <span class="ms-auto h5 mb-0">{{ currency($sale->grand_total) }}</span>
                            </li>
                            <br>
                            @if ($sale->balance != 0)
                                <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                                    <div class="me-5 mb-0 h5">Paid</div>
                                    <span class="ms-auto h5 mb-0">{{ currency($sale->paid) }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                                    <div class="me-5 mb-0 h5">Balance</div>
                                    <span class="ms-auto h5 mb-0">{{ currency($sale->balance) }}</span>
                                </li>
                            @endif
                        </ul>
                    </div>
                </div>


                <div class="d-flex justify-content-end gap-2 my-4 d-print-none">
                    @if ($sales['status'] != 'cancelled')
                        <a target="_blank" href="{{ route('print::sale::invoice', $sales['id']) }}" class="btn btn-outline-light btn-icon">
                            <i class="demo-pli-printer fs-4"></i>
                        </a>
                        @can('sale.cancel')
                            <button type="button" wire:click='save("cancelled")' wire:confirm="Are you sure to cancel this?" class="btn btn-danger btn-sm">
                                Cancel
                            </button>
                        @endcan
                        @can('sale.edit completed')
                            <a href="{{ route('sale::edit', $sales['id']) }}" type="button" class="btn btn-primary">Edit</a>
                        @endcan
                        @can('sale.cancel')
                            <button type="button" wire:click='sendToWhatsapp' class="btn btn-info btn-sm">
                                Whatsapp
                            </button>
                        @endcan
                    @endif
                </div>
                <!-- END : Print button and confirm payment -->
                @if ($sale['address'])
                    <!-- Footer information -->
                    <div class="bg-body-tertiary p-3 rounded bg-opacity-40 mt-5">
                        <p class="h5">Notes &amp; Information</p>
                        <p>{{ $sale['address'] }}</p>
                    </div>
                    <!-- END : Footer information -->
                @endif
            </div>
        </div>
    </div>
    <div class="tab-base">
        <ul class="nav nav-underline nav-component border-bottom" role="tablist">
            @can('sale.view journal entries')
                @if (count($sale->journals))
                    <li class="nav-item" role="presentation">
                        <button class="nav-link px-3 active" data-bs-toggle="tab" data-bs-target="#tab-journal-entries" type="button" role="tab" aria-controls="contact" aria-selected="false"
                            tabindex="-1">
                            Journal Entries
                        </button>
                    </li>
                @endif
            @endcan
            @if (count($sale_return_items))
                <li class="nav-item" role="presentation">
                    <button class="nav-link px-3" data-bs-toggle="tab" data-bs-target="#tab-sale-return-items" type="button" role="tab" aria-controls="contact" aria-selected="false"
                        tabindex="-1">
                        Sale Return Items
                    </button>
                </li>
            @endif
        </ul>
        <div class="tab-content">
            @can('sale.view journal entries')
                @if (count($sale->journals))
                    <div id="tab-journal-entries" class="tab-pane fade active show" role="tabpanel" aria-labelledby="contact-tab">
                        <div class="table-responsive">
                            <table class="table table-striped align-middle table-sm table-bordered">
                                <thead>
                                    <tr class="bg-primary">
                                        <th class="text-white text-end">SL No</th>
                                        <th class="text-white">Date</th>
                                        <th class="text-white">Account Name</th>
                                        <th class="text-white">Description</th>
                                        <th class="text-white text-end">Debit</th>
                                        <th class="text-white text-end">Credit</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($sale->journals as $journal)
                                        @foreach ($journal->entries as $entry)
                                            <tr>
                                                <td class="text-end">{{ $entry->id }}</td>
                                                <td>{{ systemDate($journal->date) }}</td>
                                                <td>{{ $entry->account?->name }}</td>
                                                <td>{{ $entry->remarks }}</td>
                                                <td class="text-end">{{ currency($entry->credit) }}</td>
                                                <td class="text-end">{{ currency($entry->debit) }}</td>
                                            </tr>
                                        @endforeach
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            @endcan
            <div id="tab-sale-return-items" class="tab-pane fade" role="tabpanel" aria-labelledby="contact-tab">
                <div class="table-responsive">
                    <table class="table table-striped align-middle table-sm table-bordered">
                        <thead>
                            <tr class="bg-primary">
                                <th class="text-white text-end">SL No</th>
                                <th class="text-white" width="20%">Product/Service</th>
                                <th class="text-white text-end">Unit Price</th>
                                <th class="text-white text-end">Quantity</th>
                                <th class="text-white text-end">Discount</th>
                                <th class="text-white text-end">Tax %</th>
                                <th class="text-white text-end">Total</th>
                                <th class="text-white text-end">Effective Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sale_return_items as $item)
                                <tr>
                                    <td class="text-end">{{ $loop->iteration }}</td>
                                    <td> <a href="{{ route('inventory::product::view', $item['product_id']) }}">{{ $item['name'] }}</a> </td>
                                    <td class="text-end">{{ currency($item['unit_price']) }}</td>
                                    <td class="text-end">{{ currency($item['quantity']) }}</td>
                                    <td class="text-end">{{ currency($item['discount']) }}</td>
                                    <td class="text-end">{{ currency($item['tax_amount']) }} ({{ round($item['tax'], 2) }}%)</td>
                                    <td class="text-end"> {{ currency($item['total']) }} </td>
                                    <td class="text-end"> {{ currency($item['effective_total']) }} </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-end">Total</th>
                                <th class="text-end"><b>{{ currency($sale_return_items->sum('quantity')) }}</b></th>
                                <th class="text-end"><b>{{ currency($sale_return_items->sum('discount')) }}</b></th>
                                <th class="text-end"><b>{{ currency($sale_return_items->sum('tax_amount')) }}</b></th>
                                <th class="text-end"><b>{{ currency($sale_return_items->sum('total')) }}</b></th>
                                <th class="text-end"><b>{{ currency($sale_return_items->sum('effective_total')) }}</b></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>
