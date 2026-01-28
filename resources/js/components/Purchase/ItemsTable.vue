<template>
    <div class="card shadow-sm border-0">
        <div class="card-header bg-gradient bg-primary text-white py-2">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="card-title mb-0 fw-bold text-white">
                    <i class="fa fa-cubes me-2"></i>ITEM INFO
                </h5>
                <div class="flex-grow-1" style="max-width: 800px;">
                    <select ref="productSelect" id="product-select" class="select-product_id-list" type="product"
                        style="width: 100%" placeholder="Search & Select Product">
                        <option value="">Search & Select Product</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>#</th>
                            <th style="width: 30%">Product</th>
                            <th>Barcode</th>
                            <th>Unit</th>
                            <th class="text-end">Unit Price</th>
                            <th class="text-end">Quantity</th>
                            <th class="text-end">Discount</th>
                            <th class="text-end">Tax %</th>
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
                            <td>
                                <div class="input-group input-group-sm">
                                    <select class="form-select form-select-sm border-0 bg-light" :value="item.unit_id"
                                        @change="handleUnitChange(item.key, $event.target.value)">
                                        <option v-for="unit in item.units || []" :key="unit.id" :value="unit.id">
                                            {{ unit.name }}
                                        </option>
                                    </select>
                                </div>
                            </td>
                            <td>
                                <div class="input-group input-group-sm">
                                    <input type="number" class="form-control form-control-sm text-end border-0 bg-light"
                                        :value="item.unit_price"
                                        @input="handleItemInput(item.key, 'unit_price', $event.target.value)"
                                        step="any" />
                                </div>
                            </td>
                            <td>
                                <div class="input-group input-group-sm">
                                    <input type="number" class="form-control form-control-sm text-end border-0 bg-light"
                                        :value="item.quantity" min="1"
                                        @input="handleItemInput(item.key, 'quantity', $event.target.value)"
                                        step="any" />
                                </div>
                            </td>
                            <td>
                                <div class="input-group input-group-sm">
                                    <input type="number" class="form-control form-control-sm text-end border-0 bg-light"
                                        :value="item.discount"
                                        @input="handleItemInput(item.key, 'discount', $event.target.value)"
                                        step="any" />
                                </div>
                            </td>
                            <td>
                                <div class="input-group input-group-sm">
                                    <input type="number" class="form-control form-control-sm text-end border-0 bg-light"
                                        :value="item.tax" max="50"
                                        @input="handleItemInput(item.key, 'tax', $event.target.value)" step="any" />
                                    <span class="input-group-text bg-light border-0">%</span>
                                </div>
                            </td>
                            <td class="text-end fw-bold">{{ formatCurrency(item.total) }}</td>
                            <td class="text-center">
                                <button type="button" @click="handleRemoveItem(item.key)"
                                    class="btn btn-sm btn-icon btn-outline-danger rounded-circle" title="Remove Item">
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
                                <b>{{ formatCurrency(totalQuantity) }}</b>
                            </th>
                            <th class="text-end py-2">
                                <b>{{ formatCurrency(totalDiscount) }}</b>
                            </th>
                            <th class="text-end py-2">
                                <b>{{ formatCurrency(totalTaxAmount) }}%</b>
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
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import { formatCurrency } from '@/utils/currency'
import { useLivewire } from '@/composables/useLivewire'
import { getRoute } from '@/utils/routes'

const props = defineProps({
    items: {
        type: Array,
        default: () => []
    }
})

const emit = defineEmits(['item-removed', 'item-updated'])

const productSelect = ref(null)
const { set, call, on } = useLivewire()
let tomSelectInstance = null

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

const handleItemInput = (key, field, value) => {
    const numValue = parseFloat(value) || 0
    set(`items.${key}.${field}`, numValue)
    emit('item-updated', { key, field, value: numValue })
}

const handleUnitChange = (key, unitId) => {
    set(`items.${key}.unit_id`, unitId)
    emit('item-updated', { key, field: 'unit_id', value: unitId })
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
