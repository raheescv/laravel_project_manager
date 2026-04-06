<div>
    <div class="card shadow-sm">
        <div class="card-header bg-light py-3">
            <div class="row mt-3">
                <div class="col-md-6 d-flex flex-wrap gap-2 align-items-center mb-3 mb-md-0">
                    @can('complaint.create')
                        <button class="btn btn-primary d-flex align-items-center shadow-sm" id="ComplaintAdd">
                            <i class="fa fa-plus-circle me-2"></i>
                            Add New Complaint
                        </button>
                    @endcan
                    <div class="btn-group shadow-sm">
                        @can('complaint.delete')
                            <button class="btn btn-danger btn-sm d-flex align-items-center" title="Delete Selected" data-bs-toggle="tooltip" wire:click="delete()"
                                wire:confirm="Are you sure you want to delete the selected items?">
                                <i class="fa fa-trash me-md-1 fs-5"></i>
                                <span class="d-none d-md-inline">Delete</span>
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
                        <div class="col-auto">
                            <select wire:model.live="filterCategory" class="form-select form-select-sm border-secondary-subtle shadow-sm">
                                <option value="">All Categories</option>
                                @foreach(\App\Models\ComplaintCategory::orderBy('name')->get() as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white border-secondary-subtle">
                                    <i class="fa fa-search"></i>
                                </span>
                                <input type="text" wire:model.live="search" autofocus placeholder="Search complaints..." class="form-control form-control-sm border-secondary-subtle shadow-sm"
                                    autocomplete="off">
                            </div>
                        </div>
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
                            <th class="fw-semibold"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="name" label="Name" /> </th>
                            <th class="fw-semibold">Category</th>
                            <th class="fw-semibold">Arabic Name</th>
                            <th class="fw-semibold text-center">Status</th>
                            <th class="fw-semibold text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data as $item)
                            <tr>
                                <td>
                                    <div class="form-check ms-1">
                                        <input type="checkbox" value="{{ $item->id }}" wire:model.live="selected" class="form-check-input shadow-sm" id="checkbox{{ $item->id }}" />
                                        <label class="form-check-label" for="checkbox{{ $item->id }}">{{ $item->id }}</label>
                                    </div>
                                </td>
                                <td>
                                    <span class="fw-medium text-dark">
                                        <i class="fa fa-exclamation-circle me-1 text-primary opacity-75"></i>{{ $item->name }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ $item->category->name ?? '-' }}</span>
                                </td>
                                <td>
                                    @if($item->arabic_name)
                                        <i class="fa fa-language me-1 text-muted"></i>{{ $item->arabic_name }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($item->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        @can('complaint.edit')
                                            <button table_id="{{ $item->id }}" class="btn btn-light btn-sm edit" title="Edit" data-bs-toggle="tooltip">
                                                <i class="fa fa-eye"></i>
                                            </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="fa fa-exclamation-circle fa-3x mb-3 d-block opacity-25"></i>
                                    No complaints found matching your search.
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
            <button id="ComplaintAddMobile" class="btn btn-primary rounded-circle shadow btn-lg">
                <i class="fa fa-plus"></i>
            </button>
        </div>

        @push('scripts')
            <script>
                $(document).ready(function() {
                    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                    tooltipTriggerList.map(function(tooltipTriggerEl) {
                        return new bootstrap.Tooltip(tooltipTriggerEl, { boundary: document.body });
                    });

                    $(document).on('click', '.edit', function() {
                        Livewire.dispatch("Complaint-Page-Update-Component", {
                            id: $(this).attr('table_id')
                        });
                    });
                    $('#ComplaintAdd, #ComplaintAddMobile').click(function() {
                        Livewire.dispatch("Complaint-Page-Create-Component");
                    });
                    window.addEventListener('RefreshComplaintTable', event => {
                        Livewire.dispatch("Complaint-Refresh-Component");
                    });
                });
            </script>
        @endpush
    </div>
</div>
