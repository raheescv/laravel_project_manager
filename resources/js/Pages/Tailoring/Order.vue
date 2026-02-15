<template>
    <div class="min-h-screen bg-[#f8fafc] font-sans">
        <!-- Page Header - SaleConfirmationModal style gradient -->
        <div
            class="bg-gradient-to-r from-blue-600 via-blue-700 to-indigo-700 pt-4 pb-16 px-3 relative overflow-hidden shadow-lg">
            <div class="max-w-[1600px] mx-auto relative z-10">
                <!-- Breadcrumbs - light on gradient -->
                <div class="flex items-center gap-2 text-white/80 text-xs mb-2 transition-all">
                    <a href="/dashboard"
                        class="hover:text-white no-underline flex items-center gap-1 transition-colors">
                        <i class="fa fa-home"></i>
                        <span>Home</span>
                    </a>
                    <i class="fa fa-chevron-right text-[10px] opacity-60"></i>
                    <a href="/tailoring/order"
                        class="hover:text-white no-underline flex items-center gap-1 transition-colors">
                        <i class="fa fa-scissors"></i>
                        <span>Tailoring</span>
                    </a>
                    <i class="fa fa-chevron-right text-[10px] opacity-60"></i>
                    <span class="text-white font-medium tracking-tight">Order</span>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <div class="bg-white/20 p-1 rounded-md mr-2">
                            <i class="fa fa-shopping-cart text-white text-xs"></i>
                        </div>
                        <div>
                            <h1 class="text-base font-bold text-white tracking-tight leading-tight">Tailoring Studio
                            </h1>
                            <p class="text-white/80 text-xs font-medium leading-tight">Create and manage your tailoring
                                orders with ease</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                        <a href="/tailoring/order"
                            class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold border border-white/20 bg-white/10 text-white hover:bg-white/20 transition-all no-underline">
                            <i class="fa fa-th-list text-xs"></i>
                            <span>Orders List</span>
                        </a>
                        <a :href="form.id && form.order_no ? `/tailoring/job-completion?order_no=${form.order_no}` : '/tailoring/job-completion'"
                            class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-white text-blue-700 hover:bg-white/95 shadow-sm transition-all no-underline">
                            <i class="fa fa-tasks text-xs"></i>
                            <span>Job Completion</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>


        <div class="max-w-[1600px] mx-auto px-3 -mt-12 relative z-20 pb-8">
            <div class="space-y-3">
                <!-- Order Header Section -->
                <OrderHeader v-model:orderNo="form.order_no" v-model:customer="form.customer_name"
                    v-model:customerId="form.account_id" v-model:contact="form.customer_mobile"
                    v-model:salesman="form.salesman_id" v-model:orderDate="form.order_date"
                    v-model:deliveryDate="form.delivery_date" :customers="customers" :salesmen="salesmen"
                    @add-customer="showCustomerModal = true" @customer-selected="handleCustomerSelected" />

                <!-- Category Selection -->
                <CategoryHeader :categories="categories" :selectedCategories="selectedCategories"
                    @category-selected="handleCategorySelection" />

                <!-- Main Content: Measurements and Styling - Modal-style cards -->
                <div v-if="selectedCategories.length > 0" class="animate-[fadeIn_0.5s_ease-out]">
                    <!-- Category Tabs - Modal payment-option style -->
                    <div class="flex gap-1.5 overflow-x-auto pb-3 scrollbar-hide">
                        <button v-for="id in selectedCategories" :key="id" @click="activeCategoryTab = id"
                            class="whitespace-nowrap px-3 py-2 rounded-lg font-bold text-xs transition-all duration-300 flex items-center gap-1.5 border-2"
                            :class="activeCategoryTab === id
                                ? 'bg-gradient-to-r from-blue-500 to-indigo-600 border-blue-500 text-white shadow-lg'
                                : 'bg-white border-slate-200 text-slate-700 hover:border-blue-300 hover:bg-blue-50'">
                            <i class="fa fa-dot-circle-o text-xs" v-if="activeCategoryTab === id"></i>
                            <span class="w-2 h-2 rounded-full bg-current opacity-40" v-else></span>
                            {{ getCategory(id)?.name }}
                        </button>
                    </div>

                    <!-- Active Category Content -->
                    <div v-if="activeCategoryTab" class="animate-[fadeSlideUp_0.4s_ease-out]">
                        <div class="bg-white rounded-xl shadow-2xl border border-slate-200 overflow-hidden">
                            <div class="px-3 py-3">
                                <div class="grid grid-cols-1 gap-3">
                                    <!-- Measurement Form - Modal section style -->
                                    <div class="mb-3">
                                        <div
                                            class="flex flex-col sm:flex-row sm:items-center justify-between mb-1.5 gap-1">
                                            <h6 class="text-xs font-bold text-slate-800 flex items-center gap-1">
                                                <i class="fa fa-pencil-square-o text-amber-500 text-xs"></i>
                                                <span>{{ getCategory(activeCategoryTab)?.name }} Measurements</span>
                                            </h6>
                                            <button type="button" @click="openPreviousMeasurements"
                                                :disabled="!form.account_id"
                                                class="shrink-0 inline-flex items-center justify-center px-3 py-1.5 rounded-lg text-xs font-semibold border border-slate-200 bg-white text-slate-700 hover:bg-blue-50 hover:border-blue-200 disabled:opacity-50 disabled:cursor-not-allowed transition-all">
                                                <i class="fa fa-history text-blue-500 text-xs mr-1"></i>
                                                Use Previous
                                            </button>
                                        </div>
                                        <div class="bg-slate-50 border border-slate-200 rounded-lg p-1.5">
                                            <MeasurementForm v-if="activeEditKey && measurements[activeEditKey]"
                                                :key="activeEditKey" v-model="measurements[activeEditKey]"
                                                :category="getCategory(activeCategoryTab)"
                                                :measurementOptions="measurementOptions"
                                                @add-option="handleAddMeasurementOption" />
                                        </div>
                                    </div>

                                    <!-- Product Selection - Modal section style -->
                                    <div class="mb-2">
                                        <h6 class="text-xs font-bold text-slate-800 mb-1 flex items-center gap-1">
                                            <i class="fa fa-shopping-cart text-emerald-500 text-xs"></i>
                                            <span>Fabric & Services</span>
                                        </h6>
                                        <div
                                            class="bg-gradient-to-r from-slate-50 to-blue-50 border border-slate-200 rounded-lg p-1.5">
                                            <ProductSelection v-if="activeEditKey && currentItems[activeEditKey]"
                                                v-model="currentItems[activeEditKey]" :products="products"
                                                :colors="colors" :isLoading="isAddingItem[activeCategoryTab]"
                                                :isEditing="!!editingItemIds[activeCategoryTab]"
                                                :barcode-from-scanner="barcodeFromScanner"
                                                @add-item="(item) => handleAddItem(item, activeCategoryTab)"
                                                @calculate-amount="(item) => calculateItemAmount(item, activeCategoryTab)"
                                                @clear="handleItemClear(activeCategoryTab)"
                                                @open-scanner="isScannerOpen = true"
                                                @clear-barcode="handleClearBarcode" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Summary and Work Orders - Modal style -->
                <div class="bg-white rounded-xl shadow-2xl border border-slate-200 overflow-hidden">
                    <div class="px-3 py-3">
                        <h6 class="text-xs font-bold text-slate-800 mb-1 flex items-center gap-1">
                            <i class="fa fa-receipt text-emerald-500 text-xs"></i>
                            Summary and Work Orders
                        </h6>
                        <div class="bg-gradient-to-r from-slate-50 to-blue-50 border border-slate-200 rounded-lg p-1.5">
                            <div class="grid grid-cols-1 lg:grid-cols-12 gap-3">
                                <div class="lg:col-span-3">
                                    <SummaryTable :items="form.items" />
                                </div>
                                <div class="lg:col-span-9">
                                    <WorkOrdersPreview :items="form.items" @edit="handleEditItem"
                                        @remove="handleRemoveItem" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="sticky bottom-4 z-30">
                    <ActionButtons :isLoading="isSubmitting" :canSubmit="canSubmit" @clear="handleClear"
                        @create-order="handleCreateOrder" @payment="handlePayment" />
                </div>
            </div>
        </div>


        <!-- Modals -->
        <CustomerModal v-if="showCustomerModal" :show="showCustomerModal" :customer-types="customerTypes"
            :countries="countries" @close="showCustomerModal = false" @customerSaved="handleCustomerAdded"
            @customerSelected="handleCustomerSelected" />

        <SaleConfirmationModal :show="showConfirmationModal" :sale-data="confirmationData" :loading="isSubmitting"
            :payment-method="selectedPaymentMethod" :send-to-whatsapp="sendToWhatsapp"
            @update:paymentMethod="val => selectedPaymentMethod = val"
            @update:sendToWhatsapp="val => sendToWhatsapp = val" @openCustomPayment="showCustomPaymentModal = true"
            @close="showConfirmationModal = false" @submit="processSubmitOrder" />

        <CustomPaymentModal :show="showCustomPaymentModal" :total-amount="grandTotal" :payment-methods="paymentMethods"
            :initial-payments="form.payments" @close="showCustomPaymentModal = false" @save="handleCustomPaymentSave" />

        <OldMeasurementModal :show="showOldMeasurementModal" :account-id="form.account_id"
            :category-id="pendingOldMeasurementCategoryId"
            :category-name="getCategory(pendingOldMeasurementCategoryId)?.name"
            :category="getCategory(pendingOldMeasurementCategoryId)" @select="handleOldMeasurementSelect"
            @skip="handleOldMeasurementSkip" />

        <BarcodeScanner :isOpen="isScannerOpen" emitRawBarcode @barcode-scanned="handleBarcodeScanned"
            @close="closeScanner" />
    </div>
