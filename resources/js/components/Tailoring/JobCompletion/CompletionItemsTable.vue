<template>
    <div class="space-y-4">
        <!-- Header with Select All -->
        <div class="flex items-center justify-between bg-white p-4 rounded-lg shadow-sm border border-gray-200">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-indigo-50 rounded-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-600" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                </div>
                <div>
                    <h3 class="font-bold text-gray-800">Job Completion Items</h3>
                    <p class="text-xs text-gray-500">{{ items.length }} items total</p>
                </div>
            </div>

            <label class="flex items-center gap-2 cursor-pointer group">
                <span class="text-sm font-medium text-gray-600 group-hover:text-indigo-600 transition-colors">Select All for Completion</span>
                <input type="checkbox" @change="handleSelectAll" :checked="allSelected" 
                    class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500" />
            </label>
        </div>

        <!-- Empty State -->
        <div v-if="items.length === 0"
            class="flex flex-col items-center justify-center p-12 bg-white rounded-lg border-2 border-dashed border-gray-200">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mb-4 text-gray-300" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9.172 9.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="text-lg font-medium text-gray-500">No items found</p>
            <p class="text-sm text-gray-400">Search for an order to see completion items</p>
        </div>

        <!-- Table -->
        <div v-else class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider w-12">
                                <input type="checkbox" @change="handleSelectAll" :checked="allSelected"
                                    class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500" />
                            </th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                Item Details
                            </th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                Tailor Assignment
                            </th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                Material Tracking
                            </th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                Completion Date
                            </th>
                            <th scope="col" class="px-3 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider w-32">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="item in items" :key="item.id"
                            class="hover:bg-gray-50 transition-colors"
                            :class="{ 'bg-indigo-50/30': item.is_selected_for_completion }">
                            <!-- Checkbox Column -->
                            <td class="px-3 py-4 whitespace-nowrap">
                                <input :checked="item.is_selected_for_completion" @change="toggleItemCompletion(item, $event)"
                                    type="checkbox"
                                    class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500" />
                            </td>

                            <!-- Item Details Column -->
                            <td class="px-3 py-4">
                                <div class="space-y-2">
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-0.5 bg-indigo-100 text-indigo-700 text-xs font-bold rounded">
                                            #{{ item.item_no }}
                                        </span>
                                        <span class="text-xs text-gray-500 uppercase font-semibold">{{ item.category?.name }}</span>
                                    </div>
                                    <div class="font-semibold text-gray-900">{{ item.product_name }}</div>
                                    <div class="grid grid-cols-2 gap-2 text-xs">
                                        <div>
                                            <span class="text-gray-500">Qty:</span>
                                            <span class="font-semibold ml-1">{{ item.quantity }}</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-500">Amount:</span>
                                            <span class="font-semibold text-indigo-600 ml-1">{{ formatCurrency(item.amount) }}</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-500">Model:</span>
                                            <span class="font-semibold ml-1">{{ item.categoryModel?.name || '-' }}</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-500">Length:</span>
                                            <span class="font-semibold ml-1">{{ item.length || '-' }}</span>
                                        </div>
                                    </div>
                                    <button @click="viewMeasurements(item)"
                                        class="text-xs text-amber-600 hover:text-amber-700 font-semibold flex items-center gap-1">
                                        <i class="fa fa-eye"></i> View Measurements
                                    </button>
                                </div>
                            </td>

                            <!-- Tailor Assignment Column -->
                            <td class="px-3 py-4">
                                <div class="space-y-2">
                                    <div>
                                        <label class="block text-xs text-gray-500 font-medium mb-1">Assign Worker</label>
                                        <SearchableSelect 
                                            :modelValue="item.tailor_id" 
                                            @update:modelValue="item.tailor_id = $event"
                                            :options="tailors"
                                            placeholder="Select tailor..." 
                                            class="w-full" />
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 font-medium mb-1">Rate/Item</label>
                                        <input v-model.number="item.tailor_commission" @input="calculateCommission(item)"
                                            type="number" step="0.01" min="0"
                                            class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                                    </div>
                                    <div class="text-xs">
                                        <span class="text-gray-500">Total Commission:</span>
                                        <span class="font-bold text-indigo-600 ml-1">{{ formatCurrency(item.tailor_total_commission) }}</span>
                                    </div>
                                </div>
                            </td>

                            <!-- Material Tracking Column -->
                            <td class="px-3 py-4">
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-xs text-gray-500 font-medium">Stock Balance:</span>
                                        <div class="flex items-center gap-2">
                                            <span :class="item.stock_balance < 0 ? 'text-rose-600' : 'text-emerald-600'"
                                                class="text-sm font-bold">
                                                {{ (item.stock_balance || 0).toFixed(3) }}
                                            </span>
                                            <button @click="refreshStock(item)"
                                                class="p-1 hover:bg-gray-100 rounded transition-colors"
                                                title="Refresh Stock">
                                                <i class="fa fa-refresh text-xs text-gray-500"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 font-medium mb-1">In Stock</label>
                                        <div class="px-2 py-1.5 bg-gray-50 border border-gray-200 rounded text-sm font-semibold text-gray-700">
                                            {{ (item.product?.stock_quantity || 0).toFixed(3) }}
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-xs text-gray-500 font-medium mb-1">Used Qty</label>
                                            <input v-model.number="item.used_quantity" @input="calculateStockBalance(item)"
                                                type="number" step="0.001" min="0"
                                                class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                                        </div>
                                        <div>
                                            <label class="block text-xs text-gray-500 font-medium mb-1">Wastage</label>
                                            <input v-model.number="item.wastage" @input="calculateStockBalance(item)"
                                                type="number" step="0.001" min="0"
                                                class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Completion Date Column -->
                            <td class="px-3 py-4">
                                <input v-model="item.item_completion_date" type="date"
                                    class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                            </td>

                            <!-- Actions Column -->
                            <td class="px-3 py-4 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <button @click="saveItem(item)"
                                        class="w-full px-3 py-1.5 bg-indigo-600 text-white text-xs font-semibold rounded hover:bg-indigo-700 transition-colors flex items-center justify-center gap-1">
                                        <i class="fa fa-save"></i>
                                        Save
                                    </button>
                                    <span v-if="item.is_selected_for_completion"
                                        class="px-2 py-0.5 bg-emerald-100 text-emerald-700 text-xs font-semibold rounded-full">
                                        ✓ Ready
                                    </span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Measurement View Modal -->
        <MeasurementViewModal v-if="selectedItemForView" :show="showViewModal" :item="selectedItemForView"
            @close="closeViewModal" />
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import axios from 'axios'
import { useToast } from 'vue-toastification'
import MeasurementViewModal from '@/components/Tailoring/MeasurementViewModal.vue'
import SearchableSelect from '@/components/SearchableSelect.vue'

