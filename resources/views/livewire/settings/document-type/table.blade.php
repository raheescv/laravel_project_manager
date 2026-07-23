<div>
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-transparent border-0 px-4 pt-4 pb-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="flex-shrink-0 d-inline-flex align-items-center justify-content-center rounded-3 bg-info-subtle text-info"
                        style="width: 2.75rem; height: 2.75rem;">
                        <i class="fa fa-list-ul fa-lg"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-bold">All Document Types</h6>
                        <small class="text-muted">Create, edit and organise your document classifications.</small>
                    </div>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    @can('document type.create')
                        <button class="btn btn-primary btn-sm rounded-pill d-inline-flex align-items-center gap-1 px-3 shadow-sm"
                            id="DocumentTypeAdd">
                            <i class="fa fa-plus-circle"></i> Add New Type
                        </button>
                    @endcan
                    @can('document type.delete')
                        <button class="btn btn-outline-danger btn-sm rounded-pill d-inline-flex align-items-center gap-1 px-3"
                            title="Delete Selected" data-bs-toggle="tooltip" wire:click="delete()"
                            wire:confirm="Are you sure you want to delete the selected items?" @disabled(!count($selected))>
                            <i class="fa fa-trash"></i>
                            <span class="d-none d-md-inline">Delete</span>
                            @if (count($selected))
                                <span class="badge rounded-pill text-bg-danger">{{ count($selected) }}</span>
                            @endif
                        </button>
                    @endcan
                </div>
            </div>

            {{-- Toolbar --}}
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3">
                <div class="input-group input-group-sm shadow-sm" style="max-width: 340px;">
                    <span class="input-group-text bg-body border-end-0"><i class="fa fa-search text-muted"></i></span>
                    <input type="text" wire:model.live.debounce.300ms="search" autofocus
                        placeholder="Search document types..." class="form-control border-start-0 ps-0"
                        autocomplete="off">
                </div>
                <div class="d-flex align-items-center gap-2">
                    <label class="form-label mb-0 text-muted small fw-semibold">Show</label>
                    <select wire:model.live="limit" class="form-select form-select-sm shadow-sm" style="width: auto;">
                        <option value="10">10</option>
                        <option value="100">100</option>
                        <option value="500">500</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr class="text-uppercase small text-muted">
                            <th class="fw-semibold py-3 ps-4">
                                <div class="d-flex align-items-center gap-2">
                                    <input type="checkbox" wire:model.live="selectAll" class="form-check-input mt-0"
                                        id="selectAllCheckbox" title="Select all" />
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="id" label="ID" />
                                </div>
                            </th>
                            <th class="fw-semibold py-3">
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="name" label="Name" />
                            </th>
                            <th class="fw-semibold py-3">Arabic Name</th>
                            <th class="fw-semibold py-3 text-center pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        @forelse ($data as $item)
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="checkbox" value="{{ $item->id }}" wire:model.live="selected"
                                            class="form-check-input mt-0" id="checkbox{{ $item->id }}" />
                                        <label class="text-muted small mb-0" for="checkbox{{ $item->id }}">#{{ $item->id }}</label>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="flex-shrink-0 d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary"
                                            style="width: 2rem; height: 2rem;">
                                            <i class="fa fa-file-text-o"></i>
                                        </span>
                                        <span class="fw-semibold">{{ $item->name }}</span>
                                    </div>
                                </td>
                                <td>
                                    @if ($item->arabic_name)
                                        <span class="text-body-secondary" dir="rtl">
                                            <i class="fa fa-language me-1 opacity-75"></i>{{ $item->arabic_name }}
                                        </span>
                                    @else
                                        <span class="text-muted">&mdash;</span>
                                    @endif
                                </td>
                                <td class="text-center pe-4">
                                    @can('document type.edit')
                                        <button table_id="{{ $item->id }}"
                                            class="btn btn-sm btn-outline-primary rounded-pill edit px-3" title="View / Edit"
                                            data-bs-toggle="tooltip">
                                            <i class="fa fa-eye"></i>
                                        </button>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-5">
                                    <i class="fa fa-folder-open-o fa-3x mb-3 d-block text-muted opacity-25"></i>
                                    <p class="text-muted mb-0">No document types found matching your search.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($data->hasPages())
                <div class="px-4 py-3 border-top">
                    {{ $data->links() }}
                </div>
            @endif
        </div>

        <div class="position-fixed bottom-0 end-0 mb-4 me-4 d-md-none" style="z-index: 1030;">
            <button id="DocumentTypeAddMobile" class="btn btn-primary rounded-circle shadow btn-lg">
                <i class="fa fa-plus"></i>
            </button>
        </div>

        @push('scripts')
            <script>
                $(document).ready(function() {
                    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                    tooltipTriggerList.map(function(tooltipTriggerEl) {
                        return new bootstrap.Tooltip(tooltipTriggerEl, {
                            boundary: document.body
                        });
                    });

                    $(document).on('click', '.edit', function() {
                        Livewire.dispatch("DocumentType-Page-Update-Component", {
                            id: $(this).attr('table_id')
                        });
                    });
                    $('#DocumentTypeAdd, #DocumentTypeAddMobile').click(function() {
                        Livewire.dispatch("DocumentType-Page-Create-Component");
                    });
                    window.addEventListener('RefreshDocumentTypeTable', event => {
                        Livewire.dispatch("DocumentType-Refresh-Component");
                    });
                });
            </script>
        @endpush
    </div>
</div>
