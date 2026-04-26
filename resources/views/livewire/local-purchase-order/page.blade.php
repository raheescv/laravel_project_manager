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

                    <div class="col-md-4" wire:ignore>
                        <label class="form-label">Vendor</label>
                        {{ html()->select('vendor_id')->value($this->vendor_id)->class('select-vendor_id')->id('vendor_id')->placeholder('Select Vendor') }}
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Date</label>
                        <input type="date" wire:model="date" class="form-control" value="{{ $this->date }}">
                    </div>

                    <div class="col-md-5">

                        <label class="form-label">Purchase Requests</label>
                        <button type="button" class="btn btn-outline-primary w-100 d-flex justify-content-between align-items-center"
                            @click="$dispatch('open-pr-modal')">
                            Select Products from Purchase Requests
                        </button>
                    </div>

                </div>
            </div>
        </div>

        <div class="mb-4 border-0 shadow-sm card" wire:ignore x-data="lpoProducts(@js($items), @js($this->productOptions), @js($this->accountOptions))">

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
                            <input type="number" class="form-control" min="1" x-model.number="newQty" placeholder="Qty">
                        </div>
                        <div class="col-auto" style="width: 130px;">
                            <input type="number" class="form-control" min="0" step="0.01" x-model.number="newRate" placeholder="Rate">
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
                                    <th class="text-muted fw-semibold" style="width: 220px;">Expense Account</th>
                                    <th class="text-center text-muted fw-semibold" style="width: 130px;">Qty</th>
                                    <th class="text-center text-muted fw-semibold" style="width: 130px;">Rate</th>
                                    <th class="text-center text-muted fw-semibold" style="width: 130px;">Amount</th>
                                    <th class="text-center text-muted fw-semibold pe-4" style="width: 80px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(row, index) in items" :key="row.product_id">
                                    <tr>
                                        <td class="ps-4 text-muted" x-text="index + 1"></td>
                                        <td class="fw-medium" x-text="getProductName(row.product_id)"></td>
                                        <td x-init="initAccountSelect($el.querySelector('select'), row)">
                                            <select></select>
                                        </td>
                                        <td class="text-center">
                                            <input type="number"
                                                class="form-control-sm text-center mx-auto border-0 bg-light"
                                                style="width: 75px;" min="1" x-model.number="row.quantity" @input="sync()">
                                        </td>
                                        <td class="text-center">
                                            <input type="number"
                                                class="form-control-sm text-center mx-auto border-0 bg-light"
                                                style="width: 90px;" min="0" step="0.01" x-model.number="row.rate" @input="sync()">
                                        </td>
                                        <td class="text-center fw-medium" x-text="(Number(row.quantity) * Number(row.rate)).toFixed(2)"></td>
                                        <td class="text-center pe-4">
                                            <button type="button" class="btn btn-sm btn-light text-danger border-0" @click="removeItem(index)"
                                                title="Remove">
                                                <i class="fa fa-times"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                        <div class="bg-light px-4 py-3 d-flex justify-content-end align-items-center border-top">
                            <span class="text-muted me-3">Total</span>
                            <span class="badge bg-success fs-6 rounded-pill px-3" x-text="totalAmount().toFixed(2)"></span>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div class="shadow-sm card">
            <div class="card-body d-flex justify-content-between">
                <a href="{{ route('lpo::index') }}" class="btn btn-light">
                    <i class="fa fa-arrow-left me-1"></i> Back
                </a>

                <button class="px-4 btn btn-success">
                    <i class="fa fa-check me-1"></i> Save
                </button>
            </div>
        </div>

    </form>

    <x-account.vendor-modal />

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
                                                <th class="text-end" width="20%">Qty</th>
                                                <th class="text-end" width="20%">Rate</th>
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
                                                    <td class="text-end" x-text="product.quantity"></td>
                                                    <td class="text-end" x-text="product.rate"></td>
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
        @include('components.select.vendorSelect')
        @include('components.select.productSelect')
        @include('components.select.accountSelect')

        <script>
            $(document).ready(function() {
                $('#vendor_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('vendor_id', value);
                });

                window.addEventListener('AddToVendorSelectBox', event => {
                    var data = event.detail[0];
                    var tomSelectInstance = document.querySelector('#vendor_id').tomselect;
                    if (data && data['name']) {
                        tomSelectInstance.addOption({
                            id: data['id'],
                            name: data['name']
                        });
                        tomSelectInstance.setValue(data['id']);
                        @this.set('vendor_id', data['id']);
                    }
                });
            });

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
                                rate: product.rate || 0,
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

                        // const modalEl = document.getElementById('PurchaseRequestModal');
                        // bootstrap.Modal.getInstance(modalEl).hide();
                    }
                }
            }

            function lpoProducts(initialItems, productOptions, accountOptions) {
                return {
                    productOptions: productOptions,
                    accountOptions: accountOptions,
                    items: Array.isArray(initialItems) && initialItems.length ? [...initialItems] : [],
                    newProductId: null,
                    newQty: 1,
                    newRate: 0,
                    newAccountId: null,

                    initAccountSelect(el, row) {
                        const self = this;
                        const ts = initAccountSelectList(el, v => { row.account_id = v || null; self.sync(); });
                        if (row.account_id) {
                            const acc = this.accountOptions.find(a => String(a.id) === String(row.account_id));
                            if (acc) { ts.addOption(acc); ts.setValue(row.account_id, true); }
                        }
                    },

                    init() {
                        this.$nextTick(() => {
                            $('#product_id').on('change', (event) => {
                                const value = event.target.value || null;
                                this.newProductId = value;
                                if (value) {
                                    const product = this.productOptions.find(p => String(p.id) === String(value));
                                    if (product) {
                                        if (product.cost != null) this.newRate = Number(product.cost);
                                        this.newAccountId = product.expense_account_id ?? null;
                                    }
                                } else {
                                    this.newAccountId = null;
                                }
                            });
                        });

                        window.addEventListener('merge-products', (e) => {
                            this.mergeProducts(e.detail);
                        });
                    },

                    getAvailableOptions() {
                        const usedIds = this.items.map(i => String(i.product_id));
                        return this.productOptions
                            .filter(p => !usedIds.includes(String(p.id)))
                            .map(p => ({
                                value: p.id,
                                text: p.name
                            }));
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
                            quantity: this.newQty || 1,
                            rate: this.newRate || 0,
                            account_id: this.newAccountId,
                        });
                        this.newProductId = null;
                        this.newQty = 1;
                        this.newRate = 0;
                        this.newAccountId = null;
                        this.refreshSelectOptions();
                        this.sync();
                    },

                    removeItem(index) {
                        this.items.splice(index, 1);
                        this.refreshSelectOptions();
                        this.sync();
                    },

                    mergeProducts(products) {
                        if (!products) return;

                        let existing = [...this.items];

                        products.forEach(p => {
                            const existingIndex = existing.findIndex(i =>
                                String(i.product_id) == String(p.product_id)
                            );

                            if (existingIndex !== -1) {
                                existing[existingIndex].quantity += Number(p.quantity);
                            } else {
                                const prod = this.productOptions.find(o => String(o.id) === String(p.product_id));
                                existing.push({
                                    product_id: Number(p.product_id),
                                    quantity: Number(p.quantity),
                                    rate: Number(p.rate || 0),
                                    account_id: prod?.expense_account_id ?? null,
                                });
                            }
                        });

                        this.items = existing;
                        this.refreshSelectOptions();
                        this.sync();
                    },

                    getProductName(productId) {
                        const product = this.productOptions.find(p => String(p.id) === String(productId));
                        return product ? product.name : '';
                    },

                    totalAmount() {
                        return this.items.reduce((sum, i) =>
                            sum + (Number(i.quantity) * Number(i.rate)), 0);
                    },

                    sync() {
                        @this.set('items', this.items);
                    },
                }
            }
        </script>
    @endpush
</div>
