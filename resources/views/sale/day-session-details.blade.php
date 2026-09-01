<x-app-layout>
    @php
        $isClosed = $stats['is_closed'];
        $invoices = $stats['sales_count'] + $stats['tailoring_count'];
        $grossTotal = $stats['sales_total'] + $stats['tailoring_total'];
        $variance = $stats['variance'];
        $vClass = ! $isClosed ? 'pending' : (abs($variance) < 0.01 ? 'ok' : ($variance < 0 ? 'short' : 'over'));
        $vTag = match ($vClass) {
            'ok' => 'Balanced — drawer matched expected',
            'short' => 'Short — counted less than expected',
            'over' => 'Over — counted more than expected',
            default => 'Session still open — reconciled at close',
        };
        $vSign = $variance === null ? '' : ($variance > 0 ? '+' : '');
        $openPct = $stats['expected'] > 0 ? max(0, min(100, ($stats['opening'] / $stats['expected']) * 100)) : 0;
        $salesPct = $stats['expected'] > 0 ? 100 - $openPct : 0;
        $initial = fn ($name) => mb_strtoupper(mb_substr(trim((string) $name), 0, 1) ?: '?');
        $openerName = $session->opener->name ?? 'Unknown';
        $closerName = $session->closer->name ?? 'Unknown';
    @endphp

    <div class="content__header content__boxed overlapping">
        <div class="content__wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('sale::day-sessions-report') }}">Day Sessions</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Session #{{ $session->id }}</li>
                </ol>
            </nav>
            <h1 class="page-title mb-0 mt-2">Day Session #{{ $session->id }}</h1>
            <p class="lead">
                {{ $session->branch->name }} · {{ $isClosed ? 'closed' : 'open' }} session of {{ systemDate($session->opened_at) }}
            </p>
        </div>
    </div>

    <div class="content__boxed">
        <div class="content__wrap">
            <x-sale.day-session-premium />

            <div class="dsv">
                {{-- ============ HERO ============ --}}
                <section class="hero">
                    <div class="hero__top">
                        <div class="hero__id">
                            <div class="hero__glyph {{ $isClosed ? '' : 'is-open' }}">
                                <i class="fa {{ $isClosed ? 'fa-lock' : 'fa-bolt' }}"></i>
                            </div>
                            <div>
                                <div class="hero__row">
                                    <h4 class="hero__title">{{ $session->branch->name }}</h4>
                                    @if ($isClosed)
                                        <span class="pill"><i class="fa fa-check"></i> Closed · Session #{{ $session->id }}</span>
                                    @else
                                        <span class="pill pill--green"><span class="pulse"></span> Live · Session #{{ $session->id }}</span>
                                    @endif
                                </div>
                                <div class="hero__sub">
                                    <span title="{{ systemDateTime($session->opened_at) }}"><i class="fa fa-play-circle"></i> Opened <b>{{ $session->opened_at->format('d M, h:i A') }}</b> by {{ $openerName }}</span>
                                    @if ($isClosed && $session->closed_at)
                                        <span title="{{ systemDateTime($session->closed_at) }}"><i class="fa fa-lock"></i> Closed <b>{{ $session->closed_at->format('d M, h:i A') }}</b> by {{ $closerName }}</span>
                                    @endif
                                    <span><i class="fa fa-clock-o"></i> {{ $isClosed ? 'Duration' : 'Running for' }} <b>{{ $stats['duration'] }}</b></span>
                                </div>
                            </div>
                        </div>
                        <div class="hero__actions">
                            <a href="{{ route('sale::day-sessions-report') }}" class="hbtn" title="Back to day sessions"><i class="fa fa-arrow-left"></i> Back</a>
                            @if (! $isClosed)
                                @can('day session.create')
                                    <a href="{{ route('sale::day-management') }}" class="hbtn" title="Open day management"><i class="fa fa-sliders"></i> Manage day</a>
                                @endcan
                            @endif
                            @can('day session.print')
                                <a href="{{ route('print::sale::day-session-report', $session->id) }}" class="hbtn hbtn--green" target="_blank" title="Print thermal receipt"><i class="fa fa-print"></i> Print</a>
                                <a href="{{ route('print::sale::day-session-report-pdf', $session->id) }}" class="hbtn hbtn--accent" target="_blank" title="Open PDF report"><i class="fa fa-file-pdf-o"></i> PDF</a>
                            @endcan
                        </div>
                    </div>

                    <div class="kpis">
                        <div class="kpi">
                            <span class="kpi__rail r-accent"></span>
                            <div class="kpi__ic i-accent"><i class="fa fa-shopping-cart"></i></div>
                            <div class="kpi__lbl">Total sales</div>
                            <div class="kpi__val">{{ currency($grossTotal) }}</div>
                            <div class="kpi__foot">
                                <i class="fa fa-file-text-o"></i> {{ $invoices }} {{ Str::plural('invoice', $invoices) }}
                                @if ($stats['tailoring_count'] > 0)
                                    · incl. {{ $stats['tailoring_count'] }} tailoring
                                @endif
                            </div>
                        </div>
                        <div class="kpi">
                            <span class="kpi__rail r-green"></span>
                            <div class="kpi__ic i-green"><i class="fa fa-money"></i></div>
                            <div class="kpi__lbl">Collected</div>
                            <div class="kpi__val">{{ currency($stats['collected']) }}</div>
                            <div class="kpi__foot">
                                @if ($stats['tailoring_paid'] > 0)
                                    <i class="fa fa-scissors"></i> Sales {{ currency($stats['sales_paid']) }} · Tailoring {{ currency($stats['tailoring_paid']) }}
                                @else
                                    <i class="fa fa-arrow-up"></i> Cash + card received
                                @endif
                            </div>
                        </div>
                        <div class="kpi">
                            <span class="kpi__rail r-deep"></span>
                            <div class="kpi__ic i-deep"><i class="fa fa-calculator"></i></div>
                            <div class="kpi__lbl">Expected in drawer</div>
                            <div class="kpi__val">{{ currency($stats['expected']) }}</div>
                            <div class="kpi__foot"><i class="fa fa-info-circle"></i> Opening {{ currency($stats['opening']) }} + collected</div>
                        </div>
                        @if ($isClosed)
                            <div class="kpi">
                                <span class="kpi__rail {{ $vClass === 'ok' ? 'r-green' : ($vClass === 'short' ? 'r-red' : 'r-amber') }}"></span>
                                <div class="kpi__ic {{ $vClass === 'ok' ? 'i-green' : ($vClass === 'short' ? 'i-red' : 'i-amber') }}"><i class="fa fa-check-square-o"></i></div>
                                <div class="kpi__lbl">Counted (closing)</div>
                                <div class="kpi__val">{{ currency($stats['counted']) }}</div>
                                <div class="kpi__foot">
                                    @if ($vClass === 'ok')
                                        <b class="t-green"><i class="fa fa-check"></i> Balanced</b>
                                    @elseif ($vClass === 'short')
                                        <b class="t-red"><i class="fa fa-arrow-down"></i> {{ currency($variance) }} short</b>
                                    @else
                                        <b class="t-amber"><i class="fa fa-arrow-up"></i> +{{ currency($variance) }} over</b>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="kpi">
                                <span class="kpi__rail r-amber"></span>
                                <div class="kpi__ic i-amber"><i class="fa fa-university"></i></div>
                                <div class="kpi__lbl">Opening balance</div>
                                <div class="kpi__val">{{ currency($stats['opening']) }}</div>
                                <div class="kpi__foot"><i class="fa fa-lock"></i> Counted at open</div>
                            </div>
                        @endif
                    </div>
                </section>

                {{-- ============ RECONCILIATION + LOG ============ --}}
                <div class="grid2">
                    <div class="panel">
                        <div class="panel__head">
                            <h3><i class="fa fa-tasks"></i> Cash reconciliation</h3>
                            @if ($isClosed)
                                <span class="pill pill--accent">Final</span>
                            @else
                                <span class="pill pill--green"><span class="pulse"></span> Live</span>
                            @endif
                        </div>
                        <div class="panel__body">
                            <div class="recon">
                                <div class="recon__row"><span class="k">Opening balance</span><span class="v">{{ currency($stats['opening']) }}</span></div>
                                <div class="recon__row"><span class="k">+ Sales collected</span><span class="v t-green">{{ currency($stats['sales_paid']) }}</span></div>
                                @if ($stats['tailoring_paid'] > 0)
                                    <div class="recon__row"><span class="k">+ Tailoring collected</span><span class="v t-green">{{ currency($stats['tailoring_paid']) }}</span></div>
                                @endif
                                <div class="recon__bar">
                                    <span class="b-open" style="width: {{ $openPct }}%"></span>
                                    <span class="b-sales" style="width: {{ $salesPct }}%"></span>
                                </div>
                                <div class="recon__legend">
                                    <span><span class="sw" style="background: var(--acc)"></span> Opening {{ round($openPct) }}%</span>
                                    <span><span class="sw" style="background: var(--green)"></span> Collected {{ round($salesPct) }}%</span>
                                </div>
                                <div class="recon__row big"><span class="k">Expected in drawer</span><span class="v">{{ currency($stats['expected']) }}</span></div>
                                <div class="recon__row">
                                    <span class="k">Counted (closing)</span>
                                    @if ($isClosed)
                                        <span class="v t-acc">{{ currency($stats['counted']) }}</span>
                                    @else
                                        <span class="v t-muted" style="font-weight: 500; font-size: 12.5px;">Pending close</span>
                                    @endif
                                </div>
                                @if ($isClosed && (float) $session->sync_amount != 0)
                                    <div class="recon__row"><span class="k">Sync amount</span><span class="v">{{ currency($session->sync_amount) }}</span></div>
                                @endif
                                <div class="variance {{ $vClass }}">
                                    <div>
                                        <div class="lab">Variance (over / short)</div>
                                        <div class="tag">{{ $vTag }}</div>
                                    </div>
                                    <div class="amt">{{ $isClosed ? $vSign.currency($variance) : '—' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="panel">
                        <div class="panel__head">
                            <h3><i class="fa fa-history"></i> Session log</h3>
                            <span class="pill"><i class="fa fa-building-o"></i> {{ $session->branch->name }}</span>
                        </div>
                        <div class="panel__body">
                            <div class="tl">
                                <div class="tl__item">
                                    <span class="tl__dot green"><i class="fa fa-play"></i></span>
                                    <div class="tl__head">
                                        <span class="tl__title">Session opened</span>
                                        <span class="tl__time">{{ systemDateTime($session->opened_at) }}</span>
                                    </div>
                                    <div class="tl__meta"><span class="av">{{ $initial($openerName) }}</span> {{ $openerName }}</div>
                                    <div class="tl__chips">
                                        <span class="chip chip--acc"><b>Float</b> {{ currency($stats['opening']) }}</span>
                                    </div>
                                </div>

                                @if ($isClosed)
                                    <div class="tl__item">
                                        <span class="tl__dot red"><i class="fa fa-stop"></i></span>
                                        <div class="tl__head">
                                            <span class="tl__title">Session closed</span>
                                            <span class="tl__time">{{ systemDateTime($session->closed_at) }}</span>
                                        </div>
                                        <div class="tl__meta"><span class="av">{{ $initial($closerName) }}</span> {{ $closerName }} · after {{ $stats['duration'] }}</div>
                                        <div class="tl__chips">
                                            <span class="chip"><b>Counted</b> {{ currency($stats['counted']) }}</span>
                                            <span class="chip"><b>Expected</b> {{ currency($stats['expected']) }}</span>
                                            <span class="chip {{ $vClass === 'ok' ? 'chip--green' : ($vClass === 'short' ? 'chip--red' : 'chip--amber') }}"><b>Δ</b> {{ $vSign }}{{ currency($variance) }}</span>
                                            @if ((float) $session->sync_amount != 0)
                                                <span class="chip"><b>Sync</b> {{ currency($session->sync_amount) }}</span>
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <div class="tl__item">
                                        <span class="tl__dot ghost"><i class="fa fa-clock-o"></i></span>
                                        <div class="tl__head">
                                            <span class="tl__title t-muted">In progress</span>
                                            <span class="tl__time">running {{ $stats['duration'] }}</span>
                                        </div>
                                        <div class="tl__meta">Drawer is reconciled when the session is closed from Day Management.</div>
                                        <div class="tl__chips">
                                            <span class="chip"><b>Expected so far</b> {{ currency($stats['expected']) }}</span>
                                        </div>
                                    </div>
                                @endif

                                @if ($session->notes)
                                    <div class="tl__item">
                                        <span class="tl__dot acc"><i class="fa fa-comment"></i></span>
                                        <div class="tl__head"><span class="tl__title">Closing note</span></div>
                                        <div class="tl__note">{{ $session->notes }}</div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ============ COLLECTIONS + TRANSACTIONS ============ --}}
                <div style="margin-top: 12px;">
                    @livewire('sale-day-session.day-session-sales-list', ['sessionId' => $session->id])
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
