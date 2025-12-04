<div>
    <div class="container-fluid">
        <!-- Compact Page Header -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0 text-dark">
                            <i class="fa fa-barcode text-primary me-2"></i>
                            Barcode Cart
                        </h4>
                        <small class="text-muted">Add products and print barcodes</small>
                    </div>
                    <div class="d-flex gap-2">
                        <button wire:click="clearCart" class="btn btn-outline-danger btn-sm" {{ empty($cartItems) ? 'disabled' : '' }}>
                            <i class="fa fa-trash me-1"></i> Clear
                        </button>
                        <button type="button" class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#keyboardShortcutsModal">
                            <i class="fa fa-keyboard me-1"></i> ⌨️
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Livewire Event Messages -->
        <div class="row">
            <div class="col-12">
                <div id="livewire-messages"></div>
            </div>
        </div>

        <div class="row">
            <!-- Product Selection Section -->
            <div class="col-lg-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-primary ">
                        <h5 class="mb-0 text-white">
                            <i class="fa fa-plus-circle me-2"></i>
                            Add Products to Cart
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Product Search -->
                        <div class="row mb-4">
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">
                                    <i class="fa fa-search me-1 text-primary"></i>Search Products
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="fa fa-search text-muted"></i>
                                    </span>
                                    <input type="text" wire:model.live.debounce.300ms="searchQuery" class="form-control" placeholder="Search by name, barcode, or code...">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">
                                    <i class="fa fa-hashtag me-1 text-primary"></i>Quantity
                                </label>
                                <input type="number" wire:model="quantity" class="form-control" min="1" value="1">
                            </div>
                        </div>

                        <!-- Barcode Scanner -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <label class="form-label fw-semibold">
                                    <i class="fa fa-barcode me-1 text-primary"></i>Barcode Scanner
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-dark text-white">
                                        <i class="fa fa-barcode"></i>
                                    </span>
                                    <input type="text" wire:model.live="barcodeInput" class="form-control" placeholder="Scan barcode or enter manually..." wire:keydown.enter="handleBarcodeScan()">
                                    <button class="btn btn-dark" type="button" wire:click="handleBarcodeScan()">
                                        <i class="fa fa-search"></i> Scan
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Product List -->
                        @if (!empty($products))
                            <div class="row">
                                <div class="col-12">
                                    <h6 class="text-muted mb-3">
                                        <i class="fa fa-list me-1"></i>Search Results ({{ count($products) }})
                                    </h6>
                                    <div class="row g-2">
                                        @foreach ($products as $product)
                                            <div class="col-md-4 col-lg-3 col-xl-2">
                                                <div class="card product-card-pos h-100 border-0 shadow-sm">
                                                    <div class="card-body p-2 text-center">
                                                        <div class="product-image mb-2">
                                                            <img src="{{ $product['thumbnail'] ?? cache('logo') }}" alt="{{ $product['name'] }}" class="rounded"
                                                                style="width: 50px; height: 50px; object-fit: cover;">
                                                        </div>
                                                        <h6 class="mb-1 fw-semibold text-dark" title="{{ $product['name'] }}">
                                                            {{ $product['name'] }}
                                                        </h6>
                                                        <div class="text-muted small mb-2">
                                                            <div class="badge bg-info badge-sm mb-1">Barcode : {{ $product['barcode'] }}</div>
                                                            @if (isset($product['size']) && $product['size'])
                                                                <div class="badge bg-warning badge-sm mb-1">Size : {{ $product['size'] }}</div>
                                                            @endif
                                                          <div class="badge bg-success badge-sm me-1 mt-1">{{ currency($product['mrp']) }}</div>

                                                        </div>
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <small class="text-muted">
                                                                Stock: {{ $product['quantity'] }}
                                                            </small>
                                                            <button wire:click="selectProduct({{ $product['id'] }})" class="btn btn-sm btn-primary btn-xs">
                                                                <i class="fa fa-plus fa-xs"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Cart Summary -->
            <div class="col-lg-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="fa fa-shopping-cart me-2"></i>
                            Cart ({{ count($cartItems) }})
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        @if (empty($cartItems))
                            <div class="text-center py-4">
                                <i class="fa fa-shopping-cart fa-2x text-muted mb-2"></i>
                                <p class="text-muted small">Cart is empty</p>
                            </div>
                        @else
                            <!-- Cart Items -->
                            <div class="cart-items" style="max-height: 400px; overflow-y: auto;">
                                @foreach ($cartItems as $cartKey => $item)
                                    <div class="cart-item-compact border-bottom p-2">
                                        <div class="d-flex align-items-center">
                                            <div class="product-image me-2">
                                                <img src="{{ $item['thumbnail'] ?? cache('logo') }}" alt="{{ $item['name'] }}" class="rounded"
                                                    style="width: 35px; height: 35px; object-fit: cover;">
                                            </div>
                                            <div class="flex-grow-1 min-w-0">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div class="min-w-0 flex-grow-1">
                                                        <h6 class="mb-0 fw-semibold" title="{{ $item['name'] }}">
                                                            {{ $item['name'] }}
                                                        </h6>
                                                        <div class="text-muted small">
                                                            <span class="badge bg-secondary badge-sm">Barcode : {{ $item['barcode'] }}</span>
                                                            @if (isset($item['size']) && $item['size'])
                                                                <span class="badge bg-warning badge-sm">Size : {{ $item['size'] }}</span>
                                                            @endif
                                                            <span class="badge bg-primary badge-sm">{{ currency($item['mrp']) }}</span>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex align-items-center gap-1">
                                                        <div class="quantity-controls-compact">
                                                            <button wire:click="updateQuantity({{ $cartKey }}, {{ $item['quantity'] - 1 }})" class="btn btn-xs btn-outline-secondary">
                                                                <i class="fa fa-minus fa-xs"></i>
                                                            </button>
                                                            <span class="mx-1 fw-bold small">{{ $item['quantity'] }}</span>
                                                            <button wire:click="updateQuantity({{ $cartKey }}, {{ $item['quantity'] + 1 }})" class="btn btn-xs btn-outline-secondary">
                                                                <i class="fa fa-plus fa-xs"></i>
                                                            </button>
                                                        </div>
                                                        <button wire:click="removeFromCart({{ $cartKey }})" class="btn btn-xs btn-outline-danger ms-1">
                                                            <i class="fa fa-times fa-xs"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <!-- Cart Totals -->
                            <div class="cart-totals border-top p-3 bg-light">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="fw-semibold">Total Items:</span>
                                    <span class="fw-bold text-primary fs-5">{{ $this->getTotalQuantity() }}</span>
                                </div>
                                <!-- Cart Footer Actions -->
                                <div class="d-flex gap-2">
                                    <button wire:click="printBarcodes" class="btn btn-primary btn-sm flex-fill" {{ empty($cartItems) ? 'disabled' : '' }}>
                                        <i class="fa fa-print me-1"></i> Print Barcodes
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Keyboard Shortcuts Modal -->
    <div class="modal fade" id="keyboardShortcutsModal" tabindex="-1" aria-labelledby="keyboardShortcutsModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="keyboardShortcutsModalLabel">
                        <i class="fa fa-keyboard me-2"></i>Keyboard Shortcuts
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Shortcut</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><kbd>Ctrl/Cmd + P</kbd></td>
                                            <td>Print Barcodes</td>
                                        </tr>
                                        <tr>
                                            <td><kbd>Ctrl/Cmd + K</kbd></td>
                                            <td>Focus Search</td>
                                        </tr>
                                        <tr>
                                            <td><kbd>Ctrl/Cmd + B</kbd></td>
                                            <td>Focus Barcode Input</td>
                                        </tr>
                                        <tr>
                                            <td><kbd>Enter</kbd></td>
                                            <td>Scan Barcode</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .product-card {
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .product-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
        }

        .cart-item {
            transition: all 0.2s ease;
        }

        .cart-item:hover {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 8px;
            margin: -8px;
        }

