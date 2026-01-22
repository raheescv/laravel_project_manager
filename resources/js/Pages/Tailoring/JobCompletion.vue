<template>
    <div class="bg-light min-vh-100 py-4">
        <div class="container pb-5">
            <!-- Page Header -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-4 d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h3 fw-bold text-dark mb-1">Job Completion</h1>
                        <p class="text-muted mb-0">Track and complete tailoring orders efficiently</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="/tailoring/order" class="quick-action-link primary">
                            <i class="fa fa-list"></i>
                            <span>Orders List</span>
                        </a>
                    </div>
                </div>
            </div>

            <div class="d-flex flex-column gap-4">
                <!-- Order Search -->
                <OrderSearch v-model:orderNo="searchForm.order_no" v-model:customer="searchForm.customer_name"
                    v-model:customerId="searchForm.customer_id"
                    v-model:contact="searchForm.customer_mobile" :customers="customers" :orderNumbers="orderNumbers"
                    @search="handleSearchOrder" @clear="handleClearSearch" />

                <!-- Status Bar -->

                <!-- Order Summary Header -->
                <CompletionHeader v-if="order" :order="order" :racks="racks" :cutters="cutters"
                    @update-rack="handleUpdateRack" @update-cutter="handleUpdateCutter" />

                <!-- Completion Items Table -->
                <CompletionItemsTable v-if="order" :items="order.items" :tailors="tailors" @update-item="handleUpdateItem"
                    @calculate-stock="handleCalculateStock" @calculate-commission="handleCalculateCommission" />

                <!-- Action Buttons -->
                <div v-if="order" class="d-flex justify-content-center gap-3 mt-2">
                    <button @click="handleUpdateCompletion"
                        class="btn btn-outline-secondary btn-lg px-5 fw-bold shadow-sm">
                        <i class="fa fa-refresh me-2"></i> Update
                    </button>
                    <button @click="handleSubmitCompletion"
                        class="btn btn-primary btn-lg px-5 fw-bold shadow-sm">
                        <i class="fa fa-check-circle me-2"></i> Submit Completion
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

<style scoped>
.quick-action-link {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    border: 1px solid rgba(0, 0, 0, 0.05);
    background: white;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
}

.quick-action-link:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    background-color: #f8fafc;
}

.quick-action-link.primary {
    color: #3b82f6;
}

.quick-action-link.success {
    color: #10b981;
}
</style>
