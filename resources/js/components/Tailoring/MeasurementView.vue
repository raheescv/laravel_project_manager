<template>
    <div class="space-y-4 min-h-[200px]">
        <!-- Compact Header -->
        <div class="flex items-center justify-between border-b pb-3">
            <div class="space-y-0.5">
                <div class="flex items-center gap-2">
                    <span class="px-1.5 py-0.5 bg-gray-100 text-[10px] font-bold rounded uppercase">Item #{{ item.item_no }}</span>
                    <h2 class="text-lg font-black text-gray-900">{{ item.product_name || 'Generic Item' }}</h2>
                </div>
                <div class="text-xs text-gray-500 font-medium">
                    {{ item.category?.name }} • {{ modelName }}
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Measurement Groups -->
            <div v-for="(group, groupIdx) in groupedMeasurements" :key="groupIdx" class="space-y-2">
                <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest pl-1">{{ group.title }}</h4>
                <div class="bg-white border rounded-lg overflow-hidden">
                    <table class="w-full text-left text-xs">
                        <tbody class="divide-y">
                            <tr v-for="key in group.keys" :key="key" class="hover:bg-gray-50">
                                <td class="py-2 px-3 font-semibold text-gray-500 bg-gray-50/50 w-2/5">{{ formatLabel(key) }}</td>
                                <td class="py-2 px-3 font-bold text-gray-900">{{ getValue(key) || '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Notes Section -->
        <div v-if="item.tailoring_notes" class="mt-4 p-3 bg-amber-50 rounded-lg border border-amber-100 text-xs">
            <span class="block font-black text-amber-700 uppercase mb-1">Special Instructions:</span>
            <p class="text-gray-700 leading-normal">{{ item.tailoring_notes }}</p>
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

const groupedMeasurements = [
    {
        title: 'Dimensions',
        keys: ['length', 'shoulder', 'sleeve', 'chest', 'stomach', 'sl_chest', 'sl_so', 'neck', 'bottom']
    },
    {
        title: 'Components',
        keys: ['mar_size', 'cuff_size', 'collar_size', 'regal_size', 'knee_loose', 'fp_size', 'side_pt_size', 'button_no', 'neck_d_button']
    },
    {
        title: 'Styles',
        keys: ['mar_model', 'cuff', 'cuff_cloth', 'cuff_model', 'collar', 'collar_cloth', 'collar_model', 'fp_down', 'fp_model', 'pen', 'side_pt_model', 'stitching', 'button', 'mobile_pocket']
    }
]

const hasGroupValues = (group) => group.keys.some(key => getValue(key) !== null)

const totalVisibleFields = computed(() => {
    return groupedMeasurements.reduce((acc, group) => {
        return acc + group.keys.filter(key => getValue(key) !== null).length
    }, 0)
})

const formatLabel = (key) => key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
</script>
