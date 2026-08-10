<template>
    <div class="posx min-h-screen md:max-h-screen relative md:overflow-hidden" :data-pos-preset="colorPreset">
        <!-- Two soft ambient glows from the preset, nothing more -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="posx-glow -top-20 sm:-top-40 -right-20 sm:-right-40 w-48 h-48 sm:w-96 sm:h-96"></div>
            <div class="posx-glow accent -bottom-20 sm:-bottom-40 -left-20 sm:-left-40 w-48 h-48 sm:w-96 sm:h-96"></div>
        </div>

        <div class="container-fluid min-h-screen md:h-screen relative z-10 flex flex-col md:overflow-hidden">
            <form class="flex-1 flex flex-col min-h-0 md:overflow-hidden">
                <!-- Three-column shell. Responsive on the shell's OWN width via
                     container queries, not the viewport — see pos-premium.css. -->
                <div class="posx-shell flex-1 min-h-0">
                    <div class="posx-grid">
                        <!-- Categories -->
                        <div class="posx-col posx-col-side">
                            <CategoriesSidebar :categories="categories" :selected-category="selectedCategory"
                                @category-selected="selectCategory" />
                        </div>

                        <!-- Products -->
                        <div class="posx-col posx-col-main">
                                <!-- Compact customer - employee-product search area -->
                            <div class="posx-panel mb-2 sm:mb-2 p-2.5 sm:p-3 md:p-2.5 relative flex-shrink-0 mobile-search-area">

                                <!-- Ticket context: which sale day this books into -->
                                <div class="posx-ctx">
                                    <div v-if="daySession"
                                        :title="daySessionTitle || ('Session: ' + daySession.label)"
                                        :class="['posx-session', daySession.is_today ? '' : 'is-warn']">
                                        <i :class="daySession.is_today ? 'fa fa-calendar-o' : 'fa fa-exclamation-triangle'"></i>
                                        <span class="posx-session-key">Session</span>
                                        <span class="posx-session-date">{{ daySession.label }}</span>
                                    </div>
                                    <span v-else></span>

                                    <button type="button" @click="viewDraftSales"
                                        class="posx-btn posx-btn-ghost posx-btn-sm" title="Open draft sales">
                                        <i class="fa fa-file-text-o"></i>
                                        <span>Drafts</span>
                                    </button>
                                </div>

                                <!-- Customer · Mobile · Employee · Sale Type — one row -->
                                <div class="posx-ctrl-grid">
                                    <div class="space-y-1.5">
                                        <div class="posx-label-row">
                                            <label class="posx-label mb-0">
                                                <i class="fa fa-user"></i>
                                                <span>Customer</span>
                                            </label>
                                        </div>
                                        <div class="posx-group">
                                            <SearchableSelect v-model="form.account_id" :options="formattedCustomers"
                                                placeholder="Select Customer"
                                                filter-placeholder="Search by name or mobile..." :visibleItems="8"
                                                @search="searchCustomers" @change="handleCustomerChange"
                                                input-class="posx-field posx-field-select" />
                                            <button type="button" class="posx-gbtn" @click="viewCustomerDetails"
                                                :disabled="!form.account_id || form.account_id === 3"
                                                :class="{ 'has-flag': hasCustomerFeedbacks }"
                                                :title="hasCustomerFeedbacks ? 'View customer details — has feedback' : 'View customer details'">
                                                <i class="fa fa-eye"></i>
                                            </button>
                                            <button type="button" class="posx-gbtn add" @click="addNewCustomer"
                                                title="Add new customer">
                                                <i class="fa fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="space-y-1.5">
                                        <div class="posx-label-row">
                                            <label class="posx-label mb-0">
                                                <i class="fa fa-phone"></i>
                                                <span>Mobile</span>
                                            </label>
                                        </div>
                                        <input v-model="form.customer_mobile" type="tel" class="posx-field"
                                            placeholder="Enter mobile number">
                                    </div>

                                    <div class="space-y-1.5">
                                        <label class="posx-label mb-1.5">
                                            <i class="fa fa-user"></i>
                                            <span>Employee</span>
                                        </label>
                                        <SearchableSelect ref="employeeSelectRef" v-model="form.employee_id" :options="employees"
                                            placeholder="Select employee..." filter-placeholder="Search employees..."
                                            :visibleItems="8"
                                            data-employee-select="true"
                                            input-class="posx-field posx-field-select" />
                                    </div>
                                </div>

                                <!-- Products · Barcode · Search · Drafts -->
                                <div class="posx-search-row mt-2">
                                        <div class="posx-f">
                                            <select v-model="selectedProductType" @change="filterByProductType"
                                                class="posx-field">
                                                <option v-for="option in productTypeOptions" :key="option.value"
                                                    :value="option.value">
                                                    {{ option.label }}
                                                </option>
                                            </select>
                                            <i class="fa fa-angle-down posx-caret"></i>
                                        </div>
                                        <div class="posx-f has-ico">
                                            <i class="fa fa-barcode posx-field-ico"></i>
                                            <input v-model="barcodeKey" @input="searchByBarcode" type="text"
                                                class="posx-field"
                                                placeholder="Scan barcode" autocomplete="off">
                                        </div>
                                        <div class="posx-f has-ico">
                                            <i class="fa fa-search posx-field-ico"></i>
                                            <input v-model="productKey" @input="searchProducts" type="text"
                                                class="posx-field"
                                                placeholder="Search products" autocomplete="off">
                                        </div>
                                        <SearchableSelect v-model="form.sale_type" :options="priceTypes"
                                            placeholder="Sale type" filter-placeholder="Search sale types..."
                                            :visibleItems="8"
                                            input-class="posx-field posx-field-select"
                                            @change="loadProducts" />
                                </div>
                            </div>

                            <!-- Products Grid -->
                            <div class="posx-panel flex-1 p-2 min-h-0 flex flex-col">
                                <div class="posx-prods-scroll products-container custom-scrollbar">
                                    <div v-if="loading" class="flex items-center justify-center h-full">
                                        <div class="text-center">
                                            <div class="posx-spinner animate-spin h-12 w-12 sm:h-16 sm:w-16 mx-auto mb-4"></div>
                                            <p class="posx-ink-2 text-sm sm:text-base">Loading products...</p>
                                        </div>
                                    </div>
                                    <products-grid v-else :products="products" :lowStockThreshold="10"
                                        @product-selected="addProductToCart"></products-grid>
                                </div>
                            </div>
                        </div>

                        <!-- Cart -->
                        <div class="posx-col posx-col-cart mobile-cart-container">
                            <div class="posx-panel h-full flex flex-col min-h-0 mobile-cart-wrapper">
                                <!-- Cart Items Component -->
                                <CartItems :items="form.items" :total-quantity="totalQuantity"
                                    :can-feedback="canFeedback"
                                    @view-cart-items="viewCartItems" @clear-cart="clearCart"
                                    @update-item-quantity="updateItemQuantity" @edit-cart-item="editCartItem"
                                    @remove-cart-item="removeCartItem" @increase-quantity="increaseQuantity"
                                    @decrease-quantity="decreaseQuantity" @manage-combo-offer="manageComboOffer"
                                    @open-feedback="openFeedback"
                                    item-class="min-h-[64px] py-4" />



                                <!-- Discount Only (full width) -->
                                <div class="p-1.5 sm:p-2 border-t posx-divide">
                                    <div class="mb-1.5 sm:mb-2">
                                        <label class="posx-label mb-1">
                                            <i class="fa fa-tag"></i>
                                            <span>Discount</span>
                                        </label>
                                        <div class="flex items-stretch gap-1.5 discount-input-shell">
                                            <div class="posx-f has-ico flex-1">
                                                <i class="fa fa-tag posx-field-ico"></i>
                                                <input v-model.number="form.other_discount" @input="calculateTotals"
                                                    type="number" step="0.01" min="0" class="posx-field"
                                                    placeholder="0">
                                            </div>
                                            <button type="button" @click="convertDiscountToPercentage"
                                                class="posx-btn posx-btn-ghost shrink-0" style="width:40px;padding:0">
                                                %
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Order Total -->
                                    <div class="posx-panel-2 rounded-xl px-2 py-1.5 mb-1 sm:mb-1.5 border posx-divide mobile-summary-card">
                                        <div class="posx-total-row">
                                            <span class="flex items-center gap-2">
                                                <i class="fa fa-calculator posx-muted"></i>
                                                <span class="hidden sm:inline">Sub Total</span>
                                                <span class="sm:hidden">Subtotal</span>
                                            </span>
                                            <span class="posx-amount-muted">{{ formatNumber(form.total) }}</span>
                                        </div>
                                        <div class="posx-total-row" v-if="form.other_discount > 0">
                                            <span class="flex items-center gap-2">
                                                <i class="fa fa-tag posx-muted"></i>
                                                <span class="hidden sm:inline">Discount</span>
                                                <span class="sm:hidden">Disc</span>
                                                <span v-if="!isNaN(discountPercentage) && isFinite(discountPercentage)">({{ discountPercentage }}%)</span>
                                            </span>
                                            <span class="posx-amount-danger">-{{ formatNumber(form.other_discount) }}</span>
                                        </div>
                                        <div class="posx-total-row" v-if="Math.abs(form.round_off) > 0.01">
                                            <span class="flex items-center gap-2">
                                                <i class="fa fa-adjust posx-muted"></i>
                                                <span class="hidden sm:inline">Round Off</span>
                                                <span class="sm:hidden">Round</span>
                                            </span>
                                            <span class="posx-amount-muted">{{ Number(form.round_off).toFixed(2) }}</span>
                                        </div>
                                        <div class="posx-total-row posx-total-grand">
                                            <span>Total</span>
                                            <span class="posx-amount">{{ formatNumber(form.grand_total) }}</span>
                                        </div>
                                    </div>

                                    <!-- Action Buttons - Enhanced mobile/tablet optimized -->
                                    <div class="space-y-2 sm:space-y-2 mobile-action-buttons">
                                        <div class="grid grid-cols-2 gap-2 sm:gap-2">
                                            <button type="button" @click="saveDraft"
                                                class="posx-btn posx-btn-ghost min-h-[44px] sm:min-h-[42px]">
                                                <i class="fa fa-save"></i>
                                                <span>Draft</span>
                                            </button>
                                            <button type="button" @click="submitSale"
                                                :disabled="Object.keys(form.items).length === 0"
                                                class="posx-btn posx-btn-primary min-h-[44px] sm:min-h-[42px]">
                                                <i class="fa fa-check-circle"></i>
                                                <span class="font-extrabold">Submit</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- End cart -->
                    </div>
                </div>
            </form>
        </div>

        <!-- Cart Items Modal -->
        <CartItemsModal :show="showCartModal" :cart-items-by-employee="cartItemsByEmployee"
            :total-items="Object.keys(form.items).length" :total-quantity="totalQuantity"
            :can-edit-item-price="canEditItemPrice" @close="showCartModal = false"
            @update-item-quantity="updateItemQuantity" @remove-cart-item="removeCartItem"
            @update-item-field="updateItemField" />

        <!-- Customer Modal -->
        <CustomerModal :show="showCustomerModal" :initial-customer="newCustomer" :customer-types="customerTypes"
            :countries="countries" @close="showCustomerModal = false" @customer-saved="handleCustomerSaved"
            @customer-selected="handleCustomerSelected" />

        <!-- Customer Details Modal (using CustomerModal in view mode) -->
        <CustomerModal :show="showCustomerDetailsModal" mode="view" :customer-id="selectedCustomerId"
            @close="showCustomerDetailsModal = false" @customerSaved="handleCustomerEdit" />

        <!-- Custom Payment Modal -->
        <CustomPaymentModal :show="showCustomPaymentModal" :total-amount="form.grand_total"
            :payment-methods="paymentMethods" :initial-payments="customPaymentData.payments"
            @close="closeCustomPaymentModal" @save="handleCustomPaymentSave" />

        <!-- Feedback Modal -->
        <FeedbackModal :show="showFeedbackModal" :sale="form" @close="closeFeedbackModal"
            @feedback-submitted="handleFeedbackSubmitted" />

        <!-- Sale Confirmation Modal -->
        <SaleConfirmationModal :show="showConfirmationModal" :sale-data="confirmationData" :loading="submitting"
            :payment-method="selectedPaymentMethod" :send-to-whatsapp="sendToWhatsapp"
            @update:paymentMethod="selectPaymentMethod" @update:sendToWhatsapp="val => sendToWhatsapp = val"
            @openCustomPayment="showCustomPaymentModal = true" @close="closeConfirmationModal"
            @submit="processSubmitSale" />

        <!-- Draft Sales Modal -->
        <DraftSalesModal :show="showDraftSalesModal" @close="closeDraftSalesModal" @draft-loaded="handleDraftLoaded" />

        <!-- Edit Item Modal as a component -->
        <EditItemModal :show="showEditItemModal" :item="editItemData" :employees="employees"
            :can-edit-item-price="canEditItemPrice" @close="showEditItemModal = false" @save="onEditItemSave" />

        <!-- Combo Offer Modal -->
        <ComboOfferModal :show="showComboOfferModal" :cart-items="form.items" :initial-combo-offers="form.comboOffers"
            @close="closeComboOfferModal" @save="handleComboOfferSave" @openSettings="openComboOfferSettings" />
    </div>
