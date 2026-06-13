<div>
    <div class="card shadow-sm">
        {{-- ===== Toolbar ===== --}}
        <div class="card-header bg-light py-3">
            <div class="d-flex flex-column flex-lg-row gap-3 justify-content-between align-items-stretch align-items-lg-center">
                {{-- Actions --}}
                <div class="d-flex gap-2 align-items-center">
                    @can('rent out checklist item.create')
                        <button class="btn btn-primary btn-sm d-inline-flex align-items-center shadow-sm" id="ChecklistItemAdd">
                            <i class="fa fa-plus-circle me-2"></i>Add New Item
                        </button>
                    @endcan
                    @can('rent out checklist item.delete')
                        <button class="btn btn-danger btn-sm d-inline-flex align-items-center shadow-sm" title="Delete Selected"
                            data-bs-toggle="tooltip" wire:click="delete()"
                            wire:confirm="Are you sure you want to delete the selected items?">
                            <i class="fa fa-trash me-md-1"></i><span class="d-none d-sm-inline">Delete</span>
                        </button>
                    @endcan
                </div>

                {{-- Filters --}}
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <div wire:ignore class="flex-grow-1" style="min-width:140px; max-width:220px;">
                        <select class="tomSelect" id="filterCategoryTom">
                            <option value="">All Categories</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category }}" @selected($filterCategory === $category)>{{ $category }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div wire:ignore class="flex-grow-1" style="min-width:140px; max-width:220px;">
                        <select class="select-property_type_id" id="filterPropertyTypeTom">
                            <option value="">All Property Types</option>
                            <option value="none" @selected($filterPropertyType === 'none')>— Universal (no type) —</option>
                        </select>
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        <label class="form-label mb-0 text-muted small fw-semibold">Show:</label>
                        <select wire:model.live="limit" class="form-select form-select-sm border-secondary-subtle shadow-sm" style="width:auto;">
                            <option value="10">10</option>
                            <option value="100">100</option>
                            <option value="500">500</option>
                        </select>
                    </div>
                    <div class="input-group input-group-sm flex-grow-1" style="min-width:160px;">
                        <span class="input-group-text bg-white border-secondary-subtle"><i class="fa fa-search"></i></span>
                        <input type="text" wire:model.live="search" autofocus placeholder="Search checklist items..."
                            class="form-control form-control-sm border-secondary-subtle shadow-sm" autocomplete="off">
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle border-bottom mb-0 table-sm">
                    <thead class="bg-light text-muted">
                        <tr class="text-capitalize small">
                            <th class="fw-semibold py-2">
                                <div class="form-check ms-1">
                                    <input type="checkbox" wire:model.live="selectAll" class="form-check-input shadow-sm" id="selectAllCheckbox" />
                                    <label class="form-check-label" for="selectAllCheckbox">
                                        <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="id" label="ID" />
                                    </label>
                                </div>
                            </th>
                            <th class="fw-semibold"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="category" label="Category" /> </th>
                            <th class="fw-semibold"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="name" label="Item Name" /> </th>
                            <th class="fw-semibold">Property Type</th>
                            <th class="fw-semibold text-center">Image</th>
                            <th class="fw-semibold text-center"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="sort_order" label="Sort" /> </th>
                            <th class="fw-semibold text-center">Active</th>
                            <th class="fw-semibold text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $renderedCategory = '__start__'; @endphp
                        @forelse ($data as $item)
                            @php $itemCategory = $item->category ?: 'Uncategorized'; @endphp
                            @if ($itemCategory !== $renderedCategory)
                                <tr class="table-light">
                                    <td colspan="8" class="fw-semibold text-uppercase small text-secondary-emphasis py-2">
                                        <i class="fa fa-folder-open-o me-2 text-info"></i>{{ $itemCategory }}
                                    </td>
                                </tr>
                                @php $renderedCategory = $itemCategory; @endphp
                            @endif
                            <tr>
                                <td>
                                    <div class="form-check ms-1">
                                        <input type="checkbox" value="{{ $item->id }}" wire:model.live="selected" class="form-check-input shadow-sm" id="checkbox{{ $item->id }}" />
                                        <label class="form-check-label" for="checkbox{{ $item->id }}">{{ $item->id }}</label>
                                    </div>
                                </td>
                                <td>
                                    @if ($item->category)
                                        <span class="badge bg-info-subtle text-info-emphasis">{{ $item->category }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    <span class="fw-medium text-dark">
                                        <i class="fa fa-check-square-o me-1 text-primary opacity-75"></i>{{ $item->name }}
                                    </span>
                                </td>
                                <td>
                                    @if ($item->propertyType)
                                        <span class="badge bg-primary-subtle text-primary-emphasis">{{ $item->propertyType->name }}</span>
                                    @else
                                        <span class="badge bg-light text-muted border">All types</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if ($item->image_path)
                                        <img src="{{ asset('storage/' . $item->image_path) }}" alt="{{ $item->name }}"
                                            class="zoomable" data-img="{{ asset('storage/' . $item->image_path) }}"
                                            style="width:38px; height:38px; object-fit:cover; border-radius:6px; border:1px solid #e3e6ea; cursor:zoom-in;"
                                            title="Click to enlarge">
                                    @else
                                        <i class="fa fa-picture-o text-muted opacity-50"></i>
                                    @endif
                                </td>
                                <td class="text-center">{{ $item->sort_order }}</td>
                                <td class="text-center">
                                    @if ($item->is_active)
                                        <span class="badge bg-success-subtle text-success-emphasis">Active</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary-emphasis">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        @can('rent out checklist item.edit')
                                            <button table_id="{{ $item->id }}" class="btn btn-light btn-sm edit" title="Edit" data-bs-toggle="tooltip">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="fa fa-check-square-o fa-3x mb-3 d-block opacity-25"></i>
                                    No checklist items found matching your search.
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

        <div class="position-fixed bottom-0 end-0 mb-4 me-4 d-md-none">
            <button id="ChecklistItemAddMobile" class="btn btn-primary rounded-circle shadow btn-lg">
                <i class="fa fa-plus"></i>
            </button>
        </div>

        @push('scripts')
            @include('components.select.propertyTypeSelect')
            <script>
                $(document).ready(function() {
                    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                    tooltipTriggerList.map(function(tooltipTriggerEl) {
                        return new bootstrap.Tooltip(tooltipTriggerEl, { boundary: document.body });
                    });

                    $(document).on('click', '.edit', function() {
                        Livewire.dispatch("ChecklistItem-Page-Update-Component", {
                            id: $(this).attr('table_id')
                        });
                    });
                    $('#ChecklistItemAdd, #ChecklistItemAddMobile').click(function() {
                        Livewire.dispatch("ChecklistItem-Page-Create-Component");
                    });
                    window.addEventListener('RefreshChecklistItemTable', event => {
                        Livewire.dispatch("ChecklistItem-Refresh-Component");
                    });

                    // The two filter dropdowns are TomSelect inside wire:ignore, so bridge
                    // their change events back into Livewire (keeps live filtering working).
                    $(document).on('change', '#filterCategoryTom', function () {
                        @this.set('filterCategory', this.value);
                    });
                    $(document).on('change', '#filterPropertyTypeTom', function () {
                        @this.set('filterPropertyType', this.value);
                    });
                });
            </script>
        @endpush
    </div>
</div>
