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
                    <select 
                        :value="order.rack_id"
                        @change="handleRackChange"
                        class="px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                        <option value="">Select rack...</option>
                        <option v-for="(name, id) in racks" :key="id" :value="id">{{ name }}</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cutter</label>
                    <select 
                        :value="order.cutter_id"
                        @change="handleCutterChange"
                        class="px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                        <option value="">Select cutter...</option>
                        <option v-for="(name, id) in cutters" :key="id" :value="id">{{ name }}</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    order: Object,
    racks: Object,
    cutters: Object,
})

const emit = defineEmits(['update-rack', 'update-cutter'])

const handleRackChange = (event) => {
    emit('update-rack', event.target.value)
}

const handleCutterChange = (event) => {
    emit('update-cutter', event.target.value)
}
</script>
