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
                v-model:customerId="form.account_id" v-model:contact="form.customer_mobile"
                v-model:salesman="form.salesman_id" v-model:orderDate="form.order_date"
                v-model:deliveryDate="form.delivery_date" :customers="customers" :salesmen="salesmen"
                @add-customer="showCustomerModal = true" @customer-selected="handleCustomerSelected" />

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

        <!-- Sale Confirmation Modal replaces individual PaymentModal -->

        <!-- Customer Modal -->
        <CustomerModal v-if="showCustomerModal" :show="showCustomerModal" 
            :customer-types="customerTypes" :countries="countries"
            @close="showCustomerModal = false"
            @customerSaved="handleCustomerAdded" @customerSelected="handleCustomerSelected" />

        <!-- Sale Confirmation Modal -->
        <SaleConfirmationModal :show="showConfirmationModal" :sale-data="confirmationData" :loading="isSubmitting"
            :payment-method="selectedPaymentMethod" :send-to-whatsapp="sendToWhatsapp"
            @update:paymentMethod="val => selectedPaymentMethod = val" @update:sendToWhatsapp="val => sendToWhatsapp = val"
            @openCustomPayment="showCustomPaymentModal = true" @close="showConfirmationModal = false"
            @submit="processSubmitOrder" />

        <!-- Custom Payment Modal -->
        <CustomPaymentModal :show="showCustomPaymentModal" :total-amount="grandTotal"
            :payment-methods="paymentMethods" :initial-payments="form.payments"
            @close="showCustomPaymentModal = false" @save="handleCustomPaymentSave" />
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
import CustomerModal from '@/components/CustomerModal.vue'
import SaleConfirmationModal from '@/components/Tailoring/SaleConfirmationModal.vue'
import CustomPaymentModal from '@/components/Tailoring/CustomPaymentModal.vue'
import axios from 'axios'

const props = defineProps({
    order: Object,
    categories: Array,
    measurementOptions: Object,
    salesmen: Object,
    customers: Object,
    paymentMethods: Array,
    customerTypes: {
        type: Object,
        default: () => ({})
    },
    countries: {
        type: Object,
        default: () => ({})
    },
})

const toast = useToast()

// Form state
const form = ref({
    id: props.order?.id || null,
    order_no: props.order?.order_no || '',
    account_id: props.order?.account_id || null,
    customer_name: props.order?.customer_name || '',
    customer_mobile: props.order?.customer_mobile || '',
    salesman_id: props.order?.salesman_id || null,
    order_date: props.order?.order_date || new Date().toISOString().split('T')[0],
    delivery_date: props.order?.delivery_date || new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
    items: props.order?.items || [],
    payments: props.order?.payments || [],
})

const measurements = ref({})
const currentItems = ref({})
const selectedCategories = ref([])
const products = ref([])
const colors = ref([])
const paymentMethods = ref(props.paymentMethods || [])
const showPaymentModal = ref(false)
const showConfirmationModal = ref(false)
const showCustomPaymentModal = ref(false)
const selectedPaymentMethod = ref(1)
const sendToWhatsapp = ref(false)
const showCustomerModal = ref(false)
const isSubmitting = ref(false)
const isAddingItem = ref({})
const activeCategoryTab = ref(null)
const measurementOptions = ref(props.measurementOptions || {})
const editingItemIds = ref({}) // Map of categoryId -> itemId

const canSubmit = computed(() => {
    return form.value.items.length > 0 && form.value.customer_name
})

const grandTotal = computed(() => {
    return form.value.items.reduce((sum, item) => sum + parseFloat(item.total || 0), 0)
})

const totalPaid = computed(() => {
    const fromPayments = (form.value.payments || []).reduce((sum, p) => sum + parseFloat(p.amount || 0), 0)
    return fromPayments
})

const balance = computed(() => {
    return grandTotal.value - totalPaid.value
})

