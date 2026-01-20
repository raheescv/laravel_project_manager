<template>
    <div class="bg-white rounded-lg shadow-sm p-4 md:p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Order Information</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-7 gap-4">
            <div class="form-group">
                <label class="block text-sm font-medium text-gray-700 mb-1">Order No</label>
                <input :value="orderNo" @input="$emit('update:orderNo', $event.target.value)" type="text" readonly
                    class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-600" />
            </div>
            <div class="form-group lg:col-span-2">
                <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5 ml-1">
                    Customer
                </label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none z-10">
                        <i class="fa fa-user text-gray-400 group-focus-within:text-blue-500 transition-colors"></i>
                    </div>
                    <CustomerSelect :modelValue="customerId" @update:modelValue="id => $emit('update:customerId', id)"
                        :initialData="customerId ? { id: customerId, name: customer, mobile: contact } : null"
                        @selected="handleCustomerSelected" placeholder="Search customer..." />
                </div>
                <a @click="$emit('add-customer')"
                    class="text-xs text-blue-600 hover:text-blue-800 cursor-pointer mt-1 block ml-1">
                    + Add New Customer
                </a>
            </div>
            <div class="form-group font-medium">
                <label class="block text-sm font-medium text-gray-700 mb-1">Contact</label>
                <input :value="contact" @input="$emit('update:contact', $event.target.value)" type="tel"
                    placeholder="Enter contact..."
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
            </div>
            <div class="form-group">
                <label class="block text-sm font-medium text-gray-700 mb-1">Salesman</label>
                <SearchableSelect :modelValue="salesman" :options="salesmen"
                    placeholder="Select Salesman" @update:modelValue="$emit('update:salesman', $event)" />
            </div>
            <div class="form-group">
                <label class="block text-sm font-medium text-gray-700 mb-1">Order Date</label>
                <input :value="orderDate" @input="$emit('update:orderDate', $event.target.value)" type="date"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
            </div>
            <div class="form-group">
                <label class="block text-sm font-medium text-gray-700 mb-1">Delivery Date</label>
                <input :value="deliveryDate" @input="$emit('update:deliveryDate', $event.target.value)" type="date"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue'
import axios from 'axios'
import SearchableSelect from '@/components/SearchableSelect.vue'
import CustomerSelect from '@/components/CustomerSelect.vue'

const props = defineProps({
    orderNo: String,
    customer: String,
    customerId: [Number, String],
    contact: String,
    salesman: [Number, String],
    orderDate: String,
    deliveryDate: String,
    customers: {
        type: [Object, Array],
        default: () => []
    },
    salesmen: Object,
    customerLoading: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits([
    'update:orderNo',
    'update:customer',
    'update:customerId',
    'update:contact',
    'update:salesman',
    'update:orderDate',
    'update:deliveryDate',
    'add-customer',
    'customer-selected',
    'search-customer'
])

const handleCustomerSelected = (selected) => {
    if (selected) {
        emit('update:customer', selected.name);
        emit('update:contact', selected.mobile);
        emit('customer-selected', selected);
    } else {
        emit('update:customer', '');
        emit('update:contact', '');
        emit('customer-selected', null);
    }
}
</script>
