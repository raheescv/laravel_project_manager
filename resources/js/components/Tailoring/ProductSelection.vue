<template>
    <div class="product-selection">
        <!-- Row 1: Product search -->
        <div class="rounded-lg border border-slate-200 bg-white p-2 shadow-sm">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <!-- Product -->
                <div class="min-w-0">
                    <label class="field-label">Product</label>
                    <select ref="productSelect" placeholder="Search product..." autocomplete="off"
                        class="field-input form-control !rounded-md !border-slate-200 !text-xs !py-1.5 !px-2 w-full"></select>
                </div>
                <!-- Barcode -->
                <div class="min-w-0">
                    <label class="field-label">Barcode</label>
                    <div class="flex gap-1.5">
                        <div class="relative flex-1 min-w-0">
                            <i class="field-icon fa fa-barcode"></i>
                            <input ref="barcodeInputRef" v-model="barcodeInput" type="text"
                                placeholder="Scan or type..."
                                @keydown.enter.prevent="barcodeInput?.trim() ? handleBarcodeSubmit() : handleAdd()"
                                class="field-input field-input-icon w-full" />
                        </div>
                        <button type="button" @click="emit('open-scanner')"
                            class="scan-btn shrink-0 w-9 flex items-center justify-center rounded-md border border-indigo-200 bg-indigo-50 text-indigo-600 hover:bg-indigo-100 transition-colors"
                            title="Open scanner">
                            <i class="fa fa-camera"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Row 2: Details & pricing -->
        <div class="rounded-lg border border-slate-200 bg-slate-50/30 p-2 shadow-sm mt-2">
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-8 gap-2">
                <!-- Color -->
                <div class="col-span-2 sm:col-span-1 min-w-0">
                    <label class="field-label">Colour</label>
                    <div class="relative">
                        <i class="field-icon fa fa-paint-brush"></i>
                        <input v-model="item.product_color" type="text" placeholder="—"
                            @keydown.enter.prevent="handleAdd"
                            class="field-input field-input-icon w-full" />
                    </div>
                </div>
                <!-- Qty -->
                <div>
                    <label class="field-label">Qty</label>
                    <input v-model.number="item.quantity" type="number" step="0.001" min="0.001" placeholder="0"
                        @input="calculateAmount"
                        @keydown.enter.prevent="handleAdd"
                        class="field-input field-input-num w-full text-center" />
                </div>
                <!-- Qty/Item -->
                <div>
                    <label class="field-label">Qty/Item</label>
                    <input v-model.number="item.quantity_per_item" type="number" step="0.001" min="0.001" placeholder="1"
                        @input="calculateAmount"
                        @keydown.enter.prevent="handleAdd"
                        class="field-input field-input-num w-full text-center" />
                </div>
                <!-- Price -->
                <div>
                    <label class="field-label">Price</label>
                    <input v-model.number="item.unit_price" type="number" step="0.01" min="0" placeholder="0"
                        @input="calculateAmount"
                        @keydown.enter.prevent="handleAdd"
                        class="field-input field-input-num w-full" />
                </div>
                <!-- Stitch -->
                <div>
                    <label class="field-label">Stitching Rate</label>
                    <input v-model.number="item.stitch_rate" type="number" step="0.01" min="0" placeholder="0"
                        @input="calculateAmount"
                        @keydown.enter.prevent="handleAdd"
                        class="field-input field-input-num w-full text-center" />
                </div>
                <!-- Tax -->
                <div>
                    <label class="field-label">Tax %</label>
                    <input v-model.number="item.tax" type="number" step="0.01" min="0" placeholder="0"
                        @input="calculateAmount"
                        @keydown.enter.prevent="handleAdd"
                        class="field-input field-input-num w-full text-center" />
                </div>
                <!-- Total -->
                <div>
                    <label class="field-label text-emerald-600">Total</label>
                    <div class="field-input field-total w-full bg-emerald-50 border-emerald-200 text-emerald-700 font-black">
                        {{ formatTotal(item.total) }}
                    </div>
                </div>
                <!-- Actions -->
                <div class="col-span-2 sm:col-span-3 md:col-span-4 lg:col-span-1 flex flex-col-reverse sm:flex-row gap-1.5 justify-end items-end">
                    <button type="button" @click="handleClear"
                        class="action-btn action-btn-secondary">
                        <i class="fa fa-times"></i>
                        Clear
                    </button>
                    <button type="button" @click="handleAdd" :disabled="isLoading"
                        class="action-btn"
                        :class="isEditing ? 'action-btn-edit' : 'action-btn-add'">
                        <i v-if="isLoading" class="fa fa-spinner fa-spin"></i>
                        <i v-else :class="isEditing ? 'fa fa-save' : 'fa fa-plus'"></i>
                        {{ isLoading ? 'Wait...' : (isEditing ? 'Update' : 'Add') }}
                    </button>
                </div>
            </div>
        </div>

    </div>
</template>


<script setup>
import { ref, watch, nextTick, onMounted, onBeforeUnmount } from 'vue'
import axios from 'axios'
import { useToast } from 'vue-toastification'

