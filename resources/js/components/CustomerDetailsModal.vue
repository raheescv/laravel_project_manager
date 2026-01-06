<template>
    <div v-if="show" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-1 sm:p-2"
        @click.self="closeModal">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[95vh] sm:max-h-[90vh] overflow-hidden flex flex-col" @click.stop>
            <!-- Header -->
            <div
                class="flex items-center justify-between p-2 sm:p-3 border-b border-gray-200 bg-gradient-to-r from-purple-50 to-pink-50 flex-shrink-0">
                <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
                    <div class="p-1.5 sm:p-2 bg-purple-100 rounded-full flex-shrink-0">
                        <i class="fa fa-user text-purple-600 text-base sm:text-lg"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="text-base sm:text-lg font-semibold text-gray-900 truncate">Customer Details</h3>
                        <p class="text-xs sm:text-sm text-gray-600 hidden sm:block">View customer information and history</p>
                    </div>
                </div>
                <button @click="closeModal"
                    class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-full transition-all duration-200 flex-shrink-0 ml-2">
                    <i class="fa fa-times text-base sm:text-lg"></i>
                </button>
            </div>

            <!-- Content -->
            <div class="p-2 sm:p-3 overflow-y-auto flex-1">
                <div v-if="loading" class="flex items-center justify-center py-6">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-500"></div>
                    <span class="ml-2 text-sm sm:text-base text-gray-600">Loading customer details...</span>
                </div>

                <div v-else-if="customer" class="space-y-3 sm:space-y-4">
                    <!-- Customer Basic Info -->
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-2 sm:p-3 border border-blue-200">
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-2 sm:mb-3 gap-2">
                            <h4 class="text-base sm:text-lg font-semibold text-gray-800 flex items-center">
                                <i class="fa fa-user-circle mr-2 text-blue-600"></i>
                                Basic Information
                            </h4>
                            <button @click="editCustomer"
                                class="bg-blue-500 text-white px-3 py-1.5 rounded-md text-xs sm:text-sm hover:bg-blue-600 transition-colors w-full sm:w-auto">
                                <i class="fa fa-edit mr-1"></i>Edit
                            </button>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-3">
                            <div class="break-words">
                                <label class="text-xs font-medium text-gray-600 block mb-1">Full Name</label>
                                <p class="text-sm font-semibold text-gray-800 break-words">{{ customer.name }}</p>
                            </div>
                            <div class="break-words">
                                <label class="text-xs font-medium text-gray-600 block mb-1">Mobile</label>
                                <p class="text-sm font-semibold text-gray-800 break-words">
                                    <i class="fa fa-phone mr-1 text-green-500"></i>
                                    <a :href="`tel:${customer.mobile}`" class="hover:underline">{{ customer.mobile }}</a>
                                </p>
                            </div>
                            <div v-if="customer.email" class="break-words">
                                <label class="text-xs font-medium text-gray-600 block mb-1">Email</label>
                                <p class="text-sm font-semibold text-gray-800 break-words">
                                    <i class="fa fa-envelope mr-1 text-blue-500"></i>
                                    <a :href="`mailto:${customer.email}`" class="hover:underline break-all">{{ customer.email }}</a>
                                </p>
                            </div>
                            <div v-if="customer.whatsapp_mobile" class="break-words">
                                <label class="text-xs font-medium text-gray-600 block mb-1">WhatsApp</label>
                                <p class="text-sm font-semibold text-gray-800 break-words">
                                    <i class="fa fa-whatsapp mr-1 text-green-500"></i>
                                    <a :href="`https://wa.me/${customer.whatsapp_mobile.replace(/[^0-9]/g, '')}`" target="_blank" class="hover:underline">{{ customer.whatsapp_mobile }}</a>
                                </p>
                            </div>
                            <div v-if="customer.company" class="break-words">
                                <label class="text-xs font-medium text-gray-600 block mb-1">Company</label>
                                <p class="text-sm font-semibold text-gray-800 break-words">
                                    <i class="fa fa-building mr-1 text-orange-500"></i>
                                    {{ customer.company }}
                                </p>
                            </div>
                            <div v-if="customer.nationality" class="break-words">
                                <label class="text-xs font-medium text-gray-600 block mb-1">Nationality</label>
                                <p class="text-sm font-semibold text-gray-800 break-words">
                                    <i class="fa fa-flag mr-1 text-red-500"></i>
                                    {{ customer.nationality }}
                                </p>
                            </div>
                            <div v-if="customer.customer_type && customer.customer_type.name" class="break-words">
                                <label class="text-xs font-medium text-gray-600 block mb-1">Customer Type</label>
                                <p class="text-sm font-semibold text-gray-800 break-words">
                                    <i class="fa fa-tag mr-1 text-indigo-500"></i>
                                    {{ customer.customer_type.name }}
                                </p>
                            </div>
                            <div class="break-words">
                                <label class="text-xs font-medium text-gray-600 block mb-1">Credit Period</label>
                                <p class="text-sm font-semibold text-gray-800 break-words">
                                    <i class="fa fa-calendar mr-1 text-teal-500"></i>
                                    <span v-if="customer.credit_period_days">
                                        {{ customer.credit_period_days }} {{ customer.credit_period_days === 1 ? 'Day' : 'Days' }}
                                    </span>
                                    <span v-else class="text-gray-400">Not set</span>
                                </p>
                            </div>
                            <div v-if="customer.dob" class="break-words">
                                <label class="text-xs font-medium text-gray-600 block mb-1">Date of Birth</label>
                                <p class="text-sm font-semibold text-gray-800 break-words">
                                    <i class="fa fa-birthday-cake mr-1 text-pink-500"></i>
                                    {{ formatDate(customer.dob) }}
                                </p>
                            </div>
                            <div v-if="customer.id_no" class="break-words">
                                <label class="text-xs font-medium text-gray-600 block mb-1">ID Number</label>
                                <p class="text-sm font-semibold text-gray-800 break-words">
                                    <i class="fa fa-id-badge mr-1 text-yellow-500"></i>
                                    {{ customer.id_no }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Sales Summary -->
                    <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg p-2 sm:p-3 border border-green-200">
                        <h4 class="text-base sm:text-lg font-semibold text-gray-800 mb-2 sm:mb-3 flex items-center">
                            <i class="fa fa-chart-line mr-2 text-green-600"></i>
                            Sales Summary
                        </h4>

                        <div class="grid grid-cols-2 lg:grid-cols-4 gap-1.5 sm:gap-2">
                            <div class="text-center p-1.5 sm:p-2 bg-white rounded-lg border border-green-200">
                                <div class="text-lg sm:text-2xl font-bold text-green-600 break-words">{{ totalSales || 0 }}</div>
                                <div class="text-xs text-gray-600 mt-1">Total Sales</div>
                            </div>
                            <div class="text-center p-1.5 sm:p-2 bg-white rounded-lg border border-blue-200">
                                <div class="text-lg sm:text-2xl font-bold text-blue-600 break-words">{{ formatCurrency(totalAmount) }}</div>
                                <div class="text-xs text-gray-600 mt-1">Total Amount</div>
                            </div>
                            <div class="text-center p-1.5 sm:p-2 bg-white rounded-lg border border-emerald-200">
                                <div class="text-lg sm:text-2xl font-bold text-emerald-600 break-words">{{ formatCurrency(totalPaid) }}</div>
                                <div class="text-xs text-gray-600 mt-1">Total Paid</div>
                            </div>
                            <div class="text-center p-1.5 sm:p-2 bg-white rounded-lg border border-red-200">
                                <div class="text-lg sm:text-2xl font-bold text-red-600 break-words">{{ formatCurrency(totalBalance) }}</div>
                                <div class="text-xs text-gray-600 mt-1">Outstanding</div>
                            </div>
                        </div>
                        <div v-if="lastPurchase" class="mt-2 sm:mt-3 pt-2 sm:pt-3 border-t border-green-200">
                            <div class="text-center">
                                <div class="text-xs sm:text-sm font-semibold text-gray-700">
                                    <i class="fa fa-calendar mr-2 text-purple-500"></i>
                                    Last Purchase: {{ formatDate(lastPurchase) }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Sales -->
                    <div v-if="recentSales.length > 0" class="bg-gradient-to-r from-purple-50 to-pink-50 rounded-lg p-2 sm:p-3 border border-purple-200">
                        <h4 class="text-base sm:text-lg font-semibold text-gray-800 mb-2 sm:mb-3 flex items-center">
                            <i class="fa fa-history mr-2 text-purple-600"></i>
                            Recent Sales
                        </h4>

                        <div class="space-y-1.5">
                            <div v-for="sale in recentSales" :key="sale.id"
                                class="bg-white rounded-lg p-1.5 sm:p-2 border border-purple-200 hover:shadow-md transition-shadow">
                                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
                                    <div class="flex-1 min-w-0">
                                        <div class="font-semibold text-xs sm:text-sm text-gray-800 break-words">
                                            Sale #{{ sale.invoice_no }}
                                        </div>
                                        <div class="text-xs text-gray-600 mt-1">
                                            {{ sale.date }} • {{ sale.items_count }} items
                                        </div>
                                    </div>
                                    <div class="text-left sm:text-right flex-shrink-0">
                                        <div class="font-bold text-xs sm:text-sm text-green-600">
                                            {{ formatCurrency(sale.total) }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ sale.status }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- No Recent Sales -->
                    <div v-else class="bg-gradient-to-r from-gray-50 to-slate-50 rounded-lg p-2 sm:p-3 border border-gray-200">
                        <div class="text-center py-3">
                            <i class="fa fa-shopping-cart text-3xl sm:text-4xl text-gray-400 mb-2"></i>
                            <h4 class="text-base sm:text-lg font-semibold text-gray-600">No Recent Sales</h4>
                            <p class="text-xs sm:text-sm text-gray-500">This customer hasn't made any purchases yet.</p>
                        </div>
                    </div>
                </div>

                <div v-else class="text-center py-4 sm:py-6">
                    <i class="fa fa-exclamation-triangle text-3xl sm:text-4xl text-gray-400 mb-2"></i>
                    <h4 class="text-base sm:text-lg font-semibold text-gray-600">Customer Not Found</h4>
                    <p class="text-xs sm:text-sm text-gray-500">Unable to load customer details.</p>
                </div>
            </div>

            <!-- Footer -->
            <div class="flex items-center justify-end space-x-2 sm:space-x-3 p-2 sm:p-3 border-t border-gray-200 bg-gradient-to-r from-gray-50 to-blue-50 flex-shrink-0">
                <button type="button" @click="closeModal"
                    class="flex items-center justify-center px-2 sm:px-3 py-1.5 sm:py-2 text-xs sm:text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all duration-200 shadow-sm hover:shadow-md w-full sm:w-auto">
                    <i class="fa fa-times mr-2 text-gray-500"></i>
                    Close
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue'
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
