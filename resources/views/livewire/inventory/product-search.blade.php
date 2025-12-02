<div class="card shadow-sm border-0">
    <!-- Search and Filters Section -->
    <div class="card-body bg-light">
        <div class="row g-3 align-items-end">
            <!-- Product Name Filter -->
            <div class="col-md-4">
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
            <div class="col-md-2">
                <label class="form-label fw-semibold mb-2">
                    <i class="fa fa-barcode me-1 text-info"></i> Product Code
                </label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="fa fa-barcode text-muted"></i>
                    </span>
                    <input type="text" wire:model.live.debounce.300ms="productCode" class="form-control border-start-0" placeholder="Enter  code..." autocomplete="off" id="productCodeInput">
                </div>
            </div>
            <!-- Product Barcode Filter -->
            <div class="col-md-2">
                <label class="form-label fw-semibold mb-2">
                    <i class="fa fa-barcode me-1 text-info"></i> Product Barcode
                </label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="fa fa-barcode text-muted"></i>
                    </span>
                    <input type="text" wire:model.live.debounce.300ms="productBarcode" class="form-control border-start-0 barcode-input" placeholder="Enter  barcode..." autocomplete="off"
                        id="productBarcodeInput">
                    {{-- <button type="button" class="btn btn-outline-primary border-start-0 scanner-button" onclick="startBarcodeScanner()" title="Scan Barcode">
                        <i class="fa fa-camera"></i>
                    </button> --}}
                </div>
            </div>

            <!-- Branch Filter -->
            <div class="col-md-2" wire:ignore>
                <label class="form-label fw-semibold mb-2">
                    <i class="fa fa-building me-1 text-success"></i> Branch
                </label>
                {{ html()->select('branch_id')->class('select-branch_id-list')->multiple()->id('branch_id') }}
            </div>

            <!-- Non-Zero Filter -->
            <div class="col-md-2">
                <label class="form-label fw-semibold mb-2">
                    <i class="fa fa-filter me-1 text-warning"></i> Stock
                </label>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" wire:model.live="showNonZeroOnly" id="showNonZeroOnly">
                    <br>
                    <br>
                    <label class="form-check-label small" for="showNonZeroOnly">
                        In stock only
                    </label>
                </div>
            </div>

            <!-- Show Barcode Codes Filter -->
            <div class="col-md-2">
                <label class="form-label fw-semibold mb-2">
                    <i class="fa fa-barcode me-1 text-info"></i> Barcode SKU
                </label>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" wire:model.live="showBarcodeCodes" id="showBarcodeCodes">
                    <br>
                    <br>
                    <label class="form-check-label small" for="showBarcodeCodes" title="Show products that have barcode code`s SKU">
                        Show barcode SKU
                        <i class="fa fa-info-circle ms-1 text-muted" title="Display products that have barcode code`s SKU"></i>
                    </label>
                </div>
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
                        <button type="button" class="btn btn-outline-primary btn-sm scanner-button" onclick="startBarcodeScanner()" title="Quick Barcode Scan (Ctrl/Cmd + B)">
                            <i class="fa fa-camera me-1"></i> Quick Scan
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
                            <th class="border-0 text-end" style="cursor: pointer;">
                                <i class="fa fa-barcode me-1 text-muted"></i>
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="products.code" label="SKU" />
                            </th>
                            <th class="border-0" style="cursor: pointer;">
                                <i class="fa fa-tag me-1 text-muted"></i>
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="products.name" label="Name" />
                            </th>
                            <th class="border-0 text-end" style="cursor: pointer;">
                                <i class="fa fa-ruler me-1 text-muted"></i>
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="products.size" label="Size" />
                            </th>
                            <th class="border-0 text-end" style="cursor: pointer;">
                                <i class="fa fa-barcode me-1 text-muted"></i>
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="inventories.barcode" label="Barcode" />
                            </th>
                            <th class="border-0 text-end" style="cursor: pointer;">
                                <i class="fa fa-money me-1 text-muted"></i>
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="products.mrp" label="Price" />
                            </th>
                            <th class="border-0" style="cursor: pointer;">
                                <i class="fa fa-building me-1 text-muted"></i>
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="branches.name" label="Br" />
                            </th>
                            <th class="border-0 text-end" style="cursor: pointer;">
                                <i class="fa fa-cubes me-1 text-muted"></i>
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="inventories.quantity" label="QTY" />
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($products as $item)
                            <tr class="align-middle">
                                <td class="text-end"> <code class="text-primary">{{ $item->code }}</code> </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="fw-medium">
                                            {{ $item->name }}
                                        </div>
                                    </div>
                                </td>
                                <td class="text-end"> <code class="text-primary">{{ $item->size }}</code> </td>
                                <td class="text-end"> <code class="text-primary">{{ $item->barcode }}</code> </td>
                                <td class="text-end"> <code class="text-primary">{{ currency($item->mrp) }}</code> </td>
                                <td> <span class="fw-medium">{{ $item->branch_name }}</span> </td>
                                <td class="text-end">
                                    <span class="badge {{ $item->quantity > 0 ? 'bg-success' : 'bg-danger' }} fs-6">
                                        {{ $item->quantity }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th class="text-end" colspan="6">Total</th>
                            <th class="text-center">
                                <span class="badge bg-success fs-6">
                                    {{ $products->sum('quantity') }}
                                </span>
                            </th>
                        </tr>
                    </tfoot>
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
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="barcodeScannerModalLabel">
                        <i class="fa fa-camera me-2"></i>Barcode Scanner
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <p class="text-muted">Position the barcode within the camera view</p>
                        <div class="alert alert-info small">
                            <i class="fa fa-info-circle me-2"></i>
                            <strong>Tips:</strong> Hold the barcode steady in the center of the view.
                            Use <kbd>Ctrl/Cmd + B</kbd> to quickly open the scanner.
                        </div>
                    </div>
                    <div class="d-flex justify-content-center">
                        <div id="scanner-container" style="width: 100%; max-width: 500px; position: relative;">
                            <video id="scanner-video" style="width: 100%; height: 300px; border: 2px solid #ddd; border-radius: 8px;"></video>
                            <!-- Scanning overlay -->
                            <div class="scanner-overlay"
                                style="position: absolute; top: 20%; left: 20%; right: 20%; bottom: 20%; border: 2px solid #28a745; border-radius: 4px; pointer-events: none;">
                                <div style="position: absolute; top: -2px; left: -2px; width: 20px; height: 20px; border-top: 3px solid #28a745; border-left: 3px solid #28a745;"></div>
                                <div style="position: absolute; top: -2px; right: -2px; width: 20px; height: 20px; border-top: 3px solid #28a745; border-right: 3px solid #28a745;"></div>
                                <div style="position: absolute; bottom: -2px; left: -2px; width: 20px; height: 20px; border-bottom: 3px solid #28a745; border-left: 3px solid #28a745;"></div>
                                <div style="position: absolute; bottom: -2px; right: -2px; width: 20px; height: 20px; border-bottom: 3px solid #28a745; border-right: 3px solid #28a745;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="text-center mt-3">
                        <div id="scanner-status" class="alert alert-info" style="display: none;">
                            <i class="fa fa-search me-2"></i>Scanning for barcode...
                        </div>
                        <div id="scanner-result" class="alert alert-success" style="display: none;">
                            <i class="fa fa-check me-2"></i>Barcode detected: <strong><span id="scanned-code"></span></strong>
                        </div>
                        <div id="scanner-error" class="alert alert-warning" style="display: none;">
                            <i class="fa fa-exclamation-triangle me-2"></i>Adjust position and try again.
                        </div>
                        <div class="small text-muted mt-2">
                            <i class="fa fa-keyboard me-1"></i>Press <kbd>Enter</kbd> in the barcode field to quickly open scanner
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning" onclick="retryScanning()" id="retry-btn" style="display: none;">
                        <i class="fa fa-refresh me-1"></i>Retry
                    </button>
                    <button type="button" class="btn btn-primary" onclick="applyScannedCode()">Apply Code</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <style>
            .scanner-button {
                transition: all 0.3s ease;
            }

            .scanner-button:hover {
                transform: scale(1.05);
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            }

            .scanner-button:active {
                transform: scale(0.95);
            }

            .barcode-input:focus {
                border-color: #007bff;
                box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, .25);
            }

            .scanner-overlay {
                animation: pulse 2s infinite;
            }

            .form-check-input:checked {
                background-color: #17a2b8;
                border-color: #17a2b8;
            }

            .form-check-input:focus {
                border-color: #17a2b8;
                box-shadow: 0 0 0 0.2rem rgba(23, 162, 184, 0.25);
            }

            .filter-section {
                background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                border-radius: 8px;
                padding: 1rem;
                margin-bottom: 1rem;
            }

            @keyframes pulse {
                0% {
                    opacity: 1;
                }

                50% {
                    opacity: 0.7;
                }

                100% {
                    opacity: 1;
                }
            }
        </style>

        <script>
            $(document).ready(function() {
                $('#branch_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('branch_id', value);
                });
            });

            let scanner = null;
            let scannedBarcode = '';

            function startBarcodeScanner() {
                // Show modal
                const modal = new bootstrap.Modal(document.getElementById('barcodeScannerModal'));
                modal.show();

                // Initialize scanner after modal is shown
                document.getElementById('barcodeScannerModal').addEventListener('shown.bs.modal', function() {
                    // Add a small delay to ensure modal is fully shown
                    setTimeout(function() {
                        initializeScanner();
                    }, 300);
                });
            }

            function initializeScanner() {
                const video = document.getElementById('scanner-video');
                const statusDiv = document.getElementById('scanner-status');
                const resultDiv = document.getElementById('scanner-result');

                // Show status
                statusDiv.style.display = 'block';
                resultDiv.style.display = 'none';
                statusDiv.innerHTML = '<i class="fa fa-spinner fa-spin me-2"></i>Initializing camera...';
                statusDiv.className = 'alert alert-info';

                // Check if browser supports getUserMedia
                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    statusDiv.innerHTML = '<i class="fa fa-times me-2"></i>Camera not supported in this browser.';
                    statusDiv.className = 'alert alert-danger';
                    document.getElementById('scanner-error').style.display = 'block';
                    return;
                }

                // Try different camera constraints
                const constraints = [{
                        video: {
                            facingMode: 'environment',
                            width: {
                                min: 640,
                                ideal: 1280,
                                max: 1920
                            },
                            height: {
                                min: 480,
                                ideal: 720,
                                max: 1080
                            }
                        }
                    },
                    {
                        video: {
                            facingMode: 'environment',
                            width: {
                                ideal: 640
                            },
                            height: {
                                ideal: 480
                            }
                        }
                    },
                    {
                        video: {
                            facingMode: 'environment'
                        }
                    }
                ];

                function tryCameraAccess(index) {
                    if (index >= constraints.length) {
                        statusDiv.innerHTML = '<i class="fa fa-times me-2"></i>Unable to access camera. Please check permissions and try again.';
                        statusDiv.className = 'alert alert-danger';
                        document.getElementById('scanner-error').style.display = 'block';
                        return;
                    }

                    navigator.mediaDevices.getUserMedia(constraints[index])
                        .then(function(stream) {
                            video.srcObject = stream;
                            video.play();

                            statusDiv.innerHTML = '<i class="fa fa-spinner fa-spin me-2"></i>Loading scanner library...';

                            // Initialize barcode scanner library
                            if (typeof Quagga !== 'undefined') {
                                initializeQuaggaScanner();
                            } else {
                                // Fallback: load Quagga library
                                loadQuaggaLibrary();
                            }
                        })
                        .catch(function(err) {
                            console.error('Camera access error (attempt ' + (index + 1) + '):', err);
                            tryCameraAccess(index + 1);
                        });
                }

                tryCameraAccess(0);
            }

            function loadQuaggaLibrary() {
                // Load Quagga library dynamically
                const script = document.createElement('script');
                script.src = 'https://cdnjs.cloudflare.com/ajax/libs/quagga/0.12.1/quagga.min.js';
                script.onload = function() {
                    initializeQuaggaScanner();
                };
                script.onerror = function() {
                    // Try alternative CDN
                    const altScript = document.createElement('script');
                    altScript.src = 'https://unpkg.com/quagga@0.12.1/dist/quagga.min.js';
                    altScript.onload = function() {
                        initializeQuaggaScanner();
                    };
                    altScript.onerror = function() {
                        // Try local fallback or show error
                        console.log('All CDN attempts failed');
                        document.getElementById('scanner-status').innerHTML = '<i class="fa fa-times me-2"></i>Scanner library unavailable. Please try again.';
                        document.getElementById('scanner-status').className = 'alert alert-danger';
                        document.getElementById('scanner-error').style.display = 'block';
                    };
                    document.head.appendChild(altScript);
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
                            width: {
                                min: 640,
                                ideal: 1280,
                                max: 1920
                            },
                            height: {
                                min: 480,
                                ideal: 720,
                                max: 1080
                            },
                            facingMode: "environment"
                        },
                        area: { // Only read barcodes in center area
                            top: "15%",
                            right: "15%",
                            left: "15%",
                            bottom: "15%"
                        }
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
                        ],
                        debug: {
                            drawBoundingBox: true,
                            showFrequency: true,
                            drawScanline: true,
                            showPattern: true
                        }
                    },
                    locate: true,
                    frequency: 5, // Lower frequency for better accuracy
                    multiple: false, // Only detect one barcode at a time
                    threshold: 0.2 // Lower threshold for better detection
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

                // Add processing event for better feedback
                Quagga.onProcessed(function(result) {
                    if (result) {
                        if (result.codeResult && result.codeResult.code) {
                            console.log('Barcode detected:', result.codeResult.code);
                        }
                    }
                });

                Quagga.onDetected(function(result) {
                    const code = result.codeResult.code;
                    const confidence = result.codeResult.confidence;
                    console.log('Barcode detected:', code, 'Confidence:', confidence);

                    // More lenient validation for better detection
                    if (code && code.length > 0 && confidence > 0.1) {
                        // Additional validation for common barcode patterns
                        const isValidBarcode = validateBarcode(code);

                        if (isValidBarcode) {
                            scannedBarcode = code;

                            // Show result
                            document.getElementById('scanned-code').textContent = code;
                            resultDiv.style.display = 'block';
                            statusDiv.style.display = 'none';

                            // Stop scanning
                            Quagga.stop();

                            // Auto-apply after short delay
                            setTimeout(function() {
                                applyScannedCode();
                            }, 1000);
                        } else {
                            console.log('Invalid barcode format detected:', code);
                            // Continue scanning for better result
                        }
                    } else {
                        console.log('Low confidence barcode detected:', code, 'Confidence:', confidence);
                        // Continue scanning for better result
                    }
                });

                // Add function to validate barcode format
                function validateBarcode(code) {
                    // Remove any non-alphanumeric characters
                    const cleanCode = code.replace(/[^a-zA-Z0-9]/g, '');

                    // More lenient length check
                    if (cleanCode.length < 4 || cleanCode.length > 30) {
                        return false;
                    }

                    // Check for common invalid patterns
                    const invalidPatterns = [
                        /^0+$/, // All zeros
                        /^1+$/, // All ones
                        /^[01]+$/, // Binary only
                    ];

                    for (let pattern of invalidPatterns) {
                        if (pattern.test(cleanCode)) {
                            return false;
                        }
                    }

                    return true;
                }
            }

            function applyScannedCode() {
                if (scannedBarcode) {
                    // Set the scanned barcode to the input field
                    document.getElementById('productBarcodeInput').value = scannedBarcode;

                    // Trigger Livewire update using the new method
                    @this.setBarcode(scannedBarcode);

                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('barcodeScannerModal'));
                    modal.hide();

                    // Reset
                    scannedBarcode = '';
                    document.getElementById('scanner-result').style.display = 'none';

                    // Show success message
                    showNotification('Barcode scanned successfully: ' + scannedBarcode, 'success');
                }
            }

            function showNotification(message, type = 'info') {
                // Create notification element
                const notification = document.createElement('div');
                notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
                notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
                notification.innerHTML = `
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;

                document.body.appendChild(notification);

                // Auto-remove after 5 seconds
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.remove();
                    }
                }, 5000);
            }



            // Show error message after 8 seconds if no barcode detected
            setTimeout(function() {
                const statusDiv = document.getElementById('scanner-status');
                const errorDiv = document.getElementById('scanner-error');
                const retryBtn = document.getElementById('retry-btn');
                if (statusDiv && statusDiv.style.display !== 'none') {
                    errorDiv.style.display = 'block';
                    retryBtn.style.display = 'inline-block';
                    statusDiv.innerHTML = '<i class="fa fa-exclamation-triangle me-2"></i>Still scanning... Try adjusting position or use manual entry.';
                    statusDiv.className = 'alert alert-warning';
                }
            }, 8000);

            // Add retry button functionality
            function retryScanning() {
                const statusDiv = document.getElementById('scanner-status');
                const errorDiv = document.getElementById('scanner-error');
                const resultDiv = document.getElementById('scanner-result');

                // Reset displays
                errorDiv.style.display = 'none';
                resultDiv.style.display = 'none';
                statusDiv.style.display = 'block';
                statusDiv.innerHTML = '<i class="fa fa-search me-2"></i>Scanning for barcode...';
                statusDiv.className = 'alert alert-info';

                // Restart scanner
                if (typeof Quagga !== 'undefined') {
                    Quagga.stop();
                    setTimeout(function() {
                        Quagga.start();
                    }, 500);
                }
            }



            // Clean up when modal is hidden
            document.getElementById('barcodeScannerModal').addEventListener('hidden.bs.modal', function() {
                if (typeof Quagga !== 'undefined') {
                    Quagga.stop();
                }
                const video = document.getElementById('scanner-video');
                if (video.srcObject) {
                    video.srcObject.getTracks().forEach(track => track.stop());
                }
                // Reset scanner state
                scannedBarcode = '';
                const resultDiv = document.getElementById('scanner-result');
                if (resultDiv) {
                    resultDiv.style.display = 'none';
                }
            });

            // Add keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                // Ctrl/Cmd + B to open barcode scanner
                if ((e.ctrlKey || e.metaKey) && e.key === 'b') {
                    e.preventDefault();
                    // startBarcodeScanner();
                }

                // Enter key in barcode input to open scanner
                if (e.target.id === 'productBarcodeInput' && e.key === 'Enter') {
                    e.preventDefault();
                    // startBarcodeScanner();
                }
            });

            // Add focus event to barcode input for better UX
            document.getElementById('productBarcodeInput').addEventListener('focus', function() {
                this.select(); // Select all text when focused
            });
        </script>
    @endpush
</div>

