<div>
    {{-- Header --}}
    <div class="card mb-3 shadow-sm border-0">
        <div class="card-body py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="d-flex align-items-center">
                <span class="rounded-circle bg-warning bg-opacity-10 text-warning d-inline-flex align-items-center justify-content-center me-3"
                      style="width:42px;height:42px;">
                    <i class="fa fa-bell-o fs-5"></i>
                </span>
                <div>
                    <h5 class="mb-0 fw-semibold">Alert Rules</h5>
                    <small class="text-muted">Route trading events to database, Telegram or log</small>
                </div>
            </div>
            <a href="{{ route('flat_trade::risk') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fa fa-arrow-left me-1"></i> Back to Live Ops
            </a>
        </div>
    </div>

    {{-- New rule form --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white">
            <h6 class="card-title mb-0">
                <i class="fa fa-plus-circle text-success me-2"></i> Create new rule
            </h6>
        </div>
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small text-muted">Name</label>
                    <input type="text" class="form-control form-control-sm" placeholder="e.g. notify on big losses" wire:model="form.name">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Event</label>
                    <select class="form-select form-select-sm" wire:model="form.event">
                        <option value="OrderPlaced">OrderPlaced</option>
                        <option value="OrderRejected">OrderRejected</option>
                        <option value="StopLossHit">StopLossHit</option>
                        <option value="PositionClosed">PositionClosed</option>
                        <option value="CircuitBreakerTripped">CircuitBreakerTripped</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">Channels (comma-separated)</label>
                    <input type="text" class="form-control form-control-sm" placeholder="database, telegram, log" wire:model="form.channels">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Severity</label>
                    <select class="form-select form-select-sm" wire:model="form.severity">
                        <option value="info">info</option>
                        <option value="warning">warning</option>
                        <option value="breaker">breaker</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button wire:click="save" class="btn btn-sm btn-primary w-100">
                        <i class="fa fa-floppy-o me-1"></i> Save rule
                    </button>
                </div>
            </div>
            <div class="form-text small mt-2">
                <i class="fa fa-info-circle me-1"></i>
                Each rule fires when its <strong>event</strong> is dispatched. Channels must match registered codes:
                <code>database</code>, <code>telegram</code>, <code>log</code>.
            </div>
        </div>
    </div>

    {{-- Rules table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="card-title mb-0">
                <i class="fa fa-list-ul text-primary me-2"></i> Configured rules
            </h6>
            <span class="badge bg-light text-dark">{{ $rules->count() }} rule{{ $rules->count() === 1 ? '' : 's' }}</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Event</th>
                        <th>Channels</th>
                        <th>Severity</th>
                        <th class="text-center">Active</th>
                        <th class="text-end"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rules as $r)
                        <tr>
                            <td class="fw-semibold">{{ $r->name }}</td>
                            <td><code>{{ $r->event }}</code></td>
                            <td>
                                @foreach ($r->channels ?? [] as $c)
                                    <span class="badge bg-light text-dark border me-1">{{ $c }}</span>
                                @endforeach
                            </td>
                            <td>
                                <span class="badge bg-{{ $r->severity === 'breaker' ? 'danger' : ($r->severity === 'warning' ? 'warning' : 'info') }} bg-opacity-10 text-{{ $r->severity === 'breaker' ? 'danger' : ($r->severity === 'warning' ? 'warning' : 'info') }}">
                                    {{ $r->severity }}
                                </span>
                            </td>
                            <td class="text-center">
                                <button wire:click="toggle({{ $r->id }})"
                                        class="btn btn-sm {{ $r->is_active ? 'btn-success' : 'btn-outline-secondary' }}">
                                    <i class="fa fa-{{ $r->is_active ? 'check-circle' : 'circle-o' }}"></i>
                                </button>
                            </td>
                            <td class="text-end">
                                <button wire:click="delete({{ $r->id }})"
                                        wire:confirm="Delete rule '{{ $r->name }}'?"
                                        class="btn btn-sm btn-outline-danger">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="fa fa-bell-slash-o fs-2 d-block mb-2 opacity-50"></i>
                                No alert rules configured yet
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
