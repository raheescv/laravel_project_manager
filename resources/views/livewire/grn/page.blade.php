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

        {{-- GRN Header Details --}}
        <div class="mb-4 shadow-sm card border-0">
            <div class="card-header bg-white border-bottom py-3 px-4">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center me-3"
                        style="width: 40px; height: 40px;">
                        <i class="fa fa-file-text text-primary"></i>
                    </div>
                    <div>
                        <h5 class="mb-0 fw-bold">GRN Details</h5>
                        <small class="text-muted">Goods Received Note information</small>
                    </div>
                </div>
            </div>

            <div class="card-body px-4 py-4">
                <div class="row g-3">
                    <div class="col-md-4" wire:ignore>
                        <label class="form-label fw-semibold">
                            <i class="fa fa-shopping-cart text-muted me-1"></i> Local Purchase Order
                        </label>
                        {{ html()->select('local_purchase_order_id', $approvedLpos)->value($this->local_purchase_order_id)->class('lpo-select')->id('lpo_id')->placeholder('Select LPO') }}
                        @if ($local_purchase_order_id)
                            <a href="{{ route('lpo::view', $local_purchase_order_id) }}" target="_blank" class="small text-primary mt-1 d-inline-block">
                                <i class="fa fa-external-link me-1"></i> View LPO #{{ $local_purchase_order_id }}
                            </a>
                        @endif
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">
                            <i class="fa fa-building text-muted me-1"></i> Vendor
                        </label>
                        <div class="form-control bg-light d-flex align-items-center" style="min-height: 38px;">
                            @if ($vendor_name)
                                <span class="fw-medium">{{ $vendor_name }}</span>
                            @else
                                <span class="text-muted fst-italic">Auto-filled from LPO</span>
                            @endif
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">
                            <i class="fa fa-calendar text-muted me-1"></i> Received Date
                        </label>
                        <input type="date" wire:model="date" class="form-control" value="{{ $this->date }}">
                    </div>

                    <div class="col-md-12">
                        <label class="form-label fw-semibold">
                            <i class="fa fa-sticky-note text-muted me-1"></i> Remarks
                        </label>
                        <textarea wire:model="remarks" class="form-control" rows="2"
                            placeholder="Add any notes about this delivery (optional)">{{ $this->remarks }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- Received Items --}}
        <div class="mb-4 border-0 shadow-sm card overflow-hidden" wire:ignore
            x-data="grnProducts(@js($items), @js($this->local_purchase_order_id))">

            <div class="card-header bg-white border-bottom px-4 py-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center me-3"
                            style="width: 40px; height: 40px;">
                            <i class="fa fa-cubes text-success"></i>
                        </div>
                        <div>
                            <h5 class="mb-0 fw-bold">Received Items</h5>
                            <small class="text-muted" x-show="items.length > 0">
                                <span x-text="totalReceived()"></span> of <span x-text="totalOrdered()"></span> units received
                                (<span x-text="overallPercent()"></span>%)
                            </small>
                            <small class="text-muted" x-show="items.length === 0">
                                Select an LPO above to load items
                            </small>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2" x-show="items.length > 0">
                        <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-3"
                            @click="receiveAll()" title="Set all quantities to ordered amounts">
                            <i class="fa fa-check me-1"></i> Receive All
                        </button>
                        <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-2"
                            x-text="items.length + ' item' + (items.length > 1 ? 's' : '')"></span>
                    </div>
                </div>

                {{-- Overall Progress Bar --}}
                <div x-show="items.length > 0" class="mt-3">
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-success" role="progressbar"
                            :style="'width: ' + overallPercent() + '%'"
                            :class="{'bg-warning': overallPercent() < 50, 'bg-info': overallPercent() >= 50 && overallPercent() < 100, 'bg-success': overallPercent() >= 100}">
                        </div>
                    </div>
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
                        <p class="text-muted small mb-0">Select a Local Purchase Order above to<br>load items for receiving</p>
                    </div>
                </template>

                {{-- Items Table --}}
                <template x-if="items.length > 0">
                    <div>
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr class="bg-light">
                                    <th class="ps-4 text-muted fw-semibold text-uppercase small" style="width: 50px;">#</th>
                                    <th class="text-muted fw-semibold text-uppercase small">Product</th>
                                    <th class="text-center text-muted fw-semibold text-uppercase small" style="width: 120px;">Ordered</th>
                                    <th class="text-center text-muted fw-semibold text-uppercase small" style="width: 160px;">Received</th>
                                    <th class="text-center text-muted fw-semibold text-uppercase small" style="width: 100px;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(row, index) in items" :key="index">
                                    <tr :class="{'table-success bg-opacity-10': row.quantity >= row.ordered_quantity, 'table-warning bg-opacity-10': row.quantity > 0 && row.quantity < row.ordered_quantity}">
                                        <td class="ps-4 text-muted" x-text="index + 1"></td>
                                        <td>
                                            <div class="fw-medium" x-text="row.product_name"></div>
                                            {{-- Mini progress under product name --}}
                                            <div class="progress mt-1" style="height: 3px; width: 120px;">
                                                <div class="progress-bar"
                                                    :class="{'bg-success': row.quantity >= row.ordered_quantity, 'bg-warning': row.quantity > 0 && row.quantity < row.ordered_quantity, 'bg-secondary': !row.quantity}"
                                                    :style="'width: ' + Math.min(100, (row.quantity / row.ordered_quantity * 100)) + '%'">
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-dark border px-3 py-2" x-text="row.ordered_quantity"></span>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex align-items-center justify-content-center gap-1">
                                                <button type="button" class="btn btn-sm btn-light border-0 rounded-circle p-0 d-flex align-items-center justify-content-center"
                                                    style="width: 28px; height: 28px;"
                                                    @click="if(row.quantity > 0) { row.quantity = Math.max(0, row.quantity - 1); sync(); }">
                                                    <i class="fa fa-minus small text-muted"></i>
                                                </button>
                                                <input type="number"
                                                    class="form-control form-control-sm text-center border-0 bg-light rounded-pill fw-semibold"
                                                    style="width: 80px;"
                                                    min="0" step="0.01" :max="row.ordered_quantity"
                                                    x-model.number="row.quantity"
                                                    @input="if(row.quantity > row.ordered_quantity) row.quantity = row.ordered_quantity; sync()">
                                                <button type="button" class="btn btn-sm btn-light border-0 rounded-circle p-0 d-flex align-items-center justify-content-center"
                                                    style="width: 28px; height: 28px;"
                                                    @click="if(row.quantity < row.ordered_quantity) { row.quantity = Math.min(row.ordered_quantity, row.quantity + 1); sync(); }">
                                                    <i class="fa fa-plus small text-muted"></i>
                                                </button>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <template x-if="row.quantity >= row.ordered_quantity">
                                                <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2 py-1">
                                                    <i class="fa fa-check-circle me-1"></i> Full
                                                </span>
                                            </template>
                                            <template x-if="row.quantity > 0 && row.quantity < row.ordered_quantity">
                                                <span class="badge bg-warning bg-opacity-10 text-warning rounded-pill px-2 py-1">
                                                    <i class="fa fa-clock me-1"></i> Partial
                                                </span>
                                            </template>
                                            <template x-if="!row.quantity || row.quantity == 0">
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill px-2 py-1">
                                                    <i class="fa fa-minus-circle me-1"></i> None
                                                </span>
                                            </template>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>

                        {{-- Summary Footer --}}
                        <div class="bg-light border-top px-4 py-3">
                            <div class="row align-items-center">
                                <div class="col-md-4">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="text-center">
                                            <div class="small text-muted text-uppercase fw-semibold">Full</div>
                                            <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-1 mt-1"
                                                x-text="items.filter(i => i.quantity >= i.ordered_quantity).length">
                                            </span>
                                        </div>
                                        <div class="text-center">
                                            <div class="small text-muted text-uppercase fw-semibold">Partial</div>
                                            <span class="badge bg-warning bg-opacity-10 text-warning rounded-pill px-3 py-1 mt-1"
                                                x-text="items.filter(i => i.quantity > 0 && i.quantity < i.ordered_quantity).length">
                                            </span>
                                        </div>
                                        <div class="text-center">
                                            <div class="small text-muted text-uppercase fw-semibold">None</div>
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill px-3 py-1 mt-1"
                                                x-text="items.filter(i => !i.quantity || i.quantity == 0).length">
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-8 text-end">
                                    <div class="d-flex justify-content-end align-items-center gap-4">
                                        <div>
                                            <small class="text-muted d-block">Total Ordered</small>
                                            <span class="fw-bold" x-text="totalOrdered()"></span>
                                        </div>
                                        <div>
                                            <small class="text-muted d-block">Total Received</small>
                                            <span class="fw-bold text-success" x-text="totalReceived()"></span>
                                        </div>
                                        <div>
                                            <small class="text-muted d-block">Completion</small>
                                            <span class="fw-bold"
                                                :class="{'text-success': overallPercent() >= 100, 'text-warning': overallPercent() >= 50 && overallPercent() < 100, 'text-danger': overallPercent() < 50}"
                                                x-text="overallPercent() + '%'">
                                            </span>
                                        </div>
                                    </div>
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
                <a href="{{ route('grn::index') }}" class="btn btn-light px-4">
                    <i class="fa fa-arrow-left me-2"></i> Back to List
                </a>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success px-4 shadow-sm">
                        <i class="fa fa-check me-2"></i> {{ $grn_id ? 'Update GRN' : 'Save GRN' }}
                    </button>
                </div>
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
                            window.dispatchEvent(new CustomEvent('lpo-items-loaded', {
                                detail: data
                            }));
                        });
                    } else {
                        window.dispatchEvent(new CustomEvent('lpo-items-loaded', {
                            detail: []
                        }));
                    }
                });
            });

            function grnProducts(initialItems, lpoId) {
                return {
                    items: Array.isArray(initialItems) && initialItems.length ? [...initialItems] : [],

                    init() {
                        window.addEventListener('lpo-items-loaded', (e) => {
                            this.items = e.detail || [];
                            this.sync();
                        });
                    },

                    receiveAll() {
                        this.items.forEach(item => {
                            item.quantity = item.ordered_quantity;
                        });
                        this.sync();
                    },

                    totalOrdered() {
                        return this.items.reduce((sum, i) => sum + Number(i.ordered_quantity || 0), 0);
                    },

                    totalReceived() {
                        return this.items.reduce((sum, i) => sum + Number(i.quantity || 0), 0);
                    },

                    overallPercent() {
                        const ordered = this.totalOrdered();
                        if (ordered === 0) return 0;
                        return Math.round((this.totalReceived() / ordered) * 100);
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
