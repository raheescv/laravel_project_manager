<template>
    <div v-if="show" class="posx-modal-backdrop" aria-labelledby="modal-title" role="dialog" aria-modal="true"
        @click.self="close">
        <div class="posx-modal" style="max-width: 32rem" @click.stop>

            <div class="posx-modal-head">
                <h4 class="posx-modal-title">
                    <i class="fa fa-check-circle"></i>
                    <span>
                        Confirm Sale
                        <span class="posx-modal-sub">{{ customerName.name }}</span>
                    </span>
                </h4>
                <button type="button" class="posx-modal-close" @click="close" aria-label="Close">
                    <i class="fa fa-times"></i>
                </button>
            </div>

            <div class="posx-modal-body">
                <!-- Customer -->
                <div class="posx-section mb-3">
                    <div class="flex items-center gap-2.5">
                        <span class="posx-avatar"><i class="fa fa-user"></i></span>
                        <div class="min-w-0">
                            <div class="posx-ink text-xs font-extrabold truncate">{{ customerName.name }}</div>
                            <div class="posx-muted text-xs font-semibold">{{ customerName.mobile || 'No mobile number' }}</div>
                        </div>
                    </div>
                </div>

                <!-- Payment method -->
                <div class="mb-3">
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <h6 class="posx-section-title mb-0">Payment Method</h6>
                        <label class="posx-check">
                            <input v-model="localSendToWhatsapp" type="checkbox">
                            <i class="fa fa-whatsapp"></i>
                            <span>WhatsApp</span>
                        </label>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-1.5">
                        <button type="button" class="posx-pay"
                            :class="{ 'is-active': localPaymentMethod === 1 || localPaymentMethod === '' }"
                            @click="$emit('update:paymentMethod', 1)">
                            <i class="fa fa-money"></i><span>Cash</span>
                        </button>
                        <button type="button" class="posx-pay" :class="{ 'is-active': localPaymentMethod === 2 }"
                            @click="$emit('update:paymentMethod', 2)">
                            <i class="fa fa-credit-card"></i><span>Card</span>
                        </button>
                        <button type="button" class="posx-pay" :class="{ 'is-active': localPaymentMethod === 'credit' }"
                            @click="$emit('update:paymentMethod', 'credit')">
                            <i class="fa fa-file-text-o"></i><span>Credit</span>
                        </button>
                        <button type="button" class="posx-pay" :class="{ 'is-active': localPaymentMethod === 'custom' }"
                            @click="$emit('openCustomPayment')">
                            <i class="fa fa-cogs"></i><span>Custom</span>
                            <span v-if="localCustomPaymentCount > 0" class="posx-pay-count">{{ localCustomPaymentCount }}</span>
                        </button>
                    </div>
                </div>

                <!-- Summary -->
                <div class="posx-section mb-3">
                    <h6 class="posx-section-title">Transaction Summary</h6>
                    <div class="posx-kv">
                        <span><i class="fa fa-shopping-cart"></i> Grand Total</span>
                        <span class="posx-amount">{{ formatNumber(grandTotal) }}</span>
                    </div>
                    <div class="posx-kv">
                        <span><i class="fa fa-credit-card"></i> Paid Amount</span>
                        <span class="posx-amount-muted font-bold">{{ formatNumber(paidAmount) }}</span>
                    </div>
                    <div class="posx-kv">
                        <span><i :class="`fa ${balanceIcon}`"></i> {{ balanceText }}</span>
                        <span class="font-bold" :class="balanceTone">{{ formatNumber(Math.abs(balanceAmount)) }}</span>
                    </div>
                </div>

                <!-- Custom payment breakdown -->
                <div v-if="paymentMethods" class="posx-section mb-3">
                    <h6 class="posx-section-title">Payment Breakdown</h6>
                    <div class="posx-ink-2 text-xs font-semibold" style="font-variant-numeric: tabular-nums">
                        {{ paymentMethods }}
                    </div>
                </div>

                <!-- Status -->
                <div class="posx-note" :class="balanceNoteTone">
                    <div class="font-extrabold mb-0.5">
                        <i :class="`fa ${balanceIcon} mr-1.5`"></i>{{ statusText }}
                    </div>
                    <div class="opacity-90">{{ statusDescription }}</div>
                </div>
            </div>

            <div class="posx-modal-foot">
                <button type="button" class="posx-btn posx-btn-ghost" @click="close">
                    <i class="fa fa-times"></i> Cancel
                </button>
                <button type="button" class="posx-btn posx-btn-primary" @click="submit" :disabled="loading">
                    <i class="fa" :class="loading ? 'fa-spinner fa-spin' : 'fa-check-circle'"></i>
                    {{ loading ? 'Processing...' : (balanceAmount === 0 ? 'Submit' : 'Submit Anyway') }}
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import { computed } from 'vue'

