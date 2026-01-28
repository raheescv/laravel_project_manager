<template>
    <div v-if="show" class="modal show d-block" tabindex="-1" role="dialog">
        <!-- Background overlay -->
        <div class="modal-backdrop show" @click="$emit('close')"></div>

        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content shadow-lg border-0">
                <!-- Header -->
                <div class="modal-header bg-primary text-white py-2">
                    <h5 class="modal-title h6 fw-bold">Payment Management</h5>
                    <button type="button" class="btn-close btn-close-white" @click="$emit('close')" aria-label="Close"></button>
                </div>

                <!-- Body -->
                <div class="modal-body p-4">
                    <!-- Order Summary -->
                    <div class="alert alert-light border shadow-sm p-3 mb-4">
                        <div class="row g-3 text-center">
                            <div class="col-md-4">
                                <div class="small fw-bold text-uppercase text-muted mb-1">Grand Total</div>
                                <div class="h5 fw-bold text-dark mb-0">{{ formatCurrency(order.grand_total || 0) }}</div>
                            </div>
                            <div class="col-md-4">
                                <div class="small fw-bold text-uppercase text-muted mb-1">Total Paid</div>
                                <div class="h5 fw-bold text-success mb-0">{{ formatCurrency(order.paid || 0) }}</div>
                            </div>
                            <div class="col-md-4">
                                <div class="small fw-bold text-uppercase text-muted mb-1">Balance Due</div>
                                <div class="h5 fw-bold text-danger mb-0">{{ formatCurrency(order.balance || 0) }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Add Payment Form -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body p-3">
                            <h6 class="fw-bold mb-3 d-flex align-items-center gap-2">
                                <i class="fa fa-plus-circle text-success"></i>
                                Add Payment
                            </h6>
                            <div class="row g-3 align-items-end">
                                <div class="col-md-4">
                                    <label class="form-label small text-muted fw-bold">Payment Method</label>
                                    <select v-model="paymentForm.payment_method_id" class="form-select">
                                        <option value="">Select Method</option>
                                        <option v-for="method in paymentMethods" :key="method.id" :value="method.id">
                                            {{ method.name }}
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small text-muted fw-bold">Amount</label>
                                    <input v-model.number="paymentForm.amount" type="number" step="0.01" min="0.01"
                                        :max="order.balance || order.grand_total" placeholder="0.00" class="form-control" />
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small text-muted fw-bold">Date</label>
                                    <div class="input-group">
                                        <input v-model="paymentForm.date" type="date" class="form-control" />
                                        <button type="button" @click="handleAddPayment" class="btn btn-primary fw-bold">
                                            Add
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payments List -->
                    <div class="mb-0">
                        <h6 class="fw-bold mb-2 d-flex align-items-center gap-2">
                             <i class="fa fa-history text-primary"></i>
                             Payment History
                        </h6>
                        <div class="table-responsive rounded shadow-sm border">
                            <table class="table table-sm table-striped table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3 py-2 small fw-bold">Date</th>
                                        <th class="py-2 small fw-bold">Method</th>
                                        <th class="py-2 small fw-bold text-end">Amount</th>
                                        <th class="py-2 small fw-bold text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="payment in payments" :key="payment.id" class="align-middle">
                                        <td class="ps-3 py-2 small">{{ payment.date }}</td>
                                        <td class="py-2 small">{{ payment.payment_method?.name || payment.name }}</td>
                                        <td class="py-2 small text-end fw-bold">{{ formatCurrency(payment.amount) }}</td>
                                        <td class="py-2 text-center">
                                            <button @click="handleDeletePayment(payment.id)"
                                                class="btn btn-link text-danger p-0" title="Delete">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="payments.length === 0">
                                        <td colspan="4" class="text-center py-4 text-muted small italic">
                                            No payments added yet
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="modal-footer bg-light border-top">
                    <button type="button" @click="$emit('close')" class="btn btn-outline-secondary px-4 fw-bold">
                        <i class="fa fa-times me-2"></i>Close
                    </button>
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
