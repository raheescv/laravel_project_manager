<template>
    <div v-if="show" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
        @click.self="closeModal">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl mx-4 max-h-[90vh] overflow-hidden" @click.stop>
            <!-- Header -->
            <div
                class="flex items-center justify-between p-4 border-b border-gray-200 bg-gradient-to-r from-purple-50 to-pink-50">
                <div class="flex items-center space-x-3">
                    <div class="p-2 bg-purple-100 rounded-full">
                        <i class="fa fa-user text-purple-600 text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Customer Details</h3>
                        <p class="text-sm text-gray-600">View customer information and history</p>
                    </div>
                </div>
                <button @click="closeModal"
                    class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-full transition-all duration-200">
                    <i class="fa fa-times text-lg"></i>
                </button>
            </div>

            <!-- Content -->
            <div class="p-4 overflow-y-auto max-h-[calc(90vh-8rem)]">
                <div v-if="loading" class="flex items-center justify-center py-8">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-500"></div>
                    <span class="ml-2 text-gray-600">Loading customer details...</span>
                </div>

                <div v-else-if="customer" class="space-y-6">
                    <!-- Customer Basic Info -->
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-4 border border-blue-200">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-lg font-semibold text-gray-800 flex items-center">
                                <i class="fa fa-user-circle mr-2 text-blue-600"></i>
                                Basic Information
                            </h4>
                            <button @click="editCustomer"
                                class="bg-blue-500 text-white px-3 py-1 rounded-md text-sm hover:bg-blue-600 transition-colors">
                                <i class="fa fa-edit mr-1"></i>Edit
                            </button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs font-medium text-gray-600">Full Name</label>
                                <p class="text-sm font-semibold text-gray-800">{{ customer.name }}</p>
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-600">Mobile</label>
                                <p class="text-sm font-semibold text-gray-800">
                                    <i class="fa fa-phone mr-1 text-green-500"></i>
                                    {{ customer.mobile }}
                                </p>
                            </div>
                            <div v-if="customer.email">
                                <label class="text-xs font-medium text-gray-600">Email</label>
                                <p class="text-sm font-semibold text-gray-800">
                                    <i class="fa fa-envelope mr-1 text-blue-500"></i>
                                    {{ customer.email }}
                                </p>
                            </div>
                            <div v-if="customer.whatsapp_mobile">
                                <label class="text-xs font-medium text-gray-600">WhatsApp</label>
                                <p class="text-sm font-semibold text-gray-800">
                                    <i class="fa fa-whatsapp mr-1 text-green-500"></i>
                                    {{ customer.whatsapp_mobile }}
                                </p>
                            </div>
                            <div v-if="customer.company">
                                <label class="text-xs font-medium text-gray-600">Company</label>
                                <p class="text-sm font-semibold text-gray-800">
                                    <i class="fa fa-building mr-1 text-orange-500"></i>
                                    {{ customer.company }}
                                </p>
                            </div>
                            <div v-if="customer.nationality">
                                <label class="text-xs font-medium text-gray-600">Nationality</label>
                                <p class="text-sm font-semibold text-gray-800">
                                    <i class="fa fa-flag mr-1 text-red-500"></i>
                                    {{ customer.nationality }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Sales Summary -->
                    <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg p-4 border border-green-200">
                        <h4 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fa fa-chart-line mr-2 text-green-600"></i>
                            Sales Summary
                        </h4>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="text-center p-3 bg-white rounded-lg border border-green-200">
                                <div class="text-2xl font-bold text-green-600">{{ totalSales || 0 }}</div>
                                <div class="text-xs text-gray-600">Total Sales</div>
                            </div>
                            <div class="text-center p-3 bg-white rounded-lg border border-green-200">
                                <div class="text-2xl font-bold text-blue-600">{{ totalAmount || 0 }}</div>
                                <div class="text-xs text-gray-600">Total Amount</div>
                            </div>
                            <div class="text-center p-3 bg-white rounded-lg border border-green-200">
                                <div class="text-2xl font-bold text-purple-600">{{ lastPurchase || 'N/A' }}</div>
                                <div class="text-xs text-gray-600">Last Purchase</div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Sales -->
                    <div v-if="recentSales.length > 0" class="bg-gradient-to-r from-purple-50 to-pink-50 rounded-lg p-4 border border-purple-200">
                        <h4 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fa fa-history mr-2 text-purple-600"></i>
                            Recent Sales
                        </h4>

                        <div class="space-y-2">
                            <div v-for="sale in recentSales" :key="sale.id"
                                class="bg-white rounded-lg p-3 border border-purple-200 hover:shadow-md transition-shadow">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <div class="font-semibold text-sm text-gray-800">
                                            Sale #{{ sale.invoice_no }}
                                        </div>
                                        <div class="text-xs text-gray-600">
                                            {{ sale.date }} • {{ sale.items_count }} items
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-bold text-sm text-green-600">
                                            {{ sale.total }}
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
                    <div v-else class="bg-gradient-to-r from-gray-50 to-slate-50 rounded-lg p-4 border border-gray-200">
                        <div class="text-center py-4">
                            <i class="fa fa-shopping-cart text-4xl text-gray-400 mb-2"></i>
                            <h4 class="text-lg font-semibold text-gray-600">No Recent Sales</h4>
                            <p class="text-sm text-gray-500">This customer hasn't made any purchases yet.</p>
                        </div>
                    </div>
                </div>

                <div v-else class="text-center py-8">
                    <i class="fa fa-exclamation-triangle text-4xl text-gray-400 mb-2"></i>
                    <h4 class="text-lg font-semibold text-gray-600">Customer Not Found</h4>
                    <p class="text-sm text-gray-500">Unable to load customer details.</p>
                </div>
            </div>

            <!-- Footer -->
            <div class="flex items-center justify-end space-x-3 p-4 border-t border-gray-200 bg-gradient-to-r from-gray-50 to-blue-50">
                <button type="button" @click="closeModal"
                    class="flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all duration-200 shadow-sm hover:shadow-md">
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
const lastPurchase = ref(null)
const recentSales = ref([])

const closeModal = () => {
    emit('close')
}

const editCustomer = () => {
    emit('edit', customer.value)
}

const loadCustomerDetails = async () => {
    if (!props.customerId) return

    loading.value = true
    try {
        const response = await axios.get(`/account/customer/${props.customerId}/details`)
        customer.value = response.data.customer
        totalSales.value = response.data.total_sales || 0
        totalAmount.value = response.data.total_amount || 0
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
