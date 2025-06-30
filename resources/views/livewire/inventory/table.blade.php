<div>
    <div class="card-header bg-white p-4">
        <!-- Header Section -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center gap-3">
                <div class="btn-group">
                    @can('inventory.export')
                        <button class="btn btn-sm btn-outline-primary" title="Export as Excel" wire:click="export()">
                            <i class="demo-pli-file-excel me-1"></i>
                            <span>Export</span>
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
            </div>
        </div>

        <!-- Filter Section -->
        <div class="bg-light rounded-4 border shadow-sm p-4 mb-4">
            <h6 class="mb-4 d-flex align-items-center gap-2 text-primary">
                <i class="demo-pli-filter-2 fs-4"></i>
                <span>Filter Items</span>
            </h6>
            <div class="row g-4">
                <div class="col-12 col-md-3" wire:ignore>
                    <label class="form-label fw-semibold mb-2">
                        <i class="demo-psi-building me-1 text-info"></i> Branch
                    </label>
                    {{ html()->select('branch_id', [auth()->user()->default_branch_id => auth()->user()->branch?->name])->value(auth()->user()->default_branch_id)->class('select-assigned-branch_id-list')->multiple()->id('branch_id') }}
                </div>
                <div class="col-12 col-md-3" wire:ignore>
                    <label class="form-label fw-semibold mb-2">
                        <i class="demo-pli-reload-3 me-1 text-warning"></i> Department
                    </label>
                    {{ html()->select('department_id', [])->value('')->class('select-department_id-list')->id('department_id')->placeholder('All Departments') }}
                </div>
                <div class="col-12 col-md-3" wire:ignore>
                    <label class="form-label fw-semibold mb-2">
                        <i class="demo-pli-folder me-1 text-primary"></i> Main Category
                    </label>
                    {{ html()->select('main_category_id', [])->value('')->class('select-category_id-list')->id('main_category_id')->placeholder('All Main Categories') }}
                </div>
                <div class="col-12 col-md-3" wire:ignore>
                    <label class="form-label fw-semibold mb-2">
                        <i class="demo-pli-folder-with-document me-1 text-success"></i> Sub Category
                    </label>
                    {{ html()->select('sub_category_id', [])->value('')->class('select-category_id-list')->id('sub_category_id')->placeholder('All Sub Categories') }}
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label fw-semibold mb-2">
                        <i class="demo-pli-barcode me-1 text-secondary"></i> UPC/EAN/ISBN/SKU
                    </label>
                    <input type="text" wire:model.live="code" class="form-control shadow-sm" placeholder="Search by code...">
                </div>
                <div class="col-12 col-md-3">
                    <div class="form-group mt-4">
                        <div class="form-check">
                            <input type="checkbox" id="non_zero" wire:model.live="non_zero" class="form-check-input">
                            <label for="non_zero" class="form-check-label">
                                <i class="demo-pli-box-with-folders me-1"></i> Show Non-Zero Items Only
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <hr class="my-4">
            <div class="row g-4">
                <div class="col-12 col-md-3" wire:ignore>
                    <label class="form-label fw-semibold mb-2">
                        <i class="demo-pli-folder me-1 text-info"></i> Brand
                    </label>
                    {{ html()->select('brand', [])->value('')->class('select-brand-list')->id('brand')->placeholder('All Brand') }}
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label fw-semibold mb-2">
                        <i class="demo-pli-barcode me-1 text-warning"></i> Size
                    </label>
                    <input type="text" wire:model.live="size" class="form-control shadow-sm" placeholder="Search by size...">
                </div>
            </div>
        </div>
    </div>
    <div class="card-body px-4 pb-4">
        <div class="table-responsive" style="max-height: 600px;">
            <table class="table table-hover align-middle mb-0 border">
                <thead class="bg-light position-sticky top-0" style="z-index: 1;">
                    <tr class="text-capitalize">
                        <th class="border-bottom">
                            <div class="d-flex align-items-center gap-2">
                                <div class="form-check m-0">
                                    <input type="checkbox" class="form-check-input" wire:model.live="selectAll" id="selectAll">
                                    <label class="form-check-label" for="selectAll"></label>
                                </div>
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="inventories.id" label="ID" />
                            </div>
                        </th>
                        <th class="border-bottom"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="branches.name" label="Branch" /> </th>
                        <th class="border-bottom"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="departments.name" label="Department" /> </th>
                        <th class="border-bottom"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="main_categories.name" label="Main Category" /> </th>
                        <th class="border-bottom"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="sub_categories.name" label="Sub Category" /> </th>
                        <th class="border-bottom"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="units.name" label="Unit" /> </th>
                        <th class="border-bottom"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="brand" label="Brand" /> </th>
                        <th class="border-bottom"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="products.size" label="Size" /> </th>
                        <th class="border-bottom"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="products.code" label="Code" /> </th>
                        <th class="border-bottom"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="products.name" label="Product Name" /> </th>
                        <th class="text-end border-bottom"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="inventories.quantity" label="Quantity" /> </th>
                        <th class="text-end border-bottom"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="inventories.cost" label="Cost" /> </th>
                        <th class="text-end border-bottom"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="inventories.total" label="Total" /> </th>
                        <th class="border-bottom"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="inventories.barcode" label="Barcode" /> </th>
                        <th class="border-bottom"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="inventories.batch" label="Batch" /> </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data as $item)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="form-check m-0">
                                        <input type="checkbox" class="form-check-input" value="{{ $item->id }}" wire:model.live="selected">
                                    </div>
                                    <span class="text-muted">#{{ $item->id }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <i class="demo-psi-building fs-5 text-info"></i>
                                    <span>{{ $item->branch_name ?? 'N/A' }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <i class="demo-pli-reload-3 fs-5 text-warning"></i>
                                    <span>{{ $item->department_name ?? 'N/A' }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <i class="demo-pli-folder fs-5 text-primary"></i>
                                    <span>{{ $item->main_category_name }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <i class="demo-pli-folder-with-document fs-5 text-success"></i>
                                    <span>{{ $item->sub_category_name }}</span>
                                </div>
                            </td>
                            <td>{{ $item->unit_name }}</td>
                            <td class="text-nowrap">{{ $item->brand }}</td>
                            <td class="text-nowrap">{{ $item->size }}</td>
                            <td>
                                <code class="bg-light px-2 py-1 rounded">{{ $item->code }}</code>
                            </td>
                            <td>
                                <a href="{{ route('inventory::product::view', $item->product_id) }}" class="text-decoration-none">
                                    <strong class="text-primary">{{ $item->name }}</strong>
                                    @if ($item->name_arabic)
                                        <br>
                                        <span class="text-muted small" style="text-align: right; display: block;" dir="rtl">
                                            {{ $item->name_arabic }}
                                        </span>
                                    @endif
                                </a>
                            </td>
                            <td class="text-end fw-bold">{{ $item->quantity }}</td>
                            <td class="text-end text-muted">{{ currency($item->cost) }}</td>
                            <td class="text-end fw-bold text-primary">{{ currency($item->total) }}</td>
                            <td>
                                @if ($item->barcode)
                                    <code class="bg-light px-2 py-1 rounded">{{ $item->barcode }}</code>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if ($item->batch)
                                    <span class="badge bg-info bg-opacity-10 text-info">{{ $item->batch }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="13" class="text-center py-4 text-muted">
                                <i class="demo-pli-warning-window fs-2 d-block mb-2"></i>
                                No inventory items found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-group-divider bg-light">
                    <tr>
                        <th colspan="10" class="text-end fw-bold">Total</th>
                        <th class="text-end fw-bold text-primary">{{ currency($quantity) }}</th>
                        <th></th>
                        <th class="text-end fw-bold text-primary">{{ currency($total) }}</th>
                        <th colspan="2"></th>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="mt-4">
            {{ $data->links() }}</div>
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
                $('#brand').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('brand', value);
                });
            });
        </script>
    @endpush
</div>
