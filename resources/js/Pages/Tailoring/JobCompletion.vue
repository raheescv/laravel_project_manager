<template>
    <div class="min-h-screen bg-gray-50 p-4 md:p-6">
        <div class="max-w-7xl mx-auto space-y-6">
            <!-- Page Header -->
            <div class="bg-white rounded-lg shadow-sm p-4 md:p-6">
                <h1 class="text-2xl font-bold text-gray-800 mb-2">Job Completion</h1>
                <p class="text-gray-600">Track and complete tailoring orders</p>
            </div>

            <!-- Order Search -->
            <OrderSearch v-model:orderNo="searchForm.order_no" v-model:customer="searchForm.customer_name"
                v-model:contact="searchForm.customer_mobile" v-model:orderDate="searchForm.order_date"
                v-model:deliveryDate="searchForm.delivery_date" v-model:rack="searchForm.rack_id" :racks="racks"
                @search="handleSearchOrder" @clear="handleClearSearch" />

            <!-- Status Bar -->
            <StatusBar :recordCount="order?.items?.length || 0" :completionStatus="order?.completion_status" />

            <!-- Order Summary Header -->
            <CompletionHeader v-if="order" :order="order" :racks="racks" :cutters="cutters"
                @update-rack="handleUpdateRack" @update-cutter="handleUpdateCutter" />

            <!-- Completion Items Table -->
            <CompletionItemsTable v-if="order" :items="order.items" :tailors="tailors" @update-item="handleUpdateItem"
                @calculate-stock="handleCalculateStock" @calculate-commission="handleCalculateCommission" />

            <!-- Action Buttons -->
            <div v-if="order" class="flex justify-center gap-4">
                <button @click="handleUpdateCompletion"
                    class="px-6 py-2 bg-gray-400 text-white rounded-md hover:bg-gray-500 font-semibold">
                    Update
                </button>
                <button @click="handleSubmitCompletion"
                    class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-semibold">
                    Submit
                </button>
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
})

const toast = useToast()
const order = ref(null)
const searchForm = ref({
    order_no: '',
    customer_name: '',
    customer_mobile: '',
    order_date: '',
    delivery_date: '',
    rack_id: '',
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
        customer_name: '',
        customer_mobile: '',
        order_date: '',
        delivery_date: '',
        rack_id: '',
    }
    order.value = null
}

const handleUpdateRack = async (rackId) => {
    if (!order.value) return
    try {
        const response = await axios.put(`/tailoring/job-completion/${order.value.id}/completion`, {
            rack_id: rackId
        })
        if (response.data.success) {
            order.value.rack_id = rackId
            toast.success('Rack updated successfully')
        }
    } catch (error) {
        toast.error('Failed to update rack')
    }
}

const handleUpdateCutter = async (cutterId) => {
    if (!order.value) return
    try {
        const response = await axios.put(`/tailoring/job-completion/${order.value.id}/completion`, {
            cutter_id: cutterId
        })
        if (response.data.success) {
            order.value.cutter_id = cutterId
            toast.success('Cutter updated successfully')
        }
    } catch (error) {
        toast.error('Failed to update cutter')
    }
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
</script>
