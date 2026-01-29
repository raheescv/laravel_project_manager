<template>
    <div class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
            <!-- Product Selection -->
            <div class="lg:col-span-3">
                <label class="block text-slate-500 font-bold text-[0.65rem] uppercase tracking-widest mb-1.5 px-1">Product</label>
                <div class="relative group">
                    <select ref="productSelect" placeholder="Search product..." autocomplete="off"
                        class="form-control !rounded-xl !border-slate-200 !text-xs !py-1 !px-3"></select>
                </div>
            </div>

            <!-- Color -->
            <div class="lg:col-span-2">
                <label class="block text-slate-500 font-bold text-[0.65rem] uppercase tracking-widest mb-1.5 px-1">Colour</label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400 group-focus-within:text-emerald-500 transition-colors">
                        <i class="fa fa-paint-brush text-[10px]"></i>
                    </div>
                    <input v-model="item.product_color" type="text" placeholder="Color..."
                        class="w-full rounded-xl border border-slate-200 bg-white shadow-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition-all duration-300 text-xs py-1 pl-8 pr-3 font-medium" />
                    <button @click="addColor" class="absolute right-1.5 top-1/2 -translate-y/2 w-6 h-6 flex items-center justify-center rounded-lg bg-slate-50 text-slate-400 hover:bg-blue-50 hover:text-blue-600 transition-colors" type="button">
                        <i class="fa fa-plus text-[9px]"></i>
                    </button>
                </div>
            </div>

            <!-- Quantity -->
            <div class="lg:col-span-1">
                <label class="block text-slate-500 font-bold text-[0.65rem] uppercase tracking-widest mb-1.5 px-1">Qty</label>
                <input v-model.number="item.quantity" type="number" step="0.001" min="0.001" placeholder="0"
                    @input="calculateAmount" 
                    class="w-full rounded-xl border border-slate-200 bg-white shadow-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition-all duration-300 text-xs py-1 px-2 font-bold text-center" />
            </div>

            <!-- Item Rate -->
            <div class="lg:col-span-1">
                <label class="block text-slate-500 font-bold text-[0.65rem] uppercase tracking-widest mb-1.5 px-1">Price</label>
                <div class="relative">
                    <input v-model.number="item.unit_price" type="number" step="0.01" min="0" placeholder="0"
                        @input="calculateAmount" 
                        class="w-full rounded-xl border border-slate-200 bg-white shadow-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition-all duration-300 text-xs py-1 pl-4 pr-1.5 font-bold" />
                </div>
            </div>

            <!-- Stitch Rate -->
            <div class="lg:col-span-1">
                <label class="block text-slate-500 font-bold text-[0.65rem] uppercase tracking-widest mb-1.5 px-1">Stitch</label>
                <input v-model.number="item.stitch_rate" type="number" step="0.01" min="0" placeholder="0"
                    @input="calculateAmount" 
                    class="w-full rounded-xl border border-slate-200 bg-white shadow-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition-all duration-300 text-xs py-1 px-1.5 font-bold text-center" />
            </div>

            <!-- Tax -->
            <div class="lg:col-span-1">
                <label class="block text-slate-500 font-bold text-[0.65rem] uppercase tracking-widest mb-1.5 px-1">Tax</label>
                <input v-model.number="item.tax" type="number" step="0.01" min="0" placeholder="0"
                    @input="calculateAmount" 
                    class="w-full rounded-xl border border-slate-200 bg-white shadow-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition-all duration-300 text-xs py-1 px-1.5 font-bold text-center" />
            </div>

            <!-- Amount -->
            <div class="lg:col-span-1">
                <label class="block text-success font-bold text-[0.65rem] uppercase tracking-widest mb-1.5 px-1">Total</label>
                <div class="relative">
                    <input :value="item.total || 0" type="text" readonly
                        class="w-full rounded-xl border border-success/20 bg-success/5 text-success font-black text-xs py-1 pl-4 pr-1.5" />
                </div>
            </div>

            <!-- Actions -->
            <div class="lg:col-span-2 flex items-center gap-2">
                <button type="button" @click="handleClear"
                    class="flex-1 px-3 py-1.5 rounded-xl border border-slate-200 text-slate-400 font-bold text-[0.65rem] uppercase tracking-widest hover:bg-slate-100 hover:text-slate-600 transition-all">
                    Clear
                </button>
                <button type="button" @click="handleAdd" :disabled="isLoading"
                    class="flex-[2] px-3 py-1.5 rounded-xl transition-all duration-300 shadow-lg font-bold text-[0.65rem] uppercase tracking-widest flex items-center justify-center gap-2"
                    :class="isEditing 
                        ? 'bg-amber-500 hover:bg-amber-600 text-white shadow-amber-200' 
                        : 'bg-emerald-600 hover:bg-emerald-500 text-white shadow-emerald-200'">
                    <i v-if="isLoading" class="fa fa-spinner fa-spin"></i>
                    <i v-else :class="isEditing ? 'fa fa-save' : 'fa fa-plus'"></i>
                    <span class="whitespace-nowrap">{{ isLoading ? 'Wait...' : (isEditing ? 'Update' : 'Add Item') }}</span>
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
        preload: 'focus', // Load items when field is focused
        maxItems: 1,
        dropdownParent: 'body',
        plugins: ['clear_button'],
        load: (query, callback) => {
            // Load if query is empty (on focus) or has search text
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
                const query = this.lastQuery || '';
                const highlightText = (text) => {
                    if (!text) return '';
                    if (!query) return escape(text);
                    const re = new RegExp(`(${query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
                    return escape(text).replace(re, '<span class="ts-highlight">$1</span>');
                };

                return `<div class="d-flex align-items-center justify-content-between py-1">
                            <div class="d-flex flex-column min-width-0">
                                <span class="fw-bold text-dark text-truncate small">${highlightText(item.name)}</span>
                                <span class="text-muted text-[0.65rem] opacity-75 text-truncate mb-0">${highlightText(item.code || item.barcode || '')}</span>
                            </div>
                            <div class="text-blue-600 fw-black ms-3 small">
                                ${escape(item.mrp || 0)}
                            </div>
                        </div>`
            },
            item: (item, escape) => {
                return `<div class="d-flex align-items-center gap-2">
                            <span class="fw-bold text-slate-700 text-xs">${escape(item.name)}</span>
                         </div>`
            },
            loading: (data, escape) => {
                return '<div class="p-2 text-center text-slate-400 text-[0.65rem] font-bold uppercase tracking-widest">Searching...</div>'
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