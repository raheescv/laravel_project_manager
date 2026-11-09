<template>
    <label class="cpx-sw">
        <input type="checkbox" :checked="modelValue" :disabled="disabled" :aria-label="label" @change="$emit('update:modelValue', $event.target.checked)" />
        <i></i>
    </label>
</template>

<script setup>
defineProps({
    modelValue: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    label: { type: String, default: '' },
})
defineEmits(['update:modelValue'])
</script>
