<template>
    <div class="bg-white rounded-3xl shadow-lg shadow-slate-200/50 border border-slate-200 overflow-hidden relative">
        <!-- Background Tint -->
        <div class="absolute inset-0 bg-gradient-to-tr from-slate-50/50 to-transparent pointer-events-none"></div>
        
        <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/30 relative z-10">
            <div class="flex items-center gap-2">
                <i class="fa fa-list-alt text-indigo-500 text-xs text-indigo-500/80"></i>
                <h3 class="text-xs font-black text-slate-700 uppercase tracking-widest">Work Orders Preview</h3>
            </div>
            <div class="bg-indigo-100/50 text-indigo-600 text-[0.6rem] font-black px-2.5 py-1 rounded-lg uppercase tracking-wider border border-indigo-200/50">
                {{ items.length }} {{ items.length === 1 ? 'Job' : 'Jobs' }}
            </div>
        </div>
        
        <div class="overflow-x-auto relative z-10">
            <table class="w-full text-left border-collapse whitespace-nowrap">
                <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-100">
                        <th class="px-4 py-3 text-[0.6rem] font-black text-slate-400 uppercase tracking-[0.1em]">No</th>
                        <th class="px-4 py-3 text-[0.6rem] font-black text-slate-400 uppercase tracking-[0.1em]">Item Details</th>
                        <th class="px-4 py-3 text-[0.6rem] font-black text-slate-400 uppercase tracking-[0.1em]">Model</th>
                        <th class="px-4 py-3 text-[0.6rem] font-black text-slate-400 uppercase tracking-[0.1em] text-center">Qty</th>
                        <th class="px-4 py-3 text-[0.6rem] font-black text-slate-400 uppercase tracking-[0.1em]">Colour</th>
                        <th class="px-4 py-3 text-[0.6rem] font-black text-slate-400 uppercase tracking-[0.1em] text-right">Pricing</th>
                        <th class="px-4 py-3 text-[0.6rem] font-black text-slate-400 uppercase tracking-[0.1em] text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50/50">
                    <tr v-for="(item, index) in items" :key="item.id || item._temp_id || index" class="hover:bg-indigo-50/30 transition-colors group">
                        <td class="px-4 py-2">
                            <span class="text-[0.65rem] font-bold text-slate-400 italic">#{{ item.item_no }}</span>
                        </td>
                        <td class="px-4 py-2">
                            <div class="flex flex-col">
                                <span class="text-xs font-bold text-slate-800">{{ item.category?.name || 'Unknown' }}</span>
                                <span class="text-[0.6rem] text-slate-400 font-medium truncate max-w-[120px]">{{ item.product_name }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-2">
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-lg bg-slate-100 text-slate-600 text-[0.6rem] font-bold border border-slate-200">
                                {{ item.category_model?.name || item.tailoring_category_model_name || '-' }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-center">
                            <span class="text-xs font-black text-slate-700">{{ item.quantity }}</span>
                        </td>
                        <td class="px-4 py-2">
                            <span class="text-[0.65rem] font-bold text-slate-500 lowercase opacity-70">{{ item.product_color || '-' }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex flex-col items-end">
                                <span class="text-xs font-black text-indigo-600 tracking-tight">{{ formatCurrency(item.total) }}</span>
                                <span class="text-[0.55rem] text-slate-400 font-bold tracking-tight">{{ formatCurrency(item.unit_price) }} + {{ formatCurrency(item.stitch_rate) }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-2">
                            <div class="flex items-center justify-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button type="button" @click="viewMeasurements(item)"
                                    class="w-7 h-7 flex items-center justify-center rounded-lg bg-amber-50 text-amber-500 hover:bg-amber-500 hover:text-white transition-all"
                                    title="View Measurements">
                                    <i class="fa fa-eye text-[10px]"></i>
                                </button>
                                <button type="button" @click="$emit('edit', item)"
                                    class="w-7 h-7 flex items-center justify-center rounded-lg bg-blue-50 text-blue-500 hover:bg-blue-500 hover:text-white transition-all" title="Edit">
                                    <i class="fa fa-pencil text-[10px]"></i>
                                </button>
                                <button type="button" @click="$emit('remove', item)"
                                    class="w-7 h-7 flex items-center justify-center rounded-lg bg-rose-50 text-rose-500 hover:bg-rose-500 hover:text-white transition-all" title="Remove">
                                    <i class="fa fa-trash text-[10px]"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="items.length === 0">
                        <td colspan="7" class="px-4 py-8 text-center">
                            <div class="flex flex-col items-center gap-1 opacity-20">
                                <i class="fa fa-clipboard-list text-2xl"></i>
                                <p class="text-[0.6rem] font-bold uppercase tracking-[0.2em]">No jobs</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
                <tfoot v-if="items.length > 0">
                    <tr class="bg-slate-50/50">
                        <td colspan="5" class="px-4 py-3 text-right text-[0.6rem] font-bold text-slate-400 uppercase tracking-widest">Grand Total:</td>
                        <td class="px-4 py-3 text-right">
                            <span class="text-sm font-black text-slate-900 leading-none">{{ formatCurrency(grandTotal) }}</span>
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>


        <!-- Measurement View Modal -->
        <MeasurementViewModal v-if="selectedItemForView" :show="showViewModal" :item="selectedItemForView"
            @close="closeViewModal" />
    </div>
</template>


<script setup>
import { ref, computed } from 'vue'
import MeasurementViewModal from '@/components/Tailoring/MeasurementViewModal.vue'

const props = defineProps({
    items: {
        type: Array,
        default: () => []
    }
})

const emit = defineEmits(['edit', 'remove'])

const selectedItemForView = ref(null)
const showViewModal = ref(false)

const grandTotal = computed(() => {
    return props.items.reduce((sum, item) => sum + parseFloat(item.total || 0), 0)
})

const viewMeasurements = (item) => {
    selectedItemForView.value = item
    showViewModal.value = true
}

const closeViewModal = () => {
    showViewModal.value = false
    selectedItemForView.value = null
}

const formatCurrency = (value) => {
    return parseFloat(value || 0).toFixed(2)
}
</script>


