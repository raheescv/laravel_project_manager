@php
    use Carbon\Carbon;
    use App\Enums\LocalPurchaseOrder\LocalPurchaseOrderStatus;

    $orderDate   = $order->date ? Carbon::parse($order->date) : now();
    $refNo       = 'BASB-LPO-'.$orderDate->format('m-y').'-'.str_pad($order->id, 3, '0', STR_PAD_LEFT);

    $subtotal    = $order->items->sum(fn ($i) => $i->quantity * $i->rate);
    $grandTotal  = $order->total ?? $subtotal;
    $discount    = max(0, $subtotal - $grandTotal);

    $statusColor = match ($order->status) {
        LocalPurchaseOrderStatus::APPROVED, LocalPurchaseOrderStatus::CONFIRMED => '#9A5B1E',
        LocalPurchaseOrderStatus::PENDING => '#B7791F',
        LocalPurchaseOrderStatus::REJECTED => '#C0392B',
        default => '#9A5B1E',
    };
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>LPO #{{ str_pad($order->id, 3, '0', STR_PAD_LEFT) }}</title>
    <style>
        :root {
            --accent:        #9A5B1E;
            --accent-dark:   #784515;
            --accent-deep:   #5a330f;
            --accent-tint:   #F6EEE3;
            --accent-tint-2: #FBF6EF;
            --gold:          #8a6a12;
            --ink:           #111827;
            --muted:         #5b6675;
            --line:          #dfe4ea;
            --line-soft:     #eef1f5;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Arial', 'DejaVu Sans', sans-serif;
        }

        html, body { background: #fff; }

        body {
            color: var(--ink);
            font-size: 11px;
            line-height: 1.45;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .lpo {
            width: 100%;
            max-width: 190mm;
            margin: 0 auto;
        }

        /* ── Masthead ─────────────────────────────────────── */
        .lpo-masthead {
            display: flex;
            align-items: center;
            gap: 16px;
            padding-bottom: 14px;
            border-bottom: 3px solid var(--accent);
            position: relative;
        }
        .lpo-masthead::after {
            content: ""; position: absolute; left: 0; right: 0; bottom: -6px; height: 1px; background: var(--line);
        }
        .lpo-logo { flex: 0 0 auto; max-width: 150px; max-height: 70px; display: flex; align-items: center; }
        .lpo-logo img { max-width: 150px; max-height: 70px; object-fit: contain; display: block; }
        .lpo-logo .placeholder {
            width: 70px; height: 70px; border-radius: 14px;
            background: var(--accent); color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-size: 30px; font-weight: 800; letter-spacing: .5px;
        }
        .lpo-identity { flex: 1; min-width: 0; }
        .lpo-company {
            font-size: 23px; font-weight: 800; letter-spacing: .4px;
            color: var(--gold); line-height: 1.1; margin-bottom: 4px;
        }
        .lpo-company-sub { font-size: 10.5px; color: var(--muted); line-height: 1.5; }
        .lpo-titleblock { flex: 0 0 auto; text-align: right; }
        .lpo-doc-title {
            font-size: 20px; font-weight: 800; letter-spacing: 2px;
            color: var(--accent-deep); text-transform: uppercase;
        }
        .lpo-doc-sub {
            display: inline-block; margin-top: 5px; padding: 3px 10px; border-radius: 999px;
            background: var(--accent-tint); color: var(--accent-dark);
            font-size: 10px; font-weight: 700; letter-spacing: .5px;
        }

        /* ── Meta strip ───────────────────────────────────── */
        .lpo-meta { display: flex; gap: 10px; margin-top: 16px; }
        .lpo-meta .cell {
            flex: 1; background: var(--accent-tint-2);
            border: 1px solid var(--line); border-radius: 10px; padding: 8px 11px;
        }
        .lpo-meta .k {
            font-size: 8.5px; font-weight: 700; letter-spacing: 1.2px;
            text-transform: uppercase; color: var(--muted); margin-bottom: 3px;
        }
        .lpo-meta .v { font-size: 12px; font-weight: 700; color: var(--ink); }
        .lpo-meta .v.accent { color: var(--accent-dark); }
        .status-pill {
            display: inline-block; padding: 2px 9px; border-radius: 999px; font-size: 10px; font-weight: 700;
            color: #fff; letter-spacing: .4px;
        }

        /* ── Party panels ─────────────────────────────────── */
        .lpo-parties { display: flex; gap: 12px; margin-top: 16px; }
        .panel { flex: 1; border: 1px solid var(--line); border-radius: 10px; overflow: hidden; }
        .panel-head {
            background: var(--accent); color: #fff;
            font-size: 10.5px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; padding: 7px 12px;
        }
        .panel-body { padding: 10px 12px; }
        .panel-name { font-size: 13.5px; font-weight: 800; color: var(--ink); margin-bottom: 6px; }
        .kv { display: flex; gap: 6px; padding: 2.5px 0; font-size: 10.5px; align-items: baseline; }
        .kv .kk { flex: 0 0 92px; color: var(--muted); font-weight: 700; text-transform: uppercase; font-size: 8.8px; letter-spacing: .6px; padding-top: 1px; }
        .kv .vv { flex: 1; color: var(--ink); word-break: break-word; }

        /* ── Items table ──────────────────────────────────── */
        .lpo-items { margin-top: 18px; }
        table.items { width: 100%; border-collapse: collapse; }
        table.items thead { display: table-header-group; }
        table.items thead th {
            background: var(--accent-dark); color: #fff;
            font-size: 9.5px; font-weight: 700; letter-spacing: .6px; text-transform: uppercase;
            padding: 9px 8px; text-align: left;
        }
        table.items th.num, table.items td.num { text-align: right; }
        table.items th.ctr, table.items td.ctr { text-align: center; }
        table.items tbody tr { page-break-inside: avoid; }
        table.items tbody td {
            padding: 8px; font-size: 10.5px; border-bottom: 1px solid var(--line-soft); vertical-align: top;
        }
        table.items tbody tr:nth-child(even) td { background: var(--accent-tint-2); }
        table.items tbody td .item-name { font-weight: 700; color: var(--ink); }
        table.items tbody td.amount { font-weight: 700; }
        table.items tbody td.empty { color: #aaa; font-style: italic; text-align: center; }
        .item-index {
            display: inline-flex; align-items: center; justify-content: center;
            width: 20px; height: 20px; border-radius: 6px; background: var(--accent-tint);
            color: var(--accent-dark); font-weight: 800; font-size: 10px;
        }

        /* ── Note + totals ────────────────────────────────── */
        .lpo-bottom { display: flex; gap: 14px; margin-top: 14px; align-items: flex-start; page-break-inside: avoid; }
        .lpo-note { flex: 1; border: 1px dashed var(--line); border-radius: 10px; padding: 10px 12px; }
        .lpo-note .nt { font-size: 9px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; color: var(--muted); margin-bottom: 4px; }
        .lpo-note .nb { font-size: 10px; color: var(--ink); }
        .lpo-totals { flex: 0 0 250px; border: 1px solid var(--line); border-radius: 10px; overflow: hidden; }
        .lpo-totals .tr { display: flex; justify-content: space-between; padding: 7px 14px; font-size: 11px; }
        .lpo-totals .tr + .tr { border-top: 1px solid var(--line-soft); }
        .lpo-totals .tr .tl { color: var(--muted); font-weight: 600; }
        .lpo-totals .tr .tv { font-weight: 700; color: var(--ink); }
        .lpo-totals .grand {
            background: var(--accent); color: #fff; padding: 10px 14px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .lpo-totals .grand .tl { font-size: 11px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; }
        .lpo-totals .grand .tv { font-size: 16px; font-weight: 800; }

        /* ── Terms ────────────────────────────────────────── */
        .lpo-terms { margin-top: 18px; border: 1px solid var(--line); border-radius: 10px; overflow: hidden; page-break-inside: avoid; }
        .lpo-terms .th {
            background: var(--accent-tint); color: var(--accent-dark);
            font-size: 10px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; padding: 7px 12px;
        }
        .lpo-terms .tbody { padding: 6px 12px 10px; }
        .term { display: flex; gap: 10px; padding: 5px 0; font-size: 10.5px; }
        .term + .term { border-top: 1px solid var(--line-soft); }
        .term .tn {
            flex: 0 0 18px; height: 18px; border-radius: 50%;
            background: var(--accent); color: #fff; font-weight: 700; font-size: 10px;
            display: flex; align-items: center; justify-content: center;
        }
        .term .tt { flex: 1; }
        .term .tt b { color: var(--ink); }
        .term .tt .danger { color: #c0392b; font-weight: 700; }

        /* ── Signatures ───────────────────────────────────── */
        .lpo-signs { display: flex; gap: 14px; margin-top: 26px; page-break-inside: avoid; }
        .sign { flex: 1; text-align: center; }
        .sign .line { border-top: 1.5px solid #9aa3af; margin: 28px 10px 6px; }
        .sign .who { font-size: 11px; font-weight: 700; color: var(--accent-dark); }
        .sign .role { font-size: 9.5px; color: var(--muted); letter-spacing: .5px; text-transform: uppercase; margin-top: 1px; }
        .sign .when { font-size: 9px; color: var(--muted); margin-top: 3px; }

        /* ── Footer ───────────────────────────────────────── */
        .lpo-foot {
            margin-top: 24px; padding-top: 10px; border-top: 1px solid var(--line);
            display: flex; justify-content: space-between; align-items: center;
            font-size: 9px; color: var(--muted);
        }
        .lpo-foot .fr { font-weight: 700; color: var(--accent-dark); letter-spacing: .5px; }
    </style>
</head>
<body>
<div class="lpo">

    {{-- ── MASTHEAD ─────────────────────────────────────────── --}}
    <div class="lpo-masthead">
        <div class="lpo-logo">
            @if($companyLogo)
                <img src="{{ $companyLogo }}" alt="Company Logo">
            @else
                <div class="placeholder">{{ strtoupper(substr($companyName ?: 'C', 0, 1)) }}</div>
            @endif
        </div>
        <div class="lpo-identity">
            <div class="lpo-company">{{ strtoupper($companyName) }}</div>
            <div class="lpo-company-sub">
                @if($companyAddress){{ $companyAddress }}<br>@endif
                @if($companyPhone)TEL. {{ $companyPhone }}@endif
            </div>
        </div>
        <div class="lpo-titleblock">
            <div class="lpo-doc-title">Purchase Order</div>
            <div class="lpo-doc-sub">LOCAL PURCHASE ORDER</div>
        </div>
    </div>

    {{-- ── META STRIP ───────────────────────────────────────── --}}
    <div class="lpo-meta">
        <div class="cell">
            <div class="k">Reference No</div>
            <div class="v accent">{{ $refNo }}</div>
        </div>
        <div class="cell">
            <div class="k">Date</div>
            <div class="v">{{ $orderDate->format('d-M-Y') }}</div>
        </div>
        <div class="cell">
            <div class="k">Branch</div>
            <div class="v">{{ strtoupper($order->branch?->name ?? 'OFFICE') }}</div>
        </div>
        <div class="cell">
            <div class="k">Status</div>
            <div class="v"><span class="status-pill" style="background: {{ $statusColor }};">{{ strtoupper($order->status?->label() ?? '—') }}</span></div>
        </div>
    </div>

    {{-- ── PARTIES ──────────────────────────────────────────── --}}
    <div class="lpo-parties">
        {{-- Vendor --}}
        <div class="panel">
            <div class="panel-head">Vendor</div>
            <div class="panel-body">
                <div class="panel-name">{{ strtoupper($order->vendor?->name ?? '—') }}</div>
                <div class="kv"><span class="kk">Contact</span><span class="vv">{{ $order->vendor?->contact_person ?: '—' }}</span></div>
                <div class="kv"><span class="kk">Mobile</span><span class="vv">{{ $order->vendor?->mobile ?: '—' }}</span></div>
                <div class="kv"><span class="kk">Address</span><span class="vv">{{ $order->vendor?->place ?: '—' }}</span></div>
                <div class="kv"><span class="kk">E-mail</span><span class="vv">{{ $order->vendor?->email ?: '—' }}</span></div>
            </div>
        </div>
        {{-- Deliver To --}}
        <div class="panel">
            <div class="panel-head">Deliver To</div>
            <div class="panel-body">
                <div class="panel-name">{{ strtoupper($companyName) }}</div>
                <div class="kv"><span class="kk">Location</span><span class="vv">{{ strtoupper($order->branch?->name ?? '—') }}</span></div>
                <div class="kv"><span class="kk">Branch</span><span class="vv">{{ strtoupper($order->branch?->name ?? '—') }}</span></div>
                <div class="kv"><span class="kk">Prepared By</span><span class="vv">{{ strtoupper($order->creator?->name ?? '—') }}</span></div>
                <div class="kv"><span class="kk">Date</span><span class="vv">{{ $orderDate->format('d M Y') }}</span></div>
            </div>
        </div>
    </div>

    {{-- ── ITEMS ────────────────────────────────────────────── --}}
    <div class="lpo-items">
        <table class="items">
            <thead>
                <tr>
                    <th class="ctr" style="width:7%">#</th>
                    <th style="width:43%">Item Description</th>
                    <th class="ctr" style="width:10%">Unit</th>
                    <th class="num" style="width:12%">Qty</th>
                    <th class="num" style="width:14%">Rate (QAR)</th>
                    <th class="num" style="width:14%">Amount (QAR)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($order->items as $i => $item)
                    <tr>
                        <td class="ctr"><span class="item-index">{{ $i + 1 }}</span></td>
                        <td><div class="item-name">{{ $item->product?->name ?? '-' }}</div></td>
                        <td class="ctr">{{ $item->product?->unit?->name ?? '-' }}</td>
                        <td class="num">{{ number_format($item->quantity, 0) }}</td>
                        <td class="num">{{ number_format($item->rate, 2) }}</td>
                        <td class="num amount">{{ number_format($item->quantity * $item->rate, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty">No items</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ── NOTE + TOTALS ────────────────────────────────────── --}}
    <div class="lpo-bottom">
        <div class="lpo-note">
            <div class="nt">Notice</div>
            <div class="nb">If you have any questions about this purchase order, please contact the representative mentioned above. Goods must be delivered to the location specified.</div>
        </div>
        <div class="lpo-totals">
            <div class="tr"><span class="tl">Subtotal</span><span class="tv">QAR {{ number_format($subtotal, 2) }}</span></div>
            <div class="tr"><span class="tl">Discount</span><span class="tv">QAR {{ number_format($discount, 2) }}</span></div>
            <div class="grand"><span class="tl">Total</span><span class="tv">QAR {{ number_format($grandTotal, 2) }}</span></div>
        </div>
    </div>

    {{-- ── TERMS ────────────────────────────────────────────── --}}
    <div class="lpo-terms">
        <div class="th">Terms &amp; Conditions</div>
        <div class="tbody">
            <div class="term"><div class="tn">1</div><div class="tt"><b>Payment Terms:</b> {{ $order->payment_terms ?? '—' }}</div></div>
            <div class="term"><div class="tn">2</div><div class="tt"><b>Remarks:</b> {{ $order->decision_note ?: '—' }}</div></div>
            <div class="term"><div class="tn">3</div><div class="tt"><span class="danger"><b>Payment Mode:</b></span></div></div>
        </div>
    </div>

    {{-- ── SIGNATURES ───────────────────────────────────────── --}}
    <div class="lpo-signs">
        <div class="sign">
            <div class="line"></div>
            <div class="who">{{ strtoupper($order->creator?->name ?? '—') }}</div>
            <div class="role">Prepared By · Finance Officer</div>
        </div>
        <div class="sign">
            <div class="line"></div>
            <div class="who">{{ strtoupper($order->decisionMaker?->name ?? '—') }}</div>
            <div class="role">Reviewed By · Finance Manager</div>
        </div>
        <div class="sign">
            <div class="line"></div>
            <div class="who">{{ strtoupper($order->decisionMaker?->name ?? '—') }}</div>
            <div class="role">Approved By · CEO</div>
            @if($order->decision_at)
                <div class="when">{{ $order->decision_at->format('d M Y, h:i A') }}</div>
            @endif
        </div>
    </div>

    {{-- ── FOOTER ───────────────────────────────────────────── --}}
    <div class="lpo-foot">
        <div class="fl">Generated on {{ now()->format('d M Y, h:i A') }} · This is a system-generated purchase order.</div>
        <div class="fr">{{ strtoupper($companyName) }}</div>
    </div>

</div>
</body>
</html>
