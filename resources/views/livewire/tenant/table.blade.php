{{-- Tenant Control list — same layout as the Student list (toolbar, filter row, compact table). --}}
<div>
    <div class="card-header bg-light py-3">
        <div class="row g-2 align-items-center">
            <div class="col-md-6 d-flex flex-wrap gap-2 align-items-center">
                <button type="button" class="btn btn-primary d-flex align-items-center shadow-sm" wire:click="$dispatch('Tenant-Page-Create-Component')">
                    <i class="fa fa-plus me-2"></i> Add Tenant
                </button>
                <div class="btn-group shadow-sm">
                    <button class="btn btn-danger btn-sm d-flex align-items-center" title="Delete Selected" wire:click="delete()" @disabled(!count($selected))
                        wire:confirm="Delete the selected tenants? They can be restored from the Deleted status.">
                        <i class="fa fa-trash me-md-1 fs-5"></i><span class="d-none d-md-inline">Delete{{ count($selected) ? ' (' . count($selected) . ')' : '' }}</span>
                    </button>
                </div>
            </div>
            <div class="col-md-6">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-secondary-subtle"><i class="fa fa-search"></i></span>
                    <input type="text" wire:model.live.debounce.400ms="search" autofocus placeholder="Name, code, subdomain or domain…"
                        class="form-control form-control-sm border-secondary-subtle shadow-sm" autocomplete="off">
                    <select wire:model.live="limit" class="form-select form-select-sm border-secondary-subtle" style="max-width: 7rem" aria-label="Rows per page">
                        <option value="10">10</option>
                        <option value="100">100</option>
                        <option value="500">500</option>
                    </select>
                </div>
            </div>
        </div>
        <hr class="my-3">
        <div class="row g-2">
            <div class="col-6 col-md-4">
                <label class="form-label small fw-medium mb-1" for="tn_status">Status</label>
                <select id="tn_status" class="form-select form-select-sm" wire:model.live="status">
                    <option value="all">All tenants ({{ $counts['all'] }})</option>
                    <option value="active">Active ({{ $counts['active'] }})</option>
                    <option value="inactive">Inactive ({{ $counts['inactive'] }})</option>
                    <option value="trashed">Deleted ({{ $counts['trashed'] }})</option>
                </select>
            </div>
            <div class="col-6 col-md-4">
                <label class="form-label small fw-medium mb-1" for="tn_system">System</label>
                <select id="tn_system" class="form-select form-select-sm" wire:model.live="system">
                    <option value="">All systems</option>
                    @foreach ($systems as $option)
                        <option value="{{ $option }}">{{ $option }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label small fw-medium mb-1" for="tn_sort">Sort by</label>
                <select id="tn_sort" class="form-select form-select-sm" wire:model.live="filter">
                    <option value="date-created">Date created</option>
                    <option value="date-modified">Date modified</option>
                    <option value="alphabetically">Name A → Z</option>
                    <option value="alphabetically-reversed">Name Z → A</option>
                </select>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle border-bottom mb-0 table-sm">
                <thead class="bg-light text-muted">
                    <tr class="small">
                        <th class="fw-semibold py-2 ps-3" style="width: 2.5rem">
                            <input type="checkbox" wire:model.live="selectAll" class="form-check-input" aria-label="Select all">
                        </th>
                        <th class="fw-semibold">Tenant</th>
                        <th class="fw-semibold">Code</th>
                        <th class="fw-semibold">Workspace</th>
                        <th class="fw-semibold">System</th>
                        <th class="fw-semibold text-end">Users</th>
                        <th class="fw-semibold text-end">Branches</th>
                        <th class="fw-semibold text-end">Products</th>
                        <th class="fw-semibold">Last sale</th>
                        <th class="fw-semibold">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data as $tenant)
                        @php($isCurrent = $tenant->id === $currentTenantId)
                        <tr wire:key="tenant-row-{{ $tenant->id }}">
                            <td class="ps-3">
                                @unless ($isCurrent || $tenant->deleted_at)
                                    <input type="checkbox" value="{{ $tenant->id }}" wire:model.live="selected" class="form-check-input" aria-label="Select {{ $tenant->name }}">
                                @endunless
                            </td>
                            <td class="text-nowrap">
                                <a href="{{ route('tenants::view', $tenant->id) }}" class="d-flex align-items-center gap-2 text-decoration-none">
                                    <span class="rounded-circle border bg-primary-subtle text-primary-emphasis d-inline-flex align-items-center justify-content-center fw-semibold small flex-shrink-0"
                                        style="width: 32px; height: 32px">{{ mb_strtoupper(mb_substr($tenant->name, 0, 1)) }}</span>
                                    <span class="fw-medium">{{ $tenant->name }}</span>
                                </a>
                                @if ($isCurrent)
                                    <small class="text-primary"><i class="fa fa-map-marker me-1"></i>You are here</small>
                                @endif
                            </td>
                            <td class="text-nowrap">{{ $tenant->code }}</td>
                            <td class="text-nowrap">
                                <a href="{{ $tenant->url() }}" target="_blank" rel="noopener" class="text-body-secondary text-decoration-none">
                                    <i class="fa fa-globe me-1"></i>{{ parse_url($tenant->url(), PHP_URL_HOST) }}
                                </a>
                            </td>
                            <td class="text-nowrap">
                                @if ($systemsByTenant[$tenant->id] ?? null)
                                    <span class="badge bg-light text-body-secondary border">{{ $systemsByTenant[$tenant->id] }}</span>
                                @else
                                    <span class="text-body-secondary">-</span>
                                @endif
                            </td>
                            <td class="text-end">{{ number_format($tenant->users_count) }}</td>
                            <td class="text-end">{{ number_format($tenant->branches_count) }}</td>
                            <td class="text-end">{{ number_format($tenant->products_count) }}</td>
                            <td class="text-nowrap text-body-secondary small">
                                {{ $tenant->sales_max_created_at ? \Illuminate\Support\Carbon::parse($tenant->sales_max_created_at)->diffForHumans() : 'Never' }}
                            </td>
                            <td>
                                @if ($tenant->deleted_at)
                                    <span class="badge bg-dark">Deleted</span>
                                @elseif ($tenant->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-body-secondary py-4">
                                @if ($counts['all'] + $counts['trashed'])
                                    <div class="mb-2">No tenants match these filters.</div>
                                    <button type="button" class="btn btn-light btn-sm" wire:click="clearFilters"><i class="fa fa-filter me-1"></i>Show all tenants</button>
                                @else
                                    No tenants yet.
                                @endif
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
            window.addEventListener('RefreshTenantTable', () => Livewire.dispatch('Tenant-Refresh-Component'));
        </script>
    @endpush
</div>
