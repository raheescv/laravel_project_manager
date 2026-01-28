<template>
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0 fw-bold text-dark">Work Orders Preview</h5>
            <div class="text-muted small fw-medium">
                {{ items.length }} {{ items.length === 1 ? 'item' : 'items' }} total
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0 align-middle">
                <thead class="table-dark">
                    <tr>
                        <th class="px-3 py-3">No</th>
                        <th class="px-3 py-3">Category</th>
                        <th class="px-3 py-3">Model</th>
                        <th class="px-3 py-3">Product</th>
                        <th class="px-3 py-3">Qty</th>
                        <th class="px-3 py-3">Colour</th>
                        <th class="px-3 py-3 text-end">Rate</th>
                        <th class="px-3 py-3 text-end">Stitch Rate</th>
                        <th class="px-3 py-3 text-end">Amount</th>
                        <th class="px-3 py-3 text-center" style="width: 100px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(item, index) in items" :key="item.id || item._temp_id || index">
                        <td class="px-3">{{ item.item_no }}</td>
                        <td class="px-3 fw-bold text-primary">
                            {{ item.category?.name || 'Unknown' }}
                        </td>
                        <td class="px-3">
                            <span class="badge bg-light text-dark border">
                                {{ item.category_model?.name || item.tailoring_category_model_name || '-' }}
                            </span>
                        </td>
                        <td class="px-3">{{ item.product_name }}</td>
                        <td class="px-3">{{ item.quantity }}</td>
                        <td class="px-3">{{ item.product_color || '-' }}</td>
                        <td class="px-3 text-end">{{ formatCurrency(item.unit_price) }}</td>
                        <td class="px-3 text-end">{{ formatCurrency(item.stitch_rate) }}</td>
                        <td class="px-3 text-end fw-bold text-dark">{{ formatCurrency(item.total) }}</td>
                        <td class="px-3 text-center">
                            <div class="btn-group btn-group-sm">
                                <button type="button" @click="viewMeasurements(item)"
                                    class="btn btn-link text-warning p-1"
                                    title="View Measurements">
                                    <i class="fa fa-eye"></i>
                                </button>
                                <button type="button" @click="$emit('edit', item)"
                                    class="btn btn-link text-primary p-1" title="Edit">
                                    <i class="fa fa-pencil"></i>
                                </button>
                                <button type="button" @click="$emit('remove', item)"
                                    class="btn btn-link text-danger p-1" title="Remove">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="items.length === 0">
                        <td colspan="10" class="text-center py-5 text-muted bg-light italic">
                            No items added to the order yet.
                        </td>
                    </tr>
                </tbody>
                <tfoot v-if="items.length > 0" class="table-light">
                    <tr>
                        <td colspan="8" class="text-end fw-bold py-3">Grand Total:</td>
                        <td class="text-end fw-bold text-primary fs-5 py-3">{{ formatCurrency(grandTotal) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
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

const grandTotal = computed(() => {
    return props.items.reduce((sum, item) => sum + parseFloat(item.total || 0), 0)
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


