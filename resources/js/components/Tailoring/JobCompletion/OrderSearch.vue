<template>
    <div class="bg-white rounded-lg shadow-sm p-4 md:p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Search Order</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
            <div class="form-group">
                <label class="block text-sm font-medium text-gray-700 mb-1">Order No</label>
                <input 
                    :modelValue="orderNo"
                    @update:modelValue="$emit('update:orderNo', $event)"
                    type="text"
                    placeholder="Enter order number..."
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                />
            </div>
            <div class="form-group">
                <label class="block text-sm font-medium text-gray-700 mb-1">Customer</label>
                <input 
                    :modelValue="customer"
                    @update:modelValue="$emit('update:customer', $event)"
                    type="text"
                    placeholder="Enter customer name..."
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                />
            </div>
            <div class="form-group">
                <label class="block text-sm font-medium text-gray-700 mb-1">Contact</label>
                <input 
                    :modelValue="contact"
                    @update:modelValue="$emit('update:contact', $event)"
                    type="text"
                    placeholder="Enter contact..."
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                />
            </div>
            <div class="form-group">
                <label class="block text-sm font-medium text-gray-700 mb-1">Order Date</label>
                <input 
                    :modelValue="orderDate"
                    @update:modelValue="$emit('update:orderDate', $event)"
                    type="date"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                />
            </div>
            <div class="form-group">
                <label class="block text-sm font-medium text-gray-700 mb-1">Delivery Date</label>
                <input 
                    :modelValue="deliveryDate"
                    @update:modelValue="$emit('update:deliveryDate', $event)"
                    type="date"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                />
            </div>
            <div class="form-group">
                <label class="block text-sm font-medium text-gray-700 mb-1">Rack</label>
                <select 
                    :modelValue="rack"
                    @update:modelValue="$emit('update:rack', $event)"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
                    <option value="">Select rack...</option>
                    <option v-for="(name, id) in racks" :key="id" :value="id">{{ name }}</option>
                </select>
            </div>
        </div>
        <div class="flex justify-center gap-4 mt-4">
            <button 
                @click="$emit('clear')"
                class="px-6 py-2 bg-white border-2 border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 font-semibold"
            >
                Clear
            </button>
            <button 
                @click="$emit('search')"
                class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-semibold"
            >
                Search
            </button>
        </div>
    </div>
</template>

<script setup>
defineProps({
    orderNo: String,
    customer: String,
    contact: String,
    orderDate: String,
    deliveryDate: String,
    rack: [Number, String],
    racks: Object,
})

defineEmits(['update:orderNo', 'update:customer', 'update:contact', 'update:orderDate', 'update:deliveryDate', 'update:rack', 'search', 'clear'])
</script>
