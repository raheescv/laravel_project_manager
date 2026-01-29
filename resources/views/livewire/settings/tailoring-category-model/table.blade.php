<div class="card border-0 shadow-sm" id="category-models-card">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 d-flex align-items-center gap-2">
            <i class="fa fa-cube fs-5 text-primary"></i>
            Category Models
        </h5>
    </div>
    <div class="card-header bg-white py-3 border-top">
        <div class="row g-3 align-items-center">
            <div class="col-12 col-md-6 d-flex gap-2 flex-wrap">
                @can('tailoring category.create')
                    <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2" id="TailoringCategoryModelAdd" wire:click="$dispatch('TailoringCategoryModel-Page-Create-Component', { tailoring_category_id: @js($categoryId) })">
                        <i class="fa fa-plus fs-5"></i>
                        <span>Add Model</span>
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
                    <div class="d-flex bg-light rounded-2 px-2 flex-grow-1 flex-md-grow-0" style="min-width: 200px;">
                        <span class="d-flex align-items-center text-muted">
                            <i class="fa fa-search fs-6"></i>
                        </span>
                        <input type="text" wire:model.live="search" placeholder="Search models..." class="form-control bg-transparent border-0 py-2" style="box-shadow: none; font-size: 0.875rem;" autocomplete="off">
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        @if(!$categoryId)
            <div class="text-center py-5 text-muted">
                <i class="fa fa-cube fs-1 opacity-50"></i>
                <p class="mb-0 mt-2">Select a category above to view and manage its models.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4" width="80">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" wire:model.live="selectAll" id="selectAllModels">
                                </div>
                            </th>
                            <th width="10%"> <x-sortable-header field="id" label="ID" :sortField="$sortField" :direction="$sortDirection" /> </th>
                            <th> <x-sortable-header field="name" label="Name" :sortField="$sortField" :direction="$sortDirection" /> </th>
                            <th>Description</th>
                            <th width="10%">Category</th>
                            <th width="10%">Status</th>
                            <th width="10%" class="text-end pe-4">Action</th>
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
                                <td class="fw-semibold text-dark">{{ $item->name }}</td>
                                <td class="text-muted small">{{ Str::limit($item->description, 40) }}</td>
                                <td><span class="badge bg-info-subtle text-info">{{ $item->category?->name }}</span></td>
                                <td>
                                    @if($item->is_active)
                                        <span class="badge bg-success-subtle text-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    @can('tailoring category.edit')
                                        <button type="button" table_id="{{ $item->id }}" class="btn btn-icon btn-sm btn-hover btn-light edit-model" title="Edit Model">
                                            <i class="fa fa-pencil fs-5 text-muted"></i>
                                        </button>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">No models found for this category.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>
    @if($categoryId && $data->hasPages())
        <div class="card-footer bg-white py-3">
            {{ $data->links() }}
        </div>
    @endif

    @push('scripts')
        <script>
            $(document).ready(function() {
                $(document).on('click', '.edit-model', function() {
                    Livewire.dispatch("TailoringCategoryModel-Page-Update-Component", {
                        id: $(this).attr('table_id')
                    });
                });
                window.addEventListener('RefreshTailoringCategoryModelTable', event => {
                    Livewire.dispatch("RefreshTailoringCategoryModelTable");
                });
            });
        </script>
    @endpush
</div>
