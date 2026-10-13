@php
    $isReceipt = $kind === 'receipt';
    $title = $isReceipt ? 'Payment Receipt' : 'Payment Voucher';
    $reversed = $payment->status === \App\Models\ProjectContractPayment::STATUS_REVERSED;
    $terms = $payment->allocations->map(fn ($allocation) => trim(($allocation->term?->label ?? '').' — QR '.currency($allocation->amount)))->filter()->all();
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title }} - #{{ $payment->voucher_no }}</title>
    @include('print.project-contract.partials.voucher-style')
</head>
<body>
    @include('print.project-contract.partials.voucher-head', ['title' => $title, 'stamp' => $reversed ? 'REVERSED' : null])

    <table class="b-meta">
        <tr><td class="k">{{ $isReceipt ? 'Receipt No.' : 'Voucher No.' }}</td><td class="v">{{ $payment->voucher_no }}</td></tr>
        <tr><td class="k">Date</td><td class="v">{{ $payment->date?->format('d-m-Y') }}</td></tr>
    </table>

    <div class="b-amount">
        <div class="lbl">{{ $isReceipt ? 'Amount Received' : 'Amount Paid' }}</div>
        <div class="fig"><span class="cur">QR</span> {{ currency($payment->amount) }}</div>
    </div>

    <div class="b-words">in words — <b>{{ convertCurrencyToWords($payment->amount) }}</b></div>

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
                <td class="f-value">{{ $contract->reference_no }}</td>
            </tr>
            <tr>
                <td class="f-label">Project</td>
                <td class="f-colon">:</td>
                <td class="f-value">{{ $contract->project_name }}</td>
            </tr>
            <tr>
                <td class="f-label">Payment For</td>
                <td class="f-colon">:</td>
                <td class="f-value">{!! $terms ? implode('<br>', array_map('e', $terms)) : '—' !!}</td>
            </tr>
            <tr>
                <td class="f-label">Payment Mode</td>
                <td class="f-colon">:</td>
                <td class="f-value">{{ $payment->paymentMethod?->name ?: '—' }}</td>
            </tr>
            @if ($payment->cheque_no)
                <tr>
                    <td class="f-label">Cheque</td>
                    <td class="f-colon">:</td>
                    <td class="f-value">#{{ $payment->cheque_no }}@if ($payment->bank_name) &mdash; {{ $payment->bank_name }}@endif @if ($payment->cheque_date) &bull; dated {{ $payment->cheque_date->format('d-m-Y') }}@endif</td>
                </tr>
            @endif
            @if ($payment->reference)
                <tr>
                    <td class="f-label">Reference</td>
                    <td class="f-colon">:</td>
                    <td class="f-value">{{ $payment->reference }}</td>
                </tr>
            @endif
            <tr>
                <td class="f-label">Remarks</td>
                <td class="f-colon">:</td>
                <td class="f-value">{{ $payment->remarks ?: '—' }}</td>
            </tr>
            @if ($reversed)
                <tr>
                    <td class="f-label">Reversed</td>
                    <td class="f-colon">:</td>
                    <td class="f-value"><span class="neg">{{ $payment->reversed_at?->format('d-m-Y') }}</span> &mdash; {{ $payment->reversal_reason }}</td>
                </tr>
            @endif
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

    <div class="footer">Computer generated {{ $isReceipt ? 'receipt' : 'voucher' }} &bull; prepared by {{ $payment->creator?->name ?? '—' }} &bull; {{ now()->format('d/m/Y h:i A') }}</div>
</body>
</html>
