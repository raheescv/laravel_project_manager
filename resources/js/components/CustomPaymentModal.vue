<template>
    <div v-if="show" class="fixed inset-0 z-[9999] overflow-y-auto" aria-labelledby="modal-title" role="dialog"
        aria-modal="true">
        <!-- Background overlay -->
        <div class="flex items-center justify-center min-h-screen p-4 text-center">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="close">
            </div>

            <!-- Modal panel - Centered on page -->
            <div
                class="relative bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all max-w-lg w-full mx-auto">
                <!-- Header -->
                <div class="bg-blue-600 px-3 py-2 text-white">
                    <div class="flex items-center justify-between">
                        <h4 class="text-base font-semibold text-white mb-0">Custom Payment</h4>
                        <button type="button" @click="close" class="text-white hover:text-gray-200 focus:outline-none">
                            <i class="fa fa-times text-sm"></i>
                        </button>
                    </div>
                </div>

                <!-- Body -->
                <div class="px-3 py-3">
                    <!-- Total Payable Amount -->
                    <div class="text-center mb-3">
                        <h2 class="text-base font-bold text-gray-900 mb-1">Total Payable Amount</h2>
                        <h1 class="text-xl sm:text-2xl font-bold text-blue-600">₹{{ totalAmount }}</h1>
                    </div>

                    <hr class="my-3 border-gray-200">

                    <!-- Add Payment Section -->
                    <h5 class="text-sm font-semibold mb-2">Add Payment</h5>
                    <div class="space-y-2 mb-3">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <div>
                                <label for="payment-method-select" class="block text-xs font-medium text-gray-700 mb-1">
                                    Payment Method
                                </label>
                                <select v-model="paymentForm.payment_method_id"
                                    class="w-full text-xs rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 py-1">
                                    <option value="">Select Payment Method</option>
                                    <option v-for="method in paymentMethods" :key="method.id" :value="method.id">
                                        {{ method.name }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label for="payment-amount" class="block text-xs font-medium text-gray-700 mb-1">
                                    Amount
                                </label>
                                <div class="flex gap-1">
                                    <input v-model.number="paymentForm.amount" type="number" step="0.01"
                                        class="flex-1 text-xs rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 py-1"
                                        placeholder="Enter amount" @focus="$event.target.select()">
                                    <button type="button" @click="addPayment"
                                        :disabled="!paymentForm.payment_method_id || !paymentForm.amount" :class="[
                                            'px-2 py-1 rounded-md font-medium text-xs whitespace-nowrap',
                                            (!paymentForm.payment_method_id || !paymentForm.amount)
                                                ? 'bg-gray-400 text-gray-600 cursor-not-allowed'
                                                : 'bg-green-600 text-white hover:bg-green-700'
                                        ]">
                                        <i class="fa fa-plus mr-1"></i>
                                        <span class="hidden sm:inline">Add</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Error Message -->
                    <div v-if="errorMessage" class="mb-2 p-2 bg-red-50 border border-red-200 rounded-md">
                        <div class="flex">
                            <i class="fa fa-exclamation-circle text-red-400 mr-2 mt-0.5 text-xs"></i>
                            <span class="text-red-800 text-xs">{{ errorMessage }}</span>
                        </div>
                    </div>

                    <!-- Payment Summary -->
                    <div v-if="payments.length > 0">
                        <h5 class="text-sm font-semibold mb-2">Payment Summary</h5>
                        <div class="overflow-x-auto">
                            <div class="min-w-full">
                                <div
                                    class="bg-gray-50 grid grid-cols-3 gap-2 px-2 py-1 text-xs font-medium text-gray-500 uppercase tracking-wider rounded-t-md">
                                    <div>Payment Method</div>
                                    <div class="text-right">Amount</div>
                                    <div class="text-center">Action</div>
                                </div>
                                <div class="bg-white border border-gray-200 rounded-b-md">
                                    <div v-for="(payment, index) in payments" :key="index"
                                        class="grid grid-cols-3 gap-2 px-2 py-1 border-b border-gray-100 last:border-b-0 hover:bg-gray-50">
                                        <div class="text-xs text-gray-900 truncate" :title="payment.name">
                                            {{ payment.name }}
                                        </div>
                                        <div class="text-xs font-semibold text-gray-900 text-right">
                                            ₹{{ payment.amount.toFixed(2) }}
                                        </div>
                                        <div class="text-center">
                                            <button type="button" @click="removePayment(index)"
                                                class="inline-flex items-center px-1 py-0.5 border border-red-300 text-xs font-medium rounded text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-1 focus:ring-red-500">
                                                <i class="fa fa-trash text-xs"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <hr class="my-3 border-gray-200">
                    </div>

                    <!-- Payment Totals -->
                    <div class="bg-gray-50 rounded-lg p-2 space-y-1">
                        <div class="flex justify-between items-center">
                            <span class="text-xs font-semibold text-green-600">Total Paid:</span>
                            <span class="text-xs font-bold text-green-600">₹{{ totalPaid.toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs font-semibold text-red-600">Balance Due:</span>
                            <span class="text-xs font-bold text-red-600">₹{{ balanceDue.toFixed(2) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="bg-gray-50 px-3 py-2 text-right">
                    <div class="flex justify-end space-x-2">
                        <button type="button" @click="close"
                            class="inline-flex items-center px-3 py-1 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fa fa-times mr-1"></i>
                            Cancel
                        </button>
                        <button type="button" @click="savePayments" :disabled="payments.length === 0" :class="[
                            'inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md text-white focus:outline-none focus:ring-2 focus:ring-offset-2',
                            payments.length === 0
                                ? 'bg-gray-400 cursor-not-allowed'
                                : 'bg-blue-600 hover:bg-blue-700 focus:ring-blue-500'
                        ]">
                            <i class="fa fa-save mr-1"></i>
                            Save Payment
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { computed, ref, watch } from 'vue'
import { useToast } from 'vue-toastification'

export default {
    name: 'CustomPaymentModal',
    props: {
        show: {
            type: Boolean,
            default: false
        },
        totalAmount: {
            type: Number,
            required: true
        },
        paymentMethods: {
            type: Array,
            default: () => []
        },
        initialPayments: {
            type: Array,
            default: () => []
        }
    },
    emits: ['close', 'save'],
    setup(props, { emit }) {
        const toast = useToast()

        const paymentForm = ref({
            payment_method_id: '',
            amount: ''
        })

        const payments = ref([])
        const errorMessage = ref('')

        // Computed properties
        const totalPaid = computed(() => {
            return payments.value.reduce((sum, payment) => sum + payment.amount, 0)
        })

        const balanceDue = computed(() => {
            return props.totalAmount - totalPaid.value
        })

        // Watch for prop changes to reset form
        watch(() => props.show, (newVal) => {
            if (newVal) {
                // Reset form when modal opens
                const currentBalance = props.totalAmount - (props.initialPayments || []).reduce((sum, payment) => sum + payment.amount, 0)
                paymentForm.value = {
                    payment_method_id: '',
                    amount: currentBalance > 0 ? currentBalance : props.totalAmount
                }
                payments.value = [...(props.initialPayments || [])]
                errorMessage.value = ''
            }
        })

        const addPayment = () => {
            errorMessage.value = ''

            if (!paymentForm.value.payment_method_id || !paymentForm.value.amount) {
                errorMessage.value = 'Please select a payment method and enter an amount.'
                return
            }

            const amount = parseFloat(paymentForm.value.amount)
            if (amount <= 0) {
                errorMessage.value = 'Amount must be greater than 0.'
                return
            }

            if (totalPaid.value + amount > props.totalAmount) {
                errorMessage.value = 'Total payments cannot exceed the payable amount.'
                return
            }

            const selectedMethod = props.paymentMethods.find(m => m.id == paymentForm.value.payment_method_id)
            if (!selectedMethod) {
                errorMessage.value = 'Please select a valid payment method.'
                return
            }

            // Add payment to the list
            payments.value.push({
                payment_method_id: paymentForm.value.payment_method_id,
                name: selectedMethod.name,
                amount: amount
            })

            // Reset form
            paymentForm.value = {
                payment_method_id: '',
                amount: balanceDue.value
            }

            toast.success('Payment added successfully')
        }

        const removePayment = (index) => {
            if (confirm('Are you sure you want to remove this payment?')) {
                payments.value.splice(index, 1)
                const newBalance = props.totalAmount - (totalPaid.value)
                paymentForm.value = {
                    payment_method_id: '',
                    amount: balanceDue.value
                }

                toast.success('Payment removed successfully')
            }
        }

        const close = () => {
            emit('close')
        }

        const savePayments = () => {
            if (payments.value.length === 0) {
                errorMessage.value = 'Please add at least one payment method.'
                return
            }

            // Emit the payments data
            emit('save', {
                payments: payments.value,
                totalPaid: totalPaid.value,
                balanceDue: balanceDue.value
            })

            // Use setTimeout to ensure close event is emitted after save event is processed
            setTimeout(() => {
                close()
            }, 100)
        }

        return {
            paymentForm,
            payments,
            errorMessage,
            totalPaid,
            balanceDue,
            addPayment,
            removePayment,
            close,
            savePayments
        }
    }
}
</script>

<style scoped>
/* Add any custom styles if needed */
</style>
