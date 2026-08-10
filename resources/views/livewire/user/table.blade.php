<div class="usrx">
    @use('Illuminate\Support\Str')

    {{--
        ╔══════════════════════════════════════════════════════════════════════╗
        ║  Users — "Facet Rail" premium design system                           ║
        ║                                                                      ║
        ║  Roles, designations and status are a clickable rail with live       ║
        ║  counts rather than dropdowns: each tally is what you would get if   ║
        ║  you clicked that facet (see Table::getFilters()'s $except).         ║
        ║                                                                      ║
        ║  Styling lives in components/user/premium.blade.php, scoped to       ║
        ║  .usrx and derived from the active settings theme.                   ║
        ║                                                                      ║
        ║  Preview: docs/users-premium-preview.html (direction C)              ║
        ╚══════════════════════════════════════════════════════════════════════╝
    --}}
    <x-user.premium />

    @php
        $activeRole = $role_id ? $roles->firstWhere('id', (int) $role_id) : null;
        $activeDesignation = $designation_id ? $designations->firstWhere('id', (int) $designation_id) : null;
        $hasFilters = $search !== '' || $role_id !== '' || $designation_id !== '' || $is_active !== '';
    @endphp

    <div class="content__boxed">
        <div class="content__wrap">

            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Users</li>
                </ol>
            </nav>

            {{-- ── HERO ─────────────────────────────────────────────────── --}}
            <div class="usrx-hero">
                <div class="mesh"></div>
                <div class="glow"></div>
                <div class="usrx-hero-inner">
                    <div class="doc-ic"><i class="fa fa-users"></i></div>
                    <div class="h-main">
                        <div class="h-eyebrow">Access Control</div>
                        <div class="h-ref">Users Directory</div>
                        <div class="h-meta">
                            <span><i class="fa fa-shield"></i>System login accounts</span>
                            <span><i class="fa fa-users"></i>{{ $data->total() }} {{ Str::plural('user', $data->total()) }} matching</span>
                            @if ($activeRole)
                                <span><i class="fa fa-filter"></i>{{ $activeRole->name }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="h-right">
                        @if ($hasFilters)
                            <button type="button" class="btn-hero ghost" wire:click="resetFilters">
                                <i class="fa fa-times"></i> Clear filters
                            </button>
                        @endif
                        @can('user.create')
                            <button type="button" class="btn-hero" id="UserAdd">
                                <i class="fa fa-plus"></i> Add User
                            </button>
                        @endcan
                    </div>
                </div>
            </div>

            <div class="u-layout">

                {{-- ── FACET RAIL ───────────────────────────────────────── --}}
                <div>
                    <div class="rail">
                        <div class="rh">
                            <span class="t"><i class="fa fa-lock"></i> Roles</span>
                            @if ($role_id !== '')
                                <button type="button" class="rst" wire:click="setRole('')">Reset</button>
                            @endif
                        </div>
                        <div class="facets">
                            <button type="button" class="facet @if ($role_id === '') on @endif" wire:click="setRole('')">
                                <span class="sw"></span>
                                <span class="n">All roles</span>
                                <span class="c">{{ $allRolesCount }}</span>
                            </button>
                            @foreach ($roles as $role)
                                @php $count = (int) ($roleCounts[$role->id] ?? 0); @endphp
                                <button type="button" wire:key="role-{{ $role->id }}"
                                    class="facet @if ((int) $role_id === $role->id) on @elseif (! $count) is-empty @endif"
                                    wire:click="setRole({{ $role->id }})">
                                    <span class="sw"></span>
                                    <span class="n text-capitalize">{{ $role->name }}</span>
                                    <span class="c">{{ $count }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div class="rail">
                        <div class="rh">
                            <span class="t"><i class="fa fa-briefcase"></i> Designations</span>
                            @if ($designation_id !== '')
                                <button type="button" class="rst" wire:click="setDesignation('')">Reset</button>
                            @endif
                        </div>
                        <div class="facets">
                            <button type="button" class="facet @if ($designation_id === '') on @endif" wire:click="setDesignation('')">
                                <span class="sw"></span>
                                <span class="n">All designations</span>
                                <span class="c">{{ $allDesignationsCount }}</span>
                            </button>
                            @forelse ($designations as $designation)
                                @php $count = (int) ($designationCounts[$designation->id] ?? 0); @endphp
                                <button type="button" wire:key="designation-{{ $designation->id }}"
                                    class="facet @if ((int) $designation_id === $designation->id) on @elseif (! $count) is-empty @endif"
                                    wire:click="setDesignation({{ $designation->id }})">
                                    <span class="sw"></span>
                                    <span class="n text-capitalize">{{ $designation->name }}</span>
                                    <span class="c">{{ $count }}</span>
                                </button>
                            @empty
                                <div class="facet is-empty"><span class="n">No designations yet</span></div>
                            @endforelse
                        </div>
                    </div>

                    <div class="rail">
                        <div class="rh">
                            <span class="t"><i class="fa fa-toggle-on"></i> Status</span>
                            @if ($is_active !== '')
                                <button type="button" class="rst" wire:click="setStatus('')">Reset</button>
                            @endif
                        </div>
                        <div class="facets">
                            <button type="button" class="facet @if ($is_active === '') on @endif" wire:click="setStatus('')">
                                <span class="sw"></span>
                                <span class="n">All status</span>
                                <span class="c">{{ $statusCounts['active'] + $statusCounts['inactive'] }}</span>
                            </button>
                            <button type="button" class="facet @if ($is_active === '1') on @endif" wire:click="setStatus('1')">
                                <span class="sw"></span>
                                <span class="n">Active</span>
                                <span class="c">{{ $statusCounts['active'] }}</span>
                            </button>
                            <button type="button" class="facet @if ($is_active === '0') on @endif" wire:click="setStatus('0')">
                                <span class="sw"></span>
                                <span class="n">Inactive</span>
                                <span class="c">{{ $statusCounts['inactive'] }}</span>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- ── ROSTER ───────────────────────────────────────────── --}}
                <div class="u-card">
                    <div class="u-toolbar">
                        <label class="u-search">
                            <i class="fa fa-search"></i>
                            <input type="search" autofocus autocomplete="off" wire:model.live.debounce.350ms="search"
                                placeholder="Search by name, email, mobile or code…" aria-label="Search users">
                        </label>
                        <div class="u-select">
                            <select wire:model.live="filter" aria-label="Sort by">
                                <option value="date-created">Sort: Date created</option>
                                <option value="date-modified">Sort: Date modified</option>
                                <option value="alphabetically">Sort: A → Z</option>
                                <option value="alphabetically-reversed">Sort: Z → A</option>
                            </select>
                        </div>
                        <div class="u-select">
                            <select wire:model.live="limit" aria-label="Per page">
                                <option value="12">12</option>
                                <option value="24">24</option>
                                <option value="48">48</option>
                                <option value="96">96</option>
                            </select>
                        </div>
                        <div class="seg">
                            <button type="button" class="@if ($view === 'list') on @endif" wire:click="setView('list')" title="List view">
                                <i class="fa fa-list"></i>
                            </button>
                            <button type="button" class="@if ($view === 'grid') on @endif" wire:click="setView('grid')" title="Card view">
                                <i class="fa fa-th-large"></i>
                            </button>
                        </div>
                    </div>

                    @if ($hasFilters)
                        <div class="chips">
                            <span class="lbl">Active filters</span>
                            @if ($search !== '')
                                <span class="chip">Search <b>{{ Str::limit($search, 24) }}</b>
                                    <button type="button" wire:click="$set('search', '')" aria-label="Clear search"><i class="fa fa-times"></i></button>
                                </span>
                            @endif
                            @if ($activeRole)
                                <span class="chip">Role <b class="text-capitalize">{{ $activeRole->name }}</b>
                                    <button type="button" wire:click="setRole('')" aria-label="Clear role"><i class="fa fa-times"></i></button>
                                </span>
                            @endif
                            @if ($activeDesignation)
                                <span class="chip">Designation <b class="text-capitalize">{{ $activeDesignation->name }}</b>
                                    <button type="button" wire:click="setDesignation('')" aria-label="Clear designation"><i class="fa fa-times"></i></button>
                                </span>
                            @endif
                            @if ($is_active !== '')
                                <span class="chip">Status <b>{{ $is_active === '1' ? 'Active' : 'Inactive' }}</b>
                                    <button type="button" wire:click="setStatus('')" aria-label="Clear status"><i class="fa fa-times"></i></button>
                                </span>
                            @endif
                            <button type="button" class="chip-clear" wire:click="resetFilters">Clear all</button>
                        </div>
                    @endif

                    @if ($data->isEmpty())
                        <div class="empty">
                            <i class="fa fa-user-times"></i>
                            <h5>No users found</h5>
                            <p>
                                @if ($hasFilters)
                                    Nothing matches the filters you have applied.
                                @else
                                    There are no login accounts on this tenant yet.
                                @endif
                            </p>
                            @if ($hasFilters)
                                <button type="button" class="btn-x" wire:click="resetFilters">
                                    <i class="fa fa-refresh"></i> Clear filters
                                </button>
                            @endif
                        </div>
                    @elseif ($view === 'grid')
                        {{-- ── CARD VIEW ────────────────────────────────── --}}
                        <div class="u-grid">
                            @foreach ($data as $user)
                                @php
                                    $initials = Str::of($user->name)->trim()->explode(' ')
                                        ->filter()->take(2)
                                        ->map(fn ($part) => Str::upper(Str::substr($part, 0, 1)))
                                        ->implode('');
                                @endphp
                                <div class="ucard" wire:key="card-{{ $user->id }}">
                                    <div class="cap">
                                        <div class="st">
                                            <span class="bdg">
                                                <i class="fa fa-circle" style="font-size:6px"></i>
                                                {{ $user->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="body">
                                        <div class="av s-lg {{ $user->is_active ? 'on' : 'off' }}">
                                            @if ($user->image)
                                                <img src="{{ $user->photo_url }}" alt="{{ $user->name }}" loading="lazy">
                                            @else
                                                <span class="ini">{{ $initials ?: '?' }}</span>
                                            @endif
                                            <span class="dot"></span>
                                        </div>
                                        <div class="nm text-capitalize">
                                            <a href="{{ route('users::view', $user->id) }}">{{ $user->name }}</a>
                                        </div>
                                        <div class="dg">
                                            <i class="fa fa-briefcase"></i>
                                            {{ $user->designation?->name ?: 'No designation' }}
                                        </div>
                                        <div class="roles">
                                            @if ($user->is_super_admin)
                                                <span class="bdg role admin"><i class="fa fa-star"></i> Super Admin</span>
                                            @endif
                                            @forelse ($user->roles as $role)
                                                <span class="bdg role">{{ $role->name }}</span>
                                            @empty
                                                @unless ($user->is_super_admin)
                                                    <span class="bdg none">No role assigned</span>
                                                @endunless
                                            @endforelse
                                        </div>
                                        <div class="lines">
                                            <div class="ln"><i class="fa fa-envelope"></i><span>{{ $user->email ?: '—' }}</span></div>
                                            <div class="ln"><i class="fa fa-phone"></i><span>{{ $user->mobile ?: '—' }}</span></div>
                                            <div class="ln"><i class="fa fa-map-marker"></i><span>{{ $user->branch?->name ?: 'No default branch' }}</span></div>
                                        </div>
                                    </div>
                                    <div class="acts">
                                        <a href="{{ route('users::view', $user->id) }}" class="key"><i class="fa fa-eye"></i> View</a>
                                        @can('user.edit')
                                            <a href="#" class="edit" table_id="{{ $user->id }}"><i class="fa fa-pencil"></i> Edit</a>
                                        @endcan
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        {{-- ── LIST VIEW ────────────────────────────────── --}}
                        <div class="rowlist">
                            @foreach ($data as $user)
                                @php
                                    $initials = Str::of($user->name)->trim()->explode(' ')
                                        ->filter()->take(2)
                                        ->map(fn ($part) => Str::upper(Str::substr($part, 0, 1)))
                                        ->implode('');
                                @endphp
                                <div class="urow" wire:key="row-{{ $user->id }}">
                                    <div class="av s-md {{ $user->is_active ? 'on' : 'off' }}">
                                        @if ($user->image)
                                            <img src="{{ $user->photo_url }}" alt="{{ $user->name }}" loading="lazy">
                                        @else
                                            <span class="ini">{{ $initials ?: '?' }}</span>
                                        @endif
                                        <span class="dot"></span>
                                    </div>
                                    <div class="main">
                                        <div class="nm text-capitalize">
                                            <a href="{{ route('users::view', $user->id) }}">{{ $user->name }}</a>
                                        </div>
                                        <div class="sub">
                                            <span><i class="fa fa-envelope"></i>{{ $user->email ?: '—' }}</span>
                                            <span><i class="fa fa-phone"></i>{{ $user->mobile ?: '—' }}</span>
                                        </div>
                                    </div>
                                    <div class="tags">
                                        @if ($user->designation?->name)
                                            <span class="bdg desig text-capitalize">{{ $user->designation->name }}</span>
                                        @endif
                                        @if ($user->is_super_admin)
                                            <span class="bdg role admin"><i class="fa fa-star"></i> Super Admin</span>
                                        @endif
                                        @forelse ($user->roles as $role)
                                            <span class="bdg role">{{ $role->name }}</span>
                                        @empty
                                            @unless ($user->is_super_admin)
                                                <span class="bdg none">No role</span>
                                            @endunless
                                        @endforelse
                                        @unless ($user->is_active)
                                            <span class="bdg off"><i class="fa fa-circle" style="font-size:6px"></i> Inactive</span>
                                        @endunless
                                    </div>
                                    <div class="meta">
                                        <div class="k">Branch</div>
                                        <div class="v text-capitalize">{{ $user->branch?->name ?: '—' }}</div>
                                    </div>
                                    <a href="{{ route('users::view', $user->id) }}" class="go" title="Open {{ $user->name }}">
                                        <i class="fa fa-angle-right"></i>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if ($data->isNotEmpty())
                        <div class="u-foot">
                            <span class="cnt">
                                Showing <b>{{ $data->firstItem() }}–{{ $data->lastItem() }}</b> of <b>{{ $data->total() }}</b>
                                {{ Str::plural('user', $data->total()) }}
                            </span>
                            {{ $data->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            $(document).ready(function() {
                $(document).on('click', '#UserAdd', function() {
                    Livewire.dispatch("User-Page-Create-Component");
                });
                $(document).on('click', '.usrx .edit', function(e) {
                    e.preventDefault();
                    Livewire.dispatch("User-Page-Update-Component", {
                        id: $(this).attr('table_id')
                    });
                });
                window.addEventListener('RefreshUserTable', event => {
                    Livewire.dispatch("User-Refresh-Component");
                });
            });
        </script>
    @endpush
</div>
