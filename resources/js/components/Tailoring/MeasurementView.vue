<template>
    <div class="flex flex-col gap-6">
        <!-- Premium Header Area -->
        <div class="flex items-center justify-between px-1">
            <div class="flex items-center gap-4">
                <div class="flex flex-col items-center bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-200">
                    <span class="text-[0.6rem] font-black text-slate-400 uppercase tracking-[0.1em]">ITEM</span>
                    <span class="text-base font-black text-slate-900 leading-none">#{{ item.item_no }}</span>
                </div>
                <div>
                    <h5 class="text-base font-black text-slate-900 leading-none mb-1">{{ item.product_name || 'Generic Item' }}</h5>
                    <div class="text-[0.65rem] text-slate-500 font-bold uppercase tracking-widest flex items-center gap-2">
                        <span>{{ item.category?.name || 'Item' }}</span>
                        <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                        <span class="text-blue-600">{{ modelName }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Balanced Grid Layout -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Dimensions Column -->
            <div class="flex flex-col gap-2">
                <div class="flex items-center gap-2 px-1">
                    <i class="fa fa-arrows-alt text-slate-400 text-[10px]"></i>
                    <h6 class="text-[0.6rem] font-black text-slate-400 uppercase tracking-[0.15em]">Dimensions</h6>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden divide-y divide-slate-100">
                    <div v-for="key in groups.dimensions" :key="key" class="flex items-stretch hover:bg-slate-50/50 transition-colors">
                        <div class="w-1/2 bg-slate-50/50 px-3 py-2 text-[0.7rem] font-bold text-slate-500 border-r border-slate-100 flex items-center capitalize">
                            {{ formatLabel(key) }}
                        </div>
                        <div class="w-1/2 px-3 py-2 text-[0.7rem] font-black text-slate-800 flex items-center" :class="{ 'opacity-30': !getValue(key) }">
                            {{ getValue(key) ?? '-' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Components Column -->
            <div class="flex flex-col gap-2">
                <div class="flex items-center gap-2 px-1">
                    <i class="fa fa-puzzle-piece text-slate-400 text-[10px]"></i>
                    <h6 class="text-[0.6rem] font-black text-slate-400 uppercase tracking-[0.15em]">Components</h6>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden divide-y divide-slate-100">
                    <div v-for="key in groups.components" :key="key" class="flex items-stretch hover:bg-slate-50/50 transition-colors">
                        <div class="w-1/2 bg-slate-50/50 px-3 py-2 text-[0.7rem] font-bold text-slate-500 border-r border-slate-100 flex items-center capitalize">
                            {{ formatLabel(key) }}
                        </div>
                        <div class="w-1/2 px-3 py-2 text-[0.7rem] font-black text-slate-800 flex items-center" :class="{ 'opacity-30': !getValue(key) }">
                            {{ getValue(key) ?? '-' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Styles Column -->
            <div class="flex flex-col gap-2 lg:col-span-1">
                <div class="flex items-center gap-2 px-1">
                    <i class="fa fa-cut text-slate-400 text-[10px]"></i>
                    <h6 class="text-[0.6rem] font-black text-slate-400 uppercase tracking-[0.15em]">Styles & Models</h6>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="grid grid-cols-1 divide-y divide-slate-100">
                        <div v-for="key in groups.styles" :key="key" class="flex items-stretch hover:bg-slate-50/50 transition-colors">
                            <div class="w-1/2 bg-slate-50/50 px-3 py-2 text-[0.7rem] font-bold text-slate-500 border-r border-slate-100 flex items-center capitalize">
                                {{ formatLabel(key) }}
                            </div>
                            <div class="w-1/2 px-3 py-2 text-[0.7rem] font-black text-slate-800 flex items-center" :class="{ 'opacity-30': !getValue(key) }">
                                {{ getValue(key) ?? '-' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notes Section -->
        <div v-if="item.tailoring_notes" class="mt-2">
            <div class="bg-amber-50 rounded-2xl border border-amber-100 p-4">
                <div class="flex items-center gap-2 mb-2">
                    <i class="fa fa-info-circle text-amber-600 text-xs"></i>
                    <h6 class="text-[0.6rem] font-black text-amber-700 uppercase tracking-widest leading-none">Special Instructions</h6>
                </div>
                <p class="text-[0.75rem] font-bold text-amber-900 leading-relaxed">
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

const getValue = (key) => {
    const val = props.item[key];
    if (val === null || val === undefined || val === '') return null;
    return val;
}

const groups = {
    dimensions: ['length', 'shoulder', 'sleeve', 'chest', 'stomach', 'sl_chest', 'sl_so', 'neck', 'bottom'],
    components: ['mar_size', 'cuff_size', 'collar_size', 'regal_size', 'knee_loose', 'fp_size', 'side_pt_size', 'button_no', 'neck_d_button'],
    styles: ['mar_model', 'cuff', 'cuff_cloth', 'cuff_model', 'collar', 'collar_cloth', 'collar_model', 'fp_down', 'fp_model', 'pen', 'side_pt_model', 'stitching', 'button', 'mobile_pocket']
}

const formatLabel = (key) => {
    const labels = {
        'sl_chest': 'Sleeve Chest',
        'sl_so': 'Sleeve Shoulder',
        'mar_size': 'Mar Size',
        'mar_model': 'Mar Model',
        'cuff_cloth': 'Cuff Cloth',
        'cuff_model': 'Cuff Model',
        'collar_cloth': 'Collar Cloth',
        'collar_model': 'Collar Model',
        'fp_down': 'FP Down',
        'fp_model': 'FP Model',
        'fp_size': 'FP Size',
        'side_pt_size': 'Side Pkt Size',
        'side_pt_model': 'Side Pkt Model',
        'neck_d_button': 'Neck D Button',
        'button_no': 'Btn No',
        'mobile_pocket': 'Mob Pkt'
    }
    return labels[key] || key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}
</script>

<style scoped>
.measurement-view {
    color: #1e293b;
}

/* Header Badge */
.badge-accent {
    display: flex;
    flex-direction: column;
    align-items: center;
    background: #f1f5f9;
    padding: 4px 12px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
}
.badge-accent .label {
    font-size: 0.6rem;
    font-weight: 700;
    color: #64748b;
    letter-spacing: 0.05em;
}
.badge-accent .value {
    font-size: 1rem;
    font-weight: 800;
    color: #0f172a;
}

/* Group Section */
.group-header {
    font-size: 0.7rem;
    font-weight: 700;
    color: #94a3b8;
    letter-spacing: 0.1em;
    margin-bottom: 0.5rem;
    padding-left: 0.25rem;
}

/* Card Design */
.measurement-card {
    background: white;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    overflow: hidden;
}

/* Rows Styling */
.m-row {
    display: flex;
    border-bottom: 1px solid #f1f5f9;
}
.m-row:last-child {
    border-bottom: none;
}

.m-label {
    flex: 1.2;
    background: #f8fafc;
    padding: 8px 12px;
    font-size: 0.8rem;
    font-weight: 600;
    color: #64748b;
    border-right: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
}

.m-value {
    flex: 1;
    padding: 8px 12px;
    font-size: 0.8rem;
    font-weight: 700;
    color: #0f172a;
    display: flex;
    align-items: center;
}

@media (min-width: 576px) {
    .border-end-sm {
        border-right: 1px solid #e2e8f0;
    }
}

/* Notes Section */
.notes-box {
    background: #fffbeb;
    border: 1px solid #fef3c7;
    border-radius: 12px;
    padding: 12px 16px;
}
.notes-header {
    font-size: 0.65rem;
    font-weight: 700;
    color: #92400e;
    letter-spacing: 0.05em;
    margin-bottom: 4px;
}
.notes-content {
    font-size: 0.85rem;
    color: #78350f;
    font-weight: 500;
}

.text-slate-900 { color: #0f172a; }
.text-slate-500 { color: #64748b; }
</style>