.product-card-pos .badge {
    margin: 2px 4px;
}

.badge-sm {
    margin-right: 4px;
    margin-bottom: 4px;
}


        .quantity-controls {
            display: flex;
            align-items: center;
        }

        .quantity-controls .btn {
            width: 32px;
            height: 32px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .cart-totals {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
        }

        /* Professional styling improvements */
        .card {
            transition: all 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1) !important;
        }

        .btn {
            transition: all 0.2s ease;
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .badge {
            font-size: 0.75rem;
        }

        /* Loading states */
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }

        /* Success animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .cart-item {
            animation: fadeInUp 0.3s ease;
        }

        /* Compact cart items */
        .cart-item-compact {
            transition: all 0.2s ease;
        }

        .cart-item-compact:hover {
            background-color: #f8f9fa;
        }

        .quantity-controls-compact {
            display: flex;
            align-items: center;
        }

        .quantity-controls-compact .btn {
            width: 24px;
            height: 24px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
        }

        /* POS-style product cards */
        .product-card-pos {
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .product-card-pos:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
        }

        .product-card-pos .card-body {
            padding: 0.5rem;
        }

        .product-card-pos h6 {
            font-size: 0.8rem;
            line-height: 1.2;
        }

        .badge-sm {
            font-size: 0.65rem;
            padding: 0.25rem 0.5rem;
        }

        /* Responsive improvements */
        @media (max-width: 768px) {
            .barcode-grid {
                grid-template-columns: 1fr;
            }

            .quantity-controls {
                flex-direction: column;
                gap: 5px;
            }

            .product-card-pos {
                margin-bottom: 0.5rem;
            }
        }

        /* Compact layout improvements */
        .min-w-0 {
            min-width: 0;
        }

        .text-truncate {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* Scrollbar styling */
        .cart-items::-webkit-scrollbar {
            width: 6px;
        }

        .cart-items::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        .cart-items::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }

        .cart-items::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        /* Livewire message styling */
        .livewire-message {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 300px;
            animation: slideInRight 0.3s ease;
        }

        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    </style>

    @push('scripts')
        <script>
            document.addEventListener('livewire:init', () => {
                // Handle Livewire events
                Livewire.on('success', (event) => {
                    showMessage(event.message, 'success');
                });

                Livewire.on('error', (event) => {
                    showMessage(event.message, 'error');
                });

                // Function to show messages
                function showMessage(message, type) {
                    const messageDiv = document.createElement('div');
                    messageDiv.className = `livewire-message alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show`;
                    messageDiv.innerHTML = `
            <i class="fa fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

                    document.body.appendChild(messageDiv);

                    // Auto remove after 3 seconds
                    setTimeout(() => {
                        if (messageDiv.parentNode) {
                            messageDiv.remove();
                        }
                    }, 3000);

                    // Handle close button
                    messageDiv.querySelector('.btn-close').addEventListener('click', () => {
                        messageDiv.remove();
                    });
                }

                // Keyboard shortcuts
                document.addEventListener('keydown', function(e) {
                    // Ctrl/Cmd + P to print barcodes
                    if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                        e.preventDefault();
                        @this.call('printBarcodes');
                    }

                    // Ctrl/Cmd + K to focus on search
                    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                        e.preventDefault();
                        document.querySelector('input[wire\\:model\\.live\\.debounce\\.300ms="searchQuery"]').focus();
                    }

                    // Ctrl/Cmd + B to focus on barcode input
                    if ((e.ctrlKey || e.metaKey) && e.key === 'b') {
                        e.preventDefault();
                        document.querySelector('input[wire\\:model="barcodeInput"]').focus();
                    }
                });

                // Auto-focus on barcode input when page loads
                setTimeout(() => {
                    const barcodeInput = document.querySelector('input[wire\\:model="barcodeInput"]');
                    if (barcodeInput) {
                        barcodeInput.focus();
                    }
                }, 1000);
            });
        </script>
    @endpush
</div>
