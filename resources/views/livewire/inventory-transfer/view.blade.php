<div>
    <div class="col-md-12 mb-3">
        <div class="card">
            <div class="card-body">
                <!-- Invoice info -->
                <div class="d-md-flex">
                    <div class="col-md-12 mb-4">
                        <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
                            <div class="card-body p-5 bg-white">
                                <div class="bg-light rounded-4 p-4 mb-4">
                                    <div class="row align-items-center text-center">
                                        <div class="col-md-5 mb-3 mb-md-0">
                                            <div class="text-muted small">From Branch</div>
                                            <div class="fs-5 fw-semibold text-primary">{{ $model->fromBranch?->name }}</div>
                                        </div>
                                        <div class="col-md-2">
                                            <i class="bi bi-arrow-left-right fs-1 text-success"></i>
                                        </div>
                                        <div class="col-md-5">
                                            <div class="text-muted small">To Branch</div>
                                            <div class="fs-5 fw-semibold text-primary">{{ $model->toBranch?->name }}</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Transfer Meta Info -->
                                <div class="row g-4">
                                    <div class="col-md-6 col-lg-4">
                                        <div class="border rounded-3 p-3 bg-light h-100">
                                            <div class="text-muted small mb-1"><i class="bi bi-hash me-1"></i>Transfer No</div>
                                            <div class="fw-medium">#{{ $model->id }}</div>
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-lg-4">
                                        <div class="border rounded-3 p-3 bg-light h-100">
                                            <div class="text-muted small mb-1"><i class="bi bi-calendar-event me-1"></i> Date</div>
                                            <div class="fw-medium">{{ systemDate($model->date) }}</div>
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-lg-4">
                                        <div class="border rounded-3 p-3 bg-light h-100">
                                            <div class="text-muted small mb-1"><i class="bi bi-check-circle me-1"></i>Status</div>
                                            <span class="badge bg-success px-3 py-2 fs-6 rounded-pill">{{ ucFirst($model->status) }}</span>
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-lg-4">
                                        <div class="border rounded-3 p-3 bg-light h-100">
                                            <div class="text-muted small mb-1"><i class="bi bi-person-circle me-1"></i>Created By</div>
                                            <div class="fw-medium">{{ $model->createdBy?->name }}</div>
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-lg-4">
                                        <div class="border rounded-3 p-3 bg-light h-100">
                                            <div class="text-muted small mb-1"><i class="bi bi-person-check me-1"></i>Approved By</div>
                                            <div class="fw-medium">{{ $model->approvedBy?->name }}</div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                </div>
                <!-- END : Invoice info -->

                <!-- Invoice table -->
                <h5 class="card-title">Items</h5>
                <div class="table-responsive">
                    <table class="table table-striped table-sm align-middle">
                        <thead>
                            <tr class="bg-primary">
                                <th class="text-white">SL No</th>
                                <th class="text-white" width="30%">Product</th>
                                <th class="text-white">Batch</th>
                                <th class="text-white">Barcode</th>
                                <th class="text-white text-end">Quantity</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $items = $model->items;
                            @endphp
                            @foreach ($items as $item)
                                <tr wire:key="item-{{ $item['key'] }}">
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $item['name'] }}</td>
                                    <td>{{ $item->inventory->batch }}</td>
                                    <td>{{ $item->inventory->barcode }}</td>
                                    <td class="text-end">{{ $item['quantity'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            @php
                                $items = collect($items);
                            @endphp
                            <tr>
                                <th colspan="4" class="text-end">Total</th>
                                <th class="text-end"><b>{{ currency($items->sum('quantity')) }}</b></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <!-- END : Invoice table -->

                <div class="d-flex justify-content-end gap-2 my-4 d-print-none">
                    @if ($model['status'] != 'cancelled')
                        <a target="_blank" href="{{ route('inventory::transfer::print', $model['id']) }}" class="btn btn-outline-light btn-icon">
                            <i class="demo-pli-printer fs-4"></i>
                        </a>
                        @can('inventory transfer.edit completed')
                            <a href="{{ route('inventory::transfer::edit', $model['id']) }}" type="button" class="btn btn-primary">Edit</a>
                        @endcan
                    @endif
                </div>
                <!-- END : Print button and confirm payment -->
                @if ($model['description'])
                    <!-- Footer information -->
                    <div class="bg-body-tertiary p-3 rounded bg-opacity-40 mt-5">
                        <p class="h5">Description</p>
                        <p>{{ $model['description'] }}</p>
                    </div>
                    <!-- END : Footer information -->
                @endif
            </div>
        </div>
    </div>
    <div class="tab-base">
        <ul class="nav nav-underline nav-component border-bottom" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link px-3 active" data-bs-toggle="tab" data-bs-target="#tab-inventory-logs" type="button" role="tab" aria-controls="contact" aria-selected="false"
                    tabindex="-1">
                    Inventory Logs
                </button>
            </li>
        </ul>
        <div class="tab-content">
            <div id="tab-inventory-logs" class="tab-pane fade active show" role="tabpanel" aria-labelledby="contact-tab">
                <div class="table-responsive">
                    <table class="table table-striped table-sm">
                        <thead>
                            <tr class="text-capitalize">
                                <th>#</th>
                                <th>Date</th>
                                <th>Branch</th>
                                <th>Department</th>
                                <th>Main Category</th>
                                <th>Product</th>
                                <th>barcode</th>
                                <th>batch</th>
                                <th class="text-end">In</th>
                                <th class="text-end">out</th>
                                <th class="text-end">balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($logs as $item)
                                <tr>
                                    <td>{{ $item->id }}</td>
                                    <td>{{ systemDateTime($item->created_at) }}</td>
                                    <td>{{ $item->branch?->name }}</td>
                                    <td>{{ $item->product?->department?->name }}</td>
                                    <td>{{ $item->product?->mainCategory?->name }}</td>
                                    <td> <a href="{{ route('inventory::product::view', $item->product_id) }}">{{ $item->product?->name }}</a> </td>
                                    <td>{{ $item->barcode }}</td>
                                    <td>{{ $item->batch }}</td>
                                    <td class="text-end">{{ $item->quantity_in }}</td>
                                    <td class="text-end">{{ $item->quantity_out }}</td>
                                    <td class="text-end">{{ $item->balance }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
