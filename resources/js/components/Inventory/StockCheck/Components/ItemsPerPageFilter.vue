<template>
    <div class="items-per-page-filter">
        <label class="form-label small">Items Per Page</label>
        <select :value="value" @change="$emit('update:value', parseInt($event.target.value))"
            class="form-select form-select-sm">
            <option :value="10">10</option>
            <option :value="20">20</option>
            <option :value="30">30</option>
            <option :value="50">50</option>
            <option :value="100">100</option>
        </select>
    </div>
</template>

<script setup>
defineProps({
    value: {
        type: Number,
        default: 20
    }
})

defineEmits(['update:value'])
</script>
