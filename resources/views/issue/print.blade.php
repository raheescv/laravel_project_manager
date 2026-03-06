<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Note - #{{ $model->id }}</title>
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
    <script src="{{ asset('assets/js/signature_pdf.js') }}"></script>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: "Segoe UI", Arial, sans-serif;
            background: #eef1f5;
            margin: 0;
            padding: 24px;
            color: #111827;
            line-height: 1.35;
        }

        .print-page {
            max-width: 860px;
            margin: 0 auto;
            background: #fff;
            padding: 28px 30px 24px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            box-shadow: 0 8px 24px rgba(17, 24, 39, 0.08);
        }

        .company-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 14px;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 10px;
        }

        .company-header img {
            max-height: 72px;
            max-width: 220px;
            object-fit: contain;
            display: block;
        }

        .company-name {
            font-size: 22px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 4px;
        }

        .doc-kicker {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #64748b;
            margin-bottom: 4px;
        }

        .doc-heading {
            font-size: 30px;
            line-height: 1;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: 0.02em;
        }

        .doc-meta {
            margin-top: 6px;
            font-size: 12px;
            color: #334155;
            display: grid;
            gap: 2px;
            text-align: right;
        }

        .doc-table,
        .items-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            border: 1px solid #111827;
        }

        .doc-table td,
        .items-table th,
        .items-table td {
            border: 1px solid #111827;
            padding: 8px 10px;
            font-size: 14px;
        }

        .doc-label {
            width: 130px;
            font-weight: 700;
            text-transform: uppercase;
            text-align: left;
            color: #334155;
        }

        .doc-value {
            text-align: left;
            font-weight: 500;
        }

        .intro-text {
            margin: 12px 0 8px;
            font-size: 13px;
            font-style: normal;
            font-weight: 500;
            color: #1f2937;
        }

        .items-table th {
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            height: 40px;
            background: #f8fafc;
        }

        .items-table td {
            height: 34px;
            font-size: 13.5px;
        }

        .items-table tbody {
            display: block;
            height: 320px;
            overflow: hidden;
        }

        .items-table thead,
        .items-table tbody tr {
            display: table;
            width: 100%;
            table-layout: fixed;
        }

        .items-table caption {
            caption-side: top;
            text-align: left;
            padding: 0 0 8px 0;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #64748b;
            font-weight: 700;
        }

        .col-sn {
            width: 18px;
            text-align: left;
            font-weight: 600;
        }

        .col-ref {
            width: 170px;
            text-align: left;
            font-weight: 600;
        }

        .col-qty {
            width: 30px;
            text-align: right;
            font-weight: 700;
            padding-right: 12px !important;
        }

        .col-desc {
            text-align: left;
        }

        .remarks-section {
            margin-top: 14px;
            display: flex;
            gap: 10px;
            align-items: flex-start;
        }

        .remarks-label {
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            padding-top: 8px;
            color: #334155;
            letter-spacing: 0.04em;
        }

        .remarks-content {
            flex: 1;
        }

        .remarks-box {
            border: 1px solid #111827;
            min-height: 42px;
            width: 100%;
            margin-bottom: 8px;
            padding: 7px 10px;
            font-size: 13px;
        }

        .remarks-line {
            border-bottom: 1px solid #94a3b8;
            height: 22px;
            margin-bottom: 6px;
        }

        .approval-section {
            margin-top: 26px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
        }

        .approval-meta {
            width: 50%;
        }

        .meta-label {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            color: #334155;
            margin-bottom: 4px;
        }

        .section-title {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            color: #334155;
            margin-bottom: 4px;
        }

        .meta-value {
            font-size: 13px;
            color: #111827;
            margin: 0 0 14px;
        }

        .approval-signature {
            width: 45%;
            text-align: right;
        }

        .signature-box {
            margin-top: 10px;
            border: 1px dashed #cbd5e1;
            padding: 10px;
            min-height: 100px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 220px;
            background: #fff;
        }

        .signature-image {
            max-height: 80px;
            max-width: 100%;
        }

        .signature-placeholder {
            color: #94a3b8;
            font-style: italic;
            font-size: 12px;
        }

        .doc-footer {
            margin-top: 22px;
            border-top: 1px dashed #94a3b8;
            padding-top: 8px;
            font-size: 11px;
            color: #64748b;
            display: flex;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }

        @media print {
            @page {
                size: A4;
                margin: 8mm;
            }

            body {
                background: #fff;
                padding: 0;
            }

            .print-page {
                border: none;
                padding: 0;
                max-width: 100%;
                border-radius: 0;
                box-shadow: none;
            }

            /* Keep content within a single A4 page while printing */
            .items-table tbody {
                height: 220px;
            }

            .approval-section {
                margin-top: 18px;
            }

            .doc-footer {
                margin-top: 14px;
            }
        }
    </style>
