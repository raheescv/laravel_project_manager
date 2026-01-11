<template>
    <div class="difference-filter">
        <label class="form-label small">Difference</label>
        <select :value="value" @change="$emit('update:value', $event.target.value)" class="form-select form-select-sm">
            <option value="">All</option>
            <option value="positive">Positive</option>
            <option value="negative">Negative</option>
            <option value="zero">Zero</option>
        </select>
    </div>
</template>

<script setup>
defineProps({
    value: {
        type: String,
        default: ''
    }
})

defineEmits(['update:value'])
</script>
