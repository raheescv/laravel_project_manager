<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Issue / Return - #{{ $model->id }}</title>
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f0f2f5;
            padding: 2rem;
        }

        .issue-card {
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

        @media print {
            body { background: #fff; padding: 0; }
            .issue-card { box-shadow: none; }
        }
    </style>
</head>

<body>
    <div class="issue-card">
        <div class="text-center mb-4">
            <div class="form-heading">Issue / Return</div>
            <div class="form-subtitle">Product Issue & Return Record</div>
        </div>

        <table class="table table-bordered table-sm text-center meta-table mb-4">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Issue No</th>
                    <th>Balance (Out − In)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><b>{{ $model->account?->name }}</b></td>
                    <td><b>#{{ $model->id }}</b></td>
                    <td><b>{{ number_format($model->balance ?? 0, 2) }}</b></td>
                </tr>
            </tbody>
        </table>

        @if ($model->account?->mobile)
            <p class="section-title mb-1">Contact</p>
            <p class="fw-semibold mb-3">{{ $model->account->mobile }}</p>
        @endif

        @if ($model->remarks)
            <p class="section-title mb-1">Remarks</p>
            <p class="fw-semibold mb-3">{{ $model->remarks }}</p>
        @endif

        <table class="table table-sm table-striped table-bordered">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Product</th>
                    <th class="text-end">Code</th>
                    <th>Date</th>
                    <th class="text-end">Qty Out</th>
                    <th class="text-end">Qty In</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($model->items as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $item->product?->name }}</td>
                        <td class="text-end">{{ $item->product?->code ?? '-' }}</td>
                        <td>{{ systemDate($item->date) }}</td>
                        <td class="text-end">{{ number_format($item->quantity_out, 2) }}</td>
                        <td class="text-end">{{ number_format($item->quantity_in, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="4" class="text-end">Total</th>
                    <th class="text-end"><b>{{ number_format($model->items->sum('quantity_out'), 2) }}</b></th>
                    <th class="text-end"><b>{{ number_format($model->items->sum('quantity_in'), 2) }}</b></th>
                </tr>
            </tfoot>
        </table>

        <div class="mt-4 pt-3 border-top small text-muted">
            <p class="mb-0">Printed on {{ now()->format('d M Y H:i') }}</p>
        </div>
    </div>
</body>

</html>
