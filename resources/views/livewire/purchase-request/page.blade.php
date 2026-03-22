<div>
    <form wire:submit="save">

        @if ($errors->any())
            <div class="mb-4 alert alert-danger d-flex align-items-center">
                <i class="fa fa-exclamation-circle fs-5 me-2"></i>
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mb-4 border-0 shadow-sm card">
            <div class="py-3 bg-white card-header border-bottom">
                <div class="d-flex align-items-center">
                    <i class="fa fa-file-text text-primary me-2"></i>
                    <h5 class="mb-0 fw-bold">Purchase Request Details</h5>
                </div>
            </div>

            <div class="py-4 card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Requested By</label>
                        <input type="text" class="form-control" value="{{ auth()->user()->name }}" readonly>
                    </div>
                </div>
            </div>
        </div>

        <div class="mb-4 border-0 shadow-sm card" x-data="purchaseProducts(@entangle('products'))" x-ref="productCard">

            <div class="py-3 bg-white card-header border-bottom d-flex justify-content-between">
                <div class="d-flex align-items-center">
                    <i class="fa fa-cubes text-success me-2"></i>
                    <h5 class="mb-0 fw-bold">Products</h5>
                </div>

                <button type="button" class="btn btn-sm btn-primary" @click="addRow()">
                    + Add Product
                </button>
            </div>

            <div class="py-5 card-body">

                <div class="table-responsive">
                    <table class="table align-middle table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 60%">Product</th>
                                <th style="width: 20%">Quantity</th>
                                <th style="width: 10%">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            <template x-for="(row, index) in items" :key="index">
                                <tr x-bind:id="'row_' + index">
                                    <td x-data="{
                                        init() {
                                            this.tom = new TomSelect(this.$refs.select, {
                                                create: false,
                                                sortField: 'text',
                                                documentParent: 'body',
                                                hideSelected: true,
                                                items: row.product_id ? [row.product_id] : [],
                                                onChange: (value) => {
                                                    if (this.items.some((i, iIndex) => i.product_id == value && iIndex !== index)) {
                                                        alert('Product already selected');
                                                        this.tom.clear();
                                                        return;
                                                    }
                                                    row.product_id = value;
                                                },
                                                options: this.getProductOptions(),
                                            });
                                        }
                                    }">
                                        <select x-model="row.product_id" x-bind:id="'product_' + index" x-ref="select"
                                            @change="sync()" class="form-control form-select">
                                            <option value="">Select Product</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control" min="1" x-model="row.quantity"
                                            @input="sync()">
                                    </td>

                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-danger"
                                            @click="if (confirm('Are you sure you want to remove this product?')) removeRow(index)"
                                            x-show="items.length > 1">
                                            ✕
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div class="mt-3 text-end">
                    <strong>Total Qty:</strong>
                    <span x-text="totalQty()"></span>
                </div>
            </div>
        </div>

        <div class="mb-4 border-0 shadow-sm card">
            <div class="py-3 card-body d-flex justify-content-between">
                <a href="{{ route('purchase-request::index') }}" class="btn btn-light">
                    <i class="fa fa-arrow-left"></i> Back
                </a>

                <button type="submit" class="px-4 btn btn-success">
                    <i class="fa fa-check"></i> Save
                </button>
            </div>
        </div>

    </form>

    @push('scripts')
        <script>
            function purchaseProducts(entangled) {
                return {
                    productOptions: @js($this->productOptions),

                    entangled: entangled,

                    items: entangled.length ? entangled : [{
                        product_id: null,
                        quantity: 1
                    }],

                    addRow() {
                        this.items.push({
                            product_id: null,
                            quantity: 1
                        });
                        this.sync();
                    },

                    removeRow(index) {
                        this.items.splice(index, 1);
                        this.sync();
                    },

                    totalQty() {
                        return this.items.reduce((sum, i) => sum + Number(i.quantity || 0), 0);
                    },

                    sync() {
                        this.entangled = this.items;
                    },

                    getProductOptions() {
                        return this.productOptions.map(p => ({
                            value: p.id,
                            text: p.name
                        }));
                    },

                    init() {
                        this.items = this.entangled;
                    },

                }
            }
        </script>
    @endpush
</div>
