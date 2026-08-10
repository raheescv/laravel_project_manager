<template>
    <div v-if="show" class="posx-modal-backdrop" role="dialog" aria-modal="true" @click.self="$emit('close')">
        <div class="posx-modal" style="max-width: 80rem" @click.stop>

            <div class="posx-modal-head">
                <h4 class="posx-modal-title">
                    <i class="fa fa-shopping-cart"></i>
                    <span>
                        Cart Items
                        <span class="posx-modal-sub">Manage your selected items</span>
                    </span>
                </h4>
                <button type="button" class="posx-modal-close" @click="$emit('close')" aria-label="Close">
                    <i class="fa fa-times"></i>
                </button>
            </div>

            <div class="posx-modal-body custom-scrollbar">
                <!-- Empty -->
                <div v-if="Object.keys(cartItemsByEmployee).length === 0" class="text-center py-14">
                    <div class="posx-empty-ring mx-auto mb-3"><i class="fa fa-shopping-cart"></i></div>
                    <h4 class="posx-ink text-sm font-extrabold mb-1">Your cart is empty</h4>
                    <p class="posx-muted text-xs font-semibold">Add some items to get started</p>
                </div>

                <!-- Grouped by employee -->
                <div v-else class="space-y-3">
                    <div v-for="(items, employeeName) in cartItemsByEmployee" :key="employeeName"
                        class="posx-panel posx-panel-flush">

                        <div class="posx-group-head">
                            <span class="posx-group-name">
                                <span class="posx-avatar">{{ employeeName.charAt(0).toUpperCase() }}</span>
                                {{ employeeName }}
                            </span>
                            <span class="posx-chip-neutral">
                                {{ items.length }} item{{ items.length > 1 ? 's' : '' }}
                            </span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="posx-table">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Unit</th>
                                        <th>Barcode</th>
                                        <th class="num">Price</th>
                                        <th class="num">Qty</th>
                                        <th class="num">Discount</th>
                                        <th class="num">Tax %</th>
                                        <th class="num">Total</th>
                                        <th class="num">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in items" :key="item.key">
                                        <!-- Item -->
                                        <td>
                                            <div class="flex items-center gap-2">
                                                <span class="posx-avatar" style="width:24px;height:24px;border-radius:7px;font-size:.625rem;font-weight:800">
                                                    {{ item.name.charAt(0).toUpperCase() }}
                                                </span>
                                                <div class="min-w-0">
                                                    <div class="posx-ink text-xs font-bold truncate">{{ item.name }}</div>
                                                    <div v-if="item.product_code" class="posx-muted text-xs">{{ item.product_code }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="posx-chip-neutral">{{ item.unit_name }}</span></td>
                                        <td class="posx-muted text-xs">{{ item.barcode }}</td>

                                        <!-- Price -->
                                        <td class="num">
                                            <div v-if="item.combo_offer_price && item.combo_offer_price > 0"
                                                class="flex flex-col items-end gap-1">
                                                <span class="posx-muted text-xs line-through">{{ formatNumber(item.unit_price) }}</span>
                                                <div class="flex items-center gap-1">
                                                    <input :value="item.combo_offer_price"
                                                        @input="updateItemField(item.key, 'combo_offer_price', $event.target.value)"
                                                        @change="$emit('update-item-quantity', item.key)" type="number"
                                                        step="0.01" min="0" :disabled="!canEditItemPrice"
                                                        class="posx-field posx-field-sm text-end">
                                                    <span class="posx-chip-ok">Combo</span>
                                                </div>
                                            </div>
                                            <input v-else :value="item.unit_price"
                                                @input="updateItemField(item.key, 'unit_price', $event.target.value)"
                                                @change="$emit('update-item-quantity', item.key)" type="number" step="1"
                                                min="0" :disabled="!canEditItemPrice"
                                                class="posx-field posx-field-sm text-end">
                                        </td>

                                        <!-- Qty -->
                                        <td class="num">
                                            <input :value="item.quantity"
                                                @input="updateItemField(item.key, 'quantity', $event.target.value)"
                                                @change="$emit('update-item-quantity', item.key)" type="number"
                                                min="0.001" class="posx-field posx-field-sm text-end">
                                        </td>

                                        <!-- Discount -->
                                        <td class="num">
                                            <input :value="item.discount"
                                                @input="updateItemField(item.key, 'discount', $event.target.value)"
                                                @change="$emit('update-item-quantity', item.key)" type="number" step="1"
                                                min="0" placeholder="0" class="posx-field posx-field-sm text-end">
                                        </td>

                                        <!-- Tax -->
                                        <td class="num">
                                            <input :value="item.tax"
                                                @input="updateItemField(item.key, 'tax', $event.target.value)"
                                                @change="$emit('update-item-quantity', item.key)" type="number"
                                                step="0.01" min="0" placeholder="0"
                                                class="posx-field posx-field-sm text-end">
                                        </td>

                                        <!-- Total -->
                                        <td class="num"><span class="posx-amount">{{ formatNumber(item.total, 3) }}</span></td>

                                        <!-- Action -->
                                        <td class="num">
                                            <button type="button" class="posx-icon-btn is-danger ms-auto"
                                                @click="$emit('remove-cart-item', item.key)" title="Remove item">
                                                <i class="fa fa-trash-o"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="posx-modal-foot justify-between">
                <div class="flex items-center gap-4">
                    <div>
                        <div class="posx-section-title mb-0">Items</div>
                        <div class="posx-amount text-sm">{{ totalItems }}</div>
                    </div>
                    <div>
                        <div class="posx-section-title mb-0">Quantity</div>
                        <div class="posx-amount text-sm">{{ formatNumber(totalQuantity, 3) }}</div>
                    </div>
                </div>
                <button type="button" class="posx-btn posx-btn-primary" @click="$emit('close')">
                    <i class="fa fa-check"></i> Done
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
</style>
