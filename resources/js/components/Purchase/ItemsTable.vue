<template>
    <div class="card shadow-sm border-0">
        <div class="card-header bg-gradient bg-primary text-white py-2">
            <div
                class="d-flex flex-column flex-md-row justify-content-between align-items-stretch align-items-md-center gap-2">
                <h5 class="card-title mb-0 fw-bold text-white">
                    <i class="fa fa-cubes me-2"></i>ITEM INFO
                </h5>
                <div class="product-select-wrapper">
                    <select ref="productSelect" id="product-select" class="select-product_id-list" type="product"
                        style="width: 100%" placeholder="Search & Select Product">
                        <option value="">Search & Select Product</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <!-- Desktop: table layout (xl breakpoint = 1200px+) -->
            <div class="items-table-wrapper d-none d-xl-block">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>#</th>
                                <th style="width: 30%">Product</th>
                                <th>Barcode</th>
                                <th class="unit-cell">Unit</th>
                                <th class="text-end text-nowrap">Unit Price</th>
                                <th class="text-end">Quantity</th>
                                <th class="text-end">Discount</th>
                                <th class="text-end text-nowrap">Tax %</th>
                                <th class="text-end">Total</th>
                                <th class="text-center" style="width: 80px">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, index) in items" :key="item.key || index">
                                <td class="fw-medium">{{ index + 1 }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0">{{ item.name }}</h6>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0">{{ item.barcode || '' }}</h6>
                                        </div>
                                    </div>
                                </td>
                                <td class="unit-cell">
                                    <select class="form-select form-select-sm border-0 bg-light" :value="item.unit_id"
                                        @change="handleUnitChange(item.key, $event.target.value)">
                                        <option v-for="unit in item.units || []" :key="unit.id" :value="unit.id">
                                            {{ unit.name }}
                                        </option>
                                    </select>
                                </td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <input type="number" class="form-control-sm text-end border-0 bg-light"
                                            :value="val(item.key, 'unit_price', item.unit_price)"
                                            @input="handleItemInput(item.key, 'unit_price', $event.target.value)"
                                            @blur="handleItemBlur(item.key, 'unit_price')" step="any" min="0" />
                                    </div>
                                </td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <input type="number" class="form-control-sm text-end border-0 bg-light"
                                            :value="val(item.key, 'quantity', item.quantity)"
                                            @input="handleItemInput(item.key, 'quantity', $event.target.value)"
                                            @blur="handleItemBlur(item.key, 'quantity')" step="any" min="0" />
                                    </div>
                                </td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <input type="number" class="form-control-sm text-end border-0 bg-light"
                                            :value="val(item.key, 'discount', item.discount)"
                                            @input="handleItemInput(item.key, 'discount', $event.target.value)"
                                            @blur="handleItemBlur(item.key, 'discount')" step="any" min="0" />
                                    </div>
                                </td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <input type="number" class="form-control-sm text-end border-0 bg-light"
                                            :value="val(item.key, 'tax', item.tax)"
                                            @input="handleItemInput(item.key, 'tax', $event.target.value)"
                                            @blur="handleItemBlur(item.key, 'tax')" step="any" min="0" max="50" />
                                    </div>
                                </td>
                                <td class="text-end fw-bold">{{ formatCurrency(item.total) }}</td>
                                <td class="text-center">
                                    <button type="button" @click="handleRemoveItem(item.key)"
                                        class="btn btn-sm btn-icon btn-outline-danger rounded-circle"
                                        title="Remove Item">
                                        <i class="demo-pli-recycling"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="items.length === 0">
                                <td colspan="10" class="text-center py-4 text-muted">
                                    <i class="fa fa-cubes fs-1 mb-2 d-block"></i>
                                    No items added yet
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="table-group-divider">
                            <tr class="bg-light">
                                <th colspan="5" class="text-end py-2">Total</th>
                                <th class="text-end py-2">
                                    <b>{{ parseFloat(totalQuantity).toFixed(3) }}</b>
                                </th>
                                <th class="text-end py-2">
                                    <b>{{ formatCurrency(totalDiscount) }}</b>
                                </th>
                                <th class="text-end py-2">
                                    <b>{{ formatCurrency(totalTaxAmount) }}</b>
                                </th>
                                <th class="text-end py-2">
                                    <b>{{ formatCurrency(totalAmount) }}</b>
                                </th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Mobile / smaller desktop: card layout -->
            <div class="items-cards-wrapper d-xl-none">
                <div class="items-cards-list">
                    <div v-for="(item, index) in items" :key="item.key || index" class="item-card">
                        <div class="item-card-header">
                            <span class="item-card-index">{{ index + 1 }}.</span>
                            <h6 class="item-card-title mb-0">{{ item.name }}</h6>
                            <button type="button" @click="handleRemoveItem(item.key)"
                                class="btn btn-sm btn-icon btn-outline-danger rounded-circle ms-auto"
                                title="Remove Item">
                                <i class="demo-pli-recycling"></i>
                            </button>
                        </div>
                        <div class="item-card-body">
                            <div v-if="item.barcode" class="item-card-row">
                                <span class="label">Barcode</span>
                                <span class="value">{{ item.barcode }}</span>
                            </div>
                            <div class="item-card-row">
                                <span class="label">Unit</span>
                                <select class="form-select form-select-sm" :value="item.unit_id"
                                    @change="handleUnitChange(item.key, $event.target.value)">
                                    <option v-for="unit in item.units || []" :key="unit.id" :value="unit.id">
                                        {{ unit.name }}
                                    </option>
                                </select>
                            </div>
                            <div class="item-card-row item-card-row-grid">
                                <div class="field-group">
                                    <label class="field-label">Unit Price</label>
                                    <input type="number" class="form-control-sm text-end"
                                        :value="val(item.key, 'unit_price', item.unit_price)"
                                        @input="handleItemInput(item.key, 'unit_price', $event.target.value)"
                                        @blur="handleItemBlur(item.key, 'unit_price')" step="any" min="0" />
                                </div>
                                <div class="field-group">
                                    <label class="field-label">Quantity</label>
                                    <input type="number" class="form-control-sm text-end"
                                        :value="val(item.key, 'quantity', item.quantity)"
                                        @input="handleItemInput(item.key, 'quantity', $event.target.value)"
                                        @blur="handleItemBlur(item.key, 'quantity')" step="any" min="0" />
                                </div>
                            </div>
                            <div class="item-card-row item-card-row-grid">
                                <div class="field-group">
                                    <label class="field-label">Discount</label>
                                    <input type="number" class="form-control-sm text-end"
                                        :value="val(item.key, 'discount', item.discount)"
                                        @input="handleItemInput(item.key, 'discount', $event.target.value)"
                                        @blur="handleItemBlur(item.key, 'discount')" step="any" min="0" />
                                </div>
                                <div class="field-group">
                                    <label class="field-label">Tax %</label>
                                    <input type="number" class="form-control-sm text-end"
                                        :value="val(item.key, 'tax', item.tax)"
                                        @input="handleItemInput(item.key, 'tax', $event.target.value)"
                                        @blur="handleItemBlur(item.key, 'tax')" step="any" min="0" max="50" />
                                </div>
                            </div>
                            <div class="item-card-row item-card-total">
                                <span class="label">Total</span>
                                <span class="value fw-bold">{{ formatCurrency(item.total) }}</span>
                            </div>
                        </div>
                    </div>
                    <div v-if="items.length === 0" class="item-card-empty">
                        <i class="fa fa-cubes fs-1 mb-2 d-block text-muted"></i>
                        <span class="text-muted">No items added yet</span>
                    </div>
                </div>
                <div v-if="items.length > 0" class="items-cards-summary">
                    <div class="summary-row">
                        <span>Total Qty</span>
                        <strong>{{ parseFloat(totalQuantity).toFixed(3) }}</strong>
                    </div>
                    <div class="summary-row">
                        <span>Total Discount</span>
                        <strong>{{ formatCurrency(totalDiscount) }}</strong>
                    </div>
                    <div class="summary-row">
                        <span>Total Tax</span>
                        <strong>{{ formatCurrency(totalTaxAmount) }}</strong>
                    </div>
                    <div class="summary-row summary-total">
                        <span>Grand Total</span>
                        <strong>{{ formatCurrency(totalAmount) }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import {
    ref,
    computed,
    onMounted,
    watch,
    nextTick
} from 'vue'
import {
    formatCurrency
} from '@/utils/number'
import {
    useLivewire
} from '@/composables/useLivewire'
import {
    getRoute
} from '@/utils/routes'

const props = defineProps({
    items: {
        type: Array,
        default: () => []
    }
})

const emit = defineEmits(['item-removed', 'item-updated'])

const productSelect = ref(null)
const {
    set,
    call,
    on
} = useLivewire()
let tomSelectInstance = null
const editing = ref({})

const totalQuantity = computed(() => {
    return props.items.reduce((sum, item) => sum + (parseFloat(item.quantity) || 0), 0)
})

const totalDiscount = computed(() => {
    return props.items.reduce((sum, item) => sum + (parseFloat(item.discount) || 0), 0)
})

const totalTaxAmount = computed(() => {
    return props.items.reduce((sum, item) => sum + (parseFloat(item.tax_amount) || 0), 0)
})

const totalAmount = computed(() => {
    return props.items.reduce((sum, item) => sum + (parseFloat(item.total) || 0), 0)
})

const val = (key, field, fallback) => editing.value[`${key}-${field}`] ?? fallback

const handleItemInput = (key, field, value) => {
    editing.value[`${key}-${field}`] = value
    const num = parseFloat(value) || 0
    set(`items.${key}.${field}`, num)
    emit('item-updated', {
        key,
        field,
        value: num
    })
}

const handleItemBlur = (key, field) => {
    delete editing.value[`${key}-${field}`]
}

const handleUnitChange = (key, unitId) => {
    set(`items.${key}.unit_id`, unitId)
    emit('item-updated', {
        key,
        field: 'unit_id',
        value: unitId
    })
}

const handleRemoveItem = async (key) => {
    if (confirm('Are you sure?')) {
        try {
            await call('removeItem', key)
            emit('item-removed', key)
        } catch (error) {
            console.error('Error removing item:', error)
        }
    }
}

const initializeProductSelect = () => {
    if (!productSelect.value || typeof window.TomSelect === 'undefined') {
        return
    }

    if (tomSelectInstance) {
        tomSelectInstance.destroy()
    }

    tomSelectInstance = new window.TomSelect(productSelect.value, {
        persist: false,
        valueField: 'id',
        nameField: 'name',
        searchField: ['name', 'barcode', 'code', 'mrp', 'cost', 'size', 'color', 'id'],
        load: function (query, callback) {
            const url = getRoute('product::list') +
                '?query=' + encodeURIComponent(query) +
                '&type=product'

            fetch(url)
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok')
                    return response.json()
                })
                .then(json => callback(json.items))
                .catch(err => {
                    console.error('Error loading product data:', err)
                    callback()
                })
        },
        onFocus: function () {
            this.clearOptions()
            this.load('')
        },
        onChange: function (value) {
            if (value) {
                set('product_id', value)
                tomSelectInstance.clear()
            }
        },
        render: {
            option: function (item, escape) {
                let option = `
                    <div class="dropdown-item">
                        <div class="item-icon">
                            <img src="${escape(item.thumbnail || window.logo || '')}" class="item-image" alt="${escape(item.name)}">
                        </div>
                        <div class="item-content">
                            <div class="item-name">${escape(item.name)}</div>`

                if (item.type == 'product') {
                    option += ` <div class="item-details">
                                    <span><strong>MRP:</strong> ${escape(item.mrp)}</span>
                                    <span><strong>Cost:</strong> ${escape(item.cost)}</span>
                                    ${item.barcode ? `<span><strong>barcode:</strong> ${escape(item.barcode)}</span>` : ''}
                                    ${item.size ? `<span><strong>Size:</strong> ${escape(item.size)}</span>` : ''}
                                    ${item.code ? `<span><strong>Code:</strong> ${escape(item.code)}</span>` : ''}
                                    ${item.color ? `<span><strong>Color:</strong> ${escape(item.color)}</span>` : ''}
                                </div>`
                } else {
                    option += ` <div class="item-details">
                                    <span><strong>Price:</strong> ${escape(item.mrp)}</span>
                                </div>`
                }

                option += `</div></div>`
                return option
            },
            item: function (item, escape) {
                return `<div class="selected-item">${escape(item.name || item.text || '')}</div>`
            }
        }
    })
}