</template>

<script>
import CartItems from '@/components/CartItems.vue'
import CartItemsModal from '@/components/CartItemsModal.vue'
import CategoriesSidebar from '@/components/CategoriesSidebar.vue'
import CustomerModal from '@/components/CustomerModal.vue'
import CustomPaymentModal from '@/components/CustomPaymentModal.vue'
import DraftSalesModal from '@/components/DraftSalesModal.vue'
import EditItemModal from '@/components/EditItemModal.vue'
import FeedbackModal from '@/components/FeedbackModal.vue'
import ProductsGrid from '@/components/ProductsGrid.vue'
import SaleConfirmationModal from '@/components/SaleConfirmationModal.vue'
import SearchableSelect from '@/components/SearchableSelectFixed.vue'
import ComboOfferModal from '@/components/ComboOfferModal.vue'

import {
    useForm
} from '@inertiajs/vue3'
import {
    computed,
    nextTick,
    onMounted,
    onUnmounted,
    ref,
    watch
} from 'vue'
import {
    useToast
} from 'vue-toastification'
import {
    calculateItemTotals,
    calculateCartTotals,
    applyComboOfferPricing,
    resetComboOfferPricing
} from '@/utils/itemCalculations'

export default {
    components: {
        CartItems,
        SearchableSelect,
        CustomerModal,
        CartItemsModal,
        CategoriesSidebar,
        CustomPaymentModal,
        DraftSalesModal,
        FeedbackModal,
        ProductsGrid,
        SaleConfirmationModal,
        EditItemModal,
        ComboOfferModal,

    },
    props: {
        categories: Array,
        employees: Object,
        customers: Object, // Keep this as fallback
        priceTypes: Object,
        saleData: Object,
        customerTypes: {
            type: Object,
            default: () => ({})
        },
        countries: {
            type: Object,
            default: () => ({})
        },
        paymentMethods: {
            type: Array,
            default: () => []
        },
        defaultProductType: {
            type: String,
            default: 'service'
        },
        defaultCustomerEnabled: {
            type: Boolean,
            default: true
        },
        defaultQuantity: {
            type: Number,
            default: 0.001
        },
        saleItemRowMode: {
            type: String,
            default: 'merge'
        },
        canEditItemPrice: {
            type: Boolean,
            default: false
        },
        canFeedback: {
            type: Boolean,
            default: false
        },
        // The open sale-day session this ticket books into: { id, date, label,
        // opened_at, is_today }. The sale takes its date from the session rather
        // than from today, so the header shows it.
        daySession: {
            type: Object,
            default: null
        },
        // POS colour preset from Settings → Sale Settings (`pos_color_preset`):
        // 'theme' follows the app theme colour, the rest are fixed palettes.
        colorPreset: {
            type: String,
            default: 'theme'
        }
    },

    setup(props) {
        const toast = useToast()

        // Reactive data
        const loading = ref(false)
        const products = ref([])
        const employeeSelectRef = ref(null)
        // Initialize serverCustomers with default customer and props.customers
        const serverCustomers = ref({
            ...(props.defaultCustomerEnabled ? {
                3: {
                    id: 3,
                    name: 'General Customer',
                    mobile: ''
                }
            } : {}),
            ...props.customers || {}
        })
        const loadingCustomers = ref(false) // Track customer loading state
        const selectedCategory = ref('favorite')
        const productKey = ref('')
        const barcodeKey = ref('')
        const selectedPaymentMethod = ref(1)
        const sendToWhatsapp = ref(false)
        const showCartModal = ref(false)
        const showCustomerModal = ref(false)
        const showCustomPaymentModal = ref(false)
        const showDraftSalesModal = ref(false)
        const showFeedbackModal = ref(false)
        const showConfirmationModal = ref(false)
        const submitting = ref(false)
        const windowWidth = ref(typeof window !== 'undefined' ? window.innerWidth : 1024)

        // --vh is consumed by the legacy mobile stylesheets; layout itself is
        // driven by container queries, not by this value.
        const setVH = () => {
            const vh = window.innerHeight * 0.01
            document.documentElement.style.setProperty('--vh', `${vh}px`)
            windowWidth.value = window.innerWidth
        }
        const customPaymentData = ref({
            payments: [],
            totalPaid: 0,
            balanceDue: 0
        })
        const newCustomer = ref({
            name: '',
            mobile: '',
            email: ''
        })

        // Product type filter
        const selectedProductType = ref(props.defaultProductType)
        const productTypeOptions = ref([{
            value: '',
            label: 'All Types'
        },
        {
            value: 'product',
            label: 'Products'
        },
        {
            value: 'service',
            label: 'Services'
        },
        ])

        // Form data
        const form = useForm({
            id: null,
            date: new Date().toISOString().split('T')[0],
            employee_id: props.saleData.employee_id || '',
            sale_type: 'normal',
            account_id: props.defaultCustomerEnabled ? 3 : null,
            customer_mobile: '',
            other_discount: 0,
            round_off: 0,
            total: 0,
            grand_total: 0,
            paid: 0,
            balance: 0,
            items: {},
            comboOffers: [],
            payment_method: 1,
            custom_payment_data: {},
            rating: 0,
            feedback_type: 'compliment',
            feedback: ''
        })

        // Initialize form with sale data if provided
        const initializeFormWithSaleData = () => {
            if (props.saleData && props.saleData.id) {
                // Show loading error if there was an issue loading the sale
                if (props.saleData.load_error) {
                    toast.error(props.saleData.load_error)
                    return
                }

                // Update form with sale data
                form.id = props.saleData.id
                form.date = props.saleData.date || form.date
                form.employee_id = props.saleData.employee_id || ''
                form.sale_type = props.saleData.sale_type || 'normal'
                form.account_id = props.saleData.account_id || 3
                form.customer_mobile = props.saleData.customer_mobile || ''
                form.account_id = props.saleData.account_id || 3
                form.other_discount = props.saleData.other_discount || 0
                form.round_off = props.saleData.round_off || 0
                form.total = props.saleData.total || 0
                form.grand_total = props.saleData.grand_total || 0

                // Ensure the customer from the sale is included in serverCustomers
                if (props.customers && Object.keys(props.customers).length > 0) {
                    Object.entries(props.customers).forEach(([id, customer]) => {
                        serverCustomers.value[id] = customer
                    })
                }

                // Load sale items (already in correct format from controller)
                if (props.saleData.items && typeof props.saleData.items === 'object') {
                    form.items = {
                        ...props.saleData.items
                    }
                }

                // Handle payment method
                if (props.saleData.payment_method === 'custom' && props.saleData.custom_payment_data) {
                    selectedPaymentMethod.value = 'custom'
                    customPaymentData.value = props.saleData.custom_payment_data
                    form.payment_method = 'custom'
                    form.custom_payment_data = props.saleData.custom_payment_data
                } else {
                    selectedPaymentMethod.value = props.saleData.payment_method || 1
                    form.payment_method = props.saleData.payment_method || 1
                    customPaymentData.value = {
                        payments: [],
                        totalPaid: 0,
                        balanceDue: 0
                    }
                }

                // Handle combo offers (already processed by controller)
                if (props.saleData.comboOffers && Array.isArray(props.saleData.comboOffers)) {
                    form.comboOffers = props.saleData.comboOffers
                }

                // Recalculate totals to ensure consistency
                calculateTotals()

                const statusText = props.saleData.status === 'draft' ? 'draft' : 'sale'
                toast.success(`${statusText.charAt(0).toUpperCase() + statusText.slice(1)} loaded successfully`)
            }
        }

        // Initialize confirmationData after form is created
        const confirmationData = ref({
            customerName: '',
            paymentMethods: ''
        })

        // Computed properties
        const totalQuantity = computed(() => {
            return Object.values(form.items).reduce((sum, item) => sum + item.quantity, 0)
        })

        const discountPercentage = computed(() => {
            return form.total ? Math.round((form.other_discount / form.total) * 100 * 100) / 100 : 0
        })

        // Spells out what the session chip means on hover — mainly for the
        // backdated case, where every sale rung up now is filed under an
        // earlier date because that day was never closed.
        const daySessionTitle = computed(() => {
            if (!props.daySession) return ''
            const opened = `opened ${props.daySession.opened_at}`
            return props.daySession.is_today
                ? `Today's day session (${opened}) — sales are dated ${props.daySession.date}`
                : `The open day session is from ${props.daySession.label} (${opened}) — sales are dated ${props.daySession.date}, not today`
        })

        const cartItemsByEmployee = computed(() => {
            const grouped = {}
            Object.entries(form.items).forEach(([key, item]) => {
                const employeeName = item.employee_name || 'Unknown Employee'
                if (!grouped[employeeName]) {
                    grouped[employeeName] = []
                }
                grouped[employeeName].push({
                    key,
                    ...item
                })
            })
            return grouped
        })

        // Methods
        const selectCategory = (categoryId) => {
            selectedCategory.value = categoryId
            loadProducts()
        }

        // New method to fetch customers from server
        const fetchCustomers = async (query = '') => {
            try {
                const response = await axios.get(`/account/list?query=${encodeURIComponent(query)}&model=customer`, {
                    headers: {
                        'Cache-Control': 'no-cache'
                    }
                })

                if (response.data?.items) {
                    // Start with existing customers to preserve them
                    const customerObj = {
                        ...serverCustomers.value
                    }
                    if (props.defaultCustomerEnabled) {
                        // Ensure default customer is always present
                        customerObj[3] = {
                            id: 3,
                            name: 'General Customer',
                            mobile: '',
                            phone: ''
                        }
                    }

                    // Add props customers (excluding default to avoid duplicates)
                    Object.entries(props.customers || {}).forEach(([id, customer]) => {
                        if (parseInt(id) !== 3) customerObj[id] = customer
                    })

                    // Add/update server customers from API response
                    response.data.items.forEach(customer => {
                        customerObj[customer.id] = customer
                    })

                    serverCustomers.value = customerObj

                    // Update mobile if default customer is selected
                    if (form.account_id === 3 && customerObj[3]) {
                        form.customer_mobile = customerObj[3].mobile || customerObj[3].phone || form.customer_mobile || ''
                    }
                }
            } catch (error) {
                toast.error('Failed to load customer list')
            }
        }

        const loadProducts = async () => {
            loading.value = true
            try {
                const response = await axios.get('/products', {
                    params: {
                        category_id: selectedCategory.value,
                        sale_type: form.sale_type,
                        search: productKey.value,
                        type: selectedProductType.value
                    }
                })

                // Filter and validate products
                const validProducts = (response.data || []).filter(product => {
                    if (!product?.id) {
                        return false
                    }
                    return true
                })

                products.value = validProducts
            } catch (error) {
                toast.error('Failed to load products')
            } finally {
                loading.value = false
            }
        }

        const filterByProductType = () => {
            loadProducts()
        }

        const searchCustomers = debounce((query) => {
            if (query && query.length > 1) {
                loadingCustomers.value = true
                fetchCustomers(query).finally(() => {
                    loadingCustomers.value = false
                })
            }
        }, 300)

        const searchProducts = debounce(() => {
            loadProducts()
        }, 300)

        const getServerErrorMessage = (error, fallbackMessage) => {
            const data = error?.response?.data

            if (data?.errors) {
                const validationErrors = Object.values(data.errors).flat()
                if (validationErrors.length) {
                    return validationErrors.join('\n')
                }
            }

            return data?.message || data?.error || fallbackMessage
        }

        const createTempCartRowKey = () => `tmp-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`

        const buildCartItemKey = (item) => {
            const baseKey = `${item.employee_id}-${item.inventory_id}`

            if (props.saleItemRowMode === 'separate') {
                const suffix = item.id ?? item.cart_row_key ?? createTempCartRowKey()
                return `${baseKey}-${suffix}`
            }

            return baseKey
        }

        const searchByBarcode = debounce(async () => {
            if (!barcodeKey.value) return

            try {
                const response = await axios.get('/products/by-barcode', {
                    params: {
                        barcode: barcodeKey.value
                    }
                })

                if (response.data) {
                    await addProductToCart(response.data)
                    barcodeKey.value = ''
                } else {
                    toast.error(response.data?.message || response.data?.error || 'Product not found')
                }
            } catch (error) {
                toast.error(getServerErrorMessage(error, 'Failed to find product'))
            }
        }, 300)

        const addProductToCart = async (product) => {
            if (!form.employee_id) {
                toast.error('Please select an employee first.')
                // Open the employee dropdown
                await nextTick()
                // Small delay to ensure DOM is ready and ref is set
                setTimeout(() => {
                    if (employeeSelectRef.value) {
                        // Use the focus method which opens dropdown and focuses input
                        if (employeeSelectRef.value.focus) {
                            employeeSelectRef.value.focus()
                        } else if (employeeSelectRef.value.openDropdown) {
                            employeeSelectRef.value.openDropdown()
                        }
                    } else {
                        // Fallback: try to find and click the input directly
                        const employeeInput = document.querySelector('input[placeholder*="employee" i]')
                        if (employeeInput) {
                            employeeInput.focus()
                            employeeInput.click()
                            employeeInput.scrollIntoView({ behavior: 'smooth', block: 'center' })
                        }
                    }
                }, 200)
                return
            }

            // Check for id in multiple possible locations
            const productId = product?.id || product?.product_id || product?.inventory_id;
            if (!productId) {
                console.error('addProductToCart: Invalid product data - missing id:', product);
                toast.error('Invalid product data')
                return
            }

            try {
                const response = await axios.post('/pos/add-item', {
                    inventory_id: productId,
                    employee_id: form.employee_id,
                    sale_type: form.sale_type,
                    unit_id: product?.unit_id || null
                })

                const item = response.data
                if (props.saleItemRowMode === 'separate' && !item.id && !item.cart_row_key) {
                    item.cart_row_key = createTempCartRowKey()
                }
                const key = buildCartItemKey(item)

                if (form.items[key]) {
                    // default quantity taken from the settings
                    form.items[key].quantity = (parseFloat(form.items[key].quantity) || 0) + props.defaultQuantity
                    await updateItemQuantity(key)
                } else {
                    form.items[key] = item
                }
                calculateTotals()
            } catch (error) {
                toast.error(error.response?.data?.message || 'Failed to add product to cart')
            }
        }

        const updateItemQuantity = async (key) => {
            const item = form.items[key]
            if (!item) return

            try {
                calculateTotals() // Update UI immediately

                const response = await axios.post('/pos/update-item', {
                    key,
                    item_id: item.id,
                    quantity: item.quantity,
                    item: item
                })

                if (response.data) {
                    form.items[key] = response.data
                }
            } catch (error) {
                toast.error(error.response?.data?.message || 'Failed to update quantity')
            }
            calculateTotals() // Re-calculate after server update
        }

        const removeCartItem = async (key) => {
            if (confirm('Are you sure you want to remove this item?')) {
                try {
                    await axios.post('/pos/remove-item', {
                        key,
                        item_id: form.items[key].id
                    })

                    delete form.items[key]
                    calculateTotals()
                    toast.success('Item removed from cart')
                } catch (error) {
                    toast.error(error.response?.data?.error || 'Failed to remove item')
                }
            }
        }

        const updateItemField = (payload) => {
            const {
                key,
                field,
                value
            } = payload
            const item = form.items[key]
            if (!item) return

            // Update field with proper type conversion
            if (['quantity', 'unit_price', 'discount', 'tax', 'combo_offer_price'].includes(field)) {
                item[field] = Number(value) || 0
            } else {
                item[field] = value
            }

            // Recalculate item totals using utility function
            form.items[key] = calculateItemTotals(item)

            calculateTotals()

            // Update server for critical fields
            if (['quantity', 'unit_price', 'discount', 'tax', 'combo_offer_price'].includes(field)) {
                updateItemQuantity(key)
            }
        }

        const increaseQuantity = (key) => {
            if (!form.items[key]) return

            form.items[key].quantity = (parseInt(form.items[key].quantity) || 0) + 1
            calculateTotals()
            updateItemQuantity(key)
        }

        const decreaseQuantity = (key) => {
            if (!form.items[key]) return

            const currentQty = parseInt(form.items[key].quantity) || 0
            if (currentQty > 1) {
                form.items[key].quantity = currentQty - 1
                calculateTotals()
                updateItemQuantity(key)
            } else {
                toast.info('Quantity cannot be less than 1. Remove item instead?')
            }
        }

        const selectPaymentMethod = (method) => {
            if (method === 'custom') {
                showCustomPaymentModal.value = true
                return
            }
            selectedPaymentMethod.value = method
            form.payment_method = method
            form.custom_payment_data = {} // Clear custom payment data for other methods
            customPaymentData.value = {
                payments: [],
                totalPaid: 0,
                balanceDue: 0
            } // Clear custom payment data for other methods
        }

        const calculateTotals = () => {
            // Calculate item totals for each item
            Object.keys(form.items).forEach(key => {
                form.items[key] = calculateItemTotals(form.items[key])
            })

            // Calculate cart totals
            const totals = calculateCartTotals(form.items)
            form.gross_amount = totals.gross_amount
            form.item_discount = totals.item_discount
            form.tax_amount = totals.tax_amount
            form.total = totals.total

            // Calculate grand total with discount and round off
            const otherDiscount = Number(form.other_discount) || 0
            const grandTotal = parseFloat(form.total) - otherDiscount
            const roundedTotal = Math.round(grandTotal)

            form.round_off = Math.round((roundedTotal - grandTotal) * 100) / 100
            form.grand_total = roundedTotal
        }

        const updateTotals = (totals) => {
            Object.assign(form, totals)
        }

        const clearCart = () => {
            if (confirm('Are you sure you want to clear the cart?')) {
                form.items = {}
                calculateTotals()
                toast.success('Cart cleared')
            }
        }

        const viewCartItems = () => {
            showCartModal.value = true
        }

        // --- Vue-based Edit Item Modal ---
        const showEditItemModal = ref(false)
        const editItemKey = ref(null)
        const editItemData = ref({})

        // Combo Offer Modal
        const showComboOfferModal = ref(false)
        const showCustomerDetailsModal = ref(false)
        const selectedCustomerId = ref(null)
        const hasCustomerFeedbacks = ref(false)

        // Handler for saving from EditItemModal
        const onEditItemSave = (updatedItem) => {
            if (editItemKey.value && form.items[editItemKey.value]) {
                // Use Object.assign to preserve reactivity
                Object.assign(form.items[editItemKey.value], updatedItem)
                calculateTotals()
                showEditItemModal.value = false
                toast.success('Item updated successfully')
            }
        }
        const editCartItem = (key) => {
            const item = form.items[key]
            if (!item) return
            editItemKey.value = key
            // Deep clone to avoid mutating original until save
            editItemData.value = JSON.parse(JSON.stringify(item))
            showEditItemModal.value = true
        }

        const saveEditedItem = () => {
            if (editItemKey.value && form.items[editItemKey.value]) {
                // Validate numeric fields
                ['quantity', 'unit_price', 'discount', 'tax'].forEach(field => {
                    editItemData.value[field] = Number(editItemData.value[field]) || 0
                })
                // Calculate amounts (inspired by Livewire)
                const item = editItemData.value
                item.gross_amount = item.unit_price * item.quantity
                item.net_amount = item.gross_amount - item.discount
                item.tax_amount = Math.round(item.net_amount * (item.tax / 100) * 100) / 100
                item.total = Math.round((item.net_amount + item.tax_amount) * 100) / 100

                // Save back to cart
                form.items[editItemKey.value] = {
                    ...item
                }
                calculateTotals()
                showEditItemModal.value = false
                toast.success('Item updated successfully')
            }
        }

        const addNewCustomer = () => {
            showCustomerModal.value = true
        }

        const viewCustomerDetails = () => {
            if (!form.account_id || form.account_id === 3) {
                toast.error('Please select a customer first')
                return
            }

            // Show customer details modal
            selectedCustomerId.value = form.account_id
            showCustomerDetailsModal.value = true
        }

        const handleCustomerEdit = (customer) => {
            // Close details modal and open edit modal
            showCustomerDetailsModal.value = false
            newCustomer.value = customer
            showCustomerModal.value = true
        }

        const handleCustomerSaved = (customer) => {
            // Add the new customer to the existing list
            if (customer && customer.id) {
                serverCustomers.value[customer.id] = customer
            }

            // Set the form to use the new customer
            form.account_id = parseInt(customer.id)
            form.customer_mobile = customer.mobile || '';
        }

        const handleCustomerChange = (selectedValue) => {
            if (selectedValue) {
                const customer = findCustomerById(selectedValue)
                if (customer) {
                    const normalized = normalizeCustomerData(customer)
                    form.customer_mobile = normalized.mobile
                }
                // Check if customer has feedbacks
                checkCustomerFeedbacks(selectedValue)
            } else {
                form.customer_mobile = ''
                hasCustomerFeedbacks.value = false
            }
        }

        const checkCustomerFeedbacks = async (customerId) => {
            if (!customerId || customerId === 3) {
                hasCustomerFeedbacks.value = false
                return
            }

            try {
                const response = await axios.get(`/account/customer/${customerId}/details`)
                if (response.data?.feedbacks && Array.isArray(response.data.feedbacks) && response.data.feedbacks.length > 0) {
                    hasCustomerFeedbacks.value = true
                } else {
                    hasCustomerFeedbacks.value = false
                }
            } catch (error) {
                // Silently fail - don't show error for this check
                hasCustomerFeedbacks.value = false
            }
        }

        const handleCustomerSelected = (customer) => {
            // Select the existing customer
            form.account_id = customer.id
            // Use customer mobile if available, otherwise preserve existing (from sale data)
            form.customer_mobile = customer.mobile || customer.phone || form.customer_mobile || ''

            toast.success('Customer selected successfully')
        }

        const handleCustomPaymentSave = (paymentData) => {
            // Save custom payment data
            customPaymentData.value = paymentData
            selectedPaymentMethod.value = 'custom'
            form.payment_method = 'custom'
            form.custom_payment_data = paymentData

            // Make sure the modal closes after saving
            showCustomPaymentModal.value = false

            const paymentMethods = paymentData.payments.map(p => p.name).join(', ')
            toast.success(`Custom payment methods configured: ${paymentMethods}`)
        }

        const closeCustomPaymentModal = () => {
            showCustomPaymentModal.value = false
            // If no custom payment was saved and custom was selected, reset to cash
            if (selectedPaymentMethod.value === 'custom' && (!customPaymentData.value.payments || customPaymentData.value.payments.length === 0)) {
                selectedPaymentMethod.value = 1
                form.payment_method = 1
            }
        }

        const viewDraftSales = () => {
            showDraftSalesModal.value = true
        }

        const handleDraftLoaded = (draft) => {
            // Navigate to the POS page with the draft ID
            window.location.href = `/sale/pos/${draft.id}`
        }

        const closeDraftSalesModal = () => {
            showDraftSalesModal.value = false
        }

        const openFeedback = () => {
            if (!props.canFeedback) {
                toast.error('You do not have permission to access feedback')
                return
            }
            showFeedbackModal.value = true
        }

        const closeFeedbackModal = () => {
            showFeedbackModal.value = false
        }

        const handleFeedbackSubmitted = (feedbackData) => {
            // Add feedback data to the form so it's submitted with the sale
            form.rating = feedbackData.rating;
            form.feedback_type = feedbackData.feedback_type;
            form.feedback = feedbackData.feedback;

            toast.success('Thank you for your feedback!')
        }

        const saveDraft = () => {
            processSale('draft')
        }

        const submitSale = () => {
            if (Object.keys(form.items).length === 0) {
                toast.error('Please add at least one item to cart')
                return
            }

            // Prepare confirmation data
            updateConfirmationData()

            // Show the confirmation modal instead of directly submitting
            showConfirmationModal.value = true
        }

        // Unified function to handle both draft save and sale submission
        const processSale = async (status = 'completed') => {
            submitting.value = true

            try {
                // For draft, we don't need all validations
                // Ensure all items have required fields for completed sales
                const validItems = {};
                let hasInvalidItems = false;

                Object.entries(form.items).forEach(([key, item]) => {
                    // Validate each item
                    if (!item.inventory_id || !item.product_id || !item.employee_id) {
                        hasInvalidItems = true;
                        return;
                    }

                    // Convert numbers to ensure proper format
                    validItems[key] = {
                        ...item,
                        unit_price: Number(item.unit_price) || 0,
                        quantity: Number(item.quantity) || 1,
                        discount: Number(item.discount) || 0,
                        tax: Number(item.tax) || 0,
                    };
                });

                if (hasInvalidItems) {
                    throw new Error('Some items have missing required fields. Please check the cart items.');
                }

                if (Object.keys(form.items).length === 0) {
                    throw new Error('Cart is empty. Please add items before submitting.');
                }

                // Prepare form data for submission
                const formData = {
                    id: form.id,
                    date: form.date,
                    employee_id: form.employee_id,
                    sale_type: form.sale_type,
                    account_id: form.account_id || null,
                    customer_mobile: form.customer_mobile,
                    other_discount: Number(form.other_discount) || 0,
                    round_off: Number(form.round_off) || 0,

                    gross_amount: Number(form.gross_amount) || 0,
                    item_discount: Number(form.item_discount) || 0,
                    tax_amount: Number(form.tax_amount) || 0,

                    total: Number(form.total) || 0,
                    grand_total: Number(form.grand_total) || 0,
                    items: validItems,
                    comboOffers: form.comboOffers || [],
                    payment_method: form.payment_method,
                    custom_payment_data: form.payment_method === 'custom' ?
                        (customPaymentData.value && customPaymentData.value.payments && customPaymentData.value.payments.length > 0 ?
                            customPaymentData.value : {
                                payments: []
                            }) : {
                            payments: []
                        },
                    send_to_whatsapp: sendToWhatsapp.value || false,
                    rating: Number(form.rating) || 0,
                    feedback_type: form.feedback_type || null,
                    feedback: form.feedback || null,
                    status: status
                };

                // Send data to server
                const response = await axios.post('/pos/submit', formData, {
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    timeout: 30000
                });

                submitting.value = false;

                if (status === 'draft') {
                    toast.success('Sale saved as draft');
                } else {
                    showConfirmationModal.value = false;
                    toast.success('Sale submitted successfully');
                    if (response.data && response.data.sale_id) {
                        const printUrl = `/print/sale/invoice/${response.data.sale_id}`;
                        const printWindow = window.open(printUrl, '_blank');
                        if (!printWindow) {
                            toast.error('Popup blocked. Please allow popups for this site.');
                        }
                    }
                }
                // Reset form or redirect for completed sales (with delay to allow print window to open)
                setTimeout(() => {
                    if (response.data && response.data.redirect) {
                        window.location.href = response.data.redirect;
                    } else {
                        // Reset form for new sale
                        form.reset();
                        form.items = {};
                        calculateTotals();
                        customPaymentData.value = {
                            payments: [],
                            totalPaid: 0,
                            balanceDue: 0
                        };
                        selectedPaymentMethod.value = 1;
                    }
                }, 1000); // 1 second delay to ensure print window opens first
            } catch (error) {
                // Get detailed error message from response if available
                let errorMessage = status === 'draft' ? 'Failed to save draft' : 'Failed to submit sale';

                if (error.response) {
                    errorMessage = error.response.data.message || error.response.data.error || errorMessage;

                    // Handle validation errors
                    if (error.response.data.errors) {
                        const validationErrors = error.response.data.errors;
                        const firstError = Object.values(validationErrors)[0];
                        if (firstError && firstError[0]) {
                            errorMessage = firstError[0];
                        }
                    }
                } else if (error.message) {
                    errorMessage = error.message;
                }

                submitting.value = false;
                toast.error(errorMessage);
            }
        }

        // This function gets called when user confirms in the modal
        const processSubmitSale = () => {
            processSale('completed')
        }

        // Helper function to find customer by ID
        const findCustomerById = (id) => {
            if (!id) return null

            // Try both string and number keys
            const stringId = String(id)
            const numberId = Number(id)

            // Check server customers first
            const serverCustomer = serverCustomers.value[stringId] || serverCustomers.value[numberId]
            if (serverCustomer) return serverCustomer

            // Fallback to props customers
            const propsCustomer = props.customers?.[stringId] || props.customers?.[numberId]
            return propsCustomer || null
        }

        // Helper function to normalize customer data
        const normalizeCustomerData = (customer) => {
            if (!customer) return {
                name: 'Walk-in Customer',
                mobile: ''
            }

            if (typeof customer === 'string') {
                const parts = customer.includes(' - ') ? customer.split(' - ') : [customer, '']
                return {
                    name: parts[0].trim(),
                    mobile: parts[1]?.trim() || ''
                }
            }

            return {
                name: customer.name || 'Unknown Customer',
                mobile: customer.mobile || customer.phone || ''
            }
        }

        // Prepare data for the confirmation modal
        const updateConfirmationData = () => {
            const customer = findCustomerById(form.account_id)
            const customerDetails = normalizeCustomerData(customer)

            // Create a plain object from the form data for the confirmation modal
            const formData = {
                employee_id: form.employee_id,
                sale_type: form.sale_type,
                account_id: form.account_id,
                customer_mobile: form.customer_mobile,
                other_discount: form.other_discount,
                round_off: form.round_off,
                total: form.total,
                grand_total: form.grand_total,
                items: form.items,
                payment_method: form.payment_method
            };

            confirmationData.value = {
                ...formData,
                customerName: customerDetails,
                custom_payment_data: customPaymentData.value
            }
        }

        const closeConfirmationModal = () => {
            showConfirmationModal.value = false
        }

        // Helper function to clear form for new sale
        const startNewSale = () => {
            // Reset form to default values
            form.reset()
            form.items = {}
            form.comboOffers = []
            form.date = new Date().toISOString().split('T')[0]
            form.employee_id = ''
            form.sale_type = 'normal'
            form.account_id = 3
            form.customer_mobile = ''
            form.other_discount = 0
            form.round_off = 0
            form.total = 0
            form.grand_total = 0
            form.payment_method = 1

            // Reset payment method
            selectedPaymentMethod.value = 1
            customPaymentData.value = {
                payments: [],
                totalPaid: 0,
                balanceDue: 0
            }

            calculateTotals()
            toast.success('Ready for new sale')
        }

        // Combo Offer Methods
        const manageComboOffer = () => {
            if (Object.keys(form.items).length === 0) {
                toast.error('Please add items to cart before managing combo offers')
                return
            }
            showComboOfferModal.value = true
        }

        const closeComboOfferModal = () => {
            showComboOfferModal.value = false
        }

        const openComboOfferSettings = () => {
            // This would typically open the combo offer management page
            // For now, we'll show a toast and close the modal
            toast.info('Combo offer settings would open here')
            closeComboOfferModal()
        }

        const handleComboOfferSave = (comboData) => {
            // Get all item keys that are in combo offers
            const comboOfferItemKeys = new Set(Object.keys(comboData.comboOfferItems))
            // Reset combo offer prices for items no longer in any combo offer
            // This updates items in place to maintain reactivity
            resetComboOfferPricing(form.items, comboOfferItemKeys)

            // Apply combo offer pricing to cart items
            // This updates items in place to maintain reactivity
            applyComboOfferPricing(form.items, comboData.comboOfferItems)

            // Store combo offers data for submission
            form.comboOffers = comboData.selectedComboOffers

            // Recalculate totals - this will use the updated combo offer prices
            calculateTotals()
            toast.success('Combo offers applied successfully')
        }

        const formatNumber = (value) => {
            const num = parseFloat(value) || 0
            return num.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            })
        }

        const convertDiscountToPercentage = () => {
            // Get the current value in the discount field
            const currentValue = parseFloat(form.other_discount) || 0

            if (currentValue > 0) {
                // Validate that percentage doesn't exceed 100
                if (currentValue > 100) {
                    toast.error('Discount percentage cannot exceed 100%')
                    return
                }

                // Calculate the total before discount
                const totalBeforeDiscount = parseFloat(form.total) || 0

                if (totalBeforeDiscount > 0) {
                    // Convert percentage to actual discount amount
                    const discountAmount = (currentValue / 100) * totalBeforeDiscount
                    form.other_discount = parseFloat(discountAmount.toFixed(2))

                    // Recalculate totals
                    calculateTotals()

                    toast.success(`Converted ${currentValue}% to ${discountAmount.toFixed(2)} discount`)
                } else {
                    toast.error('No items in cart to calculate discount percentage')
                }
            } else {
                toast.error('Please enter a percentage value first')
            }
        }


        // Debounce function to limit the rate of function calls
        function debounce(func, wait) {
            let timeout
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout)
                    func(...args)
                }
                clearTimeout(timeout)
                timeout = setTimeout(later, wait)
            }
        }
        // Watchers
        watch(() => form.other_discount, () => {
            calculateTotals()
        })

        // Watch for customer selection to auto-populate mobile
        watch(() => form.account_id, (newCustomerId) => {
            if (newCustomerId) {
                const customer = findCustomerById(newCustomerId)
                if (customer) {
                    const normalized = normalizeCustomerData(customer)
                    form.customer_mobile = normalized.mobile
                }
                // Check if customer has feedbacks
                checkCustomerFeedbacks(newCustomerId)
            } else {
                form.customer_mobile = ''
                hasCustomerFeedbacks.value = false
            }
        }, {
            immediate: true
        })

        // Watch for payment method changes to update confirmation modal
        watch(selectedPaymentMethod, () => {
            updateConfirmationData()
        })

        // Watch for custom payment data changes to update confirmation modal
        watch(customPaymentData, () => {
            updateConfirmationData()
        }, {
            deep: true
        })

        // Lifecycle
        onMounted(() => {
            // Initialize form with sale data if provided from controller FIRST
            initializeFormWithSaleData()

            loadProducts()
            fetchCustomers()

            setVH()
            window.addEventListener('resize', setVH)
            window.addEventListener('orientationchange', setVH)
        })

        onUnmounted(() => {
            window.removeEventListener('resize', setVH)
            window.removeEventListener('orientationchange', setVH)
        })

        const formattedCustomers = computed(() => {
            const customersSource = serverCustomers.value || {}

            return Object.entries(customersSource).map(([id, customer]) => {
                const customerData = normalizeCustomerData(customer)
                return {
                    value: parseInt(id),
                    label: `${customerData.name} - ${customerData.mobile}`,
                    name: customerData.name,
                    mobile: customerData.mobile
                }
            })
        })
        return {
            // Reactive data
            loading,
            products,
            serverCustomers,
            loadingCustomers,
            selectedCategory,
            productKey,
            barcodeKey,
            selectedPaymentMethod,
            sendToWhatsapp,
            showCartModal,
            showCustomerModal,
            showCustomPaymentModal,
            showDraftSalesModal,
            showFeedbackModal,
            showConfirmationModal,
            submitting,
            confirmationData,
            customPaymentData,
            form,
            newCustomer,
            windowWidth,
            showEditItemModal,
            editItemKey,
            editItemData,
            selectedProductType,
            productTypeOptions,
            showComboOfferModal,
            showCustomerDetailsModal,
            selectedCustomerId,
            hasCustomerFeedbacks,
            employeeSelectRef,

            // Computed
            totalQuantity,
            discountPercentage,
            daySessionTitle,
            cartItemsByEmployee,
            formattedCustomers,

            // Methods
            selectCategory,
            fetchCustomers,
            loadProducts,
            searchCustomers,
            searchProducts,
            searchByBarcode,
            addProductToCart,
            updateItemQuantity,
            removeCartItem,
            updateItemField,
            increaseQuantity,
            decreaseQuantity,
            selectPaymentMethod,
            calculateTotals,
            clearCart,
            viewCartItems,
            editCartItem,
            saveEditedItem,
            addNewCustomer,
            viewCustomerDetails,
            handleCustomerEdit,
            handleCustomerChange,
            handleCustomerSaved,
            handleCustomerSelected,
            checkCustomerFeedbacks,
            handleCustomPaymentSave,
            closeCustomPaymentModal,
            viewDraftSales,
            handleDraftLoaded,
            closeDraftSalesModal,
            openFeedback,
            closeFeedbackModal,
            handleFeedbackSubmitted,
            // Exposed methods and data
            updateConfirmationData,
            closeConfirmationModal,
            processSubmitSale,
            saveDraft,
            submitSale,
            onEditItemSave,
            startNewSale,
            filterByProductType,
            manageComboOffer,
            closeComboOfferModal,
            openComboOfferSettings,
            handleComboOfferSave,
            convertDiscountToPercentage,
            formatNumber
        }
    }
}
</script>

<style scoped>
@import '../../../css/pos-common.css';
@import '../../../css/pos.css';
@import '../../../css/mobile-responsive.css';
@import '../../../css/pos-enhanced-responsive.css';

.payment-modal {
    position: fixed;
    z-index: 50;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
}

.modal-overlay {
    position: fixed;
    z-index: 40;
    inset: 0;
    background-color: rgba(0, 0, 0, 0.5);
}

</style>
