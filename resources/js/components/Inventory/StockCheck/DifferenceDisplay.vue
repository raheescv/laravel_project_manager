<template>
    <span :class="differenceClass">
        {{ formattedDifference }}
    </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    physicalQty: {
        type: Number,
        default: 0
    },
    recordedQty: {
        type: Number,
        default: 0
    }
})

const difference = computed(() => {
    return (props.physicalQty || 0) - (props.recordedQty || 0)
})

const differenceClass = computed(() => {
    if (difference.value < 0) return 'text-danger fw-bold'
    if (difference.value > 0) return 'text-success fw-bold'
    return 'text-muted'
})

const formattedDifference = computed(() => {
    const diff = difference.value
    const sign = diff > 0 ? '+' : ''
    return `${sign}${diff.toFixed(2)}`
})
</script>
