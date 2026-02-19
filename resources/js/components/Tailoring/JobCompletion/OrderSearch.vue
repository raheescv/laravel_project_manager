<template>
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-5 lg:gap-6 items-start">
        <!-- Search Order Form -->
        <div class="lg:col-span-4">
            <div class="search-card bg-white rounded-2xl shadow-sm border border-slate-200">
                <div class="p-5 sm:p-6">
                    <div class="flex items-center gap-3 mb-5 sm:mb-6">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600 ring-1 ring-blue-100">
                            <i class="fa fa-search text-lg"></i>
                        </div>
                        <div class="leading-tight">
                            <h5 class="text-sm font-extrabold text-slate-800 uppercase tracking-widest">Search Order</h5>
                            <p class="text-[11px] text-slate-500 font-medium mt-0.5">Find and load customer orders quickly</p>
                        </div>
                    </div>

                    <div class="flex flex-col gap-4 sm:gap-5">
                        <!-- Order Number -->
                        <div class="space-y-1.5">
                            <label class="block text-[0.65rem] font-bold text-slate-500 uppercase tracking-widest px-0.5">
                                Order Number
                            </label>
                            <SearchableSelect class="w-full" :modelValue="orderNo" :options="formattedOrderNumbers"
                                placeholder="Select Order No" filter-placeholder="Search order number..."
                                :visibleItems="8" @update:modelValue="val => $emit('update:orderNo', val)"
                                input-class="!w-full !text-xs !py-2 !px-3 !rounded-xl !bg-slate-50 !border-slate-200 !font-semibold !min-h-[40px] focus:!bg-white focus:!border-blue-300" />
                        </div>

                        <!-- Customer Selection -->
                        <div class="space-y-1.5">
                            <label class="block text-[0.65rem] font-bold text-slate-500 uppercase tracking-widest px-0.5">Customer Selection</label>
                            <SearchableSelect class="w-full" :modelValue="customerId" :options="formattedCustomers"
                                :loading="customerLoading" placeholder="Select Customer"
                                filter-placeholder="Search by name or mobile..." :visibleItems="8"
                                @search="searchCustomers" @change="handleCustomerSelect" @open="handleCustomerOpen"
                                @update:modelValue="val => $emit('update:customerId', val)"
                                input-class="!w-full !text-xs !py-2 !px-3 !rounded-xl !bg-slate-50 !border-slate-200 !font-semibold !min-h-[40px] focus:!bg-white focus:!border-blue-300" />
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:flex-wrap sm:justify-end gap-2 mt-7 pt-4 border-t border-slate-100">
                        <button @click="handleClear"
                            class="w-full sm:w-auto justify-center px-4 py-2 rounded-xl border border-slate-200 bg-white text-slate-600 hover:text-slate-800 hover:border-slate-300 font-bold text-[11px] uppercase tracking-widest transition-all flex items-center gap-2">
                            <i class="fa fa-refresh"></i> Clear
                        </button>
                        <button @click="$emit('search')"
                            class="w-full sm:w-auto justify-center px-5 py-2 rounded-xl bg-blue-600 text-white font-bold text-[11px] uppercase tracking-widest shadow-md shadow-blue-600/20 hover:bg-blue-700 transition-all flex items-center gap-2">
                            <i class="fa fa-search"></i> Search Order
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="lg:col-span-8">
            <div v-if="customerOrders.length > 0" class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-5 sm:p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center text-blue-600 ring-1 ring-blue-100">
                            <i class="fa fa-list text-sm"></i>
                        </div>
                        <h6 class="text-sm font-extrabold text-slate-800 uppercase tracking-widest leading-none">
                            Recent Orders for <span class="text-blue-600">{{ customer }}</span>
                        </h6>
                    </div>

                    <div class="hidden md:block overflow-x-auto rounded-xl border border-slate-200">
                        <table class="w-full text-left border-collapse whitespace-nowrap">
                            <thead>
                                <tr class="bg-slate-50/90 border-b border-slate-200">
                                    <th class="px-4 py-3 text-[0.6rem] font-bold text-slate-500 uppercase tracking-widest">Order No</th>
                                    <th class="px-4 py-3 text-[0.6rem] font-bold text-slate-500 uppercase tracking-widest">Order Date</th>
                                    <th class="px-4 py-3 text-[0.6rem] font-bold text-slate-500 uppercase tracking-widest">Delivery</th>
                                    <th class="px-4 py-3 text-[0.6rem] font-bold text-slate-500 uppercase tracking-widest">Status</th>
                                    <th class="px-4 py-3 text-[0.6rem] font-bold text-slate-500 uppercase tracking-widest text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <tr v-for="order in customerOrders" :key="order.id" class="hover:bg-blue-50/40 transition-colors group">
                                    <td class="px-4 py-3.5 text-xs font-extrabold text-blue-600">{{ order.order_no }}</td>
                                    <td class="px-4 py-3.5 text-[0.68rem] font-semibold text-slate-600">{{ formatDate(order.order_date) }}</td>
                                    <td class="px-4 py-3.5 text-[0.68rem] font-semibold text-slate-600">{{ formatDate(order.delivery_date) }}</td>
                                    <td class="px-4 py-3">
                                        <span :class="getStatusBadgeClass(order.status)"
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-[0.55rem] font-black uppercase tracking-tighter border">
                                            {{ order.status || 'Pending' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3.5 text-right">
                                        <button @click="selectOrder(order.order_no)"
                                            class="text-blue-600 hover:text-blue-700 font-extrabold text-[0.65rem] uppercase flex items-center gap-1 ml-auto transition-colors">
                                            Select <i class="fa fa-chevron-right text-[8px]"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="md:hidden space-y-3">
                        <div
                            v-for="order in customerOrders"
                            :key="`mobile-${order.id}`"
                            class="rounded-xl border border-slate-200 p-3 bg-white"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-[10px] uppercase tracking-widest font-bold text-slate-400">Order No</p>
                                    <p class="text-sm font-extrabold text-blue-600">{{ order.order_no }}</p>
                                </div>
                                <span :class="getStatusBadgeClass(order.status)"
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-[0.55rem] font-black uppercase tracking-tighter border">
                                    {{ order.status || 'Pending' }}
                                </span>
                            </div>
                            <div class="grid grid-cols-2 gap-2 mt-3">
                                <div>
                                    <p class="text-[10px] uppercase tracking-widest font-bold text-slate-400">Order Date</p>
                                    <p class="text-[11px] font-semibold text-slate-600">{{ formatDate(order.order_date) }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] uppercase tracking-widest font-bold text-slate-400">Delivery</p>
                                    <p class="text-[11px] font-semibold text-slate-600">{{ formatDate(order.delivery_date) }}</p>
                                </div>
                            </div>
                            <button
                                @click="selectOrder(order.order_no)"
                                class="mt-3 w-full rounded-lg border border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100 font-extrabold text-[11px] uppercase tracking-widest py-2 transition-colors"
                            >
                                Select Order
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div v-else class="bg-white rounded-2xl shadow-sm border border-slate-200 min-h-[280px] flex flex-col items-center justify-center p-8 sm:p-10 text-center">
                <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 ring-1 ring-blue-100 flex items-center justify-center mb-3">
                    <i class="fa fa-info-circle text-xl"></i>
                </div>
                <p class="text-[0.7rem] font-extrabold uppercase tracking-widest text-slate-500">Select a customer to view recent orders</p>
                <p class="text-xs text-slate-400 mt-2 max-w-xs">Use customer search on the left to load and pick a previous order.</p>
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
        case 'completed': return 'bg-success text-white'
        case 'in_progress': return 'bg-primary text-white'
        case 'delivered': return 'bg-info text-dark'
        default: return 'bg-secondary text-white'
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

<style scoped>
.search-card {
    background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
}

/* Keep dropdown styling consistent with the refreshed panel */
:deep(.ts-control) {
    border-radius: 0.75rem !important;
    border-color: #dbe2ea !important;
    box-shadow: 0 1px 2px 0 rgba(15, 23, 42, 0.05) !important;
    font-size: 0.75rem !important;
}

:deep(.ts-control:focus) {
    box-shadow: 0 0 0 3px #e0ecff !important;
    border-color: #4f8ff8 !important;
}

:deep(.ts-wrapper.single .ts-control) {
    background-image: none !important;
}

:deep(.ts-dropdown) {
    border-radius: 0.75rem !important;
    box-shadow: 0 14px 24px -10px rgba(15, 23, 42, 0.22) !important;
    border: 1px solid #e2e8f0 !important;
    margin-top: 6px !important;
}
</style>
