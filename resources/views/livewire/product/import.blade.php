<div>
    <div class="modal-header d-flex justify-content-between align-items-center bg-light">
        <h1 class="modal-title fs-5 mb-0">
            <i class="demo-psi-inbox-full fs-4 me-2"></i>Product Import
        </h1>
        <div>
            <button type="button" class="btn btn-info btn-sm" wire:click="sample">
                <i class="demo-psi-download-from-cloud me-2"></i>Download Template
            </button>
        </div>
    </div>
    <form wire:submit="save">
        <div class="modal-body">
            @if ($this->getErrorBag()->count())
                <div class="alert alert-danger mb-4">
                    <h6 class="alert-heading fw-semibold">Import Errors</h6>
                    <ol class="mb-0">
                        <?php foreach ($this->getErrorBag()->toArray() as $value): ?>
                        <li>{{ $value[0] }}</li>
                        <?php endforeach; ?>
                    </ol>
                </div>
            @endif

            <div class="text-center mb-4">
                <div class="mb-3">
                    <i class="demo-psi-file-excel fs-1 text-success"></i>
                </div>
                <h5>Upload Product Excel File</h5>
                <p class="text-muted">Drag and drop your Excel file or click to browse</p>
            </div>

            <div class="upload-zone @error('file') is-invalid @enderror">
                <div class="dz-message" wire:loading.remove wire:target="file">
                    <input type="file" wire:model="file" class="upload-input" accept=".xlsx,.xls,.csv">
                    @if ($file)
                        <div class="text-success">
                            <i class="demo-psi-check fs-1"></i>
                            <span>{{ $file->getClientOriginalName() }}</span>
                        </div>
                    @else
                        <div class="dz-message-text">
                            <i class="demo-psi-upload-to-cloud fs-1"></i>
                            <span>Drop files here or click to upload.</span>
                        </div>
                    @endif
                </div>
                <div wire:loading wire:target="file">
                    <i class="demo-psi-repeat-2 fa-spin fs-1"></i>
                    <span>Uploading...</span>
                </div>
            </div>
            @error('file')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror

            <div class="mt-4">
                <label class="form-label">Import Progress</label>
                <div class="progress" style="height: 15px">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" id="progress-bar" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0"
                        aria-valuemax="100">0%</div>
                </div>
            </div>
        </div>
        <div class="modal-footer bg-light">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                <i class="demo-psi-cross me-1"></i> Close
            </button>
            <button type="submit" class="btn btn-success" @if (!$file) disabled @endif>
                <i class="demo-psi-file-import me-1"></i> Import Products
            </button>
        </div>
    </form>

    @push('styles')
        <style>
            .upload-zone {
                border: 2px dashed #e0e4e7;
                padding: 2rem;
                text-align: center;
                border-radius: 0.375rem;
                cursor: pointer;
                transition: all 0.2s ease;
            }

            .upload-zone:hover {
                border-color: #0f6cbd;
            }

            .upload-input {
                position: absolute;
                width: 100%;
                height: 100%;
                top: 0;
                left: 0;
                opacity: 0;
                cursor: pointer;
            }

            .dz-message-text {
                color: #5e6e82;
            }
        </style>
    @endpush

    @push('scripts')
        <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
        <script>
            Pusher.logToConsole = true;
            const pusher = new Pusher("{{ env('PUSHER_APP_KEY') }}", {
                cluster: "{{ env('PUSHER_APP_CLUSTER') }}",
                encrypted: true,
            });
            const channel = pusher.subscribe('file-import-channel-{{ auth()->id() }}');
            channel.bind('file-import-event-{{ auth()->id() }}', function(data) {
                console.log(data);
                if (data.type === 'Product') {
                    const progressBar = document.getElementById('progress-bar');
                    progressBar.style.width = `${data.progress}%`;
                    progressBar.setAttribute('aria-valuenow', data.progress);
                    $('#progress-bar').text(Math.round(data.progress, 2));
                    if (data.progress === 100) {
                        alert('Import completed!');
                        window.location.reload();
                    }
                }
            });
        </script>
    @endpush
</div>
