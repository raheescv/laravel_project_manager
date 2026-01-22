<template>
    <div class="card mb-4 border shadow-none" style="border-color: #e2e8f0 !important;">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h5 class="card-title text-gray-800 d-flex align-items-center gap-2 mb-0">
                    <i class="fa fa-shopping-bag text-primary"></i>
                    {{ isEditing ? 'Update Product' : 'Select Products' }}
                </h5>
                <span class="badge bg-white text-muted border border-light shadow-sm rounded-pill fw-normal px-3 py-2">
                    {{ isEditing ? 'Update selected product details' : 'Add products to the order' }}
                </span>
            </div>

            <div class="row g-2 align-items-end">
                <!-- Product Selection -->
                <div class="col-lg-3">
                    <label class="form-label small text-muted text-uppercase fw-bold mb-1">Product</label>
                    <div class="position-relative">
                        <select ref="productSelect" placeholder="Search product..." autocomplete="off"
                            class="form-control"></select>
                    </div>
                </div>

                <!-- Color -->
                <div class="col-lg-2 col-md-6">
                    <label class="form-label small text-muted text-uppercase fw-bold mb-1">Colour</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted px-2">
                            <i class="fa fa-paint-brush"></i>
                        </span>
                        <input v-model="item.product_color" type="text" placeholder="Color..."
                            class="form-control border-start-0 ps-0" />
                        <button @click="addColor" class="btn btn-outline-secondary btn-sm" type="button">
                            <i class="fa fa-plus"></i>
                        </button>
                    </div>
                </div>

                <!-- Quantity -->
                <div class="col">
                    <label class="form-label small text-muted text-uppercase fw-bold mb-1">Qty</label>
                    <input v-model.number="item.quantity" type="number" step="0.001" min="0.001" placeholder="0"
                        @input="calculateAmount" class="form-control fw-bold px-2" />
                </div>

                <!-- Item Rate -->
                <div class="col">
                    <label class="form-label small text-muted text-uppercase fw-bold mb-1">Price</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted px-1 fw-bold">₹</span>
                        <input v-model.number="item.unit_price" type="number" step="0.01" min="0" placeholder="0"
                            @input="calculateAmount" class="form-control border-start-0 ps-0 px-1" />
                    </div>
                </div>

                <!-- Stitch Rate -->
                <div class="col">
                    <label class="form-label small text-muted text-uppercase fw-bold mb-1">Stitch</label>
                    <input v-model.number="item.stitch_rate" type="number" step="0.01" min="0" placeholder="0"
                        @input="calculateAmount" class="form-control px-2" />
                </div>

                <!-- Tax -->
                <div class="col">
                    <label class="form-label small text-muted text-uppercase fw-bold mb-1">Tax</label>
                    <input v-model.number="item.tax" type="number" step="0.01" min="0" placeholder="0"
                        @input="calculateAmount" class="form-control px-2" />
                </div>

                <!-- Amount -->
                <div class="col">
                    <label class="form-label small text-muted text-uppercase fw-bold mb-1">Total</label>
                    <div class="input-group">
                        <span
                            class="input-group-text bg-success bg-opacity-10 border-success border-end-0 text-success px-1 fw-bold">₹</span>
                        <input :value="item.total || 0" type="text" readonly
                            class="form-control bg-success bg-opacity-10 border-success border-start-0 text-success fw-bold px-1" />
                    </div>
                </div>

                <div class="col-lg-auto col-md-12 d-flex gap-1 justify-content-end">
                    <button type="button" @click="handleClear"
                        class="btn btn-link btn-sm text-secondary text-decoration-none px-2" title="Clear/Cancel">
                        <i class="fa fa-times me-1"></i>Clear
                    </button>
                    <button type="button" @click="handleAdd" :disabled="isLoading"
                        class="btn btn-dark d-flex align-items-center justify-content-center gap-2 px-4 shadow-none rounded-3">
                        <i v-if="isLoading" class="fa fa-spinner fa-spin"></i>
                        <i v-else :class="isEditing ? 'fa fa-save' : 'fa fa-plus'"></i>
                        <span class="text-nowrap fw-semibold">{{ isLoading ? (isEditing ? 'Updating...' : 'Adding...') :
                            (isEditing ? 'Update' : 'Add Item') }}</span>
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
    }
})

