{{--
    Day Session Manager — "Journey" flow (Float → Sales in progress → Count → Close)
    built from Bootstrap 5.3 / Nifty theme classes only — no custom stylesheet.
    Every top-level block is a .card because the page header band overlaps the content.
    Layout reference: docs/day-management-premium-v2-preview.html (direction C).
--}}
<div>
    @if (session()->has('success'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="fa fa-check-circle"></i><span>{{ session('success') }}</span></div>
    @endif
    @if (session()->has('error'))
        <div class="alert alert-danger d-flex align-items-center gap-2"><i class="fa fa-exclamation-circle"></i><span>{{ session('error') }}</span></div>
    @endif

    @if ($currentSession)
        @php
            $expected = (float) ($sessionStats['expected_amount'] ?? 0);
            $counted = (float) $closing_amount;
            $variance = $counted - $expected;
            $openAmt = (float) ($sessionStats['opening_amount'] ?? 0);
            $tailoringAmt = (float) ($sessionStats['total_tailoring_amount'] ?? 0);
            $collected = (float) ($sessionStats['total_amount'] ?? 0);
            $salesAmt = max(0, $collected - $tailoringAmt);
            $pct = fn ($v) => $expected > 0 ? round(max(0, min(100, ($v / $expected) * 100))) : 0;
            $tone = abs($variance) < 0.005 ? 'success' : ($variance < 0 ? 'danger' : 'warning');
            $toneText = ['success' => 'Balanced — drawer matches expected', 'danger' => 'Short — counted less than expected', 'warning' => 'Over — counted more than expected'][$tone];
            $toneIcon = ['success' => 'fa-check-circle', 'danger' => 'fa-arrow-circle-down', 'warning' => 'fa-arrow-circle-up'][$tone];
            $toneSign = $variance > 0.004 ? '+' : ($variance < -0.004 ? '−' : '');
            $openedAt = \Carbon\Carbon::parse($sessionStats['opened_at']);
            $invoices = (int) ($sessionStats['total_sales'] ?? 0);
            $orders = (int) ($sessionStats['total_tailoring_orders'] ?? 0);
        @endphp

        <!-- TITLE + STEPPER -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                    <div>
                        <h3 class="mb-1 d-flex align-items-center flex-wrap gap-2">
                            {{ $currentSession->branch->name }}
                            <span class="badge rounded-pill text-bg-success fs-6 fw-normal"><i class="fa fa-bolt me-1"></i>Live</span>
                        </h3>
                        <div class="text-body-secondary">
                            Session #{{ $currentSession->id }} · opened {{ $openedAt->format('h:i A') }} by {{ $sessionStats['opened_by'] }} ·
                            <span title="{{ systemDateTime($sessionStats['opened_at']) }}">{{ $openedAt->diffForHumans() }}</span>
                        </div>
                    </div>
                    <div>
                        <label for="dsj-date" class="form-label small text-uppercase fw-semibold text-primary mb-1"><i class="fa fa-calendar me-1"></i> Business date</label>
                        <div class="input-group">
                            <span class="input-group-text text-bg-primary border-primary"><i class="fa fa-calendar"></i></span>
                            <input id="dsj-date" type="date" class="form-control border-primary bg-primary-subtle text-primary-emphasis fw-bold" wire:model="date">
                        </div>
                    </div>
                </div>

                <div class="row g-2 mt-1 pt-3 border-top">
                    <div class="col-6 col-lg-3">
                        <div class="d-flex align-items-center gap-2 p-2 rounded-3 border h-100">
                            <span class="badge rounded-pill text-bg-success"><i class="fa fa-check"></i></span>
                            <div class="lh-sm"><div class="fw-semibold">Float counted</div><small class="text-body-secondary">{{ currency($openAmt) }} · {{ $openedAt->format('h:i A') }}</small></div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="d-flex align-items-center gap-2 p-2 rounded-3 border h-100">
                            <span class="badge rounded-pill text-bg-success"><i class="fa fa-bolt"></i></span>
                            <div class="lh-sm"><div class="fw-semibold">Sales in progress</div><small class="text-body-secondary">{{ $invoices }} {{ Str::plural('invoice', $invoices) }} · {{ currency($collected) }} collected</small></div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="d-flex align-items-center gap-2 p-2 rounded-3 border border-primary bg-primary-subtle h-100">
                            <span class="badge rounded-pill text-bg-primary">3</span>
                            <div class="lh-sm"><div class="fw-semibold text-primary-emphasis">Count the drawer</div><small class="text-body-secondary">Expected {{ currency($expected) }}</small></div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="d-flex align-items-center gap-2 p-2 rounded-3 border h-100 text-body-secondary">
                            <span class="badge rounded-pill bg-body-tertiary text-body-secondary border">4</span>
                            <div class="lh-sm"><div class="fw-semibold">Close the day</div><small>Locks this branch</small></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3 align-items-start">
            <!-- SALES SUMMARY -->
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header d-flex align-items-center gap-2">
                        <i class="fa fa-line-chart text-primary"></i>
                        <div><h5 class="card-title mb-0">Sales summary</h5><small class="text-body-secondary">Everything that flowed into the drawer today</small></div>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item px-0">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-semibold"><i class="fa fa-university text-body-secondary me-2"></i>Opening float</span>
                                    <span class="fw-bold">{{ currency($openAmt) }}</span>
                                </div>
                                <div class="progress" role="progressbar" aria-valuenow="{{ $pct($openAmt) }}" aria-valuemin="0" aria-valuemax="100" style="height: 6px"><div class="progress-bar bg-secondary" style="width: {{ $pct($openAmt) }}%"></div></div>
                            </li>
                            <li class="list-group-item px-0">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-semibold"><i class="fa fa-shopping-cart text-body-secondary me-2"></i>Sales <small class="text-body-secondary fw-normal">· {{ $invoices }} {{ Str::plural('invoice', $invoices) }}</small></span>
                                    <span class="fw-bold">{{ currency($salesAmt) }}</span>
                                </div>
                                <div class="progress" role="progressbar" aria-valuenow="{{ $pct($salesAmt) }}" aria-valuemin="0" aria-valuemax="100" style="height: 6px"><div class="progress-bar" style="width: {{ $pct($salesAmt) }}%"></div></div>
                            </li>
                            <li class="list-group-item px-0">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-semibold"><i class="fa fa-scissors text-body-secondary me-2"></i>Tailoring <small class="text-body-secondary fw-normal">· {{ $orders }} {{ Str::plural('order', $orders) }}</small></span>
                                    <span class="fw-bold">{{ currency($tailoringAmt) }}</span>
                                </div>
                                <div class="progress" role="progressbar" aria-valuenow="{{ $pct($tailoringAmt) }}" aria-valuemin="0" aria-valuemax="100" style="height: 6px"><div class="progress-bar bg-warning" style="width: {{ $pct($tailoringAmt) }}%"></div></div>
                            </li>
                        </ul>
                        <div class="rounded-3 bg-primary-subtle p-3 mt-3 d-flex justify-content-between align-items-center gap-3">
                            <div><div class="fw-bold text-primary-emphasis">Expected in drawer</div><small class="text-body-secondary">opening + everything collected</small></div>
                            <div class="fs-3 fw-bold">{{ currency($expected) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- COUNT & CLOSE -->
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header d-flex align-items-center gap-2">
                        <i class="fa fa-money text-primary"></i>
                        <div><h5 class="card-title mb-0">Count &amp; close</h5><small class="text-body-secondary">Step 3 of 4 · enter what's physically in the drawer</small></div>
                    </div>
                    <div class="card-body">
                        <form wire:submit.prevent="closeDay">
                            <label for="dsj-counted" class="form-label d-block text-center text-uppercase small fw-semibold text-body-secondary">Counted amount <span class="text-danger">*</span></label>
                            <div class="input-group input-group-lg mb-2">
                                <button type="button" class="btn btn-outline-secondary" title="−10" wire:click="$set('closing_amount', {{ max(0, round($counted - 10, 2)) }})"><i class="fa fa-minus"></i></button>
                                <input id="dsj-counted" type="number" step="0.01" min="0" class="form-control text-center fw-bold fs-3" wire:model.live="closing_amount" placeholder="0.00">
                                <button type="button" class="btn btn-outline-secondary" title="+10" wire:click="$set('closing_amount', {{ round($counted + 10, 2) }})"><i class="fa fa-plus"></i></button>
                            </div>
                            <div class="text-center small text-body-secondary mb-3">Expected <b>{{ currency($expected) }}</b> · type the recount, the difference shows below</div>
                            @error('closing_amount') <div class="text-danger small text-center mb-2">{{ $message }}</div> @enderror

                            <div class="alert alert-{{ $tone }} d-flex align-items-center gap-3">
                                <i class="fa {{ $toneIcon }} fa-2x"></i>
                                <div><div class="fw-semibold">{{ $toneText }}</div><small>Variance over / short</small></div>
                                <div class="ms-auto fs-4 fw-bold">{{ $toneSign }}{{ currency(abs($variance)) }}</div>
                            </div>

                            @if ($currentSession?->branch?->moq_sync)
                                @can('day close.sync amount')
                                    <div class="mb-3">
                                        <label for="dsj-sync" class="form-label d-flex align-items-center">Sync amount <span class="text-danger ms-1">*</span> <span class="badge text-bg-primary ms-auto">MOQ sync</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fa fa-refresh"></i></span>
                                            <input id="dsj-sync" type="number" step="0.01" min="0" class="form-control" wire:model="sync_amount" placeholder="0.00">
                                        </div>
                                        @error('sync_amount') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                    </div>
                                @endcan
                            @endif

                            <div class="mb-3">
                                <label for="dsj-notes" class="form-label">Notes <span class="text-body-secondary">(optional)</span></label>
                                <textarea id="dsj-notes" class="form-control" rows="2" wire:model="notes" placeholder="Anything worth noting about today's session…"></textarea>
                                @error('notes') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>

                            <div class="form-text mb-3"><i class="fa fa-info-circle text-warning me-1"></i> Closing locks sales &amp; tailoring for this branch and can't be undone. Recount the drawer first.</div>

                            <button type="button" class="btn btn-danger btn-lg w-100" onclick="confirmCloseSession()"><i class="fa fa-lock me-1"></i> Close the day</button>
                            <div class="d-flex gap-2 mt-2">
                                <a href="{{ route('sale::create') }}" class="btn btn-outline-primary flex-fill"><i class="fa fa-plus me-1"></i> New sale</a>
                                <a href="{{ route('sale::day-session', $currentSession->id) }}" class="btn btn-outline-secondary flex-fill"><i class="fa fa-eye me-1"></i> Details</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @else
        @php
            $branchName = $branches->firstWhere('id', $branch_id)?->name ?? 'Day Session';
        @endphp

        <!-- TITLE + STEPPER (not started) -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                    <div>
                        <h3 class="mb-1 d-flex align-items-center flex-wrap gap-2">
                            {{ $branchName }}
                            <span class="badge rounded-pill text-bg-warning fs-6 fw-normal"><i class="fa fa-moon-o me-1"></i>Not started</span>
                        </h3>
                        <div class="text-body-secondary">No open session for this branch on the selected date</div>
                    </div>
                    <div>
                        <label for="dsj-date" class="form-label small text-uppercase fw-semibold text-primary mb-1"><i class="fa fa-calendar me-1"></i> Business date</label>
                        <div class="input-group">
                            <span class="input-group-text text-bg-primary border-primary"><i class="fa fa-calendar"></i></span>
                            <input id="dsj-date" type="date" class="form-control border-primary bg-primary-subtle text-primary-emphasis fw-bold" wire:model="date">
                        </div>
                    </div>
                </div>

                <div class="row g-2 mt-1 pt-3 border-top">
                    <div class="col-6 col-lg-3">
                        <div class="d-flex align-items-center gap-2 p-2 rounded-3 border border-primary bg-primary-subtle h-100">
                            <span class="badge rounded-pill text-bg-primary">1</span>
                            <div class="lh-sm"><div class="fw-semibold text-primary-emphasis">Count the float</div><small class="text-body-secondary">Start the day</small></div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="d-flex align-items-center gap-2 p-2 rounded-3 border h-100 text-body-secondary">
                            <span class="badge rounded-pill bg-body-tertiary text-body-secondary border">2</span>
                            <div class="lh-sm"><div class="fw-semibold">Sales in progress</div><small>Sales &amp; tailoring</small></div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="d-flex align-items-center gap-2 p-2 rounded-3 border h-100 text-body-secondary">
                            <span class="badge rounded-pill bg-body-tertiary text-body-secondary border">3</span>
                            <div class="lh-sm"><div class="fw-semibold">Count the drawer</div><small>End of day recount</small></div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="d-flex align-items-center gap-2 p-2 rounded-3 border h-100 text-body-secondary">
                            <span class="badge rounded-pill bg-body-tertiary text-body-secondary border">4</span>
                            <div class="lh-sm"><div class="fw-semibold">Close the day</div><small>Locks this branch</small></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- OPEN SESSION -->
        <div class="row justify-content-center mb-3">
            <div class="col-md-8 col-lg-6 col-xl-5">
                <div class="card text-center">
                    <div class="card-body p-4">
                        <div class="display-5 text-primary mb-2"><i class="fa fa-unlock-alt"></i></div>
                        <h4 class="card-title">Start today's session</h4>
                        <p class="text-body-secondary">Count the cash in the drawer and enter the opening float. That unlocks sales &amp; tailoring for this branch.</p>
                        <form wire:submit.prevent="openDay" class="text-start">
                            <label for="dsj-opening" class="form-label fw-semibold">Opening cash amount <span class="text-danger">*</span></label>
                            <div class="input-group input-group-lg mb-1">
                                <span class="input-group-text"><i class="fa fa-money"></i></span>
                                <input id="dsj-opening" type="number" step="0.01" min="0" class="form-control text-center fw-bold" wire:model="opening_amount" placeholder="0.00">
                            </div>
                            @error('opening_amount') <div class="text-danger small mb-2">{{ $message }}</div> @enderror
                            <button type="submit" class="btn btn-success btn-lg w-100 mt-2"><i class="fa fa-unlock me-1"></i> Start session</button>
                        </form>
                        <div class="small text-body-secondary mt-3 d-flex flex-wrap justify-content-center gap-3">
                            <span><i class="fa fa-check text-success me-1"></i>Count notes &amp; coins</span>
                            <span><i class="fa fa-check text-success me-1"></i>Remove yesterday's takings</span>
                            <span><i class="fa fa-check text-success me-1"></i>Then start</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- ALL OPEN SESSIONS -->
    @if (count($openSessions) > 0)
        <div class="d-flex align-items-center justify-content-between mb-2">
            <h5 class="mb-0"><i class="fa fa-th-large text-primary me-2"></i>Open sessions</h5>
            <span class="badge rounded-pill text-bg-success"><i class="fa fa-bolt me-1"></i>{{ count($openSessions) }} {{ Str::plural('branch', count($openSessions)) }} live</span>
        </div>
        <div class="row g-3">
            @foreach ($openSessions as $session)
                <div class="col-md-6 col-xl-4" wire:key="open-session-{{ $session->id }}">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <span class="badge bg-primary-subtle text-primary-emphasis rounded-3 fs-5 p-2"><i class="fa fa-building"></i></span>
                                <div class="flex-grow-1 overflow-hidden">
                                    <div class="fw-bold text-truncate">{{ $session->branch->name }}</div>
                                    <small class="text-body-secondary">Session #{{ $session->id }} · {{ SystemDate($session->opened_at) }}</small>
                                </div>
                                <span class="badge rounded-pill text-bg-success"><i class="fa fa-bolt me-1"></i>Live</span>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-6"><div class="bg-body-tertiary rounded-3 p-2 small text-body-secondary">Opened<div class="fw-bold text-body">{{ $session->opened_at->format('h:i A') }}</div></div></div>
                                <div class="col-6"><div class="bg-body-tertiary rounded-3 p-2 small text-body-secondary">Float<div class="fw-bold text-body">{{ currency($session->opening_amount) }}</div></div></div>
                                <div class="col-12"><div class="bg-body-tertiary rounded-3 p-2 small text-body-secondary">Opened by<div class="fw-bold text-body text-truncate">{{ $session->opener->name ?? 'Unknown' }}</div></div></div>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-secondary btn-sm flex-fill" wire:click="changeBranch({{ $session->branch_id }})"><i class="fa fa-cog me-1"></i> Manage</button>
                                <a href="{{ route('sale::day-session', $session->id) }}" class="btn btn-primary btn-sm flex-fill"><i class="fa fa-eye me-1"></i> Details</a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@push('scripts')
    <script>
        function confirmCloseSession() {
            Swal.fire({
                title: 'Are you sure?',
                html: "Are you sure you want to close the session? This action cannot be undone. @if($currentSession?->branch?->moq_sync) <br><i> The API sync amount is " + @this.get('sync_amount') + "</i> @endif ",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fa fa-lock me-2"></i>Yes, Close Session',
                cancelButtonText: '<i class="fa fa-times me-2"></i>Cancel',
                reverseButtons: true,
                focusCancel: true,
                customClass: {
                    confirmButton: 'btn btn-danger',
                    cancelButton: 'btn btn-secondary'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    @this.call('closeDay');
                }
            });
        }
    </script>
@endpush
