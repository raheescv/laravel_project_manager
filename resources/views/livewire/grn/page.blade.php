<div>
    <form wire:submit="save">

        @if ($errors->any())
            <div class="mb-4 alert alert-danger">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mb-4 shadow-sm card">
            <div class="bg-white card-header">
                <h5 class="mb-0 fw-bold">GRN Details</h5>
            </div>

            <div class="card-body">
                <div class="row g-3">

                    <div class="col-md-4" wire:ignore>
                        <label class="form-label">Local Purchase Order</label>
                        {{ html()->select('local_purchase_order_id')->value($this->local_purchase_order_id)->class('select-lpo_id')->id('lpo_id')->placeholder('Select LPO') }}
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Date</label>
                        <input type="date" wire:model="date" class="form-control" value="{{ $this->date }}">
                    </div>

                    <div class="col-md-5">
                        <label class="form-label">Remarks</label>
                        <input type="text" wire:model="remarks" class="form-control" placeholder="Optional remarks">
                    </div>

                </div>
            </div>
        </div>

        <div class="mb-4 border-0 shadow-sm card" wire:ignore x-data="grnProducts(@js($items), @js($this->local_purchase_order_id))">

            <div class="card-header bg-white border-bottom px-4 py-3">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="d-flex align-items-center">
                        <i class="fa fa-cubes text-success me-2"></i>
                        <h5 class="mb-0 fw-bold">Received Items</h5>
                    </div>
                    <span class="badge bg-primary rounded-pill" x-show="items.length > 0"
                        x-text="items.length + ' item' + (items.length > 1 ? 's' : '')"></span>
                </div>

                <div class="text-muted small" x-show="items.length === 0">
                    Select a Local Purchase Order above to load items
                </div>
            </div>

            <div class="card-body p-0">
                <template x-if="items.length === 0">
                    <div class="py-5 text-center text-muted">
                        <i class="fa fa-box-open fs-1 d-block mb-3 opacity-50"></i>
                        <p class="mb-0">No items loaded yet</p>
                        <small>Select a Local Purchase Order to load items</small>
                    </div>
                </template>

                <template x-if="items.length > 0">
                    <div>
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr class="bg-light">
                                    <th class="ps-4 text-muted fw-semibold" style="width: 50px;">#</th>
                                    <th class="text-muted fw-semibold">Product</th>
                                    <th class="text-center text-muted fw-semibold" style="width: 130px;">Ordered Qty</th>
                                    <th class="text-center text-muted fw-semibold" style="width: 150px;">Received Qty</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(row, index) in items" :key="index">
                                    <tr>
                                        <td class="ps-4 text-muted" x-text="index + 1"></td>
                                        <td class="fw-medium" x-text="row.product_name"></td>
                                        <td class="text-center text-muted" x-text="row.ordered_quantity"></td>
                                        <td class="text-center">
                                            <input type="number"
                                                class="form-control form-control-sm text-center mx-auto border-0 bg-light rounded-pill"
                                                style="width: 100px;" min="0" step="0.01" x-model.number="row.quantity" @input="sync()">
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </template>
            </div>
        </div>

        <div class="shadow-sm card">
            <div class="card-body d-flex justify-content-between">
                <a href="{{ route('grn::index') }}" class="btn btn-light">
                    <i class="fa fa-arrow-left me-1"></i> Back
                </a>

                <button class="px-4 btn btn-success">
                    <i class="fa fa-check me-1"></i> Save
                </button>
            </div>
        </div>

    </form>

    @push('scripts')
        <script>
            $(document).ready(function() {
                var lpoOptions = @js($this->approvedLpos);
                var tomSelect = new TomSelect('#lpo_id', {
                    create: false,
                    sortField: 'text',
                    placeholder: 'Select LPO',
                    options: lpoOptions.map(function(lpo) {
                        return { value: lpo.id, text: lpo.label };
                    }),
                    onChange: function(value) {
                        @this.set('local_purchase_order_id', value || null);
                        if (value) {
                            @this.getLpoItems().then(function(data) {
                                window.dispatchEvent(new CustomEvent('lpo-items-loaded', { detail: data }));
                            });
                        } else {
                            window.dispatchEvent(new CustomEvent('lpo-items-loaded', { detail: [] }));
                        }
                    }
                });

                @if($this->local_purchase_order_id)
                    tomSelect.setValue('{{ $this->local_purchase_order_id }}');
                @endif
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

                    sync() {
                        @this.set('items', this.items);
                    },
                }
            }
        </script>
    @endpush
</div>
