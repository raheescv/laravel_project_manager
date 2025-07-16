<template>
    <div
        class="min-h-screen max-h-screen bg-gradient-to-br from-gray-50 via-blue-50 to-indigo-50 relative overflow-hidden">
        <!-- Optimized floating background elements for mobile -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div
                class="absolute -top-20 sm:-top-40 -right-20 sm:-right-40 w-48 h-48 sm:w-96 sm:h-96 bg-gradient-to-br from-blue-100/30 to-indigo-100/30 rounded-full blur-2xl sm:blur-3xl opacity-40 sm:opacity-60">
            </div>
            <div
                class="absolute -bottom-20 sm:-bottom-40 -left-20 sm:-left-40 w-48 h-48 sm:w-96 sm:h-96 bg-gradient-to-tr from-slate-100/30 to-gray-100/30 rounded-full blur-2xl sm:blur-3xl opacity-40 sm:opacity-60">
            </div>
            <div
                class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-32 h-32 sm:w-64 sm:h-64 bg-gradient-to-r from-blue-50/40 to-indigo-50/40 rounded-full blur-2xl sm:blur-3xl opacity-30 sm:opacity-50">
            </div>
        </div>

        <div class="container-fluid h-screen relative z-10 flex flex-col">
            <form class="flex-1 flex flex-col min-h-0">
                <!-- Mobile-first responsive layout -->
                <div
                    class="flex flex-col lg:flex-row flex-1 gap-2 sm:gap-3 lg:gap-4 xl:gap-6 p-2 sm:p-3 lg:p-4 xl:p-6 min-h-0">
                    <!-- Categories Sidebar Component - Mobile: Hidden by default, Desktop: Always visible -->
                    <div class="order-1 lg:order-1 w-full lg:w-auto lg:h-full">
                        <CategoriesSidebar :categories="categories" :selected-category="selectedCategory"
                            @category-selected="selectCategory" />
                    </div>

                    <!-- Main Content Area - Products and Cart -->
                    <div
                        class="flex-1 flex flex-col lg:flex-row gap-2 sm:gap-3 lg:gap-4 order-2 lg:order-2 min-h-0 lg:h-full">
                        <!-- Products Section -->
                        <div class="flex-1 lg:flex-[0.6] xl:flex-[0.55] flex flex-col order-1 lg:order-1 min-h-0">
                            <!-- Compact customer - employee-product search area -->
                            <div
                                class="bg-gradient-to-br from-white/95 via-blue-50/30 to-indigo-50/20 backdrop-blur-xl rounded-lg shadow-lg border border-white/60 mb-2 p-3 relative overflow-hidden">
                                <!-- Subtle background elements -->
                                <div class="absolute inset-0 overflow-hidden">
                                    <div class="absolute top-0 right-0 w-16 h-16 bg-gradient-to-br from-blue-200/20 to-indigo-300/20 rounded-full blur-lg"></div>
                                    <div class="absolute bottom-0 left-0 w-12 h-12 bg-gradient-to-tr from-emerald-200/20 to-teal-300/20 rounded-full blur-md"></div>
                                </div>

                                <!-- Customer and Mobile -->
                                <div class="relative grid grid-cols-1 lg:grid-cols-2 gap-2 mb-3">
                                    <div class="space-y-1">
                                        <label class="text-xs font-bold text-slate-700 flex items-center justify-between">
                                            <span>Customer</span>
                                            <button type="button" @click="addNewCustomer"
                                                class="bg-gradient-to-r from-blue-500 to-indigo-600 text-white px-2 py-1 rounded-md hover:from-blue-600 hover:to-indigo-700 transition-all duration-200 shadow-sm hover:shadow-md transform hover:-translate-y-0.5 font-semibold text-xs flex items-center">
                                                <i class="fa fa-plus mr-1 text-xs"></i>
                                                Add
                                            </button>
                                        </label>
                                        <SearchableSelect v-model="form.account_id" :options="formattedCustomers"
                                            placeholder="Select Customer"
                                            filter-placeholder="Search by name or mobile..." :visibleItems="8"
                                            @search="searchCustomers"
                                            @change="handleCustomerChange"
                                            input-class="w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500/20 transition-all duration-200 bg-white/90 backdrop-blur-sm hover:shadow-md text-xs py-2" />
                                    </div>
                                    <div class="space-y-1">
                                        <label class="text-xs font-bold text-slate-700 flex items-center">
                                            Mobile
                                        </label>
                                        <input v-model="form.customer_mobile" type="text"
                                            class="w-full rounded-lg border-slate-200 shadow-sm focus:border-emerald-500 focus:ring-emerald-500/20 transition-all duration-200 bg-white/90 backdrop-blur-sm hover:shadow-md text-xs py-2"
                                            placeholder="Mobile">
                                    </div>
                                </div>

                                <!-- Employee and Sale Type -->
                                <div class="relative grid grid-cols-1 lg:grid-cols-2 gap-2 mb-3">
                                    <div class="space-y-1">
                                        <label class="text-xs font-bold text-slate-700 flex items-center">
                                            Employee
                                        </label>
                                        <SearchableSelect v-model="form.employee_id" :options="employees"
                                            placeholder="Select employee..." filter-placeholder="Search employees..."
                                            :visibleItems="8"
                                            input-class="w-full rounded-lg border-slate-200 shadow-sm focus:border-purple-500 focus:ring-purple-500/20 transition-all duration-200 bg-white/90 backdrop-blur-sm hover:shadow-md text-xs py-2" />
                                    </div>
                                    <div class="space-y-1">
                                        <label class="text-xs font-bold text-slate-700 flex items-center">
                                            Sale Type
                                        </label>
                                        <SearchableSelect v-model="form.sale_type" :options="priceTypes"
                                            placeholder="Select type..." filter-placeholder="Search sale types..."
                                            :visibleItems="8"
                                            input-class="w-full rounded-lg border-slate-200 shadow-sm focus:border-orange-500 focus:ring-orange-500/20 transition-all duration-200 bg-white/90 backdrop-blur-sm hover:shadow-md text-xs py-2"
                                            @change="loadProducts" />
                                    </div>
                                </div>

                                <!-- Search Section -->
                                <div class="relative">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-2">
                                        <div class="space-y-1">
                                            <select v-model="selectedProductType" @change="filterByProductType"
                                                class="w-full rounded-lg border-slate-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500/20 transition-all duration-200 bg-white/90 backdrop-blur-sm hover:shadow-md text-xs py-2">
                                                <option v-for="option in productTypeOptions" :key="option.value" :value="option.value">
                                                    {{ option.label }}
                                                </option>
                                            </select>
                                        </div>
                                        <div class="space-y-1">
                                            <div class="relative group">
                                                <input v-model="barcodeKey" @input="searchByBarcode" type="text"
                                                    class="w-full pl-8 pr-3 py-2 rounded-lg border-slate-200 shadow-sm focus:border-purple-500 focus:ring-purple-500/20 transition-all duration-200 bg-white/90 backdrop-blur-sm group-hover:shadow-md text-xs"
                                                    placeholder="Scan barcode" autocomplete="off">
                                                <div
                                                    class="absolute left-3 top-1/2 transform -translate-y-1/2 text-purple-500 group-focus-within:text-purple-600 transition-colors">
                                                    <i class="fa fa-barcode text-xs"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="space-y-1">
                                            <div class="relative group">
                                                <input v-model="productKey" @input="searchProducts" type="text"
                                                    class="w-full pl-8 pr-3 py-2 rounded-lg border-slate-200 shadow-sm focus:border-orange-500 focus:ring-orange-500/20 transition-all duration-200 bg-white/90 backdrop-blur-sm group-hover:shadow-md text-xs"
                                                    placeholder="Search products" autocomplete="off">
                                                <div
                                                    class="absolute left-3 top-1/2 transform -translate-y-1/2 text-orange-500 group-focus-within:text-orange-600 transition-colors">
                                                    <i class="fa fa-search text-xs"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <button type="button" @click="viewDraftSales"
                                            class="bg-gradient-to-r from-slate-600 via-slate-700 to-slate-800 text-white py-2 px-3 rounded-lg hover:from-slate-700 hover:via-slate-800 hover:to-slate-900 transition-all duration-200 shadow-sm hover:shadow-md transform hover:-translate-y-0.5 font-semibold text-xs flex items-center justify-center">
                                            <i class="fa fa-file-alt mr-1 text-xs"></i>
                                            <span class="hidden sm:inline">Drafts</span>
                                            <span class="sm:hidden">Drafts</span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Products Grid -->
                            <div
                                class="bg-white rounded-lg shadow-md border border-slate-200 flex-1 p-2 sm:p-3 min-h-0">
                                <div class="h-full overflow-y-auto products-container custom-scrollbar"
                                    :style="{ 'height': windowWidth >= 1024 ? 'calc(100vh - 220px)' : 'calc(60vh - 100px)', 'min-height': '300px' }">
                                    <div v-if="loading" class="flex items-center justify-center h-full">
                                        <div class="text-center">
                                            <div
                                                class="animate-spin rounded-full h-12 w-12 sm:h-16 sm:w-16 border-b-2 border-blue-500 mx-auto mb-4">
                                            </div>
                                            <p class="text-slate-600 text-sm sm:text-base">Loading products...</p>
                                        </div>
                                    </div>
                                    <products-grid v-else :products="products" :lowStockThreshold="10"
                                        @product-selected="addProductToCart"></products-grid>
                                </div>
                            </div>
                        </div>

                        <!-- Cart Section - Mobile: bottom section, Desktop: right sidebar -->
                        <div
                            class="w-full lg:w-96 xl:w-[30rem] lg:flex-[0.4] xl:flex-[0.45] flex flex-col order-3 lg:order-3 h-full">
                            <div
                                class="bg-white rounded-xl shadow-lg border border-slate-200 h-full flex flex-col min-h-0">
                                <!-- Cart Items Component -->
                                <CartItems :items="form.items" :total-quantity="totalQuantity" :cart-height="cartHeight"
                                    :max-height="windowWidth >= 1024 ? '100%' : '400px'"
                                    @view-cart-items="viewCartItems" @clear-cart="clearCart"
                                    @update-item-quantity="updateItemQuantity" @edit-cart-item="editCartItem"
                                    @remove-cart-item="removeCartItem" @increase-quantity="increaseQuantity"
                                    @decrease-quantity="decreaseQuantity" @manage-combo-offer="manageComboOffer"
                                    item-class="min-h-[64px] py-4" />



                                <!-- Discount Only (full width) -->
                                <div class="p-2 sm:p-3 border-t border-slate-200">
                                    <div class="mb-2 sm:mb-3">
                                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                                            <i class="fa fa-tag mr-1 text-blue-500"></i>
                                            Discount
                                        </label>
                                        <input v-model.number="form.other_discount" @input="calculateTotals"
                                            type="number" step="0.01"
                                            class="w-full text-xs py-1.5 sm:py-2 rounded border-slate-300 focus:border-blue-500 focus:ring-blue-500"
                                            placeholder="0">
                                    </div>

                                    <!-- Enhanced Order Total - Mobile optimized -->
                                    <div
                                        class="bg-gradient-to-br from-slate-50 via-blue-50/30 to-purple-50/20 rounded-xl p-2 sm:p-3 mb-2 sm:mb-3 border border-slate-200/60 shadow-sm">
                                        <div class="space-y-1.5 sm:space-y-2 text-xs sm:text-sm">
                                            <div class="flex justify-between items-center">
                                                <span class="text-slate-600 font-medium flex items-center">
                                                    <i class="fa fa-calculator text-blue-500 mr-1 sm:mr-2 text-xs"></i>
                                                    <span class="hidden sm:inline">Sub Total</span>
                                                    <span class="sm:hidden">Subtotal</span>
                                                </span>
                                                <span
                                                    class="font-bold text-slate-800 bg-white px-1.5 sm:px-2 py-0.5 sm:py-1 rounded-lg shadow-sm text-xs sm:text-sm">{{
                                                        form.total }}</span>
                                            </div>
                                            <div class="flex justify-between items-center text-red-600">
                                                <span class="font-medium flex items-center">
                                                    <i class="fa fa-tag text-red-500 mr-1 sm:mr-2 text-xs"></i>
                                                    <span class="hidden sm:inline">
                                                        Discount
                                                        ( {{ discountPercentage }}%)
                                                    </span>
                                                    <span class="sm:hidden">Disc ({{ discountPercentage }}%)</span>
                                                </span>
                                                <span
                                                    class="font-bold bg-red-50 px-1.5 sm:px-2 py-0.5 sm:py-1 rounded-lg shadow-sm text-xs sm:text-sm">
                                                    -{{ form.other_discount }}
                                                </span>
                                            </div>
                                            <div class="flex justify-between items-center text-blue-600">
                                                <span class="font-medium flex items-center">
                                                    <i class="fa fa-adjust text-blue-500 mr-1 sm:mr-2 text-xs"></i>
                                                    <span class="hidden sm:inline">Round Off</span>
                                                    <span class="sm:hidden">Round</span>
                                                </span>
                                                <span
                                                    class="font-bold bg-blue-50 px-1.5 sm:px-2 py-0.5 sm:py-1 rounded-lg shadow-sm text-xs sm:text-sm">₹{{
                                                        Number(form.round_off).toFixed(2) }}</span>
                                            </div>
                                            <div
                                                class="border-t border-slate-300/60 pt-1.5 sm:pt-2 flex justify-between items-center">
                                                <span
                                                    class="font-bold text-base sm:text-lg text-slate-800 flex items-center">
                                                    <i class="fa fa-receipt text-emerald-500 mr-1 sm:mr-2 text-sm"></i>
                                                    Total
                                                </span>
                                                <div
                                                    class="bg-gradient-to-r from-emerald-500 to-green-500 text-white px-2 sm:px-4 py-1.5 sm:py-2 rounded-xl shadow-lg">
                                                    <span class="text-base sm:text-lg font-black">
                                                        {{ form.grand_total }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Action Buttons - Mobile optimized -->
                                    <div class="space-y-2">
                                        <button type="button" @click="openFeedback"
                                            class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 text-white py-2.5 sm:py-2 px-3 rounded-xl text-xs sm:text-sm font-bold hover:from-blue-700 hover:to-indigo-700 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1 relative overflow-hidden group min-h-[44px]">
                                            <div
                                                class="absolute inset-0 bg-gradient-to-r from-white/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                            </div>
                                            <i class="fa fa-comment mr-1 sm:mr-2 relative z-10 text-xs sm:text-sm"></i>
                                            <span class="relative z-10">Feedback</span>
                                        </button>
                                        <div class="grid grid-cols-2 gap-2">
                                            <button type="button" @click="saveDraft"
                                                class="bg-gradient-to-r from-slate-600 via-slate-700 to-gray-700 text-white py-2.5 sm:py-2 px-2 sm:px-3 rounded-xl text-xs sm:text-sm font-bold hover:from-slate-700 hover:via-slate-800 hover:to-gray-800 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1 relative overflow-hidden group min-h-[44px]">
                                                <div
                                                    class="absolute inset-0 bg-gradient-to-r from-white/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                                </div>
                                                <i class="fa fa-save mr-1 relative z-10 text-xs sm:text-sm"></i>
                                                <span class="relative z-10">Draft</span>
                                            </button>
                                            <button type="button" @click="submitSale"
                                                :disabled="Object.keys(form.items).length === 0" :class="[
                                                    'py-2.5 sm:py-2 px-2 sm:px-3 rounded-xl text-xs sm:text-sm font-bold transition-all duration-300 shadow-lg relative overflow-hidden group min-h-[44px]',
                                                    Object.keys(form.items).length === 0
                                                        ? 'bg-gray-400 text-gray-600 cursor-not-allowed'
                                                        : 'bg-gradient-to-r from-green-500 via-green-600 to-emerald-600 text-white hover:from-green-600 hover:via-green-700 hover:to-emerald-700 hover:shadow-xl transform hover:-translate-y-1 btn-pulse'
                                                ]">
                                                <div v-if="Object.keys(form.items).length > 0"
                                                    class="absolute inset-0 bg-gradient-to-r from-white/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                                </div>
                                                <i class="fa fa-check-circle mr-1 relative z-10 text-xs sm:text-sm"></i>
                                                <span class="relative z-10">Submit</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- End Cart Section -->
                    </div>
                    <!-- End Main Content Area - Products and Cart -->
                </div>
                <!-- End Mobile-first responsive layout -->
            </form>
        </div>

        <!-- Cart Items Modal -->
        <CartItemsModal :show="showCartModal" :cart-items-by-employee="cartItemsByEmployee"
            :total-items="Object.keys(form.items).length" :total-quantity="totalQuantity" @close="showCartModal = false"
            @update-item-quantity="updateItemQuantity" @remove-cart-item="removeCartItem"
            @update-item-field="updateItemField" />

        <!-- Customer Modal -->
        <CustomerModal :show="showCustomerModal" :initial-customer="newCustomer" :customer-types="customerTypes"
            :countries="countries" @close="showCustomerModal = false" @customer-saved="handleCustomerSaved"
            @customer-selected="handleCustomerSelected" />

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
        <EditItemModal :show="showEditItemModal" :item="editItemData" :employees="employees" @close="showEditItemModal = false"
            @save="onEditItemSave" />

                        <!-- Combo Offer Modal -->
        <ComboOfferModal
            :show="showComboOfferModal"
            :cart-items="form.items"
            :initial-combo-offers="form.comboOffers"
            @close="closeComboOfferModal"
            @save="handleComboOfferSave"
            @openSettings="openComboOfferSettings" />
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
import { useForm } from '@inertiajs/vue3'
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { useToast } from 'vue-toastification'

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
        ComboOfferModal
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
        }
    },

    setup(props) {
        const toast = useToast()

        // Reactive data
        const loading = ref(false)
        const products = ref([])
        // Initialize serverCustomers with default customer and props.customers
        const serverCustomers = ref({
            3: {
                id: 3,
                name: 'General Customer',
                mobile: ''
            },
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
        const selectedProductType = ref('service')
        const productTypeOptions = ref([
            { value: '', label: 'All Types' },
            { value: 'product', label: 'Products' },
            { value: 'service', label: 'Services' },
        ])

        // Form data
        const form = useForm({
            id: null,
            date: new Date().toISOString().split('T')[0],
            employee_id: '',
            sale_type: 'normal',
            account_id: 3,
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
                    form.items = { ...props.saleData.items }
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
                    customPaymentData.value = { payments: [], totalPaid: 0, balanceDue: 0 }
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

        const cartItemsByEmployee = computed(() => {
            const grouped = {}
            Object.entries(form.items).forEach(([key, item]) => {
                const employeeName = item.employee_name || 'Unknown Employee'
                if (!grouped[employeeName]) {
                    grouped[employeeName] = []
                }
                grouped[employeeName].push({ key, ...item })
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
                    headers: { 'Cache-Control': 'no-cache' }
                })

                if (response.data?.items) {
                    // Start with existing customers to preserve them
                    const customerObj = { ...serverCustomers.value }

                    // Ensure default customer is always present
                    customerObj[3] = { id: 3, name: 'General Customer', mobile: '', phone: '' }

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
                        form.customer_mobile = customerObj[3].mobile || customerObj[3].phone || ''
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

        const searchByBarcode = debounce(async () => {
            if (!barcodeKey.value) return

            try {
                const response = await axios.get('/products/by-barcode', {
                    params: { barcode: barcodeKey.value }
                })

                if (response.data) {
                    await addProductToCart(response.data)
                    barcodeKey.value = ''
                } else {
                    toast.error('Product not found')
                }
            } catch (error) {
                toast.error('Failed to find product')
            }
        }, 300)

        const addProductToCart = async (product) => {
            if (!form.employee_id) {
                toast.error('Please select an employee first')
                return
            }

            if (!product?.id) {
                toast.error('Invalid product data')
                return
            }

            try {
                const response = await axios.post('/pos/add-item', {
                    inventory_id: product.id,
                    employee_id: form.employee_id,
                    sale_type: form.sale_type
                })

                const item = response.data
                const key = `${item.employee_id}-${item.inventory_id}`

                if (form.items[key]) {
                    form.items[key].quantity += 1
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
                    toast.error('Failed to remove item')
                }
            }
        }

        const updateItemField = (payload) => {
            const { key, field, value } = payload
            const item = form.items[key]
            if (!item) return

            // Update field with proper type conversion
            if (['quantity', 'unit_price', 'discount', 'tax'].includes(field)) {
                item[field] = Number(value) || 0
            } else {
                item[field] = value
            }

            // Recalculate item totals
            const quantity = Number(item.quantity) || 1
            const unitPrice = Number(item.unit_price) || 0
            const discountAmount = Number(item.discount) || 0
            const taxRate = Number(item.tax) || 0

            item.gross_amount = unitPrice * quantity
            item.net_amount = item.gross_amount - discountAmount
            item.tax_amount = item.net_amount * (taxRate / 100)
            item.total = item.net_amount + item.tax_amount

            calculateTotals()

            // Update server for critical fields
            if (['quantity', 'unit_price', 'discount', 'tax'].includes(field)) {
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
            let tax_amount = 0;
            let item_discount = 0;
            let gross_amount = 0;
            let total = 0;

            Object.values(form.items).forEach(item => {
                const quantity = Number(item.quantity) || 1
                const unitPrice = Number(item.unit_price) || 0
                const discountAmount = Number(item.discount) || 0
                const taxRate = Number(item.tax) || 0

                item.gross_amount = unitPrice * quantity
                item.net_amount = item.gross_amount - discountAmount
                item.tax_amount = item.net_amount * (taxRate / 100)

                // Use combo offer price if available, otherwise use regular total
                if (item.combo_offer_price && item.combo_offer_price > 0) {
                    item.total = item.combo_offer_price * quantity
                } else {
                    item.total = item.net_amount + item.tax_amount
                }

                total += item.total
                gross_amount += item.gross_amount
                item_discount += item.discount
                tax_amount += item.tax_amount
            })

            form.gross_amount = parseFloat(gross_amount).toFixed(2);
            form.item_discount = parseFloat(item_discount).toFixed(2);
            form.tax_amount = parseFloat(tax_amount).toFixed(2);
            form.total = parseFloat(total).toFixed(2);

            // Calculate grand total with discount and round off
            const otherDiscount = Number(form.other_discount) || 0
            const grandTotal = total - otherDiscount
            const roundedTotal = Math.round(grandTotal)

            form.round_off = parseFloat((roundedTotal - grandTotal).toFixed(2))
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

        // Handler for saving from EditItemModal
        const onEditItemSave = (updatedItem) => {
            console.log('Saving edited item:', updatedItem)
            if (editItemKey.value && form.items[editItemKey.value]) {
                console.log('Saving edited item:', updatedItem)
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
                item.tax_amount = item.net_amount * (item.tax / 100)
                item.total = item.net_amount + item.tax_amount

                // Save back to cart
                form.items[editItemKey.value] = { ...item }
                calculateTotals()
                showEditItemModal.value = false
                toast.success('Item updated successfully')
            }
        }

        const addNewCustomer = () => {
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
            } else {
                form.customer_mobile = ''
            }
        }

        const handleCustomerSelected = (customer) => {
            // Select the existing customer
            form.account_id = customer.id
            form.customer_mobile = customer.mobile || ''

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
                            customPaymentData.value : { payments: [] }) :
                        { payments: [] },
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
                }

                // Reset form or redirect for completed sales
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
            } catch (error) {
                console.log(error);
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
            if (!customer) return { name: 'Walk-in Customer', mobile: '' }

            if (typeof customer === 'string') {
                const parts = customer.includes(' - ') ? customer.split(' - ') : [customer, '']
                return { name: parts[0].trim(), mobile: parts[1]?.trim() || '' }
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
            customPaymentData.value = { payments: [], totalPaid: 0, balanceDue: 0 }

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
            // Update cart items with combo offer pricing
            Object.entries(comboData.comboOfferItems).forEach(([key, item]) => {
                if (form.items[key]) {
                    // Update the cart item with combo offer pricing
                    form.items[key] = {
                        ...form.items[key],
                        combo_offer_price: item.combo_offer_price || 0,
                        discount: item.discount || 0,
                        combo_offer_id: item.combo_offer_id || null
                    }
                }
            })

            // Store combo offers data for submission
            form.comboOffers = comboData.selectedComboOffers

            // Recalculate totals
            calculateTotals()

            toast.success('Combo offers applied successfully')
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
            } else {
                form.customer_mobile = ''
            }
        }, { immediate: true })

        // Watch for payment method changes to update confirmation modal
        watch(selectedPaymentMethod, () => {
            updateConfirmationData()
        })

        // Watch for custom payment data changes to update confirmation modal
        watch(customPaymentData, () => {
            updateConfirmationData()
        }, { deep: true })

        // Lifecycle
        onMounted(() => {
            // Initialize form with sale data if provided from controller FIRST
            initializeFormWithSaleData()

            loadProducts()
            fetchCustomers()

            // Handle viewport height and window width tracking
            const setVH = () => {
                const vh = window.innerHeight * 0.01
                document.documentElement.style.setProperty('--vh', `${vh}px`)
                windowWidth.value = window.innerWidth
            }

            setVH()
            window.addEventListener('resize', setVH)
            window.addEventListener('orientationchange', setVH)
        })

        onUnmounted(() => {
            const setVH = () => {
                const vh = window.innerHeight * 0.01
                document.documentElement.style.setProperty('--vh', `${vh}px`)
                windowWidth.value = window.innerWidth
            }

            window.removeEventListener('resize', setVH)
            window.removeEventListener('orientationchange', setVH)
        })

        const cartHeight = computed(() => {
            if (windowWidth.value >= 1024) {
                // Desktop: Take full height minus header, footer and padding
                return 'calc(100vh - 150px)'
            } else {
                // Mobile: Limited height
                return 'calc(40vh - 100px)'
            }
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

            // Computed
            totalQuantity,
            discountPercentage,
            cartItemsByEmployee,
            cartHeight,
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
            handleCustomerChange,
            handleCustomerSaved,
            handleCustomerSelected,
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
            handleComboOfferSave
        }
    }
}
</script>

<style scoped>
@import '../../../css/pos-common.css';
@import '../../../css/pos.css';
@import '../../../css/mobile-responsive.css';

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
