<template>
    <div class="pcx-card">
        <div class="pcx-chd">
            <div class="pcx-ci"><i class="fa fa-cubes"></i></div>
            <h2 class="pcx-ct">Item Info</h2>
            <span class="pcx-cnt">{{ items.length }} {{ items.length === 1 ? 'line' : 'lines' }}</span>
        </div>

        <!-- one scan/search field, framed so it reads as the way in -->
        <div class="pcx-search">
            <i class="fa fa-barcode"></i>
            <div class="pcx-search-field">
                <select ref="productSelect" id="product-select" class="select-product_id-list" type="product"
                    placeholder="Scan barcode or search product…">
                    <option value="">Scan barcode or search product…</option>
                </select>
            </div>
        </div>

        <!-- Desktop: the full grid (xl and up) -->
        <div class="d-none d-xl-block">
            <div class="table-responsive">
                <table class="pcx-table">
                    <thead>
                        <tr>
                            <th style="width:34px">#</th>
                            <th style="width:30%">Product</th>
                            <th class="pcx-unit-cell">Unit</th>
                            <th class="e">Unit Price</th>
                            <th class="e">Quantity</th>
                            <th class="e">Discount</th>
                            <th class="e">Tax %</th>
                            <th class="e">Total</th>
                            <th style="width:44px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, index) in items" :key="item.key || index">
                            <td>
                                <div class="pcx-idx">{{ index + 1 }}</div>
                            </td>
                            <td>
                                <h6 class="pcx-pname">{{ item.name }}</h6>
                                <div v-if="item.barcode" class="pcx-pbar">{{ item.barcode }}</div>
                            </td>
                            <td class="pcx-unit-cell">
                                <select class="pcx-unit" :value="item.unit_id"
                                    @change="handleUnitChange(item.key, $event.target.value)">
                                    <option v-for="unit in item.units || []" :key="unit.id" :value="unit.id">
                                        {{ unit.name }}
                                    </option>
                                </select>
                            </td>
                            <td class="text-end">
                                <input type="number" class="pcx-cell"
                                    :value="val(item.key, 'unit_price', item.unit_price)"
                                    @input="handleItemInput(item.key, 'unit_price', $event.target.value)"
                                    @blur="handleItemBlur(item.key, 'unit_price')" step="any" min="0" />
                            </td>
                            <td class="text-end">
                                <input type="number" class="pcx-cell" :value="val(item.key, 'quantity', item.quantity)"
                                    @input="handleItemInput(item.key, 'quantity', $event.target.value)"
                                    @blur="handleItemBlur(item.key, 'quantity')" step="any" min="0" />
                            </td>
                            <td class="text-end">
                                <input type="number" class="pcx-cell" :value="val(item.key, 'discount', item.discount)"
                                    @input="handleItemInput(item.key, 'discount', $event.target.value)"
                                    @blur="handleItemBlur(item.key, 'discount')" step="any" min="0" />
                            </td>
                            <td class="text-end">
                                <input type="number" class="pcx-cell pcx-cell--tax" :value="val(item.key, 'tax', item.tax)"
                                    @input="handleItemInput(item.key, 'tax', $event.target.value)"
                                    @blur="handleItemBlur(item.key, 'tax')" step="any" min="0" max="50" />
                            </td>
                            <td class="pcx-rtot">{{ formatNumber(item.total) }}</td>
                            <td class="text-center">
                                <button type="button" @click="handleRemoveItem(item.key)" class="pcx-del"
                                    title="Remove Item">
                                    <i class="fa fa-trash-o"></i>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="items.length === 0">
                            <td colspan="9">
                                <div class="pcx-empty">
                                    <i class="fa fa-cubes"></i>
                                    <div class="t">No items yet</div>
                                    <div class="s">Scan a barcode or search a product above to add the first line.</div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot v-if="items.length > 0">
                        <tr>
                            <td colspan="4" class="lab">Totals</td>
                            <td>{{ formatNumber(totalQuantity) }}</td>
                            <td>{{ formatNumber(totalDiscount) }}</td>
                            <td>{{ formatNumber(totalTaxAmount) }}</td>
                            <td>{{ formatNumber(totalAmount) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Below xl: the same line as a card, so nothing has to scroll sideways -->
        <div class="d-xl-none">
            <div class="pcx-icards">
                <div v-for="(item, index) in items" :key="item.key || index" class="pcx-icard">
                    <div class="pcx-icard-hd">
                        <div class="pcx-idx">{{ index + 1 }}</div>
                        <h6 class="pcx-pname">{{ item.name }}</h6>
                        <button type="button" @click="handleRemoveItem(item.key)" class="pcx-del" title="Remove Item">
                            <i class="fa fa-trash-o"></i>
                        </button>
                    </div>
                    <div class="pcx-icard-bd">
                        <div v-if="item.barcode" class="pcx-fld full">
                            <label>Barcode</label>
                            <div class="pcx-pbar">{{ item.barcode }}</div>
                        </div>
                        <div class="pcx-fld full">
                            <label>Unit</label>
                            <select class="pcx-unit" :value="item.unit_id"
                                @change="handleUnitChange(item.key, $event.target.value)">
                                <option v-for="unit in item.units || []" :key="unit.id" :value="unit.id">
                                    {{ unit.name }}
                                </option>
                            </select>
                        </div>
                        <div class="pcx-fld">
                            <label>Unit Price</label>
                            <input type="number" class="pcx-cell" :value="val(item.key, 'unit_price', item.unit_price)"
                                @input="handleItemInput(item.key, 'unit_price', $event.target.value)"
                                @blur="handleItemBlur(item.key, 'unit_price')" step="any" min="0" />
                        </div>
                        <div class="pcx-fld">
                            <label>Quantity</label>
                            <input type="number" class="pcx-cell" :value="val(item.key, 'quantity', item.quantity)"
                                @input="handleItemInput(item.key, 'quantity', $event.target.value)"
                                @blur="handleItemBlur(item.key, 'quantity')" step="any" min="0" />
                        </div>
                        <div class="pcx-fld">
                            <label>Discount</label>
                            <input type="number" class="pcx-cell" :value="val(item.key, 'discount', item.discount)"
                                @input="handleItemInput(item.key, 'discount', $event.target.value)"
                                @blur="handleItemBlur(item.key, 'discount')" step="any" min="0" />
                        </div>
                        <div class="pcx-fld">
                            <label>Tax %</label>
                            <input type="number" class="pcx-cell" :value="val(item.key, 'tax', item.tax)"
                                @input="handleItemInput(item.key, 'tax', $event.target.value)"
                                @blur="handleItemBlur(item.key, 'tax')" step="any" min="0" max="50" />
                        </div>
                        <div class="pcx-icard-tot">
                            <span class="k">Line Total</span>
                            <span class="pcx-rtot">{{ formatNumber(item.total) }}</span>
                        </div>
                    </div>
                </div>

                <div v-if="items.length === 0" class="pcx-empty">
                    <i class="fa fa-cubes"></i>
                    <div class="t">No items yet</div>
                    <div class="s">Scan a barcode or search a product above to add the first line.</div>
                </div>

                <div v-if="items.length > 0" class="pcx-icards-sum">
                    <div>
                        <div class="k">Qty</div>
                        <div class="v">{{ formatNumber(totalQuantity) }}</div>
                    </div>
                    <div>
                        <div class="k">Discount</div>
                        <div class="v">{{ formatNumber(totalDiscount) }}</div>
                    </div>
                    <div>
                        <div class="k">Tax</div>
                        <div class="v">{{ formatNumber(totalTaxAmount) }}</div>
                    </div>
                    <div>
                        <div class="k">Total</div>
                        <div class="v">{{ formatNumber(totalAmount) }}</div>
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
    formatNumber
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

const handleRemoveItem = (key) => {
    if (!confirm('Are you sure?')) return
    call('removeItem', key).then(() => {
        emit('item-removed', key)
    }).catch((error) => {
        console.error('Error removing item:', error)
    })
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
