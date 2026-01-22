<template>
    <div class="row g-4">
        <!-- Search Order Form -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body p-4">
                    <h5 class="fw-bold text-dark mb-4 d-flex align-items-center gap-2">
                        <i class="fa fa-search text-primary"></i>
                        Search Order
                    </h5>

                    <div class="d-flex flex-column gap-3">
                        <!-- Order Number -->
                        <div>
                            <label class="form-label x-small fw-bold text-muted text-uppercase mb-1">
                                Order Number
                            </label>
                            <SearchableSelect :modelValue="orderNo" :options="formattedOrderNumbers"
                                placeholder="Select Order No" filter-placeholder="Search order number..."
                                :visibleItems="8" @update:modelValue="val => $emit('update:orderNo', val)"
                                input-class="w-full rounded-lg border-2 border-purple-200/60 shadow-md focus:border-purple-500 focus:ring-2 focus:ring-purple-500/30 transition-all duration-200 bg-white/95 backdrop-blur-sm hover:shadow-lg hover:border-purple-300 text-sm sm:text-sm py-2 sm:py-2 px-3 min-h-[40px] sm:min-h-[36px] font-medium" />
                        </div>

                        <!-- Customer Selection -->
                        <div>
                            <label class="form-label x-small fw-bold text-muted text-uppercase mb-1">Customer.</label>
                            <SearchableSelect :modelValue="customerId" :options="formattedCustomers"
                                :loading="customerLoading" placeholder="Select Customer"
                                filter-placeholder="Search by name or mobile..." :visibleItems="8"
                                @search="searchCustomers" @change="handleCustomerSelect" @open="handleCustomerOpen"
                                @update:modelValue="val => $emit('update:customerId', val)"
                                input-class="w-full rounded-lg border-2 border-indigo-200/60 shadow-md focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 transition-all duration-200 bg-white/95 backdrop-blur-sm hover:shadow-lg hover:border-indigo-300 text-sm sm:text-sm py-2 sm:py-2 px-3 min-h-[40px] sm:min-h-[36px] font-medium" />
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <button @click="handleClear" class="btn btn-outline-secondary btn-sm px-3 fw-bold">
                            <i class="fa fa-refresh me-1"></i> Clear
                        </button>
                        <button @click="$emit('search')" class="btn btn-primary btn-sm px-4 fw-bold shadow-sm">
                            <i class="fa fa-search me-1"></i> Search Order
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="col-lg-8">
            <div v-if="customerOrders.length > 0" class="card shadow-sm border-0 h-100">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                        <i class="fa fa-list text-primary"></i>
                        Recent Orders for <span class="text-primary">{{ customer }}</span>
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle">
                            <thead class="table-light">
                                <tr class="x-small fw-bold text-uppercase text-muted">
                                    <th class="py-2">Order No</th>
                                    <th class="py-2">Order Date</th>
                                    <th class="py-2">Delivery</th>
                                    <th class="py-2">Status</th>
                                    <th class="py-2 text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="order in customerOrders" :key="order.id">
                                    <td class="fw-bold text-primary">{{ order.order_no }}</td>
                                    <td class="small text-muted">{{ formatDate(order.order_date) }}</td>
                                    <td class="small text-muted">{{ formatDate(order.delivery_date) }}</td>
                                    <td>
                                        <span :class="getStatusBadgeClass(order.completion_status)"
                                            class="badge rounded-pill x-small fw-bold">
                                            {{ order.completion_status || 'Pending' }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <button @click="selectOrder(order.order_no)"
                                            class="btn btn-link btn-sm text-primary fw-bold text-decoration-none p-0">
                                            Select <i class="fa fa-chevron-right ms-1"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div v-else class="card shadow-sm border-2 border-dashed h-100 text-center text-muted">
                <div class="card-body d-flex flex-column justify-content-center py-5">
                    <i class="fa fa-info-circle fs-3 mb-2 text-primary opacity-50"></i>
                    <p class="small fw-bold mb-0">Select a customer to view recent orders</p>
                </div>
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
