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
                <h5 class="mb-0 fw-bold">Order Details</h5>
            </div>

            <div class="card-body">
                <div class="row g-3">

                    <div class="col-md-6" x-data="vendorSelect(@entangle('vendor_id'))" x-init="init()" wire:ignore>
                        <label class="form-label">Vendor</label>
                        <select x-ref="vendor" class="form-control"></select>
                    </div>

                    <div class="col-md-6">

                        <label class="form-label">Purchase Requests</label>
                        <button type="button" class="btn btn-outline-primary w-100 d-flex justify-content-between align-items-center"
                            @click="$dispatch('open-pr-modal')">
                            Select Products from Purchase Requests
                        </button>
                    </div>

                </div>
            </div>
        </div>

        <div class="mb-4 shadow-sm card" x-data="purchaseOrderProducts(@entangle('items'))" x-init="init()" @merge-products.window="mergeProducts($event.detail)">

            <div class="bg-white card-header d-flex justify-content-between">
                <h5 class="mb-0 fw-bold">Products</h5>

                <button type="button" class="btn btn-sm btn-primary" @click="addRow()">
                    + Add Product
                </button>
            </div>

            <div class="card-body">

                <table class="table align-middle table-bordered">
                    <thead>
                        <tr>
                            <th width="50%">Product</th>
                            <th width="20%">Qty</th>
                            <th width="20%">Rate</th>
                            <th width="10%"></th>
                        </tr>
                    </thead>

                    <tbody>
                        <template x-for="(row, index) in items" :key="row.uid ?? row.product_id ?? index">

                            <tr>
                                <td x-data="productSelect(row, index)" wire:ignore>
                                    <select x-ref="select"></select>
                                </td>

                                <td>
                                    <input type="number" class="form-control" min="1" x-model="row.quantity">
                                </td>

                                <td>
                                    <input type="number" class="form-control" x-model="row.rate" step="0.01">
                                </td>

                                <td class="text-center">
                                    <button type="button" class="btn btn-danger btn-sm" @click="removeRow(index)">
                                        ✕
                                    </button>
                                </td>

                            </tr>

                        </template>
                    </tbody>
                </table>

                <div class="text-end">
                    <strong>Total:</strong>
                    <span x-text="totalAmount()"></span>
                </div>

            </div>
        </div>

        <div class="shadow-sm card">
            <div class="card-body d-flex justify-content-between">
                <a href="#" class="btn btn-light">Back</a>

                <button class="px-4 btn btn-success">
                    Save
                </button>
            </div>
        </div>

    </form>

    <div class="modal fade" id="PurchaseRequestModal" tabindex="-1" style="z-index: 1060;" wire:ignore.self>
        <div class="modal-dialog modal-xl">
            <div class="modal-content" x-data="purchaseRequestModal()">

                <div class="modal-header">
                    <h5 class="modal-title">Select Products from Purchase Requests</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <div class="row">

                        <div class="col-md-4 border-end">

                            <h6 class="mb-3 fw-bold">Purchase Requests</h6>

                            <div class="list-group">

                                <template x-for="pr in prs" :key="pr.id">
                                    <button type="button" class="list-group-item list-group-item-action"
                                        :class="{ 'active': selectedPr && selectedPr.id === pr.id }" @click="selectPr(pr)">

                                        <div class="fw-semibold">PR #<span x-text="pr.id"></span></div>

                                        <small class="text-muted" x-text="pr.products.length + ' products'"></small>
                                    </button>
                                </template>

                            </div>

                        </div>

                        <div class="col-md-8">

                            <template x-if="!selectedPr">
                                <div class="py-5 text-center text-muted">
                                    Select a Purchase Request to view products
                                </div>
                            </template>

                            <template x-if="selectedPr">
                                <div>

                                    <h6 class="mb-3 fw-bold">
                                        Products in PR #<span x-text="selectedPr.id"></span>
                                    </h6>

                                    <table class="table table-sm table-bordered">
                                        <thead>
                                            <tr>
                                                <th width="5%"></th>
                                                <th>Product</th>
                                                <th width="20%">Qty</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <template x-for="product in selectedPr.products" :key="product.product_id">
                                                <tr>
                                                    <td>
                                                        <input type="checkbox" :checked="isSelected(selectedPr.id, product.product_id)"
                                                            @change="toggleProduct(selectedPr.id, product)">
                                                    </td>

                                                    <td x-text="product.name"></td>
                                                    <td x-text="product.quantity"></td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>

                                </div>
                            </template>

                        </div>

                    </div>

                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button class="btn btn-primary" @click="addSelected()">
                        Add Selected Products
                    </button>
                </div>

            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function purchaseRequestModal() {
                return {
                    prs: [],
                    selectedPr: null,
                    selected: {},

                    init() {
                        window.addEventListener('open-pr-modal', () => {
                            this.loadPRs();
                            const modal = new bootstrap.Modal(document.getElementById('PurchaseRequestModal'));
                            modal.show();
                        });
                    },

                    loadPRs() {
                        this.$wire.getApprovedPurchaseRequestsWithProducts()
                            .then(data => {
                                this.prs = data;
                            });
                    },

                    selectPr(pr) {
                        this.selectedPr = pr;
                    },

                    toggleProduct(prId, product) {
                        const key = prId + '-' + product.product_id;

                        if (this.selected[key]) {
                            delete this.selected[key];
                        } else {
                            this.selected[key] = {
                                product_id: product.product_id,
                                quantity: product.quantity,
                                rate: 0,
                                purchase_request_id: prId
                            };
                        }
                    },

                    isSelected(prId, productId) {
                        return !!this.selected[prId + '-' + productId];
                    },

                    addSelected() {
                        const products = Object.values(this.selected);

                        this.$dispatch('merge-products', products);

                        this.selected = {};
                        this.selectedPr = null;

                        const modalEl = document.getElementById('PurchaseRequestModal');
                        bootstrap.Modal.getInstance(modalEl).hide();
                    }
                }
            }

            function purchaseRequestSelector(selectedRequestsEntangled) {
                return {
                    tom: null,
                    selectedRequestsEntangled,
                    selected: selectedRequestsEntangled || [],

                    init() {
                        if (this.tom) return;

                        this.tom = new TomSelect(this.$refs.select, {
                            valueField: 'id',
                            labelField: 'label',
                            searchField: 'label',
                            options: @js($approvedPurchaseRequests),
                            items: this.selected,

                            onChange: (values) => {
                                this.selected = values;
                                this.selectedRequestsEntangled = values;
                                this.fetchProducts(values);
                            }
                        });
                    },

                    fetchProducts(ids) {
                        if (!ids || !ids.length) {
                            this.$dispatch('merge-products', []);
                            return;
                        }

                        this.$wire.getProductsFromRequests(ids)
                            .then(products => {
                                this.$dispatch('merge-products', products);
                            });
                    }
                }
            }

            function purchaseOrderProducts(itemsEntangled) {
                return {
                    itemsEntangled,
                    items: Array.isArray(itemsEntangled) ? itemsEntangled : [],

                    init() {
                        this.$watch('items', (value) => {
                            this.itemsEntangled = value;
                        }, {
                            deep: true
                        });
                    },

                    addRow() {
                        this.items.push({
                            uid: Date.now() + Math.random(),
                            product_id: null,
                            quantity: 1,
                            rate: 0,
                            is_pr: false
                        });
                    },

                    removeRow(index) {
                        this.items.splice(index, 1);
                    },

                    mergeProducts(products) {
                        if (!products) return;

                        let existing = [...this.items];

                        products.forEach(p => {
                            const existingIndex = existing.findIndex(i =>
                                i.product_id == p.product_id
                            );

                            if (existingIndex !== -1) {
                                existing[existingIndex].quantity += Number(p.quantity);
                            } else {
                                existing.push({
                                    uid: 'pr-' + p.product_id + '-' + Date.now(),
                                    product_id: Number(p.product_id),
                                    quantity: Number(p.quantity),
                                    rate: Number(p.rate || 0),
                                    is_pr: true
                                });
                            }
                        });

                        this.items = existing;
                    },

                    totalAmount() {
                        return this.items.reduce((sum, i) =>
                            sum + (Number(i.quantity) * Number(i.rate)), 0);
                    }
                }
            }

            function productSelect(row, index) {
                return {
                    tom: null,
                    productOptions: @js($productOptions),

                    init() {
                        if (this.tom) return;

                        this.tom = new TomSelect(this.$refs.select, {
                            options: this.productOptions.map(p => ({
                                value: p.id,
                                text: p.name
                            })),

                            items: row.product_id ? [row.product_id] : [],

                            onChange: (value) => {
                                if (this.items.some((i, iIndex) =>
                                        i.product_id == value && iIndex !== index)) {

                                    alert('Already added');
                                    this.tom.clear();
                                    return;
                                }

                                row.product_id = Number(value);
                            }
                        });

                        this.$watch(() => row.product_id, (val) => {
                            if (!this.tom) return;

                            if (val) {
                                this.tom.setValue(val, true);
                            } else {
                                this.tom.clear();
                            }
                        });
                    }
                }
            }

            function vendorSelect(vendorIdEntangled) {
                return {
                    tom: null,
                    vendorIdEntangled,

                    init() {
                        if (this.tom) return;

                        this.tom = new TomSelect(this.$refs.vendor, {
                            valueField: 'id',
                            labelField: 'name',
                            searchField: 'name',
                            options: @js($vendors),
                            items: this.vendorIdEntangled ? [this.vendorIdEntangled] : [],

                            onChange: (val) => this.vendorIdEntangled = val
                        });
                    }
                }
            }
        </script>
    @endpush
</div>
