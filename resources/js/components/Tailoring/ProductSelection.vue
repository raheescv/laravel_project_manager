<template>
    <div class="bg-white rounded-lg shadow-sm p-4 md:p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Select Products</h2>
        <div class="grid grid-cols-1 md:grid-cols-7 gap-4 items-end">
            <div class="form-group">
                <label class="block text-sm font-medium text-gray-700 mb-1">Product</label>
                <input 
                    v-model="item.product_name"
                    type="text"
                    placeholder="Search product..."
                    @input="searchProduct"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                />
            </div>
            <div class="form-group">
                <label class="block text-sm font-medium text-gray-700 mb-1">Product Colour</label>
                <input 
                    v-model="item.product_color"
                    type="text"
                    placeholder="Search color..."
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                />
                <a 
                    @click="addColor"
                    class="text-xs text-blue-600 hover:text-blue-800 cursor-pointer mt-1 block"
                >
                    ADD COLOR
                </a>
            </div>
            <div class="form-group">
                <label class="block text-sm font-medium text-gray-700 mb-1">Qty</label>
                <input 
                    v-model.number="item.quantity"
                    type="number"
                    step="0.001"
                    min="0.001"
                    placeholder="Enter qty..."
                    @input="calculateAmount"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                />
            </div>
            <div class="form-group">
                <label class="block text-sm font-medium text-gray-700 mb-1">Item Rate</label>
                <input 
                    v-model.number="item.unit_price"
                    type="number"
                    step="0.01"
                    min="0"
                    placeholder="Enter rate..."
                    @input="calculateAmount"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                />
            </div>
            <div class="form-group">
                <label class="block text-sm font-medium text-gray-700 mb-1">Stitch Rate</label>
                <input 
                    v-model.number="item.stitch_rate"
                    type="number"
                    step="0.01"
                    min="0"
                    placeholder="Enter stitch rate..."
                    @input="calculateAmount"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                />
            </div>
            <div class="form-group">
                <label class="block text-sm font-medium text-gray-700 mb-1">Tax</label>
                <input 
                    v-model.number="item.tax"
                    type="number"
                    step="0.01"
                    min="0"
                    placeholder="Enter tax %..."
                    @input="calculateAmount"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                />
            </div>
            <div class="form-group">
                <label class="block text-sm font-medium text-gray-700 mb-1">Amount</label>
                <input 
                    :value="item.total || 0"
                    type="text"
                    readonly
                    class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-600"
                />
            </div>
        </div>
        <button 
            type="button"
            @click="handleAdd"
            class="mt-4 px-6 py-2 bg-gray-400 text-white rounded-md hover:bg-gray-500 font-semibold"
        >
            Add
        </button>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import axios from 'axios'
import { useToast } from 'vue-toastification'

const props = defineProps({
    modelValue: Object,
    products: Array,
    colors: Array,
})

const emit = defineEmits(['update:modelValue', 'add-item', 'calculate-amount'])

const toast = useToast()
const item = ref(props.modelValue || {
    product_name: '',
    product_color: '',
    quantity: 0,
    unit_price: 0,
    stitch_rate: 0,
    tax: 0,
    total: 0,
})

const searchProduct = async () => {
    if (item.value.product_name.length < 2) return
    
    try {
        const response = await axios.get('/tailoring/order/products', {
            params: { search: item.value.product_name }
        })
        if (response.data.success && response.data.data.length > 0) {
            const product = response.data.data[0]
            item.value.product_id = product.id
            item.value.product_name = product.name
            item.value.unit_price = product.mrp || 0
        }
    } catch (error) {
        console.error('Failed to search product', error)
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

const handleAdd = () => {
    if (!item.value.product_name || !item.value.quantity || !item.value.unit_price) {
        toast.error('Please fill all required fields')
        return
    }
    emit('add-item', { ...item.value })
    // Reset form
    item.value = {
        product_name: '',
        product_color: '',
        quantity: 0,
        unit_price: 0,
        stitch_rate: 0,
        tax: 0,
        total: 0,
    }
}

watch(() => props.modelValue, (newVal) => {
    if (newVal) {
        item.value = { ...newVal }
    }
}, { deep: true })

watch(item, (newVal) => {
    emit('update:modelValue', newVal)
}, { deep: true })
</script>
