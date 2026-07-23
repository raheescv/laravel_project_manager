{{--
    ╔══════════════════════════════════════════════════════════════════════╗
    ║  Property View — "Command Workspace"                                  ║
    ║                                                                       ║
    ║  Bootstrap 5.3 only: cards, nav-pills tabs, tables, progress, badges  ║
    ║  and utilities. The @once block below holds the handful of effects    ║
    ║  Bootstrap has no class for (hero gradient, deck overlap, sticky tab   ║
    ║  bar, avatar) — everything is driven by --bs-* tokens so it tracks     ║
    ║  the settings colour scheme and dark mode automatically.              ║
    ╚══════════════════════════════════════════════════════════════════════╝
--}}
<div>
    @once
        <style>
            .pv-hero {
                background:
                    radial-gradient(120% 160% at 10% -20%, rgba(255, 255, 255, .18), transparent 52%),
                    linear-gradient(118deg,
                        color-mix(in srgb, var(--bs-primary), #000 42%) 0%,
                        var(--bs-primary) 58%,
                        color-mix(in srgb, var(--bs-primary), #fff 12%) 130%);
            }
            .pv-hero::after {
                content: ""; position: absolute; inset: 0; opacity: .5; pointer-events: none;
                background-image: radial-gradient(circle at 1px 1px, rgba(255, 255, 255, .11) 1px, transparent 0);
                background-size: 22px 22px;
                -webkit-mask-image: linear-gradient(180deg, #000, transparent 72%);
                mask-image: linear-gradient(180deg, #000, transparent 72%);
            }
            .pv-deck { margin-top: -3.25rem; }
            .pv-tabs { top: .5rem; z-index: 3; }
            .pv-av {
                width: 34px; height: 34px; flex: 0 0 auto; font-size: .72rem;
                background: linear-gradient(140deg, var(--bs-primary), color-mix(in srgb, var(--bs-primary), #fff 28%));
            }
            .pv-av-lg { width: 42px; height: 42px; font-size: .85rem; }
            .pv-plan { max-height: 320px; object-fit: contain; }
            @media (max-width: 767.98px) { .pv-deck { margin-top: 1rem; } }
        </style>
    @endonce

    @php
        $property = $this->property;
    @endphp

    @if (! $property)
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="fa fa-exclamation-triangle text-warning fs-1 mb-3 d-block"></i>
                <p class="text-muted mb-3">Property not found.</p>
                <a href="{{ route('property::property::index') }}" class="btn btn-primary btn-sm">Back to Properties</a>
            </div>
        </div>
    @else
        @php
            $stats = $this->stats;
            $current = $this->current;
            $agreements = $this->agreements;
            $maintenances = $this->maintenances;
            $documents = $this->documents;
            $occupants = $property->tenantDetails;

            $statusColors = ['occupied' => 'success', 'vacant' => 'secondary', 'booked' => 'info', 'sold' => 'primary'];
            $statusColor = $statusColors[$property->status?->value] ?? 'secondary';
            $openMaintenance = $maintenances->filter(fn ($m) => in_array($m->status?->value, ['pending', 'in_progress'], true));
            $layout = collect([
                'rooms' => ['fa-bed', 'Rooms'],
                'toilet' => ['fa-tint', 'Bath'],
                'kitchen' => ['fa-cutlery', 'Kitchen'],
                'hall' => ['fa-th', 'Hall'],
            ]);
        @endphp

        {{-- ═══════════════════════════  HERO  ═══════════════════════════ --}}
        <header class="pv-hero position-relative overflow-hidden rounded-4 shadow text-white px-3 px-lg-4 pt-3"
            style="padding-bottom:4.5rem">
            <nav aria-label="breadcrumb" class="position-relative">
                <ol class="breadcrumb small mb-3">
                    <li class="breadcrumb-item"><a href="{{ url('/') }}" class="link-light text-decoration-none"><i class="fa fa-home"></i> Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('property::property::index') }}" class="link-light text-decoration-none">Properties</a></li>
                    <li class="breadcrumb-item active text-white fw-semibold" aria-current="page">{{ $property->number }}</li>
                </ol>
            </nav>

            <div class="row g-4 align-items-end position-relative">
                <div class="col-lg-7">
                    <div class="d-flex flex-wrap gap-2 mb-2">
                        @if ($property->type?->name)
                            <span class="badge rounded-pill bg-white bg-opacity-10 border border-white border-opacity-25 text-white text-uppercase">
                                <i class="fa fa-cube"></i> {{ $property->type->name }}
                            </span>
                        @endif
                        @if ($property->ownership)
                            <span class="badge rounded-pill bg-white bg-opacity-10 border border-white border-opacity-25 text-white text-uppercase">
                                <i class="fa fa-certificate"></i> {{ $property->ownership }}
                            </span>
                        @endif
                        @if ($property->status)
                            <span class="badge rounded-pill text-bg-light text-uppercase">
                                <i class="fa fa-circle text-{{ $statusColor }}" style="font-size:.5rem"></i>
                                {{ ucfirst($property->status->label()) }}
                            </span>
                        @endif
                    </div>

                    <h1 class="h2 fw-bold mb-1 text-white">
                        {{ $property->number }}
                        @if ($property->building?->name)
                            <span class="text-white-50 fw-semibold">· {{ $property->building->name }}</span>
                        @endif
                    </h1>

                    <div class="d-flex flex-wrap gap-3 small text-white-50">
                        @if ($property->group?->name)
                            <span><i class="fa fa-map-marker"></i> {{ $property->group->name }}</span>
                        @endif
                        @if ($property->floor)
                            <span><i class="fa fa-level-up"></i> Floor {{ $property->floor }}</span>
                        @endif
                        @if ($property->size)
                            <span><i class="fa fa-arrows-alt"></i> {{ currency($property->size) }} m²</span>
                        @endif
                        @if ($property->parking)
                            <span><i class="fa fa-car"></i> Parking {{ $property->parking }}</span>
                        @endif
                        @if ($property->kahramaa)
                            <span><i class="fa fa-bolt"></i> Kahramaa {{ $property->kahramaa }}</span>
                        @endif
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="d-flex flex-wrap gap-2 justify-content-lg-end mb-3">
                        @if ($property->floor_plan)
                            <button type="button" class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#PropertyFloorPlanModal">
                                <i class="fa fa-picture-o"></i> Floor Plan
                            </button>
                        @endif
                        @can('maintenance.create')
                            <a href="{{ route('property::maintenance::create') }}" class="btn btn-sm btn-light">
                                <i class="fa fa-wrench"></i> Log Maintenance
                            </a>
                        @endcan
                        @can('property.edit')
                            <button type="button" class="btn btn-sm btn-light"
                                wire:click="$dispatch('Property-Page-Update-Component', { id: {{ $property->id }} })">
                                <i class="fa fa-pencil"></i> Edit
                            </button>
                        @endcan
                    </div>

                    @if ($current)
                        <div class="d-flex justify-content-between align-items-center small text-white-50 mb-1">
                            <span><i class="fa fa-calendar"></i> Current tenancy elapsed</span>
                            <span class="fw-bold text-white">{{ $current['elapsed'] }}%</span>
                        </div>
                        <div class="progress bg-white bg-opacity-25" role="progressbar"
                            aria-label="Tenancy elapsed" aria-valuenow="{{ $current['elapsed'] }}" aria-valuemin="0" aria-valuemax="100"
                            style="height:7px">
                            <div class="progress-bar bg-white" style="width: {{ $current['elapsed'] }}%"></div>
                        </div>
                        <div class="small text-white-50 mt-1">
                            {{ $current['agreement']->agreement_no }} ·
                            {{ systemDate($current['agreement']->start_date) }} → {{ systemDate($current['agreement']->end_date) }}
                            @if ($current['days_left'] >= 0)
                                · {{ $current['days_left'] }} days to expiry
                            @else
                                · <span class="text-warning">{{ abs($current['days_left']) }} days past end date</span>
                            @endif
                        </div>
                    @else
                        <div class="small text-white-50">
                            <i class="fa fa-info-circle"></i> No live agreement on this unit.
                        </div>
                    @endif
                </div>
            </div>
        </header>

        {{-- ═══════════════════════════  KPI DECK  ═══════════════════════════ --}}
        <div class="row g-3 pv-deck position-relative px-2 px-lg-3">
            @php
                $kpis = [
                    ['primary', 'fa-money', 'Contracted Rent', currency($property->rent), 'per month · ' . currency($property->rent * 12) . ' / year', null, null],
                    ['success', 'fa-pie-chart', 'Occupancy', $stats['occupancy'] . '%', number_format($stats['let_days']) . ' of ' . number_format($stats['tracked_days']) . ' days let',
                        $stats['gap_count'] ? $stats['gap_count'] . ' gaps · ' . $stats['gap_days'] . ' vacant days' : 'no vacancy gaps', $stats['gap_count'] ? 'danger' : 'success'],
                    ['info', 'fa-line-chart', 'Lifetime Revenue', currency($stats['collected']), 'across ' . $stats['tenancies'] . ' ' . str('tenancy')->plural($stats['tenancies']),
                        $stats['avg_rent'] ? 'avg ' . currency($stats['avg_rent']) . ' / month' : null, 'info'],
                    ['danger', 'fa-clock-o', 'Outstanding', currency($stats['outstanding']), 'balance due on agreements',
                        $stats['outstanding'] > 0 ? 'action needed' : 'all settled', $stats['outstanding'] > 0 ? 'danger' : 'success'],
                ];
            @endphp

            @foreach ($kpis as [$tone, $icon, $label, $value, $sub, $chip, $chipTone])
                <div class="col-6 col-xl-3">
                    <div class="card border-0 border-start border-4 border-{{ $tone }} shadow-sm h-100">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-start justify-content-between gap-2">
                                <div class="min-w-0">
                                    <div class="text-uppercase text-body-tertiary fw-bold" style="font-size:.65rem;letter-spacing:.06em">{{ $label }}</div>
                                    <div class="fs-4 fw-bold lh-1 mt-1 font-monospace">{{ $value }}</div>
                                    <div class="small text-body-secondary mt-1">{{ $sub }}</div>
                                </div>
                                <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-{{ $tone }}-subtle text-{{ $tone }}-emphasis"
                                    style="width:34px;height:34px"><i class="fa {{ $icon }}"></i></span>
                            </div>
                            @if ($chip)
                                <span class="badge rounded-pill bg-{{ $chipTone }}-subtle text-{{ $chipTone }}-emphasis mt-2">{{ $chip }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- ═══════════════════════════  WORKSPACE TABS  ═══════════════════════════ --}}
        <ul class="nav nav-pills gap-1 bg-body-tertiary border rounded-3 p-1 mt-3 mb-3 sticky-top pv-tabs flex-nowrap overflow-auto"
            id="propertyViewTabs" role="tablist">
            @php
                $tabs = [
                    ['overview', 'fa-th-large', 'Overview', null],
                    ['tenancies', 'fa-history', 'Tenancies', $agreements->count()],
                    ['occupants', 'fa-users', 'Occupants', $occupants->count()],
                    ['maintenance', 'fa-wrench', 'Maintenance', $maintenances->count()],
                    ['documents', 'fa-paperclip', 'Documents', $documents->count()],
                    ['activity', 'fa-clock-o', 'Activity', null],
                ];
            @endphp
            @foreach ($tabs as [$key, $icon, $label, $count])
                <li class="nav-item" role="presentation">
                    <button class="nav-link text-nowrap py-2 px-3 {{ $loop->first ? 'active' : '' }}" id="pv-{{ $key }}-tab"
                        data-bs-toggle="pill" data-bs-target="#pv-{{ $key }}" type="button" role="tab"
                        aria-controls="pv-{{ $key }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                        <i class="fa {{ $icon }}"></i> {{ $label }}
                        @if ($count)
                            <span class="badge rounded-pill bg-secondary-subtle text-secondary-emphasis ms-1">{{ $count }}</span>
                        @endif
                    </button>
                </li>
            @endforeach
        </ul>

        <div class="tab-content" id="propertyViewTabContent">

            {{-- ─────────────────────────  OVERVIEW  ───────────────────────── --}}
            <div class="tab-pane fade show active" id="pv-overview" role="tabpanel" aria-labelledby="pv-overview-tab" tabindex="0">
                <div class="row g-3">
                    {{-- Identity & Location --}}
                    <div class="col-lg-6 col-xxl-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-transparent d-flex align-items-center gap-2 py-2">
                                <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-primary-subtle text-primary-emphasis"
                                    style="width:28px;height:28px"><i class="fa fa-sitemap"></i></span>
                                <div>
                                    <h6 class="mb-0 fw-bold">Identity &amp; Location</h6>
                                    <small class="text-body-tertiary">Where this unit sits</small>
                                </div>
                            </div>
                            <ul class="list-group list-group-flush small">
                                @foreach ([
                                    ['fa-th-large', 'Group', $property->group?->name],
                                    ['fa-building', 'Building', $property->building?->name],
                                    ['fa-cubes', 'Type', $property->type?->name],
                                    ['fa-tag', 'Unit No', $property->unit_no ?: $property->number],
                                    ['fa-barcode', 'Code', $property->code],
                                    ['fa-level-up', 'Floor', $property->floor],
                                    ['fa-certificate', 'Ownership', $property->ownership],
                                ] as [$icon, $label, $value])
                                    <li class="list-group-item d-flex justify-content-between align-items-center gap-2 bg-transparent">
                                        <span class="text-body-secondary"><i class="fa {{ $icon }} fa-fw text-body-tertiary"></i> {{ $label }}</span>
                                        <span class="fw-semibold text-end">{{ $value ?: '—' }}</span>
                                    </li>
                                @endforeach
                                <li class="list-group-item d-flex justify-content-between align-items-center gap-2 bg-transparent">
                                    <span class="text-body-secondary"><i class="fa fa-dot-circle-o fa-fw text-body-tertiary"></i> Availability</span>
                                    <span class="badge bg-secondary-subtle text-secondary-emphasis">{{ ucfirst($property->availability_status ?: '—') }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center gap-2 bg-transparent">
                                    <span class="text-body-secondary"><i class="fa fa-square-o fa-fw text-body-tertiary"></i> Flag</span>
                                    <span class="badge bg-{{ $property->flag === 'active' ? 'success' : 'secondary' }}-subtle text-{{ $property->flag === 'active' ? 'success' : 'secondary' }}-emphasis">
                                        {{ ucfirst($property->flag ?: '—') }}
                                    </span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    {{-- Unit Specification --}}
                    <div class="col-lg-6 col-xxl-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-transparent d-flex align-items-center gap-2 py-2">
                                <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-primary-subtle text-primary-emphasis"
                                    style="width:28px;height:28px"><i class="fa fa-cube"></i></span>
                                <div>
                                    <h6 class="mb-0 fw-bold">Unit Specification</h6>
                                    <small class="text-body-tertiary">Layout &amp; utilities</small>
                                </div>
                            </div>
                            {{-- Layout counts: one hairline-divided strip rather than four boxes. --}}
                            <div class="card-body py-2">
                                <div class="d-flex border rounded-3 overflow-hidden">
                                    @foreach ($layout as $field => [$icon, $label])
                                        @php $count = (int) ($property->{$field} ?? 0); @endphp
                                        <div class="flex-fill text-center py-2 px-1 {{ $loop->last ? '' : 'border-end' }}">
                                            <div class="d-flex align-items-center justify-content-center gap-1 lh-1">
                                                <i class="fa {{ $icon }} {{ $count ? 'text-primary' : 'text-body-tertiary' }}" style="font-size:.8rem"></i>
                                                <span class="fw-bold {{ $count ? '' : 'text-body-tertiary' }}">{{ $count }}</span>
                                            </div>
                                            <div class="text-uppercase text-body-tertiary fw-semibold mt-1"
                                                style="font-size:.6rem;letter-spacing:.06em">{{ $label }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <ul class="list-group list-group-flush small">
                                @foreach ([
                                    ['fa-arrows-alt', 'Built-up size', $property->size ? currency($property->size) . ' m²' : null],
                                    ['fa-car', 'Parking', $property->parking],
                                    ['fa-bolt', 'Kahramaa', $property->kahramaa],
                                    ['fa-plug', 'Electricity', $property->electricity],
                                    ['fa-briefcase', 'Furniture', $property->furniture],
                                ] as [$icon, $label, $value])
                                    <li class="list-group-item d-flex justify-content-between align-items-center gap-2 bg-transparent">
                                        <span class="text-body-secondary"><i class="fa {{ $icon }} fa-fw text-body-tertiary"></i> {{ $label }}</span>
                                        <span class="fw-semibold text-end">{{ $value ?: '—' }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>

                    {{-- Current Tenancy --}}
                    <div class="col-xxl-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-transparent d-flex align-items-center gap-2 py-2">
                                <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-primary-subtle text-primary-emphasis"
                                    style="width:28px;height:28px"><i class="fa fa-key"></i></span>
                                <div>
                                    <h6 class="mb-0 fw-bold">Current Tenancy</h6>
                                    <small class="text-body-tertiary">Live rent-out agreement</small>
                                </div>
                                @if ($current)
                                    <span class="badge bg-{{ $current['agreement']->status?->color() }}-subtle text-{{ $current['agreement']->status?->color() }}-emphasis ms-auto">
                                        {{ $current['agreement']->status?->label() }}
                                    </span>
                                @endif
                            </div>

                            @if ($current)
                                @php
                                    $live = $current['agreement'];
                                    $isRental = $live->agreement_type?->value === 'rental';
                                    $viewRoute = $isRental ? 'property::rent::view' : 'property::sale::view';
                                @endphp
                                <div class="card-body">
                                    <div class="d-flex align-items-center gap-2 p-2 rounded-3 bg-body-tertiary border mb-3">
                                        <span class="pv-av pv-av-lg d-inline-flex align-items-center justify-content-center rounded-3 text-white fw-bold">
                                            {{ str($live->customer?->name ?: '—')->squish()->explode(' ')->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('') }}
                                        </span>
                                        <div class="min-w-0">
                                            <div class="fw-semibold text-truncate">{{ $live->customer?->name ?: '—' }}</div>
                                            <div class="small text-body-secondary">
                                                <a href="{{ route($viewRoute, $live->id) }}" class="fw-semibold text-decoration-none">{{ $live->agreement_no }}</a>
                                                · {{ $isRental ? 'Rental' : 'Sale' }}
                                            </div>
                                        </div>
                                        <a href="{{ route($viewRoute, $live->id) }}" class="btn btn-sm btn-outline-secondary ms-auto">
                                            <i class="fa fa-external-link"></i>
                                        </a>
                                    </div>

                                    <ul class="list-group list-group-flush small mb-3">
                                        <li class="list-group-item d-flex justify-content-between bg-transparent px-0">
                                            <span class="text-body-secondary"><i class="fa fa-calendar-o fa-fw text-body-tertiary"></i> Period</span>
                                            <span class="fw-semibold">{{ systemDate($live->start_date) }} → {{ systemDate($live->end_date) }}</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between bg-transparent px-0">
                                            <span class="text-body-secondary"><i class="fa fa-credit-card fa-fw text-body-tertiary"></i> Instalments</span>
                                            <span class="fw-semibold">{{ $live->paid_terms_count }} of {{ $live->payment_terms_count }} collected</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between bg-transparent px-0">
                                            <span class="text-body-secondary"><i class="fa fa-shield fa-fw text-body-tertiary"></i> Security held</span>
                                            <span class="fw-semibold">{{ currency($current['security']) }}</span>
                                        </li>
                                    </ul>

                                    <div class="row g-2 text-center mb-3">
                                        @foreach ([['Collected', $current['paid'], 'success'], ['Due now', $current['balance'], 'danger'], ['Contract', $current['total'], 'body']] as [$label, $value, $tone])
                                            <div class="col-4">
                                                <div class="border rounded-3 py-2 px-1 bg-body-tertiary">
                                                    <div class="text-uppercase text-body-tertiary fw-bold" style="font-size:.6rem">{{ $label }}</div>
                                                    <div class="fw-bold text-{{ $tone }} font-monospace">{{ currency($value) }}</div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div class="d-flex justify-content-between small text-body-secondary mb-1">
                                        <span>Collection progress</span><span class="fw-bold text-body">{{ $current['collected_percent'] }}%</span>
                                    </div>
                                    <div class="progress" role="progressbar" aria-label="Collection progress"
                                        aria-valuenow="{{ $current['collected_percent'] }}" aria-valuemin="0" aria-valuemax="100" style="height:6px">
                                        <div class="progress-bar bg-success" style="width: {{ $current['collected_percent'] }}%"></div>
                                    </div>
                                </div>
                            @else
                                <div class="card-body text-center py-5">
                                    <i class="fa fa-key fs-2 text-body-tertiary d-block mb-2"></i>
                                    <p class="text-body-secondary small mb-3">This unit has no live agreement.</p>
                                    @can('rent out.create')
                                        <a href="{{ route('property::rent::create', $property->id) }}" class="btn btn-sm btn-primary">
                                            <i class="fa fa-plus"></i> Raise Agreement
                                        </a>
                                    @endcan
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Letting summary --}}
                    <div class="col-lg-7">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-transparent d-flex align-items-center gap-2 py-2">
                                <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-primary-subtle text-primary-emphasis"
                                    style="width:28px;height:28px"><i class="fa fa-history"></i></span>
                                <div>
                                    <h6 class="mb-0 fw-bold">Letting Summary</h6>
                                    <small class="text-body-tertiary">
                                        Performance since {{ $stats['since'] ? systemDate($stats['since']) : 'the unit went live' }}
                                    </small>
                                </div>
                                {{-- Clicks the real pill so the nav state follows the pane. --}}
                                <button class="btn btn-sm btn-outline-secondary ms-auto" type="button"
                                    onclick="document.getElementById('pv-tenancies-tab').click()">
                                    <i class="fa fa-list"></i> All tenancies
                                </button>
                            </div>
                            <ul class="list-group list-group-flush small">
                                <li class="list-group-item d-flex justify-content-between bg-transparent">
                                    <span class="text-body-secondary"><i class="fa fa-file-text-o fa-fw text-body-tertiary"></i> Agreements raised</span>
                                    <span class="fw-semibold">
                                        {{ $stats['agreements'] }}
                                        <span class="text-body-tertiary fw-normal">({{ $stats['tenancies'] }} active or closed)</span>
                                    </span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between bg-transparent">
                                    <span class="text-body-secondary"><i class="fa fa-user fa-fw text-body-tertiary"></i> Tenants to date</span>
                                    <span class="fw-semibold">{{ $stats['tenants'] }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between bg-transparent">
                                    <span class="text-body-secondary"><i class="fa fa-calendar fa-fw text-body-tertiary"></i> Average tenancy</span>
                                    <span class="fw-semibold">{{ $stats['avg_tenancy'] }} months</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between bg-transparent">
                                    <span class="text-body-secondary"><i class="fa fa-money fa-fw text-body-tertiary"></i> Average achieved rent</span>
                                    <span class="fw-semibold">{{ currency($stats['avg_rent']) }} / month</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between bg-transparent">
                                    <span class="text-body-secondary"><i class="fa fa-ban fa-fw text-body-tertiary"></i> Vacancy gaps</span>
                                    <span class="badge bg-{{ $stats['gap_count'] ? 'danger' : 'success' }}-subtle text-{{ $stats['gap_count'] ? 'danger' : 'success' }}-emphasis">
                                        {{ $stats['gap_count'] }} gaps · {{ $stats['gap_days'] }} days
                                    </span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between bg-transparent">
                                    <span class="text-body-secondary"><i class="fa fa-wrench fa-fw text-body-tertiary"></i> Maintenance requests</span>
                                    <span class="fw-semibold">
                                        {{ $maintenances->count() }}
                                        @if ($openMaintenance->isNotEmpty())
                                            <span class="badge bg-warning-subtle text-warning-emphasis">{{ $openMaintenance->count() }} open</span>
                                        @endif
                                    </span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    {{-- Floor plan --}}
                    <div class="col-lg-5">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-transparent d-flex align-items-center gap-2 py-2">
                                <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-primary-subtle text-primary-emphasis"
                                    style="width:28px;height:28px"><i class="fa fa-picture-o"></i></span>
                                <div>
                                    <h6 class="mb-0 fw-bold">Floor Plan</h6>
                                    <small class="text-body-tertiary">{{ $property->floor_plan ? 'Click to enlarge' : 'Not uploaded' }}</small>
                                </div>
                            </div>
                            <div class="card-body d-flex align-items-center justify-content-center">
                                @if ($property->floor_plan)
                                    <img src="{{ asset($property->floor_plan) }}" alt="Floor plan for {{ $property->number }}"
                                        class="img-fluid rounded-3 border pv-plan" role="button"
                                        data-bs-toggle="modal" data-bs-target="#PropertyFloorPlanModal">
                                @else
                                    <div class="text-center text-body-tertiary py-4">
                                        <i class="fa fa-picture-o fs-2 d-block mb-2"></i>
                                        <span class="small">No floor plan on file for this unit.</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ─────────────────────────  TENANCIES  ───────────────────────── --}}
            <div class="tab-pane fade" id="pv-tenancies" role="tabpanel" aria-labelledby="pv-tenancies-tab" tabindex="0">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent d-flex align-items-center gap-2 py-2">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-primary-subtle text-primary-emphasis"
                            style="width:28px;height:28px"><i class="fa fa-history"></i></span>
                        <div>
                            <h6 class="mb-0 fw-bold">Tenancy History</h6>
                            <small class="text-body-tertiary">Every rent-out agreement raised against this unit</small>
                        </div>
                        <span class="badge bg-secondary-subtle text-secondary-emphasis ms-auto">{{ $agreements->count() }} agreements</span>
                    </div>

                    @if ($agreements->isEmpty())
                        <div class="card-body text-center py-5">
                            <i class="fa fa-file-text-o fs-2 text-body-tertiary d-block mb-2"></i>
                            <p class="text-body-secondary small mb-0">No agreement has been raised on this unit yet.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle small mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:1%">#</th>
                                        <th>Agreement</th>
                                        <th>Tenant</th>
                                        <th>Type</th>
                                        <th class="text-end">Rent</th>
                                        <th>Period</th>
                                        <th class="text-end">Term</th>
                                        <th class="text-end">Collected</th>
                                        <th class="text-end">Balance</th>
                                        <th>Status</th>
                                        <th style="width:1%"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($agreements as $agreement)
                                        @php
                                            $isRental = $agreement->agreement_type?->value === 'rental';
                                            $route = $isRental ? 'property::rent::view' : 'property::sale::view';
                                            $canView = $isRental ? 'rent out.view' : 'rent out lease.view';
                                            $months = $agreement->start_date && $agreement->end_date
                                                ? $agreement->start_date->diffInMonths($agreement->end_date) : 0;
                                        @endphp
                                        <tr>
                                            <td class="text-body-tertiary">{{ $loop->iteration }}</td>
                                            <td>
                                                @can($canView)
                                                    <a href="{{ route($route, $agreement->id) }}" class="fw-semibold text-decoration-none">{{ $agreement->agreement_no }}</a>
                                                @else
                                                    <span class="fw-semibold">{{ $agreement->agreement_no }}</span>
                                                @endcan
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="pv-av d-inline-flex align-items-center justify-content-center rounded-3 text-white fw-bold">
                                                        {{ str($agreement->customer?->name ?: '—')->squish()->explode(' ')->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('') }}
                                                    </span>
                                                    <div class="min-w-0">
                                                        <div class="fw-semibold text-truncate">{{ $agreement->customer?->name ?: '—' }}</div>
                                                        @if ($agreement->customer?->mobile)
                                                            <div class="text-body-tertiary" style="font-size:.7rem">{{ $agreement->customer->mobile }}</div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-info-subtle text-info-emphasis">{{ $isRental ? 'Rental' : 'Sale' }}</span>
                                            </td>
                                            <td class="text-end font-monospace">{{ currency($agreement->rent) }}</td>
                                            <td class="text-nowrap">{{ systemDate($agreement->start_date) }} → {{ systemDate($agreement->end_date) }}</td>
                                            <td class="text-end text-nowrap">{{ ceil($months) }} mo</td>
                                            <td class="text-end font-monospace text-success">{{ currency($agreement->terms_paid) }}</td>
                                            <td class="text-end font-monospace {{ $agreement->terms_balance > 0 ? 'text-danger' : 'text-body-tertiary' }}">
                                                {{ currency($agreement->terms_balance) }}
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $agreement->status?->color() }}-subtle text-{{ $agreement->status?->color() }}-emphasis">
                                                    {{ $agreement->status?->label() }}
                                                </span>
                                            </td>
                                            <td>
                                                @can($canView)
                                                    <a href="{{ route($route, $agreement->id) }}" class="btn btn-sm btn-outline-secondary" title="Open agreement">
                                                        <i class="fa fa-angle-right"></i>
                                                    </a>
                                                @endcan
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="card-footer bg-body-tertiary">
                            <div class="row g-3 text-center small">
                                @foreach ([
                                    ['fa-file-text-o', 'Agreements', $stats['agreements'], null],
                                    ['fa-calendar', 'Avg tenancy', $stats['avg_tenancy'] . ' mo', null],
                                    ['fa-money', 'Avg achieved rent', currency($stats['avg_rent']), null],
                                    ['fa-line-chart', 'Lifetime collected', currency($stats['collected']), 'success'],
                                ] as [$icon, $label, $value, $tone])
                                    <div class="col-6 col-md-3">
                                        <div class="text-uppercase text-body-tertiary fw-bold" style="font-size:.6rem">
                                            <i class="fa {{ $icon }}"></i> {{ $label }}
                                        </div>
                                        <div class="fw-bold font-monospace {{ $tone ? 'text-' . $tone : '' }}">{{ $value }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- ─────────────────────────  OCCUPANTS  ───────────────────────── --}}
            <div class="tab-pane fade" id="pv-occupants" role="tabpanel" aria-labelledby="pv-occupants-tab" tabindex="0">
                <div class="row g-3">
                    <div class="col-lg-7">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-transparent d-flex align-items-center gap-2 py-2">
                                <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-primary-subtle text-primary-emphasis"
                                    style="width:28px;height:28px"><i class="fa fa-users"></i></span>
                                <div>
                                    <h6 class="mb-0 fw-bold">Registered Occupants</h6>
                                    <small class="text-body-tertiary">Tenant details recorded against this unit</small>
                                </div>
                                @can('tenant detail.view')
                                    <a href="{{ route('property::tenant::index') }}" class="btn btn-sm btn-outline-secondary ms-auto">
                                        <i class="fa fa-external-link"></i> Manage
                                    </a>
                                @endcan
                            </div>

                            @if ($occupants->isEmpty())
                                <div class="card-body text-center py-5">
                                    <i class="fa fa-users fs-2 text-body-tertiary d-block mb-2"></i>
                                    <p class="text-body-secondary small mb-0">No occupants recorded for this unit.</p>
                                </div>
                            @else
                                <ul class="list-group list-group-flush">
                                    @foreach ($occupants as $occupant)
                                        <li class="list-group-item d-flex align-items-center gap-2 bg-transparent">
                                            <span class="pv-av d-inline-flex align-items-center justify-content-center rounded-3 text-white fw-bold">
                                                {{ str($occupant->name)->squish()->explode(' ')->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('') }}
                                            </span>
                                            <div class="min-w-0 flex-grow-1">
                                                <div class="fw-semibold text-truncate">{{ $occupant->name }}</div>
                                                <div class="small text-body-secondary text-truncate">
                                                    @if ($occupant->emirates_id) ID {{ $occupant->emirates_id }} @endif
                                                    @if ($occupant->passport_no) · Passport {{ $occupant->passport_no }} @endif
                                                    @if ($occupant->nationality) · {{ $occupant->nationality }} @endif
                                                </div>
                                            </div>
                                            @if ($occupant->mobile)
                                                <a href="tel:{{ $occupant->mobile }}" class="badge bg-secondary-subtle text-secondary-emphasis text-decoration-none">
                                                    <i class="fa fa-phone"></i> {{ $occupant->mobile }}
                                                </a>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-transparent d-flex align-items-center gap-2 py-2">
                                <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-primary-subtle text-primary-emphasis"
                                    style="width:28px;height:28px"><i class="fa fa-user"></i></span>
                                <div>
                                    <h6 class="mb-0 fw-bold">Tenant of Record</h6>
                                    <small class="text-body-tertiary">From the live agreement</small>
                                </div>
                            </div>

                            @if ($current && $current['agreement']->customer)
                                @php $customer = $current['agreement']->customer; @endphp
                                <ul class="list-group list-group-flush small">
                                    <li class="list-group-item d-flex justify-content-between bg-transparent">
                                        <span class="text-body-secondary"><i class="fa fa-user fa-fw text-body-tertiary"></i> Name</span>
                                        <span class="fw-semibold text-end">{{ $customer->name }}</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between bg-transparent">
                                        <span class="text-body-secondary"><i class="fa fa-phone fa-fw text-body-tertiary"></i> Mobile</span>
                                        <span class="fw-semibold text-end">{{ $customer->mobile ?: '—' }}</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between bg-transparent">
                                        <span class="text-body-secondary"><i class="fa fa-envelope fa-fw text-body-tertiary"></i> Email</span>
                                        <span class="fw-semibold text-end text-truncate">{{ $customer->email ?: '—' }}</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between bg-transparent">
                                        <span class="text-body-secondary"><i class="fa fa-users fa-fw text-body-tertiary"></i> Occupants</span>
                                        <span class="fw-semibold">{{ $occupants->count() }} registered</span>
                                    </li>
                                </ul>
                                <div class="card-body pt-2">
                                    <a href="{{ route('account::customer::view', $customer->id) }}" class="btn btn-sm btn-outline-primary w-100">
                                        <i class="fa fa-external-link"></i> Open customer record
                                    </a>
                                </div>
                            @else
                                <div class="card-body text-center py-5">
                                    <i class="fa fa-user fs-2 text-body-tertiary d-block mb-2"></i>
                                    <p class="text-body-secondary small mb-0">No live agreement, so no tenant of record.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- ─────────────────────────  MAINTENANCE  ───────────────────────── --}}
            <div class="tab-pane fade" id="pv-maintenance" role="tabpanel" aria-labelledby="pv-maintenance-tab" tabindex="0">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent d-flex align-items-center gap-2 py-2">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-primary-subtle text-primary-emphasis"
                            style="width:28px;height:28px"><i class="fa fa-wrench"></i></span>
                        <div>
                            <h6 class="mb-0 fw-bold">Maintenance &amp; Complaints</h6>
                            <small class="text-body-tertiary">Requests raised on this unit</small>
                        </div>
                        <div class="ms-auto d-flex gap-2">
                            @if ($openMaintenance->isNotEmpty())
                                <span class="badge bg-warning-subtle text-warning-emphasis align-self-center">{{ $openMaintenance->count() }} open</span>
                            @endif
                            @can('maintenance.create')
                                <a href="{{ route('property::maintenance::create') }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="fa fa-plus"></i> New request
                                </a>
                            @endcan
                        </div>
                    </div>

                    @if ($maintenances->isEmpty())
                        <div class="card-body text-center py-5">
                            <i class="fa fa-wrench fs-2 text-body-tertiary d-block mb-2"></i>
                            <p class="text-body-secondary small mb-0">No maintenance has been logged for this unit.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle small mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Issue</th>
                                        <th>Segment</th>
                                        <th>Priority</th>
                                        <th>Agreement</th>
                                        <th>Completed by</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($maintenances as $maintenance)
                                        <tr>
                                            <td class="text-nowrap">{{ systemDate($maintenance->date) }}</td>
                                            <td class="text-truncate" style="max-width:280px">{{ $maintenance->remark ?: '—' }}</td>
                                            <td>{{ $maintenance->segment?->label() ?? '—' }}</td>
                                            <td>
                                                <span class="badge bg-{{ $maintenance->priority?->color() ?? 'secondary' }}-subtle text-{{ $maintenance->priority?->color() ?? 'secondary' }}-emphasis">
                                                    {{ $maintenance->priority?->label() ?? '—' }}
                                                </span>
                                            </td>
                                            <td>
                                                @if ($maintenance->rentOut)
                                                    @php
                                                        $mRental = $maintenance->rentOut->agreement_type?->value === 'rental';
                                                        $mRoute = $mRental ? 'property::rent::view' : 'property::sale::view';
                                                    @endphp
                                                    @can($mRental ? 'rent out.view' : 'rent out lease.view')
                                                        <a href="{{ route($mRoute, $maintenance->rentOut->id) }}" class="text-decoration-none">
                                                            {{ $maintenance->rentOut->agreement_no }}
                                                        </a>
                                                    @else
                                                        {{ $maintenance->rentOut->agreement_no }}
                                                    @endcan
                                                @else
                                                    <span class="text-body-tertiary">—</span>
                                                @endif
                                            </td>
                                            <td>{{ $maintenance->completedBy?->name ?: '—' }}</td>
                                            <td>
                                                <span class="badge bg-{{ $maintenance->status?->color() }}-subtle text-{{ $maintenance->status?->color() }}-emphasis">
                                                    {{ $maintenance->status?->label() }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="card-footer bg-body-tertiary">
                            <div class="row g-3 text-center small">
                                @foreach ([
                                    ['fa-wrench', 'Requests', $maintenances->count(), null],
                                    ['fa-fire', 'Open now', $openMaintenance->count(), $openMaintenance->isNotEmpty() ? 'warning' : null],
                                    ['fa-check-circle', 'Completed', $maintenances->where('status.value', 'completed')->count(), 'success'],
                                    ['fa-calendar', 'Last raised', $maintenances->first()?->date ? systemDate($maintenances->first()->date) : '—', null],
                                ] as [$icon, $label, $value, $tone])
                                    <div class="col-6 col-md-3">
                                        <div class="text-uppercase text-body-tertiary fw-bold" style="font-size:.6rem">
                                            <i class="fa {{ $icon }}"></i> {{ $label }}
                                        </div>
                                        <div class="fw-bold {{ $tone ? 'text-' . $tone : '' }}">{{ $value }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- ─────────────────────────  DOCUMENTS  ───────────────────────── --}}
            <div class="tab-pane fade" id="pv-documents" role="tabpanel" aria-labelledby="pv-documents-tab" tabindex="0">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent d-flex align-items-center gap-2 py-2">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-primary-subtle text-primary-emphasis"
                            style="width:28px;height:28px"><i class="fa fa-paperclip"></i></span>
                        <div>
                            <h6 class="mb-0 fw-bold">Documents</h6>
                            <small class="text-body-tertiary">Attached to this unit's agreements</small>
                        </div>
                    </div>

                    @if ($documents->isEmpty())
                        <div class="card-body text-center py-5">
                            <i class="fa fa-paperclip fs-2 text-body-tertiary d-block mb-2"></i>
                            <p class="text-body-secondary small mb-0">No documents uploaded against this unit's agreements.</p>
                        </div>
                    @else
                        <ul class="list-group list-group-flush">
                            @foreach ($documents as $document)
                                <li class="list-group-item d-flex align-items-center gap-2 bg-transparent">
                                    <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-danger-subtle text-danger-emphasis"
                                        style="width:34px;height:34px"><i class="fa fa-file-pdf-o"></i></span>
                                    <div class="min-w-0 flex-grow-1">
                                        <div class="fw-semibold text-truncate">{{ $document->name }}</div>
                                        <div class="small text-body-secondary">
                                            {{ $document->documentType?->name ?: 'Document' }}
                                            @if ($document->rentOut) · {{ $document->rentOut->agreement_no }} @endif
                                            · {{ systemDate($document->created_at) }}
                                        </div>
                                    </div>
                                    <a href="{{ $document->url }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">
                                        <i class="fa fa-download"></i>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            {{-- ─────────────────────────  ACTIVITY  ───────────────────────── --}}
            <div class="tab-pane fade" id="pv-activity" role="tabpanel" aria-labelledby="pv-activity-tab" tabindex="0">
                <div class="row g-3">
                    <div class="col-lg-7">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-transparent d-flex align-items-center gap-2 py-2">
                                <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-primary-subtle text-primary-emphasis"
                                    style="width:28px;height:28px"><i class="fa fa-clock-o"></i></span>
                                <div>
                                    <h6 class="mb-0 fw-bold">Recent Changes</h6>
                                    <small class="text-body-tertiary">Audit trail for this unit</small>
                                </div>
                            </div>

                            @if ($this->activity->isEmpty())
                                <div class="card-body text-center py-5">
                                    <i class="fa fa-clock-o fs-2 text-body-tertiary d-block mb-2"></i>
                                    <p class="text-body-secondary small mb-0">No recorded changes yet.</p>
                                </div>
                            @else
                                <ul class="list-group list-group-flush small">
                                    @foreach ($this->activity as $audit)
                                        <li class="list-group-item bg-transparent">
                                            <div class="d-flex justify-content-between gap-2">
                                                <span class="fw-semibold text-capitalize">
                                                    <i class="fa fa-circle text-primary" style="font-size:.4rem;vertical-align:middle"></i>
                                                    {{ $audit->event }}
                                                    @if ($modified = collect($audit->getModified())->keys()->take(4)->implode(', '))
                                                        <span class="text-body-secondary fw-normal">— {{ $modified }}</span>
                                                    @endif
                                                </span>
                                                <span class="text-body-tertiary text-nowrap">{{ systemDate($audit->created_at) }}</span>
                                            </div>
                                            <div class="text-body-secondary">by {{ $audit->user?->name ?: 'System' }}</div>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-transparent d-flex align-items-center gap-2 py-2">
                                <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-danger-subtle text-danger-emphasis"
                                    style="width:28px;height:28px"><i class="fa fa-exclamation-triangle"></i></span>
                                <div>
                                    <h6 class="mb-0 fw-bold">Needs Attention</h6>
                                    <small class="text-body-tertiary">Open items on this unit</small>
                                </div>
                            </div>
                            <ul class="list-group list-group-flush small">
                                @if ($stats['outstanding'] > 0)
                                    <li class="list-group-item d-flex align-items-center gap-2 bg-transparent">
                                        <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-danger-subtle text-danger-emphasis"
                                            style="width:28px;height:28px"><i class="fa fa-money"></i></span>
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold">{{ currency($stats['outstanding']) }} outstanding</div>
                                            <div class="text-body-secondary">across this unit's agreements</div>
                                        </div>
                                    </li>
                                @endif
                                @if ($openMaintenance->isNotEmpty())
                                    <li class="list-group-item d-flex align-items-center gap-2 bg-transparent">
                                        <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-warning-subtle text-warning-emphasis"
                                            style="width:28px;height:28px"><i class="fa fa-wrench"></i></span>
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold">{{ $openMaintenance->count() }} maintenance {{ str('request')->plural($openMaintenance->count()) }} open</div>
                                            <div class="text-body-secondary">oldest raised {{ systemDate($openMaintenance->last()?->date) }}</div>
                                        </div>
                                    </li>
                                @endif
                                @if ($current && $current['days_left'] >= 0 && $current['days_left'] <= 120)
                                    <li class="list-group-item d-flex align-items-center gap-2 bg-transparent">
                                        <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-info-subtle text-info-emphasis"
                                            style="width:28px;height:28px"><i class="fa fa-calendar"></i></span>
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold">Renewal due in {{ $current['days_left'] }} days</div>
                                            <div class="text-body-secondary">agreement ends {{ systemDate($current['agreement']->end_date) }}</div>
                                        </div>
                                    </li>
                                @endif
                                @if ($stats['outstanding'] <= 0 && $openMaintenance->isEmpty() && ! ($current && $current['days_left'] <= 120))
                                    <li class="list-group-item text-center text-body-secondary py-5 bg-transparent">
                                        <i class="fa fa-check-circle fs-2 text-success d-block mb-2"></i>
                                        Nothing needs attention on this unit.
                                    </li>
                                @endif
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Floor plan modal --}}
        @if ($property->floor_plan)
            <div class="modal fade" id="PropertyFloorPlanModal" tabindex="-1" aria-labelledby="PropertyFloorPlanLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="PropertyFloorPlanLabel">Floor Plan · {{ $property->number }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body text-center">
                            <img src="{{ asset($property->floor_plan) }}" alt="Floor plan for {{ $property->number }}" class="img-fluid rounded-3">
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>
