<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Purchase Note</title>
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
    <script src="{{ asset('assets/js/signature_pdf.js') }}"></script>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f0f2f5;
            padding: 1rem;
        }

        .purchase-card {
            background: #ffffff;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            max-width: 1000px;
            margin: auto;
        }

        .form-heading {
            font-size: 1.75rem;
            font-weight: 700;
            color: #212529;
            margin-bottom: 0.15rem;
        }

        .form-subtitle {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 1rem;
        }

        .section-title {
            font-size: 0.8rem;
            font-weight: 600;
            color: #6c757d;
            text-transform: uppercase;
            margin-bottom: 0.15rem;
        }

        .meta-table th {
            background-color: #f8f9fa;
            color: #495057;
            padding: 0.5rem;
            font-size: 0.85rem;
        }

        .meta-table td {
            padding: 0.5rem;
            font-size: 0.85rem;
        }

        .table th,
        .table td {
            vertical-align: middle;
            font-size: 0.85rem;
            padding: 0.5rem 0.4rem;
        }

        .table thead th {
            font-size: 0.8rem;
            font-weight: 600;
            padding: 0.5rem 0.4rem;
        }

        .signature-box {
            width: 200px;
            border-bottom: 1px solid #ccc;
            padding-top: 0.5rem;
            margin-left: auto;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="purchase-card">
        <div class="text-center mb-3">
            <div class="form-heading">Purchase Note</div>
            <div class="form-subtitle">Purchase Record</div>
        </div>
        <!-- Purchase Details Table -->
        <table class="table table-bordered table-sm text-center meta-table mb-3">
            <thead>
                <tr>
                    <th>Vendor</th>
                    <th>Invoice No</th>
                    <th>Date</th>
                    <th>Purchase No</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td> <b>{{ $model->account?->name }}</b> </td>
                    <td> <b>{{ $model->invoice_no }}</b> </td>
                    <td> <b>{{ systemDate($model->date) }}</b> </td>
                    <td> <b>{{ $model->id }}</b> </td>
                </tr>
            </tbody>
        </table>
        @if ($model->address)
            <div class="row mb-2">
                <div class="col-md-12">
                    <p class="section-title mb-1">Address</p>
                    <p class="fw-semibold mb-2" style="font-size: 0.85rem;">{{ $model->address }}</p>
                </div>
            </div>
        @endif

        <!-- Product Table -->
        <table class="table table-sm table-striped table-bordered mb-3">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Product</th>
                    <th class="text-end">Barcode</th>
                    <th class="text-end">Unit Price</th>
                    <th class="text-end">Quantity</th>
                    <th class="text-end">Unit</th>
                    <th class="text-end">Discount</th>
                    <th class="text-end">Tax %</th>
                    <th class="text-end">Tax Amount</th>
                    <th class="text-end">Total</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $items = $model->items;
                @endphp
                @foreach ($items as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $item['name'] }}</td>
                        <td class="text-end">{{ $item->product?->barcode ?? '-' }}</td>
                        <td class="text-end">{{ currency($item['unit_price']) }}</td>
                        <td class="text-end">{{ $item['quantity'] }}</td>
                        <td class="text-end">{{ $item->unit?->name }}</td>
                        <td class="text-end">{{ currency($item['discount']) }}</td>
                        <td class="text-end">{{ $item['tax'] }}%</td>
                        <td class="text-end">{{ currency($item['tax_amount']) }}</td>
                        <td class="text-end">{{ currency($item['total']) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot style="border-top: 2px solid #dee2e6;">
                @php
                    $items = collect($items);
                @endphp
                <tr>
                    <th colspan="9" class="text-end" style="padding: 0.4rem;">Gross Amount</th>
                    <th class="text-end" style="padding: 0.4rem;"><b>{{ currency($model->gross_amount) }}</b></th>
                </tr>
                @if ($model->item_discount > 0)
                    <tr>
                        <th colspan="9" class="text-end" style="padding: 0.4rem;">Item Discount</th>
                        <th class="text-end" style="padding: 0.4rem;"><b>{{ currency($model->item_discount) }}</b></th>
                    </tr>
                @endif
                @if ($model->tax_amount > 0)
                    <tr>
                        <th colspan="9" class="text-end" style="padding: 0.4rem;">Tax Amount</th>
                        <th class="text-end" style="padding: 0.4rem;"><b>{{ currency($model->tax_amount) }}</b></th>
                    </tr>
                @endif
                <tr>
                    <th colspan="9" class="text-end" style="padding: 0.4rem;">Total</th>
                    <th class="text-end" style="padding: 0.4rem;"><b>{{ currency($model->total) }}</b></th>
                </tr>
                @if ($model->other_discount > 0)
                    <tr>
                        <th colspan="9" class="text-end" style="padding: 0.4rem;">Other Discount</th>
                        <th class="text-end" style="padding: 0.4rem;"><b>{{ currency($model->other_discount) }}</b></th>
                    </tr>
                @endif
                @if ($model->freight > 0)
                    <tr>
                        <th colspan="9" class="text-end" style="padding: 0.4rem;">Freight</th>
                        <th class="text-end" style="padding: 0.4rem;"><b>{{ currency($model->freight) }}</b></th>
                    </tr>
                @endif
                <tr style="border-top: 2px solid #212529;">
                    <th colspan="9" class="text-end" style="padding: 0.5rem; font-size: 0.95rem;">Grand Total</th>
                    <th class="text-end" style="padding: 0.5rem; font-size: 0.95rem;"><b>{{ currency($model->grand_total) }}</b></th>
                </tr>
            </tfoot>
        </table>

        <!-- Footer Info -->
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-top: 25px; gap: 15px;">
            <div style="width: 50%;">
                <div style="margin-bottom: 12px;">
                    <p style="font-weight: bold; margin-bottom: 2px; font-size: 0.85rem;">🧑 Created By</p>
                    <p style="margin: 0; font-size: 0.85rem;">{{ $model->createdUser?->name ?? '-' }}</p>
                </div>
                <div>
                    <p style="font-weight: bold; margin-bottom: 2px; font-size: 0.85rem;">🕒 Last Updated At</p>
                    <p style="margin: 0; font-size: 0.85rem;">{{ systemDateTime($model->created_at) }}</p>
                </div>
            </div>
            <div style="width: 45%; text-align: right;">
                <div style="margin-bottom: 8px;">
                    <p style="font-weight: bold; margin-bottom: 2px; font-size: 0.85rem;">✅ Approved By</p>
                    <p style="margin: 0; font-size: 0.85rem;">{{ $model->updatedUser?->name ?? '-' }}</p>
                </div>
                <div style="margin-top: 8px; border: 1px dashed #ccc; padding: 8px; min-height: 80px; display: inline-block;">
                    @if ($model->signature)
                        <img src="{{ public_path('storage/' . $model->signature) }}" alt="Signature" style="max-height: 70px;">
                    @else
                        <span style="color: #999; font-style: italic; font-size: 0.8rem;">No signature</span>
                    @endif
                </div>
            </div>
        </div>
        @if (!$model->signature)
            <div class="row">
                @livewire('purchase.sign', ['model' => $model])
            </div>
        @endif
    </div>
</body>

</html>
