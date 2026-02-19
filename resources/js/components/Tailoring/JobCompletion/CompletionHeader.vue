<template>
    <div
        class="completion-header rounded-xl md:rounded-2xl overflow-hidden shadow-sm border border-slate-200/90 bg-gradient-to-br from-white via-slate-50/30 to-white">
        <div class="p-3 sm:p-4">
            <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-3 sm:gap-4">
                <!-- Order & Customer info -->
                <div class="flex flex-wrap items-stretch gap-2 sm:gap-3">
                    <div
                        class="flex items-center gap-2 sm:gap-2.5 min-w-0 rounded-lg bg-white border border-slate-200/80 shadow-sm px-2.5 py-2 sm:px-3 sm:py-2.5 flex-1 sm:flex-initial">
                        <div
                            class="shrink-0 w-9 h-9 sm:w-10 sm:h-10 rounded-lg bg-blue-100 flex items-center justify-center text-blue-600">
                            <i class="fa fa-file-text text-sm sm:text-base"></i>
                        </div>
                        <div class="min-w-0 max-w-[250px]">
                            <div
                                class="flex items-center gap-1.5 text-[0.65rem] font-bold text-slate-500 uppercase tracking-wider mb-0.5">
                                <i class="fa fa-sort-numeric-asc text-slate-400"></i> Order Number
                            </div>
                            <div class="text-sm sm:text-base font-bold text-blue-600 truncate">{{ order.order_no }}
                            </div>
                            <a v-if="order?.id" :href="`/tailoring/order/${order.id}`"
                                class="inline-flex items-center gap-1 text-[11px] font-semibold text-blue-700 hover:text-blue-800 mt-1 no-underline">
                                <i class="fa fa-external-link"></i>
                                View Order
                            </a>
                        </div>
                    </div>
                    <div
                        class="flex items-center gap-2 sm:gap-2.5 min-w-0 rounded-lg bg-white border border-slate-200/80 shadow-sm px-2.5 py-2 sm:px-3 sm:py-2.5 flex-1 sm:flex-initial">
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
                    class="w-full xl:w-[520px] xl:min-w-[460px] rounded-xl border border-slate-200/80 bg-white/80 p-2.5 sm:p-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 sm:gap-3 items-end">
                    <div class="min-w-0">
                        <label class="flex items-center gap-1.5 text-xs font-semibold text-slate-700 mb-1">
                            <i class="fa fa-archive text-slate-500"></i> Rack Assignment
                        </label>
                        <VSelect
                            :modelValue="order.rack_id"
                            @update:modelValue="emit('update-rack', $event)"
                            :options="rackOptions"
                            placeholder="Select rack"
                            class="completion-header-vselect"
                        />
                    </div>
                    <div class="min-w-0">
                        <label class="flex items-center gap-1.5 text-xs font-semibold text-slate-700 mb-1">
                            <i class="fa fa-scissors text-slate-500"></i> Assigned Cutter
                        </label>
                        <VSelect
                            :modelValue="order.cutter_id"
                            @update:modelValue="emit('update-cutter', $event)"
                            :options="cutterOptions"
                            placeholder="Select cutter"
                            class="completion-header-vselect"
                        />
                    </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>


<script setup>
import { computed } from 'vue'
import VSelect from '@/components/VSelect.vue'

const props = defineProps({
    order: Object,
    racks: Object,
    cutters: Object,
})

const emit = defineEmits(['update-rack', 'update-cutter'])

const normalizeOptions = (source) => {
    if (Array.isArray(source)) {
        return source.map((option) => ({
            value: option?.value ?? option?.id,
            label: option?.label ?? option?.name ?? String(option?.id ?? ''),
        })).filter((option) => option.value !== undefined && option.value !== null && option.label)
    }

    return Object.entries(source || {}).map(([value, label]) => ({
        value,
        label: String(label),
    }))
}

const rackOptions = computed(() => normalizeOptions(props.racks))
const cutterOptions = computed(() => normalizeOptions(props.cutters))
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

.completion-header :deep(.completion-header-vselect .multiselect__tags) {
    min-height: 36px;
    padding: 5px 26px 5px 10px;
    border-radius: 0.75rem;
    border: 1px solid #e2e8f0;
    background: #fff;
    font-size: 0.8rem;
    font-weight: 600;
}

.completion-header :deep(.completion-header-vselect .multiselect--active .multiselect__tags) {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgb(59 130 246 / 0.15);
}

.completion-header :deep(.completion-header-vselect .multiselect__single),
.completion-header :deep(.completion-header-vselect .multiselect__placeholder) {
    margin-bottom: 0;
}

.completion-header :deep(.completion-header-vselect .multiselect__select) {
    width: 28px;
    height: 34px;
}

@media (max-width: 639px) {
    .completion-header :deep(.completion-header-vselect .multiselect__tags) {
        min-height: 34px;
        padding: 4px 24px 4px 8px;
        font-size: 0.75rem;
    }
}
</style>
