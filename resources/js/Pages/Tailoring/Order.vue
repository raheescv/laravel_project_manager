<template>
    <div ref="pageRoot" class="min-h-screen bg-[#f8fafc] font-sans" @keydown="handleKeyboardNavigation">
        <!-- Page Header - SaleConfirmationModal style gradient -->
        <div
            class="bg-gradient-to-r from-blue-600 via-blue-700 to-indigo-700 pt-4 pb-16 px-3 relative overflow-hidden shadow-lg">
            <div class="max-w-[1600px] mx-auto relative z-10">
                <!-- Breadcrumbs - light on gradient -->
                <div class="flex items-center gap-2 text-white/80 text-xs mb-2 transition-all">
                    <a href="/dashboard" tabindex="-1"
                        class="hover:text-white no-underline flex items-center gap-1 transition-colors">
                        <i class="fa fa-home"></i>
                        <span>Home</span>
                    </a>
                    <i class="fa fa-chevron-right text-[10px] opacity-60"></i>
                    <a href="/tailoring/order" tabindex="-1"
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
                        <a href="/tailoring/order" tabindex="-1"
                            class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold border border-white/20 bg-white/10 text-white hover:bg-white/20 transition-all no-underline">
                            <i class="fa fa-th-list text-xs"></i>
                            <span>Orders List</span>
                        </a>
                        <a tabindex="-1"
                            :href="form.id && form.order_no ? `/tailoring/job-completion?order_no=${form.order_no}` : '/tailoring/job-completion'"
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
                                            <div class="relative flex items-center gap-1.5">
                                                <button type="button" @click="openPreviousMeasurements"
                                                    :disabled="!form.account_id"
                                                    class="shrink-0 inline-flex items-center justify-center px-3 py-1.5 rounded-lg text-xs font-semibold border border-slate-200 bg-white text-slate-700 hover:bg-blue-50 hover:border-blue-200 disabled:opacity-50 disabled:cursor-not-allowed transition-all">
                                                    <i class="fa fa-history text-blue-500 text-xs mr-1"></i>
                                                    Use Previous
                                                </button>
                                                <button type="button" @click="toggleCartMeasurementPicker"
                                                    :disabled="!cartMeasurementPresets.length"
                                                    class="shrink-0 inline-flex items-center justify-center px-3 py-1.5 rounded-lg text-xs font-semibold border border-slate-200 bg-white text-slate-700 hover:bg-emerald-50 hover:border-emerald-200 disabled:opacity-50 disabled:cursor-not-allowed transition-all">
                                                    <i class="fa fa-clone text-emerald-500 text-xs mr-1"></i>
                                                    From Cart
                                                </button>

                                                <div v-if="showCartMeasurementPicker"
                                                    class="absolute right-0 top-full mt-1.5 w-[360px] max-w-[92vw] rounded-lg border border-slate-200 bg-white shadow-xl z-30 overflow-hidden">
                                                    <div
                                                        class="px-2.5 py-2 text-[0.65rem] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100 bg-slate-50">
                                                        Use Current Cart Measurements
                                                    </div>
                                                    <div class="max-h-72 overflow-y-auto p-1">
                                                        <button v-for="(preset, idx) in cartMeasurementPresets"
                                                            :key="preset.id || idx" type="button"
                                                            @click="applyCartMeasurementPreset(preset)"
                                                            class="w-full text-left p-2 rounded-md hover:bg-emerald-50 border border-transparent hover:border-emerald-200 transition-colors">
                                                            <div class="text-xs font-semibold text-slate-800 truncate">
                                                                {{ preset.tailoring_category_model_name || 'Model -' }} /
                                                                {{ preset.tailoring_category_model_type_name || 'Type -' }}
                                                            </div>
                                                            <div class="text-[0.68rem] text-slate-500 truncate">
                                                                {{ getCartMeasurementPreview(preset) }}
                                                            </div>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="bg-slate-50 border border-slate-200 rounded-lg p-1.5">
                                            <MeasurementForm v-if="activeEditKey && measurements[activeEditKey]"
                                                :key="activeEditKey" v-model="measurements[activeEditKey]"
                                                :category="getCategory(activeCategoryTab)"
                                                :measurementOptions="measurementOptions"
                                                :canQuickAddMeasurementOption="props.canQuickAddMeasurementOption"
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
            :initial-payments="form.payments" :session-date="form.order_date" @close="showCustomPaymentModal = false"
            @save="handleCustomPaymentSave" />

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
    canQuickAddMeasurementOption: {
        type: Boolean,
        default: false
    },
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
const editingModelTypeIds = ref({}) // Map of categoryId -> modelTypeId (when editing)
const showOldMeasurementModal = ref(false)
const pendingOldMeasurementCategoryId = ref(null)
const showCartMeasurementPicker = ref(false)
const pageRoot = ref(null)
const PREFILL_STORAGE_KEY = 'tailoring_order_prefill_v1'

