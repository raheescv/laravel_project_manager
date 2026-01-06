<div>
    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">

            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Tenants</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Tenants</h1>
            <p class="lead">
                This page contains tenant information
            </p>
            <!-- Search form -->
            <div class="col-md-8 offset-md-2 mb-3">
                <div class="searchbox input-group">
                    <input class="searchbox__input form-control form-control-lg" autofocus wire:model.live="search" type="search" placeholder="Search tenants . . ." aria-label="Search">
                    <div class="searchbox__btn-group">
                        <button class="searchbox__btn btn btn-icon bg-transparent shadow-none border-0 btn-sm" type="submit">
                            <i class="demo-pli-magnifi-glass"></i>
                        </button>
                    </div>
                </div>
            </div>
            <!-- END : Search form -->
            <div class="d-md-flex align-items-baseline mt-3">
                <button type="button" class="btn btn-info hstack gap-2 mb-3" id="TenantAdd">
                    <i class="demo-psi-add fs-4"></i>
                    <span class="vr"></span>
                    Add new
                </button>
                @if(count($selected) > 0)
                    <button type="button" class="btn btn-danger hstack gap-2 mb-3 ms-2" wire:click="delete">
                        <i class="demo-psi-remove fs-4"></i>
                        <span class="vr"></span>
                        Delete Selected ({{ count($selected) }})
                    </button>
                @endif
                <div class="d-flex align-items-center gap-1 text-nowrap ms-auto mb-3">
                    <span class="d-none d-md-inline-block me-2">Page : </span>
                    <select class="d-inline-block w-auto form-select" wire:model.live="limit">
                        <option value="10" selected="">10</option>
                        <option value="100" selected="">100</option>
                        <option value="500" selected="">500</option>
                    </select>
                </div>
                <div class="d-flex align-items-center gap-1 text-nowrap ms-auto mb-3">
                    <span class="d-none d-md-inline-block me-2">Sort by : </span>
                    <select class="d-inline-block w-auto form-select" wire:model.live="filter">
                        <option value="date-created" selected="">Date Created</option>
                        <option value="date-modified">Date Modified</option>
                        <option value="alphabetically">Alphabetically</option>
                        <option value="alphabetically-reversed">Alphabetically Reversed</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
    <div class="content__boxed">
        <div class="content__wrap">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>
                                        <input type="checkbox" wire:model.live="selectAll" class="form-check-input">
                                    </th>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Code</th>
                                    <th>Subdomain</th>
                                    <th>Domain</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($data as $tenant)
                                    <tr>
                                        <td>
                                            <input type="checkbox" wire:model.live="selected" value="{{ $tenant->id }}" class="form-check-input">
                                        </td>
                                        <td>{{ $tenant->id }}</td>
                                        <td>{{ $tenant->name }}</td>
                                        <td>{{ $tenant->code }}</td>
                                        <td>{{ $tenant->subdomain }}</td>
                                        <td>{{ $tenant->domain ?? 'N/A' }}</td>
                                        <td>
                                            @if($tenant->is_active)
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-danger">Inactive</span>
                                            @endif
                                        </td>
                                        <td>{{ $tenant->created_at->format('Y-m-d H:i') }}</td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-primary" wire:click="$dispatch('Tenant-Page-Update-Component', { id: '{{ $tenant->id }}' })">
                                                <i class="demo-psi-pen"></i> Edit
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center">No tenants found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $data->links() }}
                </div>
            </div>
        </div>
    </div>
    @push('scripts')
        <script>
            $(document).ready(function() {
                $('#TenantAdd').click(function() {
                    Livewire.dispatch("Tenant-Page-Create-Component");
                });
                window.addEventListener('RefreshTenantTable', event => {
                    Livewire.dispatch("Tenant-Refresh-Component");
                });
            });
        </script>
    @endpush
</div>

