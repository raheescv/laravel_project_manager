{{--
    Tenant Control → one tenant. Same "Campus ID" look as the student view: it
    reuses the scoped .svx system from components/student/view-premium.
    Users/Branches query only once opened. Font Awesome 4.3 icons only.
--}}
@php
    $words = preg_split('/\s+/', trim($tenant->name), -1, PREG_SPLIT_NO_EMPTY);
    $initials = mb_strtoupper(mb_substr($words[0] ?? '?', 0, 1) . (count($words) > 1 ? mb_substr(end($words), 0, 1) : ''));
    $system = $settings['active_module'] ?? null;
    $isLive = $tenant->is_active && !$tenant->deleted_at;
    $host = parse_url($tenant->url(), PHP_URL_HOST);
    $maxMonth = max(1, collect($summary['trend'])->max('total'));
    $maxBranch = max(1, collect($summary['top_branches'])->max('total'));
    $switchBlocker = match (true) {
        $isCurrentTenant => 'You are already signed into this tenant.',
        (bool) $tenant->deleted_at => 'This tenant is deleted.',
        !$tenant->is_active => 'Activate the tenant before switching into it.',
        !$switchUser => 'No active user to sign in as. Create an admin from the Seeding tab.',
        default => null,
    };
    $tabs = [
        'overview' => ['fa-info-circle', 'Overview', null],
        'users' => ['fa-users', 'Users', $summary['users']['total']],
        'branches' => ['fa-sitemap', 'Branches', $summary['branches']],
        'analytics' => ['fa-bar-chart', 'Analytics', null],
        'seeding' => ['fa-magic', 'Seeding', null],
        'server' => ['fa-server', 'Server', null],
    ];
    $domainStatus = [
        \App\Models\Tenant::DOMAIN_ACTIVE => ['ok', 'fa-lock', 'Live with SSL'],
        \App\Models\Tenant::DOMAIN_PENDING => ['', 'fa-clock-o', 'Waiting for the server'],
        \App\Models\Tenant::DOMAIN_FAILED => ['off', 'fa-exclamation-triangle', 'Failed'],
    ];
@endphp

