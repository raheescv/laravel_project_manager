<template>
    <div
        class="completion-header rounded-xl md:rounded-2xl overflow-hidden shadow-md border border-slate-200/90 bg-gradient-to-br from-white via-slate-50/30 to-white">
        <div class="p-3 sm:p-4">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 sm:gap-4">
                <!-- Order & Customer info -->
                <div class="flex flex-wrap items-stretch gap-2 sm:gap-3">
                    <div
                        class="flex items-center gap-2.5 sm:gap-3 min-w-0 rounded-lg bg-white border border-slate-200/80 shadow-sm px-2.5 py-2 sm:px-3 sm:py-2.5 flex-1 sm:flex-initial">
                        <div
                            class="shrink-0 w-9 h-9 sm:w-10 sm:h-10 rounded-lg bg-blue-100 flex items-center justify-center text-blue-600">
                            <i class="fa fa-file-text text-sm sm:text-base"></i>
                        </div>
                        <div class="min-w-0">
                            <div
                                class="flex items-center gap-1.5 text-[0.65rem] font-bold text-slate-500 uppercase tracking-wider mb-0.5">
                                <i class="fa fa-sort-numeric-asc text-slate-400"></i> Order
                                Number</div>
                            <div class="text-sm sm:text-base font-bold text-blue-600 truncate">{{ order.order_no }}
                            </div>
                        </div>
                    </div>
                    <div
                        class="flex items-center gap-2.5 sm:gap-3 min-w-0 rounded-lg bg-white border border-slate-200/80 shadow-sm px-2.5 py-2 sm:px-3 sm:py-2.5 flex-1 sm:flex-initial">
                        <div
                            class="shrink-0 w-9 h-9 sm:w-10 sm:h-10 rounded-lg bg-emerald-100 flex items-center justify-center text-emerald-600">
                            <i class="fa fa-user text-sm sm:text-base"></i>
                        </div>
                        <div class="min-w-0">
                            <div
                                class="flex items-center gap-1.5 text-[0.65rem] font-bold text-slate-500 uppercase tracking-wider mb-0.5">
                                <i class="fa fa-user text-slate-400"></i> Customer
                            </div>
                            <div class="text-sm sm:text-base font-bold text-slate-800 truncate">{{ order.customer_name
                            }}</div>
                        </div>
                    </div>
                </div>

                <!-- Assignments -->
                <div
                    class="flex flex-col sm:flex-row items-stretch sm:items-end gap-2 sm:gap-3 w-full lg:w-auto lg:min-w-0">
                    <div class="flex-1 min-w-0 sm:min-w-[140px]">
                        <label class="flex items-center gap-1.5 text-xs font-semibold text-slate-700 mb-1">
                            <i class="fa fa-archive text-slate-500"></i> Rack Assignment
                        </label>
                        <SearchableSelect :modelValue="order.rack_id" @update:modelValue="emit('update-rack', $event)"
                            :options="racks" placeholder="Select rack..." input-class="completion-header-select" />
                    </div>
                    <div class="flex-1 min-w-0 sm:min-w-[140px]">
                        <label class="flex items-center gap-1.5 text-xs font-semibold text-slate-700 mb-1">
                            <i class="fa fa-scissors text-slate-500"></i> Assigned Cutter
                        </label>
                        <SearchableSelect :modelValue="order.cutter_id"
                            @update:modelValue="emit('update-cutter', $event)" :options="cutters"
                            placeholder="Select cutter..." input-class="completion-header-select" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>


<script setup>
import SearchableSelect from '@/components/SearchableSelect.vue'

const props = defineProps({
    order: Object,
    racks: Object,
    cutters: Object,
})

const emit = defineEmits(['update-rack', 'update-cutter'])
</script>

<style scoped>
.completion-header {
    position: relative;
}

.completion-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, #3b82f6 0%, #06b6d4 50%, #10b981 100%);
    opacity: 0.9;
}

.completion-header :deep(.completion-header-select) {
    width: 100%;
    padding: 0.375rem 0.5rem;
    padding-right: 2rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: #1e293b;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.completion-header :deep(.completion-header-select:hover) {
    border-color: #cbd5e1;
}

.completion-header :deep(.completion-header-select:focus) {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgb(59 130 246 / 0.15);
    outline: none;
}

@media (max-width: 639px) {
    .completion-header :deep(.completion-header-select) {
        min-height: 40px;
    }
}
</style>
