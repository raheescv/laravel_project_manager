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

            <!-- Modal panel - Ultra compact -->
            <div
                class="relative inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-2 sm:align-middle sm:max-w-md w-full max-h-[90vh] overflow-y-auto">
                <!-- Ultra Compact Header -->
                <div
                    class="bg-gradient-to-r from-blue-600 via-blue-700 to-indigo-700 px-3 py-2 text-white flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="bg-white/20 p-1 rounded-md mr-2">
                            <i class="fa fa-check-circle text-white text-xs"></i>
                        </div>
                        <h4 class="text-base font-bold text-white">
                            Confirm Sale
                        </h4>
                    </div>
                    <button type="button" @click="close"
                        class="text-white hover:text-gray-200 focus:outline-none transition-colors">
                        <i class="fa fa-times text-xs"></i>
                    </button>
                </div>

                <!-- Ultra Compact Body -->
                <div class="px-3 py-3">
                    <!-- Customer Info - Ultra Compact -->
                    <div
                        class="bg-gradient-to-r from-slate-50 to-blue-50 border border-slate-200 rounded-lg p-1.5 mb-2 text-center relative overflow-hidden">
                        <div class="relative z-10 flex items-center justify-center gap-1.5">
                            <div
                                class="w-6 h-6 bg-gradient-to-r from-blue-500 to-indigo-600 text-white rounded-full flex items-center justify-center shadow-sm">
                                <i class="fa fa-user text-xs"></i>
                            </div>
                            <div class="text-left">
                                <h4 class="font-bold text-slate-800 text-xs">{{ customerName.name }}</h4>
                                <p class="text-slate-600 text-xs">{{ customerName.mobile || 'No mobile number' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Method Selection - Ultra Compact -->
                    <div class="mb-3">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-2 gap-1">
                            <h6 class="text-xs font-bold text-slate-800 flex items-center gap-1">
                                <i class="fa fa-credit-card text-blue-500 text-xs"></i>
                                <span>Payment Method</span>
                            </h6>
                            <label
                                class="flex items-center text-xs gap-1 bg-green-50 border border-green-200 rounded px-1.5 py-0.5 cursor-pointer hover:bg-green-100 transition-colors">
                                <input v-model="localSendToWhatsapp" type="checkbox"
                                    class="rounded border-green-300 text-green-600 focus:ring-green-500">
                                <i class="fa fa-whatsapp text-green-500 text-xs"></i>
                                <span class="font-medium text-green-700">WhatsApp</span>
                            </label>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-1.5">
                            <!-- Cash Payment -->
                            <div class="payment-option">
                                <button type="button" @click="$emit('update:paymentMethod', 1)" :class="[
                                    'w-full h-12 flex flex-col items-center justify-center p-1 border-2 relative transition-all duration-300 rounded-lg hover:scale-105',
                                    localPaymentMethod === 1 || localPaymentMethod === ''
                                        ? 'bg-gradient-to-r from-green-500 to-emerald-600 border-green-500 shadow-lg text-white'
                                        : 'bg-white border-slate-200 text-slate-700 hover:shadow-md hover:border-green-300 hover:bg-green-50'
                                ]">
                                    <div class="icon-wrapper mb-0.5">
                                        <i
                                            :class="['fa fa-money text-sm', localPaymentMethod === 1 || localPaymentMethod === '' ? 'text-white' : 'text-green-500']"></i>
                                    </div>
                                    <span
                                        :class="['text-xs font-bold', localPaymentMethod === 1 || localPaymentMethod === '' ? 'text-white' : 'text-slate-700']">Cash</span>
                                    <div v-if="localPaymentMethod === 1 || localPaymentMethod === ''"
                                        class="absolute top-0.5 right-0.5"> <i
                                            class="fa fa-check-circle text-white bg-green-600 rounded-full text-xs"></i>
                                    </div>
                                </button>
                            </div>
                            <!-- Card Payment -->
                            <div class="payment-option">
                                <button type="button" @click="$emit('update:paymentMethod', 2)" :class="[
                                    'w-full h-12 flex flex-col items-center justify-center p-1 border-2 relative transition-all duration-300 rounded-lg hover:scale-105',
                                    localPaymentMethod === 2
                                        ? 'bg-gradient-to-r from-blue-500 to-indigo-600 border-blue-500 shadow-lg text-white'
                                        : 'bg-white border-slate-200 text-slate-700 hover:shadow-md hover:border-blue-300 hover:bg-blue-50'
                                ]">
                                    <div class="icon-wrapper mb-0.5">
                                        <i :class="[
                                            'fa fa-credit-card text-sm',
                                            localPaymentMethod === 2
                                                ? 'text-white'
                                                : 'text-blue-500'
                                        ]"></i>
                                    </div>
                                    <span :class="[
                                        'text-xs font-bold',
                                        localPaymentMethod === 2
                                            ? 'text-white'
                                            : 'text-slate-700'
                                    ]">Card</span>
                                    <div v-if="localPaymentMethod === 2" class="absolute top-0.5 right-0.5">
                                        <i class="fa fa-check-circle text-white bg-blue-600 rounded-full text-xs"></i>
                                    </div>
                                </button>
                            </div>
                            <!-- Credit Payment -->
                            <div class="payment-option">
                                <button type="button" @click="$emit('update:paymentMethod', 'credit')" :class="[
                                    'w-full h-12 flex flex-col items-center justify-center p-1 border-2 relative transition-all duration-300 rounded-lg hover:scale-105',
                                    localPaymentMethod === 'credit'
                                        ? 'bg-gradient-to-r from-purple-500 to-pink-600 border-purple-500 shadow-lg text-white'
                                        : 'bg-white border-slate-200 text-slate-700 hover:shadow-md hover:border-purple-300 hover:bg-purple-50'
                                ]">
                                    <div class="icon-wrapper mb-0.5">
                                        <i :class="[
                                            'fa fa-file-text-o text-sm',
                                            localPaymentMethod === 'credit'
                                                ? 'text-white'
                                                : 'text-purple-500'
                                        ]"></i>
                                    </div>
                                    <span :class="[
                                        'text-xs font-bold',
                                        localPaymentMethod === 'credit'
                                            ? 'text-white'
                                            : 'text-slate-700'
                                    ]">Credit</span>
                                    <div v-if="localPaymentMethod === 'credit'" class="absolute top-0.5 right-0.5">
                                        <i class="fa fa-check-circle text-white bg-purple-600 rounded-full text-xs"></i>
                                    </div>
                                </button>
                            </div>
                            <!-- Custom Payment -->
                            <div class="payment-option">
                                <button type="button" @click="$emit('openCustomPayment')" :class="[
                                    'w-full h-12 flex flex-col items-center justify-center p-1 border-2 relative transition-all duration-300 rounded-lg hover:scale-105',
                                    localPaymentMethod === 'custom'
                                        ? 'bg-gradient-to-r from-amber-500 to-orange-600 border-amber-500 shadow-lg text-white'
                                        : 'bg-white border-slate-200 text-slate-700 hover:shadow-md hover:border-amber-300 hover:bg-amber-50'
                                ]">
                                    <div class="icon-wrapper mb-0.5">
                                        <i :class="[
                                            'fa fa-cogs text-sm',
                                            localPaymentMethod === 'custom'
                                                ? 'text-white'
                                                : 'text-amber-500'
                                        ]"></i>
                                    </div>
                                    <span :class="[
                                        'text-xs font-bold',
                                        localPaymentMethod === 'custom'
                                            ? 'text-white'
                                            : 'text-slate-700'
                                    ]">
                                        {{ localCustomPaymentCount > 0 ? `${localCustomPaymentCount}` : 'Custom' }}
                                    </span>
                                    <div v-if="localPaymentMethod === 'custom'" class="absolute top-0.5 right-0.5">
                                        <i class="fa fa-check-circle text-white bg-amber-600 rounded-full text-xs"></i>
                                    </div>
                                    <div v-if="localCustomPaymentCount > 0 && localPaymentMethod !== 'custom'"
                                        class="absolute -top-0.5 -right-0.5 w-3 h-3 bg-green-500 rounded-full border border-white flex items-center justify-center">
                                        <span class="text-white text-xs font-bold">{{ localCustomPaymentCount }}</span>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Transaction Summary - Ultra Compact -->
                    <div class="mb-2">
                        <h6 class="text-xs font-bold text-slate-800 mb-1 flex items-center gap-1">
                            <i class="fa fa-receipt text-emerald-500 text-xs"></i>
                            Transaction Summary
                        </h6>
                        <div class="bg-gradient-to-r from-slate-50 to-blue-50 border border-slate-200 rounded-lg p-1.5">
                            <div class="space-y-0.5">
                                <div class="flex justify-between items-center py-0.5 border-b border-slate-200">
                                    <span class="text-slate-700 font-semibold text-xs flex items-center">
                                        <i class="fa fa-dollar-sign mr-1 text-slate-500 text-xs"></i>
                                        Grand Total
                                    </span>
                                    <span class="font-bold text-sm text-emerald-600">
                                        {{ formatNumber(grandTotal) }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center py-0.5 border-b border-slate-200">
                                    <span class="text-slate-700 font-semibold text-xs flex items-center">
                                        <i class="fa fa-credit-card mr-1 text-slate-500 text-xs"></i>
                                        Paid Amount
                                    </span>
                                    <span class="font-bold text-sm text-blue-600">
                                        {{ formatNumber(paidAmount) }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center py-0.5" :class="{
                                    'bg-green-50 rounded px-1': balanceAmount === 0,
                                    'bg-red-50 rounded px-1': balanceAmount > 0,
                                    'bg-yellow-50 rounded px-1': balanceAmount < 0
                                }">
                                    <span class="font-bold text-xs flex items-center" :style="`color: ${balanceColor}`">
                                        <i :class="`fa ${balanceIcon} mr-1 text-xs`"></i>
                                        {{ balanceText }}
                                    </span>
                                    <span class="font-bold text-sm" :style="`color: ${balanceColor}`">
                                        {{ formatNumber(Math.abs(balanceAmount)) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Methods (if applicable) - Ultra Compact -->
                    <div v-if="paymentMethods" class="mb-2">
                        <h6 class="text-xs font-bold text-slate-800 mb-1 flex items-center gap-1">
                            <i class="fa fa-credit-card text-blue-500 text-xs"></i>
                            Payment Methods
                        </h6>
                        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg p-1.5">
                            <div
                                class="bg-white p-1 rounded border-l-2 border-blue-500 font-mono text-xs text-slate-700">
                                {{ paymentMethods }}
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Ultra Compact Footer -->
                <div class="bg-gradient-to-r from-slate-50 to-gray-50 px-3 py-2 border-t border-slate-200">
                    <div class="flex flex-col gap-2">
                        <!-- Action Buttons -->
                        <div class="flex flex-col sm:flex-row justify-end gap-1.5">
                            <button type="button" @click="close"
                                class="inline-flex items-center justify-center px-3 py-1.5 border border-slate-300 shadow-sm text-xs font-semibold rounded-lg text-slate-700 bg-white hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
                                <i class="fa fa-times mr-1 text-xs"></i>
                                Cancel
                            </button>
                            <button type="button" @click="submit" :disabled="loading" :class="[
                                'inline-flex items-center justify-center px-3 py-1.5 border border-transparent text-xs font-semibold rounded-lg text-white shadow-lg focus:outline-none focus:ring-2 focus:ring-offset-2 transition-all duration-200 hover:scale-105',
                                loading ? 'opacity-75 cursor-not-allowed' : '',
                                balanceAmount === 0
                                    ? 'bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 focus:ring-green-500'
                                    : 'bg-gradient-to-r from-yellow-500 to-amber-600 hover:from-yellow-600 hover:to-amber-700 focus:ring-yellow-500'
                            ]">
                                <i class="fa mr-1 text-xs"
                                    :class="loading ? 'fa-spinner fa-spin' : 'fa-check-circle'"></i>
                                {{ loading ? 'Processing...' :
                                    (balanceAmount === 0 ? 'Submit' : 'Submit Anyway') }}
                            </button>
                        </div>

                        <!-- Status Indicator - Ultra Compact and Clickable -->
                        <div class="text-center p-1.5 rounded-lg border-2 cursor-pointer transition-all duration-200 hover:scale-105"
                            :class="{
                                'bg-gradient-to-r from-green-50 to-emerald-50 border-green-200 hover:from-green-100 hover:to-emerald-100': balanceAmount === 0,
                                'bg-gradient-to-r from-yellow-50 to-amber-50 border-yellow-200 hover:from-yellow-100 hover:to-amber-100': balanceAmount > 0,
                                'bg-gradient-to-r from-blue-50 to-indigo-50 border-blue-200 hover:from-blue-100 hover:to-indigo-100': balanceAmount < 0
                            }" @click="balanceAmount === 0 && !loading ? submit() : null">
                            <div class="w-6 h-6 rounded-full flex items-center justify-center mx-auto mb-0.5 shadow-sm"
                                :style="`background: linear-gradient(135deg, ${balanceColor}, ${balanceColor}dd); color: white;`">
                                <i :class="`fa ${balanceIcon} text-xs`"></i>
                            </div>
                            <div class="font-bold text-xs mb-0.5" :style="`color: ${balanceTextColor}`">
                                {{ statusText }}
                            </div>
                            <div class="text-xs opacity-90" :style="`color: ${balanceTextColor}`">
                                {{ statusDescription }}
                            </div>
                            <!-- Click hint for small screens -->
                            <div v-if="balanceAmount === 0 && !loading" class="text-xs mt-0.5 opacity-75"
                                :style="`color: ${balanceTextColor}`">
                                <i class="fa fa-hand-pointer mr-1"></i>Tap to submit
                            </div>
                        </div>
                    </div>
                </div>
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

        const balanceColor = computed(() => {
            if (balanceAmount.value === 0) return '#198754' // Green
            if (balanceAmount.value > 0) return '#dc3545' // Red
            return '#fd7e14' // Orange
        })

        const balanceTextColor = computed(() => {
            if (balanceAmount.value === 0) return '#495057' // Dark gray
            if (balanceAmount.value > 0) return '#856404' // Dark yellow
            return '#0c5460' // Dark blue
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

        const formatDate = (dateString) => {
            if (!dateString) return ''
            const date = new Date(dateString)
            return date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
        }

        const paymentMethods = computed(() => {
            if (props.saleData.payment_method === 'custom' && props.saleData.custom_payment_data?.payments) {
                return props.saleData.custom_payment_data.payments
                    .map(p => `${formatDate(p.date)} ${p.name}: ${formatNumber(parseFloat(p.amount))}`)
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
            balanceColor,
            balanceTextColor,
            balanceText,
            statusText,
            statusDescription,
            paymentMethods,
            localPaymentMethod,
            localSendToWhatsapp,
            localCustomPaymentCount,
            formatNumber,
            formatDate,
            close,
            submit
        }
    }
}
</script>
