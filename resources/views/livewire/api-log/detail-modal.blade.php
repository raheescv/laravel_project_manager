<!-- API Log Detail Modal -->
<div class="modal fade" id="apiLogDetailModal" tabindex="-1" aria-labelledby="apiLogDetailModalLabel" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary ">
                <h5 class="modal-title fw-bold text-white" id="apiLogDetailModalLabel">
                    <i class="demo-psi-database me-2"></i>API Log Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" wire:click="closeModal"></button>
            </div>

            <div class="modal-body p-4">
                @if ($apiLog)
                    <div class="row g-4">
                        <!-- Request Details Section -->
                        <div class="col-12 col-lg-6">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-header bg-light">
                                    <h6 class="card-title mb-0 fw-semibold text-primary">
                                        <i class="demo-psi-send me-2"></i>Request Details
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label class="form-label fw-semibold text-muted small">Endpoint</label>
                                            <div class="p-2 bg-light rounded border">
                                                <code class="text-primary">{{ $apiLog->endpoint }}</code>
                                            </div>
                                        </div>

                                        <div class="col-6">
                                            <label class="form-label fw-semibold text-muted small">Method</label>
                                            <div class="p-2 bg-light rounded border">
                                                <span class="badge bg-info text-white">{{ strtoupper($apiLog->method) }}</span>
                                            </div>
                                        </div>

                                        <div class="col-6">
                                            <label class="form-label fw-semibold text-muted small">Username</label>
                                            <div class="p-2 bg-light rounded border">
                                                {{ $apiLog->username ?? 'N/A' }}
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label fw-semibold text-muted small">Date & Time</label>
                                            <div class="p-2 bg-light rounded border">
                                                <i class="demo-psi-calendar-4 me-1"></i>
                                                {{ $apiLog->created_at->format('M d, Y H:i:s') }}
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label fw-semibold text-muted small">Request Data</label>
                                            <div class="bg-dark text-light p-3 rounded border">
                                                <pre class="mb-0 text-light small" style="max-height: 200px; overflow-y: auto;">{{ ($apiLog->request) }}</pre>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Response Details Section -->
                        <div class="col-12 col-lg-6">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-header bg-light">
                                    <h6 class="card-title mb-0 fw-semibold text-success">
                                        <i class="demo-psi-reply me-2"></i>Response Details
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-6">
                                            <label class="form-label fw-semibold text-muted small">Status</label>
                                            <div class="p-2 bg-light rounded border">
                                                @if ($apiLog->status === 'success')
                                                    <span class="badge bg-success">
                                                        <i class="demo-psi-check me-1"></i>Success
                                                    </span>
                                                @elseif($apiLog->status === 'failed')
                                                    <span class="badge bg-danger">
                                                        <i class="demo-psi-close me-1"></i>Failed
                                                    </span>
                                                @else
                                                    <span class="badge bg-warning text-dark">
                                                        <i class="demo-psi-clock me-1"></i>Pending
                                                    </span>
                                                @endif
                                            </div>
                                        </div>

                                        @if ($apiLog->description)
                                            <div class="col-12">
                                                <label class="form-label fw-semibold text-muted small">Error Message</label>
                                                <div class="bg-danger bg-opacity-10 border border-danger rounded p-3">
                                                    <div class="text-danger small">
                                                        <i class="demo-psi-warning me-1"></i>
                                                        {{ $apiLog->description }}
                                                    </div>
                                                </div>
                                            </div>
                                        @endif

                                        @if ($apiLog->response)
                                            <div class="col-12">
                                                <label class="form-label fw-semibold text-muted small">Response Data</label>
                                                <div class="bg-dark text-light p-3 rounded border">
                                                    <pre class="mb-0 text-light small" style="max-height: 200px; overflow-y: auto;">{{ $apiLog->response }}</pre>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="text-center py-5">
                        <div class="text-muted">
                            <i class="demo-psi-information display-4 text-muted"></i>
                            <h6 class="mt-3 mb-2">No API Log Details</h6>
                            <p class="text-muted mb-0">The requested API log details could not be found.</p>
                        </div>
                    </div>
                @endif
            </div>

            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="closeModal">
                    <i class="demo-psi-close me-1"></i>Close
                </button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('showApiLogDetail', (apiLogId) => {
                const modalElement = document.getElementById('apiLogDetailModal');
                if (modalElement) {
                    const modal = new bootstrap.Modal(modalElement);
                    modal.show();
                }
            });

            // Handle modal close event
            document.getElementById('apiLogDetailModal').addEventListener('hidden.bs.modal', function() {
                // Reset any form data or state if needed
                console.log('API Log detail modal closed');
            });
        });
    </script>
</div>
