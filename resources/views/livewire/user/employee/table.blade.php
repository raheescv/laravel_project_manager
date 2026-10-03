<div class="usrx">
    @use('Illuminate\Support\Str')

    {{--
        Employees — the same "Facet Rail" premium system as the Users list
        (livewire/user/table.blade.php). Roles, designations, branches and
        status are a clickable rail with live counts; see Table::getFilters().
        Styling: components/user/premium.blade.php, scoped to .usrx.
    --}}
    <x-user.premium />

    @php
        $activeRole = $role_id ? $roles->firstWhere('id', (int) $role_id) : null;
        $activeDesignation = $designation_id ? $designations->firstWhere('id', (int) $designation_id) : null;
        $activeBranch = $branch_id ? ($branches[(int) $branch_id] ?? null) : null;
        $hasFilters = $search !== '' || $role_id !== '' || $designation_id !== '' || $is_active !== '' || $branch_id !== '';
        $selectedCount = count($selected);
        $initialsOf = fn ($name) => Str::of($name)->trim()->explode(' ')
            ->filter()->take(2)
            ->map(fn ($part) => Str::upper(Str::substr($part, 0, 1)))
            ->implode('');
        $branchIdsOf = fn ($item) => $item->branches
            ->pluck('branch_id')
            ->unique()
            ->filter(fn ($id) => isset($branches[$id]))
            ->sortByDesc(fn ($id) => $id == $item->default_branch_id)
            ->values();
    @endphp

    <div class="content__boxed">
        <div class="content__wrap">

            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Employees</li>
                </ol>
            </nav>

            {{-- ── HERO ─────────────────────────────────────────────────── --}}
            <div class="usrx-hero">
                <div class="aurora" aria-hidden="true">
                    <span class="blob b1"></span><span class="blob b2"></span><span class="blob b3"></span>
                    <span class="spark"></span><span class="spark"></span><span class="spark"></span>
                    <span class="spark"></span><span class="spark"></span><span class="spark"></span>
                </div>
                <div class="mesh"></div>
                <div class="glow"></div>
                <div class="usrx-hero-inner">
                    <div class="doc-ic"><i class="fa fa-id-badge"></i></div>
                    <div class="h-main">
                        <div class="h-eyebrow">People</div>
                        <div class="h-ref">Employee Directory</div>
                        <div class="h-meta">
                            <span><i class="fa fa-briefcase"></i>Staff, roles &amp; branch access</span>
                            <span><i class="fa fa-users"></i>{{ $data->total() }} {{ Str::plural('employee', $data->total()) }} matching</span>
                            @if ($activeBranch)
                                <span><i class="fa fa-sitemap"></i>{{ $activeBranch }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="h-right">
                        @if ($hasFilters)
                            <button type="button" class="btn-hero ghost" wire:click="resetFilters">
                                <i class="fa fa-times"></i> Clear filters
                            </button>
                        @endif
                        @can('employee.export')
                            <button type="button" class="btn-hero ghost" wire:click="export()" wire:loading.attr="disabled" wire:target="export">
                                <i class="fa fa-file-excel-o" wire:loading.remove wire:target="export"></i>
                                <i class="fa fa-spinner fa-spin" wire:loading wire:target="export"></i>
                                Export
                            </button>
                        @endcan
                        @can('employee.create')
                            <button type="button" class="btn-hero" id="EmployeeAdd">
                                <i class="fa fa-plus"></i> Add Employee
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
                            <span class="t"><i class="fa fa-sitemap"></i> Branches</span>
                            @if ($branch_id !== '')
                                <button type="button" class="rst" wire:click="setBranch('')">Reset</button>
                            @endif
                        </div>
                        <div class="facets">
                            <button type="button" class="facet @if ($branch_id === '') on @endif" wire:click="setBranch('')">
                                <span class="sw"></span>
                                <span class="n">All branches</span>
                                <span class="c">{{ $allBranchesCount }}</span>
                            </button>
                            @foreach ($branches as $id => $name)
                                @php $count = (int) ($branchCounts[$id] ?? 0); @endphp
                                <button type="button" wire:key="branch-{{ $id }}"
                                    class="facet @if ((int) $branch_id === $id) on @elseif (! $count) is-empty @endif"
                                    wire:click="setBranch({{ $id }})">
                                    <span class="sw"></span>
                                    <span class="n text-capitalize">{{ $name }}</span>
                                    <span class="c">{{ $count }}</span>
                                </button>
                            @endforeach
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
                        @canany(['employee.edit', 'employee.delete'])
                            <label class="ck-all" title="Select all matching employees">
                                <input type="checkbox" class="form-check-input" wire:model.live="selectAll" aria-label="Select all">
                            </label>
                        @endcanany
                        <label class="u-search">
                            <i class="fa fa-search"></i>
                            <input type="search" autofocus autocomplete="off" wire:model.live.debounce.350ms="search"
                                placeholder="Search by name, code, email, mobile, place…" aria-label="Search employees">
                        </label>
                        <div class="u-select">
                            <select wire:model.live="filter" aria-label="Sort by">
                                <option value="order">Sort: Display order</option>
                                <option value="alphabetically">Sort: A → Z</option>
                                <option value="alphabetically-reversed">Sort: Z → A</option>
                                <option value="code">Sort: Code</option>
                                <option value="date-created">Sort: Date created</option>
                                <option value="date-modified">Sort: Date modified</option>
                            </select>
                        </div>
                        <div class="u-select">
                            <select wire:model.live="limit" aria-label="Per page">
                                <option value="12">12</option>
                                <option value="24">24</option>
                                <option value="48">48</option>
                                <option value="96">96</option>
                                <option value="500">500</option>
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

                    @if ($selectedCount)
                        <div class="bulk">
                            <span class="n"><b>{{ $selectedCount }}</b> {{ Str::plural('employee', $selectedCount) }} selected</span>
                            <div class="acts">
                                @can('employee.edit')
                                    <button type="button" class="btn-x" wire:click="openBranchModal()">
                                        <i class="fa fa-sitemap"></i> Assign branches
                                    </button>
                                @endcan
                                @can('employee.delete')
                                    <button type="button" class="btn-x danger" wire:click="delete()"
                                        wire:confirm="Are you sure you want to delete the selected items?">
                                        <i class="fa fa-trash"></i> Delete
                                    </button>
                                @endcan
                                <button type="button" class="chip-clear" wire:click="clearSelection">Clear selection</button>
                            </div>
                        </div>
                    @endif

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
                            @if ($activeBranch)
                                <span class="chip">Branch <b class="text-capitalize">{{ $activeBranch }}</b>
                                    <button type="button" wire:click="setBranch('')" aria-label="Clear branch"><i class="fa fa-times"></i></button>
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
                            <h5>No employees found</h5>
                            <p>
                                @if ($hasFilters)
                                    Nothing matches the filters you have applied.
                                @else
                                    There are no employees on this tenant yet.
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
                            @foreach ($data as $item)
                                @php
                                    $initials = $initialsOf($item->name);
                                    $assignedBranchIds = $branchIdsOf($item);
                                    $isSelected = in_array((string) $item->id, $selected, true);
                                @endphp
                                <div class="ucard @if ($isSelected) is-sel @endif" wire:key="card-{{ $item->id }}">
                                    <div class="cap">
                                        @canany(['employee.edit', 'employee.delete'])
                                            <label class="ck">
                                                <input type="checkbox" class="form-check-input" value="{{ $item->id }}" wire:model.live="selected" aria-label="Select {{ $item->name }}">
                                            </label>
                                        @endcanany
                                        <div class="st">
                                            <span class="bdg">
                                                <i class="fa fa-circle" style="font-size:6px"></i>
                                                {{ $item->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="body">
                                        <div class="av s-lg {{ $item->is_active ? 'on' : 'off' }}">
                                            @if ($item->image)
                                                <img src="{{ $item->photo_url }}" alt="{{ $item->name }}" loading="lazy">
                                            @else
                                                <span class="ini">{{ $initials ?: '?' }}</span>
                                            @endif
                                            <span class="dot"></span>
                                        </div>
                                        <div class="nm text-capitalize">
                                            <a href="{{ route('users::employee::view', $item->id) }}">{{ $item->name }}</a>
                                        </div>
                                        <div class="dg">
                                            <i class="fa fa-briefcase"></i>
                                            {{ $item->designation ?: 'No designation' }}
                                            @if ($item->code)
                                                <span class="code">{{ $item->code }}</span>
                                            @endif
                                        </div>
                                        <div class="roles">
                                            @forelse ($item->roles as $role)
                                                <span class="bdg role text-capitalize">{{ $role->name }}</span>
                                            @empty
                                                <span class="bdg none">No role assigned</span>
                                            @endforelse
                                        </div>
                                        <div class="lines">
                                            <div class="ln"><i class="fa fa-envelope"></i><span>{{ $item->email ?: '—' }}</span></div>
                                            <div class="ln"><i class="fa fa-phone"></i><span>{{ $item->mobile ?: '—' }}</span></div>
                                            <div class="ln">
                                                <i class="fa fa-sitemap"></i>
                                                <span title="{{ $assignedBranchIds->map(fn ($id) => $branches[$id])->implode(', ') }}">
                                                    {{ $assignedBranchIds->map(fn ($id) => $branches[$id])->implode(', ') ?: 'No branch assigned' }}
                                                </span>
                                            </div>
                                            @if ($item->place || $item->nationality)
                                                <div class="ln"><i class="fa fa-map-marker"></i><span>{{ collect([$item->place, $item->nationality])->filter()->implode(' · ') }}</span></div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="acts">
                                        <a href="{{ route('users::employee::view', $item->id) }}" class="key"><i class="fa fa-eye"></i> View</a>
                                        @can('employee.edit')
                                            <a href="#" class="edit" table_id="{{ $item->id }}"><i class="fa fa-pencil"></i> Edit</a>
                                        @endcan
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        {{-- ── LIST VIEW ────────────────────────────────── --}}
                        <div class="rowlist">
                            @foreach ($data as $item)
                                @php
                                    $initials = $initialsOf($item->name);
                                    $assignedBranchIds = $branchIdsOf($item);
                                    $isSelected = in_array((string) $item->id, $selected, true);
                                @endphp
                                <div class="urow @if ($isSelected) is-sel @endif" wire:key="row-{{ $item->id }}">
                                    @canany(['employee.edit', 'employee.delete'])
                                        <label class="ck">
                                            <input type="checkbox" class="form-check-input" value="{{ $item->id }}" wire:model.live="selected" aria-label="Select {{ $item->name }}">
                                        </label>
                                    @endcanany
                                    <div class="av s-md {{ $item->is_active ? 'on' : 'off' }}">
                                        @if ($item->image)
                                            <img src="{{ $item->photo_url }}" alt="{{ $item->name }}" loading="lazy">
                                        @else
                                            <span class="ini">{{ $initials ?: '?' }}</span>
                                        @endif
                                        <span class="dot"></span>
                                    </div>
                                    <div class="main">
                                        <div class="nm text-capitalize">
                                            <a href="{{ route('users::employee::view', $item->id) }}">{{ $item->name }}</a>
                                            @if ($item->code)
                                                <span class="code">{{ $item->code }}</span>
                                            @endif
                                        </div>
                                        <div class="sub">
                                            <span><i class="fa fa-envelope"></i>{{ $item->email ?: '—' }}</span>
                                            <span><i class="fa fa-phone"></i>{{ $item->mobile ?: '—' }}</span>
                                            @if ($item->place || $item->nationality)
                                                <span><i class="fa fa-map-marker"></i>{{ collect([$item->place, $item->nationality])->filter()->implode(' · ') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="tags">
                                        @if ($item->designation)
                                            <span class="bdg desig text-capitalize">{{ $item->designation }}</span>
                                        @endif
                                        @forelse ($item->roles as $role)
                                            <span class="bdg role text-capitalize">{{ $role->name }}</span>
                                        @empty
                                            <span class="bdg none">No role</span>
                                        @endforelse
                                        @unless ($item->is_active)
                                            <span class="bdg off"><i class="fa fa-circle" style="font-size:6px"></i> Inactive</span>
                                        @endunless
                                    </div>
                                    <div class="meta">
                                        <div class="k">Branches</div>
                                        <div class="v text-capitalize" title="{{ $assignedBranchIds->map(fn ($id) => $branches[$id])->implode(', ') }}">
                                            @if ($assignedBranchIds->isEmpty())
                                                —
                                            @else
                                                @if ($assignedBranchIds->first() == $item->default_branch_id)
                                                    <i class="fa fa-star text-warning"></i>
                                                @endif
                                                {{ $branches[$assignedBranchIds->first()] }}
                                                @if ($assignedBranchIds->count() > 1)
                                                    <span class="more">+{{ $assignedBranchIds->count() - 1 }}</span>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                    <div class="meta ord">
                                        <div class="k">Order</div>
                                        <div class="v">{{ $item->order_no ?? 0 }}</div>
                                    </div>
                                    @can('employee.edit')
                                        <a href="#" class="go edit" table_id="{{ $item->id }}" title="Edit {{ $item->name }}">
                                            <i class="fa fa-pencil"></i>
                                        </a>
                                    @endcan
                                    <a href="{{ route('users::employee::view', $item->id) }}" class="go" title="Open {{ $item->name }}">
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
                                {{ Str::plural('employee', $data->total()) }}
                            </span>
                            {{ $data->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ── Bulk Branch Assignment ───────────────────────────────────────── --}}
    <div class="modal fade" id="EmployeeBranchModal" tabindex="-1" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-light py-3">
                    <h5 class="modal-title"><i class="fa fa-sitemap me-2"></i>Assign Branches</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">
                        <i class="fa fa-users me-1"></i>
                        Applies to <span class="fw-semibold text-body">{{ $selectedCount }}</span> selected {{ Str::plural('employee', $selectedCount) }}.
                    </p>
                    <div class="mb-3" wire:ignore>
                        <label for="bulk_branch_ids" class="form-label small fw-medium">Branches</label>
                        <select class="tomSelect" id="bulk_branch_ids" multiple>
                            @foreach ($branches as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-medium d-block">How to apply</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" id="bulk_branch_mode_replace" value="replace" wire:model="bulk_branch_mode">
                            <label class="form-check-label" for="bulk_branch_mode_replace">
                                Replace <span class="text-muted small">— keep only the branches selected above</span>
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" id="bulk_branch_mode_append" value="append" wire:model="bulk_branch_mode">
                            <label class="form-check-label" for="bulk_branch_mode_append">
                                Add <span class="text-muted small">— keep what they already have and add these</span>
                            </label>
                        </div>
                    </div>
                    <div>
                        <label for="bulk_default_branch_id" class="form-label small fw-medium">
                            Default Branch <span class="text-muted fw-normal">(optional)</span>
                        </label>
                        <select class="form-select" id="bulk_default_branch_id" wire:model="bulk_default_branch_id">
                            <option value="">Keep each employee's current default</option>
                            @foreach ($branches as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text small">Only applied to employees who end up assigned to that branch.</div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary d-flex align-items-center" wire:click="assignBranches()" wire:loading.attr="disabled" wire:target="assignBranches">
                        <i class="fa fa-save me-1" wire:loading.remove wire:target="assignBranches"></i>
                        <i class="fa fa-spinner fa-spin me-1" wire:loading wire:target="assignBranches"></i>
                        Assign Branches
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            $(document).ready(function() {
                $(document).on('click', '#EmployeeAdd', function() {
                    Livewire.dispatch("Employee-Page-Create-Component");
                });
                $(document).on('click', '.usrx .edit', function(e) {
                    e.preventDefault();
                    Livewire.dispatch("Employee-Page-Update-Component", {
                        id: $(this).attr('table_id')
                    });
                });
                window.addEventListener('RefreshEmployeeTable', event => {
                    Livewire.dispatch("Employee-Refresh-Component");
                });

                // Bulk branch assignment modal. Values are set deferred so
                // picking a branch does not fire a round trip (and reset the
                // table's page) before the Assign button is pressed.
                window.addEventListener('OpenEmployeeBranchModal', event => {
                    $('#EmployeeBranchModal').modal('show');
                });
                window.addEventListener('CloseEmployeeBranchModal', event => {
                    $('#EmployeeBranchModal').modal('hide');
                    var branchSelect = document.getElementById('bulk_branch_ids');
                    if (branchSelect && branchSelect.tomselect) {
                        branchSelect.tomselect.clear();
                    }
                });
                $(document).on('change', '#bulk_branch_ids', function() {
                    @this.set('bulk_branch_ids', $(this).val() || [], false);
                });
            });
        </script>
    @endpush
</div>
