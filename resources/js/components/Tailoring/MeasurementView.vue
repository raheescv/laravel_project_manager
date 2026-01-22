<template>
    <div class="measurement-view">
        <!-- Premium Header Area -->
        <div class="d-flex align-items-center justify-content-between mb-4 px-1">
            <div class="d-flex align-items-center gap-3">
                <div class="badge-accent">
                    <span class="label">ITEM</span>
                    <span class="value">#{{ item.item_no }}</span>
                </div>
                <div>
                    <h5 class="fw-bold text-slate-900 mb-0">{{ item.product_name || 'Generic Item' }}</h5>
                    <div class="small text-slate-500 fw-medium">
                        {{ item.category?.name || 'Item' }} <span class="mx-1 opacity-25">•</span> {{ modelName }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Balanced Grid Layout -->
        <div class="row g-4">
            <!-- Dimensions Column -->
            <div class="col-xl-4 col-md-6">
                <div class="group-section">
                    <div class="group-header">
                        <i class="fa fa-ruler-combined me-2"></i>
                        DIMENSIONS
                    </div>
                    <div class="measurement-card">
                        <div v-for="key in groups.dimensions" :key="key" class="m-row">
                            <div class="m-label">{{ formatLabel(key) }}</div>
                            <div class="m-value" :class="{ 'text-muted opacity-50': !getValue(key) }">
                                {{ getValue(key) ?? '-' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Components Column -->
            <div class="col-xl-4 col-md-6">
                <div class="group-section">
                    <div class="group-header">
                        <i class="fa fa-puzzle-piece me-2"></i>
                        COMPONENTS
                    </div>
                    <div class="measurement-card">
                        <div v-for="key in groups.components" :key="key" class="m-row">
                            <div class="m-label">{{ formatLabel(key) }}</div>
                            <div class="m-value" :class="{ 'text-muted opacity-50': !getValue(key) }">
                                {{ getValue(key) ?? '-' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Styles Column (Full width on large, balanced with internal grid) -->
            <div class="col-xl-4 col-12">
                <div class="group-section">
                    <div class="group-header">
                        <i class="fa fa-cut me-2"></i>
                        STYLES & MODELS
                    </div>
                    <div class="measurement-card">
                        <!-- Dual column grid for styles to balance height -->
                        <div class="row g-0">
                            <div class="col-sm-6 border-end-sm">
                                <div v-for="key in groups.styles.slice(0, 7)" :key="key" class="m-row">
                                    <div class="m-label">{{ formatLabel(key) }}</div>
                                    <div class="m-value" :class="{ 'text-muted opacity-50': !getValue(key) }">
                                        {{ getValue(key) ?? '-' }}
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div v-for="key in groups.styles.slice(7)" :key="key" class="m-row">
                                    <div class="m-label">{{ formatLabel(key) }}</div>
                                    <div class="m-value" :class="{ 'text-muted opacity-50': !getValue(key) }">
                                        {{ getValue(key) ?? '-' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notes Section -->
        <div v-if="item.tailoring_notes" class="mt-4 notes-box">
            <div class="notes-header">
                <i class="fa fa-info-circle me-1"></i>
                SPECIAL INSTRUCTIONS
            </div>
            <div class="notes-content">
                {{ item.tailoring_notes }}
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