const props = defineProps({
    modelValue: Object,
    products: Array,
    colors: Array,
    isLoading: {
        type: Boolean,
        default: false
    },
    isEditing: {
        type: Boolean,
        default: false
    },
    /** Barcode passed from parent (e.g. Order page) when scanner is at page level */
    barcodeFromScanner: {
        type: [String, Object],
        default: null
    }
})

const emit = defineEmits(['update:modelValue', 'add-item', 'calculate-amount', 'clear', 'open-scanner', 'clear-barcode'])

const toast = useToast()
const productSelect = ref(null)
const barcodeInputRef = ref(null)
let tomSelectInstance = null

const barcodeInput = ref('')

const item = ref(props.modelValue || {
    product_id: null,
    product_name: '',
    product_color: '',
    quantity: 0,
    quantity_per_item: 1,
    unit_price: 0,
    stitch_rate: 0,
    tax: 0,
    total: 0,
})

const lookupProductByBarcode = async (barcode) => {
    const code = String(barcode || '').trim().replace(/[^a-zA-Z0-9]/g, '')
    if (!code) return null

    try {
        const res = await axios.get('/tailoring/order/products/by-barcode', {
            params: { barcode: code }
        })
        if (res.data?.success && res.data?.data) {
            return res.data.data
        }
    } catch (err) {
        console.error('Barcode lookup failed:', err)
    }
    return null
}

const selectProduct = (product) => {
    if (!product) return
    item.value.product_id = product.id
    item.value.product_name = product.name
    item.value.unit_price = parseFloat(product.mrp || 0)
    item.value.quantity = 1
    item.value.quantity_per_item = item.value.quantity_per_item ?? 1
    if (tomSelectInstance) {
        if (!tomSelectInstance.options[product.id]) {
            tomSelectInstance.addOption({
                id: product.id,
                name: product.name,
                mrp: product.mrp,
                code: product.code,
                barcode: product.barcode
            })
        }
        tomSelectInstance.setValue(product.id, true)
    }
    calculateAmount()
}

const handleBarcodeSubmit = async () => {
    const code = barcodeInput.value?.trim()
    if (!code) return

    const product = await lookupProductByBarcode(code)
    if (product) {
        selectProduct(product)
        barcodeInput.value = ''
        toast.success(`Product added: ${product.name}`)
    } else {
        toast.error(`Barcode not found: ${code}`)
    }
}

const processBarcode = async (code) => {
    const c = String(code || '').trim().replace(/[^a-zA-Z0-9]/g, '')
    if (!c) return
    const product = await lookupProductByBarcode(c)
    if (product) {
        selectProduct(product)
        toast.success(`Product added: ${product.name}`)
    } else {
        toast.error(`Barcode not found: ${c}`)
    }
}

