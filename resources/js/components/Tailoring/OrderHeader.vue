<template>
    <div class="card mb-3 border shadow-none" style="border-color: #e2e8f0 !important;">
        <div class="card-body">
            <h5 class="card-title text-gray-800 fw-bold mb-4">Order Information</h5>
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label small text-muted">Order No</label>
                    <input :value="orderNo" @input="$emit('update:orderNo', $event.target.value)" type="text" readonly
                        class="w-full rounded-lg border-2 border-slate-200 bg-slate-50 text-slate-500 text-sm py-2 px-3 min-h-[40px] sm:min-h-[36px] font-medium" />
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">Customer</label>
                    <SearchableSelect :modelValue="customerId" :options="formattedCustomers"
                        :loading="customerLoading" placeholder="Select Customer"
                        filter-placeholder="Search by name or mobile..." :visibleItems="8" @search="searchCustomers"
                        @change="handleCustomerChange" @open="handleCustomerOpen"
                        @update:modelValue="id => $emit('update:customerId', id)"
                        input-class="w-full rounded-lg border-2 border-indigo-200/60 shadow-md focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 transition-all duration-200 bg-white/95 backdrop-blur-sm hover:shadow-lg hover:border-indigo-300 text-sm sm:text-sm py-2 sm:py-2 px-3 min-h-[40px] sm:min-h-[36px] font-medium" />

                    <small class="mt-1 d-block">
                        <a @click.prevent="$emit('add-customer')" href="#"
                            class="text-decoration-none text-primary fw-bold small">
                            <i class="fa fa-plus-circle me-1"></i> Add New Customer
                        </a>
                    </small>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Contact</label>
                    <input :value="contact" @input="$emit('update:contact', $event.target.value)" type="tel"
                        placeholder="Enter contact..." 
                        class="w-full rounded-lg border-2 border-emerald-200/60 shadow-md focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/30 transition-all duration-200 bg-white/95 backdrop-blur-sm hover:shadow-lg hover:border-emerald-300 text-sm sm:text-sm py-2 sm:py-2 px-3 min-h-[40px] sm:min-h-[36px] font-medium placeholder:text-slate-400" />
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Salesman</label>
                    <SearchableSelect :modelValue="salesman" :options="salesmen" placeholder="Select Salesman"
                        @update:modelValue="$emit('update:salesman', $event)" 
                        input-class="w-full rounded-lg border-2 border-purple-200/60 shadow-md focus:border-purple-500 focus:ring-2 focus:ring-purple-500/30 transition-all duration-200 bg-white/95 backdrop-blur-sm hover:shadow-lg hover:border-purple-300 text-sm sm:text-sm py-2 sm:py-2 px-3 min-h-[40px] sm:min-h-[36px] font-medium" />
                </div>
                <div class="col-md-3">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label small text-muted">Order Date</label>
                            <input :value="orderDate" @input="$emit('update:orderDate', $event.target.value)"
                                type="date" 
                                class="w-full rounded-lg border-2 border-blue-200/60 shadow-md focus:border-blue-500 focus:ring-2 focus:ring-blue-500/30 transition-all duration-200 bg-white/95 backdrop-blur-sm hover:shadow-lg hover:border-blue-300 text-sm sm:text-sm py-2 sm:py-2 px-3 min-h-[40px] sm:min-h-[36px] font-medium" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted">Delivery</label>
                            <input :value="deliveryDate" @input="$emit('update:deliveryDate', $event.target.value)"
                                type="date" 
                                class="w-full rounded-lg border-2 border-rose-200/60 shadow-md focus:border-rose-500 focus:ring-2 focus:ring-rose-500/30 transition-all duration-200 bg-white/95 backdrop-blur-sm hover:shadow-lg hover:border-rose-300 text-sm sm:text-sm py-2 sm:py-2 px-3 min-h-[40px] sm:min-h-[36px] font-medium" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, watch, computed } from 'vue'
import axios from 'axios'
import SearchableSelect from '@/components/SearchableSelectFixed.vue'
import debounce from 'lodash/debounce'

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
    'search-customer',
    'update:customerLoading'
])

const serverCustomers = ref({
    ...props.customers || {}
})

const formattedCustomers = computed(() => {
    return Object.entries(serverCustomers.value).map(([id, customer]) => {
        const name = customer.name || (typeof customer === 'string' ? customer : 'Unknown')
        const mobile = customer.mobile || customer.phone || ''
        return {
            value: parseInt(id),
            label: `${name} ${mobile ? ' - ' + mobile : ''}`,
            name: name,
            mobile: mobile
        }
    })
})

const searchCustomers = debounce(async (query) => {
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
    if (Object.keys(serverCustomers.value).length <= 1) { // If only 0 or 1 (the initial one), load more
        searchCustomers('')
    }
}

const handleCustomerChange = (id) => {
    const selected = serverCustomers.value[id]
    if (selected) {
        emit('update:customer', selected.name);
        emit('update:contact', selected.mobile || selected.phone || '');
        emit('customer-selected', selected);
    } else {
        emit('update:customer', '');
        emit('update:contact', '');
        emit('customer-selected', null);
    }
}

// Watch initial customer to populate serverCustomers
watch(() => props.customerId, (newId) => {
    if (newId && !serverCustomers.value[newId]) {
        if (props.customer) {
            serverCustomers.value[newId] = {
                id: newId,
                name: props.customer,
                mobile: props.contact
            }
        }
    }
}, { immediate: true })

watch(() => props.customers, (newVal) => {
    serverCustomers.value = { ...serverCustomers.value, ...newVal }
}, { deep: true })
</script>
