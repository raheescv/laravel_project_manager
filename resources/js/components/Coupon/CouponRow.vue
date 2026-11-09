<template>
    <div class="cpx-row">
        <span class="cpx-label">{{ label }}<em v-if="required"> *</em></span>
        <div class="cpx-field">
            <slot></slot>
            <small v-if="error" class="cpx-err">{{ error }}</small>
        </div>
    </div>
</template>

<script setup>
defineProps({
    label: { type: String, default: '' },
    required: { type: Boolean, default: false },
    error: { type: String, default: '' },
})
</script>
