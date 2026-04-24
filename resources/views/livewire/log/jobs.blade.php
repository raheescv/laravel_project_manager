<div x-data="jobPayloadViewer()">
    <div class="card shadow-sm">
        <div class="card-header bg-light py-3">
            <div class="row g-3 align-items-end">
                <div class="col-lg-5">
                    <label class="form-label small fw-semibold text-muted">Search</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-secondary-subtle">
                            <i class="demo-psi-magnifi-glass"></i>
                        </span>
                        <input type="text" wire:model.live="search" class="form-control border-secondary-subtle shadow-sm"
                            placeholder="Search by job, queue, payload, or ID">
                    </div>
                </div>
                <div class="col-lg-3">
                    <label class="form-label small fw-semibold text-muted">Queue</label>
                    <select wire:model.live="queue" class="form-select form-select-sm border-secondary-subtle shadow-sm">
                        <option value="">All Queues</option>
                        @foreach ($queues as $queueName)
                            <option value="{{ $queueName }}">{{ $queueName }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2">
                    <label class="form-label small fw-semibold text-muted">Status</label>
                    <select wire:model.live="status" class="form-select form-select-sm border-secondary-subtle shadow-sm">
                        <option value="">All Statuses</option>
                        <option value="queued">Queued</option>
                        <option value="processing">Processing</option>
                        <option value="retrying">Retrying</option>
                        <option value="delayed">Delayed</option>
                    </select>
                </div>
                <div class="col-lg-2">
                    <label class="form-label small fw-semibold text-muted">Rows</label>
                    <select wire:model.live="limit" class="form-select form-select-sm border-secondary-subtle shadow-sm">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-nowrap">
                        <tr>
                            <th class="ps-3">
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="id" label="ID" />
                            </th>
                            <th>
                                <span class="text-muted small fw-semibold">Job</span>
                            </th>
                            <th>
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="queue" label="Queue" />
                            </th>
                            <th>
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="attempts" label="Attempts" />
                            </th>
                            <th>
                                <span class="text-muted small fw-semibold">Status</span>
                            </th>
                            <th>
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="created_at" label="Queued At" />
                            </th>
                            <th>
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="available_at" label="Available At" />
                            </th>
                            <th>
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="reserved_at" label="Reserved At" />
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data as $item)
                            @php
                                $status = $this->resolveStatus($item->reserved_at, $item->available_at, $item->attempts);
                            @endphp
                            <tr wire:key="job-{{ $item->id }}">
                                <td class="ps-3">
                                    <span class="badge bg-secondary rounded-pill">{{ $item->id }}</span>
                                </td>
                                <td role="button" wire:click="loadPayload({{ $item->id }})"
                                    wire:loading.class="opacity-50" wire:target="loadPayload({{ $item->id }})">
                                    <div class="fw-semibold text-dark">{{ $this->resolveJobName($item->payload) }}</div>
                                    <div class="small text-muted text-break">{{ \Illuminate\Support\Str::limit($item->payload, 120) }}</div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ $item->queue }}</span>
                                </td>
                                <td>{{ $item->attempts }}</td>
                                <td>
                                    <span class="badge {{ $this->statusBadgeClass($status) }}">{{ $status }}</span>
                                </td>
                                <td>
                                    <span class="small text-muted text-nowrap">{{ $this->formatTimestamp($item->created_at) }}</span>
                                </td>
                                <td>
                                    <span class="small text-muted text-nowrap">{{ $this->formatTimestamp($item->available_at) }}</span>
                                </td>
                                <td>
                                    <span class="small text-muted">{{ $this->formatTimestamp($item->reserved_at) ?? 'Not reserved' }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="demo-psi-information display-6"></i>
                                        <p class="mt-2 mb-0">No queued jobs found.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($data->hasPages())
            <div class="card-footer bg-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted small">
                        Showing {{ $data->firstItem() }} to {{ $data->lastItem() }} of {{ $data->total() }} jobs
                    </div>
                    <div>{{ $data->links() }}</div>
                </div>
            </div>
        @endif
    </div>

    {{-- Job Payload Details Modal --}}
    <div class="modal fade" id="jobPayloadModal" tabindex="-1" aria-labelledby="jobPayloadModalLabel"
        aria-hidden="true" wire:ignore.self>
        <div
            class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered modal-fullscreen-sm-down">
            <div class="modal-content border-0 shadow-lg">

                {{-- HEADER --}}
                <template x-if="job">
                    <div class="modal-header bg-white border-bottom py-2 px-3">
                        <div class="flex-grow-1 min-w-0 pe-2">
                            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                <span class="badge" :class="job.statusBadge" x-text="job.status"></span>
                                <span class="text-muted small">
                                    ID <span class="fw-semibold text-dark" x-text="'#' + job.id"></span>
                                </span>
                                <span class="text-muted small">&middot;</span>
                                <span class="badge bg-light text-dark border fw-normal"
                                    x-text="job.queue"></span>
                            </div>
                            <h6 class="modal-title fw-bold text-dark mb-0 text-truncate"
                                id="jobPayloadModalLabel" x-text="job.jobName"></h6>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                </template>

                {{-- SCROLLABLE BODY --}}
                <template x-if="job">
                    <div class="modal-body p-3 bg-body-tertiary">

                        {{-- Stat strip --}}
                        <div class="row g-2 mb-3">
                            <div class="col-6 col-lg-3">
                                <div class="bg-white rounded border px-2 py-2 h-100">
                                    <div class="text-uppercase fw-semibold text-muted"
                                        style="font-size: .65rem; letter-spacing: .05em;">Attempts</div>
                                    <div class="fw-bold text-dark lh-1 mt-1" style="font-size: 1.05rem;"
                                        x-text="job.attempts"></div>
                                </div>
                            </div>
                            <div class="col-6 col-lg-3">
                                <div class="bg-white rounded border px-2 py-2 h-100">
                                    <div class="text-uppercase fw-semibold text-muted"
                                        style="font-size: .65rem; letter-spacing: .05em;">Max Tries</div>
                                    <div class="fw-bold text-dark lh-1 mt-1" style="font-size: 1.05rem;"
                                        x-text="job.maxTries ?? '—'"></div>
                                </div>
                            </div>
                            <div class="col-6 col-lg-3">
                                <div class="bg-white rounded border px-2 py-2 h-100">
                                    <div class="text-uppercase fw-semibold text-muted"
                                        style="font-size: .65rem; letter-spacing: .05em;">Timeout</div>
                                    <div class="fw-bold text-dark lh-1 mt-1" style="font-size: 1.05rem;"
                                        x-text="job.timeout ? job.timeout + 's' : '—'"></div>
                                </div>
                            </div>
                            <div class="col-6 col-lg-3">
                                <div class="bg-white rounded border px-2 py-2 h-100">
                                    <div class="text-uppercase fw-semibold text-muted"
                                        style="font-size: .65rem; letter-spacing: .05em;">Backoff</div>
                                    <div class="fw-bold text-dark lh-1 mt-1 text-break"
                                        style="font-size: 1.05rem;" x-text="job.backoff ?? '—'"></div>
                                </div>
                            </div>
                        </div>

                        {{-- Identity --}}
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <i class="demo-psi-code text-primary small"></i>
                            <h6 class="text-uppercase fw-bold text-muted mb-0"
                                style="font-size: .7rem; letter-spacing: .05em;">Identity</h6>
                        </div>
                        <div class="bg-white rounded border overflow-hidden mb-3">
                            <dl class="row g-0 mb-0 small">
                                <dt
                                    class="col-sm-4 text-muted fw-semibold border-bottom px-2 py-1 bg-body-tertiary">
                                    Display Name</dt>
                                <dd class="col-sm-8 mb-0 text-dark text-break border-bottom px-2 py-1"
                                    x-text="job.displayName || '—'"></dd>

                                <dt
                                    class="col-sm-4 text-muted fw-semibold border-bottom px-2 py-1 bg-body-tertiary">
                                    Job Class</dt>
                                <dd class="col-sm-8 mb-0 text-break border-bottom px-2 py-1">
                                    <template x-if="job.jobClass">
                                        <code class="text-primary" x-text="job.jobClass"></code>
                                    </template>
                                    <template x-if="!job.jobClass">
                                        <span class="text-muted">—</span>
                                    </template>
                                </dd>

                                <dt
                                    class="col-sm-4 text-muted fw-semibold border-bottom px-2 py-1 bg-body-tertiary">
                                    UUID</dt>
                                <dd class="col-sm-8 mb-0 text-break border-bottom px-2 py-1">
                                    <template x-if="job.uuid">
                                        <code class="text-muted" x-text="job.uuid"></code>
                                    </template>
                                    <template x-if="!job.uuid">
                                        <span class="text-muted">—</span>
                                    </template>
                                </dd>

                                <dt
                                    class="col-sm-4 text-muted fw-semibold border-bottom px-2 py-1 bg-body-tertiary">
                                    Max Exceptions</dt>
                                <dd class="col-sm-8 mb-0 text-dark border-bottom px-2 py-1"
                                    x-text="job.maxExceptions ?? '—'"></dd>

                                <dt
                                    class="col-sm-4 text-muted fw-semibold border-bottom px-2 py-1 bg-body-tertiary">
                                    Retry Until</dt>
                                <dd class="col-sm-8 mb-0 text-dark border-bottom px-2 py-1"
                                    x-text="job.retryUntil ?? '—'"></dd>

                                <dt class="col-sm-4 text-muted fw-semibold px-2 py-1 bg-body-tertiary">Tags
                                </dt>
                                <dd class="col-sm-8 mb-0 px-2 py-1">
                                    <template x-if="job.tags && job.tags.length">
                                        <div class="d-flex flex-wrap gap-1">
                                            <template x-for="(tag, idx) in job.tags" :key="idx">
                                                <span
                                                    class="badge bg-primary bg-opacity-10 text-primary fw-normal"
                                                    x-text="tag"></span>
                                            </template>
                                        </div>
                                    </template>
                                    <template x-if="!job.tags || !job.tags.length">
                                        <span class="text-muted">—</span>
                                    </template>
                                </dd>
                            </dl>
                        </div>

                        {{-- Timeline --}}
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <i class="demo-psi-clock text-primary small"></i>
                            <h6 class="text-uppercase fw-bold text-muted mb-0"
                                style="font-size: .7rem; letter-spacing: .05em;">Timeline</h6>
                        </div>
                        <div class="bg-white rounded border px-3 py-2 mb-3">
                            <div class="row g-2 small">
                                <div class="col-12 col-md-4">
                                    <div class="d-flex align-items-start gap-2">
                                        <span class="rounded-circle bg-secondary flex-shrink-0 mt-1"
                                            style="width: 8px; height: 8px;"></span>
                                        <div class="flex-grow-1 min-w-0">
                                            <div class="text-muted text-uppercase fw-semibold"
                                                style="font-size: .65rem; letter-spacing: .05em;">Queued At
                                            </div>
                                            <div class="text-dark" x-text="job.createdAt || '—'"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-4">
                                    <div class="d-flex align-items-start gap-2">
                                        <span class="rounded-circle bg-warning flex-shrink-0 mt-1"
                                            style="width: 8px; height: 8px;"></span>
                                        <div class="flex-grow-1 min-w-0">
                                            <div class="text-muted text-uppercase fw-semibold"
                                                style="font-size: .65rem; letter-spacing: .05em;">Available At
                                            </div>
                                            <div class="text-dark" x-text="job.availableAt || '—'"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-4">
                                    <div class="d-flex align-items-start gap-2">
                                        <span class="rounded-circle bg-primary flex-shrink-0 mt-1"
                                            style="width: 8px; height: 8px;"></span>
                                        <div class="flex-grow-1 min-w-0">
                                            <div class="text-muted text-uppercase fw-semibold"
                                                style="font-size: .65rem; letter-spacing: .05em;">Reserved At
                                            </div>
                                            <div class="text-dark"
                                                x-text="job.reservedAt || 'Not reserved'"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Command Summary (human-readable) --}}
                        <template x-if="job.summary">
                            <div>
                                {{-- Listener + Event --}}
                                <template x-if="job.summary.type === 'listener' && job.summary.listener">
                                    <div>
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <i class="demo-psi-headphones text-primary small"></i>
                                            <h6 class="text-uppercase fw-bold text-muted mb-0"
                                                style="font-size: .7rem; letter-spacing: .05em;">Listener</h6>
                                        </div>
                                        <div class="bg-white rounded border overflow-hidden mb-3 small">
                                            <div class="row g-0 border-bottom">
                                                <div
                                                    class="col-sm-4 text-muted fw-semibold px-2 py-1 bg-body-tertiary">
                                                    Listener</div>
                                                <div class="col-sm-8 text-break px-2 py-1">
                                                    <code class="text-primary"
                                                        x-text="job.summary.listener.classShort || job.summary.listener.class"></code>
                                                    <span class="text-muted ms-1"
                                                        x-text="'::' + (job.summary.listener.method || 'handle') + '()'"></span>
                                                    <div class="text-muted small"
                                                        x-show="job.summary.listener.class && job.summary.listener.classShort && job.summary.listener.class !== job.summary.listener.classShort"
                                                        x-text="job.summary.listener.class"></div>
                                                </div>
                                            </div>
                                            <template x-if="job.summary.event && (job.summary.event.classShort || job.summary.event.class)">
                                                <div class="row g-0">
                                                    <div class="col-sm-4 text-muted fw-semibold px-2 py-1 bg-body-tertiary">
                                                        Event</div>
                                                    <div class="col-sm-8 text-break px-2 py-1">
                                                        <code class="text-dark"
                                                            x-text="job.summary.event.classShort || job.summary.event.class"></code>
                                                        <div class="text-muted small"
                                                            x-show="job.summary.event.class && job.summary.event.classShort && job.summary.event.class !== job.summary.event.classShort"
                                                            x-text="job.summary.event.class"></div>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>

                                {{-- Target Model (audit dispatch) --}}
                                <template x-if="job.summary.model">
                                    <div>
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <i class="demo-psi-database text-primary small"></i>
                                            <h6 class="text-uppercase fw-bold text-muted mb-0"
                                                style="font-size: .7rem; letter-spacing: .05em;">Target</h6>
                                        </div>
                                        <div class="bg-white rounded border overflow-hidden mb-3 small">
                                            <div class="row g-0 border-bottom">
                                                <div
                                                    class="col-sm-4 text-muted fw-semibold px-2 py-1 bg-body-tertiary">
                                                    Model</div>
                                                <div class="col-sm-8 text-break px-2 py-1">
                                                    <code class="text-primary"
                                                        x-text="job.summary.model.classShort || job.summary.model.class"></code>
                                                    <span class="text-muted ms-1"
                                                        x-show="job.summary.model.id !== null && job.summary.model.id !== undefined"
                                                        x-text="'#' + job.summary.model.id"></span>
                                                    <div class="text-muted small"
                                                        x-show="job.summary.model.class && job.summary.model.classShort && job.summary.model.class !== job.summary.model.classShort"
                                                        x-text="job.summary.model.class"></div>
                                                </div>
                                            </div>
                                            <template x-if="job.summary.model.event">
                                                <div class="row g-0">
                                                    <div class="col-sm-4 text-muted fw-semibold px-2 py-1 bg-body-tertiary">
                                                        Action</div>
                                                    <div class="col-sm-8 px-2 py-1">
                                                        <span
                                                            class="badge bg-info bg-opacity-10 text-info-emphasis text-uppercase fw-semibold"
                                                            style="font-size: .7rem;"
                                                            x-text="job.summary.model.event"></span>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>

                                {{-- Field changes --}}
                                <template x-if="job.summary.changes && job.summary.changes.length">
                                    <div>
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <i class="demo-psi-repeat text-primary small"></i>
                                            <h6 class="text-uppercase fw-bold text-muted mb-0"
                                                style="font-size: .7rem; letter-spacing: .05em;">Changes</h6>
                                        </div>
                                        <div class="bg-white rounded border overflow-hidden mb-3">
                                            <table class="table table-sm align-middle mb-0 small">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th class="ps-2 py-1" style="width: 25%;">Field</th>
                                                        <th class="py-1" style="width: 37.5%;">Old</th>
                                                        <th class="pe-2 py-1" style="width: 37.5%;">New</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <template x-for="(change, idx) in job.summary.changes"
                                                        :key="idx">
                                                        <tr>
                                                            <td class="ps-2 py-1 fw-semibold text-dark text-break"
                                                                x-text="change.field"></td>
                                                            <td class="py-1 text-danger-emphasis text-break"
                                                                x-text="change.old"></td>
                                                            <td class="pe-2 py-1 text-success-emphasis text-break"
                                                                x-text="change.new"></td>
                                                        </tr>
                                                    </template>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </template>

                                {{-- User & request context --}}
                                <template x-if="job.summary.user || job.summary.context">
                                    <div>
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <i class="demo-psi-male text-primary small"></i>
                                            <h6 class="text-uppercase fw-bold text-muted mb-0"
                                                style="font-size: .7rem; letter-spacing: .05em;">Actor &amp;
                                                Request</h6>
                                        </div>
                                        <div class="bg-white rounded border overflow-hidden mb-3 small">
                                            <template x-if="job.summary.user">
                                                <div class="row g-0 border-bottom">
                                                    <div
                                                        class="col-sm-4 text-muted fw-semibold px-2 py-1 bg-body-tertiary">
                                                        User</div>
                                                    <div class="col-sm-8 text-break px-2 py-1">
                                                        <span class="fw-semibold text-dark"
                                                            x-text="job.summary.user.name || '—'"></span>
                                                        <span class="text-muted ms-1"
                                                            x-show="job.summary.user.id !== null && job.summary.user.id !== undefined"
                                                            x-text="'#' + job.summary.user.id"></span>
                                                        <div class="text-muted"
                                                            x-show="job.summary.user.email"
                                                            x-text="job.summary.user.email"></div>
                                                    </div>
                                                </div>
                                            </template>
                                            <template x-if="job.summary.context && job.summary.context.ipAddress">
                                                <div class="row g-0 border-bottom">
                                                    <div
                                                        class="col-sm-4 text-muted fw-semibold px-2 py-1 bg-body-tertiary">
                                                        IP Address</div>
                                                    <div class="col-sm-8 text-break px-2 py-1">
                                                        <code class="text-dark"
                                                            x-text="job.summary.context.ipAddress"></code>
                                                    </div>
                                                </div>
                                            </template>
                                            <template x-if="job.summary.context && job.summary.context.url">
                                                <div class="row g-0 border-bottom">
                                                    <div
                                                        class="col-sm-4 text-muted fw-semibold px-2 py-1 bg-body-tertiary">
                                                        URL</div>
                                                    <div class="col-sm-8 text-break px-2 py-1">
                                                        <span class="text-dark"
                                                            x-text="job.summary.context.url"></span>
                                                    </div>
                                                </div>
                                            </template>
                                            <template x-if="job.summary.context && job.summary.context.userAgent">
                                                <div class="row g-0">
                                                    <div
                                                        class="col-sm-4 text-muted fw-semibold px-2 py-1 bg-body-tertiary">
                                                        User Agent</div>
                                                    <div class="col-sm-8 text-break px-2 py-1">
                                                        <span class="text-muted"
                                                            x-text="job.summary.context.userAgent"></span>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>

                                {{-- Generic job properties (non-listener jobs) --}}
                                <template x-if="job.summary.properties && job.summary.properties.length">
                                    <div>
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <i class="demo-psi-layers-2 text-primary small"></i>
                                            <h6 class="text-uppercase fw-bold text-muted mb-0"
                                                style="font-size: .7rem; letter-spacing: .05em;">Properties
                                            </h6>
                                        </div>
                                        <div class="bg-white rounded border overflow-hidden mb-3 small">
                                            <template x-for="(prop, idx) in job.summary.properties"
                                                :key="idx">
                                                <div class="row g-0 border-bottom">
                                                    <div
                                                        class="col-sm-4 text-muted fw-semibold px-2 py-1 bg-body-tertiary"
                                                        x-text="prop.key"></div>
                                                    <div class="col-sm-8 text-dark text-break px-2 py-1"
                                                        x-text="prop.value"></div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>

                        {{-- Fallback when we couldn't parse a summary --}}
                        <template x-if="!job.summary">
                            <div class="alert alert-light border d-flex align-items-start gap-2 mb-3 small py-2">
                                <i class="demo-psi-information text-muted mt-1"></i>
                                <div>
                                    <div class="fw-semibold text-dark">No structured summary available</div>
                                    <div class="text-muted">The serialized command couldn't be decoded. Expand the raw payload below to inspect the full contents.</div>
                                </div>
                            </div>
                        </template>

                        {{-- Raw Payload --}}
                        <div class="d-flex justify-content-between align-items-center gap-2 mb-1">
                            <div class="d-flex align-items-center gap-2">
                                <i class="demo-psi-file-2 text-primary small"></i>
                                <h6 class="text-uppercase fw-bold text-muted mb-0"
                                    style="font-size: .7rem; letter-spacing: .05em;">Raw JSON Payload</h6>
                            </div>
                            <div class="d-flex align-items-center gap-1">
                                <button type="button"
                                    class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1 py-0 px-2"
                                    style="font-size: .75rem;" @click="showRaw = !showRaw">
                                    <i :class="showRaw ? 'demo-psi-eye-close' : 'demo-psi-eye'"></i>
                                    <span x-text="showRaw ? 'Hide' : 'Show'"></span>
                                </button>
                                <button type="button"
                                    class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1 py-0 px-2"
                                    style="font-size: .75rem;" @click="copyPayload()">
                                    <i :class="copied ? 'demo-psi-check' : 'demo-psi-file-copy'"></i>
                                    <span x-text="copied ? 'Copied!' : 'Copy'"></span>
                                </button>
                            </div>
                        </div>
                        <pre x-show="showRaw" class="bg-white text-dark rounded border mb-0 px-3 py-2"
                            style="white-space: pre; font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace; font-size: .78rem; line-height: 1.5; overflow: auto; max-height: 400px;"
                            x-text="job.pretty"></pre>
                    </div>
                </template>

                {{-- FOOTER --}}
                <template x-if="job">
                    <div class="modal-footer bg-white border-top py-1 px-3">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
                            <i class="demo-psi-close me-1"></i>Close
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </div>

    @script
        <script>
            Alpine.data('jobPayloadViewer', () => ({
                job: null,
                copied: false,
                showRaw: false,
                modal: null,

                init() {
                    const modalElement = document.getElementById('jobPayloadModal');
                    if (modalElement) {
                        this.modal = bootstrap.Modal.getOrCreateInstance(modalElement);
                        modalElement.addEventListener('hidden.bs.modal', () => {
                            this.job = null;
                            this.copied = false;
                            this.showRaw = false;
                        });
                    }

                    Livewire.on('job-payload-loaded', (payload) => {
                        const details = Array.isArray(payload) ? payload[0]?.details : payload?.details;
                        this.open(details);
                    });
                },

                open(details) {
                    if (!details) {
                        return;
                    }
                    this.job = details;
                    this.copied = false;
                    this.showRaw = false;
                    if (this.modal) {
                        this.modal.show();
                    }
                },

                async copyPayload() {
                    if (!this.job || !this.job.pretty) {
                        return;
                    }

                    try {
                        await navigator.clipboard.writeText(this.job.pretty);
                        this.copied = true;
                        setTimeout(() => {
                            this.copied = false;
                        }, 1800);
                    } catch (error) {
                        console.error('Failed to copy payload', error);
                    }
                },
            }));
        </script>
    @endscript
</div>
