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
                                    <i :class="`fa ${balanceIcon} mr-2`" :style="`color: ${balanceColor}`"></i>
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
            type: Boolean,
            default: false
        },
        saleData: {
            type: Object,
            required: true
        },
        loading: {
            type: Boolean,
            default: false
        }
    },
    emits: ['close', 'submit'],
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
            close,
            submit
        }
    }
}
</script>
