<div class="card shadow-sm">
    <div class="card-header bg-white py-3">
        <div class="row g-2 align-items-end">
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold text-muted mb-1" for="qr_from">From</label>
                <input type="date" id="qr_from" class="form-control form-control-sm" wire:model.live="from_date">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold text-muted mb-1" for="qr_to">To</label>
                <input type="date" id="qr_to" class="form-control form-control-sm" wire:model.live="to_date" max="{{ date('Y-m-d') }}">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold text-muted mb-1" for="qr_status">Status</label>
                <select id="qr_status" class="form-select form-select-sm" wire:model.live="status">
                    <option value="">All statuses</option>
                    <option value="success">Successful</option>
                    <option value="pending">Pending</option>
                    <option value="failed">Failed</option>
                    <option value="cancelled">Cancelled</option>
                    <option value="review">Needs review</option>
                    <option value="unresolved">Unresolved</option>
                    <option value="refunded">Refunded</option>
                    <option value="refund_pending">Refund pending</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold text-muted mb-1" for="qr_type">Type</label>
                <select id="qr_type" class="form-select form-select-sm" wire:model.live="type">
                    <option value="">Payments &amp; refunds</option>
                    <option value="payment">Payments</option>
                    <option value="refund">Refunds</option>
                </select>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label small fw-semibold text-muted mb-1" for="qr_search">Search</label>
                <input type="text" id="qr_search" class="form-control form-control-sm" wire:model.live.debounce.400ms="search"
                    placeholder="Student, admission no, parent, mobile, PUN or confirmation">
            </div>
        </div>
        <div class="d-flex flex-wrap gap-3 align-items-center mt-3">
            <select class="form-select form-select-sm" style="max-width: 7rem" wire:model.live="perPage" aria-label="Rows per page">
                <option value="25">25</option>
                <option value="100">100</option>
                <option value="500">500</option>
            </select>
            <div class="btn-group btn-group-sm" role="group" aria-label="Card type">
                @foreach (['' => 'All cards', 'qpay' => 'Debit · QPay', 'mpgs' => 'Credit · MPGS'] as $value => $label)
                    <input type="radio" class="btn-check" name="qr_gateway" id="qr_gateway_{{ $value ?: 'all' }}" value="{{ $value }}" wire:model.live="gateway" autocomplete="off">
                    <label class="btn btn-outline-secondary" for="qr_gateway_{{ $value ?: 'all' }}">{{ $label }}</label>
                @endforeach
            </div>
            @can('report.student recharge')
                <button class="btn btn-success btn-sm ms-auto" wire:click="export">
                    <i class="fa fa-file-excel-o me-1"></i> Export
                </button>
            @endcan
        </div>
    </div>

    <div class="card-body pb-0">
        <div class="row g-2">
            @php($tiles = [
                ['Collected', currency($totals['collected']), 'text-success'],
                ['Successful top-ups', (int) $totals['payments'], 'text-body'],
                ['Refunded', currency($totals['refunded']), 'text-danger'],
                ['Pending', (int) $totals['pending'], 'text-warning'],
                ['Failed', (int) $totals['failed'], 'text-muted'],
                ['Needs review', (int) $totals['review'], 'text-danger'],
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
            "Collected" counts only payments the gateway confirmed — those are the ones on the students' cards and in the books.
            A payment left pending can be checked with its gateway (QPay for debit cards, MPGS for credit cards) from its row.
        </p>
    </div>

    <div class="card-body p-0 mt-3">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle border-bottom mb-0">
                <thead class="bg-light text-muted small">
                    <tr>
                        <th class="ps-3"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="qpay_transactions.id" label="Date" /></th>
                        <th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="accounts.name" label="Student" /></th>
                        <th>Parent</th>
                        <th>PUN / confirmation</th>
                        <th>Card</th>
                        <th class="text-end"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="qpay_transactions.amount" label="Amount" /></th>
                        <th><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="qpay_transactions.status" label="Status" /></th>
                        <th>Gateway response</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr wire:key="qpay-{{ $row->id }}">
                            <td class="ps-3 text-nowrap">{{ systemDateTime($row->created_at) }}</td>
                            <td class="text-nowrap">
                                <a href="{{ route('student::view', $row->account_id) }}" class="text-decoration-none fw-medium">{{ $row->student_name }}</a>
                                <div class="small text-muted">{{ $row->admission_no }}{{ $row->grade ? ' · ' . trim(implode(' - ', array_filter([$row->grade, $row->section]))) : '' }}</div>
                            </td>
                            <td class="small text-nowrap">
                                {{ $row->guardian_name ?: '-' }}
                                @if ($row->guardian_mobile)
                                    <div class="text-muted">{{ $row->guardian_mobile }}</div>
                                @endif
                            </td>
                            <td class="small font-monospace">
                                {{ $row->pun }}
                                @if ($row->confirmation_id)
                                    <div class="text-muted">{{ $row->confirmation_id }}</div>
                                @endif
                            </td>
                            <td class="small text-nowrap">
                                <span class="badge {{ $row->isCreditCard() ? 'bg-primary-subtle text-primary-emphasis' : 'bg-info-subtle text-info-emphasis' }}">
                                    <i class="fa {{ $row->isCreditCard() ? 'fa-credit-card' : 'fa-globe' }} me-1"></i>{{ $row->methodLabel() }}
                                </span>
                                <div class="font-monospace text-muted">{{ $row->cardLabel() ?: '-' }}</div>
                            </td>
                            <td class="text-end fw-semibold text-nowrap {{ $row->type === 'refund' ? 'text-danger' : '' }}">
                                {{ $row->type === 'refund' ? '-' : '' }}{{ currency($row->amount) }}
                            </td>
                            <td>
                                <span @class([
                                    'badge',
                                    'bg-success' => $row->status === 'success',
                                    'bg-danger' => $row->status === 'failed',
                                    'bg-warning text-dark' => in_array($row->status, ['pending', 'refund_pending', 'review']),
                                    'bg-secondary' => in_array($row->status, ['refunded', 'unresolved', 'cancelled']),
                                ])>{{ $row->statusLabel() }}</span>
                                @if ($row->tampered_at)
                                    <span class="badge bg-danger" title="The response failed the secure hash check and was verified by inquiry">Tampered</span>
                                @endif
                            </td>
                            <td class="small text-muted">
                                {{ $row->gateway_status }} {{ $row->gateway_status_message }}
                                @if ($row->failure_reason)
                                    <div class="text-danger">{{ $row->failure_reason }}</div>
                                @endif
                            </td>
                            <td class="text-end pe-3">
                                {{-- Released payments are still asked about, so they keep the Check button. --}}
                                @if ($row->awaitsResult())
                                    <button type="button" class="btn btn-sm btn-light text-nowrap" wire:click="inquire({{ $row->id }})" wire:loading.attr="disabled" title="Ask {{ $row->gatewayLabel() }} for the result">
                                        <i class="fa fa-refresh"></i> Check
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">No online recharges match these filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3 border-top">{{ $rows->links() }}</div>
    </div>
</div>
