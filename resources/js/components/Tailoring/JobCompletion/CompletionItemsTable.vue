<template>
    <div class="flex flex-col gap-3">
        <!-- Header with Select All -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600">
                        <i class="fa fa-tasks text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-800 leading-none mb-1">Job Completion Items</h3>
                        <p class="text-slate-400 text-[10px] font-medium uppercase tracking-wider">{{ items.length }} items total</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <span class="text-[0.65rem] font-bold text-slate-400 uppercase tracking-widest">Select All</span>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="selectAllItems" @change="handleSelectAll" :checked="allSelected" class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="items.length === 0" class="bg-white rounded-2xl shadow-sm border-2 border-dashed border-slate-200 py-12 text-center">
            <div class="flex flex-col items-center gap-2 opacity-25">
                <i class="fa fa-search text-4xl text-slate-400"></i>
                <h5 class="text-sm font-bold text-slate-600 uppercase tracking-widest">No items found</h5>
                <p class="text-xs text-slate-400 italic">Search for an order to see completion items</p>
            </div>
        </div>

        <!-- Table -->
        <div v-else class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse whitespace-nowrap">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-100">
                            <th class="px-4 py-3 w-12">
                                <input type="checkbox" @change="handleSelectAll" :checked="allSelected"
                                    class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500/20">
                            </th>
                            <th class="px-4 py-3 text-[0.6rem] font-bold text-slate-400 uppercase tracking-widest">Item Details</th>
                            <th class="px-4 py-3 text-[0.6rem] font-bold text-slate-400 uppercase tracking-widest">Tailor Assignment</th>
                            <th class="px-4 py-3 text-[0.6rem] font-bold text-slate-400 uppercase tracking-widest">Material Tracking</th>
                            <th class="px-4 py-3 text-[0.6rem] font-bold text-slate-400 uppercase tracking-widest">Completion Date</th>
                            <th class="px-4 py-3 text-[0.6rem] font-bold text-slate-400 uppercase tracking-widest text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <tr v-for="item in items" :key="item.id"
                            class="transition-colors hover:bg-slate-50/50"
                            :class="{ 'bg-blue-50/30': item.is_selected_for_completion }">
                            <!-- Checkbox Column -->
                            <td class="px-4 py-3">
                                <input type="checkbox"
                                    :checked="item.is_selected_for_completion"
                                    @change="toggleItemCompletion(item, $event)"
                                    class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500/20">
                            </td>

                            <!-- Item Details Column -->
                            <td class="px-4 py-3">
                                <div class="flex flex-col gap-1 text-[0.65rem]">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-[0.6rem] font-black text-blue-600 bg-blue-50 px-1.5 py-0.5 rounded">#{{ item.item_no }}</span>
                                        <span class="font-bold text-slate-400 uppercase tracking-tighter">{{ item.category?.name }}</span>
                                    </div>
                                    <div class="text-xs font-bold text-slate-800 mt-0.5 truncate max-w-[150px]">{{ item.product_name }}</div>
                                    <div class="grid grid-cols-2 gap-x-3 gap-y-0.5 text-slate-500 mt-1">
                                        <div class="flex items-center gap-1">
                                            <span>Qty:</span>
                                            <span class="font-bold text-slate-700">{{ item.quantity }}</span>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <span>Price:</span>
                                            <span class="font-bold text-blue-600">{{ formatCurrency(item.amount) }}</span>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <span>Model:</span>
                                            <span class="font-bold text-slate-700 truncate max-w-[60px]">{{ item.categoryModel?.name || '-' }}</span>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <span>Len:</span>
                                            <span class="font-bold text-slate-700">{{ item.length || '-' }}</span>
                                        </div>
                                    </div>
                                    <button @click="viewMeasurements(item)"
                                        class="flex items-center gap-1 text-amber-500 hover:text-amber-600 font-bold uppercase tracking-widest text-[0.55rem] mt-1 transition-colors">
                                        <i class="fa fa-eye"></i> Measurements
                                    </button>
                                </div>
                            </td>

                            <!-- Tailor Assignment Column -->
                            <td class="px-4 py-3">
                                <div class="flex flex-col gap-2 min-w-[160px]">
                                    <div>
                                        <label class="block text-[0.55rem] font-bold text-slate-400 uppercase tracking-widest mb-1">Assign Tailor</label>
                                        <SearchableSelect :modelValue="item.tailor_id"
                                            @update:modelValue="item.tailor_id = $event" :options="tailors"
                                            placeholder="Select tailor..." 
                                            input-class="!text-[0.65rem] !py-1 !px-2 !rounded-lg !bg-white !border-slate-200" />
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1">
                                            <label class="block text-[0.55rem] font-bold text-slate-400 uppercase tracking-widest mb-1">Rate/Item</label>
                                            <input v-model.number="item.tailor_commission"
                                                @input="calculateCommission(item)" type="number" step="0.01" min="0"
                                                class="w-full text-[0.65rem] font-bold py-0.5 px-2 rounded-lg border border-slate-200 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all" />
                                        </div>
                                        <div class="pt-4">
                                            <p class="text-[0.55rem] font-bold text-slate-400 uppercase leading-none">Total</p>
                                            <p class="text-[0.65rem] font-black text-blue-600">{{ formatCurrency(item.tailor_total_commission) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Material Tracking Column -->
                            <td class="px-4 py-3">
                                <div class="flex flex-col gap-2 min-w-[180px]">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[0.55rem] font-bold text-slate-400 uppercase tracking-widest">Balance:</span>
                                        <div class="flex items-center gap-1.5">
                                            <span :class="item.stock_balance < 0 ? 'text-rose-500' : 'text-emerald-500'" class="text-[0.65rem] font-black">
                                                {{ (item.stock_balance || 0).toFixed(3) }}
                                            </span>
                                            <button @click="refreshStock(item)" class="text-slate-300 hover:text-blue-500 transition-colors">
                                                <i class="fa fa-refresh text-[10px]"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="bg-slate-50 rounded-lg p-1.5 border border-slate-100">
                                        <div class="flex items-center justify-between mb-1.5">
                                            <span class="text-[0.5rem] font-bold text-slate-400 uppercase tracking-tighter">Current Stock</span>
                                            <span class="text-[0.65rem] font-black text-slate-700">{{ (item.product?.stock_quantity || 0).toFixed(3) }}</span>
                                        </div>
                                        <div class="grid grid-cols-2 gap-2">
                                            <div class="flex flex-col gap-0.5">
                                                <span class="text-[0.5rem] font-bold text-slate-400 uppercase">Used</span>
                                                <input v-model.number="item.used_quantity"
                                                    @input="calculateStockBalance(item)" type="number" step="0.001" min="0"
                                                    class="w-full text-[0.65rem] font-bold py-0.5 px-1.5 rounded border border-slate-200 bg-white" />
                                            </div>
                                            <div class="flex flex-col gap-0.5">
                                                <span class="text-[0.5rem] font-bold text-slate-400 uppercase">Waste</span>
                                                <input v-model.number="item.wastage" @input="calculateStockBalance(item)"
                                                    type="number" step="0.001" min="0"
                                                    class="w-full text-[0.65rem] font-bold py-0.5 px-1.5 rounded border border-slate-200 bg-white" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Completion Date Column -->
                            <td class="px-4 py-3">
                                <div class="min-w-[110px]">
                                    <label class="block text-[0.55rem] font-bold text-slate-400 uppercase tracking-widest mb-1">Completion</label>
                                    <input v-model="item.item_completion_date" type="date"
                                        class="w-full text-[0.6rem] font-bold py-0.5 px-1.5 rounded-lg border border-slate-200 bg-white focus:border-blue-500 transition-all" />
                                </div>
                            </td>

                            <!-- Actions Column -->
                            <td class="px-4 py-3">
                                <div class="flex flex-col gap-1.5 w-max mx-auto">
                                    <button @click="saveItem(item)" 
                                        class="px-3 py-1 rounded-lg bg-blue-600 text-white font-bold text-[0.6rem] uppercase tracking-widest shadow-sm shadow-blue-200 hover:bg-blue-700 transition-all flex items-center justify-center gap-1.5">
                                        <i class="fa fa-save"></i> Save Job
                                    </button>
                                    <div v-if="item.is_selected_for_completion" class="animate-[fadeIn_0.3s_ease-out]">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-600 text-[0.55rem] font-black uppercase tracking-tighter border border-emerald-100 w-full justify-center">
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
