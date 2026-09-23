<div>
    <x-report.studio />

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
                        <label for="wr_from">From</label>
                        <input type="date" id="wr_from" wire:model.live="from_date" max="{{ $to_date }}">
                    </div>
                    <div class="f">
                        <label for="wr_to">To</label>
                        <input type="date" id="wr_to" wire:model.live="to_date" max="{{ date('Y-m-d') }}">
                    </div>
                </div>

                <div class="grp">
                    <h4>Cohort</h4>
                    <div class="f">
                        <label for="wr_grade">Grade</label>
                        <select id="wr_grade" wire:model.live="grade">
                            <option value="">All grades</option>
                            @foreach ($grades as $value)
                                <option value="{{ $value }}">{{ $value }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="f">
                        <label for="wr_section">Section</label>
                        <select id="wr_section" wire:model.live="section">
                            <option value="">All sections</option>
                            @foreach ($sections as $value)
                                <option value="{{ $value }}">{{ $value }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="f">
                        <label for="wr_status">Student status</label>
                        <select id="wr_status" wire:model.live="status">
                            <option value="">All</option>
                            @foreach (\App\Models\StudentDetail::STATUSES as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grp">
                    <h4>Flags</h4>
                    <label class="sw {{ $overdrawn_only ? 'is-on' : '' }}">
                        <span><i class="fa fa-exclamation-triangle"></i> Overdrawn only</span>
                        <input type="checkbox" class="sw__in" wire:model.live="overdrawn_only">
                        <span class="sw__tr"></span>
                    </label>
                </div>

                <div class="rail-foot">
                    @can('report.student wallet')
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
                            <span class="stat__ic"><i class="fa fa-credit-card"></i></span>
                            <div class="k">On cards now</div>
                            <div class="v">{{ currency($totals->closing) }}</div>
                        </div>
                        <div class="stat">
                            <span class="stat__ic"><i class="fa fa-users"></i></span>
                            <div class="k">Students</div>
                            <div class="v">{{ number_format((int) $totals->students) }}</div>
                        </div>
                        <div class="stat">
                            <span class="stat__ic"><i class="fa fa-history"></i></span>
                            <div class="k">Opening</div>
                            <div class="v">{{ currency($totals->opening) }}</div>
                        </div>
                        <div class="stat in">
                            <span class="stat__ic"><i class="fa fa-arrow-down"></i></span>
                            <div class="k">Added</div>
                            <div class="v">{{ currency($totals->period_in) }}</div>
                        </div>
                        <div class="stat out">
                            <span class="stat__ic"><i class="fa fa-arrow-up"></i></span>
                            <div class="k">Spent</div>
                            <div class="v">{{ currency($totals->period_out) }}</div>
                        </div>
                        <div class="stat bad">
                            <span class="stat__ic"><i class="fa fa-exclamation-triangle"></i></span>
                            <div class="k">Overdrawn</div>
                            <div class="v">{{ currency(abs($totals->overdrawn)) }} <small>· {{ (int) $totals->overdrawn_students }}</small></div>
                        </div>
                    </div>
                    <p class="wrxsum__note">"On cards now" is the money the school is holding for families — it matches the Student Card Balances total in the books.</p>
                </div>

                <div class="tools">
                    <div class="search">
                        <i class="fa fa-search"></i>
                        <input type="text" id="wr_search" wire:model.live.debounce.400ms="search" placeholder="Search name, admission no or card" aria-label="Search students">
                    </div>
                    <span class="busy" wire:loading><i class="fa fa-refresh fa-spin"></i></span>
                    <div class="tools__end">
                        <span class="tools__cnt">{{ number_format($rows->total()) }} {{ Str::plural('student', $rows->total()) }}</span>
                        <select wire:model.live="perPage" aria-label="Rows per page">
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
                                    <button type="button" class="th-sort {{ $sortField === 'student_details.admission_no' ? 'is-on' : '' }}" wire:click="sortBy('student_details.admission_no')">
                                        Admission <i class="fa fa-sort{{ $sortField === 'student_details.admission_no' ? '-' . $sortDirection : '' }}"></i>
                                    </button>
                                </th>
                                <th>
                                    <button type="button" class="th-sort {{ $sortField === 'accounts.name' ? 'is-on' : '' }}" wire:click="sortBy('accounts.name')">
                                        Student <i class="fa fa-sort{{ $sortField === 'accounts.name' ? '-' . $sortDirection : '' }}"></i>
                                    </button>
                                </th>
                                <th>
                                    <button type="button" class="th-sort {{ $sortField === 'student_details.grade' ? 'is-on' : '' }}" wire:click="sortBy('student_details.grade')">
                                        Class <i class="fa fa-sort{{ $sortField === 'student_details.grade' ? '-' . $sortDirection : '' }}"></i>
                                    </button>
                                </th>
                                <th>Card</th>
                                <th class="num">Opening</th>
                                <th class="num">
                                    <button type="button" class="th-sort {{ $sortField === 'period_in' ? 'is-on' : '' }}" wire:click="sortBy('period_in')">
                                        Added <i class="fa fa-sort{{ $sortField === 'period_in' ? '-' . $sortDirection : '' }}"></i>
                                    </button>
                                </th>
                                <th class="num">
                                    <button type="button" class="th-sort {{ $sortField === 'period_out' ? 'is-on' : '' }}" wire:click="sortBy('period_out')">
                                        Spent <i class="fa fa-sort{{ $sortField === 'period_out' ? '-' . $sortDirection : '' }}"></i>
                                    </button>
                                </th>
                                <th class="num">
                                    <button type="button" class="th-sort {{ $sortField === 'closing_balance' ? 'is-on' : '' }}" wire:click="sortBy('closing_balance')">
                                        Balance <i class="fa fa-sort{{ $sortField === 'closing_balance' ? '-' . $sortDirection : '' }}"></i>
                                    </button>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $row)
                                <tr wire:key="wallet-{{ $row->id }}" class="{{ $row->closing_balance < 0 ? 'is-flagged' : '' }}">
                                    <td class="adm">{{ $row->admission_no }}</td>
                                    <td>
                                        <a href="{{ route('student::view', $row->id) }}" class="nm">{{ $row->name }}</a>
                                    </td>
                                    <td>{{ trim(implode(' - ', array_filter([$row->grade, $row->section]))) ?: '—' }}</td>
                                    <td>
                                        @if (! $row->card_uid)
                                            <span class="tag off">No card</span>
                                        @elseif ($row->card_status === 'blocked')
                                            <span class="tag bad">Blocked</span>
                                        @else
                                            <span class="tag">Active</span>
                                        @endif
                                    </td>
                                    <td class="num {{ (float) $row->opening_balance == 0.0 ? 'zero' : '' }}">{{ currency($row->opening_balance) }}</td>
                                    <td class="num {{ $row->period_in > 0 ? 'in' : 'zero' }}">{{ $row->period_in > 0 ? currency($row->period_in) : '—' }}</td>
                                    <td class="num {{ $row->period_out > 0 ? 'out' : 'zero' }}">{{ $row->period_out > 0 ? currency($row->period_out) : '—' }}</td>
                                    <td class="num bal {{ $row->closing_balance < 0 ? 'neg' : '' }}">{{ currency($row->closing_balance) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8">
                                        <div class="empty">
                                            <div class="empty__ring"><i class="fa fa-credit-card"></i></div>
                                            <h4>No students match these filters</h4>
                                            <p>Try a wider date range, another grade, or clear the overdrawn flag.</p>
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
