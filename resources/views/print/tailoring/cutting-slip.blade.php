<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cutting Slip - {{ $order->order_no }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #fff;
            color: #000;
        }

        .print-container {
            width: 900px;
            margin: auto;
            border: 1px solid #000;
            padding: 25px;
            box-sizing: border-box;
            background: #fff;
        }

        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }

        .header-left h1 { margin: 0; font-size: 1.6rem; text-transform: uppercase; }
        .header-left p { margin: 2px 0; font-size: 1rem; font-weight: bold; color: #333; }
        .header-center { text-align: center; }
        .header-center h2 { margin: 0; font-size: 1.2rem; border-bottom: 1px solid #000; display: inline-block; padding-bottom: 2px; }
        .header-right { text-align: right; font-size: 0.9rem; font-weight: bold; line-height: 1.4; }

        .measure-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 8px;
            margin-bottom: 12px;
        }

        .measure-box {
            border: 1px solid #000;
            border-radius: 6px;
            display: flex;
            align-items: center;
            height: 35px;
            overflow: hidden;
        }

        .measure-val { 
            width: 40%; 
            background: #f0f0f0;
            border-right: 1px solid #000; 
            height: 100%; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-weight: bold; 
            font-size: 1rem;
        }
        
        .measure-label { 
            width: 60%; 
            padding-left: 8px; 
            font-weight: bold; 
            font-size: 0.75rem;
            color: #444;
        }

        .slip-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 0.85rem;
        }

        .slip-table th {
            border: 1px solid #000;
            padding: 10px 5px;
            background-color: #f2f2f2;
            text-transform: capitalize;
            font-weight: bold;
        }

        .slip-table td {
            border: 1px solid #000;
            padding: 10px 8px;
            text-align: center;
        }

        .col-desc { text-align: left !important; width: 25%; font-weight: bold; }
        .highlight-yellow { background-color: #ffff00; font-weight: bold; padding: 2px 5px; }

        .info-bar {
            display: grid;
            grid-template-columns: repeat(8, 1fr);
            border: 1px solid #000;
            margin-top: 10px;
            background: #fff;
        }

        .info-bar div {
            padding: 8px 4px;
            text-align: center;
            border-right: 1px solid #000;
            font-size: 0.7rem;
            font-weight: bold;
            background-color: #f9f9f9;
        }

        .info-values {
            display: grid;
            grid-template-columns: repeat(8, 1fr);
            border: 1px solid #000;
            border-top: none;
            margin-bottom: 20px;
            min-height: 30px;
        }

        .info-values div {
            border-right: 1px solid #000;
            padding: 5px;
            text-align: center;
            font-weight: bold;
        }

        .bottom-section {
            display: flex;
            gap: 30px;
            margin-top: 20px;
        }

        .field-row {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            font-size: 0.9rem;
            font-weight: bold;
        }

        .field-input {
            flex: 1;
            border-bottom: 1px dashed #000;
            margin-left: 10px;
            min-height: 22px;
        }

        .qr-placeholder {
            width: 110px;
            height: 110px;
            border: 1px solid #eee;
            padding: 5px;
        }

        .signature-row {
            display: flex;
            justify-content: space-between;
            margin-top: 50px;
            padding-top: 10px;
            border-top: 1px solid #eee;
            font-size: 0.9rem;
            font-weight: bold;
        }

        @media print {
            body { padding: 0; }
            .print-container { border: 1px solid #000; width: 100%; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

@php
    $firstItem = $order->items->first();
@endphp

<div class="print-container">
    <div class="header">
        <div class="header-left">
            <p>{{ $order->customer_mobile ?? 'No Mobile' }}</p>
            <h1>{{ $order->customer_name }}</h1>
            <p>ID: {{ $order->order_no }}</p>
        </div>
        <div class="header-center">
            <h2>{{ strtoupper($firstItem->category->name ?? 'TAILORING') }} CUTTING SLIP</h2>
        </div>
        <div class="header-right">
            Order Date: {{ $order->order_date ? $order->order_date->format('d/m/Y') : '-' }}<br>
            Delivery Date: {{ $order->delivery_date ? $order->delivery_date->format('d/m/Y') : '-' }}
        </div>
    </div>

    <div class="measure-grid">
        <div class="measure-box"><div class="measure-val">{{ $firstItem->length }}</div><div class="measure-label">Length</div></div>
        <div class="measure-box"><div class="measure-val">{{ $firstItem->shoulder }}</div><div class="measure-label">(Shoulder)</div></div>
        <div class="measure-box"><div class="measure-val">{{ $firstItem->sleeve }}</div><div class="measure-label">(Sleeve)</div></div>
        <div class="measure-box"><div class="measure-val">{{ $firstItem->chest }}</div><div class="measure-label">(Chest)</div></div>
        <div class="measure-box"><div class="measure-val">{{ $firstItem->stomach }}</div><div class="measure-label">(Stomach)</div></div>
        <div class="measure-box"><div class="measure-val">{{ $firstItem->sl_chest }}</div><div class="measure-label">(S-L Chest)</div></div>
    </div>

    <div class="measure-grid">
        <div class="measure-box"><div class="measure-val">{{ $firstItem->mar_size }}</div><div class="measure-label">Mar Size</div></div>
        <div class="measure-box"><div class="measure-val">{{ $firstItem->regal_size }}</div><div class="measure-label">(Regal Size)</div></div>
        <div class="measure-box"><div class="measure-val">{{ $firstItem->knee_loose }}</div><div class="measure-label">(Knee Loose)</div></div>
        <div class="measure-box"><div class="measure-val">{{ $firstItem->fp_down }}</div><div class="measure-label">(FP Down)</div></div>
        <div class="measure-box"><div class="measure-val">{{ $firstItem->bottom }}</div><div class="measure-label">(Bottom)</div></div>
        <div class="measure-box"><div class="measure-val">{{ $firstItem->neck }}</div><div class="measure-label">(Neck)</div></div>
    </div>

    <div class="measure-grid" style="grid-template-columns: 2fr 1fr 1fr;">
        <div class="field-row" style="margin-bottom: 0;">Notes: <div class="field-input">{{ $firstItem->tailoring_notes }}</div></div>
        <div class="measure-box"><div class="measure-val">{{ $firstItem->fp_size }}</div><div class="measure-label">(FP Size)</div></div>
        <div class="measure-box"><div class="measure-val">{{ $firstItem->neck_d_button }}</div><div class="measure-label">(Neck D Bottom)</div></div>
    </div>

    <table class="slip-table">
        <thead>
            <tr>
                <th class="col-desc">Description</th>
                <th>Barcode</th>
                <th>Qty</th>
                <th>Type</th>
                <th>Model</th>
                <th>Color</th>
                <th>Used</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
            <tr>
                <td class="col-desc">{{ $item->product_name }}</td>
                <td>{{ $item->product->barcode ?? '-' }}</td>
                <td>{{ number_format($item->quantity, 1) }}</td>
                <td>{{ $item->category->name ?? '-' }}</td>
                <td><span class="highlight-yellow">{{ $item->categoryModel->name ?? 'Standard' }}</span></td>
                <td>{{ $item->product_color ?? '-' }}</td>
                <td></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="info-bar">
        <div>MAR Model</div>
        <div>FP Model</div>
        <div>Pen</div>
        <div>Mobile Pocket</div>
        <div>Button No.</div>
        <div>Side PT Model</div>
        <div>Side PT Size</div>
        <div style="border-right:none">Cuff</div>
    </div>
    <div class="info-values">
        <div>{{ $firstItem->mar_model ?? '-' }}</div>
        <div>{{ $firstItem->fp_model ?? '-' }}</div>
        <div>{{ $firstItem->pen ?? '-' }}</div>
        <div>{{ $firstItem->mobile_pocket ?? '-' }}</div>
        <div>{{ $firstItem->button_no ?? '-' }}</div>
        <div>{{ $firstItem->side_pt_model ?? '-' }}</div>
        <div>{{ $firstItem->side_pt_size ?? '-' }}</div>
        <div style="border-right:none">{{ $firstItem->cuff ?? '-' }}</div>
    </div>

    <div class="bottom-section">
        <div style="flex: 2;">
            <div class="field-row">Cutting Master: <div class="field-input">{{ $order->cutter->name ?? '' }}</div></div>
            <div class="field-row">Tailor Name: <div class="field-input">{{ $firstItem->tailor->name ?? '' }}</div></div>
            <div class="field-row" style="margin-top: 20px; gap: 40px;">
                <label><input type="checkbox" {{ $order->status == 'confirmed' ? 'checked' : '' }}> Booking</label>
                <label><input type="checkbox" {{ $order->status == 'completed' ? 'checked' : '' }}> Finished</label>
                <label><input type="checkbox" {{ $order->status == 'delivered' ? 'checked' : '' }}> Delivered</label>
            </div>
        </div>
        <div style="flex: 1; text-align: right;">
            <div class="field-row">Remarks: <div class="field-input"></div></div>
            <div style="display: inline-block; margin-top: 10px;">
                <div class="qr-placeholder">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data={{ $order->order_no }}-{{ $categoryId }}" alt="QR Code" width="100">
                </div>
            </div>
        </div>
    </div>

    <div class="signature-row">
        <div>Prepared By: {{ $order->salesman->name ?? '' }}</div>
        <div>Approved By: {{ $order->customer_name }}</div>
    </div>
</div>

<div class="no-print" style="text-align: center; margin-top: 20px; padding-bottom: 40px;">
    <button onclick="window.print()" style="padding: 12px 30px; cursor: pointer; background: #000; color: #fff; border: none; border-radius: 5px; font-weight: bold; font-size: 1rem;">Print Cutting Slip</button>
</div>

</body>
</html>
