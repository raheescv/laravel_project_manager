@once
    @push('styles')
        <style>
        /* ── Vendor hero ── */
        .pv-hero {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.6rem 0.85rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            background: #fff;
            margin-bottom: 0.5rem;
        }
        .pv-hero-left  { display: flex; align-items: center; gap: 0.6rem; min-width: 0; }
        .pv-hero-icon  {
            flex-shrink: 0;
            width: 34px; height: 34px;
            border-radius: 50%;
            background: #1e3a8a;
            color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.85rem;
        }
        .pv-hero-name  { font-size: 0.95rem; font-weight: 700; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .pv-hero-meta  { display: flex; flex-wrap: wrap; gap: 0.3rem; margin-top: 0.15rem; }
        .pv-hero-tag   {
            display: inline-flex; align-items: center; gap: 0.25rem;
            font-size: 0.7rem; color: #475569;
            background: #f1f5f9; border-radius: 0.25rem;
            padding: 0.1rem 0.45rem;
        }
        .pv-hero-stats { display: flex; gap: 0.4rem; flex-shrink: 0; }
        .pv-stat {
            text-align: center; min-width: 80px;
            padding: 0.3rem 0.6rem;
            border-radius: 0.35rem;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
        }
        .pv-stat-lbl { font-size: 0.65rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.04em; }
        .pv-stat-val { font-size: 0.85rem; font-weight: 700; line-height: 1.2; }

        /* ── Tabs ── */
        .pv-wrap .nav-underline .nav-link { padding: 0.4rem 0.75rem; font-size: 0.8rem; }

        /* ── Statement toolbar ── */
        .pv-toolbar {
            display: flex; align-items: flex-end; gap: 0.5rem; flex-wrap: wrap;
            padding: 0.6rem 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            background: #f8fafc;
            margin-bottom: 0.5rem;
        }
        .pv-toolbar .pv-field { display: flex; flex-direction: column; gap: 0.15rem; }
        .pv-toolbar .pv-field label { font-size: 0.68rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; }
        .pv-toolbar .form-control,
        .pv-toolbar .form-select { font-size: 0.78rem; padding: 0.28rem 0.5rem; height: auto; }
        .pv-toolbar .btn { font-size: 0.78rem; padding: 0.28rem 0.7rem; white-space: nowrap; }

        /* ── Summary strip ── */
        .pv-summary {
            display: flex; gap: 0.4rem;
            padding: 0.55rem 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            background: #f8fafc;
            margin-bottom: 0.5rem;
            flex-wrap: wrap;
        }
        .pv-metric {
            flex: 1; min-width: 110px;
            padding: 0.4rem 0.6rem;
            border-radius: 0.4rem;
            border: 1px solid #e2e8f0;
            background: #fff;
        }
        .pv-metric.is-primary { background: #1e3a8a; border-color: #1e3a8a; }
        .pv-metric-lbl { font-size: 0.65rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: #94a3b8; }
        .pv-metric.is-primary .pv-metric-lbl { color: rgba(255,255,255,0.65); }
        .pv-metric-val { font-size: 0.92rem; font-weight: 700; color: #0f172a; margin-top: 0.1rem; }
        .pv-metric.is-primary .pv-metric-val { color: #fff; }

        /* Opening split row */
        .pv-opening {
            display: flex; align-items: center; gap: 1.5rem; flex-wrap: wrap;
            padding: 0.4rem 0.75rem;
            border-left: 3px solid #38bdf8;
            background: #f0f9ff;
            border-radius: 0.25rem;
            margin-bottom: 0.5rem;
            font-size: 0.78rem;
        }
        .pv-opening .lbl { color: #64748b; }
        .pv-opening .val { font-weight: 700; }

        /* ── Table card ── */
        .pv-table-card {
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            overflow: hidden;
            background: #fff;
        }
        .pv-table-head {
            display: flex; align-items: center; justify-content: space-between;
            padding: 0.45rem 0.75rem;
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
        }
        .pv-table-head .pv-th-title { font-size: 0.8rem; font-weight: 700; color: #0f172a; }
        .pv-table-head .pv-count {
            font-size: 0.7rem; font-weight: 700; color: #2563eb;
            background: #eff6ff; border-radius: 999px;
            padding: 0.15rem 0.6rem;
        }

        .pv-wrap .pv-stmt-table { --bs-table-bg: transparent; --bs-table-striped-bg: #f8fafc; margin-bottom: 0; }
        .pv-wrap .pv-stmt-table thead th {
            padding: 0.45rem 0.5rem;
            font-size: 0.72rem; font-weight: 700; letter-spacing: 0.03em; text-transform: uppercase;
            background: #1e3a8a; color: #fff; border: none; white-space: nowrap;
        }
        .pv-wrap .pv-stmt-table tbody td,
        .pv-wrap .pv-stmt-table tfoot td {
            padding: 0.35rem 0.5rem;
            font-size: 0.78rem;
            border-color: #f1f5f9;
            vertical-align: middle;
        }
        .pv-wrap .pv-stmt-table tfoot td { font-weight: 700; background: #f1f5f9; }
        .pv-wrap .pv-stmt-table tfoot tr:last-child td { background: #e0eaff; }

        @media (max-width: 767px) {
            .pv-hero { flex-direction: column; align-items: flex-start; }
            .pv-hero-stats { flex-wrap: wrap; }
            .pv-toolbar { flex-direction: column; }
            .pv-summary { flex-direction: column; }
        }
        </style>
    @endpush
@endonce

<div class="pv-wrap purchase-vendor-view">

    {{-- ── Vendor Hero ── --}}
    <div class="pv-hero">
        <div class="pv-hero-left">
            <div class="pv-hero-icon"><i class="fa fa-building"></i></div>
            <div>
                <div class="pv-hero-name">{{ $vendor['name'] ?? 'Vendor' }}</div>
                <div class="pv-hero-meta">
                    <span class="pv-hero-tag"><i class="fa fa-hashtag"></i> #{{ $vendor['id'] ?? '' }}</span>
                    @if ($vendor['mobile'] ?? null)
                        <span class="pv-hero-tag"><i class="fa fa-phone"></i> {{ $vendor['mobile'] }}</span>
                    @endif
                    @if ($vendor['email'] ?? null)
                        <span class="pv-hero-tag"><i class="fa fa-envelope-o"></i> {{ $vendor['email'] }}</span>
                    @endif
                    @if ($vendor['place'] ?? null)
                        <span class="pv-hero-tag"><i class="fa fa-map-marker"></i> {{ $vendor['place'] }}</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="pv-hero-stats">
            <div class="pv-stat">
                <div class="pv-stat-lbl">Total Purchase</div>
                <div class="pv-stat-val text-primary">{{ currency($total_purchases?->grand_total ?? 0) }}</div>
            </div>
            <div class="pv-stat">
                <div class="pv-stat-lbl">Total Paid</div>
                <div class="pv-stat-val text-success">{{ currency($total_purchases?->paid ?? 0) }}</div>
            </div>
            <div class="pv-stat">
                <div class="pv-stat-lbl">Balance</div>
                <div class="pv-stat-val text-danger">{{ currency($total_purchases?->balance ?? 0) }}</div>
            </div>
        </div>
    </div>

    {{-- ── Tabs ── --}}
    <div class="tab-base">
        <ul class="nav nav-underline nav-component border-bottom flex-nowrap overflow-x-auto" role="tablist" style="scrollbar-width: thin;">
            @foreach (['Statement', 'Payment', 'LPO', 'GRN', 'LPO Purchase'] as $tab)
                <li class="nav-item flex-shrink-0" role="presentation">
                    <button class="nav-link px-2 px-md-3 @if ($selected_tab === $tab) active @endif"
                        data-bs-toggle="tab" data-bs-target="#tab-{{ Str::slug($tab) }}"
                        type="button" role="tab"
                        wire:click="$set('selected_tab', '{{ $tab }}')">
                        {{ $tab }}
                    </button>
                </li>
            @endforeach
        </ul>

        <div class="tab-content">

            {{-- ── Statement Tab ── --}}
            <div id="tab-statement" class="tab-pane fade @if ($selected_tab === 'Statement') active show @endif" role="tabpanel">
                <div class="p-2">

                    {{-- Loading indicator --}}
                    <div wire:loading.flex wire:target="statement_from_date,statement_to_date,statement_limit"
                        class="align-items-center gap-2 mb-2 text-primary" style="font-size:0.78rem;">
                        <span class="spinner-border spinner-border-sm"></span>
                        <span>Refreshing…</span>
                    </div>

                    {{-- Toolbar --}}
                    <div class="pv-toolbar">
                        <div class="pv-field">
                            <label>From Date</label>
                            <input type="date" wire:model.live="statement_from_date" class="form-control">
                        </div>
                        <div class="pv-field">
                            <label>To Date</label>
                            <input type="date" wire:model.live="statement_to_date" class="form-control">
                        </div>
                        <div class="pv-field">
                            <label>Rows</label>
                            <select wire:model.live="statement_limit" class="form-select">
                                <option value="20">20</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                                <option value="500">500</option>
                            </select>
                        </div>
                        <a href="{{ route('print::purchase_vendor::statement', ['id' => $vendor_id, 'fromDate' => $statement_from_date, 'toDate' => $statement_to_date]) }}"
                            target="_blank" rel="noopener"
                            class="btn btn-danger d-inline-flex align-items-center gap-1 ms-auto">
                            <i class="fa fa-file-pdf-o"></i> Statement PDF
                        </a>
                    </div>

                    {{-- Summary ── --}}
                    <div class="pv-summary">
                        <div class="pv-metric">
                            <div class="pv-metric-lbl">Opening Balance</div>
                            <div class="pv-metric-val">{{ $statementSummary['opening_balance_label'] ?? currency(0) }}</div>
                        </div>
                        <div class="pv-metric">
                            <div class="pv-metric-lbl">Period Debit</div>
                            <div class="pv-metric-val text-danger">{{ currency($statementSummary['period_debit'] ?? 0) }}</div>
                        </div>
                        <div class="pv-metric">
                            <div class="pv-metric-lbl">Period Credit</div>
                            <div class="pv-metric-val text-success">{{ currency($statementSummary['period_credit'] ?? 0) }}</div>
                        </div>
                        <div class="pv-metric is-primary">
                            <div class="pv-metric-lbl">Closing Balance</div>
                            <div class="pv-metric-val">{{ $statementSummary['closing_balance_label'] ?? currency(0) }}</div>
                        </div>
                    </div>

                    {{-- Opening split (only when non-zero) --}}
                    @if (($statementSummary['opening_debit'] ?? 0) > 0 || ($statementSummary['opening_credit'] ?? 0) > 0)
                        <div class="pv-opening">
                            <span class="fw-semibold text-dark" style="font-size:0.78rem;">Opening Split</span>
                            <span><span class="lbl">Debit </span><span class="val text-danger">{{ currency($statementSummary['opening_debit'] ?? 0) }}</span></span>
                            <span><span class="lbl">Credit </span><span class="val text-success">{{ currency($statementSummary['opening_credit'] ?? 0) }}</span></span>
                            <span><span class="lbl">Net </span><span class="val">{{ $statementSummary['opening_balance_label'] ?? currency(0) }}</span></span>
                        </div>
                    @endif

                    {{-- Table ── --}}
                    <div class="pv-table-card">
                        <div class="pv-table-head">
                            <span class="pv-th-title">Statement Entries</span>
                            <span class="pv-count">
                                {{ $statementSummary['displayed_count'] ?? 0 }} / {{ $statementSummary['entry_count'] ?? 0 }} entries
                            </span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover table-sm align-middle pv-stmt-table">
                                <thead>
                                    <tr>
                                        <th class="text-nowrap">Date</th>
                                        <th>Particulars</th>
                                        <th>Description</th>
                                        <th class="text-nowrap">Reference</th>
                                        <th>Remarks</th>
                                        <th class="text-center text-nowrap">Voucher</th>
                                        <th class="text-end text-nowrap">Debit</th>
                                        <th class="text-end text-nowrap">Credit</th>
                                        <th class="text-end text-nowrap">Balance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($statementRows as $entry)
                                        <tr>
                                            <td class="text-nowrap fw-semibold">{{ systemDate($entry['date']) }}</td>
                                            <td class="fw-medium">{{ $entry['particulars'] ?: '-' }}</td>
                                            <td>{{ $entry['description'] ?: '-' }}</td>
                                            <td class="text-nowrap">
                                                @if ($entry['reference_number'])
                                                    <code class="bg-light px-1 rounded text-dark" style="font-size:0.72rem;">{{ $entry['reference_number'] }}</code>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>{{ $entry['remarks'] ?: '-' }}</td>
                                            <td class="text-center">
                                                @if (! empty($entry['can_view_payment_voucher']))
                                                    <a href="{{ route('print::purchase_vendor::payment-voucher', ['vendorId' => $vendor_id, 'journalId' => $entry['journal_id']]) }}"
                                                        target="_blank" rel="noopener"
                                                        class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size:0.7rem;">
                                                        <i class="fa fa-file-pdf-o"></i> Voucher
                                                    </a>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                @if (($entry['debit'] ?? 0) > 0)
                                                    <span class="text-danger fw-semibold">{{ currency($entry['debit']) }}</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                @if (($entry['credit'] ?? 0) > 0)
                                                    <span class="text-success fw-semibold">{{ currency($entry['credit']) }}</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td class="text-end fw-semibold">{{ $entry['balance_label'] ?? currency(0) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center text-muted py-4" style="font-size:0.8rem;">
                                                No statement entries found for the selected period.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="6" class="text-end">Period Total</td>
                                        <td class="text-end text-danger">{{ currency($statementSummary['total_debit'] ?? 0) }}</td>
                                        <td class="text-end text-success">{{ currency($statementSummary['total_credit'] ?? 0) }}</td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td colspan="8" class="text-end">Closing Balance</td>
                                        <td class="text-end">{{ $statementSummary['closing_balance_label'] ?? currency(0) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        @if (($statementSummary['entry_count'] ?? 0) > ($statementSummary['displayed_count'] ?? 0))
                            <div class="px-3 py-2 border-top text-muted" style="font-size:0.72rem;">
                                Showing {{ $statementSummary['displayed_count'] }} of {{ $statementSummary['entry_count'] }} entries. Increase rows to load more.
                            </div>
                        @endif
                    </div>

                </div>
            </div>

            {{-- ── Payment Tab ── --}}
            <div id="tab-payment" class="tab-pane fade @if ($selected_tab === 'Payment') active show @endif" role="tabpanel">
                <div class="p-2">
                    @livewire('purchase.vendor-payment', ['name' => $vendor['name'] ?? '', 'vendor_id' => $vendor_id])
                </div>
            </div>

            {{-- ── LPO Tab ── --}}
            <div id="tab-lpo" class="tab-pane fade @if ($selected_tab === 'LPO') active show @endif" role="tabpanel">
                <div class="p-2">
                    <div class="mb-2">
                        <label class="form-label small text-muted mb-1">Limit</label>
                        <select wire:model.live="lpo_limit" class="form-select form-select-sm" style="width:100px;">
                            <option value="20">20</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-sm align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Date</th>
                                    <th class="text-end">Total</th>
                                    <th>Status</th>
                                    <th>Created By</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($lpos as $lpo)
                                    <tr>
                                        <td>#{{ $lpo->id }}</td>
                                        <td class="text-nowrap">{{ $lpo->date }}</td>
                                        <td class="text-end fw-medium">{{ currency($lpo->total) }}</td>
                                        <td>
                                            <span class="badge bg-{{ $lpo->status->value === 'approved' ? 'success' : ($lpo->status->value === 'rejected' ? 'danger' : 'warning') }}">
                                                {{ ucfirst($lpo->status->value) }}
                                            </span>
                                        </td>
                                        <td>{{ $lpo->creator?->name ?? '-' }}</td>
                                        <td class="text-end">
                                            <a href="{{ route('lpo::view', $lpo->id) }}" class="btn btn-sm btn-outline-primary py-0 px-2">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted py-3">No LPOs found</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- ── GRN Tab ── --}}
            <div id="tab-grn" class="tab-pane fade @if ($selected_tab === 'GRN') active show @endif" role="tabpanel">
                <div class="p-2">
                    <div class="mb-2">
                        <label class="form-label small text-muted mb-1">Limit</label>
                        <select wire:model.live="grn_limit" class="form-select form-select-sm" style="width:100px;">
                            <option value="20">20</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-sm align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>GRN No</th>
                                    <th>Date</th>
                                    <th>LPO</th>
                                    <th>Status</th>
                                    <th>Created By</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($grns as $grn)
                                    <tr>
                                        <td>{{ $grn->grn_no }}</td>
                                        <td class="text-nowrap">{{ $grn->date }}</td>
                                        <td>
                                            @if ($grn->localPurchaseOrder)
                                                <a href="{{ route('lpo::view', $grn->local_purchase_order_id) }}" class="text-primary">#{{ $grn->local_purchase_order_id }}</a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $grn->status->value === 'accepted' ? 'success' : ($grn->status->value === 'rejected' ? 'danger' : 'warning') }}">
                                                {{ ucfirst($grn->status->value) }}
                                            </span>
                                        </td>
                                        <td>{{ $grn->creator?->name ?? '-' }}</td>
                                        <td class="text-end">
                                            <a href="{{ route('grn::view', $grn->id) }}" class="btn btn-sm btn-outline-primary py-0 px-2">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted py-3">No GRNs found</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- ── LPO Purchase Tab ── --}}
            <div id="tab-lpo-purchase" class="tab-pane fade @if ($selected_tab === 'LPO Purchase') active show @endif" role="tabpanel">
                <div class="p-2">
                    <div class="mb-2">
                        <label class="form-label small text-muted mb-1">Limit</label>
                        <select wire:model.live="lpo_purchase_limit" class="form-select form-select-sm" style="width:100px;">
                            <option value="20">20</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-sm align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Invoice No</th>
                                    <th>Date</th>
                                    <th>LPO</th>
                                    <th class="text-end">Grand Total</th>
                                    <th class="text-end">Paid</th>
                                    <th class="text-end">Balance</th>
                                    <th>Status</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($lpo_purchases as $purchase)
                                    <tr>
                                        <td>#{{ $purchase->id }}</td>
                                        <td>
                                            <a href="{{ route('lpo-purchase::view', $purchase->id) }}" class="text-primary fw-semibold">
                                                {{ $purchase->invoice_no }}
                                            </a>
                                        </td>
                                        <td class="text-nowrap">{{ $purchase->date }}</td>
                                        <td>
                                            @if ($purchase->localPurchaseOrder)
                                                <a href="{{ route('lpo::view', $purchase->local_purchase_order_id) }}" class="text-primary">#{{ $purchase->local_purchase_order_id }}</a>
                                            @endif
                                        </td>
                                        <td class="text-end fw-bold text-primary">{{ currency($purchase->grand_total) }}</td>
                                        <td class="text-end text-success fw-semibold">{{ currency($purchase->paid) }}</td>
                                        <td class="text-end text-danger fw-semibold">{{ currency($purchase->balance) }}</td>
                                        <td>
                                            <span class="badge bg-{{ $purchase->status === 'completed' ? 'success' : ($purchase->status === 'cancelled' ? 'danger' : 'warning') }}">
                                                {{ ucfirst($purchase->status) }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('lpo-purchase::view', $purchase->id) }}" class="btn btn-sm btn-outline-primary py-0 px-2">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="9" class="text-center text-muted py-3">No LPO purchases found</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
