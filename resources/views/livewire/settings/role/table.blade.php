<div>
    <div class="card-header bg-white p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center gap-2">
                <div class="btn-group">
                    @can('role.create')
                        <button class="btn btn-sm btn-primary d-flex align-items-center gap-2" id="RoleAdd">
                            <i class="demo-psi-add fs-5"></i>
                            Add New Role
                        </button>
                    @endcan
                    @can('role.delete')
                        <button class="btn btn-sm btn-outline-danger d-flex align-items-center gap-2" wire:click="delete()" wire:confirm="Are you sure you want to delete the selected items?">
                            <i class="demo-pli-recycling fs-5"></i>
                            Delete
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
                    </select>
                </div>
                <div class="input-group input-group-sm" style="width: 200px;">
                    <span class="input-group-text bg-light border-end-0">
                        <i class="demo-pli-magnifi-glass"></i>
                    </span>
                    <input type="text" wire:model.live="search" class="form-control border-start-0" placeholder="Search roles..." autofocus>
                </div>
            </div>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 border">
                <thead class="bg-light">
                    <tr>
                        <th class="border-bottom py-3">
                            <div class="d-flex align-items-center gap-2">
                                <div class="form-check mb-0">
                                    <input type="checkbox" class="form-check-input" wire:model.live="selectAll" id="selectAll">
                                    <label class="form-check-label" for="selectAll"></label>
                                </div>
                                <a href="#" class="text-decoration-none text-dark d-flex align-items-center gap-1" wire:click.prevent="sortBy('id')">
                                    ID
                                    @if ($sortField === 'id')
                                        <i class="demo-pli-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} fs-5"></i>
                                    @endif
                                </a>
                            </div>
                        </th>
                        <th class="border-bottom py-3">
                            <a href="#" class="text-decoration-none text-dark d-flex align-items-center gap-1" wire:click.prevent="sortBy('name')">
                                Role Name
                                @if ($sortField === 'name')
                                    <i class="demo-pli-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }} fs-5"></i>
                                @endif
                            </a>
                        </th>
                        <th class="border-bottom py-3 text-center" width="15%">Permissions</th>
                        <th class="border-bottom py-3 text-end" width="10%">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data as $item)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="form-check mb-0">
                                        <input type="checkbox" class="form-check-input" value="{{ $item->id }}" wire:model.live="selected">
                                    </div>
                                    <span class="text-muted">#{{ $item->id }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <i class="demo-pli-user fs-5 text-primary"></i>
                                    <span class="fw-medium">{{ $item->name }}</span>
                                </div>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('settings::roles::permission', $item->id) }}" class="btn btn-sm btn-outline-info d-inline-flex align-items-center gap-2">
                                    <i class="demo-psi-list-view fs-5"></i>
                                    Manage
                                </a>
                            </td>
                            <td>
                                <div class="d-flex justify-content-end gap-2">
                                    @can('role.edit')
                                        <button class="btn btn-sm btn-outline-primary d-inline-flex align-items-center edit" table_id="{{ $item->id }}">
                                            <i class="demo-psi-pencil fs-5"></i>
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">
                                <i class="demo-pli-warning-window fs-2 d-block mb-2"></i>
                                No roles found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3">
            {{ $data->links() }}
        </div>
        @push('scripts')
            <script>
                $(document).ready(function() {
                    $(document).on('click', '.edit', function() {
                        Livewire.dispatch("Role-Page-Update-Component", {
                            id: $(this).attr('table_id')
                        });
                    });
                    $('#RoleAdd').click(function() {
                        Livewire.dispatch("Role-Page-Create-Component");
                    });
                    window.addEventListener('RefreshRoleTable', event => {
                        Livewire.dispatch("Role-Refresh-Component");
                    });
                });
            </script>
        @endpush
    </div>
