<template>
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <h2 class="text-lg font-semibold text-gray-800 p-4 border-b border-gray-200">Work Orders Preview</h2>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-black text-white">
                        <th class="px-4 py-3 text-left text-sm font-semibold">No Type</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold">Item</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold">Qty</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold">Colour</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold">Rate</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold">Stitch Rate</th>
                        <th class="px-4 py-3 text-right text-sm font-semibold">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr 
                        v-for="(item, index) in items" 
                        :key="item.id || index"
                        class="border-b border-gray-200 hover:bg-gray-50"
                    >
                        <td class="px-4 py-3 text-sm">{{ item.item_no }} {{ item.category?.name || '' }}</td>
                        <td class="px-4 py-3 text-sm">{{ item.product_name }}</td>
                        <td class="px-4 py-3 text-sm">{{ item.quantity }}</td>
                        <td class="px-4 py-3 text-sm">{{ item.product_color || '-' }}</td>
                        <td class="px-4 py-3 text-sm">{{ formatCurrency(item.unit_price) }}</td>
                        <td class="px-4 py-3 text-sm">{{ formatCurrency(item.stitch_rate) }}</td>
                        <td class="px-4 py-3 text-sm text-right font-medium">{{ formatCurrency(item.total) }}</td>
                    </tr>
                    <tr v-if="items.length === 0">
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                            No items added yet
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    items: {
        type: Array,
        default: () => []
    }
})

const formatCurrency = (value) => {
    return parseFloat(value || 0).toFixed(2)
}
</script>
