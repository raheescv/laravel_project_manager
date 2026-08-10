<template>
    <div v-if="show" class="posx-modal-backdrop" style="z-index: 9999" aria-labelledby="modal-title" role="dialog"
        aria-modal="true" @click.self="close">
        <div class="posx-modal" style="max-width: 34rem" @click.stop>

            <div class="posx-modal-head">
                <h4 class="posx-modal-title">
                    <i class="fa fa-cogs"></i>
                    <span>
                        Custom Payment
                        <span class="posx-modal-sub">Split across payment methods</span>
                    </span>
                </h4>
                <button type="button" class="posx-modal-close" @click="close" aria-label="Close">
                    <i class="fa fa-times"></i>
                </button>
            </div>

            <div class="posx-modal-body">
                <!-- Payable -->
                <div class="posx-section text-center mb-3">
                    <div class="posx-section-title justify-center">Total Payable Amount</div>
                    <div class="posx-amount" style="font-size: 1.5rem; color: var(--pos-acc)">
                        {{ formatNumber(totalAmount) }}
                    </div>
                </div>

                <!-- Add payment -->
                <h5 class="posx-section-title">Add Payment</h5>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 mb-3">
                    <div>
                        <label for="payment-method-select" class="posx-label mb-1">Payment Method</label>
                        <div class="relative">
                            <select id="payment-method-select" v-model="paymentForm.payment_method_id" class="posx-field">
                                <option value="">Select Payment Method</option>
                                <option v-for="method in paymentMethods" :key="method.id" :value="method.id">
                                    {{ method.name }}
                                </option>
                            </select>
                            <i class="fa fa-angle-down posx-caret"></i>
                        </div>
                    </div>
                    <div>
                        <label for="payment-amount" class="posx-label mb-1">Amount</label>
                        <div class="flex gap-1.5">
                            <input id="payment-amount" v-model.number="paymentForm.amount" type="number" step="0.01"
                                class="posx-field" placeholder="Enter amount" @focus="$event.target.select()">
                            <button type="button" class="posx-btn posx-btn-primary shrink-0" @click="addPayment"
                                :disabled="!paymentForm.payment_method_id || !paymentForm.amount">
                                <i class="fa fa-plus"></i>
                                <span class="hidden sm:inline">Add</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div v-if="errorMessage" class="posx-note is-danger mb-3">
                    <i class="fa fa-exclamation-circle mr-1.5"></i>{{ errorMessage }}
                </div>

                <!-- Summary -->
                <div v-if="payments.length > 0" class="posx-panel posx-panel-flush overflow-x-auto mb-3">
                    <table class="posx-table">
                        <thead>
                            <tr>
                                <th>Payment Method</th>
                                <th class="num">Amount</th>
                                <th class="num">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(payment, index) in payments" :key="index">
                                <td class="posx-ink font-bold truncate" :title="payment.name">{{ payment.name }}</td>
                                <td class="num"><span class="posx-amount">{{ formatNumber(payment.amount) }}</span></td>
                                <td class="num">
                                    <button type="button" class="posx-icon-btn is-danger ms-auto"
                                        @click="removePayment(index)" title="Remove payment">
                                        <i class="fa fa-trash-o"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Totals -->
                <div class="posx-section">
                    <div class="posx-kv">
                        <span><i class="fa fa-check-circle"></i> Total Paid</span>
                        <span class="posx-amount-ok">{{ formatNumber(totalPaid) }}</span>
                    </div>
                    <div class="posx-kv">
                        <span><i class="fa fa-exclamation-circle"></i> Balance Due</span>
                        <span :class="balanceDue > 0 ? 'posx-amount-danger' : 'posx-amount-ok'">
                            {{ formatNumber(balanceDue) }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="posx-modal-foot">
                <button type="button" class="posx-btn posx-btn-ghost" @click="close">
                    <i class="fa fa-times"></i> Cancel
                </button>
                <button type="button" class="posx-btn posx-btn-primary" @click="savePayments"
                    :disabled="payments.length === 0">
                    <i class="fa fa-save"></i> Save Payment
                </button>
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

        const formatNumber = (value, decimals = 2) => {
            const num = parseFloat(value) || 0
            return num.toLocaleString('en-US', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals
            })
        }

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
            formatNumber,
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
