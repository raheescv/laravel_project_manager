@php
    $isReceipt = $kind === 'receipt';
    $title = $isReceipt ? 'Payment Receipt' : 'Payment Voucher';
    $paid = (float) $allocations->sum('amount');
    $latest = $allocations->max(fn ($allocation) => $allocation->payment->date);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title }} - {{ $contract->reference_no }} · {{ $term->label }}</title>
    @include('print.project-contract.partials.voucher-style')
</head>
<body>
    @include('print.project-contract.partials.voucher-head', ['title' => $title])

    <table class="b-meta">
        <tr><td class="k">{{ $isReceipt ? 'Receipt No.' : 'Voucher No.' }}</td><td class="v">{{ $allocations->pluck('payment.voucher_no')->unique()->implode(', ') ?: '—' }}</td></tr>
        <tr><td class="k">Date</td><td class="v">{{ $latest?->format('d-m-Y') ?? '—' }}</td></tr>
    </table>

    <div class="b-amount">
        <div class="lbl">{{ $isReceipt ? 'Amount Received' : 'Amount Paid' }}</div>
        <div class="fig"><span class="cur">QR</span> {{ currency($paid) }}</div>
    </div>

    <div class="b-words">in words — <b>{{ convertCurrencyToWords($paid) }}</b></div>

    <table class="b-fields">
        <tbody>
            <tr>
                <td class="f-label">{{ $isReceipt ? 'Received By' : 'Paid To' }}</td>
                <td class="f-colon">:</td>
                <td class="f-value">{{ $contract->vendor?->name ?? '—' }}@if ($contract->vendor?->alias_name) &mdash; {{ $contract->vendor->alias_name }}@endif</td>
            </tr>
            <tr>
                <td class="f-label">Contract No.</td>
                <td class="f-colon">:</td>
                <td class="f-value">{{ $contract->reference_no }} &mdash; {{ $contract->project_name }}</td>
            </tr>
            <tr>
                <td class="f-label">Payment For</td>
                <td class="f-colon">:</td>
                <td class="f-value">{{ $term->label }} <span class="muted">&bull; due {{ $term->due_date?->format('d-m-Y') }} &bull; term amount QR {{ currency($term->amount) }}</span></td>
            </tr>
            <tr>
                <td class="f-label">Payments</td>
                <td class="f-colon">:</td>
                <td class="f-value">
                    @foreach ($allocations as $allocation)
                        {{ $allocation->payment->voucher_no }} &mdash; {{ $allocation->payment->date?->format('d-m-Y') }} &mdash; {{ $allocation->payment->paymentMethod?->name ?? '—' }}@if ($allocation->payment->cheque_no) #{{ $allocation->payment->cheque_no }}@endif &mdash; QR {{ currency($allocation->amount) }}@if (! $loop->last)<br>@endif
                    @endforeach
                </td>
            </tr>
            <tr>
                <td class="f-label">Balance on Term</td>
                <td class="f-colon">:</td>
                <td class="f-value">QR {{ currency(max((float) $term->amount - $paid, 0)) }}</td>
            </tr>
            <tr>
                <td class="f-label">Remarks</td>
                <td class="f-colon">:</td>
                <td class="f-value">{{ $term->remarks ?: '—' }}</td>
            </tr>
        </tbody>
    </table>

    <table class="sig-table">
        <tr>
            <td>
                <div class="sig-line"></div>
                <div class="sig-lbl">Receiver's Signature</div>
            </td>
            <td>
                <div class="sig-line"></div>
                <div class="sig-lbl">{{ $isReceipt ? 'Authorised Signature' : 'Accountant Signature' }}</div>
            </td>
        </tr>
    </table>

    <div class="footer">Computer generated {{ $isReceipt ? 'receipt' : 'voucher' }} &bull; {{ now()->format('d/m/Y h:i A') }}</div>
</body>
</html>