<div class="svx">
    <x-student.view-premium />
    @once
        @push('styles')
            <style>
                /* Hero actions: glass buttons that stay legible on the gradient (the theme's
                   btn-outline-light turns its label dark), one solid white main action. */
                .svx .hero .tact { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
                .svx .hero .tbtn { display: inline-flex; align-items: center; gap: 7px; height: 34px; padding: 0 14px; border-radius: 10px;
                    font-size: 12.5px; font-weight: 600; line-height: 1; color: #fff; white-space: nowrap; cursor: pointer;
                    background: rgba(255, 255, 255, .12); border: 1px solid rgba(255, 255, 255, .26);
                    -webkit-backdrop-filter: blur(6px); backdrop-filter: blur(6px);
                    transition: background .15s, border-color .15s, transform .15s, box-shadow .15s; }
                .svx .hero .tbtn i { font-size: 12px; opacity: .85; }
                .svx .hero .tbtn:hover { background: rgba(255, 255, 255, .22); border-color: rgba(255, 255, 255, .42); }
                .svx .hero .tbtn:active { transform: translateY(1px); }
                .svx .hero .tbtn:focus-visible { outline: 2px solid #fff; outline-offset: 2px; }
                .svx .hero .tbtn.danger:hover { background: rgba(var(--bs-danger-rgb), .55); border-color: rgba(var(--bs-danger-rgb), .9); }
                .svx .hero .tbtn.ok:hover { background: rgba(var(--bs-success-rgb), .55); border-color: rgba(var(--bs-success-rgb), .9); }
                .svx .hero .tbtn.main { background: #fff; border-color: #fff; color: var(--hero-1); box-shadow: 0 8px 20px -10px rgba(0, 0, 0, .55); }
                .svx .hero .tbtn.main i { opacity: 1; }
                .svx .hero .tbtn.main:hover { background: rgba(255, 255, 255, .9); transform: translateY(-1px); box-shadow: 0 12px 24px -12px rgba(0, 0, 0, .6); }
                .svx .hero .tbtn:disabled { opacity: .6; cursor: progress; transform: none; }
                .svx .hero .tnote { display: inline-flex; align-items: center; gap: 7px; height: 34px; padding: 0 12px; border-radius: 10px;
                    font-size: 12px; color: rgba(255, 255, 255, .85); background: rgba(0, 0, 0, .16); border: 1px dashed rgba(255, 255, 255, .3); }
                @media (max-width: 575.98px) { .svx .hero .tact { width: 100%; } .svx .hero .tbtn { flex: 1 1 auto; justify-content: center; } }
            </style>
        @endpush
    @endonce

    {{-- Hero: who the tenant is, and what can be done to it --}}
    <div class="sheet hero mb-3 rise" style="--i:0">
        <div class="d-flex gap-3 align-items-start">
            <div class="av">
                {{ $initials }}
                <span @class(['dot', 'bg-success' => $isLive, 'bg-secondary' => !$isLive])>
                    <i class="fa {{ $isLive ? 'fa-check' : 'fa-minus' }}"></i>
                </span>
            </div>
            <div class="flex-grow-1 min-w-0">
                <a href="{{ route('tenants::index') }}" class="eyebrow" title="Back to Tenant Control"><i class="fa fa-building"></i>Tenant <b>· {{ $tenant->code }}</b></a>
                <div class="nm">{{ $tenant->name }}</div>
                <div class="d-flex flex-wrap gap-2">
                    <span @class(['chip', 'ok' => $isLive, 'off' => !$isLive])>
                        <i class="fa fa-circle"></i>{{ $tenant->deleted_at ? 'Deleted' : ($tenant->is_active ? 'Active' : 'Inactive') }}
                    </span>
                    <span class="chip"><i class="fa fa-cubes"></i>{{ $system ?: 'No system chosen' }}</span>
                    <span class="chip"><i class="fa fa-calendar-o"></i>Since {{ $tenant->created_at?->format('M Y') }}</span>
                    @if ($isCurrentTenant)
                        <span class="chip"><i class="fa fa-map-marker"></i>You are here</span>
                    @endif
                </div>
                <div class="pline">
                    <i class="fa fa-globe"></i>
                    <a href="{{ $tenant->url() }}" target="_blank" rel="noopener">{{ $host }}</a>
                    @if ($switchUser)
                        <span>· switch signs in as <b>{{ $switchUser->name }}</b></span>
                    @endif
                    <div class="tact ms-auto">
                        @if ($tenant->deleted_at)
                            <button type="button" class="tbtn main" wire:click="restore" wire:loading.attr="disabled" wire:target="restore">
                                <i class="fa fa-undo"></i>Restore tenant
                            </button>
                        @else
                            <button type="button" class="tbtn" wire:click="$dispatch('Tenant-Page-Update-Component', { id: '{{ $tenant->id }}' })">
                                <i class="fa fa-pencil"></i>Edit
                            </button>
                            @if ($tenant->is_active)
                                @unless ($isCurrentTenant)
                                    <button type="button" class="tbtn danger" wire:click="toggleStatus({{ $tenant->id }}, false)"
                                        wire:loading.attr="disabled" wire:target="toggleStatus"
                                        wire:confirm="Deactivate {{ $tenant->name }}? Its users will no longer be able to reach the workspace.">
                                        <i class="fa fa-power-off"></i>Deactivate
                                    </button>
                                @endunless
                            @else
                                <button type="button" class="tbtn ok" wire:click="toggleStatus({{ $tenant->id }}, true)" wire:loading.attr="disabled" wire:target="toggleStatus">
                                    <i class="fa fa-power-off"></i>Activate
                                </button>
                            @endif
                            @if ($switchBlocker)
                                <span class="tnote"><i class="fa fa-info-circle"></i>{{ $switchBlocker }}</span>
                            @else
                                <button type="button" class="tbtn main" wire:click="switchInto({{ $tenant->id }})" wire:loading.attr="disabled" wire:target="switchInto"
                                    wire:confirm="Switch into {{ $tenant->name }} as {{ $switchUser->name }}? The session lasts {{ \App\Services\ImpersonationService::DURATION_MINUTES }} minutes and is logged.">
                                    <i class="fa fa-sign-in" wire:loading.remove wire:target="switchInto"></i>
                                    <i class="fa fa-spinner fa-spin" wire:loading wire:target="switchInto"></i>
                                    Switch into
                                </button>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- KPI tiles --}}
    <div class="row g-3 mb-3">
        @foreach ([
            ['icon' => 'fa-users', 'tone' => 'primary', 'label' => 'Users', 'value' => number_format($summary['users']['total']), 'foot' => $summary['users']['active'] . ' active · ' . $summary['users']['inactive'] . ' inactive'],
            ['icon' => 'fa-sitemap', 'tone' => 'info', 'label' => 'Branches', 'value' => number_format($summary['branches']), 'foot' => $summary['users']['employees'] . ' ' . Str::plural('employee', $summary['users']['employees'])],
            ['icon' => 'fa-cube', 'tone' => 'warning', 'label' => 'Products', 'value' => number_format($summary['products']), 'foot' => number_format($summary['customers']) . ' ' . Str::plural('customer', $summary['customers'])],
            ['icon' => 'fa-line-chart', 'tone' => 'success', 'label' => 'Sales · 30 days', 'value' => number_format($summary['sales']['last_30_days'], 2), 'foot' => $summary['last_activity'] ? 'Last activity ' . \Illuminate\Support\Carbon::parse($summary['last_activity'])->diffForHumans() : 'No transactions yet'],
        ] as $index => $kpi)
            <div class="col-6 col-xl-3">
                <div class="sheet kpi rise" style="--i:{{ $index + 1 }}">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <div class="lab">{{ $kpi['label'] }}</div>
                        <div class="ic bg-{{ $kpi['tone'] }}-subtle text-{{ $kpi['tone'] }}-emphasis"><i class="fa {{ $kpi['icon'] }}"></i></div>
                    </div>
                    <div class="val">{{ $kpi['value'] }}</div>
                    <div class="ft">{{ $kpi['foot'] }}</div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Tabs --}}
    <div class="sheet rise" style="--i:5">
        <div class="rail" role="tablist">
            @foreach ($tabs as $tab => [$icon, $label, $count])
                <button type="button" role="tab" aria-selected="{{ $selected_tab === $tab ? 'true' : 'false' }}" @class(['on' => $selected_tab === $tab]) wire:click="selectTab('{{ $tab }}')">
                    <i class="fa {{ $icon }}"></i>{{ $label }}
                    @if ($count)
                        <span class="cnt">{{ $count }}</span>
                    @endif
                </button>
            @endforeach
        </div>

        @if ($selected_tab === 'overview')
            <div class="pane">
                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="ph">
                            <span class="pi"><i class="fa fa-building"></i></span>
                            <div>
                                <h6>Profile</h6>
                                <div class="hint">How the tenant is identified</div>
                            </div>
                        </div>
                        <div class="row g-2">
                            @foreach ([
                                ['fa-building', 'Name', $tenant->name],
                                ['fa-hashtag', 'Code', $tenant->code],
                                ['fa-sitemap', 'Subdomain', $tenant->subdomain],
                                ['fa-link', 'Domain', $tenant->domain],
                                ['fa-calendar-o', 'Created', $tenant->created_at?->format('d M Y, h:i A')],
                                ['fa-clock-o', 'Updated', $tenant->updated_at?->format('d M Y, h:i A')],
                            ] as [$icon, $label, $value])
                                <div class="col-sm-6">
                                    <div class="fld">
                                        <div class="k"><i class="fa {{ $icon }}"></i>{{ $label }}</div>
                                        <div @class(['v', 'none' => blank($value), 'mono' => in_array($label, ['Code', 'Subdomain']) && filled($value)])>{{ filled($value) ? $value : 'Not set' }}</div>
                                    </div>
                                </div>
                            @endforeach
                            @if (filled($tenant->description))
                                <div class="col-12">
                                    <div class="fld">
                                        <div class="k"><i class="fa fa-align-left"></i>Description</div>
                                        <div class="v">{{ $tenant->description }}</div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="ph">
                            <span class="pi"><i class="fa fa-cogs"></i></span>
                            <div>
                                <h6>Configuration</h6>
                                <div class="hint">From the tenant's own settings</div>
                            </div>
                        </div>
                        <div class="row g-2">
                            @foreach ([
                                ['fa-cubes', 'System', $system],
                                ['fa-briefcase', 'Company name', $settings['company_name'] ?? null],
                                ['fa-money', 'Currency', $settings['base_currency_code'] ?? ($settings['currency_code'] ?? null)],
                                ['fa-phone', 'Contact', collect([$settings['mobile'] ?? null, $settings['email'] ?? null])->filter()->implode(' · ')],
                            ] as [$icon, $label, $value])
                                <div class="col-sm-6">
                                    <div class="fld">
                                        <div class="k"><i class="fa {{ $icon }}"></i>{{ $label }}</div>
                                        <div @class(['v', 'none' => blank($value)])>{{ filled($value) ? $value : 'Not set' }}</div>
                                    </div>
                                </div>
                            @endforeach
                            @if (!$system || !$summary['branches'])
                                <div class="col-12">
                                    <div class="fld note">
                                        <div class="k"><i class="fa fa-exclamation-triangle"></i>Not provisioned</div>
                                        <div class="v">
                                            This tenant is missing its defaults.
                                            <a href="#" wire:click.prevent="selectTab('seeding')">Open Seeding</a> to add them.
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if ($selected_tab === 'users')
            <div class="pane">
                <div class="tblw">
                    <div class="table-responsive">
                        <table class="table tbl">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Type</th>
                                    <th>Access</th>
                                    <th>Status</th>
                                    <th class="text-end">Joined</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($users as $user)
                                    <tr wire:key="tenant-user-{{ $user->id }}">
                                        <td class="fw-medium text-nowrap">
                                            {{ $user->name }}
                                            @if ($switchUser?->id === $user->id)
                                                <i class="fa fa-sign-in text-primary ms-1" title="Switch into signs in as this user"></i>
                                            @endif
                                        </td>
                                        <td class="text-body-secondary">{{ $user->email }}</td>
                                        <td class="text-capitalize">{{ $user->type }}</td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-1">
                                                @if ($user->is_super_admin)
                                                    <span class="tc plain"><i class="fa fa-star text-warning me-1"></i>Super Admin</span>
                                                @endif
                                                @if ($user->is_admin)
                                                    <span class="tc plain purchase">Administrator</span>
                                                @endif
                                                @foreach ($user->roles as $role)
                                                    <span class="tc plain">{{ $role->name }}</span>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td><span @class(['chip', 'ok' => $user->is_active, 'off' => !$user->is_active])><i class="fa fa-circle"></i>{{ $user->is_active ? 'Active' : 'Inactive' }}</span></td>
                                        <td class="text-end text-body-secondary text-nowrap">{{ $user->created_at?->format('d M Y') }}</td>
                                        <td class="text-end">
                                            <button type="button" @class(['btn btn-sm', 'btn-primary' => $accessUserId === $user->id, 'btn-light' => $accessUserId !== $user->id]) wire:click="editAccess({{ $user->id }})" title="Roles & permissions">
                                                <i class="fa fa-key"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @if ($accessUserId === $user->id)
                                        @php
                                            $userRoleIds = $user->roles->pluck('id')->all();
                                        @endphp
                                        <tr wire:key="tenant-user-access-{{ $user->id }}">
                                            <td colspan="7" class="bg-body-tertiary">
                                                <div class="small text-body-secondary mb-2"><i class="fa fa-key me-1"></i>Tap to grant or remove — applies straight away.</div>
                                                <div class="d-flex flex-wrap gap-2">
                                                    <button type="button" @class(['chip', 'ok' => $user->is_admin]) wire:click="toggleAdmin({{ $user->id }})" wire:loading.attr="disabled">
                                                        <i @class(['fa', 'fa-check-square-o' => $user->is_admin, 'fa-square-o' => !$user->is_admin])></i>Administrator
                                                    </button>
                                                    @forelse ($roles as $role)
                                                        @php
                                                            $hasRole = in_array($role->id, $userRoleIds, true);
                                                        @endphp
                                                        <button type="button" wire:key="tenant-user-{{ $user->id }}-role-{{ $role->id }}" @class(['chip', 'ok' => $hasRole]) wire:click="toggleRole({{ $user->id }}, {{ $role->id }})" wire:loading.attr="disabled">
                                                            <i @class(['fa', 'fa-check-square-o' => $hasRole, 'fa-square-o' => !$hasRole])></i>{{ $role->name }}
                                                            <span class="opacity-75">· {{ $role->permissions_count }} {{ Str::plural('permission', $role->permissions_count) }}</span>
                                                        </button>
                                                    @empty
                                                        <span class="small text-body-secondary">This tenant has no roles yet. Run provisioning from the Seeding tab to add the Admin role.</span>
                                                    @endforelse
                                                </div>
                                            </td>
                                        </tr>
                                    @endif
                                @empty
                                    <tr class="none">
                                        <td colspan="7">No users yet. Create an admin from the Seeding tab.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        @if ($selected_tab === 'branches')
            <div class="pane">
                <div class="tblw">
                    <div class="table-responsive">
                        <table class="table tbl">
                            <thead>
                                <tr>
                                    <th>Branch</th>
                                    <th>Code</th>
                                    <th>Location</th>
                                    <th>Mobile</th>
                                    <th class="text-end">Users</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($branches as $branch)
                                    <tr wire:key="tenant-branch-{{ $branch->id }}">
                                        <td class="fw-medium">{{ $branch->name }}</td>
                                        <td class="mono">{{ $branch->code }}</td>
                                        <td class="text-body-secondary">{{ $branch->location ?: '-' }}</td>
                                        <td class="text-body-secondary">{{ $branch->mobile ?: '-' }}</td>
                                        <td class="text-end">{{ $branch->users_count }}</td>
                                    </tr>
                                @empty
                                    <tr class="none">
                                        <td colspan="5">No branches yet. The Seeding tab adds a Main branch.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        @if ($selected_tab === 'analytics')
            <div class="pane">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                    <div class="small text-body-secondary">Completed documents only · updated {{ \Illuminate\Support\Carbon::parse($summary['generated_at'])->diffForHumans() }}</div>
                    <button type="button" class="btn btn-sm btn-outline-primary" wire:click="refreshAnalytics" wire:loading.attr="disabled" wire:target="refreshAnalytics">
                        <i class="fa fa-refresh me-1" wire:loading.class="fa-spin" wire:target="refreshAnalytics"></i>Refresh
                    </button>
                </div>

                <div class="row g-2 mb-4">
                    @foreach ([
                        ['Sales · all time', $summary['sales']['total'], number_format($summary['sales']['count']) . ' ' . Str::plural('invoice', $summary['sales']['count'])],
                        ['Sales · this month', $summary['sales']['this_month'], now()->format('M Y')],
                        ['Purchases', $summary['purchases']['total'], number_format($summary['purchases']['count']) . ' ' . Str::plural('bill', $summary['purchases']['count'])],
                        ['Sale returns', $summary['returns']['total'], number_format($summary['returns']['count']) . ' ' . Str::plural('return', $summary['returns']['count'])],
                    ] as [$label, $value, $hint])
                        <div class="col-6 col-md-3">
                            <div class="mini">
                                <div class="k">{{ $label }}</div>
                                <div class="v">{{ number_format($value, 2) }} <small>· {{ $hint }}</small></div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="ph">
                            <span class="pi"><i class="fa fa-bar-chart"></i></span>
                            <div>
                                <h6>Sales · last 12 months</h6>
                                <div class="hint">Completed sales by invoice date</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-end gap-1 gap-sm-2 border-bottom pb-1" style="height: 200px">
                            @foreach ($summary['trend'] as $month)
                                <div class="flex-fill d-flex flex-column justify-content-end h-100" title="{{ $month['label'] }}: {{ number_format($month['total'], 2) }}">
                                    <div class="bg-primary rounded-top {{ $month['total'] ? '' : 'opacity-25' }}"
                                        style="height: {{ $month['total'] ? max(2, round(($month['total'] / $maxMonth) * 100)) : 1 }}%"></div>
                                </div>
                            @endforeach
                        </div>
                        <div class="d-flex gap-1 gap-sm-2 mt-1">
                            @foreach ($summary['trend'] as $month)
                                <div class="flex-fill text-center text-body-secondary text-truncate" style="font-size: 10px">{{ $month['label'] }}</div>
                            @endforeach
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="ph">
                            <span class="pi"><i class="fa fa-trophy"></i></span>
                            <div>
                                <h6>Top branches</h6>
                                <div class="hint">By completed sales</div>
                            </div>
                        </div>
                        @forelse ($summary['top_branches'] as $row)
                            <div class="mb-3">
                                <div class="d-flex justify-content-between small">
                                    <span class="fw-medium text-truncate me-2">{{ $row['name'] }}</span>
                                    <span class="text-body-secondary text-nowrap">{{ number_format($row['total'], 2) }}</span>
                                </div>
                                <div class="meter mt-1"><i class="bg-primary" style="width: {{ round(($row['total'] / $maxBranch) * 100) }}%"></i></div>
                                <div class="small text-body-secondary mt-1">{{ number_format($row['count']) }} {{ Str::plural('sale', $row['count']) }}</div>
                            </div>
                        @empty
                            <div class="empty"><i class="fa fa-trophy"></i>No completed sales yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        @endif

        @if ($selected_tab === 'seeding')
            <div class="pane">
                <div class="row g-4">
                    <div class="col-lg-6">
                        <form wire:submit="runProvision" class="act">
                            <h6><i class="fa fa-magic me-1 text-primary"></i>Provision defaults</h6>
                            <p>
                                Adds whatever this tenant is missing: permissions, the Admin role, a Main branch, the chart of accounts,
                                units, working days, basic settings and the default users (System, Admin, Rahees, Employee — all on the Admin role). Nothing existing is changed, so it is safe to run again.
                                Company details and receipt wording are left for the tenant to fill in.
                            </p>
                            <div class="row g-2">
                                <div class="col-12">
                                    <label class="form-label small fw-medium mb-1" for="provisionSystem">System</label>
                                    <select id="provisionSystem" class="form-select form-select-sm" wire:model="provision.system">
                                        <option value="">Keep current / none</option>
                                        @foreach ($systems as $option)
                                            <option value="{{ $option }}">{{ $option }}</option>
                                        @endforeach
                                    </select>
                                    @error('provision.system') <div class="small text-danger mt-1">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-12 small fw-medium mt-3">Admin user <span class="text-body-secondary fw-normal">(optional, leave the email empty to skip)</span></div>
                                <div class="col-md-6">
                                    <label class="form-label small mb-1" for="provisionName">Name</label>
                                    <input id="provisionName" type="text" class="form-control form-control-sm" wire:model="provision.name">
                                    @error('provision.name') <div class="small text-danger mt-1">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small mb-1" for="provisionEmail">Email</label>
                                    <input id="provisionEmail" type="email" class="form-control form-control-sm" wire:model="provision.email" autocomplete="off">
                                    @error('provision.email') <div class="small text-danger mt-1">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small mb-1" for="provisionPassword">Password</label>
                                    <input id="provisionPassword" type="password" class="form-control form-control-sm" wire:model="provision.password" autocomplete="new-password">
                                    <div class="form-text">Only used when the email is new to this tenant.</div>
                                    @error('provision.password') <div class="small text-danger mt-1">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm mt-3" wire:loading.attr="disabled" wire:target="runProvision" @disabled($tenant->deleted_at)>
                                <span wire:loading.remove wire:target="runProvision"><i class="fa fa-magic me-1"></i>Run provisioning</span>
                                <span wire:loading wire:target="runProvision"><i class="fa fa-spinner fa-spin me-1"></i>Provisioning…</span>
                            </button>
                        </form>
                    </div>
                    <div class="col-lg-6">
                        <div class="ph">
                            <span class="pi"><i class="fa fa-list-ul"></i></span>
                            <div>
                                <h6>Last run</h6>
                                <div class="hint">What each step added</div>
                            </div>
                        </div>
                        @if ($provisionSteps)
                            <div class="tblw">
                                <table class="table tbl">
                                    <tbody>
                                        @foreach ($provisionSteps as $step)
                                            <tr wire:key="step-{{ $step['key'] }}">
                                                <td>
                                                    <i class="fa {{ $step['status'] === 'created' ? 'fa-check-circle text-success' : 'fa-circle-o text-body-secondary' }} me-2"></i>{{ $step['label'] }}
                                                </td>
                                                <td class="text-end">
                                                    @if ($step['status'] === 'created')
                                                        <span class="chip ok">+{{ number_format($step['created']) }} added</span>
                                                    @else
                                                        <span class="chip off">Already present</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="empty"><i class="fa fa-list-ul"></i>Run provisioning to see what was added.</div>
                        @endif
                    </div>
                </div>
            </div>
        @endif
        @if ($selected_tab === 'server')
            @php
                $heartbeat = $serverHealth['scheduler_last_run'] ? \Illuminate\Support\Carbon::parse($serverHealth['scheduler_last_run']) : null;
                $schedulerOk = $heartbeat && $heartbeat->gt(now()->subMinutes(3));
                $oldestJob = $serverHealth['oldest_job'] ? \Illuminate\Support\Carbon::parse($serverHealth['oldest_job']) : null;
                $queueStalled = $oldestJob && $oldestJob->lt(now()->subMinutes(5));
                [$statusTone, $statusIcon, $statusLabel] = $domainStatus[$tenant->domain_status] ?? ['', 'fa-question', 'Not synced yet'];
            @endphp
            <div class="pane">
                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="ph">
                            <span class="pi"><i class="fa fa-globe"></i></span>
                            <div>
                                <h6>Custom domain</h6>
                                <div class="hint">Its own nginx site and Let's Encrypt certificate</div>
                            </div>
                        </div>
                        @if (!$tenant->hasCustomDomain())
                            @php($wildcardSite = config('tenant_server.wildcard_site'))
                            <div class="empty">
                                <i class="fa fa-globe"></i>
                                @if ($wildcardSite)
                                    No custom domain. This tenant is reached at <b>{{ $host }}</b> through the shared wildcard site, so there is nothing to set up.
                                @else
                                    No domain set up yet. Point a DNS <b>A record</b> to this server, then enter it here — the server writes its nginx site and Let's Encrypt certificate.
                                @endif
                                @unless ($tenant->deleted_at)
                                    <form class="mt-3 mx-auto text-start" style="max-width: 420px" wire:submit="saveCustomDomain">
                                        <label for="customDomain" class="form-label small fw-medium mb-1">Domain</label>
                                        <div class="input-group input-group-sm">
                                            <input id="customDomain" type="text" class="form-control font-monospace" wire:model="customDomain" placeholder="{{ $wildcardSite ? 'shop.example.com' : $host }}" autocomplete="off">
                                            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="saveCustomDomain">
                                                <i class="fa fa-plus me-1"></i>Set up
                                            </button>
                                        </div>
                                        @error('customDomain') <div class="small text-danger mt-1">{{ $message }}</div> @enderror
                                        @unless ($wildcardSite)
                                            <button type="button" class="btn btn-link btn-sm px-0 mt-1" wire:click="$set('customDomain', '{{ $host }}')">
                                                Use the subdomain <span class="font-monospace">{{ $host }}</span>
                                            </button>
                                        @endunless
                                    </form>
                                @endunless
                            </div>
                        @else
                            <div class="row g-2 mb-3">
                                <div class="col-sm-6">
                                    <div class="fld">
                                        <div class="k"><i class="fa fa-link"></i>Domain</div>
                                        <div class="v mono"><a href="https://{{ $tenant->domain }}" target="_blank" rel="noopener">{{ $tenant->domain }}</a></div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="fld">
                                        <div class="k"><i class="fa fa-heartbeat"></i>Status</div>
                                        <div class="v"><span @class(['chip', $statusTone => $statusTone])><i class="fa {{ $statusIcon }}"></i>{{ $statusLabel }}</span></div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="fld">
                                        <div class="k"><i class="fa fa-refresh"></i>Last synced</div>
                                        <div @class(['v', 'none' => !$tenant->domain_synced_at])>{{ $tenant->domain_synced_at?->diffForHumans() ?? 'Never' }}</div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="fld">
                                        <div class="k"><i class="fa fa-file-text-o"></i>nginx site</div>
                                        <div class="v mono small">{{ basename(app(\App\Services\TenantServerService::class)->sitePath($tenant)) }}</div>
                                    </div>
                                </div>
                                @if ($tenant->domain_error)
                                    <div class="col-12">
                                        <div class="fld note">
                                            <div class="k"><i class="fa fa-exclamation-triangle"></i>Last error</div>
                                            <div class="v small" style="white-space: pre-wrap">{{ $tenant->domain_error }}</div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                            <div class="act">
                                <h6><i class="fa fa-sitemap me-1 text-primary"></i>DNS</h6>
                                <p>Create an <b>A record</b> for <span class="mono">{{ $tenant->domain }}</span> pointing to this server's IP address. The server writes the nginx site and requests the certificate within a minute of any change.</p>
                                <button type="button" class="btn btn-sm btn-primary" wire:click="requestDomainSync" wire:loading.attr="disabled" wire:target="requestDomainSync" @disabled($tenant->deleted_at)>
                                    <i class="fa fa-refresh me-1"></i>{{ $tenant->domain_status === \App\Models\Tenant::DOMAIN_FAILED ? 'Retry now' : 'Sync again' }}
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger" wire:click="removeCustomDomain" wire:loading.attr="disabled" wire:target="removeCustomDomain" @disabled($tenant->deleted_at)
                                    wire:confirm="Remove {{ $tenant->domain }}? Its nginx site is taken down and the tenant is no longer reachable at that address.">
                                    <i class="fa fa-trash me-1"></i>Remove
                                </button>
                            </div>
                            @if ($nginxPreview)
                                <details class="act">
                                    <summary class="small fw-medium">nginx configuration (with SSL)</summary>
                                    <pre class="mono small mb-0 mt-2 p-2 rounded-3 bg-body-tertiary" style="max-height: 320px; overflow: auto">{{ $nginxPreview }}</pre>
                                </details>
                            @endif
                        @endif
                    </div>
                    <div class="col-lg-6">
                        <div class="ph">
                            <span class="pi"><i class="fa fa-server"></i></span>
                            <div>
                                <h6>Shared services</h6>
                                <div class="hint">One scheduler and one queue serve every tenant</div>
                            </div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-sm-6">
                                <div class="mini">
                                    <div class="k">Scheduler (cron)</div>
                                    <div @class(['v', 'text-success-emphasis' => $schedulerOk, 'text-danger-emphasis' => !$schedulerOk])>
                                        <i class="fa {{ $schedulerOk ? 'fa-check-circle' : 'fa-times-circle' }} me-1"></i>{{ $schedulerOk ? 'Running' : 'Not running' }}
                                    </div>
                                    <div class="small text-body-secondary">{{ $heartbeat ? 'Last run ' . $heartbeat->diffForHumans() : 'No run recorded' }}</div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="mini">
                                    <div class="k">Queue · {{ $serverHealth['queue_driver'] }}</div>
                                    <div @class(['v', 'text-danger-emphasis' => $queueStalled])>
                                        {{ $serverHealth['pending_jobs'] ?? '—' }} <small>pending</small> · {{ $serverHealth['failed_jobs'] ?? '—' }} <small>failed</small>
                                    </div>
                                    <div class="small text-body-secondary">
                                        @if ($queueStalled)
                                            <span class="text-danger-emphasis"><i class="fa fa-exclamation-triangle me-1"></i>Oldest job waiting {{ $oldestJob->diffForHumans(null, true) }}; workers may be down</span>
                                        @elseif ($serverHealth['queue_driver'] === 'sync')
                                            Jobs run inline, no worker needed
                                        @else
                                            Nothing stuck
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="act" x-data="{ copied: false }">
                            <h6><i class="fa fa-terminal me-1 text-primary"></i>One-time server setup</h6>
                            <p>
                                Run once as root on the server (and again after moving the app). It installs the Supervisor queue workers and a cron file that
                                runs the scheduler and applies custom domains every minute. Paths come from <span class="mono">config/tenant_server.php</span>.
                            </p>
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control font-monospace" value="{{ $serverSetupCommand }}" readonly x-ref="cmd" onclick="this.select()" aria-label="Setup command">
                                <button type="button" class="btn btn-outline-primary bg-body" x-on:click="navigator.clipboard.writeText($refs.cmd.value); copied = true; setTimeout(() => copied = false, 2000)">
                                    <i class="fa" :class="copied ? 'fa-check' : 'fa-files-o'"></i> <span x-text="copied ? 'Copied' : 'Copy'">Copy</span>
                                </button>
                            </div>
                            <div class="form-text">Preview first with <span class="mono">--dry-run</span>.</div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
    @push('scripts')
        <script>
            window.addEventListener('RefreshTenantTable', () => Livewire.dispatch('Tenant-Refresh-Component'));
        </script>
    @endpush
</div>
