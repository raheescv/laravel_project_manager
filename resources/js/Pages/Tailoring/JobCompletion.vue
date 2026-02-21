<template>
    <div class="min-h-screen bg-slate-50 font-sans relative overflow-hidden">
        <div class="pointer-events-none absolute -top-24 -right-16 h-64 w-64 rounded-full bg-blue-200/35 blur-3xl"></div>
        <div class="pointer-events-none absolute top-72 -left-24 h-72 w-72 rounded-full bg-cyan-200/25 blur-3xl"></div>
        <div class="pointer-events-none absolute bottom-24 right-10 h-56 w-56 rounded-full bg-emerald-200/25 blur-3xl"></div>
        <!-- Page Header - same as Order.vue (gradient, compact) -->
        <div class="bg-gradient-to-r from-blue-600 via-blue-700 to-indigo-700 pt-4 pb-14 sm:pb-16 px-3 relative overflow-hidden shadow-lg">
            <div class="max-w-[1600px] mx-auto relative z-10">
                <!-- Breadcrumbs - light on gradient -->
                <div class="flex flex-wrap items-center gap-2 text-white/80 text-[11px] sm:text-xs mb-2 transition-all">
                    <a href="/dashboard" class="hover:text-white no-underline flex items-center gap-1 transition-colors">
                        <i class="fa fa-home"></i>
                        <span>Home</span>
                    </a>
                    <i class="fa fa-chevron-right text-[10px] opacity-60"></i>
                    <a href="/tailoring/order" class="hover:text-white no-underline items-center gap-1 transition-colors hidden sm:inline-flex">
                        <i class="fa fa-scissors"></i>
                        <span>Tailoring</span>
                    </a>
                    <i class="fa fa-chevron-right text-[10px] opacity-60 hidden sm:block"></i>
                    <span class="text-white font-medium tracking-tight">Job Completion</span>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
                    <div class="flex items-center gap-2.5">
                        <div class="bg-white/20 p-1.5 rounded-md">
                            <i class="fa fa-tasks text-white text-xs"></i>
                        </div>
                        <div>
                            <h1 class="text-base font-bold text-white tracking-tight leading-tight">Job Completion</h1>
                            <p class="text-white/80 text-[11px] sm:text-xs font-medium leading-tight">Track and complete tailoring orders efficiently</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 sm:flex gap-1.5 w-full sm:w-auto">
                        <a href="/tailoring/order" class="inline-flex items-center justify-center gap-1.5 px-2.5 py-1.5 rounded-lg text-[11px] sm:text-xs font-semibold border border-white/20 bg-white/10 text-white hover:bg-white/20 transition-all no-underline">
                            <i class="fa fa-th-list text-xs"></i>
                            <span>Orders List</span>
                        </a>
                        <a href="/tailoring/order/create" class="inline-flex items-center justify-center gap-1.5 px-2.5 py-1.5 rounded-lg text-[11px] sm:text-xs font-semibold bg-white text-blue-700 hover:bg-white/95 shadow-sm transition-all no-underline">
                            <i class="fa fa-plus text-xs"></i>
                            <span>New Order</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-[1600px] mx-auto px-2.5 sm:px-3 -mt-10 sm:-mt-12 relative z-20 pb-8">
            <div class="space-y-2.5 sm:space-y-3">
                <!-- Order Search -->
                <div class="bg-white/95 backdrop-blur-sm rounded-xl shadow-lg border border-slate-200/90 overflow-hidden">
                    <div class="px-2.5 sm:px-3 py-2.5 sm:py-3">
                        <h6 class="text-xs font-bold text-slate-800 mb-1 flex items-center gap-1">
                            <i class="fa fa-search text-blue-500 text-xs"></i>
                            <span>Search Order</span>
                        </h6>
                        <div class="bg-gradient-to-r from-slate-50 to-blue-50 border border-slate-200 rounded-lg p-1">
                            <OrderSearch v-model:orderNo="searchForm.order_no" v-model:customer="searchForm.customer_name"
                                v-model:customerId="searchForm.customer_id"
                                v-model:contact="searchForm.customer_mobile" :customers="customers" :orderNumbers="orderNumbers"
                                @search="handleSearchOrder" @clear="handleClearSearch" />
                        </div>
                    </div>
                </div>

                <!-- Order Summary Header -->
                <div v-if="order" class="bg-white/95 backdrop-blur-sm rounded-xl shadow-lg border border-slate-200/90 overflow-hidden">
                    <div class="px-2.5 sm:px-3 py-2.5 sm:py-3">
                        <h6 class="text-xs font-bold text-slate-800 mb-1 flex items-center gap-1">
                            <i class="fa fa-info-circle text-blue-500 text-xs"></i>
                            <span>Order Summary</span>
                        </h6>
                        <div class="bg-gradient-to-r from-slate-50 to-blue-50 border border-slate-200 rounded-lg p-1">
                            <CompletionHeader :order="order" :racks="racks" :cutters="cutters"
                                @update-rack="handleUpdateRack" @update-cutter="handleUpdateCutter"
                                @update-cutter-rating="handleUpdateCutterRating" />
                        </div>
                    </div>
                </div>

                <!-- Completion Items Table -->
                <div v-if="order" class="bg-white/95 backdrop-blur-sm rounded-xl shadow-lg border border-slate-200/90 overflow-hidden">
                    <div class="px-2.5 sm:px-3 py-2.5 sm:py-3">
                        <h6 class="text-xs font-bold text-slate-800 mb-1 flex items-center gap-1">
                            <i class="fa fa-list-alt text-emerald-500 text-xs"></i>
                            <span>Completion Items</span>
                        </h6>
                        <div class="bg-gradient-to-r from-slate-50 to-blue-50 border border-slate-200 rounded-lg p-1">
                            <CompletionItemsTable :items="order?.items || []" :tailors="tailors || {}" @update-item="handleUpdateItem" />
                        </div>
                    </div>
                </div>

                <!-- Action Buttons - same style as Order.vue ActionButtons / SaleConfirmationModal footer -->
                <div v-if="order" class="sticky bottom-2 sm:bottom-4 z-30">
                    <div class="bg-gradient-to-r from-slate-50/95 to-gray-50/95 backdrop-blur-sm rounded-xl border border-slate-200 overflow-hidden shadow-md">
                        <div class="px-2.5 sm:px-3 py-2">
                            <div class="flex flex-col sm:flex-row justify-end gap-1.5 sm:justify-center">
                                <button type="button" @click="handleUpdateCompletion"
                                    class="w-full sm:w-auto inline-flex items-center justify-center px-3 py-1.5 border border-slate-300 shadow-sm text-xs font-semibold rounded-lg text-slate-700 bg-white hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
                                    <i class="fa fa-refresh mr-1 text-xs"></i>
                                    Update Details
                                </button>
                                <button type="button" @click="handleSubmitCompletion"
                                    class="w-full sm:w-auto inline-flex items-center justify-center px-3 py-1.5 border border-transparent text-xs font-semibold rounded-lg text-white shadow-lg focus:outline-none focus:ring-2 focus:ring-offset-2 transition-all duration-200 sm:hover:scale-105 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 focus:ring-green-500">
                                    <i class="fa fa-check-circle mr-1 text-xs"></i>
                                    Submit Completion
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import OrderSearch from '@/components/Tailoring/JobCompletion/OrderSearch.vue'
import CompletionHeader from '@/components/Tailoring/JobCompletion/CompletionHeader.vue'
import CompletionItemsTable from '@/components/Tailoring/JobCompletion/CompletionItemsTable.vue'
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

const handleUpdateCutterRating = (rating) => {
    if (!order.value) return
    order.value.cutter_rating = rating === null || rating === undefined || rating === '' ? null : Number(rating)
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

const handleUpdateCompletion = async () => {
    if (!order.value) return

    const selectedItems = order.value.items.filter(item => item.is_selected_for_completion).map(item => item.id)

    try {
        const response = await axios.put(`/tailoring/job-completion/${order.value.id}/completion`, {
            items: order.value.items,
            selected_item_ids: selectedItems,
            rack_id: order.value.rack_id,
            cutter_id: order.value.cutter_id,
            cutter_rating: order.value.cutter_rating,
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
            rack_id: order.value.rack_id,
            cutter_id: order.value.cutter_id,
            cutter_rating: order.value.cutter_rating,
        })
        if (response.data.success) {
            order.value = response.data.data
            toast.success('Completion submitted successfully')
        } else {
            toast.error(response.data.message || 'Failed to submit completion')
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
