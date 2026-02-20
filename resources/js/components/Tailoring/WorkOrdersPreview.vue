<template>
    <div class="work-preview-wrap rounded-3xl overflow-hidden relative">
        <div class="absolute inset-0 bg-gradient-to-tr from-slate-50/70 via-white to-blue-50/60 pointer-events-none">
        </div>

        <div
            class="px-4 py-3 border-b border-slate-200/80 flex items-center justify-between bg-gradient-to-r from-indigo-50 via-blue-50 to-white relative z-10">
            <div class="flex items-center gap-2.5 min-w-0">
                <span
                    class="w-9 h-9 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center border border-indigo-200 shadow-sm shrink-0">
                    <i class="fa fa-list-alt text-sm"></i>
                </span>
                <div class="min-w-0">
                    <h3 class="text-sm font-black text-slate-800 uppercase tracking-wide mb-0 truncate">Work Orders
                        Preview</h3>
                    <p class="text-[11px] text-slate-500 font-semibold mb-0 truncate">Structured summary of item
                        details, quantity and pricing</p>
                </div>
            </div>
            <div
                class="bg-gradient-to-r from-indigo-100 to-blue-100 text-indigo-700 text-[0.72rem] font-black px-3 py-1.5 rounded-xl uppercase tracking-wider border border-indigo-200 shadow-sm shrink-0">
                {{ items.length }} {{ items.length === 1 ? 'Job' : 'Jobs' }}
            </div>
        </div>

        <div class="overflow-x-auto relative z-10">
            <table class="work-orders-table w-full text-left border-collapse min-w-[760px] lg:min-w-[980px]">
                <colgroup>
                    <col class="col-no">
                    <col class="col-item">
                    <col class="col-qty">
                    <col class="col-pricing">
                    <col class="col-actions">
                </colgroup>
                <thead>
                    <tr class="bg-slate-100/90 border-b border-slate-200">
                        <th class="px-4 py-3 text-[0.65rem] font-black text-slate-500 uppercase tracking-[0.1em]">
                            No
                        </th>
                        <th class="px-4 py-3 text-[0.65rem] font-black text-slate-500 uppercase tracking-[0.1em]">
                            Item Details
                        </th>
                        <th
                            class="px-4 py-3 text-[0.65rem] font-black text-slate-500 uppercase tracking-[0.1em] text-center">
                            Qty / Meter
                        </th>
                        <th
                            class="px-4 py-3 text-[0.65rem] font-black text-slate-500 uppercase tracking-[0.1em] text-right">
                            Pricing
                        </th>
                        <th
                            class="px-4 py-3 text-[0.65rem] font-black text-slate-500 uppercase tracking-[0.1em] text-center">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="(item, index) in items" :key="item.id || item._temp_id || index" class="row-item">
                        <td class="px-4 py-3 align-top">
                            <span
                                class="inline-flex items-center justify-center min-w-[36px] h-7 px-2 text-[0.7rem] font-black text-indigo-700 bg-indigo-100 rounded-lg border border-indigo-200">
                                #{{ item.item_no }}
                            </span>
                        </td>
                        <td class="px-4 py-3 align-top">
                            <div class="flex flex-col gap-1 text-[0.72rem] leading-tight">
                                <div>
                                    <span class="text-slate-500 font-semibold">Category:</span>
                                    <span class="text-slate-900 font-black">{{ item.category?.name || '-' }}</span>
                                </div>
                                <div>
                                    <span class="text-slate-500 font-semibold">Model:</span> <span
                                        class="text-slate-700 font-semibold">
                                        {{ item.tailoring_category_model_name || item.category_model?.name || '-' }}
                                    </span>
                                </div>
                                <div>
                                    <span class="text-slate-500 font-semibold">Type:</span>
                                    <span class="text-slate-700 font-semibold">
                                        {{ item.tailoring_category_model_type_name || item.categoryModelType?.name ||
                                        item.category_model_type?.name || '-' }}
                                    </span>
                                </div>
                                <div class="truncate max-w-[300px] text-slate-800 font-bold pt-0.5">
                                    {{ item.product_name || '-' }}
                                </div>
                                <div>
                                    <span class="text-slate-500 font-semibold">Color:</span>
                                    <span class="text-slate-700 font-semibold">
                                        {{ item.product_color || 'N/A' }}
                                    </span>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center align-top">
                            <div class="flex flex-col items-center gap-1.5">
                                <div class="flex items-center gap-1.5">
                                    <span
                                        class="text-[0.58rem] font-bold uppercase tracking-[0.08em] text-slate-500">Qty</span>
                                    <span class="text-lg leading-none font-black text-slate-800 tabular-nums">
                                        {{ Math.round(item.quantity) }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span
                                        class="text-[0.58rem] font-bold uppercase tracking-[0.08em] text-slate-500">Meter</span>
                                    <span class="leading-none font-black text-slate-700 tabular-nums">
                                        {{ item.quantity_per_item ?? 1 }}
                                    </span>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-right align-top">
                            <div class="flex flex-col items-end gap-0.5">
                                <span
                                    class="text-2xl leading-none font-black text-indigo-700 tracking-tight tabular-nums">
                                    {{ formatCurrency(item.total) }}
                                </span>
                                <div class="text-[0.58rem] text-slate-500 font-bold tracking-tight leading-tight">
                                    <div class="flex justify-end gap-3">
                                        <span class="w-10 text-right text-capitalize">price</span>
                                        <span class="w-24 text-right tabular-nums">
                                            {{ formatCurrency(item.unit_price) }}
                                        </span>
                                    </div>
                                    <div class="flex justify-end gap-3"><span class="w-10 text-right text-capitalize">
                                            stitch
                                        </span>
                                        <span class="w-24 text-right tabular-nums">
                                            {{ formatCurrency(item.stitch_rate) }}
                                        </span>
                                    </div>
                                    <div class="flex justify-end gap-3"><span class="w-10 text-right text-capitalize">
                                            tax
                                        </span>
                                        <span class="w-24 text-right tabular-nums">{{ formatPercent(item.tax) }}</span>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 align-top">
                            <div class="flex items-center justify-center gap-2">
                                <button type="button" @click="viewMeasurements(item)"
                                    class="w-9 h-9 flex items-center justify-center rounded-xl bg-amber-50 text-amber-600 border border-amber-100 hover:bg-amber-500 hover:text-white transition-all"
                                    title="View Measurements">
                                    <i class="fa fa-eye text-xs"></i>
                                </button>
                                <button type="button" @click="$emit('edit', item)"
                                    class="w-9 h-9 flex items-center justify-center rounded-xl bg-blue-50 text-blue-600 border border-blue-100 hover:bg-blue-500 hover:text-white transition-all"
                                    title="Edit">
                                    <i class="fa fa-pencil text-xs"></i>
                                </button>
                                <button type="button" @click="$emit('remove', item)"
                                    class="w-9 h-9 flex items-center justify-center rounded-xl bg-rose-50 text-rose-600 border border-rose-100 hover:bg-rose-500 hover:text-white transition-all"
                                    title="Remove">
                                    <i class="fa fa-trash text-xs"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="items.length === 0">
                        <td colspan="6" class="px-4 py-8 text-center">
                            <div class="flex flex-col items-center gap-1 opacity-30">
                                <i class="fa fa-clipboard-list text-2xl"></i>
                                <p class="text-[0.62rem] font-bold uppercase tracking-[0.2em]">No jobs</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
                <tfoot v-if="items.length > 0">
                    <tr class="bg-gradient-to-r from-slate-50 to-blue-50 border-t border-slate-200">
                        <td colspan="4"
                            class="px-4 py-3 text-right text-[0.65rem] font-black text-slate-500 uppercase tracking-[0.08em]">
                            Grand Total:
                        </td>
                        <td class="px-4 py-3 text-right">
                            <span class="text-3xl font-black text-indigo-800 leading-none tabular-nums">
                                {{ formatCurrency(grandTotal) }}
                            </span>
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <MeasurementViewModal v-if="selectedItemForView" :show="showViewModal" :item="selectedItemForView"
            @close="closeViewModal" />
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import MeasurementViewModal from '@/components/Tailoring/MeasurementViewModal.vue'
import { formatPercent, formatCurrency } from '@/utils/number'

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

</script>

<style scoped>
.work-preview-wrap {
    border: 1px solid #cbd5e1;
    box-shadow: 0 14px 36px rgba(15, 23, 42, 0.09);
    background: #fff;
}

.work-orders-table tbody tr {
    transition: background-color 160ms ease;
}

.work-orders-table tbody tr.row-item:hover {
    background-color: #f8fbff !important;
}

.work-orders-table .col-no {
    width: 6%;
}

.work-orders-table .col-item {
    width: 43%;
}

.work-orders-table .col-qty {
    width: 16%;
}

.work-orders-table .col-color {
    width: 9%;
}

.work-orders-table .col-pricing {
    width: 14%;
}

.work-orders-table .col-actions {
    width: 10%;
}

@media (max-width: 1024px) {
    .work-orders-table {
        min-width: 900px;
    }
}

@media (max-width: 768px) {
    .work-orders-table {
        min-width: 760px;
    }

    .work-orders-table th,
    .work-orders-table td {
        padding-left: 0.65rem;
        padding-right: 0.65rem;
    }

    .work-orders-table .col-item {
        width: 40%;
    }
}
</style>
