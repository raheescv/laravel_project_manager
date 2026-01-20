<template>
    <div class="grid grid-cols-1 lg:grid-cols-10 gap-6">
        <!-- Search Order Form (4/10) -->
        <div class="lg:col-span-4">
            <div class="bg-white rounded-xl shadow-md border border-gray-100 p-4 md:p-6 transition-all hover:shadow-lg h-full">
                <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center gap-2">
                    <i class="fa fa-search text-blue-600"></i>
                    Search Order
                </h2>
                <div class="space-y-6">
                    <!-- Order No with Autocomplete -->
                    <div class="form-group">
                        <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5 ml-1">
                            Order Number
                        </label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none z-10">
                                <i class="fa fa-hashtag text-gray-400 group-focus-within:text-blue-500 transition-colors"></i>
                            </div>
                            <select ref="orderNoSelect" placeholder="Enter or select order no..." autocomplete="off"
                                class="w-full"></select>
                        </div>
                    </div>

                    <!-- Customer Dropdown with Search -->
                    <div class="form-group">
                        <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5 ml-1">
                            Customer
                        </label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none z-10">
                                <i class="fa fa-user text-gray-400 group-focus-within:text-blue-500 transition-colors"></i>
                            </div>
                            <select ref="customerSelect" placeholder="Search customer (Name or Mobile)..." autocomplete="off"
                                class="w-full"></select>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-8 pt-6 border-t border-gray-50">
                    <button @click="handleClear"
                        class="px-6 py-2.5 bg-white border border-gray-300 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-gray-800 font-semibold transition-all shadow-sm flex items-center gap-2">
                        <i class="fa fa-refresh"></i>
                        Clear
                    </button>
                    <button @click="$emit('search')"
                        class="px-8 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg hover:from-blue-700 hover:to-blue-800 font-bold transition-all shadow-md hover:shadow-lg flex items-center gap-2">
                        <i class="fa fa-search"></i>
                        Search Order
                    </button>
                </div>
            </div>
        </div>

        <!-- Recent Orders (6/10) -->
        <div class="lg:col-span-6">
            <div v-if="customerOrders.length > 0"
                class="bg-white rounded-xl shadow-md border border-gray-100 p-4 transition-all overflow-hidden h-full">
                <h3 class="text-sm font-bold text-gray-700 mb-3 flex items-center gap-2">
                    <i class="fa fa-list text-blue-500"></i>
                    Recent Orders for {{ customer }}
                </h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-gray-600 font-semibold uppercase text-xs">
                            <tr>
                                <th class="px-4 py-3">Order No</th>
                                <th class="px-4 py-3">Order Date</th>
                                <th class="px-4 py-3">Delivery Date</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="order in customerOrders" :key="order.id" class="hover:bg-blue-50/50 transition-colors">
                                <td class="px-4 py-3 font-bold text-blue-600">{{ order.order_no }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ formatDate(order.order_date) }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ formatDate(order.delivery_date) }}</td>
                                <td class="px-4 py-3">
                                    <span :class="getStatusClass(order.completion_status)"
                                        class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase">
                                        {{ order.completion_status || 'Pending' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <button @click="selectOrder(order.order_no)"
                                        class="text-blue-600 hover:text-blue-800 font-bold flex items-center gap-1 ml-auto">
                                        Select <i class="fa fa-chevron-right text-[10px]"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Placeholder when no orders are loaded to maintain layout if desired, 
                 or we can let it be empty. The user said 4-6 ratio, so keeping the 
                 structure even if empty helps visualize the ratio. -->
            <div v-else class="h-full flex items-center justify-center bg-gray-50/50 border-2 border-dashed border-gray-200 rounded-xl p-8 text-gray-400">
                <div class="text-center">
                    <i class="fa fa-info-circle text-2xl mb-2"></i>
                    <p>Select a customer to view recent orders</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, watch, nextTick } from 'vue'
import axios from 'axios'

const props = defineProps({
    orderNo: String,
    customer: String,
    contact: String,
    customers: {
        type: Array,
        default: () => []
    },
    orderNumbers: {
        type: Array,
        default: () => []
    }
})

const emit = defineEmits(['update:orderNo', 'update:customer', 'update:contact', 'search', 'clear'])

const orderNoSelect = ref(null)
const customerSelect = ref(null)
let tomOrderNo = null
let tomCustomer = null

const customerOrders = ref([])

const fetchCustomerOrders = async (customerName) => {
    if (!customerName) {
        customerOrders.value = []
        return
    }

    try {
        const response = await axios.post('/tailoring/job-completion/search-orders', {
            customer_name: customerName
        })
        if (response.data.success) {
            customerOrders.value = response.data.data
        }
    } catch (error) {
        console.error('Failed to fetch customer orders:', error)
    }
}

const selectOrder = (orderNo) => {
    emit('update:orderNo', orderNo)
    nextTick(() => {
        emit('search')
    })
}

const formatDate = (dateString) => {
    if (!dateString) return '-'
    return new Date(dateString).toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    })
}

