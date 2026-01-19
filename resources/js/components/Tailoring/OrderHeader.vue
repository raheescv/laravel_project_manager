<template>
    <div class="bg-white rounded-lg shadow-sm p-4 md:p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Order Information</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <div class="form-group">
                <label class="block text-sm font-medium text-gray-700 mb-1">Order No</label>
                <input :value="orderNo" @input="$emit('update:orderNo', $event.target.value)" type="text" readonly
                    class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-600" />
            </div>
            <div class="form-group">
                <label class="block text-sm font-medium text-gray-700 mb-1">Customer</label>
                <input :value="customer" @input="$emit('update:customer', $event.target.value)" type="text"
                    placeholder="Search by name or mobile..."
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                <a @click="$emit('add-customer')"
                    class="text-xs text-blue-600 hover:text-blue-800 cursor-pointer mt-1 block">
                    Add Customer
                </a>
            </div>
            <div class="form-group">
                <label class="block text-sm font-medium text-gray-700 mb-1">Contact</label>
                <input :value="contact" @input="$emit('update:contact', $event.target.value)" type="tel" placeholder="Enter contact..."
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
            </div>
            <div class="form-group">
                <label class="block text-sm font-medium text-gray-700 mb-1">Salesman</label>
                <select :value="salesman" @change="$emit('update:salesman', $event.target.value)"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Select Salesman</option>
                    <option v-for="(name, id) in salesmen" :key="id" :value="id">{{ name }}</option>
                </select>
            </div>
            <div class="form-group">
                <label class="block text-sm font-medium text-gray-700 mb-1">Order Date</label>
                <input :value="orderDate" @input="$emit('update:orderDate', $event.target.value)" type="date"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    orderNo: String,
    customer: String,
    contact: String,
    salesman: [Number, String],
    orderDate: String,
    customers: Object,
    salesmen: Object,
})

defineEmits(['update:orderNo', 'update:customer', 'update:contact', 'update:salesman', 'update:orderDate', 'add-customer'])
</script>
