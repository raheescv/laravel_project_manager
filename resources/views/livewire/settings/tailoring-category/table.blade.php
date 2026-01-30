<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3">
        <div class="row g-3 align-items-center">
            <div class="col-12 col-md-6 d-flex gap-2">
                @can('tailoring category.create')
                    <button class="btn btn-primary d-inline-flex align-items-center gap-2" id="TailoringCategoryAdd">
                        <i class="fa fa-plus fs-5"></i>
                        <span>Add New</span>
                    </button>
                @endcan
                @can('tailoring category.delete')
                    @if(count($selected) > 0)
                        <button class="btn btn-outline-danger d-inline-flex align-items-center gap-2" wire:click="delete()" wire:confirm="Are you sure you want to delete the selected items?">
                            <i class="fa fa-trash fs-5"></i>
                            <span>Delete</span>
                        </button>
                    @endif
                @endcan
            </div>
            <div class="col-12 col-md-6">
                <div class="d-flex flex-wrap gap-2 justify-content-md-end">
                    <div class="d-flex bg-light rounded-2 px-2">
                        <span class="d-flex align-items-center text-muted">
                            <i class="fa fa-list fs-6"></i>
                        </span>
                        <select wire:model.live="limit" class="form-select bg-transparent border-0 fw-semibold py-2" style="width: 80px; box-shadow: none; font-size: 0.875rem;">
                            <option value="10">10</option>
                            <option value="100">100</option>
                            <option value="500">500</option>
                        </select>
                    </div>
                    <div class="d-flex bg-light rounded-2 px-2 flex-grow-1 flex-md-grow-0" style="min-width: 250px;">
                        <span class="d-flex align-items-center text-muted">
                            <i class="fa fa-search fs-6"></i>
                        </span>
                        <input type="text" wire:model.live="search" autofocus placeholder="Search tailoring categories..." class="form-control bg-transparent border-0 py-2" style="box-shadow: none; font-size: 0.875rem;" autocomplete="off">
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4" width="80">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" wire:model.live="selectAll" id="selectAll">
                            </div>
                        </th>
                        <th width="10%"> <x-sortable-header field="id" label="ID" :sortField="$sortField" :direction="$sortDirection" /> </th>
                        <th width="15%"> <x-sortable-header field="order" label="Order" :sortField="$sortField" :direction="$sortDirection" /> </th>
                        <th> <x-sortable-header field="name" label="Name" :sortField="$sortField" :direction="$sortDirection" /> </th>
                        <th>Description</th>
                        <th width="10%">Status</th>
                        <th width="20%" class="text-end pe-4">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data as $item)
                        <tr>
                            <td class="ps-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="{{ $item->id }}" wire:model.live="selected">
                                </div>
                            </td>
                            <td><span class="badge bg-light text-dark border">#{{ $item->id }}</span></td>
                            <td><span class="badge bg-info">{{ $item->order ?? 0 }}</span></td>
                            <td class="fw-semibold text-dark">{{ $item->name }}</td>
                            <td class="text-muted small">{{ Str::limit($item->description, 40) }}</td>
                            <td>
                                @if($item->is_active)
                                    <span class="badge bg-success-subtle text-success">Active</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                @can('tailoring category.view')
                                    <button type="button" class="btn btn-icon btn-sm btn-hover btn-light manage-models" data-category-id="{{ $item->id }}" title="Manage Models">
                                        <i class="fa fa-cube fs-5 text-info"></i>
                                    </button>
                                    <button type="button" class="btn btn-icon btn-sm btn-hover btn-light manage-measurements" data-category-id="{{ $item->id }}" title="Manage Measurements">
                                        <i class="fa fa-tasks fs-5 text-warning"></i>
                                    </button>
                                @endcan
                                @can('tailoring category.edit')
                                    <button table_id="{{ $item->id }}" class="btn btn-icon btn-sm btn-hover btn-light edit" title="Edit Tailoring Category">
                                        <i class="fa fa-pencil fs-5 text-muted"></i>
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">No tailoring categories found matching your search.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($data->hasPages())
        <div class="card-footer bg-white py-3">
            {{ $data->links() }}
        </div>
    @endif

    @push('scripts')
        <script>
            $(document).ready(function() {
                $(document).on('click', '.edit', function() {
                    Livewire.dispatch("TailoringCategory-Page-Update-Component", {
                        id: $(this).attr('table_id')
                    });
                });
                $('#TailoringCategoryAdd').click(function() {
                    Livewire.dispatch("TailoringCategory-Page-Create-Component");
                });
                window.addEventListener('RefreshTailoringCategoryTable', event => {
                    Livewire.dispatch("RefreshTailoringCategoryTable");
                });
                $(document).on('click', '.manage-models', function() {
                    var categoryId = $(this).data('category-id');
                    Livewire.dispatch('SelectCategoryForModels', { id: categoryId });
                    
                    // Switch to models tab
                    var tabEl = document.querySelector('#models-tab');
                    if (tabEl) {
                        var tab = new bootstrap.Tab(tabEl);
                        tab.show();
                    }
                    
                    document.getElementById('tailoring-settings-tabs-card')?.scrollIntoView({ behavior: 'smooth' });
                });
                $(document).on('click', '.manage-measurements', function() {
                    var categoryId = $(this).data('category-id');
                    Livewire.dispatch('SelectCategoryForMeasurements', { categoryId: categoryId });
                    
                    // Switch to measurements tab
                    var tabEl = document.querySelector('#measurements-tab');
                    if (tabEl) {
                        var tab = new bootstrap.Tab(tabEl);
                        tab.show();
                    }
                    
                    document.getElementById('tailoring-settings-tabs-card')?.scrollIntoView({ behavior: 'smooth' });
                });
            });
        </script>
    @endpush
</div>