const getStatusClass = (status) => {
    switch (status?.toLowerCase()) {
        case 'completed': return 'bg-green-100 text-green-700'
        case 'in_progress': return 'bg-blue-100 text-blue-700'
        case 'delivered': return 'bg-purple-100 text-purple-700'
        default: return 'bg-gray-100 text-gray-700'
    }
}

const initTomSelect = () => {
    if (typeof window.TomSelect === 'undefined') return

    // Order No Select
    const orderOptions = props.orderNumbers.map(no => ({ value: no, text: no }))
    tomOrderNo = new window.TomSelect(orderNoSelect.value, {
        valueField: 'value',
        labelField: 'text',
        searchField: ['text'],
        options: orderOptions,
        create: true,
        placeholder: 'Enter or select order no...',
        maxItems: 1,
        plugins: ['clear_button'],
        onChange: (value) => {
            emit('update:orderNo', value)
        }
    })

    // Customer Select
    const customerOptions = props.customers.map(c => ({
        value: c.name,
        text: c.label,
        name: c.name,
        mobile: c.mobile
    }))
    tomCustomer = new window.TomSelect(customerSelect.value, {
        valueField: 'value',
        labelField: 'text',
        searchField: ['name', 'mobile'],
        options: customerOptions,
        create: false,
        placeholder: 'Search customer...',
        maxItems: 1,
        plugins: ['clear_button'],
        render: {
            option: function(data, escape) {
                return `<div class="py-2 px-3 border-b border-gray-50 hover:bg-blue-50 transition-colors">
                    <div class="font-bold text-gray-800">${escape(data.name)}</div>
                    <div class="text-xs text-blue-600 flex items-center gap-1">
                        <i class="fa fa-phone scale-75"></i> ${escape(data.mobile || 'No Contact')}
                    </div>
                </div>`;
            },
            item: function(data, escape) {
                return `<div>${escape(data.name)} <span class="text-gray-400 text-xs ml-1">(${escape(data.mobile || '-')})</span></div>`;
            }
        },
        onChange: (value) => {
            emit('update:customer', value)
            const selected = customerOptions.find(c => c.value === value)
            if (selected) {
                emit('update:contact', selected.mobile)
                fetchCustomerOrders(value)
            } else {
                customerOrders.value = []
            }
        }
    })

    // Set initial values
    if (props.orderNo) tomOrderNo.setValue(props.orderNo, true)
    if (props.customer) {
        tomCustomer.setValue(props.customer, true)
        fetchCustomerOrders(props.customer)
    }
}

const handleClear = () => {
    if (tomOrderNo) tomOrderNo.clear()
    if (tomCustomer) tomCustomer.clear()
    customerOrders.value = []
    emit('clear')
}

watch(() => props.orderNo, (val) => {
    if (tomOrderNo && val !== tomOrderNo.getValue()) {
        tomOrderNo.setValue(val, true)
    }
})

watch(() => props.customer, (val) => {
    if (tomCustomer && val !== tomCustomer.getValue()) {
        tomCustomer.setValue(val, true)
        fetchCustomerOrders(val)
    }
})

onMounted(() => {
    if (typeof window.TomSelect !== 'undefined') {
        initTomSelect()
    } else {
        const check = setInterval(() => {
            if (typeof window.TomSelect !== 'undefined') {
                clearInterval(check)
                initTomSelect()
            }
        }, 100)
    }
})

onBeforeUnmount(() => {
    if (tomOrderNo) tomOrderNo.destroy()
    if (tomCustomer) tomCustomer.destroy()
})
</script>

<style>
/* Custom styling for TomSelect to match our theme */
.ts-control {
    border-radius: 0.5rem !important;
    padding: 0.625rem 0.75rem 0.625rem 2.5rem !important;
    border-color: #d1d5db !important;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
    font-size: 0.875rem !important;
}

.ts-control:focus {
    box-shadow: 0 0 0 4px #eff6ff !important;
    border-color: #3b82f6 !important;
}

.ts-wrapper.single .ts-control {
    background-image: none !important;
}

.ts-dropdown {
    border-radius: 0.5rem !important;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important;
    border: 1px solid #f3f4f6 !important;
    margin-top: 5px !important;
}
</style>
