<template>
    <div class="selected-items-table">
        <div v-if="!items || items.length === 0" class="card mb-3">
            <div class="card-body text-center text-muted py-5">
                <i class="fa fa-inbox fa-3x mb-3"></i>
                <p class="mb-0">No items selected. Search and select products to set opening balance.</p>
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
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="border-0">#</th>
                                <th class="border-0">Product Code</th>
                                <th class="border-0">Product Name</th>
                                <th class="border-0">Barcode</th>
                                <th class="border-0 text-end">Current Qty</th>
                                <th class="border-0 text-end">Opening Qty</th>
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
                                <td>{{ item . name }}</td>
                                <td>
                                    <code class="text-muted">{{ item . barcode || '-' }}</code>
                                </td>
                                <td class="text-end">
                                    <span :class="`badge ${(item.current_quantity || 0) > 0 ? 'bg-success' : 'bg-secondary'}`">
                                        {{ item . current_quantity || 0 }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <input type="number" :ref="el => setQuantityInputRef(el, item.inventory_id)" :value="item.opening_quantity || ''" @input="handleQuantityChange(item, $event)" min="0" step="0.001" placeholder="0"
                                        style="border: none; background: transparent; width: 100%; text-align: right; outline: none; color: blue;" />
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

    const emit = defineEmits(['remove', 'update-quantity'])

    const quantityInputRefs = ref({})
    const previousItemsLength = ref(0)

    const setQuantityInputRef = (el, inventoryId) => {
        if (el) {
            quantityInputRefs.value[inventoryId] = el
        }
    }

    const handleQuantityChange = (item, event) => {
        const quantity = parseFloat(event.target.value) || 0
        emit('update-quantity', item.inventory_id, quantity)
    }

    const handleRemove = (item) => {
        emit('remove', item.inventory_id)
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
