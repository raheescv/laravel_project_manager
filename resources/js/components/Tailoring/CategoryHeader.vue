<template>
    <div class="bg-white rounded-lg shadow-sm p-4">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-4">
                <span class="text-sm font-semibold text-gray-700">Item Types:</span>
                <label 
                    v-for="category in categories" 
                    :key="category.id"
                    class="flex items-center gap-2 cursor-pointer"
                >
                    <input 
                        type="checkbox"
                        :value="category.id"
                        :checked="selectedCategories.includes(category.id)"
                        @change="handleCategoryToggle(category.id)"
                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                    />
                    <span class="text-sm text-gray-700">{{ category.name }}</span>
                </label>
            </div>
        </div>
    </div>
</template>

<script setup>
import { watch } from 'vue'

const props = defineProps({
    categories: {
        type: Array,
        default: () => []
    },
    selectedCategories: {
        type: Array,
        default: () => []
    }
})

const emit = defineEmits(['update:selectedCategories', 'category-selected'])

const handleCategoryToggle = (categoryId) => {
    const updated = props.selectedCategories.includes(categoryId)
        ? props.selectedCategories.filter(id => id !== categoryId)
        : [...props.selectedCategories, categoryId]
    
    emit('update:selectedCategories', updated)
    emit('category-selected', updated)
}
</script>
