<template>
    <div class="bg-white rounded-xl shadow-2xl border border-slate-200 overflow-hidden">
        <div class="px-3 py-3">
            <h6 class="text-xs font-bold text-slate-800 mb-1 flex items-center gap-1">
                <i class="fa fa-info-circle text-blue-500 text-xs"></i>
                <span>Order Primary Information</span>
            </h6>
            <div class="bg-gradient-to-r from-slate-50 to-blue-50 border border-slate-200 rounded-lg p-1.5">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-3">
                <!-- Order No -->
                <div class="lg:col-span-2">
                    <label class="block text-slate-500 font-bold text-[0.7rem] uppercase tracking-widest mb-1 px-1">Order No</label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <i class="fa fa-hashtag text-[10px] text-indigo-500/70"></i>
                        </div>
                        <input :value="orderNo" @input="$emit('update:orderNo', $event.target.value)" type="text" readonly
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 text-slate-500 text-sm py-1.5 pl-8 pr-4 font-bold cursor-not-allowed" />
                    </div>
                </div>

                <!-- Customer -->
                <div class="lg:col-span-3">
                    <div class="flex items-center justify-between mb-1 px-1">
                        <label class="block text-slate-500 font-bold text-[0.7rem] uppercase tracking-widest">Customer</label>
                        <button @click.prevent="$emit('add-customer')" class="text-indigo-600 hover:text-indigo-700 font-bold text-[0.7rem] uppercase tracking-widest flex items-center gap-1 transition-colors">
                            <i class="fa fa-plus-circle"></i>
                            New
                        </button>
                    </div>
                    <SearchableSelect :modelValue="customerId" :options="formattedCustomers"
                        :loading="customerLoading" placeholder="Select Customer"
                        filter-placeholder="Search by name or mobile..." :visibleItems="8" @search="searchCustomers"
                        @change="handleCustomerChange" @open="handleCustomerOpen"
                        @update:modelValue="id => $emit('update:customerId', id)"
                        input-class="w-full rounded-xl border border-slate-200 bg-white shadow-sm focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all duration-300 text-sm py-1.5 px-4 font-medium" />
                </div>

                <!-- Contact -->
                <div class="md:col-span-1 lg:col-span-2">
                    <label class="block text-slate-500 font-bold text-[0.7rem] uppercase tracking-widest mb-1 px-1">Contact</label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-emerald-500 transition-colors">
                            <i class="fa fa-phone text-xs"></i>
                        </div>
                        <input :value="contact" @input="$emit('update:contact', $event.target.value)" type="tel"
                            placeholder="Enter contact..." 
                            class="w-full rounded-xl border border-slate-200 bg-white shadow-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition-all duration-300 text-sm py-1.5 pl-9 pr-4 font-medium placeholder:text-slate-300" />
                    </div>
                </div>

                <!-- Salesman -->
                <div class="md:col-span-1 lg:col-span-2">
                    <label class="block text-slate-500 font-bold text-[0.7rem] uppercase tracking-widest mb-1 px-1">Salesman</label>
                    <SearchableSelect :modelValue="salesman" :options="salesmen" placeholder="Select Salesman"
                        @update:modelValue="$emit('update:salesman', $event)" 
                        input-class="w-full rounded-xl border border-slate-200 bg-white shadow-sm focus:border-purple-500 focus:ring-4 focus:ring-purple-500/10 transition-all duration-300 text-sm py-1.5 px-4 font-medium" />
                </div>

                <!-- Dates Group -->
                <div class="lg:col-span-3">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-slate-500 font-bold text-[0.7rem] uppercase tracking-widest mb-1 px-1">Order Date</label>
                            <input :value="orderDate" @input="$emit('update:orderDate', $event.target.value)"
                                type="date" 
                                class="w-full rounded-xl border border-slate-200 bg-white shadow-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all duration-300 text-sm py-1.5 px-3 font-medium cursor-pointer" />
                        </div>
                        <div>
                            <label class="block text-slate-500 font-bold text-[0.7rem] uppercase tracking-widest mb-1 px-1">Delivery</label>
                            <input :value="deliveryDate" @input="$emit('update:deliveryDate', $event.target.value)"
                                type="date" 
                                class="w-full rounded-xl border border-slate-200 bg-white shadow-sm focus:border-rose-500 focus:ring-4 focus:ring-rose-500/10 transition-all duration-300 text-sm py-1.5 px-3 font-medium cursor-pointer" />
                        </div>
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