const focusableSelector = [
    'input:not([type="hidden"]):not([disabled])',
    'select:not([disabled])',
    'textarea:not([disabled])',
    'button:not([disabled])',
    'a[href]',
    '[tabindex]:not([tabindex="-1"])'
].join(', ')

const isVisibleFocusable = (el) => {
    if (!el || !(el instanceof HTMLElement)) return false
    if (el.hasAttribute('disabled')) return false
    if (el.getAttribute('aria-hidden') === 'true') return false
    return el.offsetParent !== null || el.getClientRects().length > 0
}

const getFocusableElements = () => {
    if (!pageRoot.value) return []
    return Array.from(pageRoot.value.querySelectorAll(focusableSelector))
        .filter(isVisibleFocusable)
}

const focusByStep = (currentTarget, step) => {
    const focusableElements = getFocusableElements()
    if (focusableElements.length === 0) return

    const currentFocusable = currentTarget?.closest?.(focusableSelector) || currentTarget
    const currentIndex = focusableElements.indexOf(currentFocusable)
    const fallbackIndex = step > 0 ? -1 : 0
    const baseIndex = currentIndex === -1 ? fallbackIndex : currentIndex
    const nextIndex = (baseIndex + step + focusableElements.length) % focusableElements.length
    const nextEl = focusableElements[nextIndex]
    if (nextEl) {
        nextEl.focus()
        nextEl.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' })
    }
}

const shouldSkipCustomHandling = (target) => {
    if (!(target instanceof HTMLElement)) return true
    if (target.isContentEditable) return true
    if (target.closest('[contenteditable="true"]')) return true

    const tagName = target.tagName?.toLowerCase()
    if (tagName === 'textarea') return true

    return false
}

const handleKeyboardNavigation = (event) => {
    if (event.defaultPrevented || event.isComposing) return
    if (!(event.target instanceof HTMLElement)) return
    if (shouldSkipCustomHandling(event.target)) return

    if (event.key === 'Enter' && !event.ctrlKey && !event.metaKey && !event.altKey && !event.shiftKey) {
        const target = event.target.closest(focusableSelector) || event.target
        event.preventDefault()

        if (target instanceof HTMLButtonElement ||
            (target instanceof HTMLInputElement && ['checkbox', 'radio'].includes(target.type))) {
            target.click()
        }

        focusByStep(target, 1)
        return
    }

    if (event.key === 'Tab' && event.shiftKey) {
        const target = event.target.closest(focusableSelector) || event.target
        event.preventDefault()
        // Standard behavior override: Shift+Tab goes backward.
        focusByStep(target, -1)
    }
}

const getEditKey = (catId, modelId, modelTypeId) => `${catId}-${modelId ?? 'new'}-${modelTypeId ?? 'new'}`

const activeEditKey = computed(() => {
    if (!activeCategoryTab.value) return null
    const modelId = editingModelIds.value[activeCategoryTab.value]
    const modelTypeId = editingModelTypeIds.value[activeCategoryTab.value]
    return getEditKey(activeCategoryTab.value, modelId, modelTypeId)
})