// Listen for OpenProductBox event
on('OpenProductBox', () => {
    if (tomSelectInstance) {
        set('product_id', null)
        tomSelectInstance.clear()
        nextTick(() => {
            tomSelectInstance.open()
        })
    }
})

onMounted(() => {
    if (typeof window.TomSelect !== 'undefined') {
        initializeProductSelect()
    } else {
        setTimeout(() => {
            if (typeof window.TomSelect !== 'undefined') {
                initializeProductSelect()
            }
        }, 100)
    }
})
</script>

<style scoped>
/* Remove padding from number inputs */
.items-table-wrapper .form-control,
.items-cards-wrapper .form-control,
.items-table-wrapper .input-group .form-control,
.items-cards-wrapper input[type="number"] {
    padding: 0;
}

.items-table-wrapper .input-group-text {
    padding: 0;
}

.product-select-wrapper {
    flex: 1;
    min-width: 0;
}

@media (min-width: 768px) {
    .product-select-wrapper {
        max-width: 500px;
    }
}

@media (min-width: 1200px) {
    .product-select-wrapper {
        max-width: 800px;
    }
}

.unit-cell {
    min-width: 110px;
    width: 1%;
}

.unit-cell .form-select {
    width: 100%;
}

/* Mobile card layout */
.items-cards-wrapper {
    padding: 0.75rem;
}

