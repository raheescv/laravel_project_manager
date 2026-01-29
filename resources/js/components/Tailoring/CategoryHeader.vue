<template>
    <div class="bg-white rounded-3xl shadow-lg shadow-slate-200/50 border border-slate-200 overflow-hidden relative">
        <div class="absolute inset-0 bg-gradient-to-r from-violet-50/10 to-transparent pointer-events-none"></div>
        <div class="p-3 md:p-4 relative z-10">
            <div class="flex flex-col md:flex-row md:items-center gap-4">
                <div class="flex items-center gap-3 md:pr-6 md:border-r border-slate-100/80">
                    <div class="w-8 h-8 rounded-lg bg-violet-50 flex items-center justify-center text-violet-600 shadow-sm border border-violet-100">
                        <i class="fa fa-th-large text-xs"></i>
                    </div>
                    <div>
                        <span class="text-slate-800 font-black text-xs leading-none block mb-0.5">Item Types</span>
                        <span class="text-slate-400 text-[0.6rem] uppercase tracking-widest font-black">Select Types</span>
                    </div>
                </div>
                
                <div class="flex-1 flex flex-wrap gap-2.5">
                    <button v-for="category in categories" :key="category.id"
                        class="group relative flex items-center gap-2 px-4 py-2 rounded-xl border-2 transition-all duration-500 select-none overflow-hidden"
                        :class="selectedCategories.includes(category.id) 
                            ? 'bg-indigo-600 border-indigo-600 text-white shadow-lg shadow-indigo-200 ring-4 ring-indigo-600/10' 
                            : 'bg-slate-50 border-slate-100 text-slate-500 hover:border-indigo-400 hover:bg-white hover:text-indigo-600 hover:shadow-xl hover:-translate-y-1'"
                        @click="handleCategoryToggle(category.id)">
                        
                        <div class="relative z-10 flex items-center gap-2">
                            <span class="font-bold text-[0.75rem]">{{ category.name }}</span>
                            <div class="transition-all duration-300" 
                                :class="selectedCategories.includes(category.id) ? 'scale-110' : 'opacity-40 group-hover:opacity-100 group-hover:rotate-12'">
                                <i class="fa text-[10px]" :class="selectedCategories.includes(category.id) ? 'fa-check-circle' : 'fa-circle-thin'"></i>
                            </div>
                        </div>
                        
                        <!-- Shimmer effect for active button -->
                        <div v-if="selectedCategories.includes(category.id)" 
                            class="absolute inset-0 bg-gradient-to-r from-transparent via-white/10 to-transparent -translate-x-full animate-[shimmer_2s_infinite]"></div>
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

