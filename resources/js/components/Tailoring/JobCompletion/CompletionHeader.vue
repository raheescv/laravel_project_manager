<template>
    <div class="bg-white rounded-lg shadow-sm p-4 md:p-6">
        <div class="flex justify-between items-center flex-wrap gap-4">
            <div class="flex items-center gap-6">
                <div>
                    <span class="text-sm text-gray-600">Order No:</span>
                    <span class="text-lg font-bold text-gray-800 ml-2">{{ order.order_no }}</span>
                </div>
                <div>
                    <span class="text-sm text-gray-600">Customer:</span>
                    <span class="text-lg font-bold text-gray-800 ml-2">{{ order.customer_name }}</span>
                </div>
            </div>
            <div class="flex gap-4">
                <div class="form-group">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Rack</label>
                    <SearchableSelect 
                        :modelValue="order.rack_id"
                        @update:modelValue="emit('update-rack', $event)"
                        :options="racks"
                        placeholder="Select rack..." />
                </div>
                <div class="form-group">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cutter</label>
                    <SearchableSelect 
                        :modelValue="order.cutter_id"
                        @update:modelValue="emit('update-cutter', $event)"
                        :options="cutters"
                        placeholder="Select cutter..." />
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
