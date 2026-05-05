<div>
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-semibold">Total Assets</div>
                    <div class="fs-3 fw-bold text-dark">{{ number_format($stats['total_assets']) }}</div>
                    <div class="small text-muted">All saved asset records</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-semibold">Active Assets</div>
                    <div class="fs-3 fw-bold text-success">{{ number_format($stats['active_assets']) }}</div>
                    <div class="small text-muted">Currently usable assets</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-semibold">Purchase Cost</div>
                    <div class="fs-5 fw-bold text-primary">{{ currency($stats['total_purchase_cost']) }}</div>
                    <div class="small text-muted">Total capitalized cost</div>
                    <div class="small text-secondary mt-1">Saved depreciation: {{ currency($stats['total_depreciation']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-semibold">Estimated Book Value</div>
                    <div class="fs-5 fw-bold text-dark">{{ currency($stats['estimated_book_value']) }}</div>
                    <div class="small text-muted">Purchase cost minus saved depreciation</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-light py-3">
            <div class="row mt-3">
                <div class="col-md-6 d-flex flex-wrap gap-2 align-items-center mb-3 mb-md-0">
                    @can('asset.create')
                        <a class="btn btn-primary d-flex align-items-center shadow-sm" href="{{ route('asset::create') }}">
                            <i class="fa fa-plus me-2"></i>
                            Add Asset
                        </a>
                    @endcan
                    <div class="btn-group shadow-sm">
                        @can('asset.delete')
                            <button class="btn btn-danger btn-sm d-flex align-items-center" title="Delete Selected" data-bs-toggle="tooltip" wire:click="delete()"
                                wire:confirm="Are you sure you want to delete the selected items?">
                                <i class="fa fa-trash me-md-1 fs-5"></i>
                                <span class="d-none d-md-inline">Delete</span>
                            </button>
                        @endcan
                        @can('asset.import')
                            <a class="btn btn-info btn-sm d-flex align-items-center text-white shadow-sm" title="Import Assets" href="{{ route('asset::import') }}">
                                <i class="fa fa-cloud-download me-md-1 fs-5"></i>
                                <span class="d-none d-md-inline">Import</span>
                            </a>
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
                                    <i class="fa fa-search"></i>
                                </span>
                                <input type="text" wire:model.live="search" autofocus placeholder="Search assets..." class="form-control form-control-sm border-secondary-subtle shadow-sm"
                                    autocomplete="off">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <hr class="my-3">
            <div class="row g-3">
                <div class="col-md-3" wire:ignore>
                    <label for="department_id" class="form-label fw-medium">Department</label>
                    {{ html()->select('department_id', [])->value('')->class('select-department_id-list border-secondary-subtle shadow-sm')->id('department_id')->placeholder('All Departments') }}
                </div>
                <div class="col-md-3" wire:ignore>
                    <label for="main_category_id" class="form-label fw-medium">Asset Group</label>
                    {{ html()->select('main_category_id', [])->value('')->class('select-category_id-list border-secondary-subtle shadow-sm')->id('main_category_id')->placeholder('All Asset Groups') }}
                </div>
                <div class="col-md-3" wire:ignore>
                    <label for="brand_id" class="form-label fw-medium">Brand</label>
                    {{ html()->select('brand_id', [])->value('')->class('select-brand_id-list border-secondary-subtle shadow-sm')->id('brand_id')->placeholder('All Brands') }}
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label fw-medium">Status</label>
                    {{ html()->select('status', activeOrDisabled())->value('')->class('form-select border-secondary-subtle shadow-sm')->placeholder('All Status')->id('status')->attribute('wire:model.live', 'status') }}
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle border-bottom mb-0">
                    <thead class="bg-light text-muted">
                        <tr class="text-capitalize small">
                            <th class="fw-semibold py-2">
                                <div class="form-check ms-1">
                                    <input type="checkbox" wire:model.live="selectAll" class="form-check-input shadow-sm" id="selectAllFixedAssets" />
                                    <label class="form-check-label" for="selectAllFixedAssets">
                                        <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="id" label="#" />
                                    </label>
                                </div>
                            </th>
                            <th class="fw-semibold"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="name" label="Asset" /></th>
                            <th class="fw-semibold"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="main_category_id" label="Asset Group" /></th>
                            <th class="fw-semibold"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="brand_id" label="Brand" /></th>
                            <th class="fw-semibold"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="item_no" label="Item No" /></th>
                            <th class="fw-semibold"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="location" label="Location" /></th>
                            <th class="fw-semibold">Status</th>
                            <th class="fw-semibold text-end"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="cost" label="Purchase Cost" /></th>
                            <th class="fw-semibold text-end"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="depreciation_amount" label="Depreciation" /></th>
                            <th class="fw-semibold text-end">Book Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data as $item)
                            <tr>
                                <td>
                                    <div class="form-check ms-1">
                                        <input type="checkbox" value="{{ $item->id }}" wire:model.live="selected" class="form-check-input shadow-sm" id="fixedAsset{{ $item->id }}" />
                                        <label class="form-check-label" for="fixedAsset{{ $item->id }}">{{ $item->id }}</label>
                                    </div>
                                </td>
                                <td>
                                    <a href="{{ route('asset::edit', $item->id) }}" class="text-decoration-none fw-medium text-primary">
                                        <i class="fa fa-building-o me-1"></i>{{ $item->name }}
                                    </a>
                                    <div class="small text-muted">{{ $item->code }}</div>
                                </td>
                                <td>{{ $item->mainCategory?->name ?: '-' }}</td>
                                <td>{{ $item->brand?->name ?: '-' }}</td>
                                <td>{{ $item->item_no ?: '-' }}</td>
                                <td>{{ $item->location ?: '-' }}</td>
                                <td>
                                    <span class="badge {{ $item->status === 'active' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                                        {{ ucfirst($item->status ?: 'inactive') }}
                                    </span>
                                </td>
                                <td class="text-end fw-medium">{{ currency($item->cost) }}</td>
                                <td class="text-end">{{ currency($item->depreciation_amount) }}</td>
                                <td class="text-end fw-semibold">{{ currency(max(($item->cost ?? 0) - ($item->depreciation_amount ?? 0), 0)) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center gap-2">
                                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                                            <i class="fa fa-building-o fs-2 text-muted"></i>
                                        </div>
                                        <div class="fw-semibold fs-5">No assets found</div>
                                        <div class="text-muted">Try changing the filters, or create your first asset record.</div>
                                        @can('asset.create')
                                            <a href="{{ route('asset::create') }}" class="btn btn-primary btn-sm mt-2">Create Asset</a>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-3 border-top">
                {{ $data->links() }}
            </div>
        </div>

        @push('scripts')
            <script>
                $(document).ready(function() {
                    $('#department_id').on('change', function() {
                        @this.set('department_id', $(this).val() || null);
                    });
                    $('#main_category_id').on('change', function() {
                        @this.set('main_category_id', $(this).val() || null);
                    });
                    $('#brand_id').on('change', function() {
                        @this.set('brand_id', $(this).val() || null);
                    });
                });
            </script>
        @endpush
    </div>
</div>
