<template>
    <div class="recorded-qty-filter">
        <label class="form-label small">Recorded Qty</label>
        <select :value="value" @change="$emit('update:value', $event.target.value)" class="form-select form-select-sm">
            <option value="">All</option>
            <option value="non_zero">Non-Zero</option>
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
