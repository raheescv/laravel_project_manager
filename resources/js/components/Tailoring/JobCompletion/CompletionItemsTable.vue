<template>
    <div class="d-flex flex-column gap-3">
        <!-- Header with Select All -->
        <div class="card shadow-sm border-0">
            <div class="card-body p-3 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-2 bg-primary bg-opacity-10 rounded">
                        <i class="fa fa-tasks text-primary"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold text-dark mb-0">Job Completion Items</h6>
                        <small class="text-muted">{{ items.length }} items total</small>
                    </div>
                </div>

                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="selectAllItems" @change="handleSelectAll"
                        :checked="allSelected">
                    <label class="form-check-label small fw-bold text-muted cursor-pointer" for="selectAllItems">
                        Select All for Completion
                    </label>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="items.length === 0" class="card shadow-sm border-2 border-dashed py-5 text-center text-muted">
            <div class="card-body">
                <i class="fa fa-search fs-1 mb-3 opacity-25"></i>
                <h5 class="fw-bold">No items found</h5>
                <p class="small">Search for an order to see completion items</p>
            </div>
        </div>

        <!-- Table -->
        <div v-else class="card shadow-sm border-0 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle border-top mb-0">
                    <thead class="table-light">
                        <tr class="x-small fw-bold text-uppercase text-muted">
                            <th class="ps-3 py-3" style="width: 50px;">
                                <input class="form-check-input mt-0" type="checkbox" @change="handleSelectAll"
                                    :checked="allSelected">
                            </th>
                            <th class="py-3">Item Details</th>
                            <th class="py-3">Tailor Assignment</th>
                            <th class="py-3">Material Tracking</th>
                            <th class="py-3">Completion Date</th>
                            <th class="py-3 text-center" style="width: 140px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in items" :key="item.id"
                            :class="{ 'table-primary': item.is_selected_for_completion }">
                            <!-- Checkbox Column -->
                            <td class="ps-3">
                                <input class="form-check-input mt-0" type="checkbox"
                                    :checked="item.is_selected_for_completion"
                                    @change="toggleItemCompletion(item, $event)">
                            </td>

                            <!-- Item Details Column -->
                            <td class="py-3">
                                <div class="d-flex flex-column gap-1">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-primary fw-bold">#{{ item.item_no }}</span>
                                        <span class="small fw-bold text-secondary text-uppercase">{{ item.category?.name
                                        }}</span>
                                    </div>
                                    <div class="fw-bold text-dark">{{ item.product_name }}</div>
                                    <div class="row g-2 x-small text-muted">
                                        <div class="col-6">
                                            Qty: <span class="fw-bold text-dark">{{ item.quantity }}</span>
                                        </div>
                                        <div class="col-6">
                                            Amount: <span class="fw-bold text-primary">{{ formatCurrency(item.amount)
                                            }}</span>
                                        </div>
                                        <div class="col-6 text-truncate">
                                            Model: <span class="fw-bold text-dark">{{ item.categoryModel?.name || '-'
                                            }}</span>
                                        </div>
                                        <div class="col-6">
                                            Length: <span class="fw-bold text-dark">{{ item.length || '-' }}</span>
                                        </div>
                                    </div>
                                    <button @click="viewMeasurements(item)"
                                        class="btn btn-link btn-sm text-warning p-0 text-start text-decoration-none x-small fw-bold">
                                        <i class="fa fa-eye me-1"></i> View Measurements
                                    </button>
                                </div>
                            </td>

                            <!-- Tailor Assignment Column -->
                            <td class="py-3">
                                <div class="d-flex flex-column gap-2" style="min-width: 180px; width: 100%;">
                                    <div style="width: 100%">
                                        <label class="form-label x-small fw-bold text-muted text-uppercase mb-1">
                                            Assign Worker
                                        </label>
                                        <div style="width: 100%">
                                            <SearchableSelect :modelValue="item.tailor_id"
                                                @update:modelValue="item.tailor_id = $event" :options="tailors"
                                                placeholder="Select tailor..." class="form-select-sm" style="width: 100%" />
                                        </div>
                                    </div>
                                    <div>
                                        <label
                                            class="form-label x-small fw-bold text-muted text-uppercase mb-1">Rate/Item</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-light border-end-0">₹</span>
                                            <input v-model.number="item.tailor_commission"
                                                @input="calculateCommission(item)" type="number" step="0.01" min="0"
                                                class="form-control border-start-0" />
                                        </div>
                                    </div>
                                    <div class="x-small">
                                        <span class="text-muted">Total:</span>
                                        <span class="fw-bold text-primary ms-1">₹{{
                                            formatCurrency(item.tailor_total_commission) }}</span>
                                    </div>
                                </div>
                            </td>

                            <!-- Material Tracking Column -->
                            <td class="py-3">
                                <div class="d-flex flex-column gap-2" style="min-width: 200px;">
                                    <div class="d-flex align-items-center justify-content-between mb-0">
                                        <span class="x-small fw-bold text-muted text-uppercase">Stock Balance:</span>
                                        <div class="d-flex align-items-center gap-2">
                                            <span :class="item.stock_balance < 0 ? 'text-danger' : 'text-success'"
                                                class="small fw-bold">
                                                {{ (item.stock_balance || 0).toFixed(3) }}
                                            </span>
                                            <button @click="refreshStock(item)"
                                                class="btn btn-link btn-sm p-0 text-muted" title="Refresh Stock">
                                                <i class="fa fa-refresh x-small"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-light border-end-0 x-small fw-bold">IN
                                                STOCK</span>
                                            <input :value="(item.product?.stock_quantity || 0).toFixed(3)" readonly
                                                class="form-control bg-light border-start-0 text-center fw-bold" />
                                        </div>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label
                                                class="form-label x-small fw-bold text-muted text-uppercase mb-1">Used</label>
                                            <input v-model.number="item.used_quantity"
                                                @input="calculateStockBalance(item)" type="number" step="0.001" min="0"
                                                class="form-control form-control-sm" />
                                        </div>
                                        <div class="col-6">
                                            <label
                                                class="form-label x-small fw-bold text-muted text-uppercase mb-1">Waste</label>
                                            <input v-model.number="item.wastage" @input="calculateStockBalance(item)"
                                                type="number" step="0.001" min="0"
                                                class="form-control form-control-sm" />
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Completion Date Column -->
                            <td class="py-3">
                                <div style="min-width: 130px;">
                                    <label
                                        class="form-label x-small fw-bold text-muted text-uppercase mb-1">Date</label>
                                    <input v-model="item.item_completion_date" type="date"
                                        class="form-control form-control-sm" />
                                </div>
                            </td>

                            <!-- Actions Column -->
                            <td class="py-3 text-center">
                                <div class="d-grid gap-2">
                                    <button @click="saveItem(item)" class="btn btn-primary btn-sm fw-bold shadow-sm">
                                        <i class="fa fa-save me-1"></i> Save
                                    </button>
                                    <div v-if="item.is_selected_for_completion">
                                        <span
                                            class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 w-100 x-small fw-bold">
                                            <i class="fa fa-check-circle me-1"></i> Ready
                                        </span>
                                    </div>
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