.items-cards-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.item-card {
    background: var(--bs-body-bg, #fff);
    border: 1px solid var(--bs-border-color, #dee2e6);
    border-radius: 0.5rem;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
}

.item-card-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.75rem;
    background: var(--bs-light, #f8f9fa);
    border-bottom: 1px solid var(--bs-border-color, #dee2e6);
}

.item-card-index {
    font-weight: 600;
    color: var(--bs-secondary);
    flex-shrink: 0;
}

.item-card-title {
    font-size: 0.9rem;
    flex: 1;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.item-card-body {
    padding: 0.75rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.item-card-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.item-card-row .label {
    flex-shrink: 0;
    width: 85px;
    font-size: 0.75rem;
    color: var(--bs-secondary);
}

.item-card-row .value {
    flex: 1;
    font-size: 0.875rem;
}

.item-card-row .form-select {
    flex: 1;
    min-width: 0;
}

.item-card-row-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.5rem;
}

.item-card-row-grid .field-group {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.item-card-row-grid .field-label {
    font-size: 0.75rem;
    color: var(--bs-secondary);
}

.item-card-row-grid .form-control {
    width: 100%;
}

.item-card-total {
    margin-top: 0.25rem;
    padding-top: 0.5rem;
    border-top: 1px dashed var(--bs-border-color, #dee2e6);
}

.item-card-empty {
    text-align: center;
    padding: 2rem;
    border: 1px dashed var(--bs-border-color, #dee2e6);
    border-radius: 0.5rem;
    background: var(--bs-light, #f8f9fa);
}

.items-cards-summary {
    margin-top: 1rem;
    padding: 0.75rem;
    background: var(--bs-light, #f8f9fa);
    border-radius: 0.5rem;
    border: 1px solid var(--bs-border-color, #dee2e6);
}

.summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.25rem 0;
    font-size: 0.875rem;
}

.summary-total {
    margin-top: 0.5rem;
    padding-top: 0.5rem;
    border-top: 2px solid var(--bs-border-color, #dee2e6);
    font-size: 1rem;
}
</style>
