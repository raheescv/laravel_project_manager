<template>
    <div class="space-y-6">
        <!-- Select All Header -->
        <div class="flex items-center justify-between bg-white p-4 rounded-xl shadow-sm border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-indigo-50 rounded-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-indigo-600" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                </div>
                <div>
                    <h3 class="font-bold text-gray-800">Job Items</h3>
                    <p class="text-xs text-gray-500">{{ items.length }} items total</p>
                </div>
            </div>

            <label class="flex items-center gap-3 cursor-pointer group">
                <span class="text-sm font-medium text-gray-600 group-hover:text-indigo-600 transition-colors">Select All
                    for Completion</span>
                <div class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" @change="handleSelectAll" :checked="allSelected" class="sr-only peer" />
                    <div
                        class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600">
                    </div>
                </div>
            </label>
        </div>

        <div v-if="items.length === 0"
            class="flex flex-col items-center justify-center p-12 bg-white rounded-2xl border-2 border-dashed border-gray-200 text-gray-500">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mb-4 text-gray-300" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="9.172 9.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="text-lg font-medium">No items found</p>
            <p class="text-sm">Search for an order to see completion items</p>
        </div>

        <!-- Cards Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div v-for="item in items" :key="item.id"
                class="bg-white rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300 group overflow-hidden"
                :class="{ 'ring-2 ring-indigo-500 ring-opacity-50': item.is_selected_for_completion }">
                <!-- Card Header -->
                <div
                    class="p-4 border-b border-gray-50 bg-gradient-to-r from-gray-50 to-white flex justify-between items-start">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <span
                                class="px-2 py-0.5 bg-indigo-100 text-indigo-700 text-[10px] font-bold rounded-full uppercase tracking-wider">
                                #{{ item.item_no }}
                            </span>
                            <span class="text-xs font-semibold text-gray-500 uppercase">{{ item.category?.name }}</span>
                            <button @click="viewMeasurements(item)"
                                class="ml-2 px-2 py-0.5 bg-amber-100 text-amber-700 text-[9px] font-black rounded-md uppercase hover:bg-amber-200 transition-colors flex items-center gap-1">
                                <i class="fa fa-eye"></i> View Measurements
                            </button>
                        </div>
                        <h4 class="font-bold text-gray-900 group-hover:text-indigo-600 transition-colors">{{
                            item.product_name }}</h4>
                    </div>

                    <div class="flex flex-col items-end gap-2">
                        <input :checked="item.is_selected_for_completion" @change="toggleItemCompletion(item, $event)"
                            type="checkbox"
                            class="w-5 h-5 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500 cursor-pointer" />
                        <div class="text-[10px] font-bold text-gray-400 uppercase">Complete</div>
                    </div>
                </div>

                <!-- Card Body -->
                <div class="p-4 space-y-4">
                    <!-- Basic Info Grid -->
                    <div class="grid grid-cols-4 gap-3">
                        <div
                            class="bg-gray-50 p-2 rounded-lg text-center group-hover:bg-white transition-colors border border-transparent group-hover:border-gray-100">
                            <div class="text-[10px] text-gray-400 uppercase font-bold mb-1">Qty</div>
                            <div class="text-sm font-bold text-gray-800">{{ item.quantity }}</div>
                        </div>
                        <div
                            class="bg-gray-50 p-2 rounded-lg text-center group-hover:bg-white transition-colors border border-transparent group-hover:border-gray-100">
                            <div class="text-[10px] text-gray-400 uppercase font-bold mb-1">Model</div>
                            <div class="text-sm font-bold text-gray-800 truncate px-1"
                                :title="item.categoryModel?.name">
                                {{ item.categoryModel?.name || '-' }}
                            </div>
                        </div>
                        <div
                            class="bg-gray-50 p-2 rounded-lg text-center group-hover:bg-white transition-colors border border-transparent group-hover:border-gray-100">
                            <div class="text-[10px] text-gray-400 uppercase font-bold mb-1">Length</div>
                            <div class="text-sm font-bold text-gray-800">{{ item.length || '-' }}</div>
                        </div>
                        <div
                            class="bg-gray-50 p-2 rounded-lg text-center group-hover:bg-white transition-colors border border-transparent group-hover:border-gray-100">
                            <div class="text-[10px] text-gray-400 uppercase font-bold mb-1">Amount</div>
                            <div class="text-sm font-bold text-indigo-600">{{ formatCurrency(item.amount) }}</div>
                        </div>
                    </div>

                    <!-- Tailor Section -->
                    <div class="bg-indigo-50/30 p-4 rounded-xl space-y-3 border border-indigo-100/30">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="p-1.5 bg-indigo-50 rounded-lg">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-indigo-500"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                <span class="text-xs font-bold text-indigo-700 uppercase tracking-tight">Tailor
                                    Assignment</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <span class="text-[10px] text-gray-400 font-bold uppercase">Commission:</span>
                                <span class="text-xs font-black text-indigo-600">{{
                                    formatCurrency(item.tailor_total_commission) }}</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1">
                                <label class="text-[9px] text-gray-500 font-bold uppercase ml-1">Assign Worker</label>
                                <select v-model="item.tailor_id"
                                    class="w-full h-9 px-3 bg-white border border-indigo-100/50 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all shadow-sm">
                                    <option value="">Select tailor...</option>
                                    <option v-for="(name, id) in tailors" :key="id" :value="id">{{ name }}</option>
                                </select>
                            </div>
                            <div class="space-y-1">
                                <label class="text-[9px] text-gray-500 font-bold uppercase ml-1">Rate/Item</label>
                                <input v-model.number="item.tailor_commission" @input="calculateCommission(item)"
                                    type="number" step="0.01" min="0"
                                    class="w-full h-9 px-3 bg-white border border-indigo-100/50 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all shadow-sm font-bold text-indigo-600" />
                            </div>
                        </div>
                    </div>

                    <!-- Material Tracking Section -->
                    <div class="bg-emerald-50/30 p-4 rounded-xl space-y-3 border border-emerald-100/30">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="p-1.5 bg-emerald-50 rounded-lg cursor-pointer hover:bg-emerald-100 transition-colors"
                                    @click="refreshStock(item)" title="Refresh Stock">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-emerald-500"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                </div>
                                <span class="text-xs font-bold text-emerald-700 uppercase tracking-tight">Material
                                    Management</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <span class="text-[10px] text-gray-400 font-bold uppercase">Balance:</span>
                                <span :class="item.stock_balance < 0 ? 'text-rose-600' : 'text-emerald-600'"
                                    class="text-xs font-black">
                                    {{ (item.stock_balance || 0).toFixed(3) }}
                                </span>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-3">
                            <div class="space-y-1">
                                <label class="text-[9px] text-gray-500 font-bold uppercase ml-1">In Stock</label>
                                <div
                                    class="h-9 px-3 bg-emerald-100/30 border border-emerald-100/50 rounded-xl text-xs font-bold flex items-center justify-center text-emerald-700 shadow-inner">
                                    {{ (item.product?.stock_quantity || 0).toFixed(3) }}
                                </div>
                            </div>
                            <div class="space-y-1">
                                <label class="text-[9px] text-gray-500 font-bold uppercase ml-1 text-indigo-500">Used
                                    Qty</label>
                                <input v-model.number="item.used_quantity" @input="calculateStockBalance(item)"
                                    type="number" step="0.001" min="0"
                                    class="w-full h-9 px-3 bg-white border border-emerald-100/50 rounded-xl text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all shadow-sm font-bold" />
                            </div>
                            <div class="space-y-1">
                                <label
                                    class="text-[9px] text-gray-500 font-bold uppercase ml-1 text-rose-500">Wastage</label>
                                <input v-model.number="item.wastage" @input="calculateStockBalance(item)" type="number"
                                    step="0.001" min="0"
                                    class="w-full h-9 px-3 bg-white border border-emerald-100/50 rounded-xl text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all shadow-sm font-bold" />
                            </div>
                        </div>
                    </div>

                    <!-- Footer Section -->
                    <div class="pt-2 flex items-center justify-between gap-4">
                        <div class="flex-1">
                            <label class="text-[9px] text-gray-500 font-bold uppercase ml-1 text-indigo-500">Completion
                                Date</label>
                            <div class="relative group/date">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg xmlns="http://www.w3.org/2000/svg"
                                        class="h-4 w-4 text-gray-400 group-focus-within/date:text-indigo-500 transition-colors"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <input v-model="item.item_completion_date" type="date"
                                    class="w-full h-10 pl-10 pr-3 bg-gray-50 border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all" />
                            </div>
                        </div>
                        <div class="flex flex-col items-end gap-2">
                            <button @click="saveItem(item)"
                                class="px-4 py-2 bg-indigo-600 text-white text-xs font-bold rounded-xl hover:bg-indigo-700 transition-all shadow-sm flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                                Save Changes
                            </button>
                            <div v-if="item.is_selected_for_completion"
                                class="flex items-center gap-2 px-3 py-1 bg-emerald-50 rounded-full border border-emerald-100 text-emerald-600 shadow-sm transition-all duration-300">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span class="text-[9px] font-black uppercase tracking-wider">Ready</span>
                            </div>
                        </div>
                    </div>
                </div>
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
