@php
    $unmapped = \App\Support\LeadPipeline::UNMAPPED;
    $stageTotals = array_map(fn (array $stage) => array_sum(array_map(fn (string $status) => $counts[$status] ?? 0, $stage['statuses'])), $stages);
    $pct = fn (int $n) => $total ? round($n / $total * 100, $n * 10 < $total ? 1 : 0) : 0;
    $columnMax = max(1, ...array_map(fn (string $status) => $counts[$status] ?? 0, $pinned ?: [$unmapped]));
    $idleLabel = 'Idle '.\App\Livewire\Property\PropertyLead\Board::IDLE_DAYS.'+ days';
    $railGroups = $stages;
    if ($counts[$unmapped] || in_array($unmapped, $pinned, true)) {
        $railGroups['fix'] = ['name' => 'Needs fixing', 'icon' => 'fa-exclamation-triangle', 'tone' => 'danger', 'statuses' => [$unmapped]];
    }
@endphp
<div class="lbx"
    x-data="{
        selected: null,
        full: false,
        density: @js($density),
        open(id) { this.selected = id; Livewire.dispatch('lead-board-open', { id: id }); },
        setDensity(value) { this.density = value; $wire.saveDensity(value); },
        fit() {
            if (this.full) return;
            this.$el.style.setProperty('--lbx-top', (this.$el.getBoundingClientRect().top + window.scrollY) + 'px');
        },
        toggleFull() {
            this.full = ! this.full;
            document.body.style.overflow = this.full ? 'hidden' : '';
            if (this.full && document.documentElement.requestFullscreen) document.documentElement.requestFullscreen().catch(() => {});
            if (! this.full && document.fullscreenElement) document.exitFullscreen();
        },
    }"
    x-init="fit()"
    x-on:resize.window.debounce.150ms="fit()"
    x-on:fullscreenchange.document="if (! document.fullscreenElement && full) toggleFull()"
    x-bind:data-density="density"
    x-bind:class="{ 'peek-open': selected !== null, 'is-full': full }"
    x-on:lead-board-closed.window="selected = null"
    x-on:keydown.escape.window="selected !== null ? Livewire.dispatch('lead-board-close') : (full && toggleFull())">
    <x-property.lead-board.premium />

    <section class="lbx-card lbx-top">
        <div class="tb">
            <nav class="seg" aria-label="Lead views">
                <a href="{{ route('property::lead::list') }}"><i class="fa fa-list"></i> List</a>
                <a href="{{ route('property::lead::board') }}" class="on" aria-current="page"><i class="fa fa-columns"></i> Board</a>
                <a href="{{ route('property::lead::calendar') }}"><i class="fa fa-calendar"></i> Calendar</a>
            </nav>
            <label class="srch">
                <i class="fa fa-search"></i>
                <input type="search" wire:model.live.debounce.400ms="search" placeholder="Search name, mobile, email, company…" autocomplete="off">
            </label>
            <div class="seg" role="group" aria-label="Lead type">
                <button type="button" class="{{ $filterType === '' ? 'on' : '' }}" wire:click="$set('filterType', '')">All</button>
                @foreach ($types as $key => $label)
                    <button type="button" class="{{ $filterType === $key ? 'on' : '' }}" wire:click="$set('filterType', @js($key))">{{ $key === 'Rentout' ? 'Rent out' : $label }}</button>
                @endforeach
            </div>
            <div class="lbx-ts" wire:ignore>
                <select id="lbxProject" placeholder="All projects" aria-label="Project">
                    <option value=""></option>
                </select>
            </div>
            <div class="lbx-ts" wire:ignore>
                <select id="lbxAssigned" placeholder="All salesmen" aria-label="Assigned to">
                    <option value=""></option>
                </select>
            </div>
            <div class="lbx-ts" wire:ignore>
                <select id="lbxSource" placeholder="All sources" aria-label="Source">
                    <option value=""></option>
                    @foreach ($sources as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <span class="pill" title="Created between">
                <i class="fa fa-calendar"></i>
                <input type="date" wire:model.live="fromDate" aria-label="Created from">
                <span class="muted">–</span>
                <input type="date" wire:model.live="toDate" aria-label="Created to">
            </span>
            <label class="tgl">
                <input type="checkbox" @checked($preset === 'mine') wire:click="setPreset('mine')">
                <span class="trk"></span>My leads
            </label>
            <div class="tb-end">
                <button type="button" class="ghost" wire:click="clearFilters"><i class="fa fa-times"></i> Clear</button>
                <div class="seg" role="group" aria-label="Card density">
                    <button type="button" x-bind:class="density === 'comfort' && 'on'" x-on:click="setDensity('comfort')" title="Comfortable cards"><i class="fa fa-th-large"></i></button>
                    <button type="button" x-bind:class="density === 'compact' && 'on'" x-on:click="setDensity('compact')" title="Compact cards"><i class="fa fa-bars"></i></button>
                </div>
                <button type="button" class="ghost icon" x-on:click="toggleFull()" x-bind:title="full ? 'Exit full screen' : 'Full screen'" aria-label="Toggle full screen">
                    <i class="fa fa-expand" x-show="! full"></i>
                    <i class="fa fa-compress" x-show="full" style="display: none"></i>
                </button>
                @can('property lead.create')
                    <a href="{{ route('property::lead::create') }}" class="btn-new"><i class="fa fa-plus-circle"></i> New Lead</a>
                @endcan
            </div>
        </div>

        <div class="rib-wrap">
            <div>
                <div class="rib">
                    @foreach ($stages as $key => $stage)
                        @if ($stageTotals[$key])
                            <i class="tn tn-{{ $stage['tone'] }}" style="flex-grow: {{ $stageTotals[$key] }}" title="{{ $stage['name'] }}: {{ number_format($stageTotals[$key]) }}"></i>
                        @endif
                    @endforeach
                    @if ($counts[$unmapped])
                        <i class="tn tn-danger" style="flex-grow: {{ $counts[$unmapped] }}" title="{{ $unmapped }}: {{ number_format($counts[$unmapped]) }}"></i>
                    @endif
                </div>
                <div class="rib-legend">
                    <div class="rl total"><b>{{ number_format($total) }}</b>leads</div>
                    @foreach ($stages as $key => $stage)
                        <div class="rl tn tn-{{ $stage['tone'] }}"><span class="d"></span>{{ $stage['name'] }} <b>{{ number_format($stageTotals[$key]) }}</b><em>{{ $pct($stageTotals[$key]) }}%</em></div>
                    @endforeach
                    @if ($counts[$unmapped])
                        <div class="rl tn tn-danger"><span class="d"></span>{{ $unmapped }} <b>{{ number_format($counts[$unmapped]) }}</b><em>{{ $pct($counts[$unmapped]) }}%</em></div>
                    @endif
                </div>
            </div>
            <div class="quick">
                @foreach ([['meetings', 'fa-calendar', 'info', 'Meetings this week'], ['idle', 'fa-clock-o', 'warning', $idleLabel], ['unassigned', 'fa-user-plus', 'danger', 'Unassigned']] as [$key, $icon, $tone, $label])
                    <button type="button" class="qf tn tn-{{ $tone }} {{ $preset === $key ? 'on' : '' }}" wire:click="setPreset('{{ $key }}')">
                        <span class="ic"><i class="fa {{ $icon }}"></i></span>
                        <span><b>{{ number_format($presetCounts[$key]) }}</b><small>{{ $label }}</small></span>
                    </button>
                @endforeach
            </div>
        </div>
    </section>

    <section class="lbx-card lbx-boardcard">
        <div class="lbx-main">
            <aside class="lbx-rail" aria-label="Views and columns">
                <div class="rail-sec">Views</div>
                @foreach ([['all', 'fa-th-large', 'All leads', 'total'], ['mine', 'fa-user', 'My leads', 'mine'], ['meetings', 'fa-calendar', 'Meetings this week', 'meetings'], ['idle', 'fa-clock-o', $idleLabel, 'idle'], ['unassigned', 'fa-user-plus', 'Unassigned', 'unassigned']] as [$key, $icon, $label, $countKey])
                    <button type="button" class="rv {{ $preset === $key ? 'on' : '' }}" wire:click="setPreset('{{ $key }}')">
                        <i class="fa {{ $icon }}"></i>{{ $label }}<span>{{ number_format($presetCounts[$countKey]) }}</span>
                    </button>
                @endforeach

                <div class="rail-sec">Columns <em>{{ count($pinned) }} shown</em></div>
                @foreach ($railGroups as $group)
                    <div class="rs-stage tn tn-{{ $group['tone'] }}"><i class="fa {{ $group['icon'] }}"></i>{{ $group['name'] }}</div>
                    @foreach ($group['statuses'] as $status)
                        @php $isPinned = in_array($status, $pinned, true); @endphp
                        <button type="button" class="rs tn tn-{{ \App\Support\LeadPipeline::tone($status) }} {{ $isPinned ? 'pinned' : '' }}"
                            wire:click="togglePin(@js($status))" aria-pressed="{{ $isPinned ? 'true' : 'false' }}" title="{{ $isPinned ? 'Hide' : 'Show' }} column">
                            <span class="dot"></span><span class="rs-name">{{ $status }}</span><span class="n">{{ number_format($counts[$status] ?? 0) }}</span><i class="fa {{ $isPinned ? 'fa-eye' : 'fa-eye-slash' }} eye"></i>
                        </button>
                    @endforeach
                @endforeach
            </aside>

            <div class="lbx-bscroll">
                <div class="lbx-board" wire:loading.class="is-busy" wire:target="search,filterType,filterPropertyGroupId,filterAssignedTo,filterSource,fromDate,toDate,setPreset,clearFilters,togglePin">
                    @forelse ($pinned as $status)
                        @php
                            $column = $cards[$status];
                            $count = $counts[$status] ?? 0;
                            $isUnmapped = $status === $unmapped;
                        @endphp
                        <section class="lbx-col tn tn-{{ \App\Support\LeadPipeline::tone($status) }}" wire:key="lead-col-{{ \Illuminate\Support\Str::slug($status) }}" @if ($canMove && ! $isUnmapped) data-drop="{{ $status }}" @endif>
                            <header class="col-h">
                                <div class="col-row">
                                    <span class="dot"></span>
                                    <span class="col-name" title="{{ $status }}">
                                        @if ($isUnmapped)
                                            <i class="fa fa-exclamation-triangle"></i>
                                        @endif
                                        {{ $status }}
                                    </span>
                                    <span class="col-count">{{ number_format($count) }}</span>
                                    <button type="button" class="icon-btn" wire:click="togglePin(@js($status))" title="Hide column"><i class="fa fa-eye-slash"></i></button>
                                </div>
                                <div class="col-meter"><i style="width: {{ max(3, round($count / $columnMax * 100)) }}%"></i></div>
                                @if ($isUnmapped)
                                    <div class="col-hint">Not a real status. Drag a card to another column to fix it.</div>
                                @endif
                            </header>
                            <div class="lbx-col-body">
                                @forelse ($column as $lead)
                                    @include('livewire.property.property-lead.partials.board-card', ['lead' => $lead])
                                @empty
                                    <div class="empty"><i class="fa fa-arrows"></i>{{ $canMove && ! $isUnmapped ? 'Drop a lead here' : 'No leads' }}</div>
                                @endforelse
                            </div>
                            <footer class="col-f">
                                <span>{{ number_format($column->count()) }} of {{ number_format($count) }}</span>
                                @if ($column->count() < $count)
                                    <button type="button" class="load" wire:click="loadMore(@js($status))" wire:loading.attr="disabled" wire:target="loadMore">
                                        Load {{ min(\App\Livewire\Property\PropertyLead\Board::PAGE, $count - $column->count()) }} more
                                    </button>
                                @elseif ($count)
                                    <span class="done"><i class="fa fa-check"></i> All loaded</span>
                                @endif
                            </footer>
                        </section>
                    @empty
                        <div class="board-empty">
                            <i class="fa fa-columns"></i>
                            <b>No columns shown</b>
                            <span>Pick statuses under Columns to add them to the board.</span>
                        </div>
                    @endforelse
                </div>
            </div>

            <livewire:property.property-lead.board-peek wire:key="lead-board-peek" />
        </div>

        <div class="lbx-scrim" x-on:click="Livewire.dispatch('lead-board-close')"></div>

        @if ($lastMove)
            <div class="lbx-last" wire:key="lead-move-{{ $lastMove['key'] }}" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 8000)" x-transition.opacity role="status">
                <i class="fa fa-check ok"></i>
                <span>Moved <b>{{ $lastMove['name'] }}</b> to <b>{{ $lastMove['to'] }}</b></span>
                @if ($lastMove['from'] !== $unmapped)
                    <button type="button" wire:click="undoMove">Undo</button>
                @endif
                <button type="button" class="x" x-on:click="show = false" aria-label="Dismiss"><i class="fa fa-times"></i></button>
            </div>
        @endif
    </section>

    @script
        <script>
            (() => {
                const root = $wire.$el;
                let drag = null;

                const pickers = [
                    { id: 'lbxProject', property: 'filterPropertyGroupId', url: "{{ route('property::group::list') }}" },
                    { id: 'lbxAssigned', property: 'filterAssignedTo', url: "{{ route('users::list') }}?type=employee", extra: { id: 'none', name: 'Unassigned' } },
                    { id: 'lbxSource', property: 'filterSource' },
                ];

                pickers.forEach((picker) => {
                    const el = document.getElementById(picker.id);
                    if (!el || el.tomselect) return;

                    const settings = {
                        plugins: ['clear_button'],
                        persist: false,
                        maxOptions: null,
                        valueField: 'id',
                        labelField: 'name',
                        searchField: ['name', 'mobile'],
                        onChange(value) {
                            if (this.syncing) return;
                            $wire.set(picker.property, value || '');
                        },
                        render: {
                            option: (item, escape) => `<div>${escape(item.name || '')}${item.mobile ? `<span class="lbx-ts-sub">${escape(item.mobile)}</span>` : ''}</div>`,
                            item: (item, escape) => `<div>${escape(item.name || '')}</div>`,
                        },
                    };

                    if (picker.url) {
                        settings.load = (query, callback) => {
                            const joiner = picker.url.includes('?') ? '&' : '?';
                            fetch(picker.url + joiner + 'query=' + encodeURIComponent(query))
                                .then((response) => {
                                    if (!response.ok) throw new Error(response.statusText);
                                    return response.json();
                                })
                                .then((json) => {
                                    const extra = picker.extra && picker.extra.name.toLowerCase().includes(query.toLowerCase()) ? [picker.extra] : [];
                                    callback([...extra, ...(json.items || [])]);
                                })
                                .catch(() => callback());
                        };
                        settings.onFocus = function () {
                            this.clearOptions();
                            this.load('');
                        };
                    }

                    new TomSelect(el, settings);
                });

                $wire.on('lead-board-filters-cleared', () => {
                    pickers.forEach((picker) => {
                        const control = document.getElementById(picker.id)?.tomselect;
                        if (!control) return;
                        control.syncing = true;
                        control.clear(true);
                        control.syncing = false;
                    });
                });

                const clearOver = () => root.querySelectorAll('.lbx-col.over').forEach((column) => column.classList.remove('over'));

                root.addEventListener('dragstart', (event) => {
                    const card = event.target.closest('.lc[draggable="true"]');
                    if (!card) return;
                    drag = { id: Number(card.dataset.id), from: card.dataset.status, card };
                    event.dataTransfer.effectAllowed = 'move';
                    event.dataTransfer.setData('text/plain', card.dataset.id);
                    requestAnimationFrame(() => card.classList.add('drag-src'));
                });

                root.addEventListener('dragend', () => {
                    if (drag) drag.card.classList.remove('drag-src');
                    drag = null;
                    clearOver();
                });

                root.addEventListener('dragover', (event) => {
                    if (!drag) return;
                    const scroller = root.querySelector('.lbx-bscroll');
                    const box = scroller.getBoundingClientRect();
                    if (event.clientY > box.top && event.clientY < box.bottom) {
                        if (event.clientX < box.left + 70) scroller.scrollLeft -= 18;
                        else if (event.clientX > box.right - 70) scroller.scrollLeft += 18;
                    }
                    const column = event.target.closest('.lbx-col[data-drop]');
                    if (!column) return;
                    event.preventDefault();
                    event.dataTransfer.dropEffect = 'move';
                    if (!column.classList.contains('over')) {
                        clearOver();
                        column.classList.add('over');
                    }
                });

                root.addEventListener('drop', (event) => {
                    const column = event.target.closest('.lbx-col[data-drop]');
                    if (!drag || !column) return;
                    event.preventDefault();
                    clearOver();
                    const { id, from, card } = drag;
                    const to = column.dataset.drop;
                    if (to === from) return;
                    const body = column.querySelector('.lbx-col-body');
                    const placeholder = body.querySelector('.empty');
                    if (placeholder) placeholder.remove();
                    body.prepend(card);
                    card.classList.add('is-moving');
                    $wire.moveLead(id, to);
                });
            })()
        </script>
    @endscript
</div>