const initializeProductSelect = () => {
    if (!productSelect.value || typeof window.TomSelect === 'undefined') {
        console.warn('TomSelect not available or element missing')
        return
    }

    if (tomSelectInstance) tomSelectInstance.destroy()

    tomSelectInstance = new window.TomSelect(productSelect.value, {
        valueField: 'id',
        labelField: 'name',
        searchField: ['name', 'code', 'barcode'],
        placeholder: 'Search & Select Product...',
        preload: 'focus',
        maxItems: 1,
        dropdownParent: 'body',
        plugins: ['clear_button'],
        load: (query, callback) => {
            const url = '/tailoring/order/products?search=' + encodeURIComponent(query || '')
            axios.get(url)
                .then(res => {
                    if (res.data.success && res.data.data && Array.isArray(res.data.data)) {
                        callback(res.data.data)
                    } else {
                        callback()
                    }
                })
                .catch(err => {
                    console.error('Error searching products:', err)
                    callback()
                })
        },
        render: {
            option: function (item, escape) {
                const query = this.lastQuery || ''
                const highlightText = (text) => {
                    if (!text) return ''
                    if (!query) return escape(text)
                    const re = new RegExp(`(${query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi')
                    return escape(text).replace(re, '<span class="ts-highlight">$1</span>')
                }
                return `<div class="d-flex align-items-center justify-content-between py-1">
                    <div class="d-flex flex-column min-width-0">
                        <span class="fw-bold text-dark text-truncate small">${highlightText(item.name)}</span>
                        <span class="text-muted text-[0.65rem] opacity-75 text-truncate mb-0">${highlightText(item.code || item.barcode || '')}</span>
                    </div>
                    <div class="text-blue-600 fw-black ms-3 small">${escape(item.mrp || 0)}</div>
                </div>`
            },
            item: (item, escape) => {
                return `<div class="d-flex align-items-center gap-2"><span class="fw-bold text-slate-700 text-xs">${escape(item.name)}</span></div>`
            },
            loading: () => '<div class="p-2 text-center text-slate-400 text-[0.65rem] font-bold uppercase tracking-widest">Searching...</div>'
        },
        onChange: (value) => {
            if (value) {
                const selectedOption = tomSelectInstance.options[value]
                if (selectedOption) {
                    item.value.product_id = selectedOption.id
                    item.value.product_name = selectedOption.name
                    item.value.unit_price = parseFloat(selectedOption.mrp || 0)
                    item.value.quantity = 1
                    item.value.quantity_per_item = item.value.quantity_per_item ?? 1
                    calculateAmount()
                }
            } else {
                if (item.value.product_id) {
                    item.value.product_id = null
                    item.value.product_name = ''
                    item.value.unit_price = 0
                    item.value.quantity = 1
                    item.value.quantity_per_item = 1
                }
            }
        }
    })

    if (item.value.product_id) {
        if (!tomSelectInstance.options[item.value.product_id]) {
            tomSelectInstance.addOption({
                id: item.value.product_id,
                name: item.value.product_name || 'Selected Product',
                mrp: item.value.unit_price || 0
            })
        }
        tomSelectInstance.setValue(item.value.product_id, true)
    }
}

const calculateAmount = async () => {
    emit('calculate-amount', item.value)
}

const formatTotal = (val) => {
    return parseFloat(val || 0).toFixed(2)
}

const handleClear = () => {
    item.value = {
        product_id: null,
        product_name: '',
        product_color: '',
        quantity: 0,
        quantity_per_item: 1,
        unit_price: 0,
        stitch_rate: 0,
        tax: 0,
        total: 0,
    }
    barcodeInput.value = ''
    if (tomSelectInstance) tomSelectInstance.clear()
    emit('clear')
}

const handleAdd = () => {
    if (!item.value.product_id && !item.value.product_name) {
        toast.error('Please select a product')
        return
    }
    if (!item.value.quantity || item.value.quantity <= 0) {
        toast.error('Please enter valid quantity')
        return
    }
    if (!item.value.quantity_per_item || item.value.quantity_per_item <= 0) {
        toast.error('Please enter valid quantity per item')
        return
    }
    emit('add-item', { ...item.value })
    handleClear()
}

let isUpdatingFromProps = false
watch(item, (newVal) => {
    if (!isUpdatingFromProps) emit('update:modelValue', { ...newVal })
}, { deep: true })

watch(() => props.barcodeFromScanner, async (val) => {
    const code = val?.code ?? (typeof val === 'string' ? val : null)
    if (code) {
        await processBarcode(code)
        emit('clear-barcode')
    }
}, { flush: 'post' })

watch(() => props.modelValue, (newVal) => {
    if (newVal && JSON.stringify(newVal) !== JSON.stringify(item.value)) {
        isUpdatingFromProps = true
        item.value = { ...newVal }

        if (tomSelectInstance && newVal.product_id && tomSelectInstance.getValue() != newVal.product_id) {
            if (!tomSelectInstance.options[newVal.product_id]) {
                tomSelectInstance.addOption({
                    id: newVal.product_id,
                    name: newVal.product_name || 'Selected Product',
                    mrp: newVal.unit_price || 0
                })
            }
            tomSelectInstance.setValue(newVal.product_id, true)
        } else if (tomSelectInstance && !newVal.product_id && tomSelectInstance.getValue()) {
            tomSelectInstance.clear(true)
        }

        nextTick(() => { isUpdatingFromProps = false })
    }
}, { deep: true })

onMounted(() => {
    if (typeof window.TomSelect !== 'undefined') {
        initializeProductSelect()
    } else {
        const checkInterval = setInterval(() => {
            if (typeof window.TomSelect !== 'undefined') {
                clearInterval(checkInterval)
                initializeProductSelect()
            }
        }, 100)
        setTimeout(() => clearInterval(checkInterval), 5000)
    }
})

onBeforeUnmount(() => {
    if (tomSelectInstance) tomSelectInstance.destroy()
})
</script>

<style scoped>
.field-label {
    @apply block text-slate-500 font-semibold text-[0.6rem] uppercase tracking-wider mb-0.5;
}
.field-input {
    @apply rounded-md border border-slate-200 bg-white px-2 py-1.5 text-xs font-medium focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none transition-all min-h-[2rem];
}
.field-input-icon {
    @apply pl-7;
}
.field-input-num {
    @apply font-bold tabular-nums;
}
.field-icon {
    @apply absolute left-2 top-1/2 -translate-y-1/2 text-slate-400 text-[10px] pointer-events-none;
}
.field-total {
    @apply py-1.5 px-2 text-xs;
}
.scan-btn {
    min-height: 2rem;
}
.action-btn {
    @apply px-3 py-2 rounded-md font-bold text-[0.65rem] uppercase tracking-wider flex items-center justify-center gap-1.5 transition-all;
}
.action-btn-add {
    @apply bg-emerald-600 text-white hover:bg-emerald-500 shadow-md;
}
.action-btn-edit {
    @apply bg-amber-500 text-white hover:bg-amber-600 shadow-md;
}
.action-btn-secondary {
    @apply border border-slate-200 text-slate-500 hover:bg-slate-100 hover:text-slate-700;
}
</style>
