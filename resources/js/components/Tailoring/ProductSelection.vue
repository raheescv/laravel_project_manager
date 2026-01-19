<template>
    <div class="bg-gray-50/50 rounded-xl p-6 border border-gray-100">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                <i class="fa fa-shopping-bag text-blue-600"></i>
                {{ isEditing ? 'Update Product' : 'Select Products' }}
            </h2>
            <div class="text-sm text-gray-500 bg-white px-3 py-1 rounded-full border border-gray-200 shadow-sm">
                {{ isEditing ? 'Update selected product details' : 'Add products to the order' }}
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
            <!-- Product Selection (Wide) -->
            <div class="md:col-span-6 form-group">
                <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5 ml-1">
                    Product Search
                </label>
                <div class="relative">
                    <select ref="productSelect" placeholder="Search product by name, code or barcode..."
                        autocomplete="off" class="w-full"></select>
                </div>
            </div>

            <!-- Color -->
            <div class="md:col-span-3 form-group">
                <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5 ml-1">
                    Colour
                </label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i
                            class="fa fa-paint-brush text-gray-400 group-focus-within:text-blue-500 transition-colors"></i>
                    </div>
                    <input v-model="item.product_color" type="text" placeholder="Color..."
                        class="w-full pl-10 pr-3 py-2.5 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-100 focus:border-blue-500 transition-all text-sm shadow-sm" />
                    <a @click="addColor"
                        class="absolute right-0 top-0 mt-2.5 mr-2 text-[10px] font-bold text-blue-600 hover:text-blue-800 cursor-pointer uppercase tracking-wider bg-blue-50 px-2 py-0.5 rounded hover:bg-blue-100 transition-colors">
                        Add New
                    </a>
                </div>
            </div>

            <!-- Quantity -->
            <div class="md:col-span-3 form-group">
                <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5 ml-1">
                    Quantity
                </label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i
                            class="fa fa-sort-numeric-asc text-gray-400 group-focus-within:text-blue-500 transition-colors"></i>
                    </div>
                    <input v-model.number="item.quantity" type="number" step="0.001" min="0.001" placeholder="0.00"
                        @input="calculateAmount"
                        class="w-full pl-10 pr-3 py-2.5 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-100 focus:border-blue-500 transition-all text-sm font-medium shadow-sm" />
                </div>
            </div>

            <!-- Row 2 -->

            <!-- Item Rate -->
            <div class="md:col-span-3 form-group">
                <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5 ml-1">
                    Unit Price
                </label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span
                            class="text-gray-400 font-bold group-focus-within:text-blue-500 transition-colors">₹</span>
                    </div>
                    <input v-model.number="item.unit_price" type="number" step="0.01" min="0" placeholder="0.00"
                        @input="calculateAmount"
                        class="w-full pl-10 pr-3 py-2.5 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-100 focus:border-blue-500 transition-all text-sm shadow-sm" />
                </div>
            </div>

            <!-- Stitch Rate -->
            <div class="md:col-span-3 form-group">
                <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5 ml-1">
                    Stitch Rate
                </label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fa fa-scissors text-gray-400 group-focus-within:text-blue-500 transition-colors"></i>
                    </div>
                    <input v-model.number="item.stitch_rate" type="number" step="0.01" min="0" placeholder="0.00"
                        @input="calculateAmount"
                        class="w-full pl-10 pr-3 py-2.5 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-100 focus:border-blue-500 transition-all text-sm shadow-sm" />
                </div>
            </div>

            <!-- Tax -->
            <div class="md:col-span-2 form-group">
                <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5 ml-1">
                    Tax %
                </label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i
                            class="fa fa-percent text-gray-400 group-focus-within:text-blue-500 transition-colors text-xs"></i>
                    </div>
                    <input v-model.number="item.tax" type="number" step="0.01" min="0" placeholder="0%"
                        @input="calculateAmount"
                        class="w-full pl-10 pr-3 py-2.5 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-100 focus:border-blue-500 transition-all text-sm shadow-sm" />
                </div>
            </div>

            <!-- Amount -->
            <div class="md:col-span-4 form-group">
                <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5 ml-1">
                    Total Amount
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="text-green-600 font-bold">₹</span>
                    </div>
                    <input :value="item.total || 0" type="text" readonly
                        class="w-full pl-10 pr-3 py-2.5 border border-green-200 rounded-lg bg-green-50 text-green-700 font-bold text-lg shadow-sm" />
                </div>
            </div>

            <!-- Actions -->
            <div class="md:col-span-12 flex items-center justify-end pt-4 gap-3 border-t border-gray-200/60 mt-2">
                <button type="button" @click="handleClear"
                    class="px-5 py-2.5 text-gray-600 bg-white border border-gray-300 hover:bg-gray-50 hover:text-gray-800 rounded-lg font-medium transition-all shadow-sm flex items-center gap-2">
                    <i class="fa fa-eraser"></i> {{ isEditing ? 'Cancel' : 'Clear' }}
                </button>
                <button type="button" @click="handleAdd" :disabled="isLoading"
                    class="px-8 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg hover:from-blue-700 hover:to-blue-800 font-semibold disabled:opacity-50 disabled:cursor-not-allowed shadow-md hover:shadow-lg transition-all focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 flex items-center gap-2">
                    <i v-if="isLoading" class="fa fa-spinner fa-spin"></i>
                    <i v-else :class="isEditing ? 'fa fa-save' : 'fa fa-plus-circle'"></i>
                    {{ isLoading ? (isEditing ? 'Updating...' : 'Adding...') : (isEditing ? 'Update Product' : 'Add Product') }}
                </button>
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
            option: (item, escape) => {
                return `<div class="p-2 border-b border-gray-100 hover:bg-gray-50 transition-colors">
                            <div class="flex justify-between items-start">
                                <div class="font-medium text-gray-800">${escape(item.name)}</div>
                                <div class="text-xs font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded">
                                    ₹${escape(item.mrp || 0)}
                                </div>
                            </div>
                            <div class="text-xs text-gray-500 flex flex-wrap gap-2 mt-1">
                                ${item.code ? `<span class="bg-gray-100 px-1.5 py-0.5 rounded border border-gray-200">Ref: ${escape(item.code)}</span>` : ''}
                                ${item.barcode ? `<span class="bg-gray-100 px-1.5 py-0.5 rounded border border-gray-200">Bar: ${escape(item.barcode)}</span>` : ''}
                            </div>
                        </div>`
            },
            item: (item, escape) => {
                return `<div class="flex items-center gap-2">
                            <span class="font-medium text-gray-800">${escape(item.name)}</span>
                         </div>`
            },
            loading: (data, escape) => {
                return '<div class="spinner border-t-2 border-blue-500"></div>'
            }
        },
        onChange: (value) => {
            if (value) {
                const selectedOption = tomSelectInstance.options[value]
                if (selectedOption) {
                    item.value.product_id = selectedOption.id
                    item.value.product_name = selectedOption.name
                    item.value.unit_price = parseFloat(selectedOption.mrp || 0)
                    calculateAmount()
                }
            } else {
                // Only clear if user explicitly cleared it, don't clear if it's just a remount with existing value
                if (item.value.product_id) {
                    item.value.product_id = null
                    item.value.product_name = ''
                    item.value.unit_price = 0
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
