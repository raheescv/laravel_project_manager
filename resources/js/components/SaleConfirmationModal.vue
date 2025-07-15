<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog"
        aria-modal="true">
        <!-- Background overlay -->
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="close">
            </div>

            <!-- Modal positioning -->
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <!-- Modal panel -->
            <div
                class="relative inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <!-- Header -->
                <div class="bg-blue-600 px-4 py-3 text-white flex items-center justify-between">
                    <h4 class="text-lg font-semibold text-white mb-0">
                        <i class="fa fa-clipboard-check mr-2"></i> Confirm Sale Transaction
                    </h4>
                    <button type="button" @click="close" class="text-white hover:text-gray-200 focus:outline-none">
                        <i class="fa fa-times"></i>
                    </button>
                </div>

                <!-- Body -->
                <div class="px-4 py-4">
                    <!-- Customer Header -->
                    <div class="bg-gray-100 border border-gray-200 rounded-lg p-4 mb-4 text-center">
                        <div
                            class="w-12 h-12 bg-gray-500 text-white rounded-full flex items-center justify-center mx-auto mb-3">
                            <i class="fa fa-user text-lg"></i>
                        </div>
                        <h4 class="font-semibold text-gray-800">{{ customerName.name }} : {{ customerName.mobile }}</h4>
                    </div>

                    <!-- Payment Method Selection (moved from cart sidebar) -->
                    <div class="mb-4">
                        <div class="flex items-center justify-between mb-3">
                            <h6 class="text-sm sm:text-base font-semibold text-slate-800 mb-0 flex items-center gap-2">
                                <i class="fa fa-credit-card text-blue-500"></i>
                                <span>Payment Method</span>
                            </h6>
                            <label class="flex items-center text-xs sm:text-sm gap-2">
                                <input v-model="localSendToWhatsapp" type="checkbox"
                                    class="rounded border-slate-300 text-green-600 focus:ring-green-500">
                                <i class="fa fa-whatsapp text-green-500"></i>
                                <span class="hidden sm:inline">Send Invoice To Whatsapp</span>
                                <span class="sm:hidden">WhatsApp</span>
                            </label>
                        </div>
                        <div class="grid grid-cols-3 gap-2 sm:gap-3">
                            <!-- Cash Payment -->
                            <div class="payment-option">
                                <button type="button" @click="$emit('update:paymentMethod', 1)" :class="[
                                    'w-full h-20 sm:h-24 flex flex-col items-center justify-center p-2 sm:p-3 border-2 relative transition-all duration-200 rounded-lg',
                                    localPaymentMethod === 1 || localPaymentMethod === ''
                                        ? 'bg-green-500 border-green-500 shadow-lg text-white'
                                        : 'bg-white border-gray-200 text-gray-700 hover:shadow-md hover:border-gray-300'
                                ]">
                                    <div class="icon-wrapper mb-1 sm:mb-2">
                                        <i :class="[
                                            'fa fa-money text-lg sm:text-2xl',
                                            localPaymentMethod === 1 || localPaymentMethod === ''
                                                ? 'text-white'
                                                : 'text-green-500'
                                        ]"></i>
                                    </div>
                                    <span :class="[
                                        'text-xs sm:text-sm font-semibold',
                                        localPaymentMethod === 1 || localPaymentMethod === ''
                                            ? 'text-white'
                                            : 'text-gray-700'
                                    ]">Cash</span>
                                    <div v-if="localPaymentMethod === 1 || localPaymentMethod === ''"
                                        class="absolute top-1 right-1 sm:top-2 sm:right-2">
                                        <i
                                            class="fa fa-check-circle text-white bg-green-600 rounded-full text-xs sm:text-sm"></i>
                                    </div>
                                    <div v-if="localPaymentMethod === 1 || localPaymentMethod === ''"
                                        class="absolute inset-0 bg-green-500 bg-opacity-10 rounded-lg pointer-events-none">
                                    </div>
                                </button>
                            </div>
                            <!-- Card Payment -->
                            <div class="payment-option">
                                <button type="button" @click="$emit('update:paymentMethod', 2)" :class="[
                                    'w-full h-20 sm:h-24 flex flex-col items-center justify-center p-2 sm:p-3 border-2 relative transition-all duration-200 rounded-lg',
                                    localPaymentMethod === 2
                                        ? 'bg-blue-500 border-blue-500 shadow-lg text-white'
                                        : 'bg-white border-gray-200 text-gray-700 hover:shadow-md hover:border-gray-300'
                                ]">
                                    <div class="icon-wrapper mb-1 sm:mb-2">
                                        <i :class="[
                                            'fa fa-credit-card text-lg sm:text-2xl',
                                            localPaymentMethod === 2
                                                ? 'text-white'
                                                : 'text-blue-500'
                                        ]"></i>
                                    </div>
                                    <span :class="[
                                        'text-xs sm:text-sm font-semibold',
                                        localPaymentMethod === 2
                                            ? 'text-white'
                                            : 'text-gray-700'
                                    ]">Card</span>
                                    <div v-if="localPaymentMethod === 2"
                                        class="absolute top-1 right-1 sm:top-2 sm:right-2">
                                        <i
                                            class="fa fa-check-circle text-white bg-blue-600 rounded-full text-xs sm:text-sm"></i>
                                    </div>
                                    <div v-if="localPaymentMethod === 2"
                                        class="absolute inset-0 bg-blue-500 bg-opacity-10 rounded-lg pointer-events-none">
                                    </div>
                                </button>
                            </div>
                            <!-- Custom Payment -->
                            <div class="payment-option">
                                <button type="button" @click="$emit('openCustomPayment')" :class="[
                                    'w-full h-20 sm:h-24 flex flex-col items-center justify-center p-2 sm:p-3 border-2 relative transition-all duration-200 rounded-lg',
                                    localPaymentMethod === 'custom'
                                        ? 'bg-amber-500 border-amber-500 shadow-lg text-white'
                                        : 'bg-white border-gray-200 text-gray-700 hover:shadow-md hover:border-gray-300'
                                ]">
                                    <div class="icon-wrapper mb-1 sm:mb-2">
                                        <i :class="[
                                            'fa fa-cogs text-lg sm:text-2xl',
                                            localPaymentMethod === 'custom'
                                                ? 'text-white'
                                                : 'text-amber-500'
                                        ]"></i>
                                    </div>
                                    <span :class="[
                                        'text-xs sm:text-sm font-semibold',
                                        localPaymentMethod === 'custom'
                                            ? 'text-white'
                                            : 'text-gray-700'
                                    ]">
                                        {{ localCustomPaymentCount > 0 ? `${localCustomPaymentCount} Methods` : 'Custom'
                                        }}
                                    </span>
                                    <div v-if="localPaymentMethod === 'custom'"
                                        class="absolute top-1 right-1 sm:top-2 sm:right-2">
                                        <i
                                            class="fa fa-check-circle text-white bg-amber-600 rounded-full text-xs sm:text-sm"></i>
                                    </div>
                                    <div v-if="localPaymentMethod === 'custom'"
                                        class="absolute inset-0 bg-amber-500 bg-opacity-10 rounded-lg pointer-events-none">
                                    </div>
                                    <div v-if="localCustomPaymentCount > 0 && localPaymentMethod !== 'custom'"
                                        class="absolute -top-1 -right-1 w-3 h-3 bg-green-500 rounded-full border-2 border-white">
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Transaction Summary -->
                    <div class="mb-4">
                        <table
                            class="w-full border-collapse bg-white border border-gray-200 rounded-lg overflow-hidden">
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <td class="px-4 py-3 font-semibold text-gray-700">
                                    <i class="fa fa-dollar-sign mr-2 text-gray-500"></i>
                                    Grand Total
                                </td>
                                <td class="px-4 py-3 text-right font-semibold text-green-600">
                                    ₹{{ grandTotal.toFixed(2) }}
                                </td>
                            </tr>
                            <tr class="border-b border-gray-200">
                                <td class="px-4 py-3 text-gray-700">
                                    <i class="fa fa-credit-card mr-2 text-gray-500"></i>
                                    Paid Amount
                                </td>
                                <td class="px-4 py-3 text-right font-medium text-blue-600">
                                    ₹{{ paidAmount.toFixed(2) }}
                                </td>
                            </tr>
                            <tr
                                :class="{ 'bg-gray-50': balanceAmount === 0, 'bg-red-50': balanceAmount > 0, 'bg-yellow-50': balanceAmount < 0 }">
                                <td class="px-4 py-3 font-semibold text-gray-700">
                                    <i :class="`fa ${balanceIcon}`" :style="`color: ${balanceColor}`"></i>
                                    {{ balanceText }}
                                </td>
                                <td class="px-4 py-3 text-right font-bold" :style="`color: ${balanceColor}`">
                                    ₹{{ Math.abs(balanceAmount).toFixed(2) }}
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Payment Methods (if applicable) -->
                    <div v-if="paymentMethods" class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-4">
                        <div class="flex items-center mb-2 text-gray-700 font-semibold">
                            <i class="fa fa-credit-card mr-2 text-gray-500"></i>
                            Payment Methods Used
                        </div>
                        <div class="bg-white p-3 rounded border-l-4 border-blue-500 font-mono text-sm text-gray-700">
                            {{ paymentMethods }}
                        </div>
                    </div>

                    <!-- Status Indicator -->
                    <div class="text-center p-4" :class="{
                        'bg-gray-50 border border-gray-200': balanceAmount === 0,
                        'bg-yellow-50 border border-yellow-200': balanceAmount > 0,
                        'bg-blue-50 border border-blue-200': balanceAmount < 0
                    }">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center mx-auto mb-2"
                            :style="`background-color: ${balanceColor}; color: white;`">
                            <i :class="`fa ${balanceIcon}`"></i>
                        </div>
                        <div class="font-semibold mb-1" :style="`color: ${balanceTextColor}`">
                            {{ statusText }}
                        </div>
                        <div class="text-sm opacity-80" :style="`color: ${balanceTextColor}`">
                            {{ statusDescription }}
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="bg-gray-50 px-4 py-3 text-right">
                    <div class="flex justify-end space-x-3">
                        <button type="button" @click="close"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fa fa-times mr-2"></i>
                            Cancel
                        </button>
                        <button type="button" @click="submit" :disabled="loading" :class="[
                            'inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2',
                            loading ? 'opacity-75 cursor-not-allowed' : '',
                            balanceAmount === 0
                                ? 'bg-green-600 hover:bg-green-700 focus:ring-green-500'
                                : 'bg-yellow-500 hover:bg-yellow-600 focus:ring-yellow-500'
                        ]">
                            <i class="fa mr-2" :class="loading ? 'fa-spinner fa-spin' : 'fa-check-circle'"></i>
                            {{ loading ? 'Processing...' :
                                (balanceAmount === 0 ? 'Submit Transaction' : 'Submit Anyway') }}
                        </button>
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
            // For cash/card payment, the paid amount equals the grand total
            // For custom payment, use the total paid from custom payment data
            if (props.saleData.payment_method === 'custom' && props.saleData.custom_payment_data) {
                return parseFloat(props.saleData.custom_payment_data.totalPaid) || 0
            }
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

        const paymentMethods = computed(() => {
            if (props.saleData.payment_method === 'custom' && props.saleData.custom_payment_data?.payments) {
                return props.saleData.custom_payment_data.payments
                    .map(p => `${p.name}: ₹${parseFloat(p.amount).toFixed(2)}`)
                    .join(', ')
            }
            if (props.saleData.payment_method === 1) return 'Cash Payment'
            if (props.saleData.payment_method === 2) return 'Card Payment'
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
            close,
            submit
        }
    }
}
</script>
