<template>
    <button class="btn btn-success btn-lg" @click="$emit('save')" :disabled="disabled || loading">
        <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
        <i v-else class="fa fa-save me-1"></i>
        {{ loading ? 'Saving...' : 'Save Updates' }}
    </button>
</template>

<script setup>
defineProps({
    loading: {
        type: Boolean,
        default: false
    },
    disabled: {
        type: Boolean,
        default: false
    }
})

defineEmits(['save'])
</script>
