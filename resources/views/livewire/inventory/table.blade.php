<div>
    <div class="card-header bg-white p-2">
        <!-- Header Section -->
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="d-flex align-items-center gap-2">
                <div class="btn-group">
                    @can('inventory.export')
                        <button class="btn btn-sm btn-outline-primary" title="Export as Excel" wire:click="export()">
                            <i class="demo-pli-file-excel me-1"></i>
                            <span>Export</span>
                        </button>
                        <button class="btn btn-sm btn-outline-info" title="Product Wise Export" wire:click="exportProductWise()">
                            <i class="demo-pli-file-excel me-1"></i>
                            <span>Product Wise Export</span>
                        </button>
                    @endcan
                    @can('inventory.import')
                        <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#ProductImportModal">
                            <i class="demo-pli-download-from-cloud me-1"></i>
                            <span>Import</span>
                        </button>
                    @endcan
                </div>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <div class="input-group input-group-sm" style="width: 120px;">
                    <select wire:model.live="limit" class="form-select border-start-0">
                        <option value="10">10 rows</option>
                        <option value="100">100 rows</option>
                        <option value="500">500 rows</option>
                        <option value="1000">1000 rows</option>
                        <option value="1500">1500 rows</option>
                    </select>
                </div>
                <div class="input-group input-group-sm" style="width: 250px;">
                    <span class="input-group-text bg-light border-end-0">
                        <i class="demo-pli-magnifi-glass"></i>
                    </span>
                    <input type="text" wire:model.live="search" class="form-control border-start-0" placeholder="Search inventory..." autofocus>
                </div>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="demo-pli-layout-grid me-1"></i> Actions
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li>
                            <a class="dropdown-item" data-bs-toggle="offcanvas" data-bs-target="#inventoryColumnVisibility" aria-controls="inventoryColumnVisibility">
                                <i class="demo-pli-layout-grid me-2"></i> Column Visibility
                            </a>
                        </li>
                        @can('inventory.opening balance')
                            <li>
                                <a class="dropdown-item" href="{{ route('inventory::opening-balance') }}">
                                    <i class="demo-pli-reload-3 me-2"></i> Opening Balance
                                </a>
                            </li>
                        @endcan
                        @can('inventory.reset stock')
                            <li>
                                <a class="dropdown-item" href="#" wire:click.prevent="openResetStockModal">
                                    <i class="demo-pli-reload-3 me-2"></i> Reset Stock
                                </a>
                            </li>
                        @endcan
                    </ul>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="bg-light rounded border shadow-sm p-2 mb-2">
            <h6 class="mb-2 d-flex align-items-center gap-2 text-primary small">
                <i class="demo-pli-filter-2"></i>
                <span>Filter Items</span>
            </h6>
            <div class="row g-2">
                <div class="col-12 col-md-3" wire:ignore>
                    <label class="form-label fw-semibold mb-1 small">
                        <i class="demo-psi-building me-1 text-info"></i> Branch
                    </label>
                    {{ html()->select('branch_id', [session('branch_id') => session('branch_name')])->value(session('branch_id'))->class('select-assigned-branch_id-list')->multiple()->id('branch_id') }}
                </div>
                <div class="col-12 col-md-3" wire:ignore>
                    <label class="form-label fw-semibold mb-1 small">
                        <i class="demo-pli-reload-3 me-1 text-warning"></i> Department
                    </label>
                    {{ html()->select('department_id', [])->value('')->class('select-department_id-list')->id('department_id')->placeholder('All Departments') }}
                </div>
                <div class="col-12 col-md-3" wire:ignore>
                    <label class="form-label fw-semibold mb-1 small">
                        <i class="demo-pli-folder me-1 text-primary"></i> Main Category
                    </label>
                    {{ html()->select('main_category_id', [])->value('')->class('select-category_id-list')->id('main_category_id')->placeholder('All Main Categories') }}
                </div>

                <div class="col-12 col-md-3" wire:ignore>
                    <label class="form-label fw-semibold mb-1 small">
                        <i class="demo-pli-tag me-1 text-primary"></i> Product Name
                    </label>
                    <input type="text" wire:model.live="product_name" class="form-control form-control-sm shadow-sm" placeholder="Search by product name...">
                </div>

                <div class="col-12 col-md-3" wire:ignore>
                    <label class="form-label fw-semibold mb-1 small">
                        <i class="demo-pli-folder-with-document me-1 text-success"></i> Sub Category
                    </label>
                    {{ html()->select('sub_category_id', [])->value('')->class('select-category_id-list')->id('sub_category_id')->placeholder('All Sub Categories') }}
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label fw-semibold mb-1 small">
                        <i class="demo-pli-barcode me-1 text-secondary"></i> UPC/EAN/ISBN/SKU
                    </label>
                    <input type="text" wire:model.live="code" class="form-control form-control-sm shadow-sm" placeholder="Search by code...">
                </div>
                <div class="col-12 col-md-3" wire:ignore>
                    <label class="form-label fw-semibold mb-1 small">
                        <i class="demo-pli-folder me-1 text-info"></i> Brand
                    </label>
                    {{ html()->select('brand_id', [])->value('')->class('select-brand_id-list')->id('brand_id')->placeholder('All Brand') }}
                </div>
                <div class="col-12 col-md-3">
                    <div class="form-group mt-3">
                        <div class="form-check">
                            <input type="checkbox" id="non_zero" wire:model.live="non_zero" class="form-check-input">
                            <label for="non_zero" class="form-check-label small">
                                <i class="demo-pli-box-with-folders me-1"></i> Show Non-Zero Items Only
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <hr class="my-2">
            <div class="row g-2">
                <div class="col-12 col-md-3">
                    <label class="form-label fw-semibold mb-1 small">
                        <i class="demo-pli-folder me-1 text-warning"></i> Size
                    </label>
                    <input type="text" wire:model.live="size" class="form-control form-control-sm shadow-sm" placeholder="Search by size...">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label fw-semibold mb-1 small">
                        <i class="demo-pli-folder me-1 text-warning"></i> Barcode
                    </label>
                    <input type="text" wire:model.live="barcode" class="form-control form-control-sm shadow-sm" placeholder="Search by barcode...">
                </div>
            </div>
        </div>
    </div>
    <div class="card-body px-2 pb-2">
        <div class="table-responsive" style="max-height: 600px;">
            <table class="table table-hover table-sm align-middle mb-0 border">
                <thead class="bg-light position-sticky top-0" style="z-index: 1;">
                    <tr class="text-capitalize small">
                        <th class="border-bottom py-2">
                            <div class="d-flex align-items-center gap-1">
                                <div class="form-check m-0">
                                    <input type="checkbox" class="form-check-input" wire:model.live="selectAll" id="selectAll">
                                    <label class="form-check-label" for="selectAll"></label>
                                </div>
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="inventories.id" label="ID" />
                            </div>
                        </th>
                        @if ($inventory_visible_column['branch'] ?? true)
                            <th class="border-bottom py-2"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="branches.name" label="Branch" /> </th>
                        @endif
                        @if ($inventory_visible_column['department'] ?? true)
                            <th class="border-bottom py-2"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="departments.name" label="Department" /> </th>
                        @endif
                        @if ($inventory_visible_column['main_category'] ?? true)
                            <th class="border-bottom py-2"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="main_categories.name" label="Main Category" /> </th>
                        @endif
                        @if ($inventory_visible_column['sub_category'] ?? true)
                            <th class="border-bottom py-2"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="sub_categories.name" label="Sub Category" /> </th>
                        @endif
                        @if ($inventory_visible_column['unit'] ?? true)
                            <th class="border-bottom py-2"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="units.name" label="Unit" /> </th>
                        @endif
                        @if ($inventory_visible_column['brand'] ?? true)
                            <th class="border-bottom py-2"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="brand_name" label="Brand" /> </th>
                        @endif
                        @if ($inventory_visible_column['size'] ?? true)
                            <th class="border-bottom py-2"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="products.size" label="Size" /> </th>
                        @endif
                        @if ($inventory_visible_column['code'] ?? true)
                            <th class="border-bottom py-2"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="products.code" label="Code" /> </th>
                        @endif
                        @if ($inventory_visible_column['product_name'] ?? true)
                            <th class="border-bottom py-2"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="products.name" label="Product Name" /> </th>
                        @endif
                        @if ($inventory_visible_column['quantity'] ?? true)
                            <th class="text-end border-bottom py-2"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="inventories.quantity" label="Quantity" /> </th>
                        @endif
                        @if ($inventory_visible_column['cost'] ?? true)
                            <th class="text-end border-bottom py-2"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="inventories.cost" label="Cost" /> </th>
                        @endif
                        @if ($inventory_visible_column['total'] ?? true)
                            <th class="text-end border-bottom py-2"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="inventories.total" label="Total" /> </th>
                        @endif
                        @if ($inventory_visible_column['mrp'] ?? true)
                            <th class="text-end border-bottom py-2"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="products.mrp" label="MRP" /> </th>
                        @endif
                        @if ($inventory_visible_column['barcode'] ?? true)
                            <th class="border-bottom py-2"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="inventories.barcode" label="Barcode" /> </th>
                        @endif
                        @if ($inventory_visible_column['batch'] ?? true)
                            <th class="border-bottom py-2"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="inventories.batch" label="Batch" /> </th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data as $item)
                        <tr>
                            <td class="py-1">
                                <div class="d-flex align-items-center gap-1">
                                    <div class="form-check m-0">
                                        <input type="checkbox" class="form-check-input" value="{{ $item->id }}" wire:model.live="selected">
                                    </div>
                                    <span class="text-muted small">#{{ $item->id }}</span>
                                </div>
                            </td>
                            @if ($inventory_visible_column['branch'] ?? true)
                                <td class="py-1">
                                    <div class="d-flex align-items-center gap-1">
                                        <span class="bg-info bg-opacity-10 rounded-circle p-1 d-inline-flex align-items-center justify-content-center" title="Branch">
                                            <i class="demo-psi-building text-info" style="font-size: 0.875rem;"></i>
                                        </span>
                                        <span class="small">{{ $item->branch_name ?? 'N/A' }}</span>
                                    </div>
                                </td>
                            @endif
                            @if ($inventory_visible_column['department'] ?? true)
                                <td class="py-1">
                                    <div class="d-flex align-items-center gap-1">
                                        <span class="bg-warning bg-opacity-10 rounded-circle p-1 d-inline-flex align-items-center justify-content-center" title="Department">
                                            <i class="demo-pli-reload-3 text-warning" style="font-size: 0.875rem;"></i>
                                        </span>
                                        <span class="small">{{ $item->department_name ?? 'N/A' }}</span>
                                    </div>
                                </td>
                            @endif
                            @if ($inventory_visible_column['main_category'] ?? true)
                                <td class="py-1">
                                    <div class="d-flex align-items-center gap-1">
                                        <span class="bg-primary bg-opacity-10 rounded-circle p-1 d-inline-flex align-items-center justify-content-center" title="Main Category">
                                            <i class="demo-pli-folder text-primary" style="font-size: 0.875rem;"></i>
                                        </span>
                                        <span class="small">{{ $item->main_category_name }}</span>
                                    </div>
                                </td>
                            @endif
                            @if ($inventory_visible_column['sub_category'] ?? true)
                                <td class="py-1">
                                    <div class="d-flex align-items-center gap-1">
                                        <span class="bg-success bg-opacity-10 rounded-circle p-1 d-inline-flex align-items-center justify-content-center" title="Sub Category">
                                            <i class="demo-pli-folder-with-document text-success" style="font-size: 0.875rem;"></i>
                                        </span>
                                        <span class="small">{{ $item->sub_category_name }}</span>
                                    </div>
                                </td>
                            @endif
                            @if ($inventory_visible_column['unit'] ?? true)
                                <td class="py-1 small">{{ $item->unit_name }}</td>
                            @endif
                            @if ($inventory_visible_column['brand'] ?? true)
                                <td class="text-nowrap py-1 small">{{ $item->brand_name }}</td>
                            @endif
                            @if ($inventory_visible_column['size'] ?? true)
                                <td class="text-nowrap py-1 small">{{ $item->size }}</td>
                            @endif
                            @if ($inventory_visible_column['code'] ?? true)
                                <td class="py-1">
                                    <code class="bg-light px-1 py-0 rounded small">{{ $item->code }}</code>
                                </td>
                            @endif
                            @if ($inventory_visible_column['product_name'] ?? true)
                                <td class="py-1">
                                    <a href="{{ route('inventory::product::view', $item->product_id) }}" class="text-decoration-none">
                                        <strong class="text-primary small">{{ $item->name }}</strong>
                                        @if ($item->name_arabic)
                                            <br>
                                            <span class="text-muted" style="text-align: right; display: block; font-size: 0.75rem;" dir="rtl">
                                                {{ $item->name_arabic }}
                                            </span>
                                        @endif
                                    </a>
                                </td>
                            @endif
                            @if ($inventory_visible_column['quantity'] ?? true)
                                <td class="text-end fw-bold py-1 small">{{ $item->quantity }}</td>
                            @endif
                            @if ($inventory_visible_column['cost'] ?? true)
                                <td class="text-end text-muted py-1 small">{{ currency($item->cost) }}</td>
                            @endif
                            @if ($inventory_visible_column['total'] ?? true)
                                <td class="text-end fw-bold text-primary py-1 small">{{ currency($item->total) }}</td>
                            @endif
                            @if ($inventory_visible_column['mrp'] ?? true)
                                <td class="text-end text-muted py-1 small">{{ currency($item->mrp) }}</td>
                            @endif
                            @if ($inventory_visible_column['barcode'] ?? true)
                                <td class="py-1">
                                    @if ($item->barcode)
                                        <code class="bg-light px-1 py-0 rounded small">{{ $item->barcode }}</code>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                            @endif
                            @if ($inventory_visible_column['batch'] ?? true)
                                <td class="py-1">
                                    @if ($item->batch)
                                        <span class="badge bg-info bg-opacity-10 text-info small">{{ $item->batch }}</span>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count(array_filter($inventory_visible_column ?? [])) + 1 }}" class="text-center py-4 text-muted">
                                <i class="demo-pli-warning-window fs-2 d-block mb-2"></i>
                                No inventory items found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-group-divider bg-light position-sticky bottom-0" style="z-index: 1;">
                    <tr>
                        @if ($inventory_visible_column['branch'] ?? true)
                            <th></th>
                        @endif
                        @if ($inventory_visible_column['department'] ?? true)
                            <th></th>
                        @endif
                        @if ($inventory_visible_column['main_category'] ?? true)
                            <th></th>
                        @endif
                        @if ($inventory_visible_column['sub_category'] ?? true)
                            <th></th>
                        @endif
                        @if ($inventory_visible_column['unit'] ?? true)
                            <th></th>
                        @endif
                        @if ($inventory_visible_column['brand'] ?? true)
                            <th></th>
                        @endif
                        @if ($inventory_visible_column['size'] ?? true)
                            <th></th>
                        @endif
                        @if ($inventory_visible_column['code'] ?? true)
                            <th></th>
                        @endif
                        @if ($inventory_visible_column['product_name'] ?? true)
                        @endif
                        <th colspan="2" class="text-end fw-bold">Total</th>
                        @if ($inventory_visible_column['quantity'] ?? true)
                            <th class="text-end fw-bold text-primary">{{ $quantity }}</th>
                        @endif
                        <th></th>
                        @if ($inventory_visible_column['total'] ?? true)
                            <th class="text-end fw-bold text-primary">{{ currency($total) }}</th>
                        @endif
                        @if ($inventory_visible_column['mrp'] ?? true)
                            <th class="text-end fw-bold text-primary"></th>
                        @endif
                        @if ($inventory_visible_column['barcode'] ?? true)
                            <th class="text-end fw-bold text-primary"></th>
                        @endif
                        @if ($inventory_visible_column['batch'] ?? true)
                            <th class="text-end fw-bold text-primary"></th>
                        @endif
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="mt-4">
            {{ $data->links() }}
        </div>
    </div>
    <!-- Reset Stock Modal -->
    <div class="modal fade" id="ResetStockModal" tabindex="-1" aria-labelledby="ResetStockModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="ResetStockModalLabel">
                        <i class="demo-pli-warning-window me-2"></i> Reset Stock to Zero
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning d-flex align-items-center" role="alert">
                        <i class="demo-pli-warning-window fs-4 me-2"></i>
                        <div>
                            <strong>Warning!</strong> This action will reset all inventory quantities to 0 based on your current filters. This action cannot be undone.
                        </div>
                    </div>
                    <form wire:submit.prevent="resetStock">
                        <div class="mb-3">
                            <label for="resetReason" class="form-label fw-semibold">
                                Reason for Reset <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control @error('resetReason') is-invalid @enderror" id="resetReason" wire:model="resetReason" rows="4"
                                placeholder="Please provide a reason for resetting the stock (e.g., Stock audit, Inventory correction, etc.)" required></textarea>
                            @error('resetReason')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Minimum 3 characters, maximum 100 characters.</small>
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="demo-pli-cross me-1"></i> Cancel
                            </button>
                            <button type="submit" class="btn btn-danger">
                                <i class="demo-pli-reload-3 me-1"></i> Reset Stock to Zero
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            $(document).ready(function() {
                $('#branch_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('branch_id', value);
                });
                $('#department_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('department_id', value);
                });
                $('#main_category_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('main_category_id', value);
                });
                $('#sub_category_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('sub_category_id', value);
                });
                $('#product_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('product_id', value);
                });
                $('#brand_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('brand_id', value);
                });
            });

            // Handle Reset Stock Modal
            window.addEventListener('openResetStockModal', event => {
                $('#ResetStockModal').modal('show');
            });

            window.addEventListener('closeResetStockModal', event => {
                $('#ResetStockModal').modal('hide');
            });

            // Reset form when modal is closed
            $('#ResetStockModal').on('hidden.bs.modal', function() {
                @this.closeResetStockModal();
            });
        </script>
    @endpush
</div>
