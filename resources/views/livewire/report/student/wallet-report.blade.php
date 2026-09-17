<div class="card shadow-sm">
    <div class="card-header bg-white py-3">
        <div class="row g-2 align-items-end">
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold text-muted mb-1" for="wr_from">From</label>
                <input type="date" id="wr_from" class="form-control form-control-sm" wire:model.live="from_date">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold text-muted mb-1" for="wr_to">To</label>
                <input type="date" id="wr_to" class="form-control form-control-sm" wire:model.live="to_date" max="{{ date('Y-m-d') }}">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold text-muted mb-1" for="wr_grade">Grade</label>
                <select id="wr_grade" class="form-select form-select-sm" wire:model.live="grade">
                    <option value="">All grades</option>
                    @foreach ($grades as $value)
                        <option value="{{ $value }}">{{ $value }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold text-muted mb-1" for="wr_section">Section</label>
                <select id="wr_section" class="form-select form-select-sm" wire:model.live="section">
                    <option value="">All sections</option>
                    @foreach ($sections as $value)
                        <option value="{{ $value }}">{{ $value }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold text-muted mb-1" for="wr_status">Student status</label>
                <select id="wr_status" class="form-select form-select-sm" wire:model.live="status">
                    <option value="">All</option>
                    @foreach (\App\Models\StudentDetail::STATUSES as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold text-muted mb-1" for="wr_search">Search</label>
                <input type="text" id="wr_search" class="form-control form-control-sm" wire:model.live.debounce.400ms="search" placeholder="Name, admission no, card">
            </div>
        </div>
        <div class="d-flex flex-wrap gap-3 align-items-center mt-3">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="wr_overdrawn" wire:model.live="overdrawn_only">
                <label class="form-check-label small" for="wr_overdrawn">Overdrawn cards only</label>
            </div>
            <select class="form-select form-select-sm" style="max-width: 7rem" wire:model.live="perPage" aria-label="Rows per page">
                <option value="25">25</option>
                <option value="100">100</option>
                <option value="500">500</option>
            </select>
            @can('report.student wallet')
                <button class="btn btn-success btn-sm ms-auto" wire:click="export">
                    <i class="fa fa-file-excel-o me-1"></i> Export
                </button>
            @endcan
        </div>
    </div>

    <div class="card-body pb-0">
        <div class="row g-2">
            @php($tiles = [
                ['Students', (int) $totals->students, 'text-body'],
                ['Opening', currency($totals->opening), 'text-body'],
                ['Added', currency($totals->period_in), 'text-success'],
                ['Spent', currency($totals->period_out), 'text-danger'],
                ['On cards now', currency($totals->closing), 'text-primary'],
                ['Overdrawn', currency(abs($totals->overdrawn)) . ' · ' . (int) $totals->overdrawn_students, 'text-danger'],
            ])
            @foreach ($tiles as [$label, $value, $class])
                <div class="col-6 col-lg-2">
                    <div class="border rounded p-2 h-100 text-center">
                        <div class="small text-muted">{{ $label }}</div>
                        <div class="fw-bold {{ $class }}">{{ $value }}</div>
                    </div>
                </div>
            @endforeach
        </div>
        <p class="small text-muted mt-2 mb-0">
            "On cards now" is the money the school is holding for families — it matches the Student Card Balances total in the books.
        </p>
    </div>

    <div class="card-body p-0 mt-3">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle border-bottom mb-0">
                <thead class="bg-light text-muted small">
                    <tr>
                        <th class="ps-3"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="student_details.admission_no" label="Admission No" /></th>
                        <th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="accounts.name" label="Student" /></th>
                        <th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="student_details.grade" label="Class" /></th>
                        <th>Card</th>
                        <th class="text-end">Opening</th>
                        <th class="text-end"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="period_in" label="Added" /></th>
                        <th class="text-end"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="period_out" label="Spent" /></th>
                        <th class="text-end pe-3"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="closing_balance" label="Balance" /></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr wire:key="wallet-{{ $row->id }}">
                            <td class="ps-3 text-nowrap">{{ $row->admission_no }}</td>
                            <td class="text-nowrap">
                                <a href="{{ route('student::view', $row->id) }}" class="text-decoration-none fw-medium">{{ $row->name }}</a>
                            </td>
                            <td class="text-nowrap">{{ trim(implode(' - ', array_filter([$row->grade, $row->section]))) ?: '-' }}</td>
                            <td class="text-nowrap">
                                @if (!$row->card_uid)
                                    <span class="badge bg-light text-body-secondary border">No card</span>
                                @elseif ($row->card_status === 'blocked')
                                    <span class="badge bg-danger">Blocked</span>
                                @else
                                    <span class="badge bg-success">Active</span>
                                @endif
                            </td>
                            <td class="text-end">{{ currency($row->opening_balance) }}</td>
                            <td class="text-end text-success">{{ $row->period_in > 0 ? currency($row->period_in) : '' }}</td>
                            <td class="text-end text-danger">{{ $row->period_out > 0 ? currency($row->period_out) : '' }}</td>
                            <td class="text-end pe-3 fw-semibold {{ $row->closing_balance < 0 ? 'text-danger' : '' }}">{{ currency($row->closing_balance) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No students match these filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3 border-top">{{ $rows->links() }}</div>
    </div>
</div>
