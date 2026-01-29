<template>
    <div v-if="show" class="fixed inset-0 z-[9999] overflow-y-auto" aria-labelledby="modal-title" role="dialog"
        aria-modal="true">
        <!-- Background overlay -->
        <div class="flex items-center justify-center min-h-screen p-4 text-center">
            <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" @click="close"></div>

            <!-- Modal panel - Centered on page -->
            <div
                class="relative bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all max-w-lg w-full mx-auto border border-slate-200">
                <!-- Header -->
                <div class="px-5 py-4 border-b border-slate-100 bg-white flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center text-blue-600">
                            <i class="fa fa-credit-card text-sm"></i>
                        </div>
                        <h4 class="text-sm font-black text-slate-800 uppercase tracking-widest leading-none">Custom Payment</h4>
                    </div>
                    <button type="button" @click="close" class="w-8 h-8 rounded-full flex items-center justify-center text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-all">
                        <i class="fa fa-times text-sm"></i>
                    </button>
                </div>

                <!-- Body -->
                <div class="p-5 flex flex-col gap-6">
                    <!-- Total Payable Amount -->
                    <div class="text-center py-6 bg-slate-50 rounded-2xl border border-slate-200/60 relative overflow-hidden group">
                        <div class="absolute inset-0 bg-gradient-to-br from-blue-600/5 to-indigo-600/5 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                        <p class="text-[0.65rem] font-black text-slate-400 uppercase tracking-[0.2em] mb-1 relative z-10">Total Payable</p>
                        <h1 class="text-3xl font-black text-slate-900 leading-none relative z-10">{{ formatNumber(totalAmount) }}</h1>
                    </div>

                    <!-- Add Payment Section -->
                    <div class="flex flex-col gap-4">
                        <div class="flex items-center gap-2">
                            <h5 class="text-[0.65rem] font-black text-slate-400 uppercase tracking-widest">Add New Payment</h5>
                            <div class="flex-1 h-px bg-slate-100"></div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-3">
                             <div class="col-span-1">
                                <label class="block text-[0.6rem] font-black text-slate-400 uppercase tracking-widest mb-1 px-1">Date</label>
                                <input v-model="paymentForm.date" type="date"
                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-bold text-slate-700 focus:bg-white focus:border-blue-500 transition-all">
                            </div>
                            <div class="col-span-1">
                                <label class="block text-[0.6rem] font-black text-slate-400 uppercase tracking-widest mb-1 px-1">Method</label>
                                <div class="relative">
                                    <select v-model="paymentForm.payment_method_id"
                                        class="w-full appearance-none bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-bold text-slate-700 focus:bg-white focus:border-blue-500 transition-all">
                                        <option value="">Select Method</option>
                                        <option v-for="method in paymentMethods" :key="method.id" :value="method.id">
                                            {{ method.name }}
                                        </option>
                                    </select>
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none text-slate-400">
                                        <i class="fa fa-chevron-down text-[10px]"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-span-2">
                                <label class="block text-[0.6rem] font-black text-slate-400 uppercase tracking-widest mb-1 px-1">Amount</label>
                                <div class="flex gap-2">
                                    <input v-model.number="paymentForm.amount" type="number" step="0.01"
                                        class="flex-1 bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-black text-blue-600 focus:bg-white focus:border-blue-500 transition-all"
                                        placeholder="0.00" @focus="$event.target.select()">
                                    <button type="button" @click="addPayment"
                                        :disabled="!paymentForm.payment_method_id || !paymentForm.amount" 
                                        class="px-5 py-1.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed"
                                        :class="(!paymentForm.payment_method_id || !paymentForm.amount) ? 'bg-slate-100 text-slate-400' : 'bg-blue-600 text-white shadow-lg shadow-blue-200'">
                                        Add
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Error Message -->
                    <Transition name="fade">
                        <div v-if="errorMessage" class="bg-rose-50 border border-rose-100 rounded-xl p-3 flex items-center gap-2">
                            <i class="fa fa-exclamation-circle text-rose-500 text-xs text-xs"></i>
                            <span class="text-rose-700 text-[0.7rem] font-bold">{{ errorMessage }}</span>
                        </div>
                    </Transition>

                    <!-- Payment Summary Table -->
                    <div v-if="payments.length > 0" class="flex flex-col gap-3">
                        <div class="flex items-center gap-2">
                            <h5 class="text-[0.65rem] font-black text-slate-400 uppercase tracking-widest">Payment Breakdown</h5>
                            <div class="flex-1 h-px bg-slate-100"></div>
                        </div>
                        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden divide-y divide-slate-100">
                            <div v-for="(payment, index) in payments" :key="index"
                                class="flex items-center justify-between px-4 py-3 hover:bg-slate-50/50 transition-colors group">
                                <div class="flex flex-col">
                                    <span class="text-xs font-black text-slate-800">{{ payment.name }}</span>
                                    <span class="text-[0.6rem] font-bold text-slate-400 uppercase tracking-widest">{{ formatDate(payment.date) }}</span>
                                </div>
                                <div class="flex items-center gap-4">
                                    <span class="text-xs font-black text-blue-600">{{ formatNumber(payment.amount) }}</span>
                                    <button type="button" @click="removePayment(index)"
                                        class="w-7 h-7 flex items-center justify-center rounded-lg bg-rose-50 text-rose-500 opacity-0 group-hover:opacity-100 hover:bg-rose-500 hover:text-white transition-all">
                                        <i class="fa fa-trash text-[10px]"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Totals -->
                        <div class="grid grid-cols-2 gap-3 mt-1">
                            <div class="bg-emerald-50/50 rounded-xl p-3 border border-emerald-100 flex flex-col items-center">
                                <span class="text-[0.55rem] font-black text-emerald-600 uppercase tracking-widest mb-0.5">Total Paid</span>
                                <span class="text-sm font-black text-emerald-700 leading-none">{{ formatNumber(totalPaid) }}</span>
                            </div>
                            <div class="rounded-xl p-3 border flex flex-col items-center transition-colors"
                                :class="balanceDue > 0 ? 'bg-rose-50/50 border-rose-100' : 'bg-slate-50/50 border-slate-200'">
                                <span class="text-[0.55rem] font-black uppercase tracking-widest mb-0.5"
                                    :class="balanceDue > 0 ? 'text-rose-600' : 'text-slate-400'">Balance Due</span>
                                <span class="text-sm font-black leading-none"
                                    :class="balanceDue > 0 ? 'text-rose-700' : 'text-slate-900'">{{ formatNumber(balanceDue) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-5 py-4 bg-slate-50/50 border-t border-slate-100 flex justify-end gap-2">
                    <button type="button" @click="close"
                        class="px-5 py-2 text-xs font-black text-slate-400 uppercase tracking-widest hover:text-slate-600 transition-colors">
                        Cancel
                    </button>
                    <button type="button" @click="savePayments" :disabled="payments.length === 0" 
                        class="px-8 py-2 bg-slate-950 text-white text-xs font-black uppercase tracking-widest rounded-xl hover:bg-slate-800 transition-all active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg shadow-slate-200">
                        Confirm Payments
                    </button>
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
