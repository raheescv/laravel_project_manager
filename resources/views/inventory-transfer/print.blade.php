<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Stock Transfer Form</title>
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f0f2f5;
            padding: 2rem;
        }

        .transfer-card {
            background: #ffffff;
            padding: 2.5rem;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            max-width: 1000px;
            margin: auto;
        }

        .form-heading {
            font-size: 2rem;
            font-weight: 700;
            color: #212529;
            margin-bottom: 0.25rem;
        }

        .form-subtitle {
            font-size: 0.95rem;
            color: #6c757d;
            margin-bottom: 1.5rem;
        }

        .section-title {
            font-size: 0.9rem;
            font-weight: 600;
            color: #6c757d;
            text-transform: uppercase;
            margin-bottom: 0.25rem;
        }

        .meta-table th {
            background-color: #f8f9fa;
            color: #495057;
        }

        .table th,
        .table td {
            vertical-align: middle;
            font-size: 0.95rem;
        }

        .signature-box {
            width: 200px;
            border-top: 1px solid #ccc;
            padding-top: 0.5rem;
            margin-left: auto;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="transfer-card">
        <div class="text-center mb-4">
            <div class="form-heading">Stock Transfer</div>
            <div class="form-subtitle">Internal Inventory Movement Record</div>
        </div>
        <!-- Transfer Details Table -->
        <table class="table table-bordered text-center meta-table mb-4">
            <thead>
                <tr>
                    <th>From Branch</th>
                    <th>To Branch</th>
                    <th>Date</th>
                    <th>Transfer No</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td> <b>{{ $model->fromBranch?->name }}</b> </td>
                    <td> <b>{{ $model->toBranch?->name }}</b> </td>
                    <td> <b>{{ systemDate($model->date) }}</b> </td>
                    <td> <b>{{ $model->id }}</b> </td>
                </tr>
            </tbody>
        </table>
        @if ($model->description)
            <div class="row">
                <div class="col-md-6">
                    <p class="section-title">Description</p>
                    <p class="fw-semibold mb-3">{{ $model->description }}</p>
                </div>
            </div>
        @endif
        <!-- Product Table -->
        <table class="table table-striped table-bordered">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Product</th>
                    <th>Barcode</th>
                    <th>Batch</th>
                    <th class="text-end">Quantity</th>
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
                        <td>{{ $item->inventory->batch }}</td>
                        <td>{{ $item->inventory->barcode }}</td>
                        <td class="text-end">{{ $item['quantity'] }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                @php
                    $items = collect($items);
                @endphp
                <tr>
                    <th colspan="4" class="text-end">Total</th>
                    <th class="text-end"><b>{{ $items->sum('quantity') }}</b></th>
                </tr>
            </tfoot>
        </table>

        <!-- Footer Info -->
        <div class="row">
            <div class="col-md-6">
                <p class="section-title">Created By</p>
                <p class="fw-semibold mb-3">{{ $model->createdBy->name }}</p>

                <p class="section-title">Last Updated At</p>
                <p class="fw-semibold">{{ systemDateTime($model->created_at) }}</p>
            </div>
            <div class="col-md-6 text-end">
                <p class="section-title">Approved By</p>
                <p class="fw-semibold mb-4">{{ $model->approvedBy?->name }}</p>

                <div class="signature-box">Signature</div>
            </div>
        </div>
    </div>
</body>

</html>