const cartMeasurementPresets = computed(() => {
    const categoryId = Number(activeCategoryTab.value || 0)
    if (!categoryId) return []

    const category = getCategory(categoryId)
    if (!category) return []

    const measurementKeys = (category.active_measurements || []).map(m => m.field_key)
    const keysToCheck = [...new Set([...measurementKeys, 'tailoring_notes'])]

    return (form.value.items || []).filter(item => {
        if (Number(item.tailoring_category_id) !== categoryId) return false
        return keysToCheck.some(key => item[key] !== undefined && item[key] !== null && item[key] !== '')
    })
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

const initializeCategoryStateFromItems = (items = []) => {
    const categoryIds = [...new Set((items || []).map(i => i.tailoring_category_id).filter(Boolean))]
    selectedCategories.value = categoryIds

    categoryIds.forEach(catId => {
        const addKey = getEditKey(catId, 'new', 'new')
        if (!measurements.value[addKey]) measurements.value[addKey] = {}
        if (!currentItems.value[addKey]) {
            currentItems.value[addKey] = {
                inventory_id: null,
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
        }
    })

    if (categoryIds.length > 0) {
        activeCategoryTab.value = categoryIds[0]
    }
}

const normalizePrefillItem = (rawItem = {}, index = 0) => {
    const categoryId = Number(rawItem.tailoring_category_id || 0) || null
    const modelId = rawItem.tailoring_category_model_id ? Number(rawItem.tailoring_category_model_id) : null
    const modelTypeId = rawItem.tailoring_category_model_type_id ? Number(rawItem.tailoring_category_model_type_id) : null
    const category = categoryId ? getCategory(categoryId) : null

    const normalized = {
        ...rawItem,
        _temp_id: `prefill-${Date.now()}-${index}-${Math.random().toString(36).slice(2, 8)}`,
        item_no: index + 1,
        tailoring_category_id: categoryId,
        tailoring_category_model_id: modelId,
        tailoring_category_model_type_id: modelTypeId,
        quantity: parseFloat(rawItem.quantity || 0) || 1,
        quantity_per_item: parseFloat(rawItem.quantity_per_item || 1) || 1,
        unit_price: parseFloat(rawItem.unit_price || 0) || 0,
        stitch_rate: parseFloat(rawItem.stitch_rate || 0) || 0,
        discount: parseFloat(rawItem.discount || 0) || 0,
        tax: parseFloat(rawItem.tax || 0) || 0,
        total: parseFloat(rawItem.total || 0) || 0,
        category: category || null
    }

    return normalized
}

const hydrateFromOrderSearchPrefill = () => {
    if (form.value.id) return

    let payloadRaw = null
    try {
        payloadRaw = sessionStorage.getItem(PREFILL_STORAGE_KEY)
    } catch (error) {
        return
    }

    if (!payloadRaw) return

    try {
        const payload = JSON.parse(payloadRaw)
        const prefillItems = Array.isArray(payload?.items)
            ? payload.items.map((item, index) => normalizePrefillItem(item, index))
            : []

        if (prefillItems.length === 0) {
            sessionStorage.removeItem(PREFILL_STORAGE_KEY)
            return
        }

        const accountId = Number(payload?.customer?.account_id || 0)
        form.value.account_id = accountId > 0 ? accountId : null
        form.value.customer_name = payload?.customer?.customer_name || ''
        form.value.customer_mobile = payload?.customer?.customer_mobile || ''
        form.value.items = prefillItems

        measurements.value = {}
        currentItems.value = {}
        editingItemIds.value = {}
        editingModelIds.value = {}
        editingModelTypeIds.value = {}
        initializeCategoryStateFromItems(prefillItems)

        toast.success('Selected items loaded for new order')
        sessionStorage.removeItem(PREFILL_STORAGE_KEY)
    } catch (error) {
        console.error('Failed to read prefill data', error)
        sessionStorage.removeItem(PREFILL_STORAGE_KEY)
    }
}

// Methods
const handleCategorySelection = (categoryIds) => {
    showCartMeasurementPicker.value = false
    const prev = [...selectedCategories.value]
    const newlyAdded = categoryIds.filter(id => !prev.includes(id))
    const removed = prev.filter(id => !categoryIds.includes(id))

    selectedCategories.value = categoryIds

    // Initialize state for new categories (keyed by category + model + modelType: catId-new-new for add)
    categoryIds.forEach(id => {
        const addKey = getEditKey(id, 'new', 'new')
        if (!measurements.value[addKey]) measurements.value[addKey] = {}
        if (!currentItems.value[addKey]) currentItems.value[addKey] = {
            inventory_id: null,
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
    const addKey = catId ? getEditKey(catId, 'new', 'new') : null
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

const toggleCartMeasurementPicker = () => {
    showCartMeasurementPicker.value = !showCartMeasurementPicker.value
}

const getCartMeasurementPreview = (item) => {
    const category = getCategory(activeCategoryTab.value)
    const measurementFields = (category?.active_measurements || [])
    const parts = []

    for (const field of measurementFields) {
        const value = item?.[field.field_key]
        if (value !== undefined && value !== null && value !== '') {
            parts.push(`${field.label}: ${value}`)
            if (parts.length >= 3) break
        }
    }

    return parts.length ? parts.join(' | ') : 'No measurement preview'
}

const applyCartMeasurementPreset = (sourceItem) => {
    if (!activeCategoryTab.value || !sourceItem) return

    const categoryId = activeCategoryTab.value
    const category = getCategory(categoryId)
    const editKey = activeEditKey.value || getEditKey(
        categoryId,
        editingModelIds.value[categoryId],
        editingModelTypeIds.value[categoryId]
    )

    if (!measurements.value[editKey]) {
        measurements.value[editKey] = {}
    }

    const current = measurements.value[editKey]
    const preservedModelFields = {
        tailoring_category_model_id: current.tailoring_category_model_id,
        tailoring_category_model_name: current.tailoring_category_model_name,
        tailoring_category_model_type_id: current.tailoring_category_model_type_id,
        tailoring_category_model_type_name: current.tailoring_category_model_type_name,
    }

    const measurementKeys = (category?.active_measurements || []).map(m => m.field_key)
    const keysToCopy = [...new Set([...measurementKeys, 'tailoring_notes'])]
    const nextValues = {}

    keysToCopy.forEach((key) => {
        if (sourceItem[key] !== undefined) {
            nextValues[key] = sourceItem[key]
        }
    })

    measurements.value[editKey] = {
        ...current,
        ...nextValues,
        ...preservedModelFields
    }

    showCartMeasurementPicker.value = false
    toast.success('Cart measurements applied')
}

const openPreviousMeasurements = () => {
    if (!activeCategoryTab.value || !form.value.account_id) return
    showCartMeasurementPicker.value = false
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
    const editKey = getEditKey(categoryId, editingModelIds.value[categoryId], editingModelTypeIds.value[categoryId])
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

    const modelTypeId = itemMeasurements?.tailoring_category_model_type_id ?? item?.tailoring_category_model_type_id
    if (!modelTypeId) {
        toast.error('Please select a model type (Category Model Type) before adding the product')
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
            const additionalKeys = ['tailoring_category_model_id', 'tailoring_category_model_name', 'tailoring_category_model_type_id', 'tailoring_category_model_type_name', 'tailoring_notes']
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
                    form.value.items = [...form.value.items]
                    toast.success('Item updated successfully')
                } else {
                    // Item might have been deleted while editing? Add as new
                    finalItem._temp_id = Date.now() + Math.random().toString(36).substr(2, 9)
                    form.value.items.push(finalItem)
                    form.value.items = [...form.value.items]
                    toast.success('Item added (original not found)')
                }

                // Clear editing state
                editingItemIds.value[categoryId] = null
                editingModelIds.value[categoryId] = null
                editingModelTypeIds.value[categoryId] = null
            } else {
                // New item
                finalItem._temp_id = Date.now() + Math.random().toString(36).substr(2, 9)
                form.value.items.push(finalItem)
                form.value.items = [...form.value.items]
                toast.success('Item added to order')
            }

            // Sync measurements to all items of the same category, model AND model type
            form.value.items.forEach((it, idx) => {
                it.item_no = idx + 1
                if (it.tailoring_category_id === categoryId &&
                    it.tailoring_category_model_id === filteredMeasurements.tailoring_category_model_id &&
                    it.tailoring_category_model_type_id === filteredMeasurements.tailoring_category_model_type_id) {
                    // Update measurement fields ONLY
                    Object.assign(it, filteredMeasurements)
                }
            })
            form.value.items = [...form.value.items]

            // Reset current item form but KEEP measurements for next item (do not reset measurement values)
            const resetKey = getEditKey(categoryId, editingModelIds.value[categoryId] ?? 'new', editingModelTypeIds.value[categoryId] ?? 'new')
            const addKey = getEditKey(categoryId, 'new', 'new')
            currentItems.value[resetKey] = {
                inventory_id: null,
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
            // Exclude tailoring_notes from preserved measurements so notes are not carried over
            const preservedMeasurements = { ...filteredMeasurements }
            delete preservedMeasurements.tailoring_notes
            measurements.value[addKey] = preservedMeasurements
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

    const editKey = getEditKey(categoryId, editingModelIds.value[categoryId], editingModelTypeIds.value[categoryId])

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
    const modelTypeId = item.tailoring_category_model_type_id

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

    // Restore data to forms - keyed by category + model + model type so each item's measurements stay separate
    const editKey = getEditKey(catId, modelId, modelTypeId)
    currentItems.value[editKey] = { ...itemToEdit }
    measurements.value[editKey] = { ...itemToEdit }

    // Set editing state
    editingItemIds.value[catId] = itemToEdit.id || itemToEdit._temp_id
    editingModelIds.value[catId] = modelId
    editingModelTypeIds.value[catId] = modelTypeId

    // Set active tab to switch the UI view
    activeCategoryTab.value = catId

    toast.info("Item loaded for editing.")

    // Scroll to form area
    setTimeout(() => {
        window.scrollTo({ top: 300, behavior: 'smooth' })
    }, 100)
}

const handleItemClear = (categoryId) => {
    showCartMeasurementPicker.value = false
    // Clear editing state when user clicks clear on the form
    editingItemIds.value[categoryId] = null
    editingModelIds.value[categoryId] = null
    editingModelTypeIds.value[categoryId] = null
    // Reset add-new form slot for this category
    const addKey = getEditKey(categoryId, 'new', 'new')
    currentItems.value[addKey] = {
        inventory_id: null,
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
        const modelTypeId = item.tailoring_category_model_type_id
        if (editingItemIds.value[catId] === (item.id || item._temp_id)) {
            editingItemIds.value[catId] = null
            editingModelIds.value[catId] = null
            editingModelTypeIds.value[catId] = null
            const editKey = getEditKey(catId, modelId, modelTypeId)
            currentItems.value[editKey] = {
                inventory_id: null,
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
    syncSelectedPaymentMethodFromPayments()
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

            // Open thermal receipt print in new window
            window.open(`/tailoring/order/print/receipt-thermal/${orderId}`, '_blank')

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
    editingModelTypeIds.value = {}
    activeCategoryTab.value = null
    selectedCategories.value = []
}

// Payment handling via confirmation flow
const handlePayment = () => {
    syncSelectedPaymentMethodFromPayments()
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

const inferPaymentMethodFromPayments = (payments = [], grandTotalAmount = 0) => {
    if (!Array.isArray(payments) || payments.length === 0) {
        return 'credit'
    }

    const totalPaidAmount = payments.reduce((sum, payment) => sum + parseFloat(payment?.amount || 0), 0)
    if (payments.length > 1 || totalPaidAmount < (parseFloat(grandTotalAmount || 0) - 0.01)) {
        return 'custom'
    }

    const methodIds = [...new Set(
        payments
            .map(payment => Number(payment?.payment_method_id))
            .filter(id => Number.isFinite(id) && id > 0)
    )]

    if (methodIds.length === 1 && (methodIds[0] === 1 || methodIds[0] === 2)) {
        return methodIds[0]
    }

    return 'custom'
}

const syncSelectedPaymentMethodFromPayments = (force = false) => {
    const inferredMethod = inferPaymentMethodFromPayments(form.value.payments, grandTotal.value)
    if (force || inferredMethod === 'custom') {
        selectedPaymentMethod.value = inferredMethod
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
    hydrateFromOrderSearchPrefill()

    if (form.value.id) {
        syncSelectedPaymentMethodFromPayments(true)
    }

    // Initialize from existing order items
    if (form.value.items?.length > 0) {
        initializeCategoryStateFromItems(form.value.items)
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
