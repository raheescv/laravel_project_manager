<template>
    <div class="flex flex-col gap-4 completion-items-table">
        <!-- Header with Select All -->
        <div class="rounded-xl md:rounded-2xl overflow-hidden shadow-md border border-slate-200/80 bg-gradient-to-br from-slate-50 to-white">
            <div class="p-4 sm:p-5 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3 sm:gap-4 min-w-0">
                    <div class="shrink-0 w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-blue-100 flex items-center justify-center text-blue-700 shadow-sm">
                        <i class="fa fa-tasks text-lg sm:text-xl"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-sm sm:text-base font-bold text-slate-800 leading-tight truncate">Job Completion Items</h3>
                        <p class="text-slate-600 text-xs font-medium">{{ items.length }} items total</p>
                    </div>
                </div>
                <div class="flex items-center justify-between sm:justify-end gap-3 border-t sm:border-t-0 border-slate-200 pt-3 sm:pt-0">
                    <span class="text-xs font-semibold text-slate-600">Select All</span>
                    <label class="relative inline-flex items-center cursor-pointer shrink-0">
                        <input type="checkbox" id="selectAllItems" @change="handleSelectAll" :checked="allSelected"
                            class="sr-only peer">
                        <div
                            class="w-11 h-6 bg-slate-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600 shadow-inner">
                        </div>
                    </label>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="items.length === 0"
            class="rounded-2xl border-2 border-dashed border-slate-300 py-16 text-center bg-slate-50/80">
            <div class="flex flex-col items-center gap-3">
                <i class="fa fa-search text-5xl text-slate-500"></i>
                <h5 class="text-sm font-bold text-slate-700">No items found</h5>
                <p class="text-sm text-slate-600">Search for an order to see completion items</p>
            </div>
        </div>

        <!-- When items exist: responsive layout -->
        <template v-else>
        <!-- Mobile/Tablet: Card layout (below md) -->
        <div class="block md:hidden space-y-4">
            <div v-for="item in items" :key="item.id"
                class="rounded-xl overflow-hidden shadow-md border border-slate-200 bg-white"
                :class="{ 'ring-2 ring-blue-300 bg-blue-50/40': item.is_selected_for_completion }">
                <!-- Card header: checkbox + item no + product -->
                <div class="p-3 border-b border-slate-200 flex items-start gap-3">
                    <input type="checkbox" :checked="item.is_selected_for_completion"
                        @change="toggleItemCompletion(item, $event)"
                        class="mt-1 w-4 h-4 rounded border-slate-400 text-blue-600 focus:ring-2 focus:ring-blue-300 shrink-0">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-xs font-bold text-blue-700 bg-blue-100 px-2 py-0.5 rounded border border-blue-200">#{{ item.item_no }}</span>
                            <span class="text-xs font-semibold text-slate-600">{{ item.category?.name }}</span>
                        </div>
                        <p class="text-sm font-bold text-slate-800 mt-1 break-words">{{ item.product_name }}</p>
                        <div class="flex flex-wrap gap-x-4 gap-y-0.5 mt-1 text-xs text-slate-600">
                            <span>Qty: <strong class="text-slate-800">{{ Number(item.quantity) }}</strong></span>
                            <span>Price: <strong class="text-blue-700">{{ formatCurrency(item.total) }}</strong></span>
                        </div>
                        <button @click="viewMeasurements(item)"
                            class="mt-2 inline-flex items-center gap-1 text-amber-600 hover:text-amber-700 font-semibold text-xs py-1">
                            <i class="fa fa-eye"></i> Measurements
                        </button>
                    </div>
                </div>
                <!-- Card body: sections -->
                <div class="p-3 space-y-3">
                    <!-- Tailor -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="rounded-lg p-2.5 border border-slate-200 bg-slate-50/50">
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Assign Tailor</label>
                            <SearchableSelect :modelValue="item.tailor_id"
                                @update:modelValue="item.tailor_id = $event" :options="tailors"
                                placeholder="Select tailor..."
                                input-class="assign-tailor-dropdown" />
                        </div>
                        <div class="rounded-lg p-2.5 border border-slate-200 bg-slate-50/50">
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Rate/Item</label>
                            <input v-model.number="item.tailor_commission" @input="calculateCommission(item)"
                                type="number" step="0.01" min="0"
                                class="input-field w-full text-sm font-semibold py-2 px-2.5 rounded-lg border border-slate-300 bg-white text-slate-800" />
                            <div class="flex justify-between mt-1 text-xs">
                                <span class="text-slate-600">Total</span>
                                <span class="font-bold text-blue-700">{{ formatCurrency(item.tailor_total_commission) }}</span>
                            </div>
                        </div>
                    </div>
                    <!-- Material -->
                    <div class="rounded-lg p-2.5 border border-slate-200 bg-slate-50/50">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-xs font-semibold text-slate-700">Material</span>
                            <span class="text-xs font-bold text-slate-800">Stock: {{ (item.product?.stock_quantity || 0).toFixed(3) }}</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-[10px] font-semibold text-slate-600 mb-0.5">Used</label>
                                <input v-model.number="item.used_quantity" @input="calculateStockBalance(item)"
                                    type="number" step="0.001" min="0"
                                    class="input-field w-full text-sm py-1.5 px-2 rounded border border-slate-300 bg-white text-slate-800" />
                            </div>
                            <div>
                                <label class="block text-[10px] font-semibold text-slate-600 mb-0.5">Waste</label>
                                <input v-model.number="item.wastage" @input="calculateStockBalance(item)"
                                    type="number" step="0.001" min="0"
                                    class="input-field w-full text-sm py-1.5 px-2 rounded border border-slate-300 bg-white text-slate-800" />
                            </div>
                        </div>
                    </div>
                    <!-- Date & Qty -->
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Completion Date</label>
                            <input v-model="item.item_completion_date" type="date"
                                class="input-field w-full text-sm py-2 px-2.5 rounded-lg border border-slate-300 bg-white text-slate-800" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Completed Qty</label>
                            <input v-model.number="item.completed_quantity" type="number" step="0.001" min="0"
                                :max="Number(item.quantity) || undefined" placeholder="0"
                                class="input-field w-full text-sm py-2 px-2.5 rounded-lg border border-slate-300 bg-white text-slate-800" />
                        </div>
                    </div>
                    <!-- Rating + Save -->
                    <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-slate-200">
                        <div class="flex items-center gap-1">
                            <span class="text-xs font-semibold text-slate-600 mr-1">Rating</span>
                            <button v-for="star in 5" :key="star" type="button" @click="item.rating = star"
                                class="p-1 rounded transition-colors"
                                :class="star <= (item.rating || 0) ? 'text-amber-500' : 'text-slate-300'">
                                <i class="fa fa-star"></i>
                            </button>
                        </div>
                        <div class="flex items-center gap-2">
                            <button @click="saveItem(item)"
                                class="px-3 py-2 rounded-lg bg-blue-600 text-white font-bold text-xs shadow-sm hover:bg-blue-700 flex items-center gap-1.5">
                                <i class="fa fa-save"></i> Save
                            </button>
                            <span v-if="item.is_selected_for_completion"
                                class="inline-flex items-center gap-1 px-2 py-1 rounded bg-emerald-100 text-emerald-700 text-xs font-bold">
                                <i class="fa fa-check-circle"></i> Ready
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Desktop: Table (md and up) -->
        <div class="hidden md:block rounded-2xl overflow-hidden shadow-md border border-slate-200/80 bg-white">
            <p class="px-4 py-2 text-xs text-slate-500 bg-slate-100 border-b border-slate-200 lg:hidden">Scroll horizontally to see all columns.</p>
            <div class="overflow-x-auto overflow-y-visible scroll-smooth">
                <table class="w-full text-left border-collapse min-w-[880px]">
                    <thead>
                        <tr class="bg-slate-100 border-b-2 border-slate-200">
                            <th class="px-3 py-3 lg:px-5 lg:py-4 w-12">
                                <input type="checkbox" @change="handleSelectAll" :checked="allSelected"
                                    class="w-4 h-4 rounded border-slate-400 text-blue-600 focus:ring-2 focus:ring-blue-300 focus:ring-offset-1">
                            </th>
                            <th class="px-3 py-3 lg:px-5 lg:py-4 text-xs font-bold text-slate-700 uppercase tracking-wide">Item Details</th>
                            <th class="px-3 py-3 lg:px-5 lg:py-4 text-xs font-bold text-slate-700 uppercase tracking-wide">Tailor</th>
                            <th class="px-3 py-3 lg:px-5 lg:py-4 text-xs font-bold text-slate-700 uppercase tracking-wide">Material</th>
                            <th class="px-3 py-3 lg:px-5 lg:py-4 text-xs font-bold text-slate-700 uppercase tracking-wide">Date / Qty</th>
                            <th class="px-3 py-3 lg:px-5 lg:py-4 text-xs font-bold text-slate-700 uppercase tracking-wide text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-slate-50/30">
                        <tr v-for="item in items" :key="item.id" class="transition-colors hover:bg-slate-100/60"
                            :class="{ 'bg-blue-50/60': item.is_selected_for_completion }">
                            <!-- Checkbox Column -->
                            <td class="px-3 py-3 lg:px-5 lg:py-4">
                                <input type="checkbox" :checked="item.is_selected_for_completion"
                                    @change="toggleItemCompletion(item, $event)"
                                    class="w-4 h-4 rounded border-slate-400 text-blue-600 focus:ring-2 focus:ring-blue-300 focus:ring-offset-1">
                            </td>

                            <!-- Item Details Column -->
                            <td class="px-3 py-3 lg:px-5 lg:py-4">
                                <div class="flex flex-col gap-1.5 text-sm">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold text-blue-700 bg-blue-100 px-2 py-1 rounded-md border border-blue-200">#{{ item.item_no }}</span>
                                        <span class="font-semibold text-slate-600 text-xs uppercase tracking-wide">{{ item.category?.name }}</span>
                                    </div>
                                    <div class="text-sm font-bold text-slate-800 mt-0.5 truncate max-w-[280px] md:max-w-[350px] lg:max-w-[450px] leading-snug">{{ item.product_name }}</div>
                                    <div class="grid grid-cols-2 gap-x-4 gap-y-1 text-slate-600 mt-1.5">
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-slate-600">Qty:</span>
                                            <span class="font-semibold text-slate-800">{{ Number(item.quantity) }}</span>
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-slate-600">Price:</span>
                                            <span class="font-semibold text-blue-700">{{ formatCurrency(item.total) }}</span>
                                        </div>
                                        <div class="flex flex-col gap-0.5">
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-slate-600">Model:</span>
                                                <span class="font-semibold text-slate-800">{{ item.categoryModel?.name || item.category_model?.name || item.tailoring_category_model_name || '-' }}</span>
                                            </div>
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-slate-600">Type:</span>
                                                <span class="font-semibold text-slate-800">{{ item.categoryModelType?.name || item.category_model_type?.name || item.tailoring_category_model_type_name || '-' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <button @click="viewMeasurements(item)"
                                        class="flex items-center gap-1.5 text-amber-600 hover:text-amber-700 font-semibold uppercase tracking-wide text-xs mt-2 py-1 px-2 rounded-md hover:bg-amber-50 transition-colors border border-amber-200/60">
                                        <i class="fa fa-eye"></i> Measurements
                                    </button>
                                </div>
                            </td>

                            <!-- Tailor Assignment Column -->
                            <td class="px-3 py-3 lg:px-5 lg:py-4">
                                <div class="flex flex-col gap-2 lg:gap-3 min-w-[160px] lg:min-w-[180px]">
                                    <div class="rounded-xl p-3 border border-slate-200 bg-white shadow-sm">
                                        <label class="block text-xs font-semibold text-slate-700 mb-2">Assign Tailor</label>
                                        <SearchableSelect :modelValue="item.tailor_id"
                                            @update:modelValue="item.tailor_id = $event" :options="tailors"
                                            placeholder="Select tailor..."
                                            input-class="assign-tailor-dropdown" />
                                    </div>
                                    <div class="rounded-xl p-3 border border-slate-200 bg-white shadow-sm">
                                        <div class="flex flex-col gap-2">
                                            <div>
                                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Rate/Item</label>
                                                <input v-model.number="item.tailor_commission"
                                                    @input="calculateCommission(item)" type="number" step="0.01" min="0"
                                                    class="input-field w-full text-sm font-semibold py-2 px-3 rounded-lg border border-slate-300 bg-slate-50 text-slate-800 placeholder-slate-500 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:bg-white transition-all" />
                                            </div>
                                            <div class="flex items-center justify-between pt-1 border-t border-slate-200">
                                                <span class="text-xs font-semibold text-slate-600">Total</span>
                                                <span class="text-sm font-bold text-blue-700">{{ formatCurrency(item.tailor_total_commission) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Material Tracking Column -->
                            <td class="px-3 py-3 lg:px-5 lg:py-4">
                                <div class="flex flex-col gap-2 lg:gap-3 min-w-[160px] lg:min-w-[200px]">
                                    <div class="rounded-xl p-3 border border-slate-200 bg-white shadow-sm">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-xs font-semibold text-slate-700">Current Stock</span>
                                            <span class="text-sm font-bold text-slate-800">{{ (item.product?.stock_quantity || 0).toFixed(3) }}</span>
                                        </div>
                                        <div class="grid grid-cols-2 gap-3">
                                            <div class="flex flex-col gap-1">
                                                <span class="text-xs font-semibold text-slate-700">Used</span>
                                                <input v-model.number="item.used_quantity"
                                                    @input="calculateStockBalance(item)" type="number" step="0.001" min="0"
                                                    class="input-field w-full text-sm font-semibold py-2 px-3 rounded-lg border border-slate-300 bg-slate-50 text-slate-800 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:bg-white transition-all" />
                                            </div>
                                            <div class="flex flex-col gap-1">
                                                <span class="text-xs font-semibold text-slate-700">Waste</span>
                                                <input v-model.number="item.wastage"
                                                    @input="calculateStockBalance(item)" type="number" step="0.001" min="0"
                                                    class="input-field w-full text-sm font-semibold py-2 px-3 rounded-lg border border-slate-300 bg-slate-50 text-slate-800 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:bg-white transition-all" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Completion Date & Completed Qty Column -->
                            <td class="px-3 py-3 lg:px-5 lg:py-4">
                                <div class="min-w-[120px] lg:min-w-[140px] flex flex-col gap-2 lg:gap-3">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Completion Date</label>
                                        <input v-model="item.item_completion_date" type="date"
                                            class="input-field w-full text-sm font-semibold py-2 px-3 rounded-lg border border-slate-300 bg-slate-50 text-slate-800 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:bg-white transition-all" />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Completed Qty</label>
                                        <input v-model.number="item.completed_quantity" type="number" step="0.001"
                                            min="0" :max="Number(item.quantity) || undefined" placeholder="0"
                                            class="input-field w-full text-sm font-semibold py-2 px-3 rounded-lg border border-slate-300 bg-slate-50 text-slate-800 placeholder-slate-500 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:bg-white transition-all" />
                                    </div>
                                </div>
                            </td>

                            <!-- Actions Column -->
                            <td class="px-3 py-3 lg:px-5 lg:py-4">
                                <div class="flex flex-col gap-2 w-max mx-auto">
                                    <div class="flex flex-col gap-1.5">
                                        <label class="block text-xs font-semibold text-slate-700 text-center">Rating</label>
                                        <div class="flex items-center gap-1 justify-center">
                                            <button v-for="star in 5" :key="star" type="button"
                                                @click="item.rating = star" class="p-1.5 rounded-md transition-colors hover:bg-amber-50"
                                                :class="star <= (item.rating || 0) ? 'text-amber-500' : 'text-slate-300 hover:text-amber-400'">
                                                <i class="fa fa-star text-base"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <button @click="saveItem(item)"
                                        class="px-4 py-2.5 rounded-lg bg-blue-600 text-white font-bold text-xs uppercase tracking-wide shadow-md shadow-blue-200/50 hover:bg-blue-700 hover:shadow-lg transition-all flex items-center justify-center gap-2">
                                        <i class="fa fa-save"></i> Save Job
                                    </button>
                                    <div v-if="item.is_selected_for_completion" class="animate-[fadeIn_0.3s_ease-out]">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-emerald-100 text-emerald-700 text-xs font-bold border border-emerald-200 w-full justify-center">
                                            <i class="fa fa-check-circle"></i> Ready
                                        </span>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        </template>

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

const hasMeasurements = (item) => true

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

<style scoped>
.completion-items-table :deep(.input-field),
.completion-items-table input[type="number"],
.completion-items-table input[type="date"] {
    color: #1e293b;
    font-size: 0.875rem;
}
.completion-items-table input::placeholder {
    color: #64748b;
}
.completion-items-table input:focus {
    outline: none;
}

/* Assign Tailor dropdown – neat trigger style */
.completion-items-table :deep(.assign-tailor-dropdown) {
    width: 100%;
    padding: 0.5rem 0.75rem;
    padding-right: 2.25rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: #1e293b;
    background: #fff;
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
.completion-items-table :deep(.assign-tailor-dropdown::placeholder) {
    color: #94a3b8;
}
.completion-items-table :deep(.assign-tailor-dropdown:hover) {
    border-color: #94a3b8;
}
.completion-items-table :deep(.assign-tailor-dropdown:focus) {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgb(59 130 246 / 0.2);
    outline: none;
}

/* Touch-friendly inputs on small screens */
@media (max-width: 767px) {
    .completion-items-table input[type="number"],
    .completion-items-table input[type="date"],
    .completion-items-table input[type="text"] {
        min-height: 44px;
    }
    .completion-items-table :deep(.assign-tailor-dropdown) {
        min-height: 44px;
    }
}
/* Smooth horizontal scroll on table wrapper */
.completion-items-table .overflow-x-auto {
    -webkit-overflow-scrolling: touch;
}
</style>
