<!DOCTYPE html>
<html lang="en">

@php
    // Use pixels for consistency with the designer
    // The PDF generation will handle scaling to the correct physical size
    function convertToUnits($value)
    {
        if (is_numeric($value)) {
            return $value . 'px';
        }
        return $value;
    }

    function getElementStyle($element, $settings)
    {
        $style = [];
        foreach (['top', 'left', 'width', 'height'] as $prop) {
            $style[$prop] = convertToUnits($settings['elements'][$element][$prop] ?? 0);
        }
        return implode('; ', array_map(fn($key, $value) => "$key: $value", array_keys($style), $style));
    }
@endphp

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cheque Print</title>
    <style>
        @page {
            margin: 0;
            padding: 0;
        }

        @media print {
            .page-break {
                page-break-after: always;
            }
        }

        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }

        .cheque-container {
            position: relative;
            width: {{ (($settings['width'] ?? 210) / 25.4) * 96 }}px;
            height: {{ (($settings['height'] ?? 100) / 25.4) * 96 }}px;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            page-break-inside: avoid;
            @if($useTemplate)
                background: linear-gradient(to bottom, #e3f2fd 0%, #ffffff 100%);
                border: 2px solid #1976d2;
                border-radius: 4px;
            @else
                background: #ffffff;
                border: 1px solid #ddd;
            @endif
        }

        .cheque-element {
            position: absolute;
            overflow: hidden;
            margin: 0;
            padding: 0;
        }

        .cheque-header {
            @if($useTemplate)
                text-align: center;
                font-size: 24px;
                font-weight: bold;
                color: #1976d2;
                padding: 5px 0;
                border-bottom: 2px solid #1976d2;
            @endif
        }

        .date {
            font-size: {{ $settings['date']['font_size'] ?? 11 }}px;
            text-align: {{ $settings['date']['align'] ?? 'right' }};
            font-weight: 500;
            color: #333;
        }

        .payee {
            font-size: {{ $settings['payee']['font_size'] ?? 13 }}px;
            text-align: {{ $settings['payee']['align'] ?? 'left' }};
            font-weight: 500;
            color: #000;
            line-height: 1.3;
            text-transform: none;
        }

        .amount-in-words {
            font-size: {{ $settings['amount_in_words']['font_size'] ?? 11 }}px;
            text-align: {{ $settings['amount_in_words']['align'] ?? 'left' }};
            font-weight: 500;
            color: #000;
            line-height: 1.4;
        }

        .amount-in-numbers {
            font-size: {{ $settings['amount_in_numbers']['font_size'] ?? 14 }}px;
            text-align: {{ $settings['amount_in_numbers']['align'] ?? 'right' }};
            font-weight: bold;
            border: 1px solid #000;
            padding: 3px 8px;
            color: #000;
            background: #fff;
        }

        .signature {
            font-size: {{ $settings['signature']['font_size'] ?? 10 }}px;
            text-align: {{ $settings['signature']['align'] ?? 'right' }};
            border-top: 1px solid #000;
            padding-top: 3px;
            color: #000;
        }

        .cheque-number {
            font-size: {{ $settings['cheque_number']['font_size'] ?? 9 }}px;
            text-align: {{ $settings['cheque_number']['align'] ?? 'left' }};
            color: #555;
            font-weight: 500;
        }

        .account-number {
            font-size: {{ $settings['account_number']['font_size'] ?? 8 }}px;
            text-align: {{ $settings['account_number']['align'] ?? 'center' }};
            color: #666;
            font-weight: 400;
        }

        .bank-name {
            font-size: {{ $settings['bank_name']['font_size'] ?? 14 }}px;
            text-align: {{ $settings['bank_name']['align'] ?? 'center' }};
            font-weight: bold;
            color: #1976d2;
            letter-spacing: 0.5px;
        }

        @if($useTemplate)
            .ac-payee-only {
                text-align: center;
                font-size: 10px;
                color: #1976d2;
                font-weight: bold;
                text-decoration: underline;
                padding: 2px 0;
            }

            .cheque-footer {
                position: absolute;
                bottom: 5px;
                left: 0;
                right: 0;
                text-align: center;
                font-size: 8px;
                color: #666;
            }
        @endif
    </style>
</head>

<body>
    <div class="cheque-container">
        @if(($settings['bank_name']['visible'] ?? true) && !empty($chequeData['bank_name']))
            <div class="cheque-element bank-name" style="{{ getElementStyle('bank_name', $settings) }}">
                {{ $chequeData['bank_name'] }}
            </div>
        @endif

        @if(($settings['cheque_number']['visible'] ?? true) && !empty($chequeData['cheque_number']))
            <div class="cheque-element cheque-number" style="{{ getElementStyle('cheque_number', $settings) }}">
                {{ $chequeData['cheque_number'] }}
            </div>
        @endif

        @if(($settings['date']['visible'] ?? true) && !empty($chequeData['date']))
            <div class="cheque-element date" style="{{ getElementStyle('date', $settings) }}">
                {{ $chequeData['date'] }}
            </div>
        @endif

        @if(($settings['payee']['visible'] ?? true) && !empty($chequeData['payee']))
            <div class="cheque-element payee" style="{{ getElementStyle('payee', $settings) }}">
                Pay against this cheque to {{ $chequeData['payee'] }}
            </div>
        @endif

        @if(($settings['amount_in_words']['visible'] ?? true) && !empty($chequeData['amount_in_words']))
            <div class="cheque-element amount-in-words" style="{{ getElementStyle('amount_in_words', $settings) }}">
                **{{ $chequeData['amount_in_words'] }}**
            </div>
        @endif

        @if(($settings['amount_in_numbers']['visible'] ?? true) && !empty($chequeData['amount']))
            <div class="cheque-element amount-in-numbers" style="{{ getElementStyle('amount_in_numbers', $settings) }}">
                <strong>₦ {{ number_format($chequeData['amount'], 2, '.', ',') }}</strong>
            </div>
        @endif

        @if(($settings['signature']['visible'] ?? true))
            <div class="cheque-element signature" style="{{ getElementStyle('signature', $settings) }}">
                @if(!empty($chequeData['signature']))
                    {{ $chequeData['signature'] }}
                @else
                    ________________
                @endif
            </div>
        @endif

        @if(($settings['account_number']['visible'] ?? true) && !empty($chequeData['account_number']))
            <div class="cheque-element account-number" style="{{ getElementStyle('account_number', $settings) }}">
                {{ $chequeData['account_number'] }}
            </div>
        @endif

        @if($useTemplate)
            <div class="cheque-footer">
                GOOD AFTER TWO MONTHS
            </div>
        @endif
    </div>
</body>

</html>

