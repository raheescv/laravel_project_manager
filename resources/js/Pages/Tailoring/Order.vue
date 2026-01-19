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

            <!-- Main Content: Measurements and Styling -->
            <div v-if="selectedCategories.length > 0" class="space-y-6">
                <!-- Category Tabs -->
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                        <a v-for="id in selectedCategories" :key="id" href="#" @click.prevent="activeCategoryTab = id"
                            :class="[activeCategoryTab === id ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm']">
                            {{ getCategory(id)?.name }}
                        </a>
                    </nav>
                </div>

                <!-- Active Category Content -->
                <div v-if="activeCategoryTab" class="bg-white rounded-lg shadow-sm p-4 md:p-6 border border-gray-100">
                    <div class="grid grid-cols-1 gap-6">
                        <!-- Measurement Form -->
                        <MeasurementForm v-if="measurements[activeCategoryTab]"
                            v-model="measurements[activeCategoryTab]" :category="getCategory(activeCategoryTab)"
                            :measurementOptions="measurementOptions" @add-option="handleAddMeasurementOption" />

                        <!-- Product Selection -->
                        <ProductSelection v-if="currentItems[activeCategoryTab]"
                            v-model="currentItems[activeCategoryTab]" :products="products" :colors="colors"
                            :isLoading="isAddingItem[activeCategoryTab]"
                            :isEditing="!!editingItemIds[activeCategoryTab]"
                            @add-item="(item) => handleAddItem(item, activeCategoryTab)"
                            @calculate-amount="(item) => calculateItemAmount(item, activeCategoryTab)"
                            @clear="handleItemClear(activeCategoryTab)" />
                    </div>
                </div>
            </div>


            <!-- Summary and Work Orders -->
            <!-- Summary and Work Orders -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <!-- Summary Table -->
                <SummaryTable class="lg:col-span-3" :items="form.items" />

                <!-- Work Orders Preview -->
                <WorkOrdersPreview class="lg:col-span-9" :items="form.items" @edit="handleEditItem"
                    @remove="handleRemoveItem" />
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
const currentItems = ref({})
const selectedCategories = ref([])
const products = ref([])
const colors = ref([])
const payments = ref(props.order?.payments || [])
const paymentMethods = ref([])
const showPaymentModal = ref(false)
const isSubmitting = ref(false)
const isAddingItem = ref({})
const activeCategoryTab = ref(null)
const measurementOptions = ref(props.measurementOptions || {})
const editingItemIds = ref({}) // Map of categoryId -> itemId

const canSubmit = computed(() => {
    return form.value.items.length > 0 && form.value.customer_name
})

const getCategory = (id) => {
    return props.categories.find(c => c.id === id)
}

