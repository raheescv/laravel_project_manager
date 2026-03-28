<div>
    <div class="card shadow-sm">
        <div class="card-header bg-light py-3">
            <div class="row align-items-center">
                <div class="col-md-6 d-flex flex-wrap gap-2 align-items-center mb-3 mb-md-0">
                    <a href="{{ route('account::general-voucher::index') }}" class="btn btn-primary d-flex align-items-center shadow-sm">
                        <i class="fa fa-arrow-left me-2"></i>
                        Back to Vouchers
                    </a>
                    <div class="btn-group shadow-sm">
                        <button class="btn btn-success btn-sm d-flex align-items-center" wire:click="sample">
                            <i class="fa fa-download me-md-1 fs-5"></i>
                            <span class="d-none d-md-inline">Download Template</span>
                        </button>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex justify-content-end gap-2">
                        @foreach (['Upload File', 'Map Columns', 'Preview', 'Importing'] as $i => $label)
                            @php $num = $i + 1; @endphp
                            <div class="step-item {{ $step == $num ? 'active' : '' }} {{ $step > $num ? 'completed' : '' }}">
                                <div class="d-flex align-items-center gap-1">
                                    <div class="step-icon">{{ $num }}</div>
                                    <span class="d-none d-lg-inline small fw-semibold">{{ $label }}</span>
                                </div>
                            </div>
                            @if ($i < 3)
                                <div class="d-flex align-items-center">
                                    <i class="fa fa-chevron-right text-muted" style="font-size: .6rem;"></i>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body">
            {{-- ══════════════ STEP 1: Upload ══════════════ --}}
            @if ($step == 1)
                <div class="row py-4">
                    {{-- Left: Upload Zone --}}
                    <div class="col-lg-5 mb-4 mb-lg-0">
                        <div class="upload-zone text-center h-100 d-flex flex-column justify-content-center">
                            <input type="file" wire:model="file" class="upload-input" accept=".xlsx,.xls,.csv">
                            <div wire:loading.remove wire:target="file">
                                <div class="py-4 px-3">
                                    <i class="fa fa-cloud-upload text-primary mb-2" style="font-size: 2.5rem;"></i>
                                    <p class="fw-semibold mb-1">Drop your file here, or <span class="text-primary">browse</span></p>
                                    <span class="text-muted small">XLSX, XLS, CSV &middot; Max 10 MB</span>
                                </div>
                            </div>
                            <div wire:loading wire:target="file">
                                <div class="py-4 px-3">
                                    <div class="spinner-border spinner-border-sm text-primary mb-2"></div>
                                    <p class="fw-semibold mb-0">Uploading...</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Right: How It Works --}}
                    <div class="col-lg-7">
                        <h6 class="fw-bold text-uppercase small text-muted mb-3"><i class="fa fa-lightbulb-o me-1"></i> How It Works</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="d-flex gap-2">
                                    <div class="flex-shrink-0">
                                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary bg-opacity-10 text-primary" style="width:32px;height:32px;">
                                            <i class="fa fa-link"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <div class="fw-semibold small">Group by Reference</div>
                                        <p class="text-muted small mb-0">Rows with the same <strong>Reference Number</strong> are combined into a single voucher entry.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex gap-2">
                                    <div class="flex-shrink-0">
                                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10 text-success" style="width:32px;height:32px;">
                                            <i class="fa fa-magic"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <div class="fw-semibold small">Auto-Create Accounts</div>
                                        <p class="text-muted small mb-0">Account heads that don't exist will be <strong>automatically created</strong> with the given type and category.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex gap-2">
                                    <div class="flex-shrink-0">
                                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-warning bg-opacity-10 text-warning" style="width:32px;height:32px;">
                                            <i class="fa fa-balance-scale"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <div class="fw-semibold small">Balance Validation</div>
                                        <p class="text-muted small mb-0">Each voucher is validated so that <strong>total debits = total credits</strong> before saving.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex gap-2">
                                    <div class="flex-shrink-0">
                                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-info bg-opacity-10 text-info" style="width:32px;height:32px;">
                                            <i class="fa fa-cogs"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <div class="fw-semibold small">Background Processing</div>
                                        <p class="text-muted small mb-0">Large files are processed in the <strong>background</strong> with real-time progress updates.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- ══════════════ STEP 2: Map Columns ══════════════ --}}
            @if ($step == 2)
                <div class="row">
                    <div class="col-md-8">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light text-uppercase small fw-bold">
                                    <tr>
                                        <th style="width: 40%">Database Field</th>
                                        <th>Excel Column</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($availableFields as $field => $label)
                                        <tr>
                                            <td>
                                                <div class="fw-medium">{{ $label }}</div>
                                                <small class="text-muted">{{ $field }}</small>
                                            </td>
                                            <td>
                                                <select wire:model="mappings.{{ $field }}" class="form-select form-select-sm border-secondary-subtle shadow-sm">
                                                    <option value="">-- Do Not Import --</option>
                                                    @foreach ($headers as $header)
                                                        <option value="{{ $header }}">{{ $header }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light border-0 sticky-top" style="top: 20px;">
                            <div class="card-body p-3 text-center">
                                <i class="fa fa-info-circle fs-3 text-info mb-2"></i>
                                <h5 class="fw-bold">Column Mapping</h5>
                                <p class="text-muted small">Match your spreadsheet columns to voucher fields. Auto-matched headers are pre-selected.</p>
                                <hr>
                                <div class="d-grid gap-2">
                                    <button class="btn btn-primary" wire:click="goToStep(3)">
                                        Next: Preview <i class="fa fa-arrow-right ms-1"></i>
                                    </button>
                                    <button class="btn btn-outline-secondary btn-sm" wire:click="goToStep(1)">
                                        <i class="fa fa-arrow-left me-1"></i> Back
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- ══════════════ STEP 3: Preview ══════════════ --}}
            @if ($step == 3)
                @php
                    $mappedFields = array_filter($mappings);
                    $colCount = count($mappedFields);
                @endphp
                <div class="excel-sheet-wrapper">
                    {{-- Formula bar --}}
                    <div class="excel-formula-bar d-flex align-items-center gap-2 px-2 py-1">
                        <span class="excel-cell-ref px-2 py-1 small fw-bold">A1</span>
                        <span class="text-muted small"><i class="fa fa-table me-1"></i> Preview &middot; First {{ count($previewData) }} rows of your file</span>
                        <span class="ms-auto text-muted small">{{ $colCount }} columns mapped</span>
                    </div>

                    <div class="table-responsive excel-grid">
                        <table class="table table-bordered mb-0 excel-table">
                            {{-- Column letters row --}}
                            <thead>
                                <tr class="excel-col-header">
                                    <th class="excel-row-num"></th>
                                    @php $colLetter = 'A'; @endphp
                                    @foreach ($mappedFields as $field => $header)
                                        <th>{{ $colLetter++ }}</th>
                                    @endforeach
                                </tr>
                                {{-- Field names row --}}
                                <tr class="excel-header-row">
                                    <th class="excel-row-num">1</th>
                                    @foreach ($mappedFields as $field => $header)
                                        <th>
                                            <div class="fw-semibold">{{ $availableFields[$field] }}</div>
                                            <div class="excel-field-hint">{{ $header }}</div>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($previewData as $rowIndex => $row)
                                    <tr>
                                        <td class="excel-row-num">{{ $rowIndex + 2 }}</td>
                                        @foreach ($mappedFields as $field => $header)
                                            <td class="excel-cell">{{ $row[array_search($header, $headers)] ?? '' }}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Sheet tabs --}}
                    <div class="excel-sheet-tabs d-flex align-items-center gap-1 px-2 py-1">
                        <span class="excel-tab active"><i class="fa fa-file-excel-o me-1"></i> Sheet1</span>
                        <span class="excel-tab-add"><i class="fa fa-plus"></i></span>
                    </div>
                </div>

                <div class="d-flex justify-content-center gap-2 mt-3">
                    <button class="btn btn-outline-secondary px-4" wire:click="goToStep(2)">
                        <i class="fa fa-arrow-left me-1"></i> Back to Mapping
                    </button>
                    <button class="btn btn-success px-4 shadow-sm" wire:click="save">
                        <i class="fa fa-check-circle me-1"></i> Start Import
                    </button>
                </div>
            @endif

            {{-- ══════════════ STEP 4: Importing ══════════════ --}}
            @if ($step == 4)
                <div class="text-center py-5" wire:poll.5s="checkJobStatus">

                    @if ($importStatus === 'failed')
                        {{-- Failed state (Livewire driven) --}}
                        <div class="mb-3">
                            <i class="fa fa-times-circle text-danger" style="font-size: 4rem;"></i>
                        </div>
                        <h4 class="fw-bold text-danger">Import Failed</h4>
                        <p class="text-muted mb-3">The import job encountered an error.</p>
                        <div class="alert alert-danger mx-auto text-start" style="max-width: 500px;">
                            <i class="fa fa-exclamation-triangle me-1"></i>
                            {{ $importError }}
                            <p class="mb-0 mt-1 small">Check your notifications for the detailed error report.</p>
                        </div>
                    @elseif ($importStatus === 'completed')
                        {{-- Completed state (Livewire driven) --}}
                        <div class="mb-3">
                            <i class="fa fa-check-circle text-success" style="font-size: 4rem;"></i>
                        </div>
                        <h4 class="fw-bold text-success">Import Completed!</h4>
                        <p class="text-muted mb-3">All vouchers imported successfully.</p>
                    @else
                        {{-- Processing state --}}
                        <div class="mb-3" id="gv-spinner">
                            <div class="spinner-grow text-success" role="status" style="width: 4rem; height: 4rem;"></div>
                        </div>
                        <div class="mb-3 d-none" id="gv-success-icon">
                            <i class="fa fa-check-circle text-success" style="font-size: 4rem;"></i>
                        </div>
                        <div class="mb-3 d-none" id="gv-error-icon">
                            <i class="fa fa-times-circle text-danger" style="font-size: 4rem;"></i>
                        </div>

                        <h4 class="fw-bold" id="gv-status-title">Import in Progress</h4>
                        <p class="text-muted mb-4" id="gv-status-message">Processing your vouchers in the background. You can leave this page.</p>

                        <div class="progress mx-auto shadow-sm mb-2" style="height: 24px; max-width: 500px; border-radius: 12px;" id="gv-progress-container">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-success fw-bold" id="gv-progress-bar" role="progressbar" style="width: 0%"
                                aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                        </div>

                        <div class="alert alert-danger mx-auto mt-3 d-none text-start" style="max-width: 500px;" id="gv-error-details">
                            <i class="fa fa-exclamation-triangle me-1"></i>
                            <span id="gv-error-text"></span>
                            <p class="mb-0 mt-1 small">Check your notifications for the detailed error report.</p>
                        </div>
                    @endif

                    <div class="mt-4">
                        <a href="{{ route('account::general-voucher::index') }}" class="btn btn-outline-primary px-4">
                            Go to General Voucher List
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@push('styles')
    <style>
        .upload-zone {
            border: 2px dashed var(--bs-border-color, #dee2e6);
            border-radius: 12px;
            position: relative;
            background: var(--bs-tertiary-bg, #f8f9fa);
            transition: border-color .2s;
            cursor: pointer;
        }

        .upload-zone:hover {
            border-color: var(--bs-primary);
        }

        .upload-input {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
            z-index: 2;
        }

        .step-icon {
            width: 28px;
            height: 28px;
            line-height: 26px;
            border-radius: 50%;
            border: 2px solid var(--bs-border-color, #dee2e6);
            background: var(--bs-body-bg, #fff);
            color: var(--bs-secondary-color, #6c757d);
            font-weight: bold;
            font-size: .8rem;
            text-align: center;
            transition: all 0.3s ease;
        }

        .step-item.active .step-icon {
            border-color: var(--bs-primary);
            background: var(--bs-primary);
            color: #fff;
        }

        .step-item.completed .step-icon {
            border-color: var(--bs-success);
            background: var(--bs-success);
            color: #fff;
        }

        /* ── Excel-like spreadsheet view ── */
        .excel-sheet-wrapper {
            border: 1px solid #b4b4b4;
            border-radius: 4px;
            overflow: hidden;
            background: #fff;
        }

        [data-bs-theme="dark"] .excel-sheet-wrapper {
            border-color: var(--bs-border-color);
            background: var(--bs-body-bg);
        }

        .excel-formula-bar {
            background: #f3f3f3;
            border-bottom: 1px solid #d4d4d4;
            min-height: 28px;
        }

        [data-bs-theme="dark"] .excel-formula-bar {
            background: var(--bs-tertiary-bg);
            border-color: var(--bs-border-color);
        }

        .excel-cell-ref {
            background: #fff;
            border: 1px solid #d4d4d4;
            font-family: 'Segoe UI', Calibri, sans-serif;
            min-width: 50px;
            text-align: center;
        }

        [data-bs-theme="dark"] .excel-cell-ref {
            background: var(--bs-body-bg);
            border-color: var(--bs-border-color);
        }

        .excel-grid {
            margin: 0;
        }

        .excel-table {
            font-family: 'Segoe UI', Calibri, Arial, sans-serif;
            font-size: 12px;
            border-collapse: collapse;
        }

        .excel-table th,
        .excel-table td {
            border: 1px solid #d4d4d4 !important;
            padding: 3px 8px !important;
            vertical-align: middle;
        }

        [data-bs-theme="dark"] .excel-table th,
        [data-bs-theme="dark"] .excel-table td {
            border-color: var(--bs-border-color) !important;
        }

        .excel-col-header th {
            background: #e8e8e8;
            color: #444;
            text-align: center;
            font-weight: normal;
            font-size: 11px;
            padding: 2px 8px !important;
        }

        [data-bs-theme="dark"] .excel-col-header th {
            background: var(--bs-tertiary-bg);
            color: var(--bs-body-color);
        }

        .excel-header-row th {
            background: #e2efda;
            color: #1a3a1a;
            font-size: 11px;
            white-space: nowrap;
        }

        [data-bs-theme="dark"] .excel-header-row th {
            background: rgba(var(--bs-success-rgb), 0.15);
            color: var(--bs-body-color);
        }

        .excel-field-hint {
            font-weight: normal;
            color: #777;
            font-size: 10px;
        }

        .excel-row-num {
            background: #e8e8e8 !important;
            color: #444;
            text-align: center !important;
            font-weight: normal;
            width: 40px;
            min-width: 40px;
            font-size: 11px;
        }

        [data-bs-theme="dark"] .excel-row-num {
            background: var(--bs-tertiary-bg) !important;
            color: var(--bs-body-color);
        }

        .excel-cell {
            background: #fff;
            white-space: nowrap;
        }

        [data-bs-theme="dark"] .excel-cell {
            background: var(--bs-body-bg);
        }

        .excel-cell:hover {
            outline: 2px solid #217346;
            outline-offset: -1px;
            z-index: 1;
            position: relative;
        }

        .excel-sheet-tabs {
            background: #e8e8e8;
            border-top: 1px solid #d4d4d4;
        }

        [data-bs-theme="dark"] .excel-sheet-tabs {
            background: var(--bs-tertiary-bg);
            border-color: var(--bs-border-color);
        }

        .excel-tab {
            background: #fff;
            border: 1px solid #d4d4d4;
            border-bottom: none;
            padding: 3px 14px;
            font-size: 11px;
            border-radius: 3px 3px 0 0;
            cursor: default;
        }

        [data-bs-theme="dark"] .excel-tab {
            background: var(--bs-body-bg);
            border-color: var(--bs-border-color);
        }

        .excel-tab.active {
            font-weight: 600;
        }

        .excel-tab-add {
            color: #777;
            font-size: 11px;
            padding: 3px 8px;
            cursor: default;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script>
        let gvFileImportProgressInitialized = false;
        function initGVFileImportProgress() {
            if (gvFileImportProgressInitialized) return;
            gvFileImportProgressInitialized = true;
            const pusher = new Pusher("{{ config('services.pusher.pusher_app_key') }}", {
                cluster: "{{ config('services.pusher.pusher_app_cluster') }}",
                encrypted: true,
            });
            const channel = pusher.subscribe('file-import-channel-{{ auth()->id() }}');
            channel.bind('file-import-event-{{ auth()->id() }}', function(data) {
                if (data.type === 'GeneralVoucher') {
                    const progressBar = document.getElementById('gv-progress-bar');
                    const spinner = document.getElementById('gv-spinner');
                    const successIcon = document.getElementById('gv-success-icon');
                    const errorIcon = document.getElementById('gv-error-icon');
                    const statusTitle = document.getElementById('gv-status-title');
                    const statusMessage = document.getElementById('gv-status-message');
                    const progressContainer = document.getElementById('gv-progress-container');
                    const errorDetails = document.getElementById('gv-error-details');
                    const errorText = document.getElementById('gv-error-text');

                    if (data.progress === -1) {
                        if (spinner) spinner.classList.add('d-none');
                        if (errorIcon) errorIcon.classList.remove('d-none');
                        if (statusTitle) statusTitle.textContent = 'Import Failed';
                        if (statusMessage) statusMessage.textContent = 'The import encountered an error.';
                        if (progressContainer) progressContainer.classList.add('d-none');
                        if (errorDetails) errorDetails.classList.remove('d-none');
                        if (errorText) errorText.textContent = data.message || 'An unexpected error occurred.';
                    } else if (progressBar) {
                        progressBar.style.width = `${data.progress}%`;
                        progressBar.setAttribute('aria-valuenow', data.progress);
                        progressBar.textContent = `${Math.round(data.progress)}%`;

                        if (data.progress >= 100) {
                            if (spinner) spinner.classList.add('d-none');
                            if (successIcon) successIcon.classList.remove('d-none');
                            if (statusTitle) statusTitle.textContent = 'Import Completed!';
                            if (statusMessage) statusMessage.textContent = 'All vouchers imported. Redirecting...';
                            if (progressBar) progressBar.classList.remove('progress-bar-animated');

                            setTimeout(() => {
                                window.location.href = "{{ route('account::general-voucher::index') }}";
                            }, 2000);
                        }
                    }
                }
            });
        }

        document.addEventListener('livewire:initialized', initGVFileImportProgress);
        if (window.Livewire) {
            initGVFileImportProgress();
        }
    </script>
@endpush
