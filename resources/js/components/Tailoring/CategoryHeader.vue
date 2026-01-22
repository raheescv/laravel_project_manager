<template>
    <div class="mb-4">
        <div class="card shadow-sm border-0 rounded-[10px] bg-white border border-black/5 !important">
            <div class="card-body p-3">
                <div class="flex items-center flex-wrap gap-3">
                    <div class="pr-3 border-r-2 border-slate-100 h-6 flex items-center max-sm:border-r-0 max-sm:w-full max-sm:mb-[5px]">
                        <i class="fa fa-th-large me-2 text-primary"></i>
                        <span class="fw-bold text-slate-800">Item Types</span>
                    </div>
                    
                    <div class="flex-1 flex flex-wrap gap-2">
                        <div v-for="category in categories" :key="category.id"
                            class="px-3 py-1 rounded-[8px] bg-slate-50 border-[1.5px] border-slate-200 cursor-pointer transition-all duration-200 ease-[cubic-bezier(0.4,0,0.2,1)] select-none hover:border-blue-500 hover:bg-blue-50 hover:-translate-y-[1px]"
                            :class="{ 'bg-blue-500 border-blue-500 text-white shadow-[0_2px_8px_rgba(59,130,246,0.2)]': selectedCategories.includes(category.id) }"
                            @click="handleCategoryToggle(category.id)">
                            <div class="flex items-center gap-2">
                                <span class="font-semibold text-[0.85rem]">{{ category.name }}</span>
                                <div class="text-[0.9rem] opacity-70" :class="{ 'opacity-100': selectedCategories.includes(category.id) }">
                                    <i class="fa" :class="selectedCategories.includes(category.id) ? 'fa-check-circle' : 'fa-circle-thin'"></i>
                                </div>
                            </div>
                        </div>
                    </div>
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

