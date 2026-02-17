<template>
    <div class="flex flex-col gap-3">
        <!-- Header - SaleConfirmationModal style (gradient info box) -->
        <div
            class="bg-gradient-to-r from-slate-50 to-blue-50 border border-slate-200 rounded-lg p-1.5 text-center relative overflow-hidden">
            <div class="relative z-10 flex items-center justify-center gap-2">
                <div
                    class="w-8 h-8 bg-gradient-to-r from-blue-500 to-indigo-600 text-white rounded-full flex items-center justify-center shadow-sm flex-shrink-0">
                    <span class="text-xs font-bold">#{{ item.item_no }}</span>
                </div>
                <div class="text-left">
                    <h5 class="font-bold text-slate-800 text-sm leading-none">{{ item.product_name || 'Generic Item' }}</h5>
                    <p class="text-slate-600 text-xs mt-0.5 flex items-center gap-1.5 flex-wrap">
                        <span>{{ item.category?.name || 'Item' }}</span>
                        <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                        <span class="text-blue-600">{{ modelName }}</span>
                        <template v-if="modelTypeName">
                            <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                            <span class="text-indigo-600">{{ modelTypeName }}</span>
                        </template>
                    </p>
                </div>
            </div>
        </div>

        <!-- Grid Layout -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
            <!-- Dimensions Column -->
            <div class="flex flex-col gap-1.5">
                <h6 class="text-xs font-bold text-slate-800 flex items-center gap-1">
                    <i class="fa fa-arrows-alt text-blue-500 text-xs"></i>
                    <span>Dimensions</span>
                </h6>
                <div class="bg-gradient-to-r from-slate-50 to-blue-50 border border-slate-200 rounded-lg p-1.5">
                    <div v-for="m in getFieldsBySection(sectionGroups.dimensions)" :key="m.id"
                        class="flex items-stretch border-b border-slate-200 last:border-b-0 hover:bg-white/50 transition-colors rounded">
                        <div class="w-1/2 bg-white/50 px-2 py-1.5 text-xs font-semibold text-slate-600 border-r border-slate-200 flex items-center capitalize">
                            {{ m.label }}
                        </div>
                        <div class="w-1/2 px-2 py-1.5 text-xs font-bold text-slate-800 flex items-center" :class="{ 'opacity-30': !getValue(m.field_key) }">
                            {{ getValue(m.field_key) ?? '-' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Components Column -->
            <div class="flex flex-col gap-1.5">
                <h6 class="text-xs font-bold text-slate-800 flex items-center gap-1">
                    <i class="fa fa-puzzle-piece text-blue-500 text-xs"></i>
                    <span>Components</span>
                </h6>
                <div class="bg-gradient-to-r from-slate-50 to-blue-50 border border-slate-200 rounded-lg p-1.5">
                    <div v-for="m in getFieldsBySection(sectionGroups.components)" :key="m.id"
                        class="flex items-stretch border-b border-slate-200 last:border-b-0 hover:bg-white/50 transition-colors rounded">
                        <div class="w-1/2 bg-white/50 px-2 py-1.5 text-xs font-semibold text-slate-600 border-r border-slate-200 flex items-center capitalize">
                            {{ m.label }}
                        </div>
                        <div class="w-1/2 px-2 py-1.5 text-xs font-bold text-slate-800 flex items-center" :class="{ 'opacity-30': !getValue(m.field_key) }">
                            {{ getValue(m.field_key) ?? '-' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Styles Column -->
            <div class="flex flex-col gap-1.5 lg:col-span-1">
                <h6 class="text-xs font-bold text-slate-800 flex items-center gap-1">
                    <i class="fa fa-cut text-blue-500 text-xs"></i>
                    <span>Styles & Models</span>
                </h6>
                <div class="bg-gradient-to-r from-slate-50 to-blue-50 border border-slate-200 rounded-lg p-1.5">
                    <div v-for="m in getFieldsBySection(sectionGroups.styles)" :key="m.id"
                        class="flex items-stretch border-b border-slate-200 last:border-b-0 hover:bg-white/50 transition-colors rounded">
                        <div class="w-1/2 bg-white/50 px-2 py-1.5 text-xs font-semibold text-slate-600 border-r border-slate-200 flex items-center capitalize">
                            {{ m.label }}
                        </div>
                        <div class="w-1/2 px-2 py-1.5 text-xs font-bold text-slate-800 flex items-center" :class="{ 'opacity-30': !getValue(m.field_key) }">
                            {{ getValue(m.field_key) ?? '-' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notes Section - compact style -->
        <div v-if="item.tailoring_notes" class="mt-1">
            <h6 class="text-xs font-bold text-slate-800 mb-1 flex items-center gap-1">
                <i class="fa fa-info-circle text-amber-500 text-xs"></i>
                Special Instructions
            </h6>
            <div class="bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200 rounded-lg p-1.5">
                <p class="text-xs font-semibold text-amber-900 leading-relaxed">
                    {{ item.tailoring_notes }}
                </p>
            </div>
        </div>
    </div>
</template>


<script setup>
import { computed } from 'vue'

const props = defineProps({
    item: {
        type: Object,
        required: true
    }
})

const modelName = computed(() => {
    return props.item.categoryModel?.name ||
           props.item.category_model?.name ||
           props.item.tailoring_category_model_name ||
           'Standard'
})

const modelTypeName = computed(() => {
    return props.item.categoryModelType?.name ||
           props.item.category_model_type?.name ||
           props.item.tailoring_category_model_type_name ||
           null
})

const getFieldsBySection = (sectionId) => {
    if (!props.item?.category?.active_measurements) return []
    return props.item.category.active_measurements
        .filter(m => m.section === sectionId)
        .sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0))
}

const getValue = (key) => {
    const val = props.item[key];
    if (val === null || val === undefined || val === '') return null;
    return val;
}

// Map sections to the UI columns
const sectionGroups = {
    dimensions: 'basic_body',
    components: 'collar_cuff',
    styles: 'specifications'
}

const formatLabel = (key) => {
    const config = props.item?.category?.active_measurements?.find(m => m.field_key === String(key))
    if (config) return config.label

    // Fallback for special keys or undefined configs
    if (key === 'tailoring_notes') return 'Special Instructions'

    return String(key)
        .replace(/_/g, ' ')
        .replace(/\b\w/g, l => l.toUpperCase())
}
</script>

