<template>
    <div v-if="show"
        class="fixed inset-0 bg-gradient-to-br from-sky-900/30 via-teal-900/30 to-emerald-900/40 backdrop-blur-sm flex items-center justify-center z-50 p-2 sm:p-4">
        <div
            class="bg-white rounded-2xl shadow-2xl max-w-7xl w-full max-h-[98vh] overflow-hidden cart-modal-container border border-teal-100">
            <!-- Compact Modal Header -->
            <div
                class="bg-gradient-to-r from-sky-500 via-teal-500 to-emerald-500 text-white p-3 sm:p-4 flex justify-between items-center shadow-lg">
                <div class="flex items-center space-x-2 sm:space-x-3">
                    <div class="bg-white/20 p-1.5 sm:p-2 rounded-full shadow-inner">
                        <i class="fa fa-shopping-cart text-lg sm:text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-white text-lg sm:text-xl font-bold">Cart Items</h3>
                        <p class="text-teal-100 text-xs sm:text-sm hidden sm:block">Manage your selected items</p>
                    </div>
                </div>
                <button @click="$emit('close')"
                    class="text-white/80 hover:text-white hover:bg-white/20 p-1.5 sm:p-2 rounded-full transition-all duration-200">
                    <i class="fa fa-times text-lg sm:text-xl"></i>
                </button>
            </div>

            <!-- Modal Content -->
            <div class="p-6 overflow-y-auto max-h-[75vh] custom-scrollbar">
                <!-- Empty State -->
                <div v-if="Object.keys(cartItemsByEmployee).length === 0" class="text-center py-16">
                    <div
                        class="bg-gradient-to-br from-teal-50 to-emerald-100 w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-6 shadow-inner border border-teal-100">
                        <i class="fa fa-shopping-cart text-3xl text-teal-400"></i>
                    </div>
                    <h4 class="text-xl font-semibold text-emerald-700 mb-2">Your cart is empty</h4>
                    <p class="text-teal-500">Add some items to get started</p>
                </div>

                <!-- Cart Items by Employee -->
                <div v-else class="space-y-4">
                    <div v-for="(items, employeeName) in cartItemsByEmployee" :key="employeeName"
                        class="bg-white rounded-lg border border-teal-100 overflow-hidden shadow-sm">

                        <!-- Compact Employee Header -->
                        <div
                            class="employee-header bg-gradient-to-r from-sky-500 via-teal-500 to-emerald-500 text-white px-3 py-2 flex items-center justify-between text-sm shadow-sm">
                            <div class="flex items-center space-x-2">
                                <div class="bg-white/20 p-1 rounded-md shadow-inner">
                                    <i class="fa fa-user text-xs"></i>
                                </div>
                                <span class="font-semibold">{{ employeeName }}</span>
                            </div>
                            <div
                                class="bg-teal-400/30 backdrop-blur-sm px-2 py-0.5 rounded-md text-xs border border-teal-400/20 shadow-sm">
                                {{ items.length }} item{{ items.length > 1 ? 's' : '' }}
                            </div>
                        </div>

                        <!-- Tabular Items List -->
                        <div class="overflow-x-auto">
                            <table class="w-full cart-table">
                                <thead class="bg-teal-50/70 border-b-2 border-teal-100">
                                    <tr>
                                        <th class="text-left py-2 px-3 font-semibold text-teal-700 text-xs">Item </th>
                                        <th class="text-left py-2 px-3 font-semibold text-teal-700 text-xs">Barcode </th>
                                        <th class="text-right py-2 px-3 font-semibold text-teal-700 text-xs">Price </th>
                                        <th class="text-right py-2 px-3 font-semibold text-teal-700 text-xs">Qty </th>
                                        <th class="text-right py-2 px-3 font-semibold text-teal-700 text-xs">Discount </th>
                                        <th class="text-right py-2 px-3 font-semibold text-teal-700 text-xs">Tax % </th>
                                        <th class="text-right py-2 px-3 font-semibold text-teal-700 text-xs">Total </th>
                                        <th class="text-right py-2 px-3 font-semibold text-teal-700 text-xs">Action </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in items" :key="item.key"
                                        class="border-b border-teal-50 hover:bg-teal-50/30 transition-colors">
                                        <!-- Item Name & Code -->
                                        <td class="py-2 px-3">
                                            <div class="flex items-center space-x-2">
                                                <div
                                                    class="bg-gradient-to-br from-sky-500 to-teal-600 w-6 h-6 rounded-md flex items-center justify-center text-white text-xs font-bold flex-shrink-0 shadow-sm">
                                                    {{ item.name.charAt(0).toUpperCase() }}
                                                </div>
                                                <div class="min-w-0">
                                                    <div class="font-medium text-teal-800 text-xs truncate">
                                                        {{ item.name }}
                                                    </div>
                                                    <div v-if="item.product_code" class="text-xs text-teal-500">
                                                        {{ item.product_code }}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-2 px-3">
                                            <div class="flex items-center space-x-2">

                                                <div class="min-w-0">
                                                    <div class="font-medium text-teal-800 text-xs truncate">
                                                        {{ item.barcode }}
                                                    </div>

                                                </div>
                                            </div>
                                        </td>


                                        <!-- Price -->
                                        <td class="py-2 px-3 text-right">
                                            <input :value="item.unit_price"
                                                @input="updateItemField(item.key, 'unit_price', $event.target.value)"
                                                @change="$emit('update-item-quantity', item.key)" type="number" step="1"
                                                min="0" :disabled="!canEditItemPrice" :class="[
                                                    'w-full px-2 py-1 text-xs border rounded-md text-right transition-colors',
                                                    canEditItemPrice
                                                        ? 'border-teal-200 focus:ring-1 focus:ring-teal-500 focus:border-teal-500 bg-white text-teal-800'
                                                        : 'border-teal-100 bg-slate-100 text-slate-500 cursor-not-allowed'
                                                ]">
                                        </td>

                                        <!-- Quantity -->
                                        <td class="py-2 px-3 text-right">
                                            <input :value="item.quantity"
                                                @input="updateItemField(item.key, 'quantity', $event.target.value)"
                                                @change="$emit('update-item-quantity', item.key)" type="number"
                                                min="0.001"
                                                class="w-full px-2 py-1 text-xs border border-sky-200 rounded-md focus:ring-1 focus:ring-sky-500 focus:border-sky-500 text-right bg-white font-semibold text-sky-800 transition-colors">
                                        </td>

                                        <!-- Discount -->
                                        <td class="py-2 px-3 text-right">
                                            <input :value="item.discount"
                                                @input="updateItemField(item.key, 'discount', $event.target.value)"
                                                @change="$emit('update-item-quantity', item.key)" type="number" step="1"
                                                min="0"
                                                class="w-full px-2 py-1 text-xs border border-amber-200 rounded-md focus:ring-1 focus:ring-amber-500 focus:border-amber-500 text-right bg-white text-amber-700 transition-colors"
                                                placeholder="0">
                                        </td>

                                        <!-- Tax -->
                                        <td class="py-2 px-3 text-right">
                                            <input :value="item.tax"
                                                @input="updateItemField(item.key, 'tax', $event.target.value)"
                                                @change="$emit('update-item-quantity', item.key)" type="number"
                                                step="0.01" min="0"
                                                class="w-full px-2 py-1 text-xs border border-emerald-200 rounded-md focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 text-right bg-white text-emerald-700 transition-colors"
                                                placeholder="0">
                                        </td>

                                        <!-- Total -->
                                        <td class="py-2 px-3 text-right">
                                            <div
                                                class="bg-gradient-to-r from-teal-50 to-emerald-100 text-teal-700 font-bold text-xs px-2.5 py-1 rounded-md border border-emerald-200 inline-block shadow-sm">
                                                {{ formatNumber(item.total, 3) }}
                                            </div>
                                        </td>

                                        <!-- Actions -->
                                        <td class="py-2 px-3 text-right">
                                            <button @click="$emit('remove-cart-item', item.key)"
                                                class="p-1.5 bg-orange-50 text-orange-500 rounded-md hover:bg-orange-500 hover:text-white transition-all duration-150 border border-orange-100"
                                                title="Remove item">
                                                <i class="fa fa-trash text-xs"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div
                class="bg-gradient-to-r from-teal-50 via-sky-50 to-emerald-50 border-t border-teal-100 px-4 py-3 flex flex-col sm:flex-row justify-between items-center space-y-3 sm:space-y-0">
                <!-- Summary Stats -->
                <div class="flex items-center space-x-4">
                    <div class="modal-footer-stats bg-white px-3 py-1.5 rounded-lg shadow-sm border border-sky-100">
                        <div class="text-xs text-sky-600 uppercase tracking-wide">Items</div>
                        <div class="text-sm font-bold text-teal-700">{{ totalItems }}</div>
                    </div>
                    <div class="modal-footer-stats bg-white px-3 py-1.5 rounded-lg shadow-sm border border-emerald-100">
                        <div class="text-xs text-emerald-600 uppercase tracking-wide">Quantity</div>
                        <div class="text-sm font-bold text-teal-700">{{ formatNumber(totalQuantity, 3) }}</div>
                    </div>
                </div>

                <!-- Action Button -->
                <button @click="$emit('close')"
                    class="bg-gradient-to-r from-teal-500 to-emerald-500 text-white px-6 py-2 rounded-xl hover:from-teal-600 hover:to-emerald-600 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:scale-105 font-semibold text-sm">
                    <i class="fa fa-check mr-2"></i>Done
                </button>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'CartItemsModal',
    props: {
        show: {
            type: Boolean,
            default: false
        },
        cartItemsByEmployee: {
            type: Object,
            default: () => ({})
        },
        totalItems: {
            type: Number,
            default: 0
        },
        totalQuantity: {
            type: Number,
            default: 0
        },
        canEditItemPrice: {
            type: Boolean,
            default: false
        }
    },
    emits: ['close', 'update-item-quantity', 'remove-cart-item', 'update-item-field'],
    methods: {
        formatNumber(value, decimals = 2) {
            const num = parseFloat(value) || 0;
            return num.toLocaleString('en-US', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals
            });
        },
        updateItemField(itemKey, field, value) {
            // Convert to number for numeric fields
            const numericFields = ['unit_price', 'quantity', 'discount', 'tax'];
            let processedValue = numericFields.includes(field) ? parseFloat(value) || 0 : value;

            // Ensure quantity is at least 1
            if (field === 'quantity' && processedValue < 1) {
                // processedValue = 1;
            }

            // Ensure non-negative values for price, discount, and tax
            if (['unit_price', 'discount', 'tax'].includes(field) && processedValue < 0) {
                processedValue = 0;
            }

            // Emit the field update to parent
            this.$emit('update-item-field', {
                key: itemKey,
                field,
                value: processedValue
            });
        }
    }
}
</script>

<style scoped>
@import '../../css/pos-common.css';
@import '../../css/cart-modal.css';
</style>