const confirmationData = computed(() => {
    return {
        ...form.value,
        grand_total: grandTotal.value,
        customerName: {
            name: form.value.customer_name || 'Walk-in Customer',
            mobile: form.value.customer_mobile || ''
        },
        payment_method: selectedPaymentMethod.value,
        custom_payment_data: {
            payments: form.value.payments,
            totalPaid: totalPaid.value,
            balanceDue: balance.value
        }
    }
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

            // Define keys to extract for measurements
            const measurementKeys = [
                'length', 'shoulder', 'sleeve', 'chest', 'stomach', 'sl_chest', 'sl_so', 'neck',
                'bottom', 'mar_size', 'mar_model', 'cuff', 'cuff_size', 'cuff_cloth', 'cuff_model',
                'neck_d_button', 'side_pt_size', 'collar', 'collar_size', 'collar_cloth', 'collar_model',
                'regal_size', 'knee_loose', 'fp_down', 'fp_model', 'fp_size', 'pen', 'side_pt_model',
                'stitching', 'button', 'button_no', 'mobile_pocket', 'tailoring_notes',
                'tailoring_category_model_id', 'tailoring_category_model_name'
            ]

            // Filter measurements only
            const filteredMeasurements = {}
            measurementKeys.forEach(key => {
                if (itemMeasurements[key] !== undefined) {
                    filteredMeasurements[key] = itemMeasurements[key]
                }
            })

            // Construct the final item object
            const finalItem = {
                ...item,
                ...filteredMeasurements,
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

            // Sync measurements to all items of the same category AND model
            form.value.items.forEach((it, idx) => {
                it.item_no = idx + 1
                if (it.tailoring_category_id === categoryId && 
                    it.tailoring_category_model_id === filteredMeasurements.tailoring_category_model_id) {
                    // Update measurement fields ONLY
                    Object.assign(it, filteredMeasurements)
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

const handleEditItem = (item) => {
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

const handleRemoveItem = (item) => {
    if (confirm('Are you sure you want to remove this item?')) {
        // Find index of the item
        const index = form.value.items.findIndex(i => 
            (i.id && i.id === item.id) || 
            (i._temp_id && i._temp_id === item._temp_id)
        )

        if (index === -1) return

        const catId = item.tailoring_category_id
        if (editingItemIds.value[catId] === (item.id || item._temp_id)) {
            editingItemIds.value[catId] = null
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


const handleCreateOrder = () => {
    if (!canSubmit.value) {
        toast.error('Please add at least one item and customer name')
        return
    }
    showConfirmationModal.value = true
}

const processSubmitOrder = async () => {
    isSubmitting.value = true
    try {
        const url = form.value.id ?
            `/tailoring/order/${form.value.id}` :
            '/tailoring/order'

        const method = form.value.id ? 'put' : 'post'

        // Add payment data to form before submission
        let finalPayments = [...form.value.payments]
        
        // If Cash (1) or Card (2) is selected and no payments are manually added,
        // we should create an automatic payment for the full amount
        if ((selectedPaymentMethod.value === 1 || selectedPaymentMethod.value === 2) && finalPayments.length === 0) {
            finalPayments.push({
                payment_method_id: selectedPaymentMethod.value,
                amount: grandTotal.value,
                date: new Date().toISOString().split('T')[0]
            })
        }

        form.value.payments = finalPayments
        form.value.payment_method = selectedPaymentMethod.value

        router[method](url, form.value, {
            preserveScroll: true,
            onSuccess: () => {
                showConfirmationModal.value = false
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
        account_id: null,
        customer_name: '',
        customer_mobile: '',
        salesman_id: null,
        order_date: new Date().toISOString().split('T')[0],
        delivery_date: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
        items: [],
        payments: [],
    }
    measurements.value = {}
    currentItem.value = {}
    selectedCategory.value = null
    selectedModel.value = null
}

// Payment handling via confirmation flow
const handlePayment = () => {
    showConfirmationModal.value = true
}

const handleCustomPaymentSave = (paymentData) => {
    form.value.payments = paymentData.payments
    selectedPaymentMethod.value = 'custom'
    showCustomPaymentModal.value = false
    toast.success('Custom payments saved')
}

const handleAddCustomer = () => {
    showCustomerModal.value = true
}

const handleCustomerAdded = (customer) => {
    showCustomerModal.value = false
    // Refresh the page or update customers list
    router.reload({
        only: ['customers'],
        onSuccess: () => {
            form.value.account_id = customer.id
            form.value.customer_name = customer.name
            form.value.customer_mobile = customer.mobile
            toast.success('Customer added and selected')
        }
    })
}

const handleCustomerSelected = (customer) => {
    if (customer) {
        form.value.account_id = customer.id
        form.value.customer_name = customer.name
        form.value.customer_mobile = customer.mobile
    } else {
        form.value.account_id = null
        form.value.customer_name = ''
        form.value.customer_mobile = ''
    }
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
