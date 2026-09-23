<div>
    <x-report.studio />

    @php
        $statuses = [
            '' => 'All statuses',
            'success' => 'Successful',
            'pending' => 'Pending',
            'failed' => 'Failed',
            'cancelled' => 'Cancelled',
            'review' => 'Needs review',
            'unresolved' => 'Unresolved',
            'refunded' => 'Refunded',
            'refund_pending' => 'Refund pending',
        ];
        $statusTone = fn (string $status) => match (true) {
            $status === 'success' => '',
            $status === 'failed' => 'bad',
            in_array($status, ['pending', 'refund_pending', 'review'], true) => 'warn',
            default => 'off',
        };
    @endphp

    <div class="wrx" wire:loading.class="is-busy">
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
                        <label for="qr_from">From</label>
                        <input type="date" id="qr_from" wire:model.live="from_date" max="{{ $to_date }}">
                    </div>
                    <div class="f">
                        <label for="qr_to">To</label>
                        <input type="date" id="qr_to" wire:model.live="to_date" max="{{ date('Y-m-d') }}">
                    </div>
                </div>

                <div class="grp">
                    <h4>Transactions</h4>
                    <div class="f">
                        <label for="qr_status">Status</label>
                        <select id="qr_status" wire:model.live="status">
                            @foreach ($statuses as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="f">
                        <label for="qr_type">Type</label>
                        <select id="qr_type" wire:model.live="type">
                            <option value="">Payments &amp; refunds</option>
                            <option value="payment">Payments</option>
                            <option value="refund">Refunds</option>
                        </select>
                    </div>
                </div>

                <div class="grp">
                    <h4>Card</h4>
                    <div class="seg" role="radiogroup" aria-label="Card type">
                        @foreach (['' => ['fa-th-large', 'All cards'], 'qpay' => ['fa-globe', 'Debit · QPay'], 'mpgs' => ['fa-credit-card', 'Credit · MPGS']] as $value => [$icon, $label])
                            <label>
                                <input type="radio" name="qr_gateway" value="{{ $value }}" wire:model.live="gateway">
                                <i class="fa {{ $icon }}"></i> {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="rail-foot">
                    @can('report.student recharge')
                        <button type="button" class="btn-x solid" wire:click="export">
                            <i class="fa fa-download"></i> Export
                        </button>
                    @endcan
                    <button type="button" class="btn-x ghost" wire:click="resetFilters">Reset filters</button>
                </div>
            </aside>

            {{-- ── Main column ─────────────────────────────────── --}}
            <div class="main">
                <div class="wrxsum">
                    <div class="wrxsum__row">
                        <div class="stat hero">
                            <span class="stat__ic"><i class="fa fa-money"></i></span>
                            <div class="k">Collected</div>
                            <div class="v">{{ currency($totals['collected']) }}</div>
                        </div>
                        <div class="stat">
                            <span class="stat__ic"><i class="fa fa-check"></i></span>
                            <div class="k">Top-ups</div>
                            <div class="v">{{ number_format((int) $totals['payments']) }}</div>
                        </div>
                        <div class="stat out">
                            <span class="stat__ic"><i class="fa fa-undo"></i></span>
                            <div class="k">Refunded</div>
                            <div class="v">{{ currency($totals['refunded']) }}</div>
                        </div>
                        <div class="stat warn">
                            <span class="stat__ic"><i class="fa fa-clock-o"></i></span>
                            <div class="k">Pending</div>
                            <div class="v">{{ number_format((int) $totals['pending']) }}</div>
                        </div>
                        <div class="stat off">
                            <span class="stat__ic"><i class="fa fa-times"></i></span>
                            <div class="k">Failed</div>
                            <div class="v">{{ number_format((int) $totals['failed']) }}</div>
                        </div>
                        <div class="stat bad">
                            <span class="stat__ic"><i class="fa fa-exclamation-triangle"></i></span>
                            <div class="k">Needs review</div>
                            <div class="v">{{ number_format((int) $totals['review']) }}</div>
                        </div>
                    </div>
                    <p class="wrxsum__note">
                        "Collected" counts only payments the gateway confirmed — those are the ones on the students' cards and in the books.
                        A payment left pending can be checked with its gateway (QPay for debit cards, MPGS for credit cards) from its row.
                    </p>
                </div>

                <div class="tools">
                    <div class="search">
                        <i class="fa fa-search"></i>
                        <input type="text" id="qr_search" wire:model.live.debounce.400ms="search"
                            placeholder="Student, admission no, parent, mobile, PUN or confirmation" aria-label="Search recharges">
                    </div>
                    <span class="busy" wire:loading><i class="fa fa-refresh fa-spin"></i></span>
                    <div class="tools__end">
                        <span class="tools__cnt">{{ number_format($rows->total()) }} {{ Str::plural('transaction', $rows->total()) }}</span>
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
                                    <button type="button" class="th-sort {{ $sortField === 'qpay_transactions.id' ? 'is-on' : '' }}" wire:click="sortBy('qpay_transactions.id')">
                                        Date <i class="fa fa-sort{{ $sortField === 'qpay_transactions.id' ? '-' . $sortDirection : '' }}"></i>
                                    </button>
                                </th>
                                <th>
                                    <button type="button" class="th-sort {{ $sortField === 'accounts.name' ? 'is-on' : '' }}" wire:click="sortBy('accounts.name')">
                                        Student <i class="fa fa-sort{{ $sortField === 'accounts.name' ? '-' . $sortDirection : '' }}"></i>
                                    </button>
                                </th>
                                <th>Parent</th>
                                <th>PUN / confirmation</th>
                                <th>Card</th>
                                <th class="num">
                                    <button type="button" class="th-sort {{ $sortField === 'qpay_transactions.amount' ? 'is-on' : '' }}" wire:click="sortBy('qpay_transactions.amount')">
                                        Amount <i class="fa fa-sort{{ $sortField === 'qpay_transactions.amount' ? '-' . $sortDirection : '' }}"></i>
                                    </button>
                                </th>
                                <th>
                                    <button type="button" class="th-sort {{ $sortField === 'qpay_transactions.status' ? 'is-on' : '' }}" wire:click="sortBy('qpay_transactions.status')">
                                        Status <i class="fa fa-sort{{ $sortField === 'qpay_transactions.status' ? '-' . $sortDirection : '' }}"></i>
                                    </button>
                                </th>
                                <th>Gateway response</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $row)
                                <tr wire:key="qpay-{{ $row->id }}" class="{{ $row->status === 'review' || $row->tampered_at ? 'is-flagged' : '' }}">
                                    <td class="nowrap">
                                        {{ systemDate($row->created_at) }}
                                        <div class="sub mono">{{ $row->created_at?->format('h:i A') }}</div>
                                    </td>
                                    <td class="nowrap">
                                        <a href="{{ route('student::view', $row->account_id) }}" class="nm">{{ $row->student_name }}</a>
                                        <div class="sub">{{ $row->admission_no }}{{ $row->grade ? ' · ' . trim(implode(' - ', array_filter([$row->grade, $row->section]))) : '' }}</div>
                                    </td>
                                    <td class="nowrap">
                                        {{ $row->guardian_name ?: '—' }}
                                        @if ($row->guardian_mobile)
                                            <div class="sub mono">{{ $row->guardian_mobile }}</div>
                                        @endif
                                    </td>
                                    <td class="nowrap mono" style="font-size: 11.5px">
                                        {{ $row->pun }}
                                        @if ($row->confirmation_id)
                                            <div class="sub mono">{{ $row->confirmation_id }}</div>
                                        @endif
                                    </td>
                                    <td class="nowrap">
                                        <span class="tag {{ $row->isCreditCard() ? 'info' : 'off' }}">
                                            <i class="fa {{ $row->isCreditCard() ? 'fa-credit-card' : 'fa-globe' }}"></i>{{ $row->methodLabel() }}
                                        </span>
                                        <div class="sub mono">{{ $row->cardLabel() ?: '—' }}</div>
                                    </td>
                                    <td class="num nowrap bal {{ $row->type === 'refund' ? 'neg' : '' }}">
                                        {{ $row->type === 'refund' ? '−' : '' }}{{ currency($row->amount) }}
                                    </td>
                                    <td class="nowrap">
                                        <span class="tag {{ $statusTone($row->status) }}">{{ $row->statusLabel() }}</span>
                                        @if ($row->tampered_at)
                                            <div class="sub"><span class="tag bad" title="The response failed the secure hash check and was verified by inquiry">Tampered</span></div>
                                        @endif
                                    </td>
                                    <td class="sub" style="max-width: 180px; white-space: normal">
                                        {{ $row->gateway_status }} {{ $row->gateway_status_message }}
                                        @if ($row->failure_reason)
                                            <div class="out">{{ $row->failure_reason }}</div>
                                        @endif
                                    </td>
                                    <td class="num">
                                        {{-- Released payments are still asked about, so they keep the Check button. --}}
                                        @if ($row->awaitsResult())
                                            <button type="button" class="icon-btn" wire:click="inquire({{ $row->id }})" wire:loading.attr="disabled" title="Ask {{ $row->gatewayLabel() }} for the result">
                                                <i class="fa fa-refresh"></i> Check
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9">
                                        <div class="empty">
                                            <div class="empty__ring"><i class="fa fa-credit-card"></i></div>
                                            <h4>No online recharges match these filters</h4>
                                            <p>Try a wider date range, another status, or clear the card type.</p>
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