export default {
    name: 'SaleConfirmationModal',
    props: {
        show: {
            default: false
        },
        saleData: {
            required: true
        },
        loading: {
            default: false
        },
        paymentMethod: {
            default: 1
        },
        sendToWhatsapp: {
            default: false
        },
        openCustomPayment: {
            default: () => { }
        }
    },
    emits: ['close', 'submit', 'update:paymentMethod', 'openCustomPayment', 'update:sendToWhatsapp'],
    setup(props, { emit }) {
        const customerName = computed(() => {
            if (props.saleData.account_id && props.saleData.customerName) {
                // If customerName is an object with name and mobile properties
                if (typeof props.saleData.customerName === 'object' && props.saleData.customerName.name) {
                    return props.saleData.customerName
                }
                // If customerName is a string, create an object
                return {
                    name: props.saleData.customerName || 'Selected Customer',
                    mobile: props.saleData.customer_mobile || ''
                }
            }
            return {
                name: 'Walk-in Customer',
                mobile: ''
            }
        })

        const grandTotal = computed(() => {
            return parseFloat(props.saleData.grand_total) || 0
        })

        const paidAmount = computed(() => {
            // For credit payment (no payment), the paid amount is 0
            if (props.saleData.payment_method === 'credit' || props.paymentMethod === 'credit') {
                return 0
            }
            // For custom payment, use the total paid from custom payment data
            if (props.saleData.payment_method === 'custom' && props.saleData.custom_payment_data) {
                return parseFloat(props.saleData.custom_payment_data.totalPaid) || 0
            }
            // For cash/card payment, the paid amount equals the grand total
            return grandTotal.value
        })

        const balanceAmount = computed(() => {
            return grandTotal.value - paidAmount.value
        })

        const balanceIcon = computed(() => {
            if (balanceAmount.value === 0) return 'fa-check-circle'
            if (balanceAmount.value > 0) return 'fa-exclamation-triangle'
            return 'fa-arrow-down'
        })

        // Tone classes instead of hardcoded hexes, so the semantic colours come
        // from the POS token layer and stay correct in dark mode.
        const balanceTone = computed(() => {
            if (balanceAmount.value === 0) return 'posx-amount-ok'
            if (balanceAmount.value > 0) return 'posx-amount-danger'
            return 'posx-amount'
        })

        const balanceNoteTone = computed(() => {
            if (balanceAmount.value === 0) return ''
            if (balanceAmount.value > 0) return 'is-danger'
            return 'is-warn'
        })

        const balanceText = computed(() => {
            if (balanceAmount.value > 0) return 'Remaining Balance'
            if (balanceAmount.value < 0) return 'Overpaid Amount'
            return 'Balance'
        })

        const statusText = computed(() => {
            if (balanceAmount.value === 0) return 'Ready to Submit'
            if (balanceAmount.value > 0) return 'Partial Payment'
            return 'Overpaid Transaction'
        })

        const statusDescription = computed(() => {
            if (balanceAmount.value === 0) return 'Transaction is fully paid and ready to submit'
            if (balanceAmount.value > 0) return 'Transaction has a remaining balance'
            return 'Transaction amount exceeds payment'
        })

        const formatNumber = (value, decimals = 2) => {
            const num = parseFloat(value) || 0
            return num.toLocaleString('en-US', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals
            })
        }

        const paymentMethods = computed(() => {
            if (props.saleData.payment_method === 'custom' && props.saleData.custom_payment_data?.payments) {
                return props.saleData.custom_payment_data.payments
                    .map(p => `${p.name}: ${formatNumber(parseFloat(p.amount))}`)
                    .join(', ')
            }
            if (props.saleData.payment_method === 1 || props.paymentMethod === 1) return 'Cash Payment'
            if (props.saleData.payment_method === 2 || props.paymentMethod === 2) return 'Card Payment'
            if (props.saleData.payment_method === 'credit' || props.paymentMethod === 'credit') return 'Credit Payment (No Payment)'
            return null
        })

        const localPaymentMethod = computed({
            get() {
                return props.paymentMethod
            },
            set(value) {
                emit('update:paymentMethod', value)
            }
        })

        const localSendToWhatsapp = computed({
            get() {
                return props.sendToWhatsapp
            },
            set(value) {
                emit('update:sendToWhatsapp', value)
            }
        })

        const localCustomPaymentCount = computed(() => {
            if (props.saleData.custom_payment_data?.payments) {
                return props.saleData.custom_payment_data.payments.length
            }
            return 0
        })

        const close = () => {
            emit('close')
        }

        const submit = () => {
            emit('submit')
        }

        return {
            customerName,
            grandTotal,
            paidAmount,
            balanceAmount,
            balanceIcon,
            balanceTone,
            balanceNoteTone,
            balanceText,
            statusText,
            statusDescription,
            paymentMethods,
            localPaymentMethod,
            localSendToWhatsapp,
            localCustomPaymentCount,
            formatNumber,
            close,
            submit
        }
    }
}
</script>
