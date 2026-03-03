<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cutting Slips</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        @page {
            size: A4 landscape;
            margin: 8mm;
        }

        html,
        body {
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            background: #fff;
            color: #111;
            font-size: 14px;
            line-height: 1.35;
            font-weight: 700;
            text-rendering: geometricPrecision;
        }

        body {
            padding: 10px;
        }

        .sheet {
            width: 281mm;
            min-height: 194mm;
            max-height: 194mm;
            border: 1.6px solid #111;
            padding: 10px 12px;
            overflow: hidden;
        }

        .sheet-break {
            margin-bottom: 12px;
            page-break-after: always;
        }

        .header {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            border-bottom: 2.2px solid #111;
            padding-bottom: 6px;
        }

        .header td {
            vertical-align: top;
        }

        .left-title {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 2px;
        }

        .subtle {
            color: #111;
            font-size: 12px;
        }

        .center-title {
            text-align: center;
            font-size: 15px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin-top: 2px;
        }

        .right-meta {
            text-align: right;
            font-size: 10px;
            line-height: 1.5;
        }

        .section-title {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            font-weight: 700;
            margin-bottom: 5px;
            color: #1f2937;
        }

        .measure-grid {
            display: flex;
            flex-wrap: wrap;
            align-items: stretch;
            gap: 5px;
            margin-bottom: 8px;
        }

        .measure-cell {
            border: 1.4px solid #111;
            background: #fff;
            padding: 5px 6px;
            min-height: 28px;
            display: flex;
            align-items: center;
            width: max-content;
            max-width: 100%;
        }

        .measure-line {
            display: flex;
            align-items: center;
            gap: 4px;
            white-space: nowrap;
            font-size: 14px;
        }

        .measure-label {
            text-transform: uppercase;
            color: #111;
            font-weight: 700;
            flex: 0 0 auto;
            font-size: 11px;
        }

        .measure-value {
            font-weight: 700;
            color: #111;
            flex: 0 0 auto;
            white-space: nowrap;
            font-size: 15px;
        }

        .measure-line--stacked {
            flex-direction: column;
            align-items: flex-start;
            gap: 2px;
        }

        .std-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-bottom: 8px;
            border: 1.6px solid #111;
        }

        .std-table th {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: #fff;
            border-bottom: 1.4px solid #111;
            border-right: 1.2px solid #111;
            padding: 5px 4px;
            text-align: left;
        }

        .std-table td {
            border-top: 1.2px solid #111;
            border-right: 1.2px solid #111;
            padding: 5px 4px;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
            font-weight: 700;
        }

        .std-table th:last-child,
        .std-table td:last-child {
            border-right: none;
        }

        .ref-table {
            table-layout: fixed;
        }

        .ref-table th,
        .ref-table td {
            padding: 6px 7px;
            font-size: 13px;
            vertical-align: top;
            line-height: 1.25;
            white-space: normal;
        }

        .ref-table th {
            font-size: 12px;
            overflow-wrap: break-word;
            word-break: normal;
            hyphens: auto;
        }

        .ref-table td {
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        .ref-col-item {
            width: 8%;
            text-align: center;
            white-space: nowrap;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .bold {
            font-weight: 700;
        }

        .notes-box {
            border: 1.4px dashed #111;
            background: #fff;
            padding: 6px 8px;
            min-height: 26px;
            margin-bottom: 8px;
            font-weight: 700;
        }

        .meta-strip {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .meta-strip td {
            border: 1.4px solid #111;
            padding: 5px 6px;
            font-size: 13px;
            width: 25%;
            font-weight: 700;
        }

        .bottom {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }

        .bottom td {
            vertical-align: top;
            width: 50%;
            padding-right: 10px;
        }

        .line-field {
            margin-bottom: 7px;
            font-size: 13px;
        }

        .line-value {
            border-bottom: 1.2px dashed #111;
            display: inline-block;
            min-width: 180px;
            padding-left: 5px;
            font-weight: 700;
        }

        .personnel-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
        }

        .personnel-lines {
            flex: 1 1 auto;
            min-width: 0;
        }

        .checklist {
            margin-top: 10px;
            font-size: 12px;
            display: inline-block;
        }

        .checklist span {
            margin-right: 18px;
            font-weight: 700;
        }

        .check-item {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-right: 18px;
        }

        .check-input {
            width: 13px;
            height: 13px;
            margin: 0;
            accent-color: #000;
            vertical-align: middle;
            border: 1.4px solid #111;
        }

        .rating-box {
            flex: 0 0 210px;
            width: 210px;
            margin-top: 10px;
            text-align: center;
        }

        .rating-box .label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #111;
            font-weight: 700;
            margin-bottom: 3px;
        }

        .rating-box .square {
            display: none;
        }

        .rating-stars {
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        .rating-star {
            font-size: 24px;
            line-height: 1;
            color: #111;
            font-weight: 700;
        }

        .signatures {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .signatures td {
            width: 50%;
            vertical-align: bottom;
            padding-top: 16px;
        }

        .sign-line {
            border-top: 1.4px solid #111;
            margin: 0 20px;
            padding-top: 5px;
            text-align: center;
            font-size: 13px;
            color: #111;
            text-transform: uppercase;
            font-weight: 700;
        }

        .no-print {
            text-align: center;
            margin-top: 14px;
        }

        .no-print button {
            border: none;
            background: #111827;
            color: #fff;
            font-size: 13px;
            font-weight: 700;
            padding: 9px 20px;
            border-radius: 4px;
            cursor: pointer;
        }

        @media print {
            body {
                padding: 0;
            }

            .sheet {
                border: none;
                width: 281mm;
                min-height: 194mm;
                max-height: 194mm;
                padding: 0;
                overflow: hidden;
            }

            .sheet-break {
                margin-bottom: 0;
            }

            .no-print {
                display: none !important;
            }

            .measure-grid {
                gap: 4px;
            }
        }
    </style>
</head>

<body>
    @foreach ($slips as $slip)
        @php
            $order = $slip['order'];
            $items = $slip['items'];
            $sheetClass = $loop->last ? '' : 'sheet-break';
        @endphp

        @include('print.tailoring.partials.cutting-slip-sheet')
    @endforeach

    <div class="no-print">
        <button onclick="window.print()">Print Cutting Slips</button>
    </div>
</body>

</html>
