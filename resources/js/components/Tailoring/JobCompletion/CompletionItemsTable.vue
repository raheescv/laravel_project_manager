<template>
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="px-3 py-2 text-left font-semibold text-gray-700">No Type</th>
                        <th class="px-3 py-2 text-left font-semibold text-gray-700">Item</th>
                        <th class="px-3 py-2 text-left font-semibold text-gray-700">Item Qty</th>
                        <th class="px-3 py-2 text-left font-semibold text-gray-700">Model</th>
                        <th class="px-3 py-2 text-left font-semibold text-gray-700">Length</th>
                        <th class="px-3 py-2 text-left font-semibold text-gray-700">Amount</th>
                        <th class="px-3 py-2 text-left font-semibold text-gray-700">Tailor Com.</th>
                        <th class="px-3 py-2 text-left font-semibold text-gray-700">Tailor Total Com.</th>
                        <th class="px-3 py-2 text-left font-semibold text-gray-700">Tailor</th>
                        <th class="px-3 py-2 text-left font-semibold text-gray-700">Stock</th>
                        <th class="px-3 py-2 text-left font-semibold text-gray-700">Used Qty</th>
                        <th class="px-3 py-2 text-left font-semibold text-gray-700">Wastage</th>
                        <th class="px-3 py-2 text-left font-semibold text-gray-700">Tot Qty Used</th>
                        <th class="px-3 py-2 text-left font-semibold text-gray-700">Stock Balance</th>
                        <th class="px-3 py-2 text-left font-semibold text-gray-700">Date</th>
                        <th class="px-3 py-2 text-center font-semibold text-gray-700">
                            <input 
                                type="checkbox"
                                @change="handleSelectAll"
                                :checked="allSelected"
                                class="w-4 h-4"
                            />
                            <div class="text-xs mt-1">Sel. All</div>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr 
                        v-for="item in items" 
                        :key="item.id"
                        class="border-b border-gray-200 hover:bg-gray-50"
                    >
                        <td class="px-3 py-2">{{ item.item_no }} {{ item.category?.name || '' }}</td>
                        <td class="px-3 py-2">{{ item.product_name }}</td>
                        <td class="px-3 py-2">
                            <input 
                                :value="item.quantity"
                                type="text"
                                readonly
                                class="w-16 px-2 py-1 border border-gray-300 rounded bg-gray-50 text-center text-xs"
                            />
                        </td>
                        <td class="px-3 py-2">
                            <input 
                                :value="item.categoryModel?.name || ''"
                                type="text"
                                readonly
                                class="w-20 px-2 py-1 border border-gray-300 rounded bg-gray-50 text-center text-xs"
                            />
                        </td>
                        <td class="px-3 py-2">
                            <input 
                                :value="item.length || ''"
                                type="text"
                                readonly
                                class="w-16 px-2 py-1 border border-gray-300 rounded bg-gray-50 text-center text-xs"
                            />
                        </td>
                        <td class="px-3 py-2">
                            <input 
                                :value="formatCurrency(item.amount)"
                                type="text"
                                readonly
                                class="w-20 px-2 py-1 border border-gray-300 rounded bg-gray-50 text-right text-xs"
                            />
                        </td>
                        <td class="px-3 py-2">
                            <input 
                                v-model.number="item.tailor_commission"
                                @input="calculateCommission(item)"
                                type="number"
                                step="0.01"
                                min="0"
                                class="w-20 px-2 py-1 border border-gray-300 rounded text-center text-xs"
                            />
                        </td>
                        <td class="px-3 py-2">
                            <input 
                                :value="formatCurrency(item.tailor_total_commission)"
                                type="text"
                                readonly
                                class="w-24 px-2 py-1 border border-gray-300 rounded bg-gray-50 text-right text-xs"
                            />
                        </td>
                        <td class="px-3 py-2">
                            <select 
                                v-model="item.tailor_id"
                                @change="updateItem(item)"
                                class="w-24 px-2 py-1 border border-gray-300 rounded text-xs"
                            >
                                <option value="">Select tailor...</option>
                                <option v-for="(name, id) in tailors" :key="id" :value="id">{{ name }}</option>
                            </select>
                        </td>
                        <td class="px-3 py-2">
                            <input 
                                :value="formatCurrency(item.stock_quantity || 0)"
                                type="text"
                                readonly
                                class="w-20 px-2 py-1 border border-gray-300 rounded bg-gray-50 text-right text-xs"
                            />
                        </td>
                        <td class="px-3 py-2">
                            <input 
                                v-model.number="item.used_quantity"
                                @input="calculateStockBalance(item)"
                                type="number"
                                step="0.001"
                                min="0"
                                class="w-20 px-2 py-1 border border-gray-300 rounded text-center text-xs"
                            />
                        </td>
                        <td class="px-3 py-2">
                            <input 
                                v-model.number="item.wastage"
                                @input="calculateStockBalance(item)"
                                type="number"
                                step="0.001"
                                min="0"
                                class="w-20 px-2 py-1 border border-gray-300 rounded text-center text-xs"
                            />
                        </td>
                        <td class="px-3 py-2">
                            <input 
                                :value="formatCurrency(item.total_quantity_used || 0)"
                                type="text"
                                readonly
                                class="w-20 px-2 py-1 border border-gray-300 rounded bg-gray-50 text-right text-xs"
                            />
                        </td>
                        <td class="px-3 py-2">
                            <input 
                                :value="formatCurrency(item.stock_balance || 0)"
                                type="text"
                                readonly
                                class="w-20 px-2 py-1 border border-gray-300 rounded bg-gray-50 text-right text-xs"
                            />
                        </td>
                        <td class="px-3 py-2">
                            <input 
                                v-model="item.item_completion_date"
                                @change="updateItem(item)"
                                type="date"
                                class="w-32 px-2 py-1 border border-gray-300 rounded text-xs"
                            />
                        </td>
                        <td class="px-3 py-2 text-center">
                            <input 
                                v-model="item.is_selected_for_completion"
                                type="checkbox"
                                class="w-4 h-4"
                            />
                        </td>
                    </tr>
                    <tr v-if="items.length === 0">
                        <td colspan="16" class="px-4 py-8 text-center text-gray-500">
                            No items found
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import axios from 'axios'
import { useToast } from 'vue-toastification'

const props = defineProps({
    items: Array,
    tailors: Object,
})

const emit = defineEmits(['update-item', 'calculate-stock', 'calculate-commission'])

const toast = useToast()

const allSelected = computed(() => {
    return props.items.length > 0 && props.items.every(item => item.is_selected_for_completion)
})

const handleSelectAll = (event) => {
    props.items.forEach(item => {
        item.is_selected_for_completion = event.target.checked
    })
}

const calculateStockBalance = async (item) => {
    // This would fetch live stock from inventory
    const stockQuantity = item.stock_quantity || 0
    item.total_quantity_used = (item.used_quantity || 0) + (item.wastage || 0)
    item.stock_balance = stockQuantity - item.total_quantity_used
    
    emit('update-item', item.id, item)
}

const calculateCommission = (item) => {
    item.tailor_total_commission = (item.tailor_commission || 0) * (item.quantity || 0)
    emit('update-item', item.id, item)
}

const updateItem = (item) => {
    emit('update-item', item.id, item)
}

const formatCurrency = (value) => {
    return parseFloat(value || 0).toFixed(2)
}
</script>
