@php
    $original = $contract->extensions->first();
    $approved = $approvals->where('status', \App\Models\ProjectContractApproval::APPROVED);
    $stamp = in_array($contract->status->value, ['draft', 'pending_approval', 'rejected', 'cancelled'], true) ? strtoupper($contract->status->label()) : null;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Contract Voucher - {{ $contract->reference_no }}</title>
    @include('print.project-contract.partials.voucher-style')
</head>
<body>
    @include('print.project-contract.partials.voucher-head', ['title' => 'Contract Voucher', 'stamp' => $stamp])

    <table class="b-meta">
        <tr><td class="k">Contract No.</td><td class="v">{{ $contract->reference_no }}</td></tr>
        <tr><td class="k">Date</td><td class="v">{{ $contract->start_date?->format('d-m-Y') }}</td></tr>
    </table>

    <div class="b-amount">
        <div class="lbl">Contract Amount</div>
        <div class="fig"><span class="cur">QR</span> {{ currency($contract->contract_amount) }}</div>
    </div>

    <div class="b-words">in words — <b>{{ convertCurrencyToWords($contract->contract_amount) }}</b></div>

    <table class="b-fields">
        <tbody>
            <tr>
                <td class="f-label">Vendor</td>
                <td class="f-colon">:</td>
                <td class="f-value">{{ $contract->vendor?->name ?? '—' }}@if ($contract->vendor?->alias_name) &mdash; {{ $contract->vendor->alias_name }}@endif @if ($contract->vendor?->mobile)<span class="muted"> &bull; {{ $contract->vendor->mobile }}</span>@endif</td>
            </tr>
            <tr>
                <td class="f-label">Project</td>
                <td class="f-colon">:</td>
                <td class="f-value">{{ $contract->project_name }}</td>
            </tr>
            <tr>
                <td class="f-label">Period</td>
                <td class="f-colon">:</td>
                <td class="f-value">{{ $contract->start_date?->format('d-m-Y') }} to {{ $contract->end_date?->format('d-m-Y') }}</td>
            </tr>
            <tr>
                <td class="f-label">Payment Frequency</td>
                <td class="f-colon">:</td>
                <td class="f-value">{{ $contract->payment_frequency->label() }} &bull; {{ $contract->no_of_terms }} planned {{ $contract->no_of_terms === 1 ? 'term' : 'terms' }}</td>
            </tr>
            @if ($original)
                <tr>
                    <td class="f-label">Extended</td>
                    <td class="f-colon">:</td>
                    <td class="f-value">Originally QR {{ currency($original->old_amount) }} to {{ $original->old_end_date?->format('d-m-Y') }} <span class="muted">&bull; {{ $contract->extensions->count() }} {{ $contract->extensions->count() === 1 ? 'extension' : 'extensions' }}</span></td>
                </tr>
            @endif
            <tr>
                <td class="f-label">Paid to Date</td>
                <td class="f-colon">:</td>
                <td class="f-value">QR {{ currency($paidTotal) }} <span class="muted">&bull; balance QR {{ currency((float) $contract->contract_amount - $paidTotal) }}</span></td>
            </tr>
            @if ($contract->branch)
                <tr>
                    <td class="f-label">Branch</td>
                    <td class="f-colon">:</td>
                    <td class="f-value">{{ $contract->branch->name }}</td>
                </tr>
            @endif
            <tr>
                <td class="f-label">Approved By</td>
                <td class="f-colon">:</td>
                <td class="f-value">
                    @forelse ($approved as $approval)
                        Level {{ $approval->level }} &mdash; {{ $approval->actor?->name ?? '—' }} <span class="muted">({{ $approval->acted_at?->format('d-m-Y') }})</span>@if (! $loop->last)<br>@endif
                    @empty
                        —
                    @endforelse
                </td>
            </tr>
            <tr>
                <td class="f-label">Remarks</td>
                <td class="f-colon">:</td>
                <td class="f-value">{{ $contract->remark ?: '—' }}</td>
            </tr>
        </tbody>
    </table>

    <table class="sig-table">
        <tr>
            <td>
                <div class="sig-line"></div>
                <div class="sig-lbl">Vendor's Signature</div>
            </td>
            <td>
                <div class="sig-line"></div>
                <div class="sig-lbl">Authorised Signature</div>
            </td>
        </tr>
    </table>

    <div class="footer">Computer generated contract voucher &bull; prepared by {{ $contract->creator?->name ?? '—' }} &bull; {{ now()->format('d/m/Y h:i A') }}</div>
</body>
</html>
