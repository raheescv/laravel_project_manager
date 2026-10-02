@use('App\Support\LeadPipeline')
@php
    $stageTone = function (?string $status) use ($stages): string {
        $stage = LeadPipeline::stageOf(LeadPipeline::canonical($status));

        return $stage ? $stages[$stage]['tone'] : 'danger';
    };
    $initials = fn (?string $name): string => collect(preg_split('/\s+/', trim((string) $name)))
        ->reject(fn ($word) => $word === '' || preg_match('/^al-?$/i', $word))
        ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))
        ->take(2)->implode('');
    $typeTones = ['Sales' => 'primary', 'Rentout' => 'info', 'Corporate' => 'warning'];
    $activeStage = $filterStage ?: (filled($filterStatus) ? LeadPipeline::stageOf(LeadPipeline::canonical($filterStatus)) : null);
    $dateLabels = ['created' => 'Created', 'reassigned' => 'Reassigned', 'updated' => 'Updated'];
@endphp
<div class="llx">
    <x-property.lead-list.premium />

    {{-- Pipeline stage strip: each stage filters the list; counts follow every filter but stage/status. --}}
    <div class="llx-card strip" style="--stages: {{ count($stages) }}">
        @foreach($stages as $key => $stage)
            @php $count = $stageCounts['stages'][$key] ?? 0; @endphp
            <button type="button" wire:click="pickStage('{{ $key }}')" class="stg tn t-{{ $stage['tone'] }} {{ $activeStage === $key ? 'on' : '' }}" aria-pressed="{{ $activeStage === $key ? 'true' : 'false' }}">
                <div class="h"><i class="fa {{ $stage['icon'] }}"></i>{{ $stage['name'] }}</div>
                <div class="v">{{ number_format($count) }}<small>{{ $stageCounts['total'] ? round($count / $stageCounts['total'] * 100) : 0 }}%</small></div>
            </button>
        @endforeach
        <div class="stg tot">
            <div class="h"><i class="fa fa-users"></i>All leads</div>
            <div class="v" title="{{ $dateLabels[$dateField] ?? 'Created' }} {{ $fromDate ? systemDate($fromDate) : '…' }} – {{ $toDate ? systemDate($toDate) : '…' }}">{{ number_format($stageCounts['total']) }}<small>in range</small></div>
            <div class="mixbar">
                @foreach($stages as $key => $stage)
                    @if($stageCounts['stages'][$key] ?? 0)
                        <span class="tn t-{{ $stage['tone'] }}" style="flex: {{ $stageCounts['stages'][$key] }}" title="{{ $stage['name'] }}: {{ $stageCounts['stages'][$key] }}"></span>
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    {{-- Project × status summary, collapsed by default --}}
    @if($groups && $statuses)
        <div class="llx-card">
            <details class="mxd" wire:ignore.self>
                <summary><i class="fa fa-th"></i> Project × status <span class="hint">· click a count to filter</span><i class="fa fa-chevron-down"></i></summary>
                <div class="mx-wrap">
                    <table class="mx">
                        <thead>
                            <tr class="sg">
                                <th class="pj blank"></th>
                                @foreach($stages as $stage)
                                    @if(count($stage['statuses']))
                                        <th class="tn t-{{ $stage['tone'] }}" colspan="{{ count($stage['statuses']) }}"><i class="fa {{ $stage['icon'] }}"></i> {{ $stage['name'] }}</th>
                                    @endif
                                @endforeach
                                <th class="blank"></th>
                            </tr>
                            <tr>
                                <th class="pj">Project / group</th>
                                @foreach($stages as $stage)
                                    @foreach($stage['statuses'] as $status)
                                        <th title="{{ $status }}">{{ \Illuminate\Support\Str::limit($status, 12) }}</th>
                                    @endforeach
                                @endforeach
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($groups as $groupId => $groupName)
                                @php $row = $statusSummary[$groupId] ?? []; @endphp
                                <tr>
                                    <th class="pj" title="{{ $groupName }}">{{ \Illuminate\Support\Str::limit($groupName, 34) }}</th>
                                    @foreach($stages as $stage)
                                        @foreach($stage['statuses'] as $status)
                                            @php $count = $row[$status] ?? 0; @endphp
                                            <td>
                                                @if($count > 0)
                                                    <a href="#" wire:click.prevent="pickMatrixCell(@js($status), {{ $groupId }})" class="c tn t-{{ $stage['tone'] }}" style="background: color-mix(in srgb, var(--tn) {{ (int) round(8 + min($count / 20, 1) * 30) }}%, transparent)">{{ $count }}</a>
                                                @else
                                                    <span class="c z">·</span>
                                                @endif
                                            </td>
                                        @endforeach
                                    @endforeach
                                    <td class="tt">{{ array_sum($row) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </details>
        </div>
    @endif

    {{-- List --}}
    <div class="llx-card">
        <div class="fbar">
            <div class="search">
                <i class="fa fa-search"></i>
                <input type="text" wire:model.live.debounce.400ms="search" class="ctl" placeholder="Search name, mobile, email, company…" aria-label="Search leads" autofocus x-init="$nextTick(() => $el.focus())">
            </div>
            <button type="button" class="btn-l" data-bs-toggle="offcanvas" data-bs-target="#leadColumnVisibility" aria-controls="leadColumnVisibility" title="Columns">
                <i class="fa fa-columns"></i>
            </button>
            @can('property lead.download')
                <button type="button" class="btn-l ok" wire:click="export" wire:loading.attr="disabled" wire:target="export" title="Export to Excel">
                    <i class="fa fa-file-excel-o" wire:loading.remove wire:target="export"></i>
                    <i class="fa fa-spinner fa-spin" wire:loading wire:target="export"></i>
                    <span class="hide-sm">Export</span>
                </button>
            @endcan
        </div>

        <div class="fgrid">
            <div class="row g-2">
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="dgrp">
                        <select wire:model.live="dateField" class="ctl" aria-label="Date to filter on">
                            @foreach($dateLabels as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <input type="date" wire:model.live="fromDate" class="ctl" aria-label="From date">
                        <input type="date" wire:model.live="toDate" class="ctl" aria-label="To date">
                    </div>
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <select wire:model.live="filterType" class="ctl" aria-label="Type">
                        <option value="">All types</option>
                        @foreach($types as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3 col-xl-3">
                    <div class="fts" wire:ignore>
                        <select id="leadFilterAssigned" class="lead-filter-ts" data-property="filterAssignedTo" aria-label="Assigned To">
                            <option value="">Any assignee</option>
                            @foreach($users as $id => $name)
                                <option value="{{ $id }}" @selected((string) $filterAssignedTo === (string) $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="fts" wire:ignore>
                        <select id="leadFilterGroup" class="lead-filter-ts" data-property="filterPropertyGroupId" aria-label="Project / Group">
                            <option value="">Any project / group</option>
                            @foreach($groups as $id => $name)
                                <option value="{{ $id }}" @selected((string) $filterPropertyGroupId === (string) $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <div class="fts" wire:ignore>
                        <select id="leadFilterSource" class="lead-filter-ts" data-property="filterSource" aria-label="Source">
                            <option value="">Any source</option>
                            @foreach($sources as $key => $label)
                                <option value="{{ $key }}" @selected((string) $filterSource === (string) $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <select wire:model.live="filterSubSource" wire:key="filterSubSource-{{ md5((string) $filterSource) }}" class="ctl" aria-label="Sub Source" @disabled(! count($subSources))>
                        <option value="">{{ count($subSources) ? 'Any sub source' : 'No sub sources' }}</option>
                        @foreach($subSources as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <div class="fts" wire:ignore>
                        <select id="leadFilterStatus" class="lead-filter-ts" data-property="filterStatus" aria-label="Status">
                            <option value="">Any status</option>
                            @foreach($statuses as $key => $label)
                                <option value="{{ $key }}" @selected((string) $filterStatus === (string) $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <select wire:model.live="filterSubStatus" wire:key="filterSubStatus-{{ md5((string) $filterStatus) }}" class="ctl" aria-label="Sub Status" @disabled(! count($subStatuses))>
                        <option value="">{{ count($subStatuses) ? 'Any sub status' : 'No sub statuses' }}</option>
                        @foreach($subStatuses as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <div class="fts" wire:ignore>
                        <select id="leadFilterCountry" class="lead-filter-ts" data-property="filterCountryId" aria-label="Nationality">
                            <option value="">Any nationality</option>
                            @foreach($countries as $id => $name)
                                <option value="{{ $id }}" @selected((string) $filterCountryId === (string) $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <select wire:model.live="filterLocation" class="ctl" aria-label="Location">
                        <option value="">Any location</option>
                        @foreach($locations as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="chips">
            @foreach($activeFilters as $property => $chip)
                <span class="chip">{{ $chip['label'] }} <b title="{{ $chip['value'] }}">{{ $chip['value'] }}</b>
                    <button type="button" wire:click="$set('{{ $property }}', '')" aria-label="Remove {{ $chip['label'] }} filter"><i class="fa fa-times"></i></button>
                </span>
            @endforeach
            @if($activeFilters)
                <button type="button" class="clr" wire:click="clearFilters">Clear all</button>
            @endif
            <span class="count">
                @if($list->total())
                    Showing <b>{{ $list->firstItem() }}–{{ $list->lastItem() }}</b> of {{ number_format($list->total()) }}
                @else
                    No matches
                @endif
            </span>
        </div>

        <div class="tbl-wrap">
            <table class="tbl">
                <thead>
                    <tr>
                        <th class="ck"><input type="checkbox" wire:model.live="selectAll" class="cb" id="selectAllCheckbox" aria-label="Select all"></th>
                        <th>
                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="name" label="Lead" />
                            <span class="fnt mx-1">·</span>
                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="id" label="#" />
                        </th>
                        @foreach($columns as $column => $label)
                            @continue($column === 'mobile' || ($column === 'sub_status' && isset($columns['status'])) || ($column === 'sub_source' && isset($columns['source'])))
                            @if(in_array($column, ['created_at', 'reassigned_at', 'updated_at'], true))
                                <th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" :field="$column" :label="$label" /></th>
                            @else
                                <th>{{ $label }}</th>
                            @endif
                        @endforeach
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($list as $item)
                        @php $tone = $stageTone($item->status); @endphp
                        <tr wire:key="lead-row-{{ $item->id }}" @class(['sel' => in_array((string) $item->id, array_map('strval', $selected), true)])>
                            <td class="ck"><input type="checkbox" value="{{ $item->id }}" wire:model.live="selected" class="cb" id="ck{{ $item->id }}" aria-label="Select lead {{ $item->id }}"></td>
                            <td>
                                <div class="who">
                                    <span class="av tn t-{{ $tone }}">{{ $initials($item->name) }}</span>
                                    <div class="min-w-0">
                                        <a href="{{ route('property::lead::edit', $item->id) }}" class="nm">{{ $item->name }}</a><span class="lid">#{{ $item->id }}</span>
                                        <div class="sub">
                                            @if($item->company_name)<i class="fa fa-building-o"></i> {{ $item->company_name }}@endif
                                            @if($item->company_name && isset($columns['mobile']) && $item->mobile) · @endif
                                            @if(isset($columns['mobile']) && $item->mobile)<i class="fa fa-phone"></i> {{ $item->mobile }}@endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            @foreach($columns as $column => $label)
                                @switch($column)
                                    @case('email')
                                        <td class="nw">{!! $item->email ? e($item->email) : '<span class="fnt">—</span>' !!}</td>
                                        @break
                                    @case('property_group')
                                        <td class="nw">{!! $item->group ? e($item->group->name) : '<span class="fnt">—</span>' !!}</td>
                                        @break
                                    @case('property_type')
                                        <td class="nw">{!! $item->propertyType ? e($item->propertyType->name) : '<span class="fnt">—</span>' !!}</td>
                                        @break
                                    @case('budget')
                                        <td class="nw num">
                                            @if(filled($item->budget_min) || filled($item->budget_max))
                                                {{ filled($item->budget_min) ? currency($item->budget_min) : '…' }} – {{ filled($item->budget_max) ? currency($item->budget_max) : '…' }}
                                                @if($item->rental_type)<div class="sub">{{ $item->rental_type }}</div>@endif
                                            @else
                                                <span class="fnt">—</span>
                                            @endif
                                        </td>
                                        @break
                                    @case('source')
                                        <td class="nw">
                                            {{ $item->source ?: '—' }}
                                            @if(isset($columns['sub_source']) && $item->sub_source)<div class="sub">{{ $item->sub_source }}</div>@endif
                                        </td>
                                        @break
                                    @case('sub_source')
                                        @if(! isset($columns['source']))
                                            <td class="nw"><span class="sub">{{ $item->sub_source ?: '—' }}</span></td>
                                        @endif
                                        @break
                                    @case('type')
                                        <td><span class="pill tn t-{{ $typeTones[$item->type] ?? 'secondary' }}">{{ leadTypes()[$item->type] ?? $item->type }}</span></td>
                                        @break
                                    @case('status')
                                        <td>
                                            <span class="pill tn t-{{ $tone }}"><span class="d"></span>{{ $item->status ?: 'New Lead' }}</span>
                                            @if(isset($columns['sub_status']) && $item->sub_status)<div class="sub mt-1">{{ $item->sub_status }}</div>@endif
                                        </td>
                                        @break
                                    @case('sub_status')
                                        @if(! isset($columns['status']))
                                            <td><span class="sub">{{ $item->sub_status ?: '—' }}</span></td>
                                        @endif
                                        @break
                                    @case('assigned_to')
                                        <td>
                                            @if($item->assignee)
                                                <span class="owner"><span class="av sm tn t-secondary">{{ $initials($item->assignee->name) }}</span>{{ $item->assignee->name }}</span>
                                            @else
                                                <span class="fnt nw"><i class="fa fa-user-times"></i> Unassigned</span>
                                            @endif
                                        </td>
                                        @break
                                    @case('nationality')
                                        <td class="nw">{{ $item->country->name ?? ($item->nationality ?: '—') }}</td>
                                        @break
                                    @case('meeting')
                                        <td class="nw">
                                            @if($item->meeting_date)
                                                <div @class(['today' => $item->meeting_date->isToday()])><i class="fa fa-clock-o"></i> {{ $item->meeting_date->isToday() ? 'Today' : systemDate($item->meeting_date) }}</div>
                                                <div class="sub">{{ $item->meeting_time ? systemTime($item->meeting_time) : $item->meeting_date->diffForHumans() }}</div>
                                            @else
                                                <span class="fnt">—</span>
                                            @endif
                                        </td>
                                        @break
                                    @case('location')
                                        <td class="nw">{{ $item->location ?: '—' }}</td>
                                        @break
                                    @case('created_at')
                                    @case('reassigned_at')
                                    @case('updated_at')
                                        @php $at = $item->{$column}; @endphp
                                        <td class="nw">
                                            @if($at)
                                                <div title="{{ systemDateTime($at) }}">{{ systemDate($at) }}</div>
                                                <div class="sub">{{ $at->diffForHumans() }}</div>
                                            @else
                                                <span class="fnt">—</span>
                                            @endif
                                        </td>
                                        @break
                                @endswitch
                            @endforeach
                            <td class="text-end">
                                <a href="{{ route('property::lead::edit', $item->id) }}" class="go" title="Open lead"><i class="fa fa-angle-right fs-5"></i></a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($columns) + 3 }}" class="empty">
                                <i class="fa fa-users"></i>
                                No leads match these filters.
                                @if($activeFilters)
                                    <div class="mt-2"><button type="button" class="btn-l" wire:click="clearFilters"><i class="fa fa-times"></i> Clear filters</button></div>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pager">
            <span class="rows">Rows
                <select wire:model.live="limit" class="ctl" aria-label="Rows per page">
                    <option value="15">15</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                    <option value="250">250</option>
                </select>
            </span>
            {{ $list->links() }}
        </div>
    </div>

    {{-- Floating bulk bar: appears while leads are ticked --}}
    @if(count($selected))
        <div class="fbulk" role="toolbar" aria-label="Selected leads">
            <span class="n">{{ count($selected) }}</span>
            <span class="lbl">{{ \Illuminate\Support\Str::plural('lead', count($selected)) }} selected</span>
            <span class="sep"></span>
            <button type="button" wire:click="clearSelection"><i class="fa fa-times"></i> Clear</button>
            @can('property lead.delete')
                <button type="button" class="del" wire:click="delete" wire:loading.attr="disabled" wire:target="delete"
                    wire:confirm="Delete the {{ count($selected) }} selected {{ \Illuminate\Support\Str::plural('lead', count($selected)) }}?">
                    <i class="fa fa-trash" wire:loading.remove wire:target="delete"></i>
                    <i class="fa fa-spinner fa-spin" wire:loading wire:target="delete"></i>
                    Delete
                </button>
            @endcan
        </div>
    @endif

    @push('scripts')
        <script>
            $(document).ready(function() {
                const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.map(function(el) { return new bootstrap.Tooltip(el, { boundary: document.body }); });
            });
        </script>
    @endpush
    @script
        <script>
            (() => {
                $wire.$el.querySelectorAll('select.lead-filter-ts').forEach((el) => {
                    if (el.tomselect) return;
                    const property = el.dataset.property;
                    const control = new TomSelect(el, {
                        allowEmptyOption: true,
                        maxOptions: null,
                        onChange(value) {
                            if (this.syncing) return;
                            $wire.set(property, value);
                        },
                    });

                    $wire.$watch(property, (value) => {
                        const next = value === null || value === undefined ? '' : String(value);
                        if (String(control.getValue()) === next) return;
                        control.syncing = true;
                        control.setValue(next, true);
                        control.syncing = false;
                    });
                });
            })()
        </script>
    @endscript
</div>
