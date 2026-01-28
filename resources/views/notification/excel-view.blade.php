<x-app-layout>
    @push('styles')
        <style>
            .excel-view-page .content__header {
                border-bottom: 1px solid #d4d4d4;
            }

            .excel-view-page .page-title {
                font-weight: 600;
                letter-spacing: -0.02em;
            }

            .excel-view-page .btn-back {
                border-radius: 2px;
                font-weight: 500;
                transition: all 0.15s ease;
            }

            .excel-view-page .btn-back:hover {
                background: #e5e5e5;
                transform: translateX(-2px);
            }

            .excel-view-page .card-excel {
                border: 1px solid #d4d4d4;
                border-radius: 2px;
                box-shadow: 0 0 0 1px rgba(0, 0, 0, .05);
                overflow: hidden;
                background: #fff;
            }

            .excel-view-page .sheet-header {
                background: #f3f3f3;
                border-bottom: 1px solid #d4d4d4;
                padding: 6px 12px;
                font-weight: 600;
                font-size: 0.8rem;
                color: #333;
            }

            .excel-view-page .table-wrapper {
                overflow: auto;
                background: #fff;
            }

            .excel-view-page .excel-classic {
                border-collapse: collapse;
                font-size: 11px;
                font-family: "Segoe UI", Calibri, Arial, sans-serif;
                table-layout: auto;
                min-width: 100%;
            }

            .excel-view-page .excel-classic th,
            .excel-view-page .excel-classic td {
                border: 1px solid #d4d4d4;
                padding: 2px 6px;
                vertical-align: middle;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .excel-view-page .excel-classic th {
                background: #f3f3f3;
                color: #333;
                font-weight: 600;
                text-align: center;
            }

            .excel-view-page .excel-classic .excel-corner {
                width: 28px;
                min-width: 28px;
                max-width: 28px;
                background: #f3f3f3;
                border-right: 1px solid #d4d4d4;
                border-bottom: 1px solid #d4d4d4;
            }

            .excel-view-page .excel-classic .excel-col-header {
                width: 72px;
                min-width: 72px;
                background: #f3f3f3;
            }

            .excel-view-page .excel-classic .excel-row-header {
                width: 28px;
                min-width: 28px;
                max-width: 28px;
                text-align: center;
                background: #f3f3f3;
                color: #555;
                font-weight: 500;
            }

            .excel-view-page .excel-classic tbody td {
                background: #fff;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                min-width: 72px;
            }

            .excel-view-page .excel-classic tbody td.excel-row-header {
                position: sticky;
                left: 0;
                z-index: 1;
            }

            .excel-view-page .excel-classic thead th {
                position: sticky;
                top: 0;
                z-index: 2;
                box-shadow: 0 1px 0 #d4d4d4;
            }

            .excel-view-page .excel-classic thead th.excel-corner {
                z-index: 3;
            }

            .excel-view-page .excel-classic tbody tr:hover td {
                background: #e8f4fc;
            }

            .excel-view-page .excel-classic tbody tr:hover td.excel-row-header {
                background: #d6ebf9;
            }

            .excel-view-page .empty-state {
                padding: 48px 24px;
                text-align: center;
                color: #666;
                font-size: 11px;
            }

            .excel-view-page .file-badge {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 6px 12px;
                background: #e8f5e9;
                color: #2e7d32;
                border: 1px solid #c8e6c9;
                border-radius: 2px;
                font-size: 0.8rem;
                font-weight: 500;
            }
        </style>
    @endpush

    <div class="excel-view-page">
        <div class="content__header content__boxed overlapping">
            <div class="content__wrap">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-2">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('notification::index') }}">Notification</a></li>
                        <li class="breadcrumb-item active" aria-current="page">View Excel</li>
                    </ol>
                </nav>
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="file-badge">
                            <i class="demo-pli-file-excel"></i>
                            <span>{{ $fileName }}</span>
                        </div>
                        <h1 class="page-title mb-0">Excel Preview</h1>
                    </div>
                    <a href="{{ route('notification::index') }}" class="btn btn-light btn-sm btn-back">
                        <i class="demo-pli-arrow-left me-1"></i> Back to Notifications
                    </a>
                </div>
            </div>
        </div>
        <div class="content__boxed">
            <div class="content__wrap">
                @foreach ($sheets as $sheetIndex => $rows)
                    <div class="card card-excel mb-4">
                        @if (count($sheets) > 1)
                            <div class="sheet-header">Sheet {{ $sheetIndex + 1 }}</div>
                        @endif
                        <div class="table-wrapper">
                            <table class="excel-classic">
                                @php
                                    $maxCols = 0;
                                    foreach ($rows as $row) {
                                        $c = count((array) $row);
                                        if ($c > $maxCols) {
                                            $maxCols = $c;
                                        }
                                    }
                                    $maxCols = max(1, $maxCols);
                                    $colLetter = function ($n) {
                                        $s = '';
                                        while ($n >= 0) {
                                            $s = chr(65 + ($n % 26)) . $s;
                                            $n = (int) ($n / 26) - 1;
                                        }
                                        return $s;
                                    };
                                @endphp
                                <thead>
                                    <tr>
                                        <th class="excel-corner"></th>
                                        @for ($c = 0; $c < $maxCols; $c++)
                                            <th class="excel-col-header text-nowrap">{{ $colLetter($c) }}</th>
                                        @endfor
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($rows as $rowIndex => $row)
                                        <tr>
                                            <td class="excel-row-header text-nowrap">{{ $rowIndex + 1 }}</td>
                                            @foreach ((array) $row as $cell)
                                                <td class="text-nowrap">{{ $cell }}</td>
                                            @endforeach
                                            @if (count((array) $row) < $maxCols)
                                                @for ($i = count((array) $row); $i < $maxCols; $i++)
                                                    <td></td>
                                                @endfor
                                            @endif
                                        </tr>
                                    @empty
                                        <tr>
                                            <td class="excel-row-header">1</td>
                                            <td colspan="{{ max(1, $maxCols) }}" class="empty-state">
                                                No data in this sheet
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
