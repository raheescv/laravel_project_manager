<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <title>{{ $mode }} - #{{ $supplyRequest->order_no }}</title>
</head>

<body>
    <style>
        * {
            font-family: 'DejaVu Sans', 'Arial', sans-serif !important;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', 'Arial', sans-serif;
            margin: 0;
            padding: 3px;
            font-size: 11px;
            line-height: 1.2;
            color: #000;
            background: #fff;
        }

        .container {
            max-width: 200mm;
            margin: 0 auto;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .content-area {
            width: 100%;
            flex: 1;
        }

        /* Header */
        .header {
            border-bottom: 3px solid #000;
            padding-bottom: 8px;
            margin-bottom: 8px;
            text-align: center;
        }

        .logo-section img {
            max-width: 150px;
            max-height: 60px;
            margin-bottom: 8px;
        }

        .company-title {
            font-size: 16px;
            font-weight: bold;
            margin: 5px 0;
            color: #000;
        }

        .document-title {
            background: #000;
            color: #fff;
            padding: 5px 8px;
            margin: 5px 0 0 0;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .section-header {
            background: #000;
            color: #fff;
            padding: 3px 6px;
            font-weight: bold;
            font-size: 12px;
            margin: 8px 0 3px 0;
            text-transform: uppercase;
        }

        /* Grid Layouts */
        .meta-grid,
        .signature-grid {
            display: table;
            width: 100%;
            border: 1px solid #000;
        }

        .meta-grid {
            margin-bottom: 8px;
        }

        .signature-grid {
            margin-top: auto;
            flex-shrink: 0;
        }

        .meta-row,
        .signature-row {
            display: table-row;
        }

        .meta-cell,
        .signature-cell {
            display: table-cell;
            padding: 3px 5px;
            border-right: 1px solid #ccc;
            border-bottom: 1px solid #ccc;
            vertical-align: top;
        }

        .meta-cell:last-child,
        .signature-cell:last-child {
            border-right: none;
        }

        .meta-cell.label {
            background: #f5f5f5;
            font-weight: bold;
            width: 25%;
            color: #000;
        }

        .meta-cell.value {
            width: 25%;
            color: #333;
        }

        .signature-cell {
            width: 25%;
            height: 60px;
            text-align: center;
            vertical-align: bottom;
        }

        .signature-label {
            font-weight: bold;
            border-top: 1px solid #000;
            padding-top: 3px;
            margin-top: 20px;
            font-size: 9px;
        }

        /* Tables */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
            font-size: 10px;
        }

        .data-table th {
            background: #000;
            color: #fff;
            padding: 3px 4px;
            text-align: center;
            font-weight: bold;
            border: 1px solid #000;
            font-size: 10px;
        }

        .data-table td {
            padding: 2px 4px;
            border: 1px solid #ccc;
            vertical-align: top;
        }

        .data-table tr:nth-child(even) {
            background: #f9f9f9;
        }

        .inventory-table td:nth-child(1) { width: 5%; text-align: center; }
        .inventory-table td:nth-child(2) { width: 45%; }
        .inventory-table td:nth-child(3) { width: 10%; text-align: center; }
        .inventory-table td:nth-child(4) { width: 8%; text-align: right; }
        .inventory-table td:nth-child(5) { width: 12%; text-align: right; }
        .inventory-table td:nth-child(6) { width: 12%; text-align: right; }

        .totals-row { background: #f5f5f5; font-weight: bold; }
        .totals-row td { border-top: 2px solid #000; }

        .compact-section { margin-bottom: 8px; }

        .remarks-section,
        .final-notice {
            border: 1px solid #000;
            padding: 4px;
            margin: 5px 0;
            font-size: 9px;
            line-height: 1.3;
            page-break-inside: avoid;
        }

        .remarks-section { background: #f9f9f9; }
        .remarks-section .label { font-weight: bold; margin-bottom: 5px; font-size: 10px; }

        .arabic { text-align: right; direction: rtl; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-bold { font-weight: bold; }

        @media print {
            body { margin: 0; padding: 3px; font-size: 10px; }
            .container { margin: 0; padding: 0; min-height: 90vh; display: flex; flex-direction: column; }
            .content-area { flex: 1; padding-bottom: 130px; }
            .signature-grid {
                position: fixed !important; bottom: 0 !important; left: 0 !important; right: 0 !important;
                width: 100% !important; height: 120px !important; display: table !important;
                border: 1px solid #000 !important; background: #fff !important;
                margin: 0 !important; z-index: 9999 !important; page-break-inside: avoid !important;
                -webkit-print-color-adjust: exact; print-color-adjust: exact; flex-shrink: 0 !important;
            }
            .signature-cell {
                background: #fff !important; display: table-cell !important; width: 25% !important;
                padding: 15px 5px !important; border-right: 1px solid #ccc !important;
                text-align: center !important; vertical-align: bottom !important; height: 100px !important;
                position: relative !important;
            }
            .signature-cell:last-child { border-right: none !important; }
            .signature-label {
                font-weight: bold !important; border-top: 1px solid #000 !important; padding-top: 5px !important;
                font-size: 10px !important; position: absolute !important; bottom: 15px !important;
                left: 5px !important; right: 5px !important;
            }
            .meta-grid { page-break-inside: avoid; }
            @page { margin: 10mm; size: A4; }
        }
    </style>

    <div class="container">
        <div class="content-area">
            <!-- Header Section -->
            <div class="header">
                <div class="logo-section">
                    <img src="{{ $companyLogo }}" alt="Company Logo">
                </div>
                <div class="company-title">{{ $companyName }}</div>
                <div class="document-title">
                    @if ($mode !== 'Invoice')
                        Work Order Form
                    @else
                        {{ $mode }}
                    @endif
                </div>
            </div>

            <!-- Meta Information -->
            <div class="meta-grid">
                <div class="meta-row">
                    <div class="meta-cell label">Department</div>
                    <div class="meta-cell value">Facility Management</div>
                    <div class="meta-cell label">Date</div>
                    <div class="meta-cell value">{{ systemDate($supplyRequest->date) }}</div>
                </div>
                <div class="meta-row">
                    <div class="meta-cell label">Requested By</div>
                    <div class="meta-cell value">{{ $supplyRequest->contact_person }}</div>
                    @if ($rentout)
                        <div class="meta-cell label">Check In Date</div>
                        <div class="meta-cell value">{{ systemDate($rentout->start_date) }}</div>
                    @else
                        <div class="meta-cell label">Order Number</div>
                        <div class="meta-cell value">{{ $supplyRequest->order_no }}</div>
                    @endif
                </div>
                <div class="meta-row">
                    <div class="meta-cell label">Project/Unit No</div>
                    <div class="meta-cell value">{{ $supplyRequest->property?->number }}</div>
                    <div class="meta-cell label">Place</div>
                    <div class="meta-cell value">{{ $supplyRequest->property?->building?->group?->name }}</div>
                </div>
                <div class="meta-row">
                    <div class="meta-cell label">Building</div>
                    <div class="meta-cell value">{{ $supplyRequest->property?->building?->name }}</div>
                    @if ($rentout)
                        <div class="meta-cell label">Order Number</div>
                        <div class="meta-cell value">{{ $supplyRequest->order_no }}</div>
                    @else
                        <div class="meta-cell label">Type</div>
                        <div class="meta-cell value">{{ $supplyRequest->type }}</div>
                    @endif
                </div>
            </div>

            <!-- Items Section -->
            <div class="section-header">Items Details</div>
            <table class="data-table inventory-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Description of Item</th>
                        <th>Mode</th>
                        <th style="text-align: right;">Qty</th>
                        <th style="text-align: right;">Unit Price</th>
                        <th style="text-align: right;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($supplyRequest->items as $key => $item)
                        <tr>
                            <td>{{ $key + 1 }}</td>
                            <td>{{ $item->product?->name ?? '' }}</td>
                            <td>{{ $item->mode }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ currency($item->unit_price) }}</td>
                            <td>{{ currency($item->total) }}</td>
                        </tr>
                        @if ($item->remarks)
                            <tr>
                                <td colspan="6" style="font-style: italic; background: #f9f9f9;">
                                    <strong>Remarks:</strong> {{ $item->remarks }}
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="totals-row">
                        <td colspan="4"></td>
                        <td class="text-bold">Total</td>
                        <td class="text-bold">{{ currency($supplyRequest->total) }}</td>
                    </tr>
                    <tr class="totals-row">
                        <td colspan="4"></td>
                        <td class="text-bold">Other Charges</td>
                        <td class="text-bold">{{ currency($supplyRequest->other_charges) }}</td>
                    </tr>
                    <tr class="totals-row" style="background: #000; color: #fff;">
                        <td colspan="4"></td>
                        <td class="text-bold">Grand Total</td>
                        <td class="text-bold">{{ currency($supplyRequest->grand_total) }}</td>
                    </tr>
                </tfoot>
            </table>

            <!-- Remarks -->
            @if ($supplyRequest->remarks)
                <div class="remarks-section">
                    <div class="label">Additional Remarks:</div>
                    <div>{{ $supplyRequest->remarks }}</div>
                </div>
            @endif

            <!-- Staff Information (Invoice only) -->
            @if ($mode === 'Invoice')
                <div class="meta-grid compact-section" style="margin-top: 15px;">
                    <div class="meta-row">
                        <div class="meta-cell label">Prepared By</div>
                        <div class="meta-cell value">{{ $supplyRequest->creator?->name }}</div>
                        <div class="meta-cell label">Approved By</div>
                        <div class="meta-cell value">{{ $supplyRequest->approver?->name }}</div>
                    </div>
                    <div class="meta-row">
                        <div class="meta-cell label">Accountant</div>
                        <div class="meta-cell value">{{ $supplyRequest->accountant?->name }}</div>
                        <div class="meta-cell label">Completed By</div>
                        <div class="meta-cell value">{{ $supplyRequest->completer?->name }}</div>
                    </div>
                    <div class="meta-row">
                        <div class="meta-cell label">Last Updated By</div>
                        <div class="meta-cell value">{{ $supplyRequest->updater?->name }}</div>
                        <div class="meta-cell label">Final Approved By</div>
                        <div class="meta-cell value">{{ $supplyRequest->finalApprover?->name }}</div>
                    </div>
                </div>
            @endif

            <!-- Final Notice -->
            <div class="final-notice">
                <p><strong>Dear Valued Tenant,</strong></p>
                <p>
                    The payment of spare parts if any should be made within 5 days, failure to do so it will be deducted from your security deposit accordingly. Your kind consideration and cooperation are highly appreciated.
                </p>
                <div class="arabic" style="direction: rtl; text-align: right;">
                    <p>عزيزنا العميل</p>
                    <p>نود إعلامكم بأن دفع قيمة قطع الغيار إن وجدت يجب ان تتم خلال خمسة أيام من تاريخ اصدار الفاتورة، و في حال عدم السداد خلال هذه المدة : سيتم خصم المبلغ من التأمين الخاص بكم تباعا</p>
                    <p>شاكرين لكم حسن تعاونكم و تفهمكم.</p>
                </div>
            </div>
        </div>

        <!-- Signatures Section -->
        <div class="signature-grid">
            <div class="signature-row">
                <div class="signature-cell">
                    <div class="signature-label">Technician Signature</div>
                </div>
                <div class="signature-cell">
                    <div class="signature-label">Supervisor Signature</div>
                </div>
                <div class="signature-cell">
                    <div class="signature-label">Tenant Signature</div>
                </div>
                <div class="signature-cell">
                    <div class="signature-label">Finance Signature</div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
