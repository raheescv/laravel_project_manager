<template>
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Search Order Form -->
        <div class="lg:col-span-4">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 h-full">
                <div class="p-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600">
                            <i class="fa fa-search text-lg"></i>
                        </div>
                        <h5 class="text-sm font-black text-slate-800 uppercase tracking-widest leading-none">Search Order</h5>
                    </div>

                    <div class="flex flex-col gap-4">
                        <!-- Order Number -->
                        <div>
                            <label class="block text-[0.65rem] font-bold text-slate-400 uppercase tracking-widest mb-1.5 px-1">
                                Order Number
                            </label>
                            <SearchableSelect :modelValue="orderNo" :options="formattedOrderNumbers"
                                placeholder="Select Order No" filter-placeholder="Search order number..."
                                :visibleItems="8" @update:modelValue="val => $emit('update:orderNo', val)"
                                input-class="!text-xs !py-1 !px-3 !rounded-xl !bg-slate-50 !border-slate-200 !font-bold" />
                        </div>

                        <!-- Customer Selection -->
                        <div>
                            <label class="block text-[0.65rem] font-bold text-slate-400 uppercase tracking-widest mb-1.5 px-1">Customer Selection</label>
                            <SearchableSelect :modelValue="customerId" :options="formattedCustomers"
                                :loading="customerLoading" placeholder="Select Customer"
                                filter-placeholder="Search by name or mobile..." :visibleItems="8"
                                @search="searchCustomers" @change="handleCustomerSelect" @open="handleCustomerOpen"
                                @update:modelValue="val => $emit('update:customerId', val)"
                                input-class="!text-xs !py-1 !px-3 !rounded-xl !bg-slate-50 !border-slate-200 !font-bold" />
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 mt-8 pt-4 border-t border-slate-100">
                        <button @click="handleClear" 
                            class="px-4 py-1.5 rounded-xl text-slate-400 hover:text-slate-600 font-bold text-xs uppercase tracking-widest transition-colors flex items-center gap-2">
                            <i class="fa fa-refresh"></i> Clear
                        </button>
                        <button @click="$emit('search')" 
                            class="px-5 py-1.5 rounded-xl bg-blue-600 text-white font-bold text-xs uppercase tracking-widest shadow-lg shadow-blue-600/20 hover:bg-blue-700 transition-all flex items-center gap-2">
                            <i class="fa fa-search"></i> Search Order
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="lg:col-span-8">
            <div v-if="customerOrders.length > 0" class="bg-white rounded-2xl shadow-sm border border-slate-200 h-full overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center text-blue-600">
                            <i class="fa fa-list text-sm"></i>
                        </div>
                        <h6 class="text-sm font-black text-slate-800 uppercase tracking-widest leading-none">
                            Recent Orders for <span class="text-blue-600">{{ customer }}</span>
                        </h6>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse whitespace-nowrap">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-100">
                                    <th class="px-4 py-3 text-[0.6rem] font-bold text-slate-400 uppercase tracking-widest">Order No</th>
                                    <th class="px-4 py-3 text-[0.6rem] font-bold text-slate-400 uppercase tracking-widest">Order Date</th>
                                    <th class="px-4 py-3 text-[0.6rem] font-bold text-slate-400 uppercase tracking-widest">Delivery</th>
                                    <th class="px-4 py-3 text-[0.6rem] font-bold text-slate-400 uppercase tracking-widest">Status</th>
                                    <th class="px-4 py-3 text-[0.6rem] font-bold text-slate-400 uppercase tracking-widest text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <tr v-for="order in customerOrders" :key="order.id" class="hover:bg-slate-50/50 transition-colors group">
                                    <td class="px-4 py-3 text-xs font-black text-blue-600">{{ order.order_no }}</td>
                                    <td class="px-4 py-3 text-[0.65rem] font-bold text-slate-500">{{ formatDate(order.order_date) }}</td>
                                    <td class="px-4 py-3 text-[0.65rem] font-bold text-slate-500">{{ formatDate(order.delivery_date) }}</td>
                                    <td class="px-4 py-3">
                                        <span :class="getStatusBadgeClass(order.completion_status)"
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-[0.55rem] font-black uppercase tracking-tighter border">
                                            {{ order.completion_status || 'Pending' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <button @click="selectOrder(order.order_no)"
                                            class="text-blue-600 hover:text-blue-700 font-black text-[0.65rem] uppercase flex items-center gap-1 ml-auto transition-colors">
                                            Select <i class="fa fa-chevron-right text-[8px]"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div v-else class="bg-white rounded-2xl shadow-sm border-2 border-dashed border-slate-200 h-full flex flex-col items-center justify-center p-8 text-center text-slate-400">
                <i class="fa fa-info-circle text-3xl mb-3 opacity-20 text-blue-500"></i>
                <p class="text-[0.65rem] font-bold uppercase tracking-widest">Select a customer to view recent orders</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, watch, nextTick, computed } from 'vue'
import axios from 'axios'
import SearchableSelect from '@/components/SearchableSelectFixed.vue'
import debounce from 'lodash/debounce'

const props = defineProps({
    orderNo: String,
    customer: String,
    customerId: [String, Number],
    contact: String,
    customers: {
        type: Array,
        default: () => []
    },
    orderNumbers: {
        type: Array,
        default: () => []
    },
    customerLoading: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['update:orderNo', 'update:customer', 'update:customerId', 'update:contact', 'update:customerLoading', 'search', 'clear', 'search-customer'])

const serverCustomers = ref({})

const formattedOrderNumbers = computed(() => {
    return (props.orderNumbers || []).map(no => ({
        value: no,
        label: no
    }))
})

const formattedCustomers = computed(() => {
    const customers = Object.values(serverCustomers.value)
    if (customers.length === 0 && props.customers?.length > 0) {
        return props.customers.map(c => ({
            value: c.id,
            label: `${c.name} ${c.mobile ? ' - ' + c.mobile : ''}`,
            name: c.name,
            mobile: c.mobile
        }))
    }
    return customers.map(c => ({
        value: c.id,
        label: `${c.name} ${c.mobile ? ' - ' + c.mobile : ''}`,
        name: c.name,
        mobile: c.mobile
    }))
})

const searchCustomers = debounce(async (query) => {
    // If query is empty and we already have some customers, don't necessarily need to fetch
    // But if query is empty and we want to "load all", we should proceed
    const isInitial = !query || query.length === 0;
    if (query && query.length < 2 && !isInitial) return

    emit('update:customerLoading', true)
    try {
        const response = await axios.get(`/account/list?query=${encodeURIComponent(query || '')}&model=customer`)
        if (response.data?.items) {
            const newCustomers = { ...serverCustomers.value }
            response.data.items.forEach(c => {
                newCustomers[c.id] = c
            })
            serverCustomers.value = newCustomers
        }
    } catch (error) {
        console.error('Failed to search customers', error)
    } finally {
        emit('update:customerLoading', false)
    }
}, 300)

const handleCustomerOpen = () => {
    if (Object.keys(serverCustomers.value).length === 0) {
        searchCustomers('')
    }
}

const handleCustomerSelect = (id) => {
    const selected = serverCustomers.value[id] ||
        (Array.isArray(props.customers) ? props.customers.find(c => c.id === id) : null)

    emit('update:customerId', id)
    if (selected) {
        emit('update:customer', selected.name)
        emit('update:contact', selected.mobile || selected.phone || '')
        fetchCustomerOrders(selected.name, id)
    } else {
        emit('update:customer', '')
        emit('update:contact', '')
        customerOrders.value = []
    }
}

const customerOrders = ref([])

const fetchCustomerOrders = async (customerName, accountId = null) => {
    if (!customerName && !accountId) {
        customerOrders.value = []
        return
    }

    try {
        const response = await axios.post('/tailoring/job-completion/search-orders', {
            account_id: accountId
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

const getStatusBadgeClass = (status) => {
    switch (status?.toLowerCase()) {
        case 'completed': return 'bg-success'
        case 'in_progress': return 'bg-primary'
        case 'delivered': return 'bg-info text-dark'
        default: return 'bg-secondary'
    }
}

// Remote TomSelect code removed, replaced by SearchableSelect

const handleClear = () => {
    customerOrders.value = []
    emit('clear')
}

watch(() => props.customers, (newVal) => {
    if (newVal?.length > 0) {
        const newCustomers = { ...serverCustomers.value }
        newVal.forEach(c => {
            newCustomers[c.id || Math.random()] = c
        })
        serverCustomers.value = newCustomers
    }
}, { immediate: true })

onBeforeUnmount(() => {
    // Cleanup
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
