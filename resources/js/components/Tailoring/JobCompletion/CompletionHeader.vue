<template>
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-4">
            <div class="flex flex-col md:flex-row justify-between items-center gap-6">
                <div class="flex gap-8">
                    <div>
                        <div class="text-[0.65rem] font-bold text-slate-400 uppercase tracking-widest mb-1">Order Number</div>
                        <div class="text-lg font-black text-blue-600 leading-none">{{ order.order_no }}</div>
                    </div>
                    <div>
                        <div class="text-[0.65rem] font-bold text-slate-400 uppercase tracking-widest mb-1">Customer</div>
                        <div class="text-lg font-black text-slate-800 leading-none">{{ order.customer_name }}</div>
                    </div>
                </div>
                <div class="flex items-center gap-4 w-full md:w-auto">
                    <div class="flex-1 md:min-w-[180px]">
                        <label class="block text-[0.65rem] font-bold text-slate-400 uppercase tracking-widest mb-1.5 px-1">Rack Assignment</label>
                        <SearchableSelect :modelValue="order.rack_id" @update:modelValue="emit('update-rack', $event)"
                            :options="racks" placeholder="Select rack..." 
                            input-class="!text-xs !py-2 !px-3 !rounded-xl !bg-slate-50 !border-slate-200" />
                    </div>
                    <div class="flex-1 md:min-w-[180px]">
                        <label class="block text-[0.65rem] font-bold text-slate-400 uppercase tracking-widest mb-1.5 px-1">Assigned Cutter</label>
                        <SearchableSelect :modelValue="order.cutter_id"
                            @update:modelValue="emit('update-cutter', $event)" :options="cutters"
                            placeholder="Select cutter..." 
                            input-class="!text-xs !py-2 !px-3 !rounded-xl !bg-slate-50 !border-slate-200" />
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
