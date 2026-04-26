<div>
    <form wire:submit="save">

        @if ($errors->any())
            <div class="mb-4 alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
                <div class="d-flex align-items-start">
                    <i class="fa fa-exclamation-circle me-2 mt-1 text-danger"></i>
                    <div>
                        <strong>Please fix the following errors:</strong>
                        <ul class="mb-0 mt-1 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- Purchase Header Details --}}
        <div class="mb-4 shadow-sm card border-0">
            <div class="card-header bg-white border-bottom py-3 px-4">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center me-3"
                        style="width: 40px; height: 40px;">
                        <i class="fa fa-file-text text-primary"></i>
                    </div>
                    <div>
                        <h5 class="mb-0 fw-bold">Purchase Details</h5>
                        <small class="text-muted">LPO Purchase invoice information</small>
                    </div>
                </div>
            </div>

            <div class="card-body px-4 py-4">
                <div class="row g-3">
                    <div class="col-md-5" wire:ignore>
                        <label class="form-label fw-semibold">
                            <i class="fa fa-shopping-cart text-muted me-1"></i> Local Purchase Order
                        </label>
                        {{ html()->select('local_purchase_order_id', $approvedLpos)->value($this->local_purchase_order_id)->class('lpo-select')->id('lpo_id')->placeholder('Select LPO') }}
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">
                            <i class="fa fa-building text-muted me-1"></i> Vendor
                        </label>
                        <div class="form-control bg-light border-dashed d-flex align-items-center" style="min-height: 38px;">
                            @if ($vendor_name)
                                <span class="fw-medium">{{ $vendor_name }}</span>
                            @else
                                <span class="text-muted fst-italic">Auto-filled from LPO</span>
                            @endif
                        </div>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label fw-semibold">
                            <i class="fa fa-calendar text-muted me-1"></i> Date
                        </label>
                        <input type="date" wire:model="date" class="form-control" value="{{ $this->date }}">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label fw-semibold">
                            <i class="fa fa-barcode text-muted me-1"></i> Invoice No *
                        </label>
                        <input type="text" wire:model="invoice_no" class="form-control" placeholder="Enter invoice number" required>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label fw-semibold">
                            <i class="fa fa-sticky-note text-muted me-1"></i> Remarks
                        </label>
                        <textarea wire:model="remarks" class="form-control" rows="2"
                            placeholder="Add any notes about this purchase (optional)">{{ $this->remarks }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- Purchase Items --}}
        <div class="mb-4 border-0 shadow-sm card overflow-hidden" wire:ignore
            x-data="lpoPurchaseProducts(@js($items), @js($this->local_purchase_order_id))">

            <div class="card-header bg-white border-bottom px-4 py-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center me-3"
                            style="width: 40px; height: 40px;">
                            <i class="fa fa-cubes text-success"></i>
                        </div>
                        <div>
                            <h5 class="mb-0 fw-bold">Purchase Items</h5>
                            <small class="text-muted" x-show="items.length > 0">
                                <span x-text="items.length"></span> items — Total: <span x-text="grandTotal()"></span>
                            </small>
                            <small class="text-muted" x-show="items.length === 0">
                                Select an LPO above to load items
                            </small>
                        </div>
                    </div>
                    <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-2"
                        x-show="items.length > 0"
                        x-text="items.length + ' item' + (items.length > 1 ? 's' : '')"></span>
                </div>
            </div>

            <div class="card-body p-0">
                {{-- Empty State --}}
                <template x-if="items.length === 0">
                    <div class="py-5 text-center">
                        <div class="mb-3">
                            <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center"
                                style="width: 80px; height: 80px;">
                                <i class="fa fa-inbox fa-2x text-muted opacity-50"></i>
                            </div>
                        </div>
                        <h6 class="text-muted fw-semibold">No Items Loaded</h6>
                        <p class="text-muted small mb-0">Select a Local Purchase Order above to<br>load items for this purchase</p>
                    </div>
                </template>

                {{-- Items Table --}}
                <template x-if="items.length > 0">
                    <div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr class="bg-light">
                                        <th class="ps-4 text-muted fw-semibold text-uppercase small" style="width: 40px;">#</th>
                                        <th class="text-muted fw-semibold text-uppercase small">Product</th>
                                        <th class="text-end text-muted fw-semibold text-uppercase small" style="width: 110px;">Qty</th>
                                        <th class="text-end text-muted fw-semibold text-uppercase small" style="width: 120px;">Unit Price</th>
                                        <th class="text-end text-muted fw-semibold text-uppercase small" style="width: 110px;">Discount</th>
                                        <th class="text-end text-muted fw-semibold text-uppercase small" style="width: 90px;">Tax %</th>
                                        <th class="text-end text-muted fw-semibold text-uppercase small pe-4" style="width: 120px;">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(row, index) in items" :key="index">
                                        <tr>
                                            <td class="ps-4 text-muted" x-text="index + 1"></td>
                                            <td>
                                                <div class="fw-medium" x-text="row.product_name"></div>
                                                <small class="text-muted" x-text="row.unit_name"></small>
                                            </td>
                                            <td class="text-end">
                                                <input type="number"
                                                    class="form-control form-control-sm text-end"
                                                    min="0.01" step="0.01"
                                                    :max="row.ordered_quantity"
                                                    x-model.number="row.quantity"
                                                    @input="if(row.quantity > row.ordered_quantity) row.quantity = row.ordered_quantity; sync()">
                                            </td>
                                            <td class="text-end">
                                                <input type="number"
                                                    class="form-control form-control-sm text-end"
                                                    min="0" step="0.01"
                                                    x-model.number="row.unit_price" @input="sync()">
                                            </td>
                                            <td class="text-end">
                                                <input type="number"
                                                    class="form-control form-control-sm text-end"
                                                    min="0" step="0.01"
                                                    x-model.number="row.discount" @input="sync()">
                                            </td>
                                            <td class="text-end">
                                                <input type="number"
                                                    class="form-control form-control-sm text-end"
                                                    min="0" step="0.01"
                                                    x-model.number="row.tax" @input="sync()">
                                            </td>
                                            <td class="text-end pe-4 fw-medium" x-text="rowTotal(row)"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        {{-- Summary Footer --}}
                        <div class="bg-light border-top px-4 py-3">
                            <div class="row align-items-center">
                                <div class="col-md-6"></div>
                                <div class="col-md-6">
                                    <table class="table table-sm table-borderless mb-0">
                                        <tr>
                                            <td class="text-end text-muted">Gross Amount:</td>
                                            <td class="text-end fw-medium" style="width: 130px;" x-text="totalGross()"></td>
                                        </tr>
                                        <tr>
                                            <td class="text-end text-muted">Item Discount:</td>
                                            <td class="text-end text-danger" x-text="'- ' + totalDiscount()"></td>
                                        </tr>
                                        <tr>
                                            <td class="text-end text-muted">Tax:</td>
                                            <td class="text-end" x-text="'+ ' + totalTax()"></td>
                                        </tr>
                                        <tr class="border-top">
                                            <td class="text-end fw-bold">Grand Total:</td>
                                            <td class="text-end fw-bold text-success fs-5" x-text="grandTotal()"></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="shadow-sm card border-0">
            <div class="card-body px-4 py-3 d-flex justify-content-between align-items-center">
                <a href="{{ route('lpo-purchase::index') }}" class="btn btn-light px-4">
                    <i class="fa fa-arrow-left me-2"></i> Back to List
                </a>

                <button type="submit" class="btn btn-success px-4 shadow-sm">
                    <i class="fa fa-check me-2"></i> {{ $purchase_id ? 'Update Purchase' : 'Save Purchase' }}
                </button>
            </div>
        </div>

    </form>

    @push('scripts')
        <script>
            $(document).ready(function() {
                new TomSelect('#lpo_id', {
                    plugins: ['remove_button'],
                    dropdownParent: 'body',
                    sortField: [{
                        field: '$order',
                        direction: 'asc'
                    }],
                });

                $('#lpo_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('local_purchase_order_id', value);
                    if (value) {
                        @this.getLpoItems().then(function(data) {
                            window.dispatchEvent(new CustomEvent('lpo-purchase-items-loaded', {
                                detail: data
                            }));
                        });
                    } else {
                        window.dispatchEvent(new CustomEvent('lpo-purchase-items-loaded', {
                            detail: []
                        }));
                    }
                });
            });

            function lpoPurchaseProducts(initialItems, lpoId) {
                return {
                    items: Array.isArray(initialItems) && initialItems.length ? [...initialItems] : [],

                    init() {
                        window.addEventListener('lpo-purchase-items-loaded', (e) => {
                            this.items = e.detail || [];
                            this.sync();
                        });
                    },

                    rowTotal(row) {
                        const gross = Number(row.quantity || 0) * Number(row.unit_price || 0);
                        const net = gross - Number(row.discount || 0);
                        const taxAmt = net * (Number(row.tax || 0) / 100);
                        return (net + taxAmt).toFixed(2);
                    },

                    totalGross() {
                        return this.items.reduce((sum, i) => sum + (Number(i.quantity || 0) * Number(i.unit_price || 0)), 0).toFixed(2);
                    },

                    totalDiscount() {
                        return this.items.reduce((sum, i) => sum + Number(i.discount || 0), 0).toFixed(2);
                    },

                    totalTax() {
                        return this.items.reduce((sum, i) => {
                            const gross = Number(i.quantity || 0) * Number(i.unit_price || 0);
                            const net = gross - Number(i.discount || 0);
                            return sum + (net * (Number(i.tax || 0) / 100));
                        }, 0).toFixed(2);
                    },

                    grandTotal() {
                        return this.items.reduce((sum, i) => sum + Number(this.rowTotal(i)), 0).toFixed(2);
                    },

                    sync() {
                        @this.set('items', this.items);
                    },
                }
            }
        </script>
        <style>
            .ts-dropdown {
                z-index: 2000 !important;
            }
        </style>
    @endpush
</div>
