<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog"
        aria-modal="true">
        <!-- Background overlay -->
        <div class="flex items-center justify-center min-h-screen p-2 text-center">
            <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm transition-opacity" aria-hidden="true"
                @click="close">
            </div>

            <!-- Modal positioning -->
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <!-- Modal panel - Ultra compact (matches SaleConfirmationModal) -->
            <div
                class="relative inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-2 sm:align-middle sm:max-w-md w-full max-h-[90vh] overflow-y-auto">
                <!-- Ultra Compact Header -->
                <div
                    class="bg-gradient-to-r from-blue-600 via-blue-700 to-indigo-700 px-3 py-2 text-white flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="bg-white/20 p-1 rounded-md mr-2">
                            <i class="fa fa-credit-card text-white text-xs"></i>
                        </div>
                        <h4 class="text-base font-bold text-white">
                            Custom Payment
                        </h4>
                    </div>
                    <button type="button" @click="close"
                        class="text-white hover:text-gray-200 focus:outline-none transition-colors">
                        <i class="fa fa-times text-xs"></i>
                    </button>
                </div>

                <!-- Ultra Compact Body -->
                <div class="px-3 py-3">
                    <!-- Total Payable - Ultra Compact -->
                    <div
                        class="bg-gradient-to-r from-slate-50 to-blue-50 border border-slate-200 rounded-lg p-1.5 mb-2 text-center relative overflow-hidden">
                        <div class="relative z-10">
                            <p class="text-slate-500 text-xs font-bold mb-0.5">Total Payable</p>
                            <h4 class="font-bold text-lg text-slate-800">{{ formatNumber(totalAmount) }}</h4>
                        </div>
                    </div>

                    <!-- Add Payment Section - Ultra Compact -->
                    <div class="mb-3">
                        <h6 class="text-xs font-bold text-slate-800 mb-1.5 flex items-center gap-1">
                            <i class="fa fa-plus-circle text-blue-500 text-xs"></i>
                            <span>Add New Payment</span>
                        </h6>
                        <div class="bg-gradient-to-r from-slate-50 to-blue-50 border border-slate-200 rounded-lg p-1.5">
                            <div class="grid grid-cols-2 gap-1.5 mb-1.5">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-0.5">Date</label>
                                    <input v-model="paymentForm.date" type="date"
                                        class="w-full bg-white border border-slate-200 rounded-lg px-2 py-1 text-xs font-bold text-slate-700 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-0.5">Method</label>
                                    <VSelect
                                        v-model="paymentForm.payment_method_id"
                                        :options="paymentMethods.map(m => ({ value: m.id, label: m.name }))"
                                        placeholder="Select Method"
                                    />
                                </div>
                            </div>
                            <div class="flex gap-1.5">
                                <div class="flex-1">
                                    <label class="block text-xs font-semibold text-slate-600 mb-0.5">Amount</label>
                                    <input v-model.number="paymentForm.amount" type="number" step="0.01"
                                        class="w-full bg-white border border-slate-200 rounded-lg px-2 py-1 text-xs font-bold text-blue-600 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all"
                                        placeholder="0.00" @focus="$event.target.select()">
                                </div>
                                <div class="flex items-end">
                                    <button type="button" @click="addPayment"
                                        :disabled="!paymentForm.payment_method_id || !paymentForm.amount"
                                        class="h-[26px] px-3 rounded-lg text-xs font-semibold transition-all disabled:opacity-50 disabled:cursor-not-allowed bg-gradient-to-r from-blue-500 to-indigo-600 text-white border-0 hover:from-blue-600 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-blue-500">
                                        Add
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Error Message - Ultra Compact -->
                    <Transition name="fade">
                        <div v-if="errorMessage"
                            class="mb-2 bg-red-50 border border-red-200 rounded-lg p-1.5 flex items-center gap-1.5">
                            <i class="fa fa-exclamation-circle text-red-500 text-xs"></i>
                            <span class="text-red-700 text-xs font-bold">{{ errorMessage }}</span>
                        </div>
                    </Transition>

                    <!-- Payment Summary Table - Ultra Compact -->
                    <div v-if="payments.length > 0" class="mb-2">
                        <h6 class="text-xs font-bold text-slate-800 mb-1 flex items-center gap-1">
                            <i class="fa fa-receipt text-emerald-500 text-xs"></i>
                            Payment Breakdown
                        </h6>
                        <div class="bg-gradient-to-r from-slate-50 to-blue-50 border border-slate-200 rounded-lg p-1.5">
                            <div class="space-y-0.5 max-h-32 overflow-y-auto">
                                <div v-for="(payment, index) in payments" :key="index"
                                    class="flex items-center justify-between py-0.5 border-b border-slate-200 last:border-0 group">
                                    <div class="flex flex-col min-w-0">
                                        <span class="text-xs font-bold text-slate-800 truncate">{{ payment.name }}</span>
                                        <span class="text-[10px] font-semibold text-slate-500">{{ formatDate(payment.date) }}</span>
                                    </div>
                                    <div class="flex items-center gap-1.5 shrink-0">
                                        <span class="text-xs font-bold text-blue-600">{{ formatNumber(payment.amount) }}</span>
                                        <button type="button" @click="removePayment(index)"
                                            class="w-5 h-5 flex items-center justify-center rounded bg-rose-100 text-rose-500 opacity-0 group-hover:opacity-100 hover:bg-rose-500 hover:text-white transition-all">
                                            <i class="fa fa-trash text-[9px]"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Totals - Ultra Compact -->
                        <div class="grid grid-cols-2 gap-1.5 mt-1.5">
                            <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-lg p-1.5 text-center">
                                <span class="text-[10px] font-bold text-emerald-600 uppercase">Total Paid</span>
                                <div class="font-bold text-sm text-emerald-700">{{ formatNumber(totalPaid) }}</div>
                            </div>
                            <div class="rounded-lg p-1.5 text-center border transition-colors"
                                :class="balanceDue > 0 ? 'bg-gradient-to-r from-red-50 to-rose-50 border-red-200' : 'bg-gradient-to-r from-slate-50 to-gray-50 border-slate-200'">
                                <span class="text-[10px] font-bold uppercase"
                                    :class="balanceDue > 0 ? 'text-rose-600' : 'text-slate-500'">Balance Due</span>
                                <div class="font-bold text-sm"
                                    :class="balanceDue > 0 ? 'text-rose-700' : 'text-slate-800'">{{ formatNumber(balanceDue) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ultra Compact Footer -->
                <div class="bg-gradient-to-r from-slate-50 to-gray-50 px-3 py-2 border-t border-slate-200">
                    <div class="flex flex-col sm:flex-row justify-end gap-1.5">
                        <button type="button" @click="close"
                            class="inline-flex items-center justify-center px-3 py-1.5 border border-slate-300 shadow-sm text-xs font-semibold rounded-lg text-slate-700 bg-white hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
                            <i class="fa fa-times mr-1 text-xs"></i>
                            Cancel
                        </button>
                        <button type="button" @click="savePayments" :disabled="payments.length === 0" :class="[
                            'inline-flex items-center justify-center px-3 py-1.5 border border-transparent text-xs font-semibold rounded-lg text-white shadow-lg focus:outline-none focus:ring-2 focus:ring-offset-2 transition-all duration-200',
                            payments.length === 0 ? 'opacity-75 cursor-not-allowed' : 'hover:scale-105',
                            'bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 focus:ring-green-500'
                        ]">
                            <i class="fa fa-check-circle mr-1 text-xs"></i>
                            Confirm Payments
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
import VSelect from '@/components/VSelect.vue'

export default {
    name: 'CustomPaymentModal',
    components: { VSelect },
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

        const getTodayDate = () => {
            return new Date().toISOString().split('T')[0]
        }

        const paymentForm = ref({
            payment_method_id: '',
            amount: '',
            date: getTodayDate()
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

        const formatNumber = (value, decimals = 2) => {
            const num = parseFloat(value) || 0
            return num.toLocaleString('en-US', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals
            })
        }

        const formatDate = (dateString) => {
            if (!dateString) return ''
            const date = new Date(dateString)
            return date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
        }

        // Watch for prop changes to reset form
        watch(() => props.show, (newVal) => {
            if (newVal) {
                // Reset form when modal opens
                const currentBalance = props.totalAmount - (props.initialPayments || []).reduce((sum, payment) => sum + payment.amount, 0)
                paymentForm.value = {
                    payment_method_id: '',
                    amount: currentBalance > 0 ? currentBalance : props.totalAmount,
                    date: getTodayDate()
                }
                payments.value = [...(props.initialPayments || []).map(p => ({
                    ...p,
                    date: p.date || getTodayDate()
                }))]
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

            if (totalPaid.value + amount > props.totalAmount + 0.01) { // Added small tolerance
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
                amount: amount,
                date: paymentForm.value.date
            })

            // Reset form but keep the date
            paymentForm.value = {
                ...paymentForm.value,
                payment_method_id: '',
                amount: balanceDue.value > 0 ? balanceDue.value : ''
            }

            toast.success('Payment added successfully')
        }

        const removePayment = (index) => {
            if (confirm('Are you sure you want to remove this payment?')) {
                // The actual backend deletion is handled by UpdateTailoringOrderAction
                // when the order is saved (it removes payments not present in the sent array)
                payments.value.splice(index, 1)
                paymentForm.value.amount = balanceDue.value

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
            formatNumber,
            formatDate,
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