const emit = defineEmits(['update:modelValue', 'add-item', 'calculate-amount', 'clear'])

const toast = useToast()
const productSelect = ref(null)
let tomSelectInstance = null

const item = ref(props.modelValue || {
    product_id: null,
    product_name: '',
    product_color: '',
    quantity: 0,
    unit_price: 0,
    stitch_rate: 0,
    tax: 0,
    total: 0,
})

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
        preload: true, // Preload common items if needed
        maxItems: 1,
        dropdownParent: 'body',
        plugins: ['clear_button'],
        load: (query, callback) => {
            if (!query || query.length < 2) {
                callback()
                return
            }
            const url = '/tailoring/order/products?search=' + encodeURIComponent(query)
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
                const query = this.lastQuery || '';
                const highlightText = (text) => {
                    if (!text) return '';
                    if (!query) return escape(text);
                    const re = new RegExp(`(${query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
                    return escape(text).replace(re, '<span class="ts-highlight">$1</span>');
                };

                return `<div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex flex-column min-width-0">
                                <span class="fw-medium text-dark text-truncate">${highlightText(item.name)}</span>
                                <span class="text-muted small opacity-75 text-truncate mb-0">${highlightText(item.code || item.barcode || '')}</span>
                            </div>
                            <div class="text-success fw-bold ms-3">
                                <span class="small opacity-75">₹</span>${escape(item.mrp || 0)}
                            </div>
                        </div>`
            },
            item: (item, escape) => {
                return `<div class="d-flex align-items-center gap-2">
                            <span class="fw-medium text-dark">${escape(item.name)}</span>
                         </div>`
            },
            loading: (data, escape) => {
                return '<div class="p-2 text-center text-muted small opacity-50">Searching...</div>'
            }
        },
        onChange: (value) => {
            if (value) {
                const selectedOption = tomSelectInstance.options[value]
                if (selectedOption) {
                    item.value.product_id = selectedOption.id
                    item.value.product_name = selectedOption.name
                    item.value.unit_price = parseFloat(selectedOption.mrp || 0)
                    item.value.quantity = 1
                    calculateAmount()
                }
            } else {
                // Only clear if user explicitly cleared it, don't clear if it's just a remount with existing value
                if (item.value.product_id) {
                    item.value.product_id = null
                    item.value.product_name = ''
                    item.value.unit_price = 0
                    item.value.quantity = 1
                }
            }
        }
    })

    // Set initial value if exists
    if (item.value.product_id) {
        tomSelectInstance.setValue(item.value.product_id, true) // silently
    }
}

const calculateAmount = async () => {
    emit('calculate-amount', item.value)
}

const addColor = () => {
    const color = prompt('Enter new color name:')
    if (color && color.trim()) {
        item.value.product_color = color.trim()
    }
}

const handleClear = () => {
    item.value = {
        product_id: null,
        product_name: '',
        product_color: '',
        quantity: 0,
        unit_price: 0,
        stitch_rate: 0,
        tax: 0,
        total: 0,
    }
    if (tomSelectInstance) {
        tomSelectInstance.clear()
    }
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

    emit('add-item', { ...item.value })

    handleClear()
}

let isUpdatingFromProps = false
watch(item, (newVal) => {
    if (!isUpdatingFromProps) {
        emit('update:modelValue', { ...newVal })
    }
}, { deep: true })

watch(() => props.modelValue, (newVal) => {
    if (newVal && JSON.stringify(newVal) !== JSON.stringify(item.value)) {
        isUpdatingFromProps = true
        item.value = { ...newVal }

        // Sync TomSelect if needed
        if (tomSelectInstance && newVal.product_id && tomSelectInstance.getValue() != newVal.product_id) {
            tomSelectInstance.setValue(newVal.product_id, true)
        } else if (tomSelectInstance && !newVal.product_id && tomSelectInstance.getValue()) {
            tomSelectInstance.clear(true)
        }

        nextTick(() => {
            isUpdatingFromProps = false
        })
    }
}, { deep: true })

onMounted(() => {
    // Check for TomSelect availability
    if (typeof window.TomSelect !== 'undefined') {
        initializeProductSelect()
    } else {
        // Fallback polling if script loads late
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