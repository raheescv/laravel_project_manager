<template>
    <div v-if="show"
        class="fixed inset-0 bg-gray-900 bg-opacity-40 backdrop-blur-sm flex items-center justify-center z-50 p-1 sm:p-2"
        @click.self="closeModal">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[95vh] sm:max-h-[90vh] overflow-hidden flex flex-col"
            @click.stop>
            <!-- Header -->
            <div
                class="flex items-center justify-between p-2 sm:p-3 border-b border-gray-100 bg-gradient-to-r from-slate-50 to-blue-50 flex-shrink-0">
                <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                    <div class="p-1.5 sm:p-2 bg-blue-100 rounded-lg flex-shrink-0 shadow-sm">
                        <i class="fa fa-user text-blue-600 text-base sm:text-lg"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="text-base sm:text-lg font-bold text-slate-800 truncate">Customer Details</h3>
                        <p class="text-xs text-slate-600 hidden sm:block">View customer information and
                            history</p>
                    </div>
                </div>
                <button @click="closeModal"
                    class="p-1.5 sm:p-2 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition-all duration-200 flex-shrink-0 ml-2">
                    <i class="fa fa-times text-base sm:text-lg"></i>
                </button>
            </div>

            <!-- Content -->
            <div class="p-2 sm:p-3 overflow-y-auto flex-1 bg-gray-50">
                <div v-if="loading" class="flex items-center justify-center py-6">
                    <div class="animate-spin rounded-full h-8 w-8 border-4 border-blue-200 border-t-blue-600"></div>
                    <span class="ml-2 text-sm text-slate-600 font-medium">Loading customer details...</span>
                </div>

                <div v-else-if="customer" class="space-y-2 sm:space-y-3">
                    <!-- Customer Basic Info -->
                    <div
                        class="bg-white rounded-lg p-2.5 sm:p-3 border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-2 gap-2">
                            <h4 class="text-sm sm:text-base font-bold text-slate-800 flex items-center">
                                <i class="fa fa-user-circle mr-2 text-blue-500"></i>
                                Basic Information
                            </h4>
                            <button @click="editCustomer"
                                class="bg-blue-600 text-white px-3 py-1.5 rounded-lg text-xs font-medium hover:bg-blue-700 transition-all duration-200 shadow-sm hover:shadow-md w-full sm:w-auto flex items-center justify-center">
                                <i class="fa fa-edit mr-1"></i>Edit Customer
                            </button>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 sm:gap-3">
                            <!-- Column 1: Full Name -->
                            <div
                                class="break-words pb-2 border-b border-slate-100 last:border-b-0 sm:last:border-b sm:border-b-0">
                                <label
                                    class="text-xs font-semibold text-slate-500 uppercase tracking-wide block mb-1">Full
                                    Name</label>
                                <p class="text-sm font-semibold text-slate-800 break-words">{{ customer.name }}</p>
                            </div>
                            <!-- Column 2: Mobile -->
                            <div
                                class="break-words pb-2 border-b border-slate-100 last:border-b-0 sm:last:border-b sm:border-b-0">
                                <label
                                    class="text-xs font-semibold text-slate-500 uppercase tracking-wide block mb-1">Mobile</label>
                                <p class="text-sm font-semibold text-slate-800 break-words">
                                    <i class="fa fa-phone mr-1.5 text-emerald-500"></i>
                                    <a :href="`tel:${customer.mobile}`"
                                        class="text-blue-600 hover:text-blue-700 hover:underline transition-colors">{{
                                            customer.mobile
                                        }}</a>
                                </p>
                            </div>
                            <!-- Column 3: Credit Period -->
                            <div
                                class="break-words pb-2 border-b border-slate-100 last:border-b-0 sm:last:border-b sm:border-b-0">
                                <label
                                    class="text-xs font-semibold text-slate-500 uppercase tracking-wide block mb-1">Credit
                                    Period</label>
                                <p class="text-sm font-semibold text-slate-800 break-words">
                                    <i class="fa fa-calendar mr-1.5 text-teal-500"></i>
                                    <span v-if="customer.credit_period_days">
                                        {{ customer.credit_period_days }} {{ customer.credit_period_days === 1 ? 'Day' :
                                            'Days' }}
                                    </span>
                                    <span v-else class="text-slate-400 font-normal">Not set</span>
                                </p>
                            </div>
                            <!-- Remaining fields -->
                            <div v-if="customer.email"
                                class="break-words pb-2 border-b border-slate-100 last:border-b-0 sm:last:border-b sm:border-b-0">
                                <label
                                    class="text-xs font-semibold text-slate-500 uppercase tracking-wide block mb-1">Email</label>
                                <p class="text-sm font-semibold text-slate-800 break-words">
                                    <i class="fa fa-envelope mr-1.5 text-blue-500"></i>
                                    <a :href="`mailto:${customer.email}`"
                                        class="text-blue-600 hover:text-blue-700 hover:underline break-all transition-colors">{{
                                            customer.email }}</a>
                                </p>
                            </div>
                            <div v-if="customer.whatsapp_mobile"
                                class="break-words pb-2 border-b border-slate-100 last:border-b-0 sm:last:border-b sm:border-b-0">
                                <label
                                    class="text-xs font-semibold text-slate-500 uppercase tracking-wide block mb-1">WhatsApp</label>
                                <p class="text-sm font-semibold text-slate-800 break-words">
                                    <i class="fa fa-whatsapp mr-1.5 text-emerald-500"></i>
                                    <a :href="`https://wa.me/${customer.whatsapp_mobile.replace(/[^0-9]/g, '')}`"
                                        target="_blank"
                                        class="text-blue-600 hover:text-blue-700 hover:underline transition-colors">{{
                                        customer.whatsapp_mobile }}</a>
                                </p>
                            </div>
                            <div v-if="customer.company"
                                class="break-words pb-2 border-b border-slate-100 last:border-b-0 sm:last:border-b sm:border-b-0">
                                <label
                                    class="text-xs font-semibold text-slate-500 uppercase tracking-wide block mb-1">Company</label>
                                <p class="text-sm font-semibold text-slate-800 break-words">
                                    <i class="fa fa-building mr-1.5 text-amber-500"></i>
                                    {{ customer.company }}
                                </p>
                            </div>
                            <div v-if="customer.nationality"
                                class="break-words pb-2 border-b border-slate-100 last:border-b-0 sm:last:border-b sm:border-b-0">
                                <label
                                    class="text-xs font-semibold text-slate-500 uppercase tracking-wide block mb-1">Nationality</label>
                                <p class="text-sm font-semibold text-slate-800 break-words">
                                    <i class="fa fa-flag mr-1.5 text-rose-500"></i>
                                    {{ customer.nationality }}
                                </p>
                            </div>
                            <div v-if="customer.customer_type && customer.customer_type.name"
                                class="break-words pb-2 border-b border-slate-100 last:border-b-0 sm:last:border-b sm:border-b-0">
                                <label
                                    class="text-xs font-semibold text-slate-500 uppercase tracking-wide block mb-1">Customer
                                    Type</label>
                                <p class="text-sm font-semibold text-slate-800 break-words">
                                    <i class="fa fa-tag mr-1.5 text-indigo-500"></i>
                                    {{ customer.customer_type.name }}
                                </p>
                            </div>
                            <div v-if="customer.dob"
                                class="break-words pb-2 border-b border-slate-100 last:border-b-0 sm:last:border-b sm:border-b-0">
                                <label
                                    class="text-xs font-semibold text-slate-500 uppercase tracking-wide block mb-1">Date
                                    of Birth</label>
                                <p class="text-sm font-semibold text-slate-800 break-words">
                                    <i class="fa fa-birthday-cake mr-1.5 text-pink-500"></i>
                                    {{ formatDate(customer.dob) }}
                                </p>
                            </div>
                            <div v-if="customer.id_no"
                                class="break-words pb-2 border-b border-slate-100 last:border-b-0 sm:last:border-b sm:border-b-0">
                                <label
                                    class="text-xs font-semibold text-slate-500 uppercase tracking-wide block mb-1">ID
                                    Number</label>
                                <p class="text-sm font-semibold text-slate-800 break-words">
                                    <i class="fa fa-id-badge mr-1.5 text-amber-500"></i>
                                    {{ customer.id_no }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Sales Summary -->
                    <div
                        class="bg-white rounded-lg p-2.5 sm:p-3 border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                        <h4 class="text-sm sm:text-base font-bold text-slate-800 mb-2 flex items-center">
                            <i class="fa fa-chart-line mr-2 text-emerald-500"></i>
                            Sales Summary
                        </h4>

                        <div class="grid grid-cols-2 lg:grid-cols-4 gap-2">
                            <div
                                class="text-center p-2 bg-gradient-to-br from-emerald-50 to-teal-50 rounded-lg border border-emerald-100 shadow-sm hover:shadow-md transition-shadow">
                                <div class="text-lg sm:text-xl font-bold text-emerald-700 break-words mb-0.5">{{
                                    totalSales || 0
                                    }}</div>
                                <div class="text-xs font-medium text-slate-600 uppercase tracking-wide">Total Sales
                                </div>
                            </div>
                            <div
                                class="text-center p-2 bg-gradient-to-br from-blue-50 to-indigo-50 rounded-lg border border-blue-100 shadow-sm hover:shadow-md transition-shadow">
                                <div class="text-lg sm:text-xl font-bold text-blue-700 break-words mb-0.5">{{
                                    formatCurrency(totalAmount) }}</div>
                                <div class="text-xs font-medium text-slate-600 uppercase tracking-wide">Total Amount
                                </div>
                            </div>
                            <div
                                class="text-center p-2 bg-gradient-to-br from-teal-50 to-cyan-50 rounded-lg border border-teal-100 shadow-sm hover:shadow-md transition-shadow">
                                <div class="text-lg sm:text-xl font-bold text-teal-700 break-words mb-0.5">{{
                                    formatCurrency(totalPaid) }}</div>
                                <div class="text-xs font-medium text-slate-600 uppercase tracking-wide">Total Paid</div>
                            </div>
                            <div
                                class="text-center p-2 bg-gradient-to-br from-rose-50 to-pink-50 rounded-lg border border-rose-100 shadow-sm hover:shadow-md transition-shadow">
                                <div class="text-lg sm:text-xl font-bold text-rose-700 break-words mb-0.5">{{
                                    formatCurrency(totalBalance) }}</div>
                                <div class="text-xs font-medium text-slate-600 uppercase tracking-wide">Outstanding
                                </div>
                            </div>
                        </div>
                        <div v-if="lastPurchase" class="mt-2 pt-2 border-t border-slate-200">
                            <div class="text-center">
                                <div
                                    class="text-xs font-semibold text-slate-700 inline-flex items-center px-2 py-1 bg-slate-100 rounded-lg">
                                    <i class="fa fa-calendar mr-1.5 text-indigo-500"></i>
                                    Last Purchase: {{ formatDate(lastPurchase) }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Sales & Feedbacks Tabs -->
                    <div
                        class="bg-white rounded-lg p-2.5 sm:p-3 border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                        <!-- Tab Headers -->
                        <div
                            class="flex border-b border-slate-200 mb-2 bg-slate-50 rounded-t-lg -mx-2.5 sm:-mx-3 px-2.5 sm:px-3">
                            <button @click="activeTab = 'sales'" :class="[
                                'flex-1 px-3 sm:px-4 py-2 text-xs font-semibold transition-all duration-200 rounded-t-lg',
                                activeTab === 'sales'
                                    ? 'text-blue-700 border-b-2 border-blue-600 bg-white shadow-sm'
                                    : 'text-slate-600 hover:text-blue-600 hover:bg-white/50'
                            ]">
                                <i class="fa fa-history mr-1.5"></i>
                                Recent Sales
                            </button>
                            <button @click="activeTab = 'feedbacks'" :class="[
                                'flex-1 px-3 sm:px-4 py-2 text-xs font-semibold transition-all duration-200 rounded-t-lg',
                                activeTab === 'feedbacks'
                                    ? 'text-blue-700 border-b-2 border-blue-600 bg-white shadow-sm'
                                    : 'text-slate-600 hover:text-blue-600 hover:bg-white/50'
                            ]">
                                <i class="fa fa-comments mr-1.5"></i>
                                Customer Feedbacks
                            </button>
                        </div>

                        <!-- Tab Content: Recent Sales -->
                        <div v-if="activeTab === 'sales'">
                            <div v-if="recentSales.length > 0" class="space-y-1.5">
                                <div v-for="sale in recentSales" :key="sale.id"
                                    class="bg-slate-50 rounded-lg p-2 border border-slate-200 hover:border-blue-300 hover:shadow-md transition-all duration-200">
                                    <div class="grid grid-cols-3 gap-2">
                                        <!-- Column 1: Invoice & Date -->
                                        <div class="min-w-0">
                                            <div class="font-bold text-sm text-slate-800 break-words mb-0.5">
                                                <a :href="`/sale/view/${sale.id}`" target="_blank"
                                                    class="text-blue-600 hover:text-blue-700 hover:underline transition-colors">
                                                    #{{ sale.invoice_no }}
                                                </a>
                                            </div>
                                            <div class="text-xs text-slate-500 flex items-center">
                                                <i class="fa fa-calendar mr-1 text-slate-400"></i>
                                                {{ new Date(sale.date).toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' }) }}
                                            </div>
                                        </div>
                                        <!-- Column 2: Rating -->
                                        <div class="flex items-center justify-phcenter">
                                            <div v-if="sale.rating"
                                                class="flex items-center gap-0.5 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200">
                                                <i v-for="i in 5" :key="i"
                                                    :class="['fa fa-star text-xs', sale.rating >= i ? 'text-amber-400' : 'text-slate-300']"></i>
                                                <span class="text-xs font-semibold text-slate-700 ml-0.5">{{ sale.rating
                                                    }}/5</span>
                                            </div>
                                            <span v-else class="text-xs text-slate-400 italic">No rating</span>
                                        </div>
                                        <!-- Column 3: Grand Total & Balance -->
                                        <div class="text-right min-w-0">
                                            <div class="font-bold text-sm text-emerald-600 mb-0.5">
                                                {{ formatCurrency(sale.total) }}
                                            </div>
                                            <div v-if="sale.balance>0" class="text-xs font-medium text-slate-500">
                                                Balance: <span class="text-rose-600">{{ formatCurrency(sale.balance) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-4">
                                <i class="fa fa-shopping-cart text-3xl text-slate-300 mb-2"></i>
                                <h4 class="text-sm font-semibold text-slate-600 mb-1">No Recent Sales</h4>
                                <p class="text-xs text-slate-500">This customer hasn't made any purchases yet.
                                </p>
                            </div>
                        </div>

                        <!-- Tab Content: Customer Feedbacks -->
                        <div v-if="activeTab === 'feedbacks'">
                            <div v-if="customerFeedbacks.length > 0" class="space-y-1.5">
                                <div v-for="feedback in customerFeedbacks" :key="feedback.id"
                                    class="bg-slate-50 rounded-lg p-2 border border-slate-200 hover:border-blue-300 hover:shadow-md transition-all duration-200">
                                    <div class="space-y-2">
                                        <!-- Header: Invoice, Date, Rating, and Type -->
                                        <div
                                            class="flex items-center justify-between flex-wrap gap-1.5 pb-1.5 border-b border-slate-200">
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                <a :href="`/sale/view/${feedback.id}`" target="_blank"
                                                    class="font-bold text-sm text-blue-600 hover:text-blue-700 hover:underline transition-colors">
                                                    #{{ feedback.invoice_no }}
                                                </a>
                                                <span class="text-xs text-slate-500 flex items-center">
                                                    <i class="fa fa-calendar mr-1 text-slate-400"></i>
                                                    {{ new Date(feedback.date).toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' }) }}
                                                </span>
                                                <div v-if="feedback.rating"
                                                    class="flex items-center gap-0.5 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200">
                                                    <i v-for="i in 5" :key="i"
                                                        :class="['fa fa-star text-xs', feedback.rating >= i ? 'text-amber-400' : 'text-slate-300']"></i>
                                                    <span class="text-xs font-semibold text-slate-700 ml-0.5">{{
                                                        feedback.rating
                                                        }}/5</span>
                                                </div>
                                            </div>
                                            <span v-if="feedback.feedback_type" :class="[
                                                'px-2 py-0.5 rounded-full text-xs font-semibold shadow-sm',
                                                feedback.feedback_type === 'compliment' ? 'bg-emerald-100 text-emerald-700 border border-emerald-200' :
                                                    feedback.feedback_type === 'suggestion' ? 'bg-blue-100 text-blue-700 border border-blue-200' :
                                                        feedback.feedback_type === 'complaint' ? 'bg-rose-100 text-rose-700 border border-rose-200' :
                                                            'bg-slate-100 text-slate-700 border border-slate-200'
                                            ]">
                                                {{ formatFeedbackType(feedback.feedback_type) }}
                                            </span>
                                        </div>
                                        <!-- Comment -->
                                        <div v-if="feedback.feedback"
                                            class="text-xs text-slate-700 bg-white rounded-lg p-2 border border-slate-200">
                                            <div class="flex items-start">
                                                <i class="fa fa-comment mr-1.5 text-blue-500 mt-0.5"></i>
                                                <span class="flex-1">{{ feedback.feedback }}</span>
                                            </div>
                                        </div>
                                        <div v-else
                                            class="text-xs text-slate-400 italic bg-white rounded-lg p-2 border border-slate-200">
                                            No comment provided
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-4">
                                <i class="fa fa-comments text-3xl text-slate-300 mb-2"></i>
                                <h4 class="text-sm font-semibold text-slate-600 mb-1">No Feedbacks</h4>
                                <p class="text-xs text-slate-500">This customer hasn't provided any feedback
                                    yet.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="text-center py-6">
                    <i class="fa fa-exclamation-triangle text-3xl text-slate-300 mb-2"></i>
                    <h4 class="text-sm font-semibold text-slate-600 mb-1">Customer Not Found</h4>
                    <p class="text-xs text-slate-500">Unable to load customer details.</p>
                </div>
            </div>

            <!-- Footer -->
            <div
                class="flex items-center justify-end space-x-2 p-2 sm:p-3 border-t border-slate-200 bg-gradient-to-r from-slate-50 to-blue-50 flex-shrink-0">
                <button type="button" @click="closeModal"
                    class="flex items-center justify-center px-3 sm:px-4 py-1.5 sm:py-2 text-xs sm:text-sm font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 hover:border-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 shadow-sm hover:shadow-md w-full sm:w-auto">
                    <i class="fa fa-times mr-1.5 text-slate-500"></i>
                    Close
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import { useToast } from 'vue-toastification'

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    customerId: {
        type: [String, Number],
        default: null
    }
})

const emit = defineEmits(['close', 'edit'])

const toast = useToast()
const loading = ref(false)
const customer = ref(null)
const totalSales = ref(0)
const totalAmount = ref(0)
const totalPaid = ref(0)
const totalBalance = ref(0)
const lastPurchase = ref(null)
const recentSales = ref([])
const feedbacks = ref([])
const activeTab = ref('sales')

const closeModal = () => {
    emit('close')
}

const editCustomer = () => {
    emit('edit', customer.value)
}

const formatCurrency = (amount) => {
    if (amount === null || amount === undefined) return '0.00'
    return parseFloat(amount).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    })
}

const formatDate = (date) => {
    if (!date) return ''
    const d = new Date(date)
    return d.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    })
}

const formatFeedbackType = (type) => {
    const types = {
        'compliment': 'Compliment',
        'suggestion': 'Suggestion',
        'complaint': 'Complaint'
    }
    return types[type] || type
}

// Use the separate feedbacks list from backend
const customerFeedbacks = computed(() => {
    return feedbacks.value
})

const loadCustomerDetails = async () => {
    if (!props.customerId) return

    loading.value = true
    try {
        const response = await axios.get(`/account/customer/${props.customerId}/details`)
        customer.value = response.data.customer
        totalSales.value = response.data.total_sales || 0
        totalAmount.value = response.data.total_amount || 0
        totalPaid.value = response.data.total_paid || 0
        totalBalance.value = response.data.total_balance || 0
        lastPurchase.value = response.data.last_purchase || null
        recentSales.value = response.data.recent_sales || []
        feedbacks.value = response.data.feedbacks || []
    } catch (error) {
        toast.error('Failed to load customer details')
        console.error('Error loading customer details:', error)
    } finally {
        loading.value = false
    }
}

// Watch for changes in customerId and show prop
watch(() => [props.customerId, props.show], ([newCustomerId, newShow]) => {
    if (newShow && newCustomerId) {
        loadCustomerDetails()
    }
}, { immediate: true })
</script>
