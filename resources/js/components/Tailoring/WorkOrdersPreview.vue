<template>
    <div class="space-y-6">
        <div v-for="(group, groupKey) in groupedItems" :key="groupKey" 
            class="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-200">
            <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 flex justify-between items-center">
                <div class="flex items-center gap-4">
                    <h2 class="text-lg font-bold text-gray-800">{{ group.categoryName }}</h2>
                    <span class="px-3 py-1 bg-blue-100 text-blue-800 text-xs font-bold rounded-full uppercase tracking-wider">
                        Model: {{ group.modelName }}
                    </span>
                </div>
                <div class="text-sm text-gray-500">
                    {{ group.items.length }} {{ group.items.length === 1 ? 'item' : 'items' }}
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-black text-white">
                            <th class="px-4 py-3 text-left text-sm font-semibold">No</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold">Product</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold">Qty</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold">Colour</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold">Rate</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold">Stitch Rate</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold">Amount</th>
                            <th class="px-4 py-3 text-center text-sm font-semibold w-24">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, index) in group.items" :key="item.id || item._temp_id || index"
                            class="border-b border-gray-200 hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm font-medium">{{ item.item_no }}</td>
                            <td class="px-4 py-3 text-sm">{{ item.product_name }}</td>
                            <td class="px-4 py-3 text-sm">{{ item.quantity }}</td>
                            <td class="px-4 py-3 text-sm">{{ item.product_color || '-' }}</td>
                            <td class="px-4 py-3 text-sm">{{ formatCurrency(item.unit_price) }}</td>
                            <td class="px-4 py-3 text-sm">{{ formatCurrency(item.stitch_rate) }}</td>
                            <td class="px-4 py-3 text-sm text-right font-medium">{{ formatCurrency(item.total) }}</td>
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
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr>
                            <td colspan="6" class="px-4 py-3 text-sm font-bold text-gray-700 text-right">Group Total:</td>
                            <td class="px-4 py-3 text-sm font-bold text-blue-600 text-right">{{ formatCurrency(group.totalPrice) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div v-if="items.length === 0" class="bg-white rounded-lg shadow-sm p-12 text-center border border-dashed border-gray-300">
            <div class="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                <i class="fa fa-shopping-basket text-gray-400 text-2xl"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900">No items added yet</h3>
            <p class="mt-1 text-sm text-gray-500">Pick a category and add products to your order.</p>
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

const groupedItems = computed(() => {
    const groups = {}
    
    props.items.forEach(item => {
        const catId = item.tailoring_category_id
        const modelId = item.tailoring_category_model_id || 'no-model'
        const key = `${catId}-${modelId}`
        
        if (!groups[key]) {
            groups[key] = {
                categoryName: item.category?.name || 'Unknown',
                modelName: item.category_model?.name || item.tailoring_category_model_name || 'Standard',
                items: [],
                totalPrice: 0
            }
        }
        
        groups[key].items.push(item)
        groups[key].totalPrice += parseFloat(item.total || 0)
    })
    
    return groups
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

