<template>
    <span :class="statusClass" @click="handleClick" style="cursor: pointer;" :title="`Click to change status to ${oppositeStatusLabel}`">
        {{ statusLabel }}
    </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    status: {
        type: String,
        default: 'pending'
    },
    itemId: {
        type: Number,
        required: true
    },
    productName: {
        type: String,
        default: ''
    }
})

const emit = defineEmits(['status-change'])

const statusClass = computed(() => {
    const classes = {
        pending: 'badge bg-warning',
        completed: 'badge bg-success'
    }
    return classes[props.status] || 'badge bg-secondary'
})

const statusLabel = computed(() => {
    const labels = {
        pending: 'Pending',
        completed: 'Completed'
    }
    return labels[props.status] || props.status
})

const oppositeStatus = computed(() => {
    return props.status === 'pending' ? 'completed' : 'pending'
})

const oppositeStatusLabel = computed(() => {
    return oppositeStatus.value === 'pending' ? 'Pending' : 'Completed'
})

const handleClick = (event) => {
    event.stopPropagation()
    emit('status-change', {
        itemId: props.itemId,
        currentStatus: props.status,
        newStatus: oppositeStatus.value,
        productName: props.productName
    })
}
</script>
