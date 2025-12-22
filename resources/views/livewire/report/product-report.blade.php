<div>
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h5 class="mb-0">Product Report</h5>
                    <small class="text-muted">Analyze your product inventory and movements</small>
                </div>
                <div class="btn-group">
                    @can('product.export')
                        <button class="btn btn-success btn-sm d-flex align-items-center" title="Export to Excel" data-bs-toggle="tooltip" wire:click="export()">
                            <i class="demo-pli-file-excel me-md-1 fs-5"></i>
                            <span class="d-none d-md-inline">Export</span>
                        </button>
                    @endcan
                </div>
            </div>

            <div class="row g-3 align-items-end mb-3">
                <div class="col-md-2">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="fa fa-barcode text-primary"></i>
                        </span>
                        <input type="text" wire:model.live="barcode" class="form-control border-start-0 ps-0" placeholder="Scan barcode...">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="fa fa-search text-primary"></i>
                        </span>
                        <input type="text" wire:model.live="search" class="form-control border-start-0 ps-0" placeholder="Search products...">
                    </div>
                </div>
                <div class="col-md-2">
                    <select wire:model.live="limit" class="form-select">
                        <option value="10">10 rows</option>
                        <option value="25">25 rows</option>
                        <option value="50">50 rows</option>
                        <option value="100">100 rows</option>
                    </select>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-2">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="fa fa-calendar text-primary"></i>
                        </span>
                        {{ html()->date('from_date')->value('')->class('form-control border-start-0 ps-0')->id('from_date')->attribute('wire:model.live', 'from_date') }}
                    </div>
                    <label class="form-label small text-muted mt-1">From Date</label>
                </div>
                <div class="col-md-2">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="fa fa-calendar text-primary"></i>
                        </span>
                        {{ html()->date('to_date')->value('')->class('form-control border-start-0 ps-0')->id('to_date')->attribute('wire:model.live', 'to_date') }}
                    </div>
                    <label class="form-label small text-muted mt-1">To Date</label>
                </div>
                <div class="col-md-3" wire:ignore>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="fa fa-building text-primary"></i>
                        </span>
                        {{ html()->select('branch_id', [auth()->user()->default_branch_id => auth()->user()->branch?->name])->value(auth()->user()->default_branch_id)->class('select-assigned-branch_id-list border-start-0 ps-0')->id('branch_id')->attribute('style', 'width:80%')->placeholder('All Branches') }}
                    </div>
                    <label class="form-label small text-muted mt-1">Branch</label>
                </div>
                <div class="col-md-5" wire:ignore>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="fa fa-tags text-primary"></i>
                        </span>
                        {{ html()->select('main_category_id', [])->value('')->class('select-category_id-list border-start-0 ps-0')->id('main_category_id')->attribute('style', 'width:80%')->placeholder('All Categories') }}
                    </div>
                    <label class="form-label small text-muted mt-1">Category</label>
                </div>
            </div>
        </div>

        <div class="card-body px-0 pb-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr class="text-capitalize">
                            <th width="40%" class="border-bottom"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="products.name" label="Product" /> </th>
                            <th class="border-bottom"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="products.code" label="Code" /> </th>
                            <th class="border-bottom"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="products.barcode" label="Barcode" /> </th>
                            <th class="border-bottom"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="category_name" label="Category" /> </th>
                            <th class="text-end border-bottom"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="current_stock" label="Current Stock" /> </th>
                            <th class="text-end border-bottom"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="total_sold" label="Total Sold" /> </th>
                            <th class="text-end border-bottom"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="total_purchased" label="Total Purchased" /> </th>
                            <th class="text-end border-bottom"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="transfer_in" label="Transfer In" /> </th>
                            <th class="text-end border-bottom"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="transfer_out" label="Transfer Out" /> </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($products as $product)
                            <tr>
                                <td>
                                    <a href="{{ route('inventory::product::view', $product->id) }}" class="text-decoration-none">
                                        {{ $product->name }}
                                    </a>
                                </td>
                                <td><code class="small">{{ $product->code }}</code></td>
                                <td><code class="small">{{ $product->barcode }}</code></td>
                                <td><span class="badge bg-light text-dark">{{ $product->category_name }}</span></td>
                                <td class="text-end fw-bold {{ $product->current_stock > 0 ? 'text-success' : 'text-danger' }}">
                                    {{ number_format($product->current_stock ?? 0) }}
                                </td>
                                <td class="text-end">{{ number_format($product->total_sold ?? 0) }}</td>
                                <td class="text-end">{{ number_format($product->total_purchased ?? 0) }}</td>
                                <td class="text-end text-success">{{ number_format($product->transfer_in ?? 0) }}</td>
                                <td class="text-end text-danger">{{ number_format($product->transfer_out ?? 0) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-3">
                {{ $products->links() }}
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
            });
        </script>
    @endpush
</div>
