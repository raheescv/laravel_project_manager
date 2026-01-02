<div>
    <div class="card-header bg-white p-2">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0"><i class="fa fa-boxes me-2"></i>Employee Inventory</h6>
            <div class="d-flex gap-2 align-items-center">
                <div class="input-group input-group-sm" style="width: 120px;">
                    <select wire:model.live="limit" class="form-select border-start-0">
                        <option value="10">10 rows</option>
                        <option value="25">25 rows</option>
                        <option value="50">50 rows</option>
                        <option value="100">100 rows</option>
                    </select>
                </div>
                <div class="input-group input-group-sm" style="width: 250px;">
                    <span class="input-group-text bg-light border-end-0">
                        <i class="demo-pli-magnifi-glass"></i>
                    </span>
                    <input type="text" wire:model.live="search" class="form-control border-start-0" placeholder="Search inventory...">
                </div>
            </div>
        </div>
    </div>
    <div class="card-body px-2 pb-2">
        <div class="table-responsive" style="max-height: 500px;">
            <table class="table table-hover table-sm align-middle mb-0 border">
                <thead class="bg-light position-sticky top-0" style="z-index: 1;">
                    <tr class="text-capitalize small">
                        <th class="border-bottom py-2">
                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="inventories.id" label="ID" />
                        </th>
                        <th class="border-bottom py-2">
                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="branches.name" label="Branch" />
                        </th>
                        <th class="border-bottom py-2">
                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="products.name" label="Product" />
                        </th>
                        <th class="border-bottom py-2">
                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="brand_name" label="Brand" />
                        </th>
                        <th class="border-bottom py-2">
                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="products.code" label="Code" />
                        </th>
                        <th class="border-bottom py-2">
                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="inventories.barcode" label="Barcode" />
                        </th>
                        <th class="border-bottom py-2">
                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="inventories.batch" label="Batch" />
                        </th>
                        <th class="text-end border-bottom py-2">
                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="inventories.quantity" label="Quantity" />
                        </th>
                        <th class="text-end border-bottom py-2">
                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="inventories.cost" label="Cost" />
                        </th>
                        <th class="text-end border-bottom py-2">
                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="inventories.total" label="Total" />
                        </th>
                        <th class="text-end border-bottom py-2">
                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="products.mrp" label="MRP" />
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data as $item)
                        <tr>
                            <td class="py-1">
                                <span class="text-muted small">#{{ $item->id }}</span>
                            </td>
                            <td class="py-1">
                                <span class="small">{{ $item->branch_name ?? 'N/A' }}</span>
                            </td>
                            <td class="py-1">
                                <div>
                                    <div class="fw-semibold small">{{ $item->name }}</div>
                                    @if($item->name_arabic)
                                        <div class="text-muted" style="font-size: 0.75rem;">{{ $item->name_arabic }}</div>
                                    @endif
                                    @if($item->size)
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary small">{{ $item->size }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="py-1">
                                <span class="small">{{ $item->brand_name ?? 'N/A' }}</span>
                            </td>
                            <td class="py-1">
                                <span class="small">{{ $item->code ?? 'N/A' }}</span>
                            </td>
                            <td class="py-1">
                                <span class="small font-monospace">{{ $item->barcode }}</span>
                            </td>
                            <td class="py-1">
                                <span class="small">{{ $item->batch }}</span>
                            </td>
                            <td class="text-end py-1">
                                <span class="small fw-semibold">{{ number_format($item->quantity, 3) }}</span>
                            </td>
                            <td class="text-end py-1">
                                <span class="small">{{ currency($item->cost) }}</span>
                            </td>
                            <td class="text-end py-1">
                                <span class="small fw-semibold text-primary">{{ currency($item->total) }}</span>
                            </td>
                            <td class="text-end py-1">
                                <span class="small">{{ currency($item->mrp) }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center py-4">
                                <div class="text-muted">
                                    <i class="fa fa-box-open fa-2x mb-2"></i>
                                    <p class="mb-0">No inventory items found</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($data->count() > 0)
                    <tfoot class="bg-light">
                        <tr>
                            <td colspan="7" class="text-end fw-semibold py-2">
                                <span>Total:</span>
                            </td>
                            <td class="text-end fw-semibold py-2">
                                <span>{{ number_format($quantity, 3) }}</span>
                            </td>
                            <td colspan="2" class="text-end fw-semibold py-2">
                                <span class="text-primary">{{ currency($total) }}</span>
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
        <div class="mt-2">
            {{ $data->links() }}
        </div>
    </div>
</div>

