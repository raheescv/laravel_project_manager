{{-- The body of the daily pre-order summary; the appointment mail template wraps it. Inline styles only: mail clients drop <style>. --}}
@php
    $money = fn (float $value): string => number_format($value, $decimals);
    $cell = 'padding:7px 8px;border-bottom:1px solid #ece5da;font-family:\'Segoe UI\',Helvetica,Arial,sans-serif;font-size:13px;line-height:1.45;color:#4a4238;vertical-align:top;';
    $head = 'padding:7px 8px;border-bottom:2px solid #d9cfbf;font-family:\'Segoe UI\',Helvetica,Arial,sans-serif;font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:#8c7f6d;text-align:left;';
@endphp

<p style="margin:0 0 6px;">Pre-orders for <b>{{ $date->format('l, j F Y') }}</b>.</p>
<p style="margin:0 0 20px;font-size:13px;color:#8c7f6d;">Orders for this day closed at {{ $cutoff }}. Every order below is paid; each is a completed sale dated this day.@if ($summary['unpaid']) {{ $summary['unpaid'] }} {{ $summary['unpaid'] === 1 ? 'order was' : 'orders were' }} not paid (not enough on the card) and {{ $summary['unpaid'] === 1 ? 'is' : 'are' }} not to be served.@endif</p>

@if ($summary['students'] === 0)
    <p style="margin:0;">No paid pre-orders for this day.</p>
@else
    <p style="margin:0 0 8px;font-family:'Segoe UI',Helvetica,Arial,sans-serif;font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:#8c7f6d;font-weight:700;">To prepare</p>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;margin:0 0 24px;">
        <tr>
            <th style="{{ $head }}">Item</th>
            <th style="{{ $head }}text-align:right;">Qty</th>
            <th style="{{ $head }}text-align:right;">{{ $currency }}</th>
        </tr>
        @foreach ($summary['items'] as $item)
            <tr>
                <td style="{{ $cell }}">{{ $item['name'] }}</td>
                <td style="{{ $cell }}text-align:right;font-weight:700;">{{ $item['quantity'] }}</td>
                <td style="{{ $cell }}text-align:right;">{{ $money($item['amount']) }}</td>
            </tr>
        @endforeach
        <tr>
            <td style="{{ $cell }}font-weight:700;border-bottom:0;">{{ $summary['students'] }} {{ $summary['students'] === 1 ? 'student' : 'students' }}</td>
            <td style="{{ $cell }}text-align:right;font-weight:700;border-bottom:0;">{{ collect($summary['items'])->sum('quantity') }}</td>
            <td style="{{ $cell }}text-align:right;font-weight:700;border-bottom:0;">{{ $money($summary['total']) }}</td>
        </tr>
    </table>

    <p style="margin:0 0 8px;font-family:'Segoe UI',Helvetica,Arial,sans-serif;font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:#8c7f6d;font-weight:700;">By student</p>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
        <tr>
            <th style="{{ $head }}">Student</th>
            <th style="{{ $head }}">Order</th>
            <th style="{{ $head }}text-align:right;">{{ $currency }}</th>
        </tr>
        @foreach (collect($summary['orders'])->where('paid', true) as $order)
            <tr>
                <td style="{{ $cell }}">
                    <b>{{ $order['student'] }}</b>
                    <br><span style="color:#8c7f6d;font-size:12px;">{{ collect([$order['school'], $order['class'], $order['admission_no']])->filter()->implode(' · ') }}</span>
                </td>
                <td style="{{ $cell }}">
                    @foreach ($order['items'] as $line)
                        {{ $line['quantity'] }} × {{ $line['name'] }}@if (! $loop->last)<br>@endif
                    @endforeach
                    @if ($order['note'])
                        <br><span style="color:#8c7f6d;font-size:12px;font-style:italic;">Note: {{ $order['note'] }}</span>
                    @endif
                    @if ($order['source'] === 'weekly')
                        <br><span style="color:#8c7f6d;font-size:11px;">Weekly order</span>
                    @endif
                </td>
                <td style="{{ $cell }}text-align:right;">{{ $money($order['total']) }}<br><span style="color:#8c7f6d;font-size:11px;">#{{ $order['invoice_no'] }}</span></td>
            </tr>
        @endforeach
    </table>
@endif
