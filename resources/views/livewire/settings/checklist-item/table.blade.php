<div>
    <style>
        /* Mobile / tablet card list (shown < lg) */
        .cl-mlist { padding: .75rem; }
        .cl-mcat { display: flex; align-items: center; gap: .5rem; padding: .45rem .7rem; background: #eef2f8;
            border-left: 3px solid var(--bs-primary, #0d6efd); border-radius: 8px; margin: .85rem 0 .55rem;
            font-weight: 700; font-size: .72rem; letter-spacing: .4px; text-transform: uppercase; color: #3a4250; }
        .cl-mcat:first-child { margin-top: 0; }
        .cl-mcard { display: flex; align-items: flex-start; gap: .65rem; padding: .7rem .75rem; background: #fff;
            border: 1px solid #eceef1; border-radius: 10px; margin-bottom: .55rem; transition: box-shadow .12s, border-color .12s; }
        .cl-mcard.is-sel { border-color: var(--bs-primary, #0d6efd); box-shadow: inset 3px 0 0 var(--bs-primary, #0d6efd); }
        .cl-mthumb { width: 48px; height: 48px; border-radius: 8px; object-fit: cover; border: 1px solid #e3e6ea; flex: 0 0 auto; }
        .cl-mthumb-empty { display: inline-flex; align-items: center; justify-content: center; background: #f6f8fb; color: #cbd1d8; font-size: 18px; }
        .cl-mname { font-weight: 600; color: #26303a; font-size: .92rem; line-height: 1.25; word-break: break-word; }
        .cl-mmeta { font-size: .7rem; color: #8a929b; margin-top: .2rem; }
        .cl-mbadges { margin-top: .3rem; display: flex; flex-wrap: wrap; gap: .25rem; }
        @media (max-width: 575.98px) {
            .cl-toolbar-actions .btn-label { display: none; }
        }
    </style>

    <div class="card shadow-sm">
        {{-- ===== Toolbar ===== --}}
        <div class="card-header bg-light py-3">
            <div class="d-flex flex-column flex-lg-row gap-3 justify-content-between align-items-stretch align-items-lg-center">
                {{-- Actions --}}
                <div class="d-flex gap-2 align-items-center cl-toolbar-actions">
                    @can('rent out checklist item.create')
                        <button class="btn btn-primary btn-sm d-inline-flex align-items-center shadow-sm" id="ChecklistItemAdd">
                            <i class="fa fa-plus-circle me-md-2"></i><span class="btn-label">Add New Item</span>
                        </button>
                    @endcan
                    @can('rent out checklist item.delete')
                        <button class="btn btn-danger btn-sm d-inline-flex align-items-center shadow-sm" title="Delete Selected"
                            data-bs-toggle="tooltip" wire:click="delete()"
                            wire:confirm="Are you sure you want to delete the selected items?">
                            <i class="fa fa-trash me-md-1"></i><span class="btn-label">Delete</span>
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
            {{-- ===== Desktop table (≥ lg) ===== --}}
            <div class="table-responsive d-none d-lg-block">
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

            {{-- ===== Mobile / tablet card list (< lg) ===== --}}
            <div class="cl-mlist d-lg-none">
                {{-- Bulk select bar --}}
                @if (count($data))
                    <div class="d-flex align-items-center justify-content-between mb-2 px-1">
                        <div class="form-check mb-0">
                            <input type="checkbox" wire:model.live="selectAll" class="form-check-input shadow-sm" id="selectAllMobile">
                            <label class="form-check-label small text-muted" for="selectAllMobile">Select all</label>
                        </div>
                        @if (count($selected))
                            <span class="badge bg-primary-subtle text-primary-emphasis">{{ count($selected) }} selected</span>
                        @endif
                    </div>
                @endif

                @php $renderedCategoryM = '__start__'; @endphp
                @forelse ($data as $item)
                    @php
                        $itemCategoryM = $item->category ?: 'Uncategorized';
                        $isSel = in_array((string) $item->id, array_map('strval', (array) $selected), true);
                    @endphp
                    @if ($itemCategoryM !== $renderedCategoryM)
                        <div class="cl-mcat"><i class="fa fa-folder-open-o text-primary opacity-75"></i>{{ $itemCategoryM }}</div>
                        @php $renderedCategoryM = $itemCategoryM; @endphp
                    @endif
                    <div class="cl-mcard {{ $isSel ? 'is-sel' : '' }}">
                        <div class="form-check mt-1">
                            <input type="checkbox" value="{{ $item->id }}" wire:model.live="selected" class="form-check-input shadow-sm" id="mcheckbox{{ $item->id }}" />
                        </div>
                        @if ($item->image_path)
                            <img src="{{ asset('storage/' . $item->image_path) }}" alt="{{ $item->name }}"
                                class="cl-mthumb zoomable" data-img="{{ asset('storage/' . $item->image_path) }}"
                                style="cursor:zoom-in;" title="Click to enlarge">
                        @else
                            <span class="cl-mthumb cl-mthumb-empty"><i class="fa fa-picture-o"></i></span>
                        @endif
                        <div class="flex-grow-1" style="min-width:0;">
                            <div class="cl-mname">{{ $item->name }}</div>
                            <div class="cl-mbadges">
                                @if ($item->propertyType)
                                    <span class="badge bg-primary-subtle text-primary-emphasis">{{ $item->propertyType->name }}</span>
                                @else
                                    <span class="badge bg-light text-muted border">All types</span>
                                @endif
                                @if ($item->is_active)
                                    <span class="badge bg-success-subtle text-success-emphasis">Active</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary-emphasis">Inactive</span>
                                @endif
                            </div>
                            <div class="cl-mmeta">#{{ $item->id }} &middot; Sort {{ $item->sort_order }}</div>
                        </div>
                        @can('rent out checklist item.edit')
                            <button table_id="{{ $item->id }}" class="btn btn-light btn-sm edit flex-shrink-0" title="Edit">
                                <i class="fa fa-eye"></i>
                            </button>
                        @endcan
                    </div>
                @empty
                    <div class="text-center py-5 text-muted">
                        <i class="fa fa-check-square-o fa-3x mb-3 d-block opacity-25"></i>
                        No checklist items found matching your search.
                    </div>
                @endforelse
            </div>

            <div class="p-3 border-top">
                {{ $data->links() }}
            </div>
        </div>

        <div class="position-fixed bottom-0 end-0 mb-4 me-4 d-lg-none">
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
