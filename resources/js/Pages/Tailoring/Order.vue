<template>
    <div class="min-h-screen bg-gray-50 p-4 md:p-6">
        <div class="max-w-7xl mx-auto space-y-6">
            <!-- Page Header -->
            <div class="bg-white rounded-lg shadow-sm p-4 md:p-6">
                <h1 class="text-2xl font-bold text-gray-800 mb-2">Tailoring Order</h1>
                <p class="text-gray-600">Create and manage tailoring orders</p>
            </div>

            <!-- Order Header Section -->
            <OrderHeader v-model:orderNo="form.order_no" v-model:customer="form.customer_name"
                v-model:contact="form.customer_mobile" v-model:salesman="form.salesman_id"
                v-model:orderDate="form.order_date" :customers="customers" :salesmen="salesmen"
                @add-customer="handleAddCustomer" />

            <!-- Category Selection -->
            <CategoryHeader :categories="categories" :selectedCategories="selectedCategories"
                @category-selected="handleCategorySelection" />

            <!-- Category & Model Selector -->
            <CategoryModelSelector :categories="categories" v-model:selectedCategory="selectedCategory"
                v-model:selectedModel="selectedModel" @add-model="handleAddModel" />

            <!-- Main Content: Measurements and Styling -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Measurement Form -->
                <MeasurementForm v-model="measurements" :category="selectedCategory" :model="selectedModel"
                    :measurementOptions="measurementOptions" @add-option="handleAddMeasurementOption" />
            </div>

            <!-- Product Selection -->
            <ProductSelection v-model="currentItem" :products="products" :colors="colors" @add-item="handleAddItem"
                @calculate-amount="calculateItemAmount" />

            <!-- Summary and Work Orders -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Summary Table -->
                <SummaryTable :items="form.items" />

                <!-- Work Orders Preview -->
                <WorkOrdersPreview :items="form.items" />
            </div>

            <!-- Action Buttons -->
            <ActionButtons :isLoading="isSubmitting" :canSubmit="canSubmit" @clear="handleClear"
                @create-order="handleCreateOrder" @payment="handlePayment" />
        </div>

        <!-- Payment Modal -->
        <PaymentModal v-if="showPaymentModal" :order="form" :payments="payments" :paymentMethods="paymentMethods"
            @close="showPaymentModal = false" @add-payment="handleAddPayment" @update-payment="handleUpdatePayment"
            @delete-payment="handleDeletePayment" />
    </div>
</template>

<script setup>
import {
    ref,
    computed,
    onMounted
} from 'vue'
import {
    router
} from '@inertiajs/vue3'
import {
    useToast
} from 'vue-toastification'
import OrderHeader from '@/components/Tailoring/OrderHeader.vue'
import CategoryHeader from '@/components/Tailoring/CategoryHeader.vue'
import CategoryModelSelector from '@/components/Tailoring/CategoryModelSelector.vue'
import MeasurementForm from '@/components/Tailoring/MeasurementForm.vue'
import ProductSelection from '@/components/Tailoring/ProductSelection.vue'
import SummaryTable from '@/components/Tailoring/SummaryTable.vue'
import WorkOrdersPreview from '@/components/Tailoring/WorkOrdersPreview.vue'
import ActionButtons from '@/components/Tailoring/ActionButtons.vue'
import PaymentModal from '@/components/Tailoring/PaymentModal.vue'
import axios from 'axios'

const props = defineProps({
    order: Object,
    categories: Array,
    measurementOptions: Object,
    salesmen: Object,
    customers: Object,
})

const toast = useToast()

// Form state
const form = ref({
    id: props.order?.id || null,
    order_no: props.order?.order_no || '',
    customer_name: props.order?.customer_name || '',
    customer_mobile: props.order?.customer_mobile || '',
    salesman_id: props.order?.salesman_id || null,
    order_date: props.order?.order_date || new Date().toISOString().split('T')[0],
    items: props.order?.items || [],
    payments: props.order?.payments || [],
})

const measurements = ref({})
const currentItem = ref({})
const selectedCategory = ref(null)
const selectedModel = ref(null)
const selectedCategories = ref([])
const products = ref([])
const colors = ref([])
const payments = ref(props.order?.payments || [])
const paymentMethods = ref([])
const showPaymentModal = ref(false)
const isSubmitting = ref(false)

const canSubmit = computed(() => {
    return form.value.items.length > 0 && form.value.customer_name
})

// Methods
const handleCategorySelection = (categoryIds) => {
    selectedCategories.value = categoryIds
}

