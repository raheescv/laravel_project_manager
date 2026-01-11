<template>
    <div class="brand-filter">
        <label class="form-label small">Brand</label>
        <select :value="value" @change="$emit('update:value', $event.target.value)" class="form-select form-select-sm">
            <option value="">All Brands</option>
            <option v-for="brand in brands" :key="brand.id" :value="brand.id">
                {{ brand.name }}
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
    brands: {
        type: Array,
        default: () => []
    }
})

defineEmits(['update:value'])
</script>
