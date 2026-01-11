<template>
    <div class="category-filter">
        <label class="form-label small">Category</label>
        <select :value="value" @change="$emit('update:value', $event.target.value)" class="form-select form-select-sm">
            <option value="">All Categories</option>
            <option v-for="category in categories" :key="category.id" :value="category.id">
                {{ category.name }}
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
    categories: {
        type: Array,
        default: () => []
    }
})

defineEmits(['update:value'])
</script>
