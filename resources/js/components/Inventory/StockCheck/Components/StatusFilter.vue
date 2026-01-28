<template>
    <div class="status-filter">
        <label class="form-label small">Status</label>
        <select :value="value" @change="$emit('update:value', $event.target.value)" class="form-select form-select-sm">
            <option v-for="status in statuses" :key="status.value" :value="status.value">
                {{ status.label }}
            </option>
        </select>
    </div>
</template>

<script setup>
defineProps({
    value: {
        type: String,
        default: ''
    },
    statuses: {
        type: Array,
        default: () => [
            { value: '', label: 'All' },
            { value: 'pending', label: 'Pending' },
            { value: 'completed', label: 'Completed' }
        ]
    }
})

defineEmits(['update:value'])
</script>
