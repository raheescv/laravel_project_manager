<div>
    <div class="card shadow-sm">
        <div class="card-header bg-light py-3">
            <div class="row align-items-center">
                <div class="col-md-6 d-flex flex-wrap gap-2 align-items-center mb-3 mb-md-0">
                    <a href="{{ route($config->indexRoute) }}" class="btn btn-primary d-flex align-items-center shadow-sm">
                        <i class="fa fa-arrow-left me-2"></i>
                        Back to {{ $config->pluralLabel }}
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
                <div class="text-center py-5">
                    <div class="mb-4">
                        <i class="fa fa-cloud-upload text-primary" style="font-size: 4rem;"></i>
                    </div>
                    <h4 class="fw-bold">Upload Your Spreadsheet</h4>
                    <p class="text-muted mb-4">Supported formats: XLSX, XLS, CSV. Max size: 10MB.</p>

                    <div class="upload-zone mx-auto" style="max-width: 500px;">
                        <input type="file" wire:model="file" class="upload-input" accept=".xlsx,.xls,.csv">
                        <div wire:loading.remove wire:target="file">
                            <div class="p-5">
                                <i class="fa fa-plus-circle fs-1 text-primary mb-3"></i>
                                <h5 class="mb-1">Click to browse or drag and drop</h5>
                                <span class="text-muted small">Files will be uploaded automatically</span>
                            </div>
                        </div>
                        <div wire:loading wire:target="file" class="p-5">
                            <div class="spinner-border text-primary mb-3" role="status"></div>
                            <h5>Uploading file, please wait...</h5>
                        </div>
                    </div>

                    <div class="mt-4 mx-auto text-start" style="max-width: 720px;">
                        <div class="alert alert-info mb-0">
                            <strong><i class="fa fa-info-circle me-1"></i> Tips:</strong>
                            <ul class="mb-0 ps-3 small">
                                <li>Property column accepts the property ID or its <em>number</em> (e.g. <code>A-101</code>).</li>
                                <li>Customer column accepts the account ID or the exact account name.</li>
                                <li>Date columns accept <code>YYYY-MM-DD</code>, <code>DD/MM/YYYY</code> or Excel date cells.</li>
                                <li>If <code>total</code> is empty it will be auto-calculated from <code>rent × months − discount</code>.</li>
                                <li>Use <code>upload_type=update</code> with an <code>id</code> column to modify existing agreements.</li>
                            </ul>
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
                                <p class="text-muted small">Match your spreadsheet columns to {{ strtolower($config->singularLabel) }} fields. Auto-matched headers are pre-selected.</p>
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
                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                @foreach ($mappings as $field => $header)
                                    @if ($header)
                                        <th class="p-2 fw-bold text-nowrap small">
                                            {{ $availableFields[$field] ?? $field }}
                                            <div class="fw-normal text-muted" style="font-size: .7rem;">({{ $header }})</div>
                                        </th>
                                    @endif
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($previewData as $row)
                                <tr>
                                    @foreach ($mappings as $field => $header)
                                        @if ($header)
                                            <td class="p-2 text-nowrap small">{{ $row[array_search($header, $headers)] ?? '-' }}</td>
                                        @endif
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-center gap-2">
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
                <div class="text-center py-5">
                    <div class="mb-3">
                        <div class="spinner-grow text-success" role="status" style="width: 4rem; height: 4rem;"></div>
                    </div>
                    <h4 class="fw-bold">Import in Progress</h4>
                    <p class="text-muted mb-4">Processing your {{ strtolower($config->pluralLabel) }} in the background. You can leave this page.</p>

                    <div class="progress mx-auto shadow-sm mb-2" style="height: 24px; max-width: 500px; border-radius: 12px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-success fw-bold" id="rentout-progress-bar" role="progressbar" style="width: 0%"
                            aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                    </div>

                    <div class="mt-4">
                        <a href="{{ route($config->indexRoute) }}" class="btn btn-outline-primary px-4">
                            Go to {{ $config->pluralLabel }} List
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
            border: 3px dashed var(--bs-border-color, #dee2e6);
            border-radius: 16px;
            position: relative;
            background: var(--bs-tertiary-bg, #f8f9fa);
            transition: all 0.3s ease;
        }

        .upload-zone:hover {
            border-color: var(--bs-primary);
            background: var(--bs-body-bg, #fff);
        }

        .upload-input {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
            z-index: 10;
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
    </style>
@endpush

@push('scripts')
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script>
        let rentOutImportProgressInitialized = false;
        function initRentOutImportProgress() {
            if (rentOutImportProgressInitialized) return;
            rentOutImportProgressInitialized = true;
            const pusher = new Pusher("{{ config('services.pusher.pusher_app_key') }}", {
                cluster: "{{ config('services.pusher.pusher_app_cluster') }}",
                encrypted: true,
            });
            const channel = pusher.subscribe('file-import-channel-{{ auth()->id() }}');
            channel.bind('file-import-event-{{ auth()->id() }}', function(data) {
                if (data.type === 'RentOut') {
                    const progressBar = document.getElementById('rentout-progress-bar');
                    if (progressBar) {
                        progressBar.style.width = `${data.progress}%`;
                        progressBar.setAttribute('aria-valuenow', data.progress);
                        progressBar.textContent = `${Math.round(data.progress)}%`;

                        if (data.progress >= 100) {
                            setTimeout(() => {
                                alert('Import completed successfully!');
                                window.location.href = "{{ route($config->indexRoute) }}";
                            }, 1000);
                        }
                    }
                }
            });
        }

        document.addEventListener('livewire:initialized', initRentOutImportProgress);
        if (window.Livewire) {
            initRentOutImportProgress();
        }
    </script>
@endpush
