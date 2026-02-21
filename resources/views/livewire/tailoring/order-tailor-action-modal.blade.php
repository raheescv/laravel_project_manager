<div>
    <div class="modal" id="TailoringOrderTailorActionsModal" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-0 order-tailor-modal-header">
                    <div>
                        <h5 class="mb-1">Order Action</h5>
                        <small class="text-muted d-block">
                            <i class="fa fa-file-text-o me-1 text-primary"></i>Order: {{ $selectedTailorOrderDetails['order_no'] ?? '-' }}
                            | <i class="fa fa-calendar me-1 text-info"></i>Date: {{ !empty($selectedTailorOrderDetails['order_date']) ? systemDate($selectedTailorOrderDetails['order_date']) : '-' }}
                            | <i class="fa fa-user me-1 text-success"></i>Customer: {{ $selectedTailorOrderDetails['customer_name'] ?? '-' }}
                            @if (!empty($selectedTailorOrderDetails['customer_mobile']))
                                | <i class="fa fa-phone me-1 text-warning"></i>Mobile: {{ $selectedTailorOrderDetails['customer_mobile'] }}
                            @endif
                        </small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-2">
                    @php
                        $assignmentCollection = collect($selectedTailorAssignments ?? []);
                        $pendingCount = $assignmentCollection->where('status', 'pending')->count();
                        $completedCount = $assignmentCollection->where('status', 'completed')->count();
                        $deliveredCount = $assignmentCollection->where('status', 'delivered')->count();
                    @endphp
                    <div class="row g-2 mb-3">
                        <div class="col-md-3 col-6">
                            <div class="order-tailor-stat-card stat-card-total">
                                <div class="stat-head">
                                    <span class="stat-icon"><i class="fa fa-list-ul"></i></span>
                                    <small class="text-muted">Total Assignments</small>
                                </div>
                                <div class="stat-value">{{ $assignmentCollection->count() }}</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="order-tailor-stat-card stat-card-pending">
                                <div class="stat-head">
                                    <span class="stat-icon"><i class="fa fa-clock-o"></i></span>
                                    <small class="text-muted">Pending</small>
                                </div>
                                <div class="stat-value">{{ $pendingCount }}</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="order-tailor-stat-card stat-card-completed">
                                <div class="stat-head">
                                    <span class="stat-icon"><i class="fa fa-check-circle"></i></span>
                                    <small class="text-muted">Completed</small>
                                </div>
                                <div class="stat-value">{{ $completedCount }}</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="order-tailor-stat-card stat-card-delivered">
                                <div class="stat-head">
                                    <span class="stat-icon"><i class="fa fa-truck"></i></span>
                                    <small class="text-muted">Delivered</small>
                                </div>
                                <div class="stat-value">{{ $deliveredCount }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="status-selection-guide mb-3">
                        <div class="guide-title">
                            <i class="fa fa-info-circle me-1"></i>Status Selection Guide
                        </div>
                        <div class="guide-items">
                            <span class="guide-chip guide-pending"><i class="fa fa-clock-o me-1"></i>Pending: Initial status only (cannot be selected here)</span>
                            <span class="guide-chip guide-completed"><i class="fa fa-check-circle me-1"></i>Completed: Stitching finished</span>
                            <span class="guide-chip guide-delivered"><i class="fa fa-truck me-1"></i>Delivered: Handed to customer</span>
                        </div>
                    </div>

                    @php
                        $tailorWiseSummary = collect($selectedTailorAssignments ?? [])
                            ->groupBy(fn ($row) => trim((string) ($row['tailor_name'] ?? '')) !== '' ? $row['tailor_name'] : 'Unassigned')
                            ->map(function ($rows, $tailorName) {
                                $validRatings = $rows->pluck('rating')->filter(fn ($rating) => !is_null($rating));

                                return [
                                    'tailor_name' => $tailorName,
                                    'assignment_count' => $rows->count(),
                                    'pending_count' => $rows->where('status', 'pending')->count(),
                                    'completed_count' => $rows->where('status', 'completed')->count(),
                                    'delivered_count' => $rows->where('status', 'delivered')->count(),
                                    'avg_rating' => $validRatings->count() > 0 ? round($validRatings->avg(), 2) : null,
                                    'total_commission' => (float) $rows->sum(fn ($row) => (float) ($row['tailor_commission'] ?? 0)),
                                ];
                            })
                            ->sortByDesc('total_commission')
                            ->values();
                    @endphp
                    <div class="tailor-summary-card mb-3">
                        <div class="tailor-summary-title">
                            <span><i class="fa fa-bar-chart me-1"></i> Tailor-wise Summary Report</span>
                            <span class="tailor-summary-subtitle">Performance and payout overview by tailor</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0 tailor-summary-table">
                                <thead>
                                    <tr>
                                        <th><i class="fa fa-user me-1"></i>Tailor</th>
                                        <th class="text-center"><i class="fa fa-list-ol me-1"></i>Assignments</th>
                                        <th class="text-center"><i class="fa fa-clock-o me-1"></i>Pending</th>
                                        <th class="text-center"><i class="fa fa-check-circle me-1"></i>Completed</th>
                                        <th class="text-center"><i class="fa fa-truck me-1"></i>Delivered</th>
                                        <th class="text-center"><i class="fa fa-star me-1"></i>Average Rating</th>
                                        <th class="text-end"><i class="fa fa-money me-1"></i>Total Commission</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($tailorWiseSummary as $summary)
                                        <tr>
                                            <td class="fw-semibold">{{ $summary['tailor_name'] }}</td>
                                            <td class="text-center"><span class="summary-pill">{{ $summary['assignment_count'] }}</span></td>
                                            <td class="text-center"><span class="badge bg-warning bg-opacity-10 text-warning">{{ $summary['pending_count'] }}</span></td>
                                            <td class="text-center"><span class="badge bg-success bg-opacity-10 text-success">{{ $summary['completed_count'] + $summary['delivered_count'] }}</span></td>
                                            <td class="text-center"><span class="badge bg-dark bg-opacity-10 text-dark">{{ $summary['delivered_count'] }}</span></td>
                                            <td class="text-center">
                                                @if (!is_null($summary['avg_rating']))
                                                    <span class="badge bg-warning bg-opacity-10 text-warning">{{ number_format((float) $summary['avg_rating'], 2) }}/5</span>
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                            <td class="text-end fw-bold text-primary">{{ currency($summary['total_commission']) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-3">No tailor summary data.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm align-middle order-tailor-table mb-0">
                            <thead>
                                <tr>
                                    <th><i class="fa fa-hashtag me-1"></i>#</th>
                                    <th><i class="fa fa-user-circle me-1"></i>Tailor</th>
                                    <th class="text-end"><i class="fa fa-money me-1"></i>Commission</th>
                                    <th><i class="fa fa-calendar-check-o me-1"></i>Completion Date</th>
                                    <th class="text-center"><i class="fa fa-star me-1"></i>Rating</th>
                                    <th><i class="fa fa-flag me-1"></i>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $groupedAssignments = collect($selectedTailorAssignments ?? [])->groupBy('item_id');
                                @endphp
                                @forelse ($groupedAssignments as $itemAssignments)
                                    @php
                                        $first = $itemAssignments->first();
                                    @endphp
                                    <tr class="table-light">
                                        <td colspan="6" class="fw-semibold py-2">
                                            <i class="fa fa-tag me-1 text-primary"></i>Item #{{ $first['item_no'] ?? '-' }} - {{ $first['product_name'] ?? '-' }}
                                            <small class="text-muted ms-2">
                                                <i class="fa fa-balance-scale me-1"></i>Qty: {{ number_format((float) ($first['quantity'] ?? 0), 3) }}
                                                | <i class="fa fa-check-circle-o me-1"></i>Completion: {{ ucWords($first['completion_status'] ?? 'not completed') }}
                                                | <i class="fa fa-truck me-1"></i>Delivery: {{ ucWords($first['delivery_status'] ?? 'not delivered') }}
                                            </small>
                                        </td>
                                    </tr>
                                    @foreach ($itemAssignments as $index => $row)
                                        <tr wire:key="tailor-assignment-row-{{ $row['assignment_id'] }}">
                                            <td class="fw-semibold">{{ $index + 1 }}</td>
                                            <td>{{ $row['tailor_name'] ?: '-' }}</td>
                                            <td class="text-end fw-semibold">{{ currency($row['tailor_commission']) }}</td>
                                            <td>{{ !empty($row['completion_date']) ? systemDate($row['completion_date']) : '-' }}</td>
                                            <td class="text-center">
                                                @php
                                                    $ratingValue = max(0, min(5, (int) ($row['rating'] ?? 0)));
                                                @endphp
                                                <div class="tailor-rating-box text-start">
                                                    <div class="tailor-rating-stars">
                                                        @for ($star = 1; $star <= 5; $star++)
                                                            <i class="fa fa-star {{ $star <= $ratingValue ? 'active' : '' }}"></i>
                                                        @endfor
                                                    </div>
                                                </div>
                                            </td>
                                            <td style="min-width: 170px;">
                                                @php
                                                    $isPendingAssignment = ($row['status'] ?? 'pending') === 'pending';
                                                @endphp
                                                <div class="tailor-status-radio-group {{ $isPendingAssignment ? 'opacity-50' : '' }}">
                                                    @foreach ($tailorStatusOptions as $statusKey => $statusLabel)
                                                        <label class="tailor-status-radio-option tailor-status-{{ str_replace(' ', '-', $statusKey) }}">
                                                            <input type="radio" name="tailor_status_{{ $row['assignment_id'] }}" value="{{ $statusKey }}"
                                                                wire:change="updateTailorAssignmentStatus({{ $row['assignment_id'] }}, '{{ $statusKey }}')" @checked(($row['status'] ?? 'pending') === $statusKey) @disabled($isPendingAssignment)>
                                                            <span class="status-pill-label">
                                                                <i class="fa {{ $statusKey === 'pending' ? 'fa-clock-o' : ($statusKey === 'completed' ? 'fa-check-circle' : 'fa-truck') }} me-1"></i>
                                                                {{ $statusLabel }}
                                                            </span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                                @if ($isPendingAssignment)
                                                    <small class="text-muted d-block mt-1">Pending item is locked in this screen.</small>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            No tailor assignments found for this order.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div wire:loading wire:target="updateTailorAssignmentStatus" class="small text-primary mt-2">
                        <i class="fa fa-spinner fa-spin me-1"></i> Updating tailor status...
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('livewire:init', function() {
                Livewire.on('toggle-order-tailor-actions-modal', function() {
                    $('#TailoringOrderTailorActionsModal').modal('show');
                });
            });
        </script>
    @endpush

    @push('styles')
        <style>
            .order-tailor-modal-header {
                background: linear-gradient(135deg, #f8fbff, #ffffff);
                border-bottom: 1px solid #e9eef5 !important;
            }

            .order-tailor-stat-card {
                border: 1px solid #e6edf7;
                border-radius: 0.8rem;
                padding: 0.7rem 0.8rem;
                box-shadow: 0 8px 18px rgba(15, 23, 42, 0.05);
            }

            .order-tailor-stat-card .stat-head {
                display: flex;
                align-items: center;
                gap: 0.45rem;
                margin-bottom: 0.35rem;
            }

            .order-tailor-stat-card .stat-icon {
                width: 1.45rem;
                height: 1.45rem;
                border-radius: 999px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-size: 0.74rem;
            }

            .order-tailor-stat-card .stat-value {
                font-size: 1.25rem;
                font-weight: 800;
                line-height: 1;
            }

            .stat-card-total {
                background: linear-gradient(145deg, #eef6ff, #ffffff);
                border-color: #cfe3ff;
            }

            .stat-card-total .stat-icon {
                color: #1d4ed8;
                background: #dbeafe;
            }

            .stat-card-total .stat-value {
                color: #1d4ed8;
            }

            .stat-card-pending {
                background: linear-gradient(145deg, #fff9ec, #ffffff);
                border-color: #ffe5b3;
            }

            .stat-card-pending .stat-icon {
                color: #b45309;
                background: #ffedd5;
            }

            .stat-card-pending .stat-value {
                color: #b45309;
            }

            .stat-card-completed {
                background: linear-gradient(145deg, #effdf4, #ffffff);
                border-color: #c5efd5;
            }

            .stat-card-completed .stat-icon {
                color: #15803d;
                background: #dcfce7;
            }

            .stat-card-completed .stat-value {
                color: #15803d;
            }

            .stat-card-delivered {
                background: linear-gradient(145deg, #f1f5f9, #ffffff);
                border-color: #dbe3ec;
            }

            .stat-card-delivered .stat-icon {
                color: #334155;
                background: #e2e8f0;
            }

            .stat-card-delivered .stat-value {
                color: #0f172a;
            }

            .order-tailor-table thead th {
                background: #f8fbff;
                color: #334155;
                font-size: 0.78rem;
                border-bottom: 1px solid #e6eef6;
                white-space: nowrap;
            }

            .order-tailor-table tbody td {
                padding-top: 0.6rem;
                padding-bottom: 0.6rem;
                border-color: #eef3f8;
            }

            .status-selection-guide {
                border: 1px solid #d9e7f8;
                background: linear-gradient(135deg, #f7fbff, #ffffff);
                border-radius: 0.75rem;
                padding: 0.65rem 0.8rem;
            }

            .status-selection-guide .guide-title {
                font-size: 0.82rem;
                font-weight: 700;
                color: #1e3a5f;
                margin-bottom: 0.45rem;
            }

            .status-selection-guide .guide-items {
                display: flex;
                flex-wrap: wrap;
                gap: 0.45rem;
            }

            .status-selection-guide .guide-chip {
                display: inline-flex;
                align-items: center;
                font-size: 0.73rem;
                font-weight: 600;
                padding: 0.28rem 0.6rem;
                border-radius: 999px;
                border: 1px solid transparent;
                white-space: nowrap;
            }

            .status-selection-guide .guide-pending {
                background: #fff7e8;
                color: #9a6700;
                border-color: #ffe3a4;
            }

            .status-selection-guide .guide-completed {
                background: #ecfdf3;
                color: #166534;
                border-color: #b7f0c5;
            }

            .status-selection-guide .guide-delivered {
                background: #f1f5f9;
                color: #0f172a;
                border-color: #cbd5e1;
            }

            .tailor-summary-card {
                border: 1px solid #d9e7f8;
                border-radius: 0.75rem;
                background: linear-gradient(135deg, #ffffff, #f8fbff);
                overflow: hidden;
            }

            .tailor-summary-title {
                font-size: 0.82rem;
                font-weight: 700;
                color: #1e3a5f;
                padding: 0.6rem 0.75rem;
                border-bottom: 1px solid #e4edf8;
                background: #f5f9ff;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.5rem;
                flex-wrap: wrap;
            }

            .tailor-summary-subtitle {
                font-size: 0.71rem;
                font-weight: 600;
                color: #64748b;
                background: #eaf2ff;
                border: 1px solid #d7e6ff;
                border-radius: 999px;
                padding: 0.2rem 0.5rem;
            }

            .tailor-summary-table thead th {
                font-size: 0.75rem;
                color: #475569;
                background: #f8fbff;
                border-bottom: 1px solid #e8eef7;
                white-space: nowrap;
            }

            .tailor-summary-table tbody td {
                border-color: #eef3f8;
                font-size: 0.84rem;
            }

            .tailor-summary-table tbody tr:nth-child(even) {
                background: #fbfdff;
            }

            .tailor-summary-table tbody tr:hover {
                background: #f4f9ff;
            }

            .summary-pill {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-width: 1.75rem;
                height: 1.45rem;
                padding: 0 0.45rem;
                border-radius: 999px;
                border: 1px solid #d8e5f7;
                background: #edf4ff;
                color: #1d4ed8;
                font-weight: 700;
                font-size: 0.75rem;
            }

            .tailor-status-radio-group {
                display: flex;
                flex-wrap: wrap;
                gap: 0.4rem;
                padding: 0.3rem;
                border: 1px solid #e3ebf5;
                border-radius: 0.8rem;
                background: linear-gradient(135deg, #f8fbff, #ffffff);
                width: 100%;
            }

            .tailor-status-radio-option {
                position: relative;
                display: block;
                flex: 1 1 0;
                margin: 0;
                cursor: pointer;
                min-width: 0;
            }

            .tailor-status-radio-option .status-pill-label {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 100%;
                padding: 0.3rem 0.7rem;
                border: 1px solid #dbe7f4;
                border-radius: 999px;
                background: #fff;
                font-size: 0.73rem;
                font-weight: 600;
                transition: all 0.16s ease;
                box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
                user-select: none;
                letter-spacing: 0.1px;
                white-space: nowrap;
            }

            .tailor-status-radio-option input[type='radio'] {
                position: absolute;
                opacity: 0;
                pointer-events: none;
            }

            .tailor-status-pending .status-pill-label {
                color: #9a6700;
                border-color: #ffe3a4;
                background: #fffaf0;
            }

            .tailor-status-completed .status-pill-label {
                color: #166534;
                border-color: #b7f0c5;
                background: #f0fdf4;
            }

            .tailor-status-delivered .status-pill-label {
                color: #0f172a;
                border-color: #cbd5e1;
                background: #f8fafc;
            }

            .tailor-status-radio-option input[type='radio']:checked+.status-pill-label {
                transform: translateY(-1px);
                box-shadow: 0 7px 14px rgba(15, 23, 42, 0.12);
                animation: statusPulse 0.28s ease;
            }

            .tailor-status-pending input[type='radio']:checked+.status-pill-label {
                border-color: #f59e0b;
                background: linear-gradient(180deg, #ffe8b8, #ffd685);
                color: #7c2d12;
                box-shadow: 0 8px 16px rgba(245, 158, 11, 0.24);
            }

            .tailor-status-completed input[type='radio']:checked+.status-pill-label {
                border-color: #22c55e;
                background: linear-gradient(180deg, #bbf7d0, #86efac);
                color: #14532d;
                box-shadow: 0 8px 16px rgba(34, 197, 94, 0.22);
            }

            .tailor-status-delivered input[type='radio']:checked+.status-pill-label {
                border-color: #334155;
                background: linear-gradient(180deg, #cbd5e1, #94a3b8);
                color: #020617;
                box-shadow: 0 8px 16px rgba(51, 65, 85, 0.24);
            }

            .tailor-status-radio-option .status-pill-label:hover {
                filter: brightness(0.98);
                transform: translateY(-1px);
            }

            @keyframes statusPulse {
                0% {
                    transform: scale(0.96);
                }

                100% {
                    transform: scale(1);
                }
            }

            .tailor-rating-box {
                min-width: 150px;
            }

            .tailor-rating-title {
                color: #334155;
                font-weight: 700;
                font-size: 0.8rem;
                margin-bottom: 0.35rem;
            }

            .tailor-rating-title .fa-star {
                color: #f59e0b;
            }

            .tailor-rating-stars {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.45rem;
                padding: 0.38rem 0.62rem;
                border: 1px solid #d6e0ec;
                border-radius: 0.95rem;
                background: linear-gradient(180deg, #eef2f7, #e8edf4);
            }

            .tailor-rating-stars .fa-star {
                color: #cbd5e1;
                font-size: 1rem;
                transition: color 0.16s ease;
            }

            .tailor-rating-stars .fa-star.active {
                color: #f59e0b;
            }
        </style>
    @endpush
</div>
