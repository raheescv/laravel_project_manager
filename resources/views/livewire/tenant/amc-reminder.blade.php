{{-- Tenant Control → AMC renewals coming up, on the shared Report Studio (.wrx) system. --}}
@php
    $sortIcon = fn (string $field) => 'fa fa-sort' . ($sortField === $field ? '-' . $sortDirection : '');
@endphp
<div>
    <x-report.studio />

    <div class="wrx" wire:loading.class="is-busy">
        <div class="shell">
            {{-- ── Filter rail ─────────────────────────────────── --}}
            <aside class="rail">
                <div class="grp">
                    <h4>Renewing</h4>
                    <div class="seg" role="radiogroup" aria-label="Renewal window">
                        @foreach ($windows as $value => [$icon, $label])
                            <label>
                                <input type="radio" name="amc_window" value="{{ $value }}" wire:model.live="window">
                                <i class="fa {{ $icon }}"></i> {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="grp">
                    <h4>Tenants</h4>
                    <label class="sw {{ $includeInactive ? 'is-on' : '' }}">
                        <span><i class="fa fa-power-off"></i> Include inactive</span>
                        <input type="checkbox" class="sw__in" wire:model.live="includeInactive">
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
                            <span class="stat__ic"><i class="fa fa-bell"></i></span>
                            <div class="k">To collect</div>
                            <div class="v">{{ number_format($summary['window'][1], 2) }} <small>· {{ $summary['window'][0] }}</small></div>
                        </div>
                        <div class="stat bad">
                            <span class="stat__ic"><i class="fa fa-exclamation-circle"></i></span>
                            <div class="k">Overdue</div>
                            <div class="v">{{ number_format($summary['overdue'][1], 2) }} <small>· {{ $summary['overdue'][0] }}</small></div>
                        </div>
                        <div class="stat warn">
                            <span class="stat__ic"><i class="fa fa-clock-o"></i></span>
                            <div class="k">Due in 7 days</div>
                            <div class="v">{{ number_format($summary['week'][1], 2) }} <small>· {{ $summary['week'][0] }}</small></div>
                        </div>
                        <div class="stat in">
                            <span class="stat__ic"><i class="fa fa-check"></i></span>
                            <div class="k">Collected this month</div>
                            <div class="v">{{ number_format($summary['collected'], 2) }}</div>
                        </div>
                        <div class="stat">
                            <span class="stat__ic"><i class="fa fa-building"></i></span>
                            <div class="k">Scheduled</div>
                            <div class="v">{{ number_format($summary['scheduled']) }}</div>
                        </div>
                        <div class="stat off">
                            <span class="stat__ic"><i class="fa fa-question-circle"></i></span>
                            <div class="k">No renewal date</div>
                            <div class="v">{{ number_format($summary['unscheduled']) }}</div>
                        </div>
                    </div>
                    <p class="wrxsum__note">
                        Overdue renewals are always listed, whatever the window. Active tenants with no renewal date are left out —
                        <a href="{{ route('tenants::index') }}">set one from Tenant Control</a>.
                    </p>
                </div>

                <div class="tools">
                    <div class="search">
                        <i class="fa fa-search"></i>
                        <input type="text" wire:model.live.debounce.400ms="search" placeholder="Search name, code or subdomain" aria-label="Search tenants">
                    </div>
                    <span class="busy" wire:loading><i class="fa fa-refresh fa-spin"></i></span>
                    <div class="tools__end">
                        <span class="tools__cnt">{{ number_format($data->total()) }} {{ Str::plural('tenant', $data->total()) }}</span>
                        <select wire:model.live="limit" aria-label="Rows per page">
                            <option value="25">25 rows</option>
                            <option value="100">100 rows</option>
                            <option value="500">500 rows</option>
                        </select>
                    </div>
                </div>

                <div class="tbl-wrap">
                    <table class="wt">
                        <thead>
                            <tr>
                                <th>
                                    <button type="button" class="th-sort {{ $sortField === 'name' ? 'is-on' : '' }}" wire:click="sortBy('name')">
                                        Tenant <i class="{{ $sortIcon('name') }}"></i>
                                    </button>
                                </th>
                                <th>
                                    <button type="button" class="th-sort {{ $sortField === 'renews_on' ? 'is-on' : '' }}" wire:click="sortBy('renews_on')">
                                        Renewal <i class="{{ $sortIcon('renews_on') }}"></i>
                                    </button>
                                </th>
                                <th>Due</th>
                                <th class="num">
                                    <button type="button" class="th-sort {{ $sortField === 'amc_amount' ? 'is-on' : '' }}" wire:click="sortBy('amc_amount')">
                                        AMC <i class="{{ $sortIcon('amc_amount') }}"></i>
                                    </button>
                                </th>
                                <th>Cycle</th>
                                <th>
                                    <button type="button" class="th-sort {{ $sortField === 'payments_max_paid_on' ? 'is-on' : '' }}" wire:click="sortBy('payments_max_paid_on')">
                                        Last paid <i class="{{ $sortIcon('payments_max_paid_on') }}"></i>
                                    </button>
                                </th>
                                <th>Contact</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($data as $tenant)
                                @php
                                    $renewal = $tenant->renewalState();
                                    $contact = $contacts[$tenant->id] ?? collect();
                                @endphp
                                <tr wire:key="amc-row-{{ $tenant->id }}" class="{{ $renewal === 'overdue' ? 'is-flagged' : '' }}">
                                    <td>
                                        <a href="{{ route('tenants::view', $tenant->id) }}" class="nm">{{ $tenant->name }}</a>
                                        <div class="sub">
                                            {{ $tenant->code }}
                                            @unless ($tenant->is_active)
                                                · <span class="tag off">Inactive</span>
                                            @endunless
                                        </div>
                                    </td>
                                    <td>{{ $tenant->renews_on->format('d M Y') }}</td>
                                    <td>
                                        <span class="tag {{ ['overdue' => 'bad', 'due' => 'warn', 'ok' => 'info'][$renewal] ?? '' }}">{{ $tenant->renewalCountdown() }}</span>
                                    </td>
                                    <td class="num {{ $tenant->amc_amount === null ? 'zero' : 'bal' }}">{{ $tenant->amc_amount !== null ? number_format((float) $tenant->amc_amount, 2) : '—' }}</td>
                                    <td>{{ $tenant->amcCycleLabel() ?? '—' }}</td>
                                    <td>{{ $tenant->payments_max_paid_on ? \Illuminate\Support\Carbon::parse($tenant->payments_max_paid_on)->format('d M Y') : 'Never' }}</td>
                                    <td>
                                        @if ($contact->get('mobile'))
                                            <a href="tel:{{ $contact->get('mobile') }}" class="nm"><i class="fa fa-phone"></i> {{ $contact->get('mobile') }}</a>
                                        @endif
                                        @if ($contact->get('email'))
                                            <div class="sub"><a href="mailto:{{ $contact->get('email') }}"><i class="fa fa-envelope"></i> {{ $contact->get('email') }}</a></div>
                                        @endif
                                        @if (!$contact->get('mobile') && !$contact->get('email'))
                                            <span class="zero">—</span>
                                        @endif
                                    </td>
                                    <td class="num">
                                        <a href="{{ route('tenants::view', ['id' => $tenant->id, 'tab' => 'billing']) }}" class="icon-btn" title="Record a payment for {{ $tenant->name }}">
                                            <i class="fa fa-money"></i> Record payment
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8">
                                        <div class="empty">
                                            <div class="empty__ring"><i class="fa fa-bell-slash"></i></div>
                                            <h4>No AMC renewals in this range</h4>
                                            <p>Pick a wider window, include inactive tenants, or clear the search.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="foot">
                    <span>
                        @if ($data->total())
                            Showing {{ number_format($data->firstItem()) }}–{{ number_format($data->lastItem()) }} of {{ number_format($data->total()) }}
                        @else
                            No rows
                        @endif
                    </span>
                    @if ($data->hasPages())
                        {{ $data->links() }}
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
