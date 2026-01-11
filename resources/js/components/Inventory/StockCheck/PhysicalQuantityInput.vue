<template>
    <input type="number" :value="value" @input="handleInput" @change="handleChange" class="form-control text-end form-control-sm"
        min="0" step="1" style="width: 100%; text-align: right;" />
</template>

<script setup>
const props = defineProps({
    value: {
        type: Number,
        default: 0
    },
    itemId: {
        type: Number,
        required: true
    }
})

const emit = defineEmits(['update:value', 'change'])

const handleInput = (event) => {
    const newValue = parseFloat(event.target.value) || 0
    emit('update:value', newValue)
}

const handleChange = (event) => {
    const newValue = parseFloat(event.target.value) || 0
    emit('change', props.itemId, newValue)
}
</script>
