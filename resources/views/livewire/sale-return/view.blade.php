<div>
    <div class="col-md-12 mb-3">
        <div class="card">
            <div class="card-body">
                <!-- Invoice info -->
                <div class="d-md-flex">
                    <address class="mb-4 mb-md-0">
                        <h5 class="mb-2"> <a href="{{ route('account::customer::view', $sale_return->account_id) }}">{{ $sale_return->account?->name }}</a> </h5>
                        @if ($sale_return->customer_name)
                            <b>Customer Name :</b> {{ $sale_return->customer_name }} <br>
                        @endif
                        @if ($sale_return->customer_mobile)
                            <b>Mobile :</b> {{ $sale_return->customer_mobile }} <br>
                        @endif
                    </address>
                    <ul class="list-group list-group-borderless ms-auto">
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 pt-0 pb-1">
                            <div class="me-5 fw-semibold text-body-emphasis">Invoice #</div>
                            <span class="ms-auto text-info fw-bold">{{ $sale_return->id }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 pt-0 pb-1">
                            <div class="me-5 fw-semibold text-body-emphasis">Reference No</div>
                            <span class="ms-auto">{{ $sale_return->reference_no }}</span>
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
                            @if (isset($statusClasses[$sale_return->status]))
                                <span class="badge {{ $statusClasses[$sale_return->status] }} rounded-pill">
                                    {{ ucFirst($sale_return->status) }}
                                </span>
                            @endif
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 pt-0 pb-1">
                            <div class="me-5 fw-semibold text-body-emphasis">Date</div>
                            <span class="ms-auto">{{ systemDate($sale_return->date) }}</span>
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
                                @if ($sale_returns['other_discount'] > 0)
                                    <th class="text-white text-end">Effective Total</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td> <a href="{{ route('inventory::product::view', $item['product_id']) }}">{{ $item['name'] }}</a> </td>
                                    <td class="text-end">{{ currency($item['unit_price']) }}</td>
                                    <td class="text-end">{{ currency($item['quantity']) }}</td>
                                    <td class="text-end">{{ currency($item['discount']) }}</td>
                                    <td class="text-end">{{ currency($item['tax_amount']) }} ({{ round($item['tax'], 2) }}%)</td>
                                    <td class="text-end"> {{ currency($item['total']) }} </td>
                                    @if ($sale_returns['other_discount'] > 0)
                                        <td class="text-end"> {{ currency($item['effective_total']) }} </td>
                                    @endif
                                </tr>
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
                                @if ($sale_returns['other_discount'] > 0)
                                    <th class="text-end"><b>{{ currency($items->sum('effective_total')) }}</b></th>
                                @endif
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <!-- END : Invoice table -->
                <div class="row">
                    <div class="col-12 offset-md-8 col-md-4">
                        <ul class="list-group list-group-borderless">
                            <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                                <div class="me-5 mb-0 h5">Gross Total</div>
                                <span class="ms-auto h5 mb-0">{{ currency($sale_return->gross_amount) }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                                <div class="me-5 mb-0 h5">Sale Return Total</div>
                                <span class="ms-auto h5 mb-0">{{ currency($sale_return->total) }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                                <div class="me-5 mb-0 h5">Other Discount</div>
                                <span class="ms-auto h5 mb-0">{{ currency($sale_return->other_discount) }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                                <div class="me-5 mb-0 h5">Total Payable Amount</div>
                                <span class="ms-auto h5 mb-0">{{ currency($sale_return->grand_total) }}</span>
                            </li>
                        </ul>
                    </div>
                </div>


                <div class="d-flex justify-content-end gap-2 my-4 d-print-none">
                    @if ($sale_returns['status'] != 'cancelled')
                        @can('sales return.cancel')
                            <button type="button" wire:click='save("cancelled")' wire:confirm="Are you sure to cancel this?" class="btn btn-danger btn-sm">
                                Cancel
                            </button>
                        @endcan
                        @can('sales return.edit completed')
                            <a href="{{ route('sale_return::edit', $sale_returns['id']) }}" type="button" class="btn btn-primary">Edit</a>
                        @endcan
                        @can('sales return.cancel')
                            <button type="button" wire:click='sendToWhatsapp' class="btn btn-info btn-sm">
                                Whatsapp
                            </button>
                        @endcan
                    @endif
                </div>
                <!-- END : Print button and confirm payment -->
                @if ($sale_return['address'])
                    <!-- Footer information -->
                    <div class="bg-body-tertiary p-3 rounded bg-opacity-40 mt-5">
                        <p class="h5">Notes &amp; Information</p>
                        <p>{{ $sale_return['address'] }}</p>
                    </div>
                    <!-- END : Footer information -->
                @endif
            </div>
        </div>
    </div>
    @can('sales return.view journal entries')
        @if (count($sale_return->journals))
            <div class="row mb-3">
                <div class="col-md-12 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">Journal Entries </h5>
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
                                        @foreach ($sale_return->journals as $journal)
                                            @foreach ($journal->entries as $entry)
                                                <tr>
                                                    <td class="text-end">{{ $entry->id }}</td>
                                                    <td>{{ systemDate($journal->date) }}</td>
                                                    <td>{{ $entry->account?->name }}</td>
                                                    <td>{{ $entry->remarks }}</td>
                                                    <td class="text-end">{{ currency($entry->debit) }}</td>
                                                    <td class="text-end">{{ currency($entry->credit) }}</td>
                                                </tr>
                                            @endforeach
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endcan
</div>
