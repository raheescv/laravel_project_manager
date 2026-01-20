<template>
    <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-200">
        <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-lg font-bold text-gray-800">Work Orders Preview</h2>
            <div class="text-sm text-gray-500 font-medium">
                {{ items.length }} {{ items.length === 1 ? 'item' : 'items' }} total
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-black text-white">
                        <th class="px-4 py-3 text-left text-sm font-semibold">No</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold">Category</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold">Model</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold">Product</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold">Qty</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold">Colour</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold text-right">Rate</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold text-right">Stitch Rate</th>
                        <th class="px-4 py-3 text-right text-sm font-semibold">Amount</th>
                        <th class="px-4 py-3 text-center text-sm font-semibold w-24">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(item, index) in items" :key="item.id || item._temp_id || index"
                        class="border-b border-gray-200 hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-medium">{{ item.item_no }}</td>
                        <td class="px-4 py-3 text-sm font-semibold text-blue-700">
                            {{ item.category?.name || 'Unknown' }}
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <span class="px-2 py-0.5 bg-gray-100 text-gray-700 text-xs font-bold rounded">
                                {{ item.category_model?.name || item.tailoring_category_model_name || '-' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm">{{ item.product_name }}</td>
                        <td class="px-4 py-3 text-sm">{{ item.quantity }}</td>
                        <td class="px-4 py-3 text-sm">{{ item.product_color || '-' }}</td>
                        <td class="px-4 py-3 text-sm text-right">{{ formatCurrency(item.unit_price) }}</td>
                        <td class="px-4 py-3 text-sm text-right">{{ formatCurrency(item.stitch_rate) }}</td>
                        <td class="px-4 py-3 text-sm text-right font-bold text-gray-900">{{ formatCurrency(item.total) }}</td>
                        <td class="px-4 py-3 text-sm text-center">
                            <div class="flex justify-center gap-2">
                                <button type="button" @click="viewMeasurements(item)"
                                    class="text-amber-600 hover:text-amber-800 transition-colors"
                                    title="View Measurements">
                                    <i class="fa fa-eye"></i>
                                </button>
                                <button type="button" @click="$emit('edit', item)"
                                    class="text-blue-600 hover:text-blue-800 transition-colors" title="Edit">
                                    <i class="fa fa-pencil"></i>
                                </button>
                                <button type="button" @click="$emit('remove', item)"
                                    class="text-red-600 hover:text-red-800 transition-colors" title="Remove">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="items.length === 0">
                        <td colspan="10" class="px-4 py-12 text-center text-gray-500 italic bg-gray-50/50">
                            No items added to the order yet.
                        </td>
                    </tr>
                </tbody>
                <tfoot v-if="items.length > 0" class="bg-gray-50 border-t-2 border-gray-200">
                    <tr>
                        <td colspan="8" class="px-4 py-3 text-sm font-bold text-gray-700 text-right">Grand Total:</td>
                        <td class="px-4 py-3 text-lg font-bold text-blue-600 text-right">{{ formatCurrency(grandTotal) }}</td>
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