// Methods
const handleCategorySelection = (categoryIds) => {
    selectedCategories.value = categoryIds

    // Initialize state for new categories
    categoryIds.forEach(id => {
        if (!measurements.value[id]) measurements.value[id] = {}
        if (!currentItems.value[id]) currentItems.value[id] = {}
    })

    // Set active tab logic
    if (categoryIds.length > 0) {
        // If no active tab or current active is removed, select the last one
        if (!activeCategoryTab.value || !categoryIds.includes(activeCategoryTab.value)) {
            activeCategoryTab.value = categoryIds[categoryIds.length - 1]
        }
    } else {
        activeCategoryTab.value = null
    }
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

const handleAddItem = async (itemData, categoryId) => {
    if (!categoryId) return
    const category = getCategory(categoryId)
    // Merge data from props or current state
    const item = itemData || currentItems.value[categoryId]
    // Get measurements for this category
    const itemMeasurements = measurements.value[categoryId]

    if (!item.product_name && !item.product_id) {
        toast.error('Please select a product')
        return
    }

    if (!item.quantity || item.quantity <= 0) {
        toast.error('Please enter a valid quantity')
        return
    }

    // Validate measurements
    if (Object.keys(itemMeasurements || {}).length === 0) {
        toast.error(`Please fill in measurement details for ${category?.name || 'Item'}`)
        return
    }

    isAddingItem.value = { ...isAddingItem.value, [categoryId]: true }

    // Capture editing ID immediately to avoid race condition with clear event
    const editingId = editingItemIds.value[categoryId]

    try {
        // Calculate amounts (stateless backend call)
        const response = await axios.post('/tailoring/order/calculate-amount', {
            quantity: item.quantity,
            unit_price: item.unit_price,
            stitch_rate: item.stitch_rate || 0,
            discount: item.discount || 0,
            tax: item.tax || 0,
        })

        if (response.data.success) {
            const calculatedData = response.data.data

            // Construct the final item object
            const finalItem = {
                ...item,
                ...itemMeasurements,
                ...calculatedData,
                tailoring_category_id: categoryId,
                category: category,
            }

            if (editingId) {
                // Find and update index
                const index = form.value.items.findIndex(i =>
                    (i.id && i.id === editingId) ||
                    (i._temp_id && i._temp_id === editingId)
                )

                if (index !== -1) {
                    // Preserve ID to avoid creating doubles on backend
                    finalItem.id = form.value.items[index].id
                    finalItem._temp_id = form.value.items[index]._temp_id

                    form.value.items[index] = finalItem
                    toast.success('Item updated successfully')
                } else {
                    // Item might have been deleted while editing? Add as new
                    finalItem._temp_id = Date.now() + Math.random().toString(36).substr(2, 9)
                    form.value.items.push(finalItem)
                    toast.success('Item added (original not found)')
                }

                // Clear editing state
                editingItemIds.value[categoryId] = null
            } else {
                // New item
                finalItem._temp_id = Date.now() + Math.random().toString(36).substr(2, 9)
                form.value.items.push(finalItem)
                toast.success('Item added to order')
            }

            // Sync measurements to all items of the same category
            form.value.items.forEach((it, idx) => {
                it.item_no = idx + 1
                if (it.tailoring_category_id === categoryId) {
                    // Update measurement fields
                    Object.assign(it, itemMeasurements)
                    it.tailoring_category_model_id = finalItem.tailoring_category_model_id
                    it.tailoring_category_model_name = finalItem.tailoring_category_model_name
                }
            })

            // Reset current item form but KEEP measurements
            currentItems.value[categoryId] = {
                 product_id: null,
                 product_name: '',
                 product_color: '',
                 quantity: 0,
                 unit_price: 0,
                 stitch_rate: 0,
                 tax: 0,
                 total: 0,
            }
        }
    } catch (error) {
        console.error('Failed to add item:', error)
        toast.error(error.response?.data?.message || 'Failed to calculate item amount')
    } finally {
        isAddingItem.value = { ...isAddingItem.value, [categoryId]: false }
    }
}


const calculateItemAmount = async (item, categoryId) => {
    if (!item.quantity || !item.unit_price) return

    try {
        const response = await axios.post('/tailoring/order/calculate-amount', {
            quantity: item.quantity,
            unit_price: item.unit_price,
            stitch_rate: item.stitch_rate || 0,
            discount: item.discount || 0,
            tax: item.tax || 0,
        })

        if (response.data.success) {
            currentItems.value[categoryId] = { ...item, ...response.data.data }
        }
    } catch (error) {
        console.error('Failed to calculate amount', error)
    }
}

const handleEditItem = (item, index) => {
    // Switch to the correct category tab
    if (!selectedCategories.value.includes(item.tailoring_category_id)) {
        selectedCategories.value.push(item.tailoring_category_id)
    }
    activeCategoryTab.value = item.tailoring_category_id

    // Restore data to forms
    currentItems.value[item.tailoring_category_id] = { ...item }
    measurements.value[item.tailoring_category_id] = { ...item }

    // Set editing state
    editingItemIds.value[item.tailoring_category_id] = item.id || item._temp_id

    toast.info("Item loaded for editing.")

    // Scroll to top or form area if needed
    window.scrollTo({ top: 0, behavior: 'smooth' })
}

const handleItemClear = (categoryId) => {
    // Clear editing state when user clicks clear on the form
    editingItemIds.value[categoryId] = null
}

const handleRemoveItem = (item, index) => {
    if (confirm('Are you sure you want to remove this item?')) {
        // If we are currently editing this item, clear the form too?
        // Check per category
        const catId = item.tailoring_category_id
        if (editingItemIds.value[catId] === (item.id || item._temp_id)) {
            editingItemIds.value[catId] = null
            // Also reset form? Maybe. For now let's just clear the edit lock.
            // Actually, if we delete it, we should probably reset the form to avoid "ghost" edits
            currentItems.value[catId] = {
                product_id: null,
                product_name: '',
                product_color: '',
                quantity: 0,
                unit_price: 0,
                stitch_rate: 0,
                tax: 0,
                total: 0,
            }
            measurements.value[catId] = {}
        }

        form.value.items.splice(index, 1)
        // Re-calculate item numbers
        form.value.items.forEach((it, idx) => it.item_no = idx + 1)
        toast.success('Item removed')
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
            measurementOptions.value = response.data.data || {}
        }
    } catch (error) {
        console.error('Failed to load measurement options', error)
    }
}

onMounted(() => {
    loadMeasurementOptions()
})
</script>
