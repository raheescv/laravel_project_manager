<template>
    <div class="bg-slate-50 min-vh-100 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-12">
            <!-- Page Header -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-8 transition-all duration-300 hover:shadow-md">
                <div class="px-8 py-6 flex flex-col md:flex-row justify-between items-center gap-4">
                    <div class="text-center md:text-left">
                        <h1 class="text-3xl font-black text-slate-900 tracking-tight mb-1">Job Completion</h1>
                        <p class="text-slate-500 font-medium">Track and complete tailoring orders efficiently</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="/tailoring/order" class="group flex items-center gap-2 px-5 py-2.5 rounded-xl border-2 border-slate-100 bg-white text-slate-600 font-bold text-sm transition-all duration-300 hover:border-blue-500 hover:text-blue-600 hover:shadow-lg hover:shadow-blue-500/10">
                            <i class="fa fa-list group-hover:rotate-12 transition-transform"></i>
                            <span>Orders List</span>
                        </a>
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-8">
                <!-- Order Search -->
                <OrderSearch v-model:orderNo="searchForm.order_no" v-model:customer="searchForm.customer_name"
                    v-model:customerId="searchForm.customer_id"
                    v-model:contact="searchForm.customer_mobile" :customers="customers" :orderNumbers="orderNumbers"
                    @search="handleSearchOrder" @clear="handleClearSearch" />

                <!-- Order Summary Header -->
                <CompletionHeader v-if="order" :order="order" :racks="racks" :cutters="cutters"
                    @update-rack="handleUpdateRack" @update-cutter="handleUpdateCutter" />

                <!-- Completion Items Table -->
                <CompletionItemsTable v-if="order" :items="order.items" :tailors="tailors" @update-item="handleUpdateItem"
                    @calculate-stock="handleCalculateStock" @calculate-commission="handleCalculateCommission" />

                <!-- Action Buttons -->
                <div v-if="order" class="flex justify-center gap-4 mt-4">
                    <button @click="handleUpdateCompletion"
                        class="px-8 py-3.5 rounded-2xl border-2 border-slate-200 bg-white text-slate-600 font-bold text-sm tracking-wide shadow-sm hover:bg-slate-50 hover:border-slate-300 hover:shadow-md transition-all duration-300">
                        <i class="fa fa-refresh mr-2 opacity-50"></i> Update Details
                    </button>
                    <button @click="handleSubmitCompletion"
                        class="px-10 py-3.5 rounded-2xl bg-blue-600 text-white font-bold text-sm tracking-widest uppercase shadow-xl shadow-blue-600/20 hover:bg-blue-700 hover:shadow-2xl hover:shadow-blue-700/30 hover:-translate-y-0.5 transition-all duration-300">
                        <i class="fa fa-check-circle mr-2"></i> Submit Completion
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import { router } from '@inertiajs/vue3'
import OrderSearch from '@/components/Tailoring/JobCompletion/OrderSearch.vue'
import CompletionHeader from '@/components/Tailoring/JobCompletion/CompletionHeader.vue'
import CompletionItemsTable from '@/components/Tailoring/JobCompletion/CompletionItemsTable.vue'
import StatusBar from '@/components/Tailoring/JobCompletion/CompletionStatusBar.vue'
import axios from 'axios'

const props = defineProps({
    racks: Object,
    tailors: Object,
    cutters: Object,
    customers: Array,
    orderNumbers: Array,
})

const toast = useToast()
const order = ref(null)
const searchForm = ref({
    order_no: '',
    customer_id: null,
    customer_name: '',
    customer_mobile: '',
})

const handleSearchOrder = async () => {
    if (!searchForm.value.order_no) {
        toast.error('Please enter order number')
        return
    }

    try {
        const response = await axios.get(`/tailoring/job-completion/order-by-number/${searchForm.value.order_no}`)
        if (response.data.success) {
            order.value = response.data.data
            toast.success('Order loaded successfully')
        } else {
            toast.error(response.data.message || 'Order not found')
        }
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to load order')
    }
}

const handleClearSearch = () => {
    searchForm.value = {
        order_no: '',
        customer_id: null,
        customer_name: '',
        customer_mobile: '',
    }
    order.value = null
}

const handleUpdateRack = (rackId) => {
    if (order.value) order.value.rack_id = rackId
}

const handleUpdateCutter = (cutterId) => {
    if (order.value) order.value.cutter_id = cutterId
}

const handleUpdateItem = async (itemId, itemData) => {
    try {
        const response = await axios.put(`/tailoring/job-completion/item/${itemId}/completion`, itemData)
        if (response.data.success) {
            const index = order.value.items.findIndex(i => i.id === itemId)
            if (index !== -1) {
                order.value.items[index] = response.data.data
            }
            toast.success('Item updated successfully')
        }
    } catch (error) {
        toast.error('Failed to update item')
    }
}

const handleCalculateStock = async (itemId, stockData) => {
    try {
        const response = await axios.post('/tailoring/job-completion/calculate-stock-balance', stockData)
        if (response.data.success) {
            return response.data.data
        }
    } catch (error) {
        toast.error('Failed to calculate stock balance')
    }
}

const handleCalculateCommission = async (itemId, commissionData) => {
    try {
        const response = await axios.post('/tailoring/job-completion/calculate-tailor-commission', commissionData)
        if (response.data.success) {
            return response.data.data
        }
    } catch (error) {
        toast.error('Failed to calculate commission')
    }
}

const handleUpdateCompletion = async () => {
    if (!order.value) return

    const selectedItems = order.value.items.filter(item => item.is_selected_for_completion).map(item => item.id)

    try {
        const response = await axios.put(`/tailoring/job-completion/${order.value.id}/completion`, {
            items: order.value.items,
            selected_item_ids: selectedItems,
            rack_id: order.value.rack_id,
            cutter_id: order.value.cutter_id,
        })
        if (response.data.success) {
            order.value = response.data.data
            toast.success('Completion updated successfully')
        }
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to update completion')
    }
}

const handleSubmitCompletion = async () => {
    if (!order.value) return

    const selectedItems = order.value.items.filter(item => item.is_selected_for_completion).map(item => item.id)

    if (selectedItems.length === 0) {
        toast.error('Please select at least one item to complete')
        return
    }

    try {
        const response = await axios.post(`/tailoring/job-completion/${order.value.id}/completion/submit`, {
            items: order.value.items,
            selected_item_ids: selectedItems,
        })
        if (response.data.success) {
            order.value = response.data.data
            toast.success('Completion submitted successfully')
        }
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to submit completion')
    }
}
onMounted(() => {
    const urlParams = new URLSearchParams(window.location.search);
    const orderNo = urlParams.get('order_no');
    if (orderNo) {
        searchForm.value.order_no = orderNo;
        handleSearchOrder();
    }
})
</script>
