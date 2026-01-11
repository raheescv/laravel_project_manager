<template>
    <tr>
        <td class="d-none d-xl-table-cell">{{ item.id }}</td>
        <td class="d-none d-lg-table-cell text-center">{{ formatDate(item.updated_at) }}</td>
        <td class="d-none d-xl-table-cell">{{ item.brand_name || 'N/A' }}</td>
        <td class="d-none d-xl-table-cell">{{ item.category_name || 'N/A' }}</td>
        <td>
            <div>{{ item.product_name || 'N/A' }}</div>
            <small class="text-muted d-none d-md-inline">{{ item.product_code || '' }}</small>
        </td>
        <td class="text-end d-none d-lg-table-cell">{{ item.barcode || 'N/A' }}</td>
        <td class="text-end">
            <PhysicalQuantityInput :value="item.physical_quantity" :item-id="item.id"
                @change="handleQuantityUpdate" />
        </td>
        <td class="text-end d-none d-md-table-cell">{{ formatNumber(item.recorded_quantity) }}</td>
        <td class="text-end">
            <DifferenceDisplay :physical-qty="item.physical_quantity" :recorded-qty="item.recorded_quantity" />
        </td>
        <td>
            <StatusBadge :status="item.status" :item-id="item.id" :product-name="item.product_name"
                @status-change="handleStatusChange" />
        </td>
    </tr>
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
