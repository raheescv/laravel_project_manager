<template>
    <div class="selected-items-table">
        <div v-if="!items || items.length === 0" class="card mb-3">
            <div class="card-body text-center text-muted py-5">
                <i class="fa fa-inbox fa-3x mb-3"></i>
                <p class="mb-0">No items selected. Search and select products to adjust stock.</p>
            </div>
        </div>

        <div v-else class="card mb-3">
            <div class="card-header bg-primary">
                <h5 class="mb-0 text-white">
                    <i class="fa fa-list me-2"></i>
                    Selected Items ({{ items . length }})
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="px-3 pt-2 pb-1 text-muted small">
                    You can edit either <strong>Adjustment Qty</strong> or <strong>Adjusted Qty (Final)</strong>. Both fields stay synchronized.
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="border-0">#</th>
                                <th class="border-0">Product Code</th>
                                <th class="border-0">Product Name</th>
                                <th class="border-0">Barcode</th>
                                <th class="border-0 text-end">Current Stock</th>
                                <th class="border-0 text-end">Adjustment Qty (+/-)</th>
                                <th class="border-0 text-end">Adjusted Qty (Final)</th>
                                <th class="border-0 text-end">MRP</th>
                                <th class="border-0 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, index) in items" :key="item.inventory_id">
                                <td>
                                    <code class="text-primary">{{ index + 1 }}</code>
                                </td>
                                <td>
                                    <code class="text-primary">{{ item . code }}</code>
                                </td>
                                <td>
                                    <a :href="getProductViewUrl(item)" class="text-decoration-none fw-semibold link-primary" target="_blank" rel="noopener noreferrer">
                                        {{ item . name }}
                                    </a>
                                </td>
                                <td>
                                    <code class="text-muted">{{ item . barcode || '-' }}</code>
                                </td>
                                <td class="text-end">
                                    <span :class="`badge ${(item.current_quantity || 0) > 0 ? 'bg-success' : 'bg-secondary'}`">
                                        {{ item . current_quantity || 0 }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <input type="number" :ref="el => setQuantityInputRef(el, item.inventory_id)" :value="item.adjustment_quantity ?? 0" @input="handleAdjustmentChange(item, $event)" step="0.001" placeholder="0"
                                        style="border: none; background: transparent; width: 100%; text-align: right; outline: none; color: blue;" />
                                </td>
                                <td class="text-end">
                                    <input type="number" :value="item.adjusted_quantity ?? 0" @input="handleAdjustedQuantityChange(item, $event)" step="0.001" placeholder="0"
                                        class="text-primary"
                                        style="border: none; background: transparent; width: 100%; text-align: right; outline: none; font-weight: 600;" />
                                </td>
                                <td class="text-end">
                                    <code class="text-success">{{ item . mrp || '0.00' }}</code>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-danger" @click="handleRemove(item)" title="Remove item">
                                        <i class="fa fa-times"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, nextTick } from 'vue'

    const props = defineProps({
        items: {
            type: Array,
            default: () => []
        }
    })

    const emit = defineEmits(['remove', 'update-adjustment', 'update-adjusted-quantity'])

    const quantityInputRefs = ref({})
    const previousItemsLength = ref(0)

    const setQuantityInputRef = (el, inventoryId) => {
        if (el) {
            quantityInputRefs.value[inventoryId] = el
        }
    }

    const handleAdjustmentChange = (item, event) => {
        const adjustmentQuantity = parseFloat(event.target.value)
        emit('update-adjustment', item.inventory_id, Number.isNaN(adjustmentQuantity) ? 0 : adjustmentQuantity)
    }

    const handleAdjustedQuantityChange = (item, event) => {
        const adjustedQuantity = parseFloat(event.target.value)
        emit('update-adjusted-quantity', item.inventory_id, Number.isNaN(adjustedQuantity) ? 0 : adjustedQuantity)
    }

    const handleRemove = (item) => {
        emit('remove', item.inventory_id)
    }

    const getProductViewUrl = (item) => {
        return `/inventory/product/view/${item.product_id}`
    }

    // Watch for new items and focus on the quantity input
    watch(() => props.items.length, (newLength, oldLength) => {
        if (newLength > oldLength && props.items.length > 0) {
            // A new item was added, focus on the last item's quantity input
            nextTick(() => {
                const lastItem = props.items[props.items.length - 1]
                if (lastItem && quantityInputRefs.value[lastItem.inventory_id]) {
                    const input = quantityInputRefs.value[lastItem.inventory_id]
                    input.focus()
                    input.select()
                }
            })
        }
        previousItemsLength.value = newLength
    }, { immediate: true })
</script>

<style scoped>
    /* Component-specific styles if needed */
</style>
