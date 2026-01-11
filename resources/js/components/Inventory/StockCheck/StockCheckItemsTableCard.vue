<template>
    <div class="col-12">
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="flex-grow-1">
                        <h6 class="card-title mb-1 fw-bold">{{ item.product_name || 'N/A' }}</h6>
                        <small class="text-muted d-block">{{ item.product_code || '' }}</small>
                    </div>
                    <StatusBadge :status="item.status" :item-id="item.id" :product-name="item.product_name"
                        @status-change="handleStatusChange" />
                </div>

                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <small class="text-muted d-block">Brand</small>
                        <span class="fw-medium">{{ item.brand_name || 'N/A' }}</span>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block">Category</small>
                        <span class="fw-medium">{{ item.category_name || 'N/A' }}</span>
                    </div>
                </div>

                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <small class="text-muted d-block">Barcode</small>
                        <span class="fw-medium">{{ item.barcode || 'N/A' }}</span>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block">Recorded Qty</small>
                        <span class="fw-medium">{{ formatNumber(item.recorded_quantity) }}</span>
                    </div>
                </div>

                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <small class="text-muted d-block mb-1">Physical Qty</small>
                        <PhysicalQuantityInput :value="item.physical_quantity" :item-id="item.id"
                            @change="handleQuantityUpdate" />
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block mb-1">Difference</small>
                        <DifferenceDisplay :physical-qty="item.physical_quantity" :recorded-qty="item.recorded_quantity" />
                    </div>
                </div>

                <div class="mt-2 pt-2 border-top">
                    <small class="text-muted">
                        <i class="fa fa-clock me-1"></i>
                        Updated: {{ formatDate(item.updated_at) }}
                    </small>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import PhysicalQuantityInput from './PhysicalQuantityInput.vue'
import DifferenceDisplay from './DifferenceDisplay.vue'
import StatusBadge from './StatusBadge.vue'

defineProps({
    item: {
        type: Object,
        required: true
    },
    index: {
        type: Number,
        default: 0
    }
})

const emit = defineEmits(['update-quantity', 'status-change-request'])

const handleStatusChange = (data) => {
    emit('status-change-request', data)
}

const handleQuantityUpdate = (itemId, quantity) => {
    emit('update-quantity', itemId, quantity)
}

const formatDate = (date) => {
    if (!date) return '_'
    return new Date(date).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        hour12: true
    })
}

const formatNumber = (num) => {
    if (num === null || num === undefined) return '0.00'
    return parseFloat(num).toFixed(2)
}
</script>

<style scoped>
.card {
    border: 1px solid #dee2e6;
}

.card-title {
    font-size: 0.95rem;
    margin-bottom: 0.5rem;
}
</style>
