<div class="card shadow-sm border-0">
    <!-- Search and Filters Section -->
    <div class="card-body bg-light">
        <div class="row g-3 align-items-end">
            <!-- Product Name Filter -->
            <div class="col-md-7">
                <label class="form-label fw-semibold mb-2">
                    <i class="fa fa-user me-1 text-warning"></i> Product Name
                </label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="fa fa-tag text-muted"></i>
                    </span>
                    <input type="text" wire:model.live.debounce.300ms="productName" class="form-control border-start-0" placeholder="Enter product name..." autocomplete="off">
                </div>
            </div>
            <!-- Product Code Filter -->
            <div class="col-md-3">
                <label class="form-label fw-semibold mb-2">
                    <i class="fa fa-barcode me-1 text-info"></i> Product Code
                </label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="fa fa-barcode text-muted"></i>
                    </span>
                    <input type="text" wire:model.live.debounce.300ms="productCode" class="form-control border-start-0" placeholder="Enter product code..." autocomplete="off" id="productCodeInput">
                    <button type="button" class="btn btn-outline-primary border-start-0" onclick="startBarcodeScanner()" title="Scan Barcode">
                        <i class="fa fa-camera"></i>
                    </button>
                </div>
                <small class="text-muted">Click the camera icon to scan barcode</small>
            </div>

            <!-- Branch Filter -->
            <div class="col-md-2" wire:ignore>
                <label class="form-label fw-semibold mb-2">
                    <i class="fa fa-building me-1 text-success"></i> Branch
                </label>
                <select wire:model.live="selectedBranch" class="form-select">
                    <option value="">All Branches</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Filter Actions -->
        <div class="row mt-3">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex gap-2">
                        <button wire:click="clearFilters" class="btn btn-outline-secondary btn-sm">
                            <i class="fa fa-times me-1"></i> Clear Filters
                        </button>
                        @if ($loading)
                            <div class="d-flex align-items-center text-muted">
                                <div class="spinner-border spinner-border-sm me-2" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <small>Searching...</small>
                            </div>
                        @endif
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <label class="form-label mb-0 me-2 small">Show:</label>
                        <select wire:model.live="limit" class="form-select form-select-sm" style="width: auto;">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Results Section -->
    <div class="card-body p-0">
        @if (count($products) > 0)
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="border-0 text-end"> <i class="fa fa-barcode me-1 text-muted"></i> SKU </th>
                            <th class="border-0"> <i class="fa fa-tag me-1 text-muted"></i> Name </th>
                            <th class="border-0 text-end"> <i class="fa fa-ruler me-1 text-muted"></i> Size </th>
                            <th class="border-0 text-end"> <i class="fa fa-barcode me-1 text-muted"></i> Bar </th>
                            <th class="border-0 text-end"> <i class="fa fa-money me-1 text-muted"></i> Price </th>
                            <th class="border-0"> <i class="fa fa-building me-1 text-muted"></i> Br </th>
                            <th class="border-0 text-end"> <i class="fa fa-cubes me-1 text-muted"></i> QTY </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($products as $item)
                            <tr class="align-middle">
                                <td class="text-end"> <code class="text-primary">{{ $item->product->code }}</code> </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="fw-medium">
                                            {{ $item->product->name }}
                                        </div>
                                    </div>
                                </td>
                                <td class="text-end"> <code class="text-primary">{{ $item->product->size }}</code> </td>
                                <td class="text-end"> <code class="text-primary">{{ $item->barcode }}</code> </td>
                                <td class="text-end"> <code class="text-primary">{{ $item->product->mrp }}</code> </td>
                                <td> <span class="fw-medium">{{ $item->branch->name }}</span> </td>
                                <td class="text-end">
                                    <span class="badge {{ $item->quantity > 0 ? 'bg-success' : 'bg-danger' }} fs-6">
                                        {{ $item->quantity }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-5">
                <div class="mb-3">
                    <i class="fa fa-search fa-3x text-muted"></i>
                </div>
                <h5 class="text-muted">No products found</h5>
                <p class="text-muted mb-0">Try adjusting your search criteria or filters.</p>
            </div>
        @endif
    </div>

    <!-- Pagination -->
    @if ($products && $products->hasPages())
        {{ $products->links() }}
    @endif

    <!-- Barcode Scanner Modal -->
    <div class="modal fade" id="barcodeScannerModal" tabindex="-1" aria-labelledby="barcodeScannerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="barcodeScannerModalLabel">
                        <i class="fa fa-camera me-2"></i>Barcode Scanner
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <p class="text-muted">Position the barcode within the camera view</p>
                    </div>
                    <div class="d-flex justify-content-center">
                        <div id="scanner-container" style="width: 100%; max-width: 500px;">
                            <video id="scanner-video" style="width: 100%; height: 300px; border: 2px solid #ddd; border-radius: 8px;"></video>
                        </div>
                    </div>
                    <div class="text-center mt-3">
                        <div id="scanner-status" class="alert alert-info" style="display: none;">
                            <i class="fa fa-search me-2"></i>Scanning for barcode...
                        </div>
                        <div id="scanner-result" class="alert alert-success" style="display: none;">
                            <i class="fa fa-check me-2"></i>Barcode detected: <span id="scanned-code"></span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="applyScannedCode()">Apply Code</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let scanner = null;
        let scannedBarcode = '';

        function startBarcodeScanner() {
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('barcodeScannerModal'));
            modal.show();

            // Initialize scanner after modal is shown
            document.getElementById('barcodeScannerModal').addEventListener('shown.bs.modal', function() {
                initializeScanner();
            });
        }

        function initializeScanner() {
            const video = document.getElementById('scanner-video');
            const statusDiv = document.getElementById('scanner-status');
            const resultDiv = document.getElementById('scanner-result');

            // Show status
            statusDiv.style.display = 'block';
            resultDiv.style.display = 'none';

            // Check if browser supports getUserMedia
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                statusDiv.innerHTML = '<i class="fa fa-times me-2"></i>Camera not supported in this browser';
                statusDiv.className = 'alert alert-danger';
                return;
            }

            // Get camera access
            navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: 'environment', // Use back camera on mobile
                        width: {
                            ideal: 1280
                        },
                        height: {
                            ideal: 720
                        }
                    }
                })
                .then(function(stream) {
                    video.srcObject = stream;
                    video.play();

                    // Initialize barcode scanner library
                    if (typeof Quagga !== 'undefined') {
                        initializeQuaggaScanner();
                    } else {
                        // Fallback: load Quagga library
                        loadQuaggaLibrary();
                    }
                })
                .catch(function(err) {
                    console.error('Camera access error:', err);
                    statusDiv.innerHTML = '<i class="fa fa-times me-2"></i>Unable to access camera. Please check permissions.';
                    statusDiv.className = 'alert alert-danger';
                });
        }

        function loadQuaggaLibrary() {
            // Load Quagga library dynamically
            const script = document.createElement('script');
            script.src = 'https://cdnjs.cloudflare.com/ajax/libs/quagga/0.12.1/quagga.min.js';
            script.onload = function() {
                initializeQuaggaScanner();
            };
            script.onerror = function() {
                document.getElementById('scanner-status').innerHTML = '<i class="fa fa-times me-2"></i>Failed to load scanner library';
                document.getElementById('scanner-status').className = 'alert alert-danger';
            };
            document.head.appendChild(script);
        }

        function initializeQuaggaScanner() {
            const statusDiv = document.getElementById('scanner-status');
            const resultDiv = document.getElementById('scanner-result');

            Quagga.init({
                inputStream: {
                    name: "Live",
                    type: "LiveStream",
                    target: document.getElementById('scanner-video'),
                    constraints: {
                        width: 640,
                        height: 480,
                        facingMode: "environment"
                    },
                },
                decoder: {
                    readers: [
                        "code_128_reader",
                        "ean_reader",
                        "ean_8_reader",
                        "code_39_reader",
                        "code_39_vin_reader",
                        "codabar_reader",
                        "upc_reader",
                        "upc_e_reader",
                        "i2of5_reader"
                    ]
                }
            }, function(err) {
                if (err) {
                    console.error('Quagga initialization error:', err);
                    statusDiv.innerHTML = '<i class="fa fa-times me-2"></i>Failed to initialize scanner';
                    statusDiv.className = 'alert alert-danger';
                    return;
                }

                statusDiv.innerHTML = '<i class="fa fa-search me-2"></i>Scanning for barcode...';
                statusDiv.className = 'alert alert-info';

                Quagga.start();
            });

            Quagga.onDetected(function(result) {
                const code = result.codeResult.code;
                scannedBarcode = code;

                // Show result
                document.getElementById('scanned-code').textContent = code;
                resultDiv.style.display = 'block';
                statusDiv.style.display = 'none';

                // Stop scanning
                Quagga.stop();
            });
        }

        function applyScannedCode() {
            if (scannedBarcode) {
                // Set the scanned barcode to the input field
                document.getElementById('productCodeInput').value = scannedBarcode;

                // Trigger Livewire update
                @this.set('productCode', scannedBarcode);

                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('barcodeScannerModal'));
                modal.hide();

                // Reset
                scannedBarcode = '';
                document.getElementById('scanner-result').style.display = 'none';
            }
        }

        // Clean up when modal is hidden
        document.getElementById('barcodeScannerModal').addEventListener('hidden.bs.modal', function() {
            if (scanner) {
                Quagga.stop();
            }
            const video = document.getElementById('scanner-video');
            if (video.srcObject) {
                video.srcObject.getTracks().forEach(track => track.stop());
            }
        });
    </script>
</div>
