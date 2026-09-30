{{-- Tenant Control list — "Fleet Console" (.tnx, components/tenant/premium.blade.php; preview docs/tenant-control-premium-preview.html, direction A). --}}
<div class="tnx">
    <x-tenant.premium />

    <div class="tnx-kpis">
        <button type="button" class="tnx-kpi" wire:click="$set('status', 'all')">
            <span class="tnx-ic"><i class="fa fa-building"></i></span>
            <span><span class="tnx-v d-block">{{ number_format($counts['all']) }}</span><span class="tnx-l d-block">Workspaces</span></span>
        </button>
        <button type="button" class="tnx-kpi" wire:click="$set('status', 'active')">
            <span class="tnx-ic is-ok"><i class="fa fa-check-circle"></i></span>
            <span><span class="tnx-v d-block">{{ number_format($counts['active']) }}</span><span class="tnx-l d-block">Active</span></span>
        </button>
        <button type="button" class="tnx-kpi" wire:click="$set('status', 'renewal_due')">
            <span class="tnx-ic is-warn"><i class="fa fa-refresh"></i></span>
            <span><span class="tnx-v d-block">{{ number_format($counts['renewal_due']) }}</span><span class="tnx-l d-block">Renewal due</span></span>
        </button>
        <div class="tnx-kpi">
            <span class="tnx-ic"><i class="fa fa-users"></i></span>
            <span><span class="tnx-v d-block">{{ number_format($usersTotal) }}</span><span class="tnx-l d-block">Users across tenants</span></span>
        </div>
    </div>

    <div class="tnx-shell">
        <div class="tnx-toolbar">
            <button type="button" class="tnx-btn tnx-btn-acc" wire:click="$dispatch('Tenant-Page-Create-Component')" title="Add Tenant">
                <i class="fa fa-plus"></i><span class="tnx-txt">Add Tenant</span>
            </button>
            <button type="button" class="tnx-btn tnx-btn-danger" title="Delete Selected" wire:click="delete()" @disabled(!count($selected))
                wire:confirm="Delete the selected tenants? They can be restored from the Deleted status.">
                <i class="fa fa-trash"></i><span class="tnx-txt">Delete{{ count($selected) ? ' (' . count($selected) . ')' : '' }}</span>
            </button>
            <label class="tnx-btn tnx-btn-ghost tnx-narrow-only mb-0" title="Select all">
                <input type="checkbox" wire:model.live="selectAll" class="tnx-cb" aria-label="Select all"><span class="tnx-txt">Select all</span>
            </label>
            <div class="tnx-grow"></div>
            <label class="tnx-search">
                <i class="fa fa-search tnx-dim"></i>
                <input type="text" wire:model.live.debounce.400ms="search" autofocus placeholder="Name, code, subdomain or domain…" autocomplete="off" aria-label="Search tenants">
            </label>
        </div>

        <div class="tnx-filters">
            <div class="tnx-segs" role="tablist" aria-label="Status">
                @foreach ([
                    'all' => 'All',
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                    'renewal_due' => 'Renewal due',
                    'trashed' => 'Deleted',
                ] as $key => $label)
                    <button type="button" wire:click="$set('status', '{{ $key }}')" role="tab" aria-selected="{{ $status === $key ? 'true' : 'false' }}"
                        @class(['tnx-seg', 'is-on' => $status === $key, 'is-warn' => $key === 'renewal_due' && $counts['renewal_due']])>
                        {{ $label }} <b>{{ number_format($counts[$key]) }}</b>
                    </button>
                @endforeach
            </div>
            <div class="tnx-sels">
                <select class="tnx-sel" wire:model.live="system" aria-label="System">
                    <option value="">All systems</option>
                    @foreach ($systems as $option)
                        <option value="{{ $option }}">{{ $option }}</option>
                    @endforeach
                </select>
                <select class="tnx-sel" wire:model.live="filter" aria-label="Sort by">
                    <option value="date-created">Date created</option>
                    <option value="date-modified">Date modified</option>
                    <option value="renewal-date">Renewal date (soonest)</option>
                    <option value="last-login">Last login</option>
                    <option value="alphabetically">Name A → Z</option>
                    <option value="alphabetically-reversed">Name Z → A</option>
                </select>
            </div>
        </div>

        <table class="tnx-table">
            <thead>
                <tr>
                    <th style="width: 2.5rem"><input type="checkbox" wire:model.live="selectAll" class="tnx-cb" aria-label="Select all"></th>
                    <th>Tenant</th>
                    <th>System</th>
                    <th>Size</th>
                    <th>Started</th>
                    <th>Renewal</th>
                    <th>Activity</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($data as $tenant)
                    @php($isCurrent = $tenant->id === $currentTenantId)
                    <tr wire:key="tenant-row-{{ $tenant->id }}" @class(['tnx-row', 'is-current' => $isCurrent, 'is-deleted' => $tenant->deleted_at])>
                        <td class="c-cb">
                            @unless ($isCurrent || $tenant->deleted_at)
                                <input type="checkbox" value="{{ $tenant->id }}" wire:model.live="selected" class="tnx-cb" aria-label="Select {{ $tenant->name }}">
                            @endunless
                        </td>
                        <td class="c-who">
                            <div class="tnx-who">
                                <a href="{{ route('tenants::view', $tenant->id) }}" @class(['tnx-avatar', 'is-muted' => !$tenant->is_active || $tenant->deleted_at])>{{ mb_strtoupper(mb_substr($tenant->name, 0, 1)) }}</a>
                                <div class="tnx-meta">
                                    <div class="tnx-top">
                                        <a href="{{ route('tenants::view', $tenant->id) }}" class="tnx-name">{{ $tenant->name }}</a>
                                        <span class="tnx-code">{{ $tenant->code }}</span>
                                    </div>
                                    <a href="{{ $tenant->url() }}" target="_blank" rel="noopener" class="tnx-host"><i class="fa fa-globe me-1"></i>{{ parse_url($tenant->url(), PHP_URL_HOST) }}</a>
                                    @if ($isCurrent)
                                        <span class="tnx-here"><i class="fa fa-map-marker"></i> You are here</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="c-sys">
                            @if ($systemsByTenant[$tenant->id] ?? null)
                                <span class="tnx-chip"><i class="fa fa-cube"></i>{{ $systemsByTenant[$tenant->id] }}</span>
                            @else
                                <span class="tnx-dim">No system</span>
                            @endif
                        </td>
                        <td class="c-size">
                            <div class="tnx-size">
                                <div><div class="tnx-num">{{ number_format($tenant->users_count) }}</div><div class="tnx-num-l">Users</div></div>
                                <div><div class="tnx-num">{{ number_format($tenant->branches_count) }}</div><div class="tnx-num-l">Branches</div></div>
                                <div><div class="tnx-num">{{ number_format($tenant->products_count) }}</div><div class="tnx-num-l">Products</div></div>
                            </div>
                        </td>
                        <td class="c-start">
                            <span class="tnx-lbl">Started</span>
                            @if ($tenant->started_on)
                                <div class="tnx-when" title="{{ $tenant->started_on->format('d M Y') }}">{{ $tenant->startedAgo() }}</div>
                                <div class="tnx-sub">{{ $tenant->started_on->format('d M Y') }}</div>
                            @else
                                <span class="tnx-dim">Not set</span>
                            @endif
                        </td>
                        <td class="c-renew">
                            <span class="tnx-lbl">Renewal</span>
                            @if ($tenant->renews_on)
                                @php($renewal = $tenant->renewalState())
                                <div class="tnx-when">{{ $tenant->renews_on->format('d M Y') }}</div>
                                <div class="tnx-renew is-{{ $renewal }}">{{ $tenant->renewalCountdown() }}</div>
                                <div class="tnx-bar is-{{ $renewal }}"><i style="width: {{ $tenant->renewalProgress() }}%"></i></div>
                            @else
                                <span class="tnx-dim">No renewal set</span>
                            @endif
                            @if ($tenant->amc_amount !== null)
                                <div class="tnx-sub">AMC {{ number_format((float) $tenant->amc_amount, 2) }}{{ $tenant->amcCycleLabel() ? ' / ' . strtolower($tenant->amcCycleLabel()) : '' }}</div>
                            @endif
                        </td>
                        <td class="c-act">
                            <div class="tnx-act">
                                <span><i class="fa fa-shopping-cart"></i> Sale {{ $tenant->sales_max_created_at ? \Illuminate\Support\Carbon::parse($tenant->sales_max_created_at)->diffForHumans() : 'never' }}</span>
                                <span title="{{ $tenant->users_max_last_login_at }}"><i class="fa fa-sign-in"></i> Login {{ $tenant->users_max_last_login_at ? \Illuminate\Support\Carbon::parse($tenant->users_max_last_login_at)->diffForHumans() : 'never' }}</span>
                            </div>
                        </td>
                        <td class="c-status">
                            @if ($tenant->deleted_at)
                                <span class="tnx-pill is-del">Deleted</span>
                            @elseif ($tenant->is_active)
                                <span class="tnx-pill is-ok">Active</span>
                            @else
                                <span class="tnx-pill is-off">Inactive</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="tnx-empty">
                            <i class="fa fa-building-o"></i>
                            @if ($counts['all'] + $counts['trashed'])
                                <div class="mb-2">No tenants match these filters.</div>
                                <button type="button" class="tnx-btn tnx-btn-ghost" wire:click="clearFilters"><i class="fa fa-filter"></i>Show all tenants</button>
                            @else
                                No tenants yet.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="tnx-pager">
            <div class="tnx-grow">{{ $data->links() }}</div>
            <select wire:model.live="limit" class="tnx-sel" aria-label="Rows per page">
                <option value="10">10 / page</option>
                <option value="100">100 / page</option>
                <option value="500">500 / page</option>
            </select>
        </div>
    </div>
    @push('scripts')
        <script>
            window.addEventListener('RefreshTenantTable', () => Livewire.dispatch('Tenant-Refresh-Component'));
        </script>
    @endpush
</div>
