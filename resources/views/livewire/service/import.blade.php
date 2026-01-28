<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0 rounded-3 overflow-hidden">
                <div class="card-header bg-gradient-primary py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="card-title text-white mb-0">
                            <i class="fa fa-file-import me-2"></i>Service Import
                        </h4>
                        <button type="button" class="btn btn-light btn-sm shadow-sm" wire:click="sample">
                            <i class="fa fa-download me-2"></i>Download Template
                        </button>
                    </div>
                </div>

                <div class="card-body p-0">
                    <!-- Stepper -->
                    <div class="bg-light border-bottom p-4">
                        <div class="row g-0 justify-content-center text-center">
                            <div class="col-md-3">
                                <div class="step-item {{ $step == 1 ? 'active' : '' }} {{ $step > 1 ? 'completed' : '' }}">
                                    <div class="step-icon mx-auto mb-2">1</div>
                                    <h6 class="mb-0">Upload File</h6>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="step-item {{ $step == 2 ? 'active' : '' }} {{ $step > 2 ? 'completed' : '' }}">
                                    <div class="step-icon mx-auto mb-2">2</div>
                                    <h6 class="mb-0">Map Columns</h6>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="step-item {{ $step == 3 ? 'active' : '' }} {{ $step > 3 ? 'completed' : '' }}">
                                    <div class="step-icon mx-auto mb-2">3</div>
                                    <h6 class="mb-0">Preview & Validate</h6>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="step-item {{ $step == 4 ? 'active' : '' }}">
                                    <div class="step-icon mx-auto mb-2">4</div>
                                    <h6 class="mb-0">Importing</h6>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-4">
                        @if ($step == 1)
                            <div class="text-center py-5">
                                <div class="mb-4">
                                    <i class="fa fa-cloud-upload text-primary animate-bounce" style="font-size: 5rem;"></i>
                                </div>
                                <h2 class="fw-bold">Upload Your Spreadsheet</h2>
                                <p class="text-muted mb-4 fs-5">Supported formats: XLSX, XLS, CSV. Max size: 10MB.</p>

                                <div class="upload-zone mx-auto" style="max-width: 600px;">
                                    <input type="file" wire:model="file" class="upload-input" accept=".xlsx,.xls,.csv">
                                    <div wire:loading.remove wire:target="file">
                                        <div class="dz-message-text p-5">
                                            <i class="fa fa-plus-circle fs-1 text-primary mb-3"></i>
                                            <h4 class="mb-1">Click to browse or drag and drop</h4>
                                            <span class="text-muted">Files will be uploaded automatically</span>
                                        </div>
                                    </div>
                                    <div wire:loading wire:target="file" class="p-5">
                                        <div class="spinner-border text-primary mb-3" role="status"></div>
                                        <h4>Uploading file, please wait...</h4>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if ($step == 2)
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="card border shadow-none mb-4">
                                        <div class="card-header bg-white py-3">
                                            <h5 class="mb-0 fw-bold"><i class="fa fa-columns me-2 text-primary"></i>Column Mapping</h5>
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-hover align-middle">
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
                                                                <div class="fw-medium text-dark">{{ $label }}</div>
                                                                <small class="text-muted">{{ $field }}</small>
                                                            </td>
                                                            <td>
                                                                <select wire:model="mappings.{{ $field }}" class="form-select border-primary-subtle shadow-sm">
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
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-light border-0 shadow-sm sticky-top" style="top: 20px;">
                                        <div class="card-body p-4 text-center">
                                            <div class="mb-3">
                                                <i class="fa fa-info-circle fs-1 text-info"></i>
                                            </div>
                                            <h4 class="fw-bold">Why Mapping?</h4>
                                            <p class="text-muted">We need to know which column in your file corresponds to which product attribute. We've matched some automatically based on header
                                                names.</p>
                                            <hr>
                                            <div class="d-grid gap-2">
                                                <button class="btn btn-primary btn-lg" wire:click="goToStep(3)">
                                                    Next Step: Preview <i class="fa fa-arrow-right ms-2"></i>
                                                </button>
                                                <button class="btn btn-outline-secondary" wire:click="goToStep(1)">
                                                    Back to Upload
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if ($step == 3)
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="card border shadow-none mb-4">
                                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                                            <h5 class="mb-0 fw-bold"><i class="fa fa-table me-2 text-primary"></i>Sheet Preview (First 10 Rows)</h5>
                                            <div class="badge bg-info p-2 px-3">Only previews what we'll import</div>
                                        </div>
                                        <div class="card-body p-0">
                                            <div class="table-responsive">
                                                <table class="table table-bordered table-sm mb-0">
                                                    <thead class="table-light">
                                                        <tr>
                                                            @foreach ($mappings as $field => $header)
                                                                @if ($header)
                                                                    <th class="p-2 fw-bold text-nowrap">
                                                                        {{ $availableFields[$field] }}
                                                                        <div class="small fw-normal text-muted">({{ $header }})</div>
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
                                                                        <td class="p-2 text-nowrap">{{ $row[array_search($header, $headers)] ?? '-' }}</td>
                                                                    @endif
                                                                @endforeach
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-center gap-3">
                                        <button class="btn btn-secondary btn-lg px-5" wire:click="goToStep(2)">
                                            <i class="fa fa-arrow-left me-2"></i> Back to Mapping
                                        </button>
                                        <button class="btn btn-success btn-lg px-5 shadow-sm" wire:click="save">
                                            <i class="fa fa-check-circle me-2"></i> Start Import Now
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if ($step == 4)
                            <div class="text-center py-5">
                                <div class="mb-4">
                                    <div class="spinner-grow text-success" role="status" style="width: 5rem; height: 5rem;"></div>
                                </div>
                                <h1 class="fw-bold">Import in Progress</h1>
                                <p class="text-muted mb-4 fs-5">We are processing your products in the background. You can leave this page or wait here.</p>

                                <div class="progress mx-auto shadow-sm" style="height: 30px; max-width: 700px; border-radius: 15px;">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-success fw-bold" id="progress-bar" role="progressbar" style="width: 0%"
                                        aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                                </div>

                                <div class="mt-5">
                                    <a href="{{ route('product::index') }}" class="btn btn-outline-primary px-5 btn-lg">
                                        Go to Product List
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
    <style>
        .bg-gradient-primary {
            background: linear-gradient(135deg, #0d6efd 0%, #004085 100%);
        }

        .upload-zone {
            border: 3px dashed #dee2e6;
            border-radius: 20px;
            position: relative;
            background: #f8f9fa;
            transition: all 0.3s ease;
        }

        .upload-zone:hover {
            border-color: #0d6efd;
            background: #fff;
            transform: translateY(-2px);
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

        .step-item {
            position: relative;
            padding: 0 10px;
        }

        .step-icon {
            width: 40px;
            height: 40px;
            line-height: 38px;
            border-radius: 50%;
            border: 2px solid #dee2e6;
            background: #fff;
            color: #6c757d;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .step-item.active .step-icon {
            border-color: #0d6efd;
            background: #0d6efd;
            color: #fff;
            box-shadow: 0 0 15px rgba(13, 110, 253, 0.3);
        }

        .step-item.completed .step-icon {
            border-color: #198754;
            background: #198754;
            color: #fff;
        }

        .step-item:not(:last-child):after {
            content: '';
            position: absolute;
            top: 20px;
            left: 50%;
            width: 100%;
            height: 2px;
            background: #dee2e6;
            z-index: -1;
        }

        .animate-bounce {
            animation: bounce 2s infinite;
        }

        @keyframes bounce {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-20px);
            }
        }
    </style>
@endpush

@push('scripts')
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script>
        let fileImportProgressInitialized = false;
        function initFileImportProgress() {
            if (fileImportProgressInitialized) return;
            fileImportProgressInitialized = true;
            const pusher = new Pusher("{{ config('services.pusher.pusher_app_key') }}", {
                cluster: "{{ config('services.pusher.pusher_app_cluster') }}",
                encrypted: true,
            });
            const channel = pusher.subscribe('file-import-channel-{{ auth()->id() }}');
            channel.bind('file-import-event-{{ auth()->id() }}', function(data) {
                if (data.type === 'Product') {
                    const progressBar = document.getElementById('progress-bar');
                    if (progressBar) {
                        progressBar.style.width = `${data.progress}%`;
                        progressBar.setAttribute('aria-valuenow', data.progress);
                        progressBar.textContent = `${Math.round(data.progress)}%`;

                        if (data.progress >= 100) {
                            setTimeout(() => {
                                alert('Import completed successfully!');
                                window.location.href = "{{ route('product::index') }}";
                            }, 1000);
                        }
                    }
                }
            });
        }

        document.addEventListener('livewire:initialized', initFileImportProgress);
        if (window.Livewire) {
            initFileImportProgress();
        }
    </script>
@endpush