</head>

<body>
    @php
        $titleType = strtoupper($model->type);
        $preparedBy = $model->createdBy?->name;
        $updatedBy = $model->updatedBy?->name;
    @endphp
    <div class="print-page">
        <div class="company-header">
            <div>
                @if (cache('logo'))
                    <img src="{{ cache('logo') }}" alt="Logo">
                @endif
                <div class="company-name">{{ cache('company_name') ?? config('app.name') }}</div>
            </div>
            <div>
                <div class="doc-heading">Delivery Note</div>
                <div class="doc-meta">
                    <div><strong>Ref:</strong> #{{ $model->id }}</div>
                    <div><strong>Date:</strong> {{ systemDate($model->date) }}</div>
                    <div><strong>Type:</strong> {{ $titleType }}</div>
                    @if ($model->type === 'return' && $model->source_issue_id)
                        <div><strong>Source Issue:</strong> #{{ $model->source_issue_id }}</div>
                    @endif
                </div>
            </div>
        </div>

        <table class="doc-table">
            <tr>
                <td class="doc-label">FROM:</td>
                <td class="doc-value">{{ cache('company_name') ?? config('app.name') }}</td>
            </tr>
            <tr>
                <td class="doc-label">TO:</td>
                <td class="doc-value">{{ $model->account?->name ?? '' }}</td>
            </tr>
        </table>

        <div class="intro-text">Please find below the items delivered under this note.</div>

        <table class="items-table">
            <caption>Item Details</caption>
            <thead>
                <tr>
                    <th class="col-sn">SN</th>
                    @if ($model->type === 'return')
                        {{-- <th class="col-ref">Src Ord</th> --}}
                        {{-- <th class="col-ref">Src Item</th> --}}
                    @endif
                    <th class="col-ref">Item</th>
                    <th class="col-qty">Quantity</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($model->items as $item)
                    <tr>
                        <td class="col-sn">{{ $loop->iteration }}</td>
                        @if ($model->type === 'return')
                            {{-- <td class="col-ref">{{ $item->source_item_order ?? '-' }}</td> --}}
                            {{-- <td class="col-ref">#{{ $item->source_issue_item_id ?? '-' }}</td> --}}
                        @endif
                        <td class="col-ref">{{ $item?->product?->name ?? '' }}</td>
                        <td class="col-qty">
                            @if ($model->type === 'issue')
                                {{ number_format($item->quantity_out, 2) }}
                            @else
                                {{ number_format($item->quantity_in, 2) }}
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="remarks-section">
            <div class="remarks-label">REMARKS:</div>
            <div class="remarks-content">
                <div class="remarks-box">{{ $model->remarks ?? '' }}</div>
            </div>
        </div>

        <div class="approval-section">
            <div class="approval-meta">
                <div class="meta-label">Created By</div>
                <p class="meta-value">{{ $preparedBy ?? '-' }}</p>
                <div class="meta-label">Last Updated At</div>
                <p class="meta-value">{{ systemDateTime($model->updated_at) }}</p>
            </div>
            <div class="approval-signature">
                <div class="meta-label">Approved By</div>
                <p class="meta-value">{{ $updatedBy ?? '-' }}</p>
                <div class="signature-box">
                    @if ($model->signature)
                        <img src="{{ public_path('storage/' . $model->signature) }}" alt="Signature" class="signature-image">
                    @else
                        <span class="signature-placeholder">No signature</span>
                    @endif
                </div>
            </div>
        </div>
        @if (!$model->signature)
            <div class="row">
                @livewire('issue.sign', ['model' => $model])
            </div>
        @endif

        <div class="doc-footer">
            <span>This is a system-generated delivery note.</span>
            <span>Printed on: {{ now()->format('d M Y, h:i A') }}</span>
        </div>
    </div>
</body>

</html>
