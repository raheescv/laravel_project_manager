<div>
    <div class="card shadow-sm">
        <div class="card-header bg-light py-3">
            <div class="row mt-3">
                <div class="col-md-6 d-flex flex-wrap gap-2 align-items-center mb-3 mb-md-0">
                    @can('product.create')
                        <a class="btn btn-primary d-flex align-items-center shadow-sm" href="{{ route('product::create') }}">
                            <i class="demo-psi-add me-2"></i>
                            Add New Product
                        </a>
                    @endcan
                    <div class="btn-group shadow-sm">
                        @can('product.export')
                            <button class="btn btn-success btn-sm d-flex align-items-center" title="Export to Excel" data-bs-toggle="tooltip" wire:click="export()">
                                <i class="demo-pli-file-excel me-md-1 fs-5"></i>
                                <span class="d-none d-md-inline">Export</span>
                            </button>
                        @endcan
                        @can('product.delete')
                            <button class="btn btn-danger btn-sm d-flex align-items-center" title="Delete Selected" data-bs-toggle="tooltip" wire:click="delete()"
                                wire:confirm="Are you sure you want to delete the selected items?">
                                <i class="demo-pli-recycling me-md-1 fs-5"></i>
                                <span class="d-none d-md-inline">Delete</span>
                            </button>
                        @endcan
                        @can('product.import')
                            <button class="btn btn-info btn-sm d-flex align-items-center text-white" title="Import Products" data-bs-toggle="modal" data-bs-target="#ProductImportModal">
                                <i class="demo-pli-download-from-cloud me-md-1 fs-5"></i>
                                <span class="d-none d-md-inline">Import</span>
                            </button>
                        @endcan
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="row g-2 align-items-center">
                        <div class="col-auto">
                            <label class="form-label mb-0 text-muted small fw-semibold">Show:</label>
                        </div>
                        <div class="col-auto">
                            <select wire:model.live="limit" class="form-select form-select-sm border-secondary-subtle shadow-sm">
                                <option value="10">10</option>
                                <option value="100">100</option>
                                <option value="500">500</option>
                            </select>
                        </div>
                        <div class="col">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white border-secondary-subtle">
                                    <i class="demo-psi-magnifi-glass"></i>
                                </span>
                                <input type="text" id="search" wire:model.live="search" placeholder="Search products..." class="form-control border-secondary-subtle shadow-sm" autocomplete="off"
                                    aria-label="Search products">
                            </div>
                        </div>
                        <div class="col-auto">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-primary shadow-sm" data-bs-toggle="dropdown" aria-expanded="false" title="Column Visibility">
                                    <i class="demo-pli-layout-grid"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                    <li>
                                        <a class="dropdown-item" data-bs-toggle="offcanvas" data-bs-target="#productColumnVisibility" aria-controls="productColumnVisibility">
                                            <i class="demo-pli-column-width me-2"></i>Column Visibility
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <hr class="my-3">
            <div class="row g-3">
                <div class="col-lg-4 col-md-6" wire:ignore>
                    <div>
                        <label for="department_id" class="form-label small fw-medium text-capitalize">
                            <i class="demo-psi-building me-1 text-muted"></i>
                            Department
                        </label>
                        {{ html()->select('department_id', [])->value('')->class('select-department_id-list shadow-sm border-secondary-subtle')->id('department_id')->placeholder('All Departments') }}
                    </div>
                </div>

                <div class="col-lg-4 col-md-6" wire:ignore>
                    <div>
                        <label for="main_category_id" class="form-label small fw-medium text-capitalize">
                            <i class="demo-psi-folder me-1 text-muted"></i>
                            Main Category
                        </label>
                        {{ html()->select('main_category_id', [])->value('')->class('select-category_id-list shadow-sm border-secondary-subtle')->id('main_category_id')->placeholder('All Categories') }}
                    </div>
                </div>

                <div class="col-lg-4 col-md-6" wire:ignore>
                    <div>
                        <label for="sub_category_id" class="form-label small fw-medium text-capitalize">
                            <i class="demo-psi-folder-open me-1 text-muted"></i>
                            Sub Category
                        </label>
                        {{ html()->select('sub_category_id', [])->value('')->class('select-category_id-list shadow-sm border-secondary-subtle')->id('sub_category_id')->placeholder('All Sub Categories') }}
                    </div>
                </div>

                <div class="col-lg-4 col-md-6" wire:ignore>
                    <div>
                        <label for="brand_id" class="form-label small fw-medium text-capitalize">
                            <i class="demo-psi-tag me-1 text-muted"></i>
                            Brand
                        </label>
                        {{ html()->select('brand_id', [])->value('')->class('select-brand_id-list shadow-sm border-secondary-subtle')->id('brand_id')->placeholder('All Brands') }}
                    </div>
                </div>

                <div class="col-lg-4 col-md-6" wire:ignore>
                    <div>
                        <label for="unit_id" class="form-label small fw-medium text-capitalize">
                            <i class="demo-psi-cube me-1 text-muted"></i>
                            Unit
                        </label>
                        {{ html()->select('unit_id', [])->value('')->class('select-unit_id-list shadow-sm border-secondary-subtle')->id('unit_id')->placeholder('All Units') }}
                    </div>
                </div>

                <div class="col-lg-4 col-md-6" wire:ignore>
                    <div>
                        <label for="status" class="form-label small fw-medium text-capitalize">
                            <i class="demo-psi-toggle me-1 text-muted"></i>
                            Status
                        </label>
                        {{ html()->select('status', activeOrDisabled())->value('')->class('form-select shadow-sm border-secondary-subtle')->placeholder('Select Status')->id('status')->attribute('wire:model.live', 'status') }}
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div>
                        <label class="d-block form-label small fw-medium text-capitalize mb-2">
                            <i class="demo-psi-shop me-1 text-muted"></i>
                            Sales Status
                        </label>
                        <div class="form-check form-switch">
                            {{ html()->checkbox('is_selling', [])->value('')->class('form-check-input')->attribute('wire:model.live', 'is_selling')->id('is_selling') }}
                            <label for="is_selling" class="form-check-label">
                                &nbsp; Is Currently Selling
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle border-bottom mb-0">
                <thead class="table-light text-capitalize">
                    <tr>
                        @if ($product_visible_column['id'] ?? true)
                            <th class="border-0">
                                <div class="form-check ms-1">
                                    <input class="form-check-input" type="checkbox" wire:model.live="selectAll" id="selectAll" />
                                    <label class="form-check-label" for="selectAll">
                                        <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="id" label="ID" />
                                    </label>
                                </div>
                            </th>
                        @endif
                        @if ($product_visible_column['department'] ?? true)
                            <th class="border-0">
                                <div class="d-flex align-items-center">
                                    <i class="demo-psi-building me-2 text-secondary small"></i>
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="department_id" label="Department" />
                                </div>
                            </th>
                        @endif
                        @if ($product_visible_column['main_category'] ?? true)
                            <th class="border-0">
                                <div class="d-flex align-items-center">
                                    <i class="demo-psi-folder me-2 text-secondary small"></i>
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="main_category_id" label="Main Category" />
                                </div>
                            </th>
                        @endif
                        @if ($product_visible_column['sub_category'] ?? true)
                            <th class="border-0">
                                <div class="d-flex align-items-center">
                                    <i class="demo-psi-folder-open me-2 text-secondary small"></i>
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="sub_category_id" label="Sub Category" />
                                </div>
                            </th>
                        @endif
                        @if ($product_visible_column['unit'] ?? true)
                            <th class="border-0">
                                <div class="d-flex align-items-center">
                                    <i class="demo-psi-cube me-2 text-secondary small"></i>
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="unit_id" label="Unit" />
                                </div>
                            </th>
                        @endif
                        @if ($product_visible_column['brand'] ?? true)
                            <th class="border-0">
                                <div class="d-flex align-items-center">
                                    <i class="demo-psi-tag me-2 text-secondary small"></i>
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="brand_id" label="Brand" />
                                </div>
                            </th>
                        @endif
                        @if ($product_visible_column['size'] ?? true)
                            <th class="border-0">
                                <div class="d-flex align-items-center">
                                    <i class="demo-psi-cube me-2 text-secondary small"></i>
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="size" label="Size" />
                                </div>
                            </th>
                        @endif
                        @if ($product_visible_column['code'] ?? true)
                            <th class="border-0">
                                <div class="d-flex align-items-center">
                                    <i class="demo-psi-coding me-2 text-secondary small"></i>
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="code" label="Code" />
                                </div>
                            </th>
                        @endif
                        @if ($product_visible_column['product_name'] ?? true)
                            <th class="border-0">
                                <div class="d-flex align-items-center">
                                    <i class="demo-psi-tag me-2 text-secondary small"></i>
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="name" label="Product Name" />
                                </div>
                            </th>
                        @endif
                        @if ($product_visible_column['name_arabic'] ?? true)
                            <th class="border-0">
                                <div class="d-flex align-items-center">
                                    <i class="demo-psi-tag me-2 text-secondary small"></i>
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="name_arabic" label="Arabic Name" />
                                </div>
                            </th>
                        @endif
                        @if ($product_visible_column['barcode'] ?? true)
                            <th class="border-0">
                                <div class="d-flex align-items-center">
                                    <i class="demo-psi-barcode me-2 text-secondary small"></i>
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="barcode" label="Barcode" />
                                </div>
                            </th>
                        @endif
                        @if ($product_visible_column['cost'] ?? true)
                            <th class="border-0 text-end">
                                <div class="d-flex align-items-center justify-content-end">
                                    <i class="demo-psi-dollar me-2 text-secondary small"></i>
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="cost" label="Cost" />
                                </div>
                            </th>
                        @endif
                        @if ($product_visible_column['mrp'] ?? true)
                            <th class="border-0 text-end">
                                <div class="d-flex align-items-center justify-content-end">
                                    <i class="demo-psi-coin me-2 text-secondary small"></i>
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="mrp" label="MRP" />
                                </div>
                            </th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data as $item)
                        <tr class="align-middle">
                            @if ($product_visible_column['id'] ?? true)
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="form-check ms-1">
                                            <input class="form-check-input" type="checkbox" value="{{ $item->id }}" wire:model.live="selected" id="product-{{ $item->id }}" />
                                        </div>
                                        <span class="badge bg-secondary rounded-pill">{{ $item->id }}</span>
                                    </div>
                                </td>
                            @endif
                            @if ($product_visible_column['department'] ?? true)
                                <td>
                                    <span class="fw-medium text-primary">{{ $item->department?->name ?? '-' }}</span>
                                </td>
                            @endif
                            @if ($product_visible_column['main_category'] ?? true)
                                <td>
                                    <span class="text-secondary">{!! $item->mainCategory?->name ?? '-' !!}</span>
                                </td>
                            @endif
                            @if ($product_visible_column['sub_category'] ?? true)
                                <td>
                                    <span class="text-secondary">{{ $item->subCategory?->name ?? '-' }}</span>
                                </td>
                            @endif
                            @if ($product_visible_column['unit'] ?? true)
                                <td>
                                    <span class="badge bg-light text-dark border border-secondary-subtle">{{ $item->unit?->name ?? '-' }}</span>
                                </td>
                            @endif
                            @if ($product_visible_column['brand'] ?? true)
                                <td>
                                    <span class="badge bg-info bg-opacity-10 text-info border border-info-subtle">{{ $item->brand?->name ?? '-' }}</span>
                                </td>
                            @endif
                            @if ($product_visible_column['size'] ?? true)
                                <td>
                                    <span class="badge bg-light text-dark border border-secondary-subtle">{{ $item->size}}</span>
                                </td>
                            @endif
                            @if ($product_visible_column['code'] ?? true)
                                <td>
                                    <code class="bg-light rounded px-2 py-1 small">{{ $item->code }}</code>
                                </td>
                            @endif
                            @if ($product_visible_column['product_name'] ?? true)
                                <td>
                                    <a href="{{ route('product::edit', $item->id) }}" class="text-decoration-none fw-semibold link-primary d-block">
                                        {{ $item->name }}
                                    </a>
                                </td>
                            @endif
                            @if ($product_visible_column['name_arabic'] ?? true)
                                <td dir="rtl">
                                    <span class="text-secondary">{{ $item->name_arabic }}</span>
                                </td>
                            @endif
                            @if ($product_visible_column['barcode'] ?? true)
                                <td>
                                    @if ($item->barcode)
                                        <code class="bg-light rounded px-2 py-1 small">{{ $item->barcode }}</code>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            @endif
                            @if ($product_visible_column['cost'] ?? true)
                                <td class="text-end fw-semibold">
                                    <span class="text-success">{{ currency($item->cost) }}</span>
                                </td>
                            @endif
                            @if ($product_visible_column['mrp'] ?? true)
                                <td class="text-end fw-bold">
                                    <span class="text-dark">{{ currency($item->mrp) }}</span>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count(array_filter($product_visible_column ?? [])) + 1 }}" class="text-center py-5">
                                <div class="text-muted my-4">
                                    <i class="demo-psi-exclamation-circle fs-1 d-block mb-3 text-secondary-emphasis opacity-50"></i>
                                    <h5 class="fw-semibold mb-2">No Products Found</h5>
                                    <p class="mb-0">Try adjusting your search or filter criteria</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-3 border-top bg-light">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-2 mb-lg-0">
                    <p class="text-muted small mb-0">
                        Showing {{ $data->firstItem() ?? 0 }} to {{ $data->lastItem() ?? 0 }} of {{ $data->total() }} entries
                    </p>
                </div>
                <div class="col-lg-6 d-flex justify-content-lg-end">
                    {{ $data->links() }}
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Floating Action Button -->
    <div class="d-block d-md-none position-fixed bottom-0 end-0 mb-4 me-4" style="z-index: 1050;">
        <div class="dropup">
            <button class="btn btn-primary rounded-circle shadow-lg p-3" type="button" data-bs-toggle="dropdown" aria-expanded="false" id="mobileActionButton">
                <i class="demo-psi-gear fs-4"></i>
            </button>
            <ul class="dropdown-menu shadow-lg" aria-labelledby="mobileActionButton">
                @can('product.create')
                    <li>
                        <a class="dropdown-item d-flex align-items-center" href="{{ route('product::create') }}">
                            <i class="demo-psi-add me-2 text-success"></i>
                            Add New Product
                        </a>
                    </li>
                @endcan
                @can('product.export')
                    <li>
                        <a class="dropdown-item d-flex align-items-center" href="#" wire:click="export()">
                            <i class="demo-pli-file-excel me-2 text-success"></i>
                            Export Products
                        </a>
                    </li>
                @endcan
                @can('product.import')
                    <li>
                        <a class="dropdown-item d-flex align-items-center" href="#" data-bs-toggle="modal" data-bs-target="#ProductImportModal">
                            <i class="demo-pli-download-from-cloud me-2 text-info"></i>
                            Import Products
                        </a>
                    </li>
                @endcan
                @can('product.delete')
                    <li>
                        <a class="dropdown-item d-flex align-items-center" href="#" wire:click="delete()" wire:confirm="Are you sure you want to delete the selected items?">
                            <i class="demo-pli-recycling me-2 text-danger"></i>
                            Delete Selected
                        </a>
                    </li>
                @endcan
                <li>
                    <hr class="dropdown-divider">
                </li>
                <li>
                    <a class="dropdown-item d-flex align-items-center" href="#" onclick="document.getElementById('search').focus()">
                        <i class="demo-psi-magnifi-glass me-2 text-primary"></i>
                        Search Products
                    </a>
                </li>
            </ul>
        </div>
    </div>

    @push('scripts')
        <script>
            $(document).ready(function() {
                $('#unit_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('unit_id', value);
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
                $('#brand_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('brand_id', value);
                });
                $('#status').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('status', value);
                });
            });
        </script>
    @endpush
</div>
