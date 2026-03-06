<template>
    <div class="bg-white rounded-xl shadow-2xl border border-slate-200 overflow-hidden">
        <div class="px-3 py-3">
            <h6 class="text-xs font-bold text-slate-800 mb-1 flex items-center gap-1">
                <i class="fa fa-th-large text-blue-500 text-xs"></i>
                <span>Item Category</span>
            </h6>
            <div class="bg-gradient-to-r from-slate-50 to-blue-50 border border-slate-200 rounded-lg p-1.5">
                <div class="flex flex-wrap gap-1.5">
                    <button v-for="category in categories" :key="category.id"
                        class="group relative flex items-center gap-1.5 px-3 py-2 rounded-lg border-2 transition-all duration-300 select-none"
                        :class="selectedCategories.includes(category.id)
                            ? 'bg-gradient-to-r from-blue-500 to-indigo-600 border-blue-500 text-white shadow-lg'
                            : 'bg-white border-slate-200 text-slate-700 hover:border-blue-300 hover:bg-blue-50 hover:scale-105'"
                        @click="handleCategoryToggle(category.id)">
                        <span class="font-bold text-xs">{{ category.name }}</span>
                        <i v-if="selectedCategories.includes(category.id)" class="fa fa-check-circle text-white text-xs"></i>
                        <span v-else class="w-2 h-2 rounded-full bg-slate-400 opacity-60"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>


<script setup>
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

