<template>
    <div v-if="show" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-bold text-gray-800">Payment Management</h2>
                    <button 
                        @click="$emit('close')"
                        class="text-gray-400 hover:text-gray-600"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="p-6 space-y-6">
                <!-- Order Summary -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="text-sm text-gray-600">Grand Total:</span>
                            <span class="text-lg font-bold text-gray-800 ml-2">{{ formatCurrency(order.grand_total || 0) }}</span>
                        </div>
                        <div>
                            <span class="text-sm text-gray-600">Paid:</span>
                            <span class="text-lg font-bold text-green-600 ml-2">{{ formatCurrency(order.paid || 0) }}</span>
                        </div>
                        <div class="col-span-2">
                            <span class="text-sm text-gray-600">Balance:</span>
                            <span class="text-lg font-bold text-red-600 ml-2">{{ formatCurrency(order.balance || 0) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Add Payment Form -->
                <div class="border border-gray-200 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Add Payment</h3>
                    <div class="grid grid-cols-3 gap-4">
                        <div class="form-group">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
                            <select 
                                v-model="paymentForm.payment_method_id"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="">Select Method</option>
                                <option v-for="method in paymentMethods" :key="method.id" :value="method.id">
                                    {{ method.name }}
                                </option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Amount</label>
                            <input 
                                v-model.number="paymentForm.amount"
                                type="number"
                                step="0.01"
                                min="0.01"
                                :max="order.balance || order.grand_total"
                                placeholder="Enter amount..."
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>
                        <div class="form-group">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                            <input 
                                v-model="paymentForm.date"
                                type="date"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>
                    </div>
                    <button 
                        type="button"
                        @click="handleAddPayment"
                        class="mt-4 px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-semibold"
                    >
                        Add Payment
                    </button>
                </div>

                <!-- Payments List -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Payment History</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Date</th>
                                    <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Method</th>
                                    <th class="px-4 py-2 text-right text-sm font-semibold text-gray-700">Amount</th>
                                    <th class="px-4 py-2 text-center text-sm font-semibold text-gray-700">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr 
                                    v-for="payment in payments" 
                                    :key="payment.id"
                                    class="border-b border-gray-200"
                                >
                                    <td class="px-4 py-2 text-sm">{{ payment.date }}</td>
                                    <td class="px-4 py-2 text-sm">{{ payment.payment_method?.name || payment.name }}</td>
                                    <td class="px-4 py-2 text-sm text-right font-medium">{{ formatCurrency(payment.amount) }}</td>
                                    <td class="px-4 py-2 text-center">
                                        <button 
                                            @click="handleDeletePayment(payment.id)"
                                            class="text-red-600 hover:text-red-800 text-sm"
                                        >
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="payments.length === 0">
                                    <td colspan="4" class="px-4 py-8 text-center text-gray-500">
                                        No payments added yet
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useToast } from 'vue-toastification'

const props = defineProps({
    show: Boolean,
    order: Object,
    payments: Array,
    paymentMethods: Array,
})

const emit = defineEmits(['close', 'add-payment', 'update-payment', 'delete-payment'])

const toast = useToast()
const paymentForm = ref({
    payment_method_id: '',
    amount: 0,
    date: new Date().toISOString().split('T')[0],
})

const handleAddPayment = () => {
    if (!paymentForm.value.payment_method_id || !paymentForm.value.amount) {
        toast.error('Please fill all required fields')
        return
    }

    if (paymentForm.value.amount > (props.order.balance || props.order.grand_total)) {
        toast.error('Amount cannot exceed balance')
        return
    }

    emit('add-payment', { ...paymentForm.value })
    paymentForm.value = {
        payment_method_id: '',
        amount: 0,
        date: new Date().toISOString().split('T')[0],
    }
}

const handleDeletePayment = (paymentId) => {
    if (confirm('Are you sure you want to delete this payment?')) {
        emit('delete-payment', paymentId)
    }
}

const formatCurrency = (value) => {
    return parseFloat(value || 0).toFixed(2)
}
</script>
