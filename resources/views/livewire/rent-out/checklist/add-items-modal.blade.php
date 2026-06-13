<div class="modal-content checklist-add-modal border-0">
    <style>
        .checklist-add-modal {
            /* Drive every accent from the app theme's primary color.
               Falls back to a sensible blue if --bs-primary is undefined. */
            --cam-primary: var(--bs-primary, var(--color-primary, #2563eb));
            --cam-primary-rgb: var(--bs-primary-rgb, 37, 99, 235);
            --cam-primary-tint: color-mix(in srgb, var(--cam-primary) 10%, #fff);
            --cam-primary-tint-strong: color-mix(in srgb, var(--cam-primary) 16%, #fff);
            --cam-on-primary: #fff;
            --cam-ink: #1e293b;
            --cam-ink-muted: #475569;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 24px 60px -12px rgba(15, 23, 42, .35);
        }
        .checklist-add-modal .cam-header {
            background: linear-gradient(135deg,
                color-mix(in srgb, var(--cam-primary) 85%, #000) 0%,
                var(--cam-primary) 100%);
            color: var(--cam-on-primary);
            border: 0;
            padding: 1rem 1.25rem;
        }
        .checklist-add-modal .cam-header .modal-title {
            font-weight: 600;
            letter-spacing: .2px;
            display: flex;
            align-items: center;
            gap: .6rem;
        }
        .checklist-add-modal .cam-header .cam-icon {
            width: 32px;
            height: 32px;
            border-radius: 9px;
            background: rgba(255, 255, 255, .14);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: .9rem;
        }
        .checklist-add-modal .cam-header .btn-close {
            filter: invert(1) grayscale(1);
            opacity: .7;
        }
        .checklist-add-modal .cam-header .btn-close:hover { opacity: 1; }
        .checklist-add-modal .modal-body { padding: 1.1rem 1.25rem; background: #f8fafc; }

        .checklist-add-modal .cam-search .input-group-text {
            background: #fff;
            border-right: 0;
            color: var(--cam-primary);
        }
        .checklist-add-modal .cam-search .form-control::placeholder { color: #94a3b8; opacity: 1; }
        .checklist-add-modal .cam-search .form-control { color: var(--cam-ink); }
        .checklist-add-modal .cam-filter { color: var(--cam-ink); }
        .checklist-add-modal .cam-search .form-control,
        .checklist-add-modal .cam-search .btn,
        .checklist-add-modal .cam-filter {
            border-color: #e2e8f0;
            box-shadow: none;
        }
        .checklist-add-modal .cam-search .form-control { border-left: 0; }
        .checklist-add-modal .cam-search .form-control:focus { box-shadow: none; }
        .checklist-add-modal .cam-toolbar .btn-toggle {
            border: 1px solid #e2e8f0;
            background: #fff;
            color: #475569;
            font-weight: 500;
        }
        .checklist-add-modal .cam-toolbar .btn-toggle:hover {
            background: var(--cam-primary-tint);
            border-color: var(--cam-primary);
            color: var(--cam-primary);
        }

        .checklist-add-modal .cam-list {
            background: #fff;
            border: 1px solid #e9eef5;
            border-radius: 12px;
            max-height: 46vh;
            overflow: auto;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .6);
        }
        .checklist-add-modal .cam-list::-webkit-scrollbar { width: 9px; }
        .checklist-add-modal .cam-list::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 9px;
            border: 2px solid #fff;
        }
        .checklist-add-modal .cam-group {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: .5rem .9rem;
            font-weight: 700;
            font-size: .68rem;
            letter-spacing: .8px;
            text-transform: uppercase;
            color: #64748b;
            background: #f1f5f9;
            border-top: 1px solid #e9eef5;
            border-bottom: 1px solid #e9eef5;
        }
        .checklist-add-modal .cam-list > .cam-group:first-child { border-top: 0; }
        .checklist-add-modal .cam-group .badge {
            background: var(--cam-primary) !important;
            color: var(--cam-on-primary);
            font-weight: 600;
            letter-spacing: .3px;
        }

        .checklist-add-modal .cam-row {
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: .65rem .9rem;
            margin: 0;
            border-bottom: 1px solid #f1f5f9;
            cursor: pointer;
            transition: background .12s ease, box-shadow .12s ease;
            position: relative;
        }
        .checklist-add-modal .cam-row:hover { background: #f8fafc; }
        .checklist-add-modal .cam-row .form-check-input {
            margin: 0;
            width: 1.15rem;
            height: 1.15rem;
            border-color: #cbd5e1;
            cursor: pointer;
        }
        .checklist-add-modal .cam-row .form-check-input:checked {
            background-color: var(--cam-primary);
            border-color: var(--cam-primary);
        }
        .checklist-add-modal .cam-row .cam-name { color: var(--cam-ink); font-size: .9rem; }

        .checklist-add-modal .cam-row.is-selected {
            background: var(--cam-primary-tint);
            box-shadow: inset 3px 0 0 var(--cam-primary);
        }
        .checklist-add-modal .cam-row.is-selected .cam-name {
            font-weight: 600;
            color: color-mix(in srgb, var(--cam-primary) 75%, #000);
        }
        .checklist-add-modal .cam-row.is-added {
            opacity: .65;
            cursor: default;
            background: #fbfdfb;
        }
        .checklist-add-modal .cam-row.is-added:hover { background: #fbfdfb; }

        .checklist-add-modal .cam-pill-added {
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
            font-weight: 600;
            border-radius: 999px;
            padding: .2rem .55rem;
            font-size: .7rem;
        }
        .checklist-add-modal .cam-pill-sel {
            background: var(--cam-primary);
            color: var(--cam-on-primary);
            border-radius: 999px;
            width: 22px;
            height: 22px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: .68rem;
        }

        .checklist-add-modal .modal-footer {
            background: #fff;
            border-top: 1px solid #eef2f7;
            padding: .85rem 1.25rem;
        }
        .checklist-add-modal .cam-count-chip {
            background: var(--cam-primary);
            color: var(--cam-on-primary);
            font-weight: 700;
            border-radius: 8px;
            padding: .15rem .5rem;
            font-size: .8rem;
        }
        .checklist-add-modal .btn-add-selected {
            background: linear-gradient(135deg,
                var(--cam-primary),
                color-mix(in srgb, var(--cam-primary) 80%, #000));
            border: 0;
            color: var(--cam-on-primary);
            font-weight: 600;
            padding: .45rem 1.1rem;
            box-shadow: 0 6px 16px -6px rgba(var(--cam-primary-rgb), .7);
        }
        .checklist-add-modal .btn-add-selected:disabled { opacity: .45; box-shadow: none; }
    </style>

    <div class="modal-header cam-header">
        <h6 class="modal-title mb-0 text-white">
            <span class="cam-icon"><i class="fa fa-list-ul"></i></span>
            Add Checklist Items
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" wire:click="clearSelection"></button>
    </div>

    <div class="modal-body">
        {{-- Search + filter + select-all --}}
        <div class="d-flex gap-2 mb-3 flex-wrap cam-toolbar">
            <div class="input-group input-group-sm flex-grow-1 cam-search" style="min-width:200px;">
                <span class="input-group-text"><i class="fa fa-search"></i></span>
                <input class="form-control" placeholder="Search items…" wire:model.live.debounce.300ms="search">
                @if ($search !== '' || $filterCategory !== '' || $filterPropertyType !== '')
                    <button type="button" class="btn btn-outline-secondary" wire:click="clearSearch" title="Clear search">
                        <i class="fa fa-times"></i>
                    </button>
                @endif
            </div>
            <select class="form-select form-select-sm cam-filter" style="width:180px;" wire:model.live="filterCategory">
                <option value="">All categories</option>
                @foreach ($categories as $c)
                    <option value="{{ $c }}">{{ $c }}</option>
                @endforeach
            </select>
            <select class="form-select form-select-sm cam-filter" style="width:180px;" wire:model.live="filterPropertyType">
                <option value="">All property types</option>
                @foreach ($propertyTypes as $pt)
                    <option value="{{ $pt->id }}">{{ $pt->name }}</option>
                @endforeach
            </select>
            <button type="button" class="btn btn-sm btn-toggle" wire:click="toggleVisible" @disabled($shownCount === 0)>
                <i class="fa {{ $allShownSelected ? 'fa-square-o' : 'fa-check-square-o' }} me-1"></i>
                {{ $allShownSelected ? 'Unselect shown' : 'Select all shown' }}
            </button>
        </div>

        {{-- Summary line --}}
        <div class="d-flex align-items-center justify-content-between mb-2 small text-muted">
            <span>
                @if ($loaded)
                    Showing <strong class="text-dark">{{ $shownCount }}</strong> item{{ $shownCount === 1 ? '' : 's' }}
                    in <strong class="text-dark">{{ $categoryCount }}</strong> categor{{ $categoryCount === 1 ? 'y' : 'ies' }}
                @endif
            </span>
            @if (count($selectedIds) > 0)
                <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" wire:click="clearSelection">
                    <i class="fa fa-eraser me-1"></i>Clear selection
                </button>
            @endif
        </div>

        <div class="small text-muted mb-2">
            Tick the items present in this unit, then <strong>Add Selected</strong>.
            Already-added items are disabled.
        </div>

        {{-- Item list --}}
        <div class="cam-list">
            @forelse ($items as $category => $group)
                @php
                    $groupSelected = $group->filter(fn ($i) => in_array((string) $i->id, $selectedIds, true))->count();
                @endphp
                <div wire:key="grp-{{ \Illuminate\Support\Str::slug($category ?: 'uncategorized') }}"
                    class="cam-group sticky-top">
                    <span>{{ $category ?: 'Uncategorized' }}</span>
                    @if ($groupSelected > 0)
                        <span class="badge rounded-pill">{{ $groupSelected }} selected</span>
                    @endif
                </div>
                @foreach ($group as $item)
                    @php
                        $isAdded = in_array((int) $item->id, $excludeIds, true);
                        $isSelected = in_array((string) $item->id, $selectedIds, true);
                    @endphp
                    <label wire:key="itm-{{ $item->id }}"
                        class="cam-row {{ $isAdded ? 'is-added' : '' }} {{ $isSelected ? 'is-selected' : '' }}">
                        <input type="checkbox" class="form-check-input" value="{{ $item->id }}"
                            wire:model.live="selectedIds" @disabled($isAdded)>
                        @if ($item->image_path)
                            <img src="{{ asset('storage/' . $item->image_path) }}" alt=""
                                class="zoomable" data-img="{{ asset('storage/' . $item->image_path) }}"
                                style="width:30px; height:30px; object-fit:cover; border-radius:6px; border:1px solid #e2e8f0; cursor:zoom-in;"
                                title="Click to enlarge">
                        @else
                            <span style="width:30px; height:30px; border-radius:6px; border:1px solid #eef2f7; background:#f8fafc; display:inline-flex; align-items:center; justify-content:center; color:#cbd5e1;">
                                <i class="fa fa-picture-o"></i>
                            </span>
                        @endif
                        <span class="cam-name">{{ $item->name }}</span>
                        @if ($isAdded)
                            <span class="cam-pill-added ms-auto">
                                <i class="fa fa-check me-1"></i>added
                            </span>
                        @elseif ($isSelected)
                            <span class="cam-pill-sel ms-auto"><i class="fa fa-check"></i></span>
                        @endif
                    </label>
                @endforeach
            @empty
                <div class="text-center text-muted py-4">
                    @if (! $loaded)
                        Open to load items…
                    @elseif ($search !== '' || $filterCategory !== '' || $filterPropertyType !== '')
                        <i class="fa fa-search-minus fa-2x d-block mb-2 opacity-50"></i>
                        No items match your search.
                        <div><button type="button" class="btn btn-link btn-sm" wire:click="clearSearch">Clear filters</button></div>
                    @else
                        No active checklist items found.
                    @endif
                </div>
            @endforelse
        </div>
    </div>

    <div class="modal-footer">
        <span class="small me-auto">
            <span class="cam-count-chip me-1">{{ count($selectedIds) }}</span> selected
            @if (count($excludeIds) > 0)
                <span class="text-muted">· {{ count($excludeIds) }} already added</span>
            @endif
        </span>
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal" wire:click="clearSelection">Cancel</button>
        <button type="button" class="btn btn-primary btn-sm btn-add-selected" wire:click="addSelected" @disabled(count($selectedIds) === 0)>
            <i class="fa fa-plus me-1"></i> Add Selected
            @if (count($selectedIds) > 0)
                ({{ count($selectedIds) }})
            @endif
        </button>
    </div>
</div>
