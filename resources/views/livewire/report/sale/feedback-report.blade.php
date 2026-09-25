<div>
    <x-report.studio />

    @once
        <style>
            /* Sale Feedback — the Report Studio (.wrx) in a gold "ratings" accent,
               plus a pulse panel: rating distribution + feedback-type split. */
            .wrx.sfx {
                --wrx-ac: #c07f08;
                --wrx-ac-rgb: 192, 127, 8;
                --wrx-ac-soft: #fdf4e1;
                --sfx-star: #f2b01e;
                --sfx-star-off: #e3e5ea;
                --sfx-good: #0f9d76;
                --sfx-good-soft: #e7f6f1;
            }

            [data-bs-theme="dark"] .wrx.sfx {
                --wrx-ac: #eab53f;
                --wrx-ac-rgb: 234, 181, 63;
                --wrx-ac-soft: rgba(234, 181, 63, .14);
                --sfx-star: #f4bf45;
                --sfx-star-off: rgba(255, 255, 255, .14);
                --sfx-good: #2ec294;
                --sfx-good-soft: rgba(46, 194, 148, .14);
            }

            .wrx.sfx .stat.hero .v .fa { font-size: 13px; margin-inline-start: 3px; color: #fff; opacity: .9; }
            .wrx.sfx .stat.good .v { color: var(--sfx-good); }
            .wrx.sfx .stat.good .stat__ic { background: var(--sfx-good-soft); color: var(--sfx-good); }
            .wrx.sfx .stat.info .v { color: var(--wrx-blue); }
            .wrx.sfx .stat.info .stat__ic { background: var(--wrx-blue-soft); color: var(--wrx-blue); }

            /* ── Pulse panel ─────────────────────────────────────── */
            .wrx.sfx .pulse {
                display: grid; grid-template-columns: 190px minmax(0, 1.35fr) minmax(0, 1fr); gap: 18px;
                padding: 13px 14px; border-bottom: 1px solid var(--wrx-line);
            }
            .wrx.sfx .pulse h5 { margin: 0 0 8px; font-size: 9.5px; font-weight: 800; letter-spacing: .13em; text-transform: uppercase; color: var(--wrx-mut); }

            .wrx.sfx .score {
                display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center;
                border-radius: 11px; padding: 12px 8px;
                background: radial-gradient(120% 90% at 50% 0%, var(--wrx-ac-soft), transparent 70%), var(--wrx-rail);
                border: 1px solid var(--wrx-line);
            }
            .wrx.sfx .score__num { font-size: 34px; font-weight: 800; letter-spacing: -.04em; line-height: 1; color: var(--wrx-ink); font-variant-numeric: tabular-nums; }
            .wrx.sfx .score__num small { font-size: 13px; font-weight: 600; color: var(--wrx-mut); letter-spacing: 0; }
            .wrx.sfx .score__stars { margin: 7px 0 4px; }
            .wrx.sfx .score__cap { font-size: 11px; color: var(--wrx-mut); }

            .wrx.sfx .stars { display: inline-flex; gap: 2px; color: var(--sfx-star-off); white-space: nowrap; }
            .wrx.sfx .stars .on { color: var(--sfx-star); }
            .wrx.sfx .score__stars .fa { font-size: 15px; }
            .wrx.sfx table.wt .stars .fa { font-size: 12px; }

            .wrx.sfx .dist { display: flex; flex-direction: column; gap: 4px; }
            .wrx.sfx .dist__row {
                display: grid; grid-template-columns: 30px minmax(0, 1fr) 70px; align-items: center; gap: 9px;
                border: 1px solid transparent; background: none; border-radius: 7px; padding: 2px 6px; margin: 0 -6px;
                font-size: 11.5px; color: var(--wrx-ink-2); cursor: pointer; text-align: start; transition: background .15s, border-color .15s;
            }
            .wrx.sfx .dist__row:hover { background: var(--wrx-rail); }
            .wrx.sfx .dist__row.is-on { background: var(--wrx-ac-soft); border-color: rgba(var(--wrx-ac-rgb), .35); color: var(--wrx-ac); }
            .wrx.sfx .dist__row.is-dim { opacity: .45; }
            .wrx.sfx .dist__lbl { font-weight: 700; display: inline-flex; align-items: center; gap: 3px; }
            .wrx.sfx .dist__lbl .fa { color: var(--sfx-star); font-size: 10.5px; }
            .wrx.sfx .dist__bar { height: 8px; border-radius: 99px; background: var(--wrx-line-soft); overflow: hidden; }
            .wrx.sfx .dist__fill {
                display: block; height: 100%; border-radius: inherit; min-width: 0;
                background: linear-gradient(90deg, var(--sfx-star), color-mix(in srgb, var(--sfx-star), #b45309 35%));
                transition: width .45s var(--wrx-ease);
            }
            .wrx.sfx .dist__row.low .dist__fill { background: linear-gradient(90deg, #f08a5d, var(--wrx-red)); }
            .wrx.sfx .dist__n { text-align: end; font-variant-numeric: tabular-nums; font-weight: 700; color: var(--wrx-ink); }
            .wrx.sfx .dist__n small { font-weight: 600; color: var(--wrx-mut); margin-inline-start: 3px; }

            .wrx.sfx .split { height: 10px; border-radius: 99px; overflow: hidden; display: flex; background: var(--wrx-line-soft); margin-bottom: 10px; }
            .wrx.sfx .split span { display: block; height: 100%; transition: width .45s var(--wrx-ease); }
            .wrx.sfx .split .compliment, .wrx.sfx .legend .compliment i { background: var(--sfx-good); }
            .wrx.sfx .split .suggestion, .wrx.sfx .legend .suggestion i { background: var(--wrx-blue); }
            .wrx.sfx .split .complaint, .wrx.sfx .legend .complaint i { background: var(--wrx-red); }
            .wrx.sfx .legend { display: flex; flex-direction: column; gap: 3px; }
            .wrx.sfx .legend button {
                display: grid; grid-template-columns: 9px minmax(0, 1fr) auto; align-items: center; gap: 8px;
                border: 1px solid transparent; background: none; border-radius: 7px; padding: 3px 6px; margin: 0 -6px;
                font-size: 11.5px; font-weight: 600; color: var(--wrx-ink-2); cursor: pointer; text-align: start; transition: background .15s;
            }
            .wrx.sfx .legend button:hover { background: var(--wrx-rail); }
            .wrx.sfx .legend button.is-on { background: var(--wrx-ac-soft); border-color: rgba(var(--wrx-ac-rgb), .35); color: var(--wrx-ink); }
            .wrx.sfx .legend i { width: 9px; height: 9px; border-radius: 3px; display: block; }
            .wrx.sfx .legend b { font-variant-numeric: tabular-nums; color: var(--wrx-ink); }
            .wrx.sfx .legend b small { font-weight: 600; color: var(--wrx-mut); margin-inline-start: 3px; }

            /* ── Table bits ─────────────────────────────────────── */
            .wrx.sfx .tag.good { background: var(--sfx-good-soft); color: var(--sfx-good); }
            .wrx.sfx .quote {
                max-width: 380px; min-width: 200px; color: var(--wrx-ink-2); line-height: 1.45; font-size: 12px;
                display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; white-space: pre-line;
            }
            .wrx.sfx .quote.none { color: var(--wrx-faint); font-style: italic; }

            @media (max-width: 1100px) {
                .wrx.sfx .pulse { grid-template-columns: 160px minmax(0, 1fr); }
                .wrx.sfx .pulse .types { grid-column: 1 / -1; }
            }
            @media (max-width: 720px) {
                .wrx.sfx .pulse { grid-template-columns: 1fr; }
            }
        </style>
    @endonce

    @php
        $typeLabels = feedbackTypes();
        $typeTags = ['compliment' => 'good', 'suggestion' => 'info', 'complaint' => 'bad'];
        $typeIcons = ['compliment' => 'fa-thumbs-up', 'suggestion' => 'fa-lightbulb-o', 'complaint' => 'fa-exclamation-circle'];
        $starMax = max($summary['stars']) ?: 1;
        $typeTotal = array_sum($summary['types']) ?: 1;
        $responseRate = $summary['sales'] ? round($summary['responses'] / $summary['sales'] * 100, 1) : 0;
    @endphp

    <div class="wrx sfx" wire:loading.class="is-busy">
        <div class="shell">
            {{-- ── Filter rail ─────────────────────────────────── --}}
            <aside class="rail">
                <div class="grp">
                    <h4>Period</h4>
                    <div class="quick">
                        @foreach ($ranges as $key => $label)
                            <button type="button" class="{{ $activeRange === $key ? 'is-on' : '' }}" wire:click="setRange('{{ $key }}')">{{ $label }}</button>
                        @endforeach
                    </div>
                    <div class="f">
                        <label for="sf_from">From</label>
                        <input type="date" id="sf_from" wire:model.live="from_date" max="{{ $to_date }}">
                    </div>
                    <div class="f">
                        <label for="sf_to">To</label>
                        <input type="date" id="sf_to" wire:model.live="to_date">
                    </div>
                    <div class="f">
                        <label for="sf_branch">Branch</label>
                        <select id="sf_branch" wire:model.live="branch_id">
                            <option value="">All branches</option>
                            @foreach ($branches as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grp">
                    <h4>Feedback type</h4>
                    <div class="seg">
                        <label>
                            <input type="radio" name="sf_type" value="" wire:model.live="feedback_type">
                            <i class="fa fa-th-large"></i> All types
                        </label>
                        @foreach ($typeLabels as $value => $label)
                            <label>
                                <input type="radio" name="sf_type" value="{{ $value }}" wire:model.live="feedback_type">
                                <i class="fa {{ $typeIcons[$value] ?? 'fa-comment' }}"></i> {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="grp">
                    <h4>Rating</h4>
                    <div class="f">
                        <label for="sf_rating">Stars</label>
                        <select id="sf_rating" wire:model.live="rating">
                            <option value="">Any rating</option>
                            @foreach ([5, 4, 3, 2, 1] as $star)
                                <option value="{{ $star }}">{{ $star }} {{ Str::plural('star', $star) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <label class="sw {{ $comments_only ? 'is-on' : '' }}">
                        <span><i class="fa fa-comment-o"></i> With comments only</span>
                        <input type="checkbox" class="sw__in" wire:model.live="comments_only">
                        <span class="sw__tr"></span>
                    </label>
                </div>

                <div class="rail-foot">
                    <button type="button" class="btn-x ghost" wire:click="resetFilters">Reset filters</button>
                </div>
            </aside>

            {{-- ── Main column ─────────────────────────────────── --}}
            <div class="main">
                <div class="wrxsum">
                    <div class="wrxsum__row">
                        <div class="stat hero">
                            <span class="stat__ic"><i class="fa fa-star"></i></span>
                            <div class="k">Average rating</div>
                            <div class="v">{{ $summary['rated'] ? number_format($summary['average'], 1) : '—' }}<small>&nbsp;/ 5</small></div>
                        </div>
                        <div class="stat">
                            <span class="stat__ic"><i class="fa fa-comments"></i></span>
                            <div class="k">Responses</div>
                            <div class="v">{{ number_format($summary['responses']) }}</div>
                        </div>
                        <div class="stat in">
                            <span class="stat__ic"><i class="fa fa-pie-chart"></i></span>
                            <div class="k">Response rate</div>
                            <div class="v">{{ $responseRate }}%<small>&nbsp;of {{ number_format($summary['sales']) }}</small></div>
                        </div>
                        <div class="stat good">
                            <span class="stat__ic"><i class="fa fa-thumbs-up"></i></span>
                            <div class="k">Compliments</div>
                            <div class="v">{{ number_format($summary['types']['compliment'] ?? 0) }}</div>
                        </div>
                        <div class="stat info">
                            <span class="stat__ic"><i class="fa fa-lightbulb-o"></i></span>
                            <div class="k">Suggestions</div>
                            <div class="v">{{ number_format($summary['types']['suggestion'] ?? 0) }}</div>
                        </div>
                        <div class="stat bad">
                            <span class="stat__ic"><i class="fa fa-exclamation-circle"></i></span>
                            <div class="k">Complaints</div>
                            <div class="v">{{ number_format($summary['types']['complaint'] ?? 0) }}</div>
                        </div>
                    </div>
                </div>

                {{-- ── Pulse: score · distribution · type split ───────── --}}
                <div class="pulse">
                    <div class="score">
                        <div class="score__num">{{ $summary['rated'] ? number_format($summary['average'], 1) : '—' }}<small>/5</small></div>
                        <div class="score__stars stars" aria-label="{{ $summary['average'] }} out of 5">
                            @for ($i = 1; $i <= 5; $i++)
                                <i class="fa {{ $summary['average'] >= $i ? 'fa-star on' : ($summary['average'] >= $i - 0.5 ? 'fa-star-half-o on' : 'fa-star-o') }}"></i>
                            @endfor
                        </div>
                        <div class="score__cap">{{ number_format($summary['rated']) }} {{ Str::plural('rating', $summary['rated']) }} · {{ number_format($summary['comments']) }} {{ Str::plural('comment', $summary['comments']) }}</div>
                    </div>

                    <div>
                        <h5>Rating distribution</h5>
                        <div class="dist">
                            @foreach ($summary['stars'] as $star => $count)
                                <button type="button" wire:click="filterRating({{ $star }})"
                                    class="dist__row {{ $star <= 2 ? 'low' : '' }} {{ (string) $rating === (string) $star ? 'is-on' : ($rating !== '' ? 'is-dim' : '') }}"
                                    title="Show {{ $star }}-star feedback">
                                    <span class="dist__lbl">{{ $star }} <i class="fa fa-star"></i></span>
                                    <span class="dist__bar"><span class="dist__fill" style="width: {{ round($count / $starMax * 100, 1) }}%"></span></span>
                                    <span class="dist__n">{{ number_format($count) }}<small>{{ $summary['rated'] ? round($count / $summary['rated'] * 100) : 0 }}%</small></span>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div class="types">
                        <h5>Feedback mix</h5>
                        <div class="split">
                            @foreach ($summary['types'] as $type => $count)
                                <span class="{{ $type }}" style="width: {{ round($count / $typeTotal * 100, 2) }}%"></span>
                            @endforeach
                        </div>
                        <div class="legend">
                            @foreach ($summary['types'] as $type => $count)
                                <button type="button" class="{{ $type }} {{ $feedback_type === $type ? 'is-on' : '' }}"
                                    wire:click="$set('feedback_type', '{{ $feedback_type === $type ? '' : $type }}')">
                                    <i></i>
                                    <span>{{ $typeLabels[$type] ?? ucfirst($type) }}</span>
                                    <b>{{ number_format($count) }}<small>{{ round($count / $typeTotal * 100) }}%</small></b>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="tools">
                    <div class="search">
                        <i class="fa fa-search"></i>
                        <input type="text" id="sf_search" wire:model.live.debounce.400ms="search" placeholder="Search invoice, customer, mobile or comment" aria-label="Search feedback">
                    </div>
                    <span class="busy" wire:loading><i class="fa fa-refresh fa-spin"></i></span>
                    <div class="tools__end">
                        <span class="tools__cnt">{{ number_format($rows->total()) }} {{ Str::plural('response', $rows->total()) }}</span>
                        <select wire:model.live="perPage" aria-label="Rows per page">
                            <option value="25">25 rows</option>
                            <option value="100">100 rows</option>
                            <option value="500">500 rows</option>
                        </select>
                    </div>
                </div>

                <div class="tbl-wrap">
                    <table class="wt wide">
                        <thead>
                            <tr>
                                <th>
                                    <button type="button" class="th-sort {{ $sortField === 'sales.date' ? 'is-on' : '' }}" wire:click="sortBy('sales.date')">
                                        Date <i class="fa fa-sort{{ $sortField === 'sales.date' ? '-' . $sortDirection : '' }}"></i>
                                    </button>
                                </th>
                                <th>
                                    <button type="button" class="th-sort {{ $sortField === 'sales.invoice_no' ? 'is-on' : '' }}" wire:click="sortBy('sales.invoice_no')">
                                        Invoice <i class="fa fa-sort{{ $sortField === 'sales.invoice_no' ? '-' . $sortDirection : '' }}"></i>
                                    </button>
                                </th>
                                <th>Customer</th>
                                <th>
                                    <button type="button" class="th-sort {{ $sortField === 'sales.rating' ? 'is-on' : '' }}" wire:click="sortBy('sales.rating')">
                                        Rating <i class="fa fa-sort{{ $sortField === 'sales.rating' ? '-' . $sortDirection : '' }}"></i>
                                    </button>
                                </th>
                                <th>Type</th>
                                <th>Comment</th>
                                <th class="num">
                                    <button type="button" class="th-sort {{ $sortField === 'sales.grand_total' ? 'is-on' : '' }}" wire:click="sortBy('sales.grand_total')">
                                        Bill <i class="fa fa-sort{{ $sortField === 'sales.grand_total' ? '-' . $sortDirection : '' }}"></i>
                                    </button>
                                </th>
                                <th>Taken by</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $row)
                                <tr wire:key="feedback-{{ $row->id }}" class="{{ $row->feedback_type === 'complaint' || ($row->rating > 0 && $row->rating <= 2) ? 'is-flagged' : '' }}">
                                    <td class="nowrap">
                                        {{ systemDate($row->date) }}
                                        @if (blank($branch_id))
                                            <div class="sub">{{ $row->branch?->name }}</div>
                                        @endif
                                    </td>
                                    <td class="nowrap">
                                        @can('sale.view')
                                            <a href="{{ route('sale::view', $row->id) }}" class="nm">{{ $row->invoice_no }}</a>
                                        @else
                                            <span class="nm">{{ $row->invoice_no }}</span>
                                        @endcan
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $row->customer_name ?: $row->account?->name ?: '—' }}</div>
                                        @if ($row->customer_mobile ?: $row->account?->mobile)
                                            <div class="sub mono">{{ $row->customer_mobile ?: $row->account?->mobile }}</div>
                                        @endif
                                    </td>
                                    <td class="nowrap">
                                        @if ($row->rating > 0)
                                            <span class="stars" aria-label="{{ $row->rating }} out of 5">
                                                @for ($i = 1; $i <= 5; $i++)
                                                    <i class="fa fa-star {{ $row->rating >= $i ? 'on' : '' }}"></i>
                                                @endfor
                                            </span>
                                        @else
                                            <span class="zero">Not rated</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($row->feedback_type)
                                            <span class="tag {{ $typeTags[$row->feedback_type] ?? 'off' }}">
                                                <i class="fa {{ $typeIcons[$row->feedback_type] ?? 'fa-comment' }}"></i> {{ $typeLabels[$row->feedback_type] ?? ucfirst($row->feedback_type) }}
                                            </span>
                                        @else
                                            <span class="tag off">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if (filled($row->feedback))
                                            <div class="quote" title="{{ $row->feedback }}">{{ $row->feedback }}</div>
                                        @else
                                            <div class="quote none">No comment</div>
                                        @endif
                                    </td>
                                    <td class="num">{{ currency($row->grand_total) }}</td>
                                    <td class="nowrap">{{ $row->createdUser?->name ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8">
                                        <div class="empty">
                                            <div class="empty__ring"><i class="fa fa-star-o"></i></div>
                                            <h4>No feedback in this range</h4>
                                            <p>Try a wider period, another branch, or clear the rating and type filters.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="foot">
                    <span>
                        @if ($rows->total())
                            Showing {{ number_format($rows->firstItem()) }}–{{ number_format($rows->lastItem()) }} of {{ number_format($rows->total()) }}
                        @else
                            No rows
                        @endif
                    </span>
                    @if ($rows->hasPages())
                        {{ $rows->links() }}
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
