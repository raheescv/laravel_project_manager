<div class="apx">
    <x-property-appointment.premium />

    <div class="apx-hero mb-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <span class="apx-pill"><i class="fa fa-clipboard"></i> Log</span>
                <h1 class="apx-hero-title text-white mt-2">Email log</h1>
                <div class="apx-hero-meta mt-1">
                    Every email the system has sent, with the exact message the recipient received
                </div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button type="button" class="apx-btn apx-btn-glass" wire:click="$refresh">
                    <i class="fa fa-refresh"></i> Refresh
                </button>
            </div>
        </div>
    </div>

    <style>
        .apx .kpi-click{ cursor:pointer; transition:transform .14s ease, box-shadow .14s ease, border-color .14s ease; }
        .apx .kpi-click:hover{ transform:translateY(-2px); box-shadow:var(--shadow-md); border-color:rgba(var(--brand-rgb),.35); }
        .apx .kpi-click.on{ border-color:var(--brand); box-shadow:0 0 0 3px rgba(var(--brand-rgb),.12); }
        .apx .apx-kpi .k .lb i{ opacity:.6; margin-inline-end:3px; }
        .apx .mail-row-sub{ font-size:10.5px; color:var(--text-3); }
    </style>

    <div class="apx-kpi mb-3">
        <div class="k accent kpi-click {{ $status === '' ? 'on' : '' }}" wire:click="$set('status', '')" role="button">
            <div class="lb"><i class="fa fa-envelope-o"></i> All emails</div>
            <div class="vl">{{ number_format($stats['total']) }}</div>
            <div class="dl">in this period</div>
        </div>
        <div class="k kpi-click {{ $status === 'sent' ? 'on' : '' }}" wire:click="$set('status', 'sent')" role="button">
            <div class="lb"><i class="fa fa-check"></i> Sent</div>
            <div class="vl" style="color:var(--success)">{{ number_format($stats['sent']) }}</div>
            <div class="dl">handed to the mail server</div>
        </div>
        <div class="k kpi-click {{ $status === 'queued' ? 'on' : '' }}" wire:click="$set('status', 'queued')" role="button">
            <div class="lb"><i class="fa fa-clock-o"></i> Queued</div>
            <div class="vl" style="color:var(--warning)">{{ number_format($stats['queued']) }}</div>
            <div class="dl">waiting on a queue worker</div>
        </div>
        <div class="k kpi-click {{ $status === 'failed' ? 'on' : '' }}" wire:click="$set('status', 'failed')" role="button">
            <div class="lb"><i class="fa fa-times"></i> Failed</div>
            <div class="vl" style="color:{{ $stats['failed'] ? 'var(--danger)' : 'inherit' }}">{{ number_format($stats['failed']) }}</div>
            <div class="dl">did not go out</div>
        </div>
    </div>

    @if ($stats['queued'] > 0)
        <div class="apx-alert alert-info mb-3">
            <i class="fa fa-info-circle lead"></i>
            <div>
                <div class="t">{{ $stats['queued'] }} email(s) still queued</div>
                <div class="s">
                    Queued mail only leaves once a queue worker is running
                    (<code class="apx-codev">php artisan queue:work</code>). If these never clear, the worker is not running.
                </div>
            </div>
        </div>
    @endif

    <div class="apx-panel">
        <div class="apx-panel-h flex-wrap gap-2">
            <span class="apx-ico"><i class="fa fa-envelope-o"></i></span>
            <input type="text" class="form-control form-control-sm" style="max-width:230px"
                placeholder="Search recipient or subject…" wire:model.live.debounce.400ms="search">

            <select class="form-select form-select-sm" style="max-width:140px" wire:model.live="status">
                <option value="">All statuses</option>
                <option value="sent">Sent</option>
                <option value="queued">Queued</option>
                <option value="failed">Failed</option>
            </select>

            <select class="form-select form-select-sm" style="max-width:170px" wire:model.live="module">
                <option value="">All modules</option>
                @foreach ($modules as $key => $meta)
                    <option value="{{ $key }}">{{ $meta['label'] ?? $key }}</option>
                @endforeach
                <option value="general">Other</option>
            </select>

            <input type="date" class="form-control form-control-sm" style="max-width:150px" wire:model.live="from_date">
            <input type="date" class="form-control form-control-sm" style="max-width:150px" wire:model.live="to_date">
        </div>

        <div class="table-responsive">
            <table class="apx-tbl">
                <thead>
                    <tr>
                        <th>Sent</th>
                        <th>Recipient</th>
                        <th>Subject</th>
                        <th>Module / Event</th>
                        <th>Status</th>
                        <th style="width:90px"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr wire:key="log-{{ $log->id }}">
                            <td>
                                <div class="strong">{{ ($log->sent_at ?? $log->created_at)->format('d M Y') }}</div>
                                <div class="dim">{{ appointmentTime($log->sent_at ?? $log->created_at) }}</div>
                            </td>
                            <td><div class="strong">{{ $log->to_email }}</div></td>
                            <td>
                                <div class="strong">{{ $log->subject ?: '—' }}</div>
                                @if ($log->error)
                                    <div class="dim" style="color:var(--danger)">{{ \Illuminate\Support\Str::limit($log->error, 70) }}</div>
                                @endif
                            </td>
                            <td>
                                <div class="strong">{{ $log->moduleLabel() }}</div>
                                <div class="dim">{{ $log->typeLabel() }}</div>
                            </td>
                            <td>
                                <span class="apx-chip {{ ['sent' => 'chip-completed', 'queued' => 'chip-awaiting', 'failed' => 'chip-no_show'][$log->status] ?? 'chip-cancelled' }}">
                                    <span class="dot"></span> {{ $log->statusLabel() }}
                                </span>
                            </td>
                            <td>
                                <button type="button" class="apx-btn apx-btn-ghost" style="padding:4px 9px"
                                    wire:click="preview({{ $log->id }})">
                                    <i class="fa fa-eye"></i> View
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="apx-empty">
                                    <div class="art"><i class="fa fa-envelope-o"></i></div>
                                    <h3>No emails in this period</h3>
                                    <p>Nothing matches these filters. Every email the system sends is recorded here automatically.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-2">{{ $logs->links() }}</div>
    </div>

    {{-- ── preview: the message exactly as it was sent ───────────── --}}
    @if ($this->preview)
        @php $log = $this->preview; @endphp
        <div class="modal fade show d-block" tabindex="-1" style="background:rgba(9,13,22,.55)" wire:key="preview-{{ $log->id }}">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content" style="border:0;border-radius:16px;overflow:hidden">
                    <div class="modal-header" style="background:var(--surface-2);border-bottom:1px solid var(--border)">
                        <div>
                            <h5 class="modal-title" style="font-size:14px;font-weight:800;letter-spacing:-.02em">
                                {{ $log->subject ?: 'No subject' }}
                            </h5>
                            <div style="font-size:11px;color:var(--text-3)">
                                {{ $log->moduleLabel() }} &middot; {{ $log->typeLabel() }}
                            </div>
                        </div>
                        <button type="button" class="btn-close" wire:click="closePreview"></button>
                    </div>

                    <div class="modal-body" style="background:var(--surface)">
                        <div class="row g-2 mb-3">
                            <div class="col-md-6"><div class="apx-kv"><div class="k">To</div><div class="v">{{ $log->to_email }}</div></div></div>
                            <div class="col-md-6"><div class="apx-kv"><div class="k">Status</div>
                                <div class="v">
                                    <span class="apx-chip {{ ['sent' => 'chip-completed', 'queued' => 'chip-awaiting', 'failed' => 'chip-no_show'][$log->status] ?? 'chip-cancelled' }}">
                                        <span class="dot"></span> {{ $log->statusLabel() }}
                                    </span>
                                </div></div></div>
                            <div class="col-md-6"><div class="apx-kv"><div class="k">Sent at</div>
                                <div class="v">{{ $log->sent_at ? $log->sent_at->format('d M Y').' '.appointmentTime($log->sent_at) : 'Not sent' }}</div></div></div>
                            <div class="col-md-6"><div class="apx-kv"><div class="k">Queued by</div>
                                <div class="v">{{ $log->creator?->name ?? 'System' }}</div></div></div>
                            @if ($log->reply_to)
                                <div class="col-md-6"><div class="apx-kv"><div class="k">Reply-to</div><div class="v">{{ $log->reply_to }}</div></div></div>
                            @endif
                        </div>

                        @if ($log->error)
                            <div class="apx-alert alert-bad mb-3">
                                <i class="fa fa-exclamation-triangle lead"></i>
                                <div><div class="t">Delivery failed</div><div class="s">{{ $log->error }}</div></div>
                            </div>
                        @endif

                        <div class="apx-sect">The message as it was sent</div>
                        @if (filled($log->body))
                            {{-- Rendered in a sandboxed iframe: the body is stored HTML and
                                 must not execute or inherit styles from the admin page. --}}
                            <iframe sandbox srcdoc="{{ $log->body }}"
                                style="width:100%;height:460px;border:1px solid var(--border);border-radius:10px;background:#fff"></iframe>
                        @else
                            <div class="apx-alert alert-warn">
                                <i class="fa fa-info-circle lead"></i>
                                <div>
                                    <div class="t">No body stored for this message</div>
                                    <div class="s">It was logged before the body was captured, or the send never ran.</div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="modal-footer" style="background:var(--surface-2);border-top:1px solid var(--border)">
                        <button type="button" class="apx-btn apx-btn-ghost" wire:click="closePreview">Close</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
