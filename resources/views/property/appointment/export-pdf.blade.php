<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta charset="UTF-8">
    <title>Appointments</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10px;
            color: #1f2937;
            background: #ffffff;
        }

        .report-shell {
            border: 1px solid #dbe2ea;
            border-radius: 10px;
            overflow: hidden;
        }

        .header {
            padding: 14px 16px 10px;
            background: #f8fafc;
            border-bottom: 1px solid #e5e7eb;
        }

        .header-top {
            width: 100%;
            border-collapse: collapse;
        }

        .header-top td {
            border: none;
            padding: 0;
            vertical-align: top;
        }

        .eyebrow {
            font-size: 11px;
            color: #64748b;
            font-weight: 700;
            letter-spacing: 0.4px;
            text-transform: uppercase;
            margin-bottom: 3px;
        }

        .title {
            font-size: 18px;
            color: #0f172a;
            font-weight: 700;
            line-height: 1.2;
        }

        .meta {
            text-align: right;
            font-size: 9px;
            color: #475569;
            line-height: 1.6;
        }

        .filters {
            padding: 10px 16px 6px;
            border-bottom: 1px solid #e5e7eb;
        }

        .chip {
            display: inline-block;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            border-radius: 999px;
            padding: 3px 8px;
            margin: 0 6px 6px 0;
            font-size: 8.5px;
            color: #334155;
        }

        .chip b {
            color: #0f172a;
        }

        .table-wrap {
            padding: 12px 16px;
        }

        table.list {
            width: 100%;
            border-collapse: collapse;
        }

        table.list thead {
            display: table-header-group;
        }

        table.list tr {
            page-break-inside: avoid;
        }

        table.list th {
            background: #1e293b;
            color: #ffffff;
            border: 1px solid #d1d5db;
            padding: 6px 5px;
            text-align: left;
            font-size: 8.5px;
            font-weight: 700;
        }

        table.list td {
            border: 1px solid #e5e7eb;
            padding: 5px;
            font-size: 8.5px;
            vertical-align: middle;
        }

        table.list tbody tr:nth-child(even) td {
            background: #f8fafc;
        }

        .dim {
            color: #64748b;
        }

        .status {
            display: inline-block;
            border: 1px solid #cbd5e1;
            border-radius: 999px;
            padding: 2px 6px;
            font-size: 8px;
            color: #334155;
            background: #f8fafc;
            white-space: nowrap;
        }

        .status-scheduled { border-color: #93c5fd; background: #eff6ff; color: #1d4ed8; }
        .status-completed { border-color: #86efac; background: #f0fdf4; color: #15803d; }
        .status-cancelled { border-color: #fca5a5; background: #fef2f2; color: #b91c1c; }
        .status-no_show { border-color: #fcd34d; background: #fffbeb; color: #b45309; }

        .footer {
            padding: 0 16px 12px;
            font-size: 8px;
            color: #64748b;
            text-align: right;
        }
    </style>
</head>

<body>
    <div class="report-shell">
        <div class="header">
            <table class="header-top">
                <tr>
                    <td>
                        <div class="eyebrow">RentOut &middot; Sale</div>
                        <div class="title">Appointments</div>
                    </td>
                    <td class="meta">
                        <div><b>Generated:</b> {{ now()->format('d M Y h:i A') }}</div>
                        <div><b>Rows:</b> {{ number_format($appointments->count()) }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="filters">
            @foreach ($filterLabels as $label => $value)
                <span class="chip"><b>{{ $label }}:</b> {{ $value }}</span>
            @endforeach
        </div>

        <div class="table-wrap">
            <table class="list">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Reference</th>
                        <th>When</th>
                        <th>Customer</th>
                        <th>Mobile</th>
                        <th>Property</th>
                        <th>Agreement</th>
                        <th>Employee</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($appointments as $appointment)
                        @php($endsAt = $appointment->endsAt())
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $appointment->reference_no }}</td>
                            <td>
                                @if ($appointment->scheduled_at)
                                    {{ $appointment->scheduled_at->format('D d M Y') }}
                                    <div class="dim">{{ appointmentTime($appointment->scheduled_at) }}{{ $endsAt ? ' – '.appointmentTime($endsAt) : '' }}</div>
                                @else
                                    <span class="dim">Not booked yet</span>
                                @endif
                            </td>
                            <td>{{ $appointment->customer?->name }}</td>
                            <td>{{ $appointment->customer?->mobile }}</td>
                            <td>
                                {{ $appointment->rentOut?->property?->number ?? '—' }}
                                @if ($appointment->rentOut?->property?->building)
                                    <div class="dim">{{ $appointment->rentOut->property->building->name }}</div>
                                @endif
                            </td>
                            <td>#{{ $appointment->rent_out_id }}</td>
                            <td>{{ $appointment->employee?->name }}</td>
                            <td><span class="status status-{{ $appointment->status }}">{{ $appointment->statusLabel() }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="footer">{{ $appointments->count() }} appointment(s)</div>
    </div>
</body>

</html>
