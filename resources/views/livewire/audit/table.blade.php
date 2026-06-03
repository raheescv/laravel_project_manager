@php
    $eventBadge = fn ($e) => match ($e) {
        'created' => 'success',
        'updated' => 'primary',
        'deleted' => 'danger',
        'restored' => 'info',
        default => 'secondary',
    };
    $palette = ['primary', 'success', 'warning', 'danger', 'info', 'secondary'];
@endphp
<div>
    {{-- Summary Card --}}
    <div class="card border-0 shadow-sm mb-3 audit-summary">
        <div class="card-body p-3 p-md-4">
            <div class="row g-3 align-items-center">
                <div class="col-md-5">
                    <div class="d-flex align-items-center gap-3">
                        <div class="audit-summary-icon">
                            <i class="fa fa-history"></i>
                        </div>
                        <div>
                            <div class="text-muted small text-uppercase fw-semibold" style="letter-spacing:.5px;">Audit History</div>
                            <h4 class="fw-bold mb-0">{{ $model }} <span class="text-muted fw-normal">#{{ $table_id }}</span></h4>
                            <div class="text-muted small mt-1">
                                <i class="fa fa-database me-1"></i>App\Models\{{ $model }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-7">
                    <div class="row g-2">
                        <div class="col-6 col-md-3">
                            <div class="audit-stat">
                                <div class="audit-stat-label">Total</div>
                                <div class="audit-stat-value">{{ $stats['total'] }}</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="audit-stat">
                                <div class="audit-stat-label">First</div>
                                <div class="audit-stat-value small fw-semibold">
                                    @if ($stats['first_at'])
                                        {{ $stats['first_at']->format('M d, Y') }}
                                    @else
                                        &mdash;
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="audit-stat">
                                <div class="audit-stat-label">Last</div>
                                <div class="audit-stat-value small fw-semibold">
                                    @if ($stats['last_at'])
                                        {{ $stats['last_at']->diffForHumans() }}
                                    @else
                                        &mdash;
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="audit-stat">
                                <div class="audit-stat-label">Contributors</div>
                                <div class="audit-stat-value d-flex align-items-center gap-1">
                                    @foreach ($stats['contributors']->take(4) as $i => $u)
                                        @php $name = $u['name']; $color = $palette[$i % count($palette)]; @endphp
                                        <span class="audit-user-avatar bg-{{ $color }}-subtle text-{{ $color }}" title="{{ $name }}">
                                            {{ strtoupper(mb_substr($name, 0, 1)) }}
                                        </span>
                                    @endforeach
                                    @if ($stats['contributors']->count() > 4)
                                        <span class="audit-user-avatar bg-light text-muted border" title="{{ $stats['contributors']->count() - 4 }} more">
                                            +{{ $stats['contributors']->count() - 4 }}
                                        </span>
                                    @endif
                                    @if ($stats['contributors']->isEmpty())
                                        <span class="text-muted small">&mdash;</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters + View Toggle --}}
    <div class="card border-0 shadow-sm mb-3 audit-filters">
        <div class="card-body p-3">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md">
                    <label class="form-label small text-muted mb-1"><i class="fa fa-search me-1"></i>Search</label>
                    <input type="text" class="form-control form-control-sm" wire:model.live.debounce.300ms="search" placeholder="Field or value...">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted mb-1"><i class="fa fa-bolt me-1"></i>Event</label>
                    <select class="form-select form-select-sm" wire:model.live="event">
                        <option value="">All</option>
                        @foreach ($events as $ev)
                            <option value="{{ $ev }}">{{ ucfirst($ev) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted mb-1"><i class="fa fa-user me-1"></i>User</label>
                    <select class="form-select form-select-sm" wire:model.live="user_id">
                        <option value="">All</option>
                        @foreach ($users as $u)
                            <option value="{{ $u['id'] }}">{{ $u['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted mb-1"><i class="fa fa-calendar me-1"></i>From</label>
                    <input type="date" class="form-control form-control-sm" wire:model.live="date_from">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted mb-1"><i class="fa fa-calendar me-1"></i>To</label>
                    <input type="date" class="form-control form-control-sm" wire:model.live="date_to">
                </div>
                <div class="col-12 col-md-auto d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="resetFilters" title="Clear filters">
                        <i class="fa fa-refresh"></i>
                    </button>
                    <div class="btn-group btn-group-sm" role="group" aria-label="View">
                        <button type="button" class="btn {{ $view === 'timeline' ? 'btn-primary' : 'btn-outline-primary' }}" wire:click="setView('timeline')" title="Timeline view">
                            <i class="fa fa-list-ul"></i>
                        </button>
                        <button type="button" class="btn {{ $view === 'table' ? 'btn-primary' : 'btn-outline-primary' }}" wire:click="setView('table')" title="Table view">
                            <i class="fa fa-table"></i>
                        </button>
                    </div>
                </div>
            </div>
            @if ($stats['shown'] !== $stats['total'])
                <div class="small text-muted mt-2">
                    <i class="fa fa-filter me-1"></i>Showing {{ $stats['shown'] }} of {{ $stats['total'] }} entries
                </div>
            @endif
        </div>
    </div>

    {{-- Main view --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-3 p-md-4">
            @if ($audits->count() > 0)
                <x-audit.table :audits="$audits" :view="$view" />
            @else
                <div class="text-center py-5">
                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 72px; height: 72px;">
                        <i class="fa fa-history text-muted" style="font-size: 1.75rem;"></i>
                    </div>
                    <h6 class="text-muted mb-1">
                        @if ($stats['total'] === 0)
                            No Audit Records Found
                        @else
                            No matches for the current filters
                        @endif
                    </h6>
                    <p class="text-muted mb-0 small">
                        @if ($stats['total'] === 0)
                            No changes have been recorded for this item yet.
                        @else
                            Try clearing filters or broadening your search.
                        @endif
                    </p>
                </div>
            @endif
        </div>
    </div>

    @once
        @push('styles')
            <style>
                .audit-summary-icon {
                    width: 56px;
                    height: 56px;
                    border-radius: 14px;
                    background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
                    color: #4f46e5;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 1.5rem;
                    flex-shrink: 0;
                }
                .audit-stat {
                    background: #f8fafc;
                    border-radius: 8px;
                    padding: 10px 12px;
                    height: 100%;
                }
                .audit-stat-label {
                    font-size: 0.7rem;
                    text-transform: uppercase;
                    letter-spacing: .5px;
                    color: #64748b;
                    font-weight: 600;
                }
                .audit-stat-value {
                    font-size: 1.1rem;
                    font-weight: 700;
                    color: #0f172a;
                    margin-top: 2px;
                }
                .audit-user-avatar {
                    width: 26px;
                    height: 26px;
                    border-radius: 50%;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    font-weight: 700;
                    font-size: 0.7rem;
                    margin-left: -4px;
                }
                .audit-user-avatar:first-child { margin-left: 0; }
                .audit-filters .form-label { font-weight: 600; }
            </style>
        @endpush
    @endonce
</div>
