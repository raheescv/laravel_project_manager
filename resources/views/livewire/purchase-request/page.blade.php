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

        <div class="mb-4 border-0 shadow-sm card" wire:ignore x-data="purchaseProducts(@js($products), @js($this->productOptions))">

            <div class="card-header bg-white border-bottom px-4 py-3">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="d-flex align-items-center">
                        <i class="fa fa-cubes text-success me-2"></i>
                        <h5 class="mb-0 fw-bold">Products</h5>
                    </div>
                    <span class="badge bg-primary rounded-pill" x-show="items.length > 0"
                        x-text="items.length + ' item' + (items.length > 1 ? 's' : '')"></span>
                </div>

                <div class="bg-light rounded-3 p-3">
                    <div class="row g-2 align-items-center">
                        <div class="col">
                            {{ html()->select('product_id', [])->value('')->class('select-product_id-list shadow-none')->id('product_id')->placeholder('Select Product') }}
                        </div>
                        <div class="col-auto" style="width: 110px;">
                            <input type="number" class="form-control" min="1" x-model.number="newQty"
                                placeholder="Qty">
                        </div>
                        <div class="col-auto">
                            <button type="button" class="btn btn-primary px-3" @click="addItem()">
                                <i class="fa fa-plus me-1"></i> Add
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body p-0">
                <template x-if="items.length === 0">
                    <div class="py-5 text-center text-muted">
                        <i class="fa fa-box-open fs-1 d-block mb-3 opacity-50"></i>
                        <p class="mb-0">No products added yet</p>
                        <small>Use the form above to add products</small>
                    </div>
                </template>

                <template x-if="items.length > 0">
                    <div>
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr class="bg-light">
                                    <th class="ps-4 text-muted fw-semibold" style="width: 50px;">#</th>
                                    <th class="text-muted fw-semibold">Product</th>
                                    <th class="text-center text-muted fw-semibold" style="width: 130px;">Qty</th>
                                    <th class="text-center text-muted fw-semibold pe-4" style="width: 80px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(row, index) in items" :key="row.product_id">
                                    <tr>
                                        <td class="ps-4 text-muted" x-text="index + 1"></td>
                                        <td class="fw-medium" x-text="getProductName(row.product_id)"></td>
                                        <td class="text-center">
                                            <input type="number"
                                                class="form-control form-control-sm text-center mx-auto border-0 bg-light rounded-pill"
                                                style="width: 75px;" min="1" x-model.number="row.quantity"
                                                @input="sync()">
                                        </td>
                                        <td class="text-center pe-4">
                                            <button type="button"
                                                class="btn btn-sm btn-light text-danger border-0"
                                                @click="removeItem(index)" title="Remove">
                                                <i class="fa fa-times"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                        <div class="bg-light px-4 py-3 d-flex justify-content-end align-items-center border-top">
                            <span class="text-muted me-3">Total Quantity</span>
                            <span class="badge bg-success fs-6 rounded-pill px-3" x-text="totalQty()"></span>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div class="mb-4 border-0 shadow-sm card">
            <div class="px-4 py-3 card-body d-flex justify-content-between">
                <a href="{{ route('purchase-request::index') }}" class="btn btn-light">
                    <i class="fa fa-arrow-left me-1"></i> Back
                </a>

                <button type="submit" class="px-4 btn btn-success">
                    <i class="fa fa-check me-1"></i> Save
                </button>
            </div>
        </div>

    </form>

    @push('scripts')
        @include('components.select.productSelect')

        <script>
            function purchaseProducts(initialProducts, productOptions) {
                return {
                    productOptions: productOptions,
                    items: initialProducts.length ? [...initialProducts] : [],
                    newProductId: null,
                    newQty: 1,

                    init() {
                        this.$nextTick(() => {
                            $('#product_id').on('change', (event) => {
                                this.newProductId = event.target.value || null;
                            });
                        });
                    },

                    getAvailableOptions() {
                        const usedIds = this.items.map(i => String(i.product_id));
                        return this.productOptions
                            .filter(p => !usedIds.includes(String(p.id)))
                            .map(p => ({ value: p.id, text: p.name }));
                    },

                    refreshSelectOptions() {
                        const productSelect = document.querySelector('#product_id');
                        if (!productSelect?.tomselect) {
                            return;
                        }

                        productSelect.tomselect.clear();
                        this.newProductId = null;
                    },

                    addItem() {
                        if (!this.newProductId) {
                            alert('Please select a product');
                            return;
                        }
                        if (this.items.some(i => String(i.product_id) === String(this.newProductId))) {
                            alert('Product already added');
                            return;
                        }
                        this.items.push({
                            product_id: this.newProductId,
                            quantity: this.newQty || 1
                        });
                        this.newProductId = null;
                        this.newQty = 1;
                        this.refreshSelectOptions();
                        this.sync();
                    },

                    removeItem(index) {
                        this.items.splice(index, 1);
                        this.refreshSelectOptions();
                        this.sync();
                    },

                    getProductName(productId) {
                        const product = this.productOptions.find(p => String(p.id) === String(productId));
                        return product ? product.name : '';
                    },

                    totalQty() {
                        return this.items.reduce((sum, i) => sum + Number(i.quantity || 0), 0);
                    },

                    sync() {
                        @this.set('products', this.items);
                    },
                }
            }
        </script>
    @endpush
</div>
