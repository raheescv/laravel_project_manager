<template>
    <div class="space-y-4">
        <!-- Grouped Items Cards -->
        <div class="grid grid-cols-1 gap-4">
            <div v-for="group in groupedItems" :key="group.categoryId" 
                class="bg-white rounded-lg shadow-sm p-4 border border-gray-100 hover:shadow-md transition-shadow">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="font-bold text-gray-800 text-lg">{{ group.categoryName }}</h3>
                        <p class="text-sm text-gray-500 mt-1">
                            <span class="font-medium text-gray-700">{{ group.quantity }}</span> {{ group.quantity === 1 ? 'Item' : 'Items' }}
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-lg font-bold text-blue-600">{{ formatCurrency(group.total) }}</p>
                        <p class="text-xs text-gray-400 mt-1">Total Amount</p>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="groupedItems.length === 0" class="text-center py-8 bg-white rounded-lg border border-dashed border-gray-300">
                <p class="text-gray-500">No items added yet</p>
            </div>
        </div>

        <!-- Grand Total -->
        <div v-if="groupedItems.length > 0" class="bg-blue-50 rounded-lg p-4 border border-blue-100">
            <div class="flex justify-between items-center">
                <span class="text-gray-700 font-semibold text-lg">Net Total</span>
                <span class="text-blue-700 font-bold text-2xl">{{ formatCurrency(subTotal) }}</span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    items: {
        type: Array,
        default: () => []
    }
})

const groupedItems = computed(() => {
    const groups = {}
    
    props.items.forEach(item => {
        const catId = item.tailoring_category_id
        if (!groups[catId]) {
            groups[catId] = {
                categoryId: catId,
                categoryName: item.category?.name || 'Unknown Category',
                quantity: 0,
                total: 0
            }
        }
        
        groups[catId].quantity += parseFloat(item.quantity || 0)
        groups[catId].total += parseFloat(item.total || 0)
    })
    
    return Object.values(groups)
})

const subTotal = computed(() => {
    return props.items.reduce((sum, item) => sum + (parseFloat(item.total) || 0), 0)
})

const formatCurrency = (value) => {
    return parseFloat(value || 0).toFixed(2)
}
</script>
