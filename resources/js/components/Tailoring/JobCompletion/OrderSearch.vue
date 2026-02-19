<template>
    <div class="grid grid-cols-1 md:grid-cols-12 gap-2.5 sm:gap-3 items-start">
        <!-- Search Order Form -->
        <div class="md:col-span-5 lg:col-span-4">
            <div class="search-card bg-white rounded-xl shadow-sm border border-slate-200/90">
                <div class="p-2.5 sm:p-3">
                    <div class="flex items-center gap-2 mb-3 sm:mb-4">
                        <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center text-blue-600 ring-1 ring-blue-100 shrink-0">
                            <i class="fa fa-search text-sm"></i>
                        </div>
                        <div class="leading-tight min-w-0">
                            <h5 class="text-xs font-extrabold text-slate-800 uppercase tracking-widest">Search Order</h5>
                            <p class="text-[10px] text-slate-500 font-medium mt-0.5 truncate">Find and load customer orders quickly</p>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3">
                        <!-- Order Number -->
                        <div class="space-y-1">
                            <label class="flex items-center gap-1.5 text-[0.65rem] font-bold text-slate-500 uppercase tracking-widest px-0.5">
                                <i class="fa fa-sort-numeric-asc text-slate-400"></i>
                                <span>Order Number</span>
                            </label>
                            <SearchableSelect class="w-full min-w-0" :modelValue="orderNo" :options="formattedOrderNumbers"
                                placeholder="Select Order No" filter-placeholder="Search order number..."
                                :visibleItems="8" @update:modelValue="val => $emit('update:orderNo', val)"
                                input-class="!w-full !text-xs !py-1.5 !px-2.5 !rounded-lg !bg-slate-50 !border-slate-200 !font-semibold !min-h-[36px] focus:!bg-white focus:!border-blue-300" />
                        </div>

                        <!-- Customer Selection -->
                        <div class="space-y-1">
                            <label class="flex items-center gap-1.5 text-[0.65rem] font-bold text-slate-500 uppercase tracking-widest px-0.5">
                                <i class="fa fa-user text-slate-400"></i>
                                <span>Customer Selection</span>
                            </label>
                            <SearchableSelect class="w-full min-w-0" :modelValue="customerId" :options="formattedCustomers"
                                :loading="customerLoading" placeholder="Select Customer"
                                filter-placeholder="Search by name or mobile..." :visibleItems="8"
                                @search="searchCustomers" @change="handleCustomerSelect" @open="handleCustomerOpen"
                                @update:modelValue="val => $emit('update:customerId', val)"
                                input-class="!w-full !text-xs !py-1.5 !px-2.5 !rounded-lg !bg-slate-50 !border-slate-200 !font-semibold !min-h-[36px] focus:!bg-white focus:!border-blue-300" />
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:flex-wrap sm:justify-end gap-1.5 mt-3 pt-2.5 border-t border-slate-100">
                        <button @click="handleClear"
                            class="w-full sm:w-auto justify-center px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-600 hover:text-slate-800 hover:border-slate-300 font-bold text-[10px] uppercase tracking-widest transition-all flex items-center gap-1.5">
                            <i class="fa fa-refresh"></i> Clear
                        </button>
                        <button @click="$emit('search')"
                            class="w-full sm:w-auto justify-center px-4 py-1.5 rounded-lg bg-blue-600 text-white font-bold text-[10px] uppercase tracking-widest shadow-md shadow-blue-600/20 hover:bg-blue-700 transition-all flex items-center gap-1.5">
                            <i class="fa fa-search"></i> Search Order
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="md:col-span-7 lg:col-span-8 min-w-0">
            <div v-if="customerOrders.length > 0" class="bg-white rounded-xl shadow-sm border border-slate-200/90 overflow-hidden">
                <div class="p-2.5 sm:p-3">
                    <div class="flex items-center gap-2 mb-3">
                        <div class="w-7 h-7 rounded-lg bg-blue-50 flex items-center justify-center text-blue-600 ring-1 ring-blue-100 shrink-0">
                            <i class="fa fa-list text-xs"></i>
                        </div>
                        <h6 class="text-xs font-extrabold text-slate-800 uppercase tracking-widest leading-none truncate">
                            Recent Orders for <span class="text-blue-600">{{ customer }}</span>
                        </h6>
                    </div>

                    <div class="hidden md:block overflow-x-auto rounded-lg border border-slate-200">
                        <table class="w-full text-left border-collapse whitespace-nowrap min-w-[420px]">
                            <thead>
                                <tr class="bg-slate-50/90 border-b border-slate-200">
                                    <th class="px-2.5 py-2 text-[0.6rem] font-bold text-slate-500 uppercase tracking-widest">Order No</th>
                                    <th class="px-2.5 py-2 text-[0.6rem] font-bold text-slate-500 uppercase tracking-widest">Order Date</th>
                                    <th class="px-2.5 py-2 text-[0.6rem] font-bold text-slate-500 uppercase tracking-widest">Delivery</th>
                                    <th class="px-2.5 py-2 text-[0.6rem] font-bold text-slate-500 uppercase tracking-widest">Status</th>
                                    <th class="px-2.5 py-2 text-[0.6rem] font-bold text-slate-500 uppercase tracking-widest text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <tr v-for="order in customerOrders" :key="order.id" class="hover:bg-blue-50/40 transition-colors group">
                                    <td class="px-2.5 py-2 text-xs font-extrabold text-blue-600">{{ order.order_no }}</td>
                                    <td class="px-2.5 py-2 text-[0.65rem] font-semibold text-slate-600">{{ formatDate(order.order_date) }}</td>
                                    <td class="px-2.5 py-2 text-[0.65rem] font-semibold text-slate-600">{{ formatDate(order.delivery_date) }}</td>
                                    <td class="px-2.5 py-2">
                                        <span :class="getStatusBadgeClass(order.status)"
                                            class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[0.55rem] font-black uppercase tracking-tighter border">
                                            {{ order.status || 'Pending' }}
                                        </span>
                                    </td>
                                    <td class="px-2.5 py-2 text-right">
                                        <button @click="selectOrder(order.order_no)"
                                            class="text-blue-600 hover:text-blue-700 font-extrabold text-[0.65rem] uppercase flex items-center gap-1 ml-auto transition-colors">
                                            Select <i class="fa fa-chevron-right text-[8px]"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="md:hidden space-y-1.5">
                        <div
                            v-for="order in customerOrders"
                            :key="`mobile-${order.id}`"
                            class="rounded-lg border border-slate-200 p-2 bg-white"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="text-[10px] uppercase tracking-widest font-bold text-slate-400">Order No</p>
                                    <p class="text-xs font-extrabold text-blue-600 truncate">{{ order.order_no }}</p>
                                </div>
                                <span :class="getStatusBadgeClass(order.status)"
                                    class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[0.55rem] font-black uppercase tracking-tighter border shrink-0">
                                    {{ order.status || 'Pending' }}
                                </span>
                            </div>
                            <div class="grid grid-cols-2 gap-2 mt-2">
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
                                class="mt-2 w-full rounded-lg border border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100 font-extrabold text-[10px] uppercase tracking-widest py-1.5 transition-colors"
                            >
                                Select Order
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div v-else class="bg-white rounded-xl shadow-sm border border-slate-200/90 min-h-[180px] flex flex-col items-center justify-center p-3 sm:p-5 text-center">
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 ring-1 ring-blue-100 flex items-center justify-center mb-2 shrink-0">
                    <i class="fa fa-info-circle text-lg"></i>
                </div>
                <p class="text-[0.65rem] font-extrabold uppercase tracking-widest text-slate-500">Select a customer to view recent orders</p>
                <p class="text-[11px] text-slate-400 mt-1.5 max-w-xs">Use customer search on the left to load and pick a previous order.</p>
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

/* Keep dropdown styling consistent with compact panel */
:deep(.ts-control) {
    border-radius: 0.5rem !important;
    border-color: #dbe2ea !important;
    box-shadow: 0 1px 2px 0 rgba(15, 23, 42, 0.05) !important;
    font-size: 0.75rem !important;
}

:deep(.ts-control:focus) {
    box-shadow: 0 0 0 2px #e0ecff !important;
    border-color: #4f8ff8 !important;
}

:deep(.ts-wrapper.single .ts-control) {
    background-image: none !important;
}

:deep(.ts-dropdown) {
    border-radius: 0.5rem !important;
    box-shadow: 0 10px 20px -8px rgba(15, 23, 42, 0.18) !important;
    border: 1px solid #e2e8f0 !important;
    margin-top: 4px !important;
}
</style>