const handleAddMeasurementOption = async (type, value) => {
    try {
        const response = await axios.post('/tailoring/order/measurement-options', {
            option_type: type,
            value: value
        })
        if (response.data.success) {
            toast.success('Option added successfully')
            loadMeasurementOptions()
        }
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to add option')
    }
}

const handleAddModel = async (categoryId, modelName) => {
    try {
        const response = await axios.post('/tailoring/order/category-models', {
            tailoring_category_id: categoryId,
            name: modelName
        })
        if (response.data.success) {
            toast.success('Model added successfully')
            // Reload categories to get updated models
            const catResponse = await axios.get('/tailoring/order/categories')
            if (catResponse.data.success) {
                // Update categories
            }
        }
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to add model')
    }
}

const handleAddItem = async () => {
    if (!currentItem.value.product_name || !currentItem.value.quantity) {
        toast.error('Please fill required fields')
        return
    }

    try {
        const itemData = {
            ...currentItem.value,
            ...measurements.value,
            tailoring_category_id: selectedCategory.value?.id,
            tailoring_category_model_id: selectedModel.value?.id,
        }

        const response = await axios.post('/tailoring/order/add-item', {
            ...itemData,
            tailoring_order_id: form.value.id
        })

        if (response.data.success) {
            form.value.items.push(response.data.data)
            currentItem.value = {}
            measurements.value = {}
            toast.success('Item added successfully')
        }
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to add item')
    }
}

const calculateItemAmount = async () => {
    if (!currentItem.value.quantity || !currentItem.value.unit_price) return

    try {
        const response = await axios.post('/tailoring/order/calculate-amount', {
            quantity: currentItem.value.quantity,
            unit_price: currentItem.value.unit_price,
            stitch_rate: currentItem.value.stitch_rate || 0,
            discount: currentItem.value.discount || 0,
            tax: currentItem.value.tax || 0,
        })

        if (response.data.success) {
            Object.assign(currentItem.value, response.data.data)
        }
    } catch (error) {
        console.error('Failed to calculate amount', error)
    }
}

const handleCreateOrder = async () => {
    if (!canSubmit.value) {
        toast.error('Please add at least one item and customer name')
        return
    }

    isSubmitting.value = true
    try {
        const url = form.value.id ?
            `/tailoring/order/${form.value.id}` :
            '/tailoring/order'

        const method = form.value.id ? 'put' : 'post'

        router[method](url, form.value, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(form.value.id ? 'Order updated successfully' : 'Order created successfully')
            },
            onError: (errors) => {
                toast.error(Object.values(errors)[0] || 'Failed to save order')
            },
            onFinish: () => {
                isSubmitting.value = false
            }
        })
    } catch (error) {
        toast.error('Failed to save order')
        isSubmitting.value = false
    }
}

const handleClear = () => {
    form.value = {
        id: null,
        order_no: '',
        customer_name: '',
        customer_mobile: '',
        salesman_id: null,
        order_date: new Date().toISOString().split('T')[0],
        items: [],
        payments: [],
    }
    measurements.value = {}
    currentItem.value = {}
    selectedCategory.value = null
    selectedModel.value = null
}

const handlePayment = () => {
    showPaymentModal.value = true
}

const handleAddPayment = async (paymentData) => {
    try {
        const response = await axios.post('/tailoring/order/add-payment', {
            ...paymentData,
            tailoring_order_id: form.value.id
        })

        if (response.data.success) {
            payments.value.push(response.data.data)
            form.value.payments = payments.value
            toast.success('Payment added successfully')
        }
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to add payment')
    }
}

const handleUpdatePayment = async (paymentId, paymentData) => {
    try {
        const response = await axios.put(`/tailoring/order/update-payment/${paymentId}`, paymentData)
        if (response.data.success) {
            const index = payments.value.findIndex(p => p.id === paymentId)
            if (index !== -1) {
                payments.value[index] = response.data.data
            }
            toast.success('Payment updated successfully')
        }
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to update payment')
    }
}

const handleDeletePayment = async (paymentId) => {
    try {
        const response = await axios.delete(`/tailoring/order/remove-payment/${paymentId}`)
        if (response.data.success) {
            payments.value = payments.value.filter(p => p.id !== paymentId)
            form.value.payments = payments.value
            toast.success('Payment deleted successfully')
        }
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to delete payment')
    }
}

const handleAddCustomer = () => {
    // Open customer modal or navigate to customer creation
    router.visit('/account/customer/create')
}

const loadMeasurementOptions = async () => {
    try {
        const response = await axios.get('/tailoring/order/measurement-options')
        if (response.data.success) {
            // Update measurement options
        }
    } catch (error) {
        console.error('Failed to load measurement options', error)
    }
}

onMounted(() => {
    loadMeasurementOptions()
})
</script>
