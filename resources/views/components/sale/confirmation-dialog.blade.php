@props([
    'customer' => 'Unknown Customer',
    'grandTotal' => 0,
    'paid' => 0,
    'balance' => 0,
    'paymentMethods' => null,
])

@php
    $balance = floatval($balance);
    $balanceColor = $balance > 0 ? '#dc3545' : ($balance < 0 ? '#fd7e14' : '#198754');
    $balanceIcon = $balance > 0 ? 'fa-exclamation-triangle' : ($balance < 0 ? 'fa-arrow-down' : 'fa-check-circle');
    $statusText = $balance == 0 ? 'Ready to Submit' : ($balance > 0 ? 'Partial Payment' : 'Overpaid Transaction');
    $statusDescription = $balance == 0 ? 'Transaction is fully paid and ready to submit' : ($balance > 0 ? 'Transaction has a remaining balance' : 'Transaction amount exceeds payment');
@endphp

<div class="confirmation-container" style="text-align: left; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    <!-- Clean Customer Header -->
    <div class="customer-header" style=" background: #f8f9fa; border: 1px solid #e9ecef; color: #495057; padding: 20px; border-radius: 8px; margin-bottom: 20px; text-align: center; ">
        <div style=" width: 50px; height: 50px; background: #6c757d; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px auto; ">
            <i class="fa fa-user" style="font-size: 1.2rem;"></i>
        </div>
        <h4 style="margin: 0; font-weight: 600; color: #343a40;">{{ $customer }}</h4>
    </div>

    <!-- Clean Transaction Summary -->
    <div class="transaction-summary" style="margin-bottom: 20px;">
        <!-- Summary Table -->
        <table style=" width: 100%; border-collapse: collapse; background: white; border: 1px solid #dee2e6; border-radius: 8px; overflow: hidden; margin-bottom: 15px; ">
            <tr style="background: #f8f9fa; border-bottom: 1px solid #dee2e6;">
                <td style="padding: 12px 15px; font-weight: 600; color: #495057;">
                    <i class="fa fa-dollar-sign" style="margin-right: 8px; color: #6c757d;"></i>
                    Grand Total
                </td>
                <td style="padding: 12px 15px; text-align: right; font-weight: 600; color: #28a745;">
                    {{ $grandTotal }}
                </td>
            </tr>
            <tr style="border-bottom: 1px solid #dee2e6;">
                <td style="padding: 12px 15px; color: #495057;">
                    <i class="fa fa-credit-card" style="margin-right: 8px; color: #6c757d;"></i>
                    Paid Amount
                </td>
                <td style="padding: 12px 15px; text-align: right; font-weight: 500; color: #007bff;">
                    {{ $paid }}
                </td>
            </tr>
            <tr style="background: {{ $balance === 0 ? '#f8f9fa' : ($balance > 0 ? '#fff5f5' : '#fffbf0') }};">
                <td style="padding: 12px 15px; font-weight: 600; color: #495057;">
                    <i class="fa {{ $balanceIcon }}" style="margin-right: 8px; color: {{ $balanceColor }};"></i>
                    {{ $balance > 0 ? 'Remaining Balance' : ($balance < 0 ? 'Overpaid Amount' : 'Balance') }}
                </td>
                <td style="padding: 12px 15px; text-align: right; font-weight: 700; color: {{ $balanceColor }};">
                    {{ currency(abs($balance)) }}
                </td>
            </tr>
        </table>
    </div>

    @if ($paymentMethods)
        <!-- Payment Methods -->
        <div class="payment-methods" style=" background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 8px; padding: 15px; margin-bottom: 15px; ">
            <div style=" display: flex; align-items: center; margin-bottom: 10px; color: #495057; font-weight: 600; ">
                <i class="fa fa-credit-card" style="margin-right: 8px; color: #6c757d;"></i>
                Payment Methods Used
            </div>
            <div
                style=" background: white; padding: 12px; border-radius: 4px; border-left: 3px solid #007bff; font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace; font-size: 0.9rem; color: #495057; ">
                {{ $paymentMethods }}
            </div>
        </div>
    @endif

    <!-- Clean Status Indicator -->
    <div
        style=" text-align: center; padding: 15px; background: {{ $balance === 0 ? '#f8f9fa' : ($balance > 0 ? '#fff3cd' : '#d1ecf1') }}; border: 1px solid {{ $balance === 0 ? '#e9ecef' : ($balance > 0 ? '#ffeaa7' : '#bee5eb') }}; border-radius: 8px; color: {{ $balance === 0 ? '#495057' : ($balance > 0 ? '#856404' : '#0c5460') }}; ">
        <div
            style=" width: 40px; height: 40px; background: {{ $balanceColor }}; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px auto; ">
            <i class="fa {{ $balanceIcon }}" style="font-size: 1rem;"></i>
        </div>
        <div style="font-weight: 600; margin-bottom: 5px;">
            {{ $statusText }}
        </div>
        <div style="font-size: 0.9rem; opacity: 0.8;">
            {{ $statusDescription }}
        </div>
    </div>
</div>
