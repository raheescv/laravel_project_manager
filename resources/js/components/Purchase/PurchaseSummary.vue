<template>
    <div class="card shadow-sm border-0">
        <div class="card-header bg-gradient bg-primary text-white py-3">
            <h5 class="card-title mb-0 d-flex align-items-center text-white">
                <i class="demo-psi-receipt-4 fs-4 me-2"></i>Purchase Summary
            </h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <!-- Amounts Section -->
                <div class="col-12">
                    <div class="list-group">
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="fw-medium">Gross Total</div>
                                <div class="input-group input-group-sm" style="width: 150px;">
                                    <input type="number" class="form-control form-control-sm text-end bg-light"
                                        :value="grossAmount" disabled readonly />
                                </div>
                            </div>
                        </div>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="fw-medium">Purchase Total</div>
                                <div class="input-group input-group-sm" style="width: 150px;">
                                    <input type="number" class="form-control form-control-sm text-end bg-light"
                                        :value="total" disabled readonly />
                                </div>
                            </div>
                        </div>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="fw-medium">Other Discount</div>
                                <div class="input-group input-group-sm" style="width: 150px;">
                                    <input type="number" class="form-control form-control-sm text-end"
                                        :value="otherDiscount"
                                        @input="handleInput('other_discount', $event.target.value)" step="any" />
                                </div>
                            </div>
                        </div>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="fw-medium">Freight Charges</div>
                                <div class="input-group input-group-sm" style="width: 150px;">
                                    <input type="number" class="form-control form-control-sm text-end" :value="freight"
                                        @input="handleInput('freight', $event.target.value)" step="any" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Shipping Address -->
                <div class="col-12">
                    <div class="card bg-light border-0">
                        <div class="card-body p-2">
                            <h6 class="card-subtitle mb-2 text-muted">
                                <i class="demo-psi-map-marker-2 me-1"></i>Address
                            </h6>
                            <textarea class="form-control" rows="3" :value="address"
                                @input="handleInput('address', $event.target.value)"
                                placeholder="Enter shipping address..."></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useLivewire } from '@/composables/useLivewire'
import { formatCurrency } from '@/utils/currency'

const props = defineProps({
    grossAmount: {
        type: Number,
        default: 0
    },
    total: {
        type: Number,
        default: 0
    },
    otherDiscount: {
        type: Number,
        default: 0
    },
    freight: {
        type: Number,
        default: 0
    },
    address: {
        type: String,
        default: ''
    }
})

const { set } = useLivewire()

const handleInput = (field, value) => {
    let numValue = parseFloat(value) || 0

    // Handle percentage for other_discount
    if (field === 'other_discount' && typeof value === 'string' && value.endsWith('%')) {
        const percentage = parseFloat(value.replace('%', '')) || 0
        numValue = Math.round((props.total / 100) * percentage * 100) / 100
        if (numValue > props.total) {
            numValue = percentage
        }
    }

    set(`purchases.${field}`, numValue)
}
</script>