const props = defineProps({
    items: Array,
    tailors: Object,
})

const emit = defineEmits(['update-item', 'calculate-stock', 'calculate-commission'])

const toast = useToast()

const selectedItemForView = ref(null)
const showViewModal = ref(false)

const viewMeasurements = (item) => {
    selectedItemForView.value = item
    showViewModal.value = true
}

const closeViewModal = () => {
    showViewModal.value = false
    selectedItemForView.value = null
}

const measurementKeys = [
    'shoulder', 'sleeve', 'chest', 'stomach', 'sl_chest', 'sl_so',
    'neck', 'bottom', 'mar_size', 'cuff_size', 'collar_size',
    'regal_size', 'knee_loose', 'fp_size'
];

const hasMeasurements = (item) => true

const getMeasurementDetails = (item) => {
    const details = {};
    measurementKeys.forEach(key => {
        const label = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        details[label] = item[key] || '-';
    });
    return details;
}

const allSelected = computed(() => {
    return props.items.length > 0 && props.items.every(item => item.is_selected_for_completion)
})

const handleSelectAll = (event) => {
    const isChecked = event.target.checked
    const today = new Date().toISOString().split('T')[0]
    props.items.forEach(item => {
        item.is_selected_for_completion = isChecked
        if (isChecked && !item.item_completion_date) {
            item.item_completion_date = today
        }
    })
}

const toggleItemCompletion = (item, event) => {
    const isChecked = event.target.checked
    item.is_selected_for_completion = isChecked
    if (isChecked && !item.item_completion_date) {
        item.item_completion_date = new Date().toISOString().split('T')[0]
    }
}

const refreshStock = async (item) => {
    if (!item.product_id) {
        toast.warning('No product linked to this item')
        return
    }

    try {
        const response = await axios.get(`/tailoring/products/${item.product_id}/stock`)
        if (response.data.success) {
            if (!item.product) item.product = {}
            item.product.stock_quantity = response.data.data.stock_quantity
            calculateStockBalance(item)
            toast.success('Stock updated')
        }
    } catch (error) {
        toast.error('Failed to fetch stock')
    }
}

const calculateStockBalance = (item) => {
    const stockQuantity = parseFloat(item.product?.stock_quantity || 0)
    const usedQuantity = parseFloat(item.used_quantity || 0)
    const wastage = parseFloat(item.wastage || 0)

    item.total_quantity_used = usedQuantity + wastage
    item.stock_balance = stockQuantity - item.total_quantity_used
}

const calculateCommission = (item) => {
    item.tailor_total_commission = (parseFloat(item.tailor_commission || 0)) * (parseFloat(item.quantity || 0))
}

const saveItem = (item) => {
    // Final calculations before saving
    calculateStockBalance(item)
    calculateCommission(item)

    emit('update-item', item.id, {
        ...item,
        // Ensure completion status is sent if it was just changed via checkbox
        is_selected_for_completion: item.is_selected_for_completion
    })
}


const formatCurrency = (value) => {
    return parseFloat(value || 0).toFixed(2)
}
</script>
