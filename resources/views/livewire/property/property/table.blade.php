<div>
    {{--
        ╔══════════════════════════════════════════════════════════════════════╗
        ║  Properties — List · "Premium Compact" design system                  ║
        ║  Scoped under .propx. Accent derives from the active SETTINGS THEME    ║
        ║  (--bs-primary / --bs-* tokens) so it tracks colour + light/dark mode. ║
        ║  All Livewire bindings / TomSelect filters / JS preserved verbatim.    ║
        ╚══════════════════════════════════════════════════════════════════════╝
    --}}
    @once
        <style>
            .propx{
                --acc:var(--bs-primary); --acc-rgb:var(--bs-primary-rgb);
                --acc-d:color-mix(in srgb, var(--bs-primary), #000 14%);
                --acc-deep:color-mix(in srgb, var(--bs-primary), #000 42%);
                --acc-tint:color-mix(in srgb, var(--bs-primary), transparent 90%);
                --surface:#ffffff; --surface-2:#f5f7fa;
                --ink:var(--bs-emphasis-color); --ink-2:var(--bs-body-color);
                --muted:var(--bs-secondary-color); --faint:var(--bs-tertiary-color);
                --line:#e7ebf1; --line-soft:#eff2f6;
                --ok:var(--bs-success); --ok-rgb:var(--bs-success-rgb);
                --info:var(--bs-info); --info-rgb:var(--bs-info-rgb);
                --warn:var(--bs-warning); --warn-rgb:var(--bs-warning-rgb);
                --bad:var(--bs-danger); --bad-rgb:var(--bs-danger-rgb);
                --shadow:0 1px 2px rgba(16,24,40,.05), 0 8px 24px -10px rgba(16,24,40,.12);
                --shadow-lg:0 18px 42px -18px rgba(var(--acc-rgb),.40), 0 8px 18px -12px rgba(16,24,40,.20);
                color:var(--ink); font-size:12.5px; line-height:1.5; -webkit-font-smoothing:antialiased;
            }
            .propx *{ box-sizing:border-box; }
            [data-bs-theme="dark"] .propx{
                --surface:#272d34; --surface-2:#2e353d; --line:#3a424c; --line-soft:#343c45;
                --acc-tint:color-mix(in srgb, var(--bs-primary), transparent 84%);
                --shadow:0 1px 2px rgba(0,0,0,.4), 0 10px 28px -10px rgba(0,0,0,.5);
                --shadow-lg:0 18px 44px -18px rgba(0,0,0,.6), 0 8px 18px -12px rgba(0,0,0,.5);
            }
            .propx .p-card{ background:var(--surface); border:1px solid var(--line); border-radius:16px; box-shadow:var(--shadow); overflow:hidden; }

            /* HERO */
            .propx-hero{ position:relative; border-radius:16px; overflow:hidden; margin-bottom:12px; box-shadow:var(--shadow-lg);
                background:radial-gradient(120% 165% at 100% 0, color-mix(in srgb, var(--acc) 28%, transparent), transparent 55%),
                          linear-gradient(125deg, var(--acc-deep), var(--acc-d)); }
            .propx-hero .glow{ position:absolute; right:-60px; top:-90px; width:300px; height:300px; border-radius:50%;
                background:radial-gradient(circle, rgba(255,255,255,.16), transparent 65%); pointer-events:none; }
            .propx-hero-inner{ position:relative; display:flex; align-items:center; gap:15px; padding:15px 18px; flex-wrap:wrap; }
            .propx-hero .doc-ic{ width:46px; height:46px; border-radius:13px; flex:0 0 auto; background:rgba(255,255,255,.14);
                border:1px solid rgba(255,255,255,.22); display:flex; align-items:center; justify-content:center; font-size:20px; color:#fff;
                box-shadow:inset 0 1px 0 rgba(255,255,255,.25); }
            .propx-hero .h-main{ flex:1; min-width:200px; color:#fff; }
            .propx-hero .h-eyebrow{ font-size:9.5px; font-weight:700; letter-spacing:2px; text-transform:uppercase; color:rgba(255,255,255,.72); }
            .propx-hero .h-ref{ font-size:20px; font-weight:800; letter-spacing:.3px; line-height:1.15; margin-top:2px; }
            .propx-hero .h-right{ display:flex; gap:8px; flex-wrap:wrap; }
            .propx-hero .btn-hero{ background:#fff; color:var(--acc-deep); border:0; padding:8px 14px; border-radius:9px; font-size:11.5px; font-weight:700;
                cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; gap:6px; box-shadow:0 4px 12px rgba(0,0,0,.18); transition:transform .12s; }
            .propx-hero .btn-hero:hover{ transform:translateY(-1px); color:var(--acc-deep); }
            .propx-hero .btn-hero.ghost{ background:rgba(255,255,255,.14); color:#fff; border:1px solid rgba(255,255,255,.28); box-shadow:none; }
            .propx-hero .btn-hero.ghost:hover{ color:#fff; }
            .propx-hstats{ position:relative; display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:1px;
                background:rgba(255,255,255,.14); border-top:1px solid rgba(255,255,255,.14); }
            .propx-hs{ background:linear-gradient(180deg, rgba(255,255,255,.05), transparent); padding:10px 16px; }
            .propx-hs .s-k{ font-size:9px; font-weight:700; letter-spacing:.8px; text-transform:uppercase; color:rgba(255,255,255,.66); display:flex; align-items:center; gap:5px; }
            .propx-hs .s-v{ font-size:16px; font-weight:800; color:#fff; margin-top:3px; letter-spacing:.2px; display:flex; align-items:center; }
            .propx-hs .s-v .dot{ width:8px; height:8px; border-radius:50%; display:inline-block; margin-right:6px; }

            /* TOOLBAR + FILTERS */
            .propx .p-toolbar{ display:flex; align-items:center; gap:10px; flex-wrap:wrap; padding:13px 16px; border-bottom:1px solid var(--line-soft); }
            .propx .p-search{ flex:1; min-width:180px; display:flex; align-items:center; gap:8px; background:var(--surface-2); border:1px solid var(--line); border-radius:10px; padding:7px 12px; }
            .propx .p-search i{ color:var(--muted); }
            .propx .p-search input{ border:0; background:transparent; outline:none; width:100%; font-size:12.5px; color:var(--ink); }
            .propx .btn-x{ display:inline-flex; align-items:center; gap:6px; border:1px solid var(--line); background:var(--surface); color:var(--ink-2); padding:8px 12px; border-radius:10px; font-size:11.5px; font-weight:700; cursor:pointer; line-height:1; }
            .propx .btn-x:hover{ border-color:var(--acc); color:var(--acc-d); }
            .propx .btn-x.ok{ color:var(--ok); } .propx .btn-x.bad{ color:var(--bad); }
            .propx .p-sel{ appearance:none; border:1px solid var(--line); background:var(--surface); color:var(--ink); padding:8px 30px 8px 12px; border-radius:10px; font-size:11.5px; font-weight:600; cursor:pointer;
                background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23888' stroke-width='3' stroke-linecap='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 10px center; }

            .propx .p-filters{ display:grid; grid-template-columns:repeat(auto-fit,minmax(155px,1fr)); gap:10px; padding:14px 16px; border-bottom:1px solid var(--line-soft); background:color-mix(in srgb, var(--acc) 3%, var(--surface)); }
            .propx .f-item label{ font-size:10px; font-weight:750; letter-spacing:.5px; text-transform:uppercase; color:var(--muted); display:flex; align-items:center; gap:6px; margin-bottom:5px; }
            .propx .f-item label i{ color:var(--acc); }
            .propx .f-item select{ width:100%; }
            .propx .f-item .ts-wrapper{ font-size:11.5px; }

            /* Columns dropdown (bootstrap) reskin */
            .propx .cols-menu{ background:var(--surface); border:1px solid var(--line); border-radius:12px; box-shadow:var(--shadow-lg); padding:8px; min-width:210px; }
            .propx .cols-menu .dh{ font-size:9.5px; font-weight:750; letter-spacing:.6px; text-transform:uppercase; color:var(--muted); padding:4px 8px; }
            .propx .cols-menu .form-check{ padding:5px 8px 5px 30px; border-radius:8px; margin:0; }
            .propx .cols-menu .form-check:hover{ background:var(--surface-2); }
            .propx .cols-menu .form-check-label{ color:var(--ink); font-weight:600; }
            .propx .cols-menu .form-check-input{ accent-color:var(--acc); }
            .propx .cols-menu .cols-reset{ display:block; width:100%; margin-top:6px; padding:6px 8px; border:0; border-top:1px solid var(--line-soft); border-radius:0 0 8px 8px;
                background:transparent; text-align:left; font-size:11px; font-weight:700; color:var(--muted); cursor:pointer; }
            .propx .cols-menu .cols-reset:hover{ background:var(--surface-2); color:var(--ink); }

            /* TABLE */
            .propx .p-tblwrap{ overflow-x:auto; }
            .propx table.p-tbl{ width:100%; border-collapse:collapse; font-size:12.5px; }
            .propx table.p-tbl thead th{ background:var(--surface-2); color:var(--muted); font-size:10px; font-weight:750; letter-spacing:.6px; text-transform:uppercase; text-align:left; padding:10px 12px; border-bottom:1px solid var(--line); white-space:nowrap; }
            .propx table.p-tbl thead th a{ color:var(--muted) !important; text-decoration:none; }
            .propx table.p-tbl thead th a:hover{ color:var(--acc-d) !important; }
            .propx table.p-tbl tbody td{ padding:10px 12px; border-bottom:1px solid var(--line-soft); vertical-align:middle; color:var(--ink-2); }
            .propx table.p-tbl tbody tr{ transition:background .12s; }
            .propx table.p-tbl tbody tr:hover{ background:var(--acc-tint); }
            .propx .num-cell{ display:flex; align-items:center; gap:9px; }
            .propx .num-ic{ width:30px; height:30px; border-radius:9px; background:var(--acc-tint); color:var(--acc-d); display:flex; align-items:center; justify-content:center; font-size:13px; flex:0 0 auto; }
            .propx .num-cell .nm{ font-weight:750; color:var(--ink); }
            .propx .num-cell .sub{ font-size:10.5px; color:var(--muted); }
            .propx .chip{ display:inline-flex; align-items:center; gap:5px; padding:3px 9px; border-radius:999px; font-size:10.5px; font-weight:700; letter-spacing:.2px; }
            .propx .chip.soft{ background:var(--surface-2); color:var(--ink-2); border:1px solid var(--line); }
            .propx .chip.acc{ background:var(--acc-tint); color:var(--acc-d); }
            .propx .st{ display:inline-flex; align-items:center; gap:6px; padding:4px 11px; border-radius:999px; font-size:10.5px; font-weight:750; letter-spacing:.3px; text-transform:capitalize; }
            .propx .st .dot{ width:7px; height:7px; border-radius:50%; }
            .propx .st.occupied{ background:rgba(var(--bad-rgb),.12); color:var(--bad); } .propx .st.occupied .dot{ background:var(--bad); }
            .propx .st.vacant{ background:rgba(var(--ok-rgb),.13); color:var(--ok); } .propx .st.vacant .dot{ background:var(--ok); }
            .propx .st.booked{ background:rgba(var(--warn-rgb),.14); color:var(--warn); } .propx .st.booked .dot{ background:var(--warn); }
            .propx .st.sold{ background:rgba(var(--info-rgb),.13); color:var(--info); } .propx .st.sold .dot{ background:var(--info); }
            .propx .rent{ font-weight:750; color:var(--ink); }
            .propx .faint{ color:var(--faint); }
            .propx .act-btn{ width:30px; height:30px; border-radius:8px; border:1px solid var(--line); background:var(--surface); color:var(--acc-d); cursor:pointer; display:inline-flex; align-items:center; justify-content:center; }
            .propx .act-btn:hover{ background:var(--acc); color:#fff; border-color:var(--acc); }
            .propx .p-check{ width:15px; height:15px; accent-color:var(--acc); cursor:pointer; }

            /* FOOTER / pagination */
            .propx .p-foot{ padding:10px 16px; }
            .propx .p-foot .pagination{ margin:0; }
            .propx .p-foot .page-link{ border:1px solid var(--line); color:var(--ink-2); background:var(--surface); font-size:12px; font-weight:700; }
            .propx .p-foot .page-item.active .page-link{ background:var(--acc); border-color:var(--acc); color:#fff; }
            .propx .p-foot .page-item.disabled .page-link{ opacity:.5; }
        </style>
    @endonce

    <div class="propx">

        {{-- ── HERO ────────────────────────────────────────────────── --}}
        <div class="propx-hero">
            <div class="glow"></div>
            <div class="propx-hero-inner">
                <div class="doc-ic"><i class="fa fa-building"></i></div>
                <div class="h-main">
                    <div class="h-eyebrow">Real Estate · Portfolio</div>
                    <div class="h-ref">Properties</div>
                </div>
                @can('property.create')
                    <div class="h-right">
                        <a href="{{ route('property::property::import') }}" class="btn-hero ghost">
                            <i class="fa fa-cloud-upload"></i> Import
                        </a>
                        <button type="button" class="btn-hero" id="PropertyAdd">
                            <i class="fa fa-plus-circle"></i> Add Property
                        </button>
                    </div>
                @endcan
            </div>
            <div class="propx-hstats">
                <div class="propx-hs"><div class="s-k"><i class="fa fa-th-large"></i>Total</div><div class="s-v">{{ number_format($stats['total']) }}</div></div>
                <div class="propx-hs"><div class="s-k"><i class="fa fa-check-circle"></i>Vacant</div><div class="s-v"><span class="dot" style="background:var(--bs-success)"></span>{{ number_format($stats['vacant']) }}</div></div>
                <div class="propx-hs"><div class="s-k"><i class="fa fa-user"></i>Occupied</div><div class="s-v"><span class="dot" style="background:var(--bs-danger)"></span>{{ number_format($stats['occupied']) }}</div></div>
                <div class="propx-hs"><div class="s-k"><i class="fa fa-bookmark"></i>Booked</div><div class="s-v"><span class="dot" style="background:var(--bs-warning)"></span>{{ number_format($stats['booked']) }}</div></div>
                <div class="propx-hs"><div class="s-k"><i class="fa fa-check-circle-o"></i>Available</div><div class="s-v"><span class="dot" style="background:var(--bs-success)"></span>{{ number_format($stats['available']) }}</div></div>
                <div class="propx-hs"><div class="s-k"><i class="fa fa-handshake-o"></i>Sold</div><div class="s-v"><span class="dot" style="background:var(--bs-info)"></span>{{ number_format($stats['sold']) }}</div></div>
            </div>
        </div>

        {{-- ── MAIN CARD ───────────────────────────────────────────── --}}
        <div class="p-card">

            {{-- toolbar --}}
            <div class="p-toolbar">
                <div class="p-search">
                    <i class="fa fa-search"></i>
                    <input type="text" wire:model.live="search" autofocus autocomplete="off" placeholder="Search by number, floor, building, type…">
                </div>

                <select wire:model.live="limit" class="p-sel" title="Rows per page">
                    <option value="10">Show 10</option>
                    <option value="100">Show 100</option>
                    <option value="500">Show 500</option>
                </select>

                <div class="dropdown">
                    <button class="btn-x" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" title="Show / hide columns">
                        <i class="fa fa-columns"></i> <span class="d-none d-md-inline">Columns</span>
                    </button>
                    <ul class="dropdown-menu cols-menu">
                        <li class="dh">Visible Columns</li>
                        @foreach($columnLabels as $key => $label)
                            <li>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="col-{{ $key }}" wire:model.live="columns.{{ $key }}">
                                    <label class="form-check-label" for="col-{{ $key }}">{{ $label }}</label>
                                </div>
                            </li>
                        @endforeach
                        <li>
                            <button type="button" class="cols-reset" wire:click="resetColumns">
                                <i class="fa fa-undo"></i> Reset to default
                            </button>
                        </li>
                    </ul>
                </div>

                @can('property.view')
                    <button class="btn-x ok" title="Export to Excel" wire:click="export()">
                        <i class="fa fa-file-excel-o"></i> <span class="d-none d-md-inline">Export</span>
                    </button>
                @endcan
                @can('property.delete')
                    <button class="btn-x bad" title="Delete Selected" wire:click="delete()"
                        wire:confirm="Are you sure you want to delete the selected items?">
                        <i class="fa fa-trash"></i> <span class="d-none d-md-inline">Delete</span>
                    </button>
                @endcan
            </div>

            {{-- filters --}}
            <div class="p-filters">
                <div class="f-item" wire:ignore>
                    <label><i class="fa fa-object-group"></i> Group / Project</label>
                    <select class="p-sel select-filter-property_group_id" id="filterGroup">
                        <option value="">All Groups</option>
                        @foreach(\App\Models\PropertyGroup::orderBy('name')->get() as $group)
                            <option value="{{ $group->id }}">{{ $group->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="f-item" wire:ignore>
                    <label><i class="fa fa-building"></i> Building</label>
                    <select class="p-sel select-filter-property_building_id" id="filterBuilding">
                        <option value="">All Buildings</option>
                        @foreach(\App\Models\PropertyBuilding::orderBy('name')->get() as $building)
                            <option value="{{ $building->id }}">{{ $building->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="f-item" wire:ignore>
                    <label><i class="fa fa-tags"></i> Type</label>
                    <select class="p-sel select-filter-property_type_id" id="filterType">
                        <option value="">All Types</option>
                        @foreach(\App\Models\PropertyType::orderBy('name')->get() as $type)
                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="f-item">
                    <label><i class="fa fa-circle"></i> Status</label>
                    {{ html()->select('filterStatus', propertyStatusOptions())->value($filterStatus)->class('p-sel')->id('filterStatus')->attribute('wire:model.live', 'filterStatus')->placeholder('All Status') }}
                </div>
                <div class="f-item">
                    <label><i class="fa fa-check-circle"></i> Availability</label>
                    <select class="p-sel" wire:model.live="filterAvailabilityStatus" id="filterAvailabilityStatus">
                        <option value="">All</option>
                        <option value="available">Available</option>
                        <option value="sold">Sold</option>
                    </select>
                </div>
                <div class="f-item">
                    <label><i class="fa fa-key"></i> Ownership</label>
                    <select class="p-sel" wire:model.live="filterOwnership" id="filterOwnership">
                        <option value="">All</option>
                        <option value="Owner">Owner</option>
                        <option value="Tenant">Tenant</option>
                    </select>
                </div>
                <div class="f-item">
                    <label><i class="fa fa-flag"></i> Flag</label>
                    <select class="p-sel" wire:model.live="filterFlag" id="filterFlag">
                        <option value="">All</option>
                        <option value="active">Active</option>
                        <option value="disabled">Disabled</option>
                    </select>
                </div>
            </div>

            {{-- table --}}
            <div class="p-tblwrap">
                <table class="p-tbl">
                    <thead>
                        <tr>
                            <th style="width:34px">
                                <input type="checkbox" wire:model.live="selectAll" class="p-check" id="selectAllCheckbox" />
                            </th>
                            <th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="name" label="Number" /></th>
                            @if($columns['type'])<th>Type</th>@endif
                            @if($columns['group'])<th>Group</th>@endif
                            @if($columns['building'])<th>Building</th>@endif
                            @if($columns['floor'])<th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="floor" label="Floor" /></th>@endif
                            @if($columns['rent'])<th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="rent" label="Rent" /></th>@endif
                            @if($columns['ownership'])<th>Ownership</th>@endif
                            @if($columns['kahramaa'])<th>Kahramaa</th>@endif
                            @if($columns['parking'])<th>Parking</th>@endif
                            @if($columns['status'])<th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="status" label="Status" /></th>@endif
                            @if($columns['availability'])<th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="availability_status" label="Availability" /></th>@endif
                            <th style="text-align:center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data as $item)
                            <tr>
                                <td>
                                    <input type="checkbox" value="{{ $item->id }}" wire:model.live="selected" class="p-check" id="checkbox{{ $item->id }}" />
                                </td>
                                <td>
                                    <div class="num-cell">
                                        <div class="num-ic"><i class="fa fa-home"></i></div>
                                        <div>
                                            <div class="nm">
                                                <a href="{{ route('property::property::view', $item->id) }}" class="text-reset text-decoration-none">{{ $item->number }}</a>
                                            </div>
                                            @if($item->unit_no)<div class="sub">Unit {{ $item->unit_no }}</div>@endif
                                        </div>
                                    </div>
                                </td>
                                @if($columns['type'])
                                <td>
                                    @if($item->type?->name)
                                        <span class="chip soft"><i class="fa fa-tag"></i>{{ $item->type->name }}</span>
                                    @else
                                        <span class="faint">—</span>
                                    @endif
                                </td>
                                @endif
                                @if($columns['group'])
                                <td>@if($item->building?->group?->name){{ $item->building->group->name }}@else<span class="faint">—</span>@endif</td>
                                @endif
                                @if($columns['building'])
                                <td>@if($item->building?->name){{ $item->building->name }}@else<span class="faint">—</span>@endif</td>
                                @endif
                                @if($columns['floor'])
                                <td>@if($item->floor){{ $item->floor }}@else<span class="faint">—</span>@endif</td>
                                @endif
                                @if($columns['rent'])
                                <td class="rent">{{ number_format($item->rent, 2) }}</td>
                                @endif
                                @if($columns['ownership'])
                                <td>
                                    @if($item->ownership)
                                        <span class="chip acc">{{ $item->ownership }}</span>
                                    @else
                                        <span class="faint">—</span>
                                    @endif
                                </td>
                                @endif
                                @if($columns['kahramaa'])
                                <td>@if($item->kahramaa){{ $item->kahramaa }}@else<span class="faint">—</span>@endif</td>
                                @endif
                                @if($columns['parking'])
                                <td>
                                    @if($item->parking)
                                        <i class="fa fa-car faint"></i> {{ $item->parking }}
                                    @else
                                        <span class="faint">—</span>
                                    @endif
                                </td>
                                @endif
                                @if($columns['status'])
                                <td>
                                    @if($item->status)
                                        <span class="st {{ $item->status->value }}"><span class="dot"></span>{{ $item->status->label() }}</span>
                                    @endif
                                </td>
                                @endif
                                @if($columns['availability'])
                                <td>
                                    @if($item->availability_status)
                                        <span class="st {{ $item->availability_status === 'sold' ? 'sold' : 'vacant' }}"><span class="dot"></span>{{ ucfirst($item->availability_status) }}</span>
                                    @else
                                        <span class="faint">—</span>
                                    @endif
                                </td>
                                @endif
                                <td style="text-align:center">
                                    <a href="{{ route('property::property::view', $item->id) }}" class="act-btn" title="View" data-bs-toggle="tooltip">
                                        <i class="fa fa-eye"></i>
                                    </a>
                                    @can('property.edit')
                                        <button table_id="{{ $item->id }}" class="act-btn edit" title="Edit" data-bs-toggle="tooltip">
                                            <i class="fa fa-pencil"></i>
                                        </button>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ 3 + collect($columns)->filter()->count() }}" style="text-align:center; padding:48px 16px;">
                                    <i class="fa fa-building-o fa-3x d-block mb-3" style="opacity:.2"></i>
                                    <span class="faint">No properties found matching your search.</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- footer / pagination --}}
            <div class="p-foot border-top" style="border-color:var(--line-soft) !important;">
                {{ $data->links() }}
            </div>
        </div>
    </div>

    <!-- Floating action button for mobile -->
    @can('property.create')
        <div class="position-fixed bottom-0 end-0 mb-4 me-4 d-md-none" style="z-index:1030">
            <button id="PropertyAddMobile" class="btn btn-primary rounded-circle shadow btn-lg">
                <i class="fa fa-plus"></i>
            </button>
        </div>
    @endcan

    @push('scripts')
        <script>
            $(document).ready(function() {
                // Initialize tooltips
                const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.map(function(tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl, {
                        boundary: document.body
                    });
                });

                // Filter TomSelect change handlers
                $('#filterGroup').on('change', function() {
                    @this.set('filterGroup', $(this).val());
                });
                $('#filterBuilding').on('change', function() {
                    @this.set('filterBuilding', $(this).val());
                });
                $('#filterType').on('change', function() {
                    @this.set('filterType', $(this).val());
                });

                $(document).on('click', '.edit', function() {
                    Livewire.dispatch("Property-Page-Update-Component", {
                        id: $(this).attr('table_id')
                    });
                });

                $('#PropertyAdd, #PropertyAddMobile').click(function() {
                    Livewire.dispatch("Property-Page-Create-Component");
                });

                window.addEventListener('RefreshPropertyTable', event => {
                    Livewire.dispatch("Property-Refresh-Component");
                });
            });
        </script>
    @endpush
</div>
