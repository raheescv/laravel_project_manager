<template>
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <table class="w-full">
            <thead>
                <tr class="bg-black text-white">
                    <th class="px-4 py-3 text-left text-sm font-semibold">Type</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold">Qty</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold">Rate</th>
                    <th class="px-4 py-3 text-right text-sm font-semibold">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="item in items" :key="item.id" class="border-b border-gray-200">
                    <td class="px-4 py-3 text-sm">{{ item.category?.name || 'N/A' }}</td>
                    <td class="px-4 py-3 text-sm">{{ item.quantity }}</td>
                    <td class="px-4 py-3 text-sm">{{ formatCurrency(item.unit_price) }}</td>
                    <td class="px-4 py-3 text-sm text-right font-medium">{{ formatCurrency(item.total) }}</td>
                </tr>
                <tr class="bg-gray-50">
                    <td colspan="3" class="px-4 py-3 text-right font-semibold text-gray-700">Sub Total</td>
                    <td class="px-4 py-3 text-right font-semibold text-gray-700">{{ formatCurrency(subTotal) }}</td>
                </tr>
                <tr class="bg-blue-50">
                    <td colspan="3" class="px-4 py-3 text-right font-bold text-lg text-blue-600">Total</td>
                    <td class="px-4 py-3 text-right font-bold text-lg text-blue-600">{{ formatCurrency(subTotal) }}</td>
                </tr>
            </tbody>
        </table>
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

const subTotal = computed(() => {
    return props.items.reduce((sum, item) => sum + (parseFloat(item.total) || 0), 0)
})

const formatCurrency = (value) => {
    return parseFloat(value || 0).toFixed(2)
}
</script>