</template>


<script setup>
import {
    ref,
    computed,
    onMounted
} from 'vue'
import {
    router,
    Link
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
import OldMeasurementModal from '@/components/Tailoring/OldMeasurementModal.vue'
import BarcodeScanner from '@/components/BarcodeScanner.vue'
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
    tailoringRedirectionPage: {
        type: String,
        default: 'create'
    }
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
const isScannerOpen = ref(false)
const barcodeFromScanner = ref(null)
const isSubmitting = ref(false)
const isAddingItem = ref({})
const activeCategoryTab = ref(null)
const measurementOptions = ref(props.measurementOptions || {})
const editingItemIds = ref({}) // Map of categoryId -> itemId
const editingModelIds = ref({}) // Map of categoryId -> modelId (when editing)
const showOldMeasurementModal = ref(false)
const pendingOldMeasurementCategoryId = ref(null)

const getEditKey = (catId, modelId) => `${catId}-${modelId ?? 'new'}`

const activeEditKey = computed(() => {
    if (!activeCategoryTab.value) return null
    const modelId = editingModelIds.value[activeCategoryTab.value]
    return getEditKey(activeCategoryTab.value, modelId)
})

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
    const prev = [...selectedCategories.value]
    const newlyAdded = categoryIds.filter(id => !prev.includes(id))
    const removed = prev.filter(id => !categoryIds.includes(id))

    selectedCategories.value = categoryIds

    // Initialize state for new categories (keyed by category + model: catId-new for add)
    categoryIds.forEach(id => {
        const addKey = getEditKey(id, 'new')
        if (!measurements.value[addKey]) measurements.value[addKey] = {}
        if (!currentItems.value[addKey]) currentItems.value[addKey] = {
            product_id: null,
            product_name: '',
            product_color: '',
            quantity: 1,
            quantity_per_item: 1,
            unit_price: 0,
            stitch_rate: 0,
            tax: 0,
            total: 0,
        }
    })

    // Show old measurement modal when a new category is added and customer is selected
    if (newlyAdded.length === 1 && form.value.account_id && !removed.length) {
        pendingOldMeasurementCategoryId.value = newlyAdded[0]
        showOldMeasurementModal.value = true
    } else {
        showOldMeasurementModal.value = false
        pendingOldMeasurementCategoryId.value = null
    }

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

const handleOldMeasurementSelect = (payload) => {
    const catId = pendingOldMeasurementCategoryId.value
    const addKey = catId ? getEditKey(catId, 'new') : null
    if (addKey && measurements.value[addKey]) {
        Object.assign(measurements.value[addKey], payload)
        toast.success('Previous measurements applied')
    }
    showOldMeasurementModal.value = false
    pendingOldMeasurementCategoryId.value = null
}

const handleOldMeasurementSkip = () => {
    showOldMeasurementModal.value = false
    pendingOldMeasurementCategoryId.value = null
}

const openPreviousMeasurements = () => {
    if (!activeCategoryTab.value || !form.value.account_id) return
    pendingOldMeasurementCategoryId.value = activeCategoryTab.value
    showOldMeasurementModal.value = true
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
    const editKey = getEditKey(categoryId, editingModelIds.value[categoryId])
    // Merge data from props or current state
    const item = itemData || currentItems.value[editKey]
    // Get measurements for this category + model
    const itemMeasurements = measurements.value[editKey]

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

    const modelId = itemMeasurements?.tailoring_category_model_id ?? item?.tailoring_category_model_id
    if (!modelId) {
        toast.error('Please select a model (Category Model) before adding the product')
        return
    }

    isAddingItem.value = { ...isAddingItem.value, [categoryId]: true }

    // Capture editing ID immediately to avoid race condition with clear event
    const editingId = editingItemIds.value[categoryId]

    try {
        // Calculate amounts (stateless backend call)
        const response = await axios.post('/tailoring/order/calculate-amount', {
            quantity: item.quantity,
            quantity_per_item: item.quantity_per_item ?? 1,
            unit_price: item.unit_price,
            stitch_rate: item.stitch_rate || 0,
            discount: item.discount || 0,
            tax: item.tax || 0,
        })

        if (response.data.success) {
            const calculatedData = response.data.data

            // Dynamic keys from category configuration
            const measurementKeys = (category.active_measurements || []).map(m => m.field_key)

            // Add mandatory hidden keys
            const additionalKeys = ['tailoring_category_model_id', 'tailoring_category_model_name', 'tailoring_notes']
            const allKeys = [...new Set([...measurementKeys, ...additionalKeys])]

            // Filter measurements only
            const filteredMeasurements = {}
            allKeys.forEach(key => {
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
                editingModelIds.value[categoryId] = null
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

            // Reset current item form but KEEP measurements for next item (do not reset measurement values)
            const resetKey = getEditKey(categoryId, editingModelIds.value[categoryId] ?? 'new')
            const addKey = getEditKey(categoryId, 'new')
            currentItems.value[resetKey] = {
                product_id: null,
                product_name: '',
                product_color: '',
                quantity: 0,
                quantity_per_item: 1,
                unit_price: 0,
                stitch_rate: 0,
                tax: 0,
                total: 0,
            }
            // Preserve measurement values in the add-new slot so the form stays populated for the next item
            measurements.value[addKey] = { ...filteredMeasurements }
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

    const editKey = getEditKey(categoryId, editingModelIds.value[categoryId])

    try {
        const response = await axios.post('/tailoring/order/calculate-amount', {
            quantity: item.quantity,
            quantity_per_item: item.quantity_per_item ?? 1,
            unit_price: item.unit_price,
            stitch_rate: item.stitch_rate || 0,
            discount: item.discount || 0,
            tax: item.tax || 0,
        })

        if (response.data.success) {
            currentItems.value[editKey] = { ...item, ...response.data.data }
        }
    } catch (error) {
        console.error('Failed to calculate amount', error)
    }
}

const handleEditItem = async (item) => {
    const catId = item.tailoring_category_id
    const modelId = item.tailoring_category_model_id

    // Fetch full item from API when we have order id and persisted item
    let itemToEdit = item
    if (form.value.id && item.id) {
        try {
            const response = await axios.get(`/tailoring/order/${form.value.id}/item/${item.id}`)
            if (response.data.success && response.data.data) {
                itemToEdit = response.data.data
            }
        } catch (error) {
            console.error('Failed to fetch item:', error)
            toast.error('Could not load item details')
        }
    }

    // Switch to the correct category tab
    if (!selectedCategories.value.includes(catId)) {
        selectedCategories.value.push(catId)
    }

    // Restore data to forms - keyed by category + model so each item's measurements stay separate
    const editKey = getEditKey(catId, modelId)
    currentItems.value[editKey] = { ...itemToEdit }
    measurements.value[editKey] = { ...itemToEdit }

    // Set editing state
    editingItemIds.value[catId] = itemToEdit.id || itemToEdit._temp_id
    editingModelIds.value[catId] = modelId

    // Set active tab to switch the UI view
    activeCategoryTab.value = catId

    toast.info("Item loaded for editing.")

    // Scroll to form area
    setTimeout(() => {
        window.scrollTo({ top: 300, behavior: 'smooth' })
    }, 100)
}

const handleItemClear = (categoryId) => {
    // Clear editing state when user clicks clear on the form
    editingItemIds.value[categoryId] = null
    editingModelIds.value[categoryId] = null
    // Reset add-new form slot for this category
    const addKey = getEditKey(categoryId, 'new')
    currentItems.value[addKey] = {
        product_id: null,
        product_name: '',
        product_color: '',
        quantity: 0,
        quantity_per_item: 1,
        unit_price: 0,
        stitch_rate: 0,
        tax: 0,
        total: 0,
    }
    measurements.value[addKey] = {}
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
        const modelId = item.tailoring_category_model_id
        if (editingItemIds.value[catId] === (item.id || item._temp_id)) {
            editingItemIds.value[catId] = null
            editingModelIds.value[catId] = null
            const editKey = getEditKey(catId, modelId)
            currentItems.value[editKey] = {
                product_id: null,
                product_name: '',
                product_color: '',
                quantity: 0,
                quantity_per_item: 1,
                unit_price: 0,
                stitch_rate: 0,
                tax: 0,
                total: 0,
            }
            measurements.value[editKey] = {}
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
        const url = form.value.id ? `/tailoring/order/${form.value.id}` : '/tailoring/order'

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

        const response = await axios[method](url, form.value)

        if (response.data.success) {
            showConfirmationModal.value = false
            toast.success(form.value.id ? 'Order updated successfully' : 'Order created successfully')

            // Get the order ID from response or use existing form ID
            const orderId = response.data.data?.id || form.value.id || response.data.id

            // Redirect based on tailoring config (create page vs show page)
            const redirectUrl = props.tailoringRedirectionPage === 'show'
                ? `/tailoring/order/${orderId}`
                : '/tailoring/order/create'
            window.location.href = redirectUrl
        } else {
            toast.error(response.data.message || 'Failed to save order')
            isSubmitting.value = false
        }
    } catch (error) {
        toast.error(error.response?.data?.message || Object.values(error.response?.data?.errors || {})[0] || 'Failed to save order')
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
    currentItems.value = {}
    editingItemIds.value = {}
    editingModelIds.value = {}
    activeCategoryTab.value = null
    selectedCategories.value = []
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

const handleBarcodeScanned = (data) => {
    barcodeFromScanner.value = data?.code ? data : null
}

const closeScanner = () => {
    isScannerOpen.value = false
}

const handleClearBarcode = () => {
    barcodeFromScanner.value = null
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

    // Initialize from existing order items
    if (props.order?.items?.length > 0) {
        const categoryIds = [...new Set(props.order.items.map(i => i.tailoring_category_id))]
        selectedCategories.value = categoryIds

        // Initialize add-new slots for each category (measurements keyed by category + model)
        categoryIds.forEach(catId => {
            const addKey = getEditKey(catId, 'new')
            measurements.value[addKey] = {}
            currentItems.value[addKey] = {
                product_id: null,
                product_name: '',
                product_color: '',
                quantity: 1,
                quantity_per_item: 1,
                unit_price: 0,
                stitch_rate: 0,
                tax: 0,
                total: 0,
            }
        })

        if (categoryIds.length > 0) {
            activeCategoryTab.value = categoryIds[0]
        }
    }
})
</script>

<style scoped>
@keyframes shimmer {
    0% {
        transform: translateX(-100%);
    }

    100% {
        transform: translateX(100%);
    }
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }

    to {
        opacity: 1;
    }
}

@keyframes fadeSlideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.scrollbar-hide::-webkit-scrollbar {
    display: none;
}

.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
