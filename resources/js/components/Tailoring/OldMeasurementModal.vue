<template>
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog"
                aria-modal="true">
                <!-- Background overlay -->
                <div class="flex items-center justify-center min-h-screen p-2 text-center">
                    <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm transition-opacity" aria-hidden="true"
                        @click="handleSkip">
                    </div>

                    <!-- Modal positioning -->
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                    <!-- Modal panel -->
                    <div
                        class="relative inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-2 sm:align-middle sm:max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                        <!-- Header -->
                        <div
                            class="bg-gradient-to-r from-blue-600 via-blue-700 to-indigo-700 px-3 py-2 text-white flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="bg-white/20 p-1 rounded-md mr-2">
                                    <i class="fa fa-history text-white text-xs"></i>
                                </div>
                                <div>
                                    <h4 class="text-base font-bold text-white">
                                        Use Previous Measurements
                                    </h4>
                                    <p class="text-xs text-white/90 mt-0.5">
                                        Select from {{ categoryName }} orders for this customer
                                    </p>
                                </div>
                            </div>
                            <button type="button" @click="handleSkip"
                                class="text-white hover:text-gray-200 focus:outline-none transition-colors">
                                <i class="fa fa-times text-xs"></i>
                            </button>
                        </div>

                        <!-- Body -->
                        <div class="px-3 py-3 max-h-[60vh] overflow-y-auto">
                            <!-- Loading -->
                            <div v-if="loading"
                                class="flex flex-col items-center justify-center py-12 text-slate-400">
                                <i class="fa fa-spinner fa-spin text-2xl mb-3 text-blue-500"></i>
                                <span class="text-xs font-bold">Loading previous measurements...</span>
                            </div>

                            <!-- Empty state -->
                            <div v-else-if="!items.length"
                                class="flex flex-col items-center justify-center py-12 text-center">
                                <div
                                    class="w-12 h-12 bg-gradient-to-r from-slate-100 to-blue-50 border border-slate-200 rounded-lg flex items-center justify-center mb-3">
                                    <i class="fa fa-inbox text-xl text-slate-400"></i>
                                </div>
                                <p class="text-slate-600 font-bold text-xs mb-1">No previous measurements found</p>
                                <p class="text-slate-400 text-xs mb-3">Enter measurements manually for this customer</p>
                                <button type="button" @click="handleSkip"
                                    class="inline-flex items-center justify-center px-3 py-1.5 border border-transparent text-xs font-semibold rounded-lg text-white shadow-lg bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 transition-all duration-200">
                                    <i class="fa fa-edit mr-1 text-xs"></i>
                                    Enter New Measurements
                                </button>
                            </div>

                            <!-- Measurement cards -->
                            <div v-else class="space-y-2">
                                <div v-for="(item, idx) in items" :key="item.id || idx"
                                    @click="handleSelect(item)"
                                    class="group cursor-pointer rounded-lg border border-slate-200 bg-gradient-to-r from-slate-50 to-blue-50 p-2.5 transition-all duration-300 hover:border-blue-300 hover:shadow-md active:scale-[0.99]">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2 mb-1.5">
                                                <span
                                                    class="inline-flex items-center px-2 py-0.5 rounded text-[0.65rem] font-bold uppercase tracking-wider bg-blue-100 text-blue-700">
                                                    {{ item.order_no || 'Order' }}
                                                </span>
                                                <span class="text-[0.65rem] font-bold text-slate-400 uppercase tracking-widest">
                                                    {{ formatDate(item.order_date) }}
                                                </span>
                                                <span v-if="item.model_name"
                                                    class="text-xs font-bold text-slate-600 truncate">
                                                    {{ item.model_name }}
                                                </span>
                                            </div>
                                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-x-3 gap-y-1">
                                                <div v-for="(val, key) in getPreviewFields(item)" :key="key"
                                                    class="flex items-center gap-1.5">
                                                    <span class="text-[0.6rem] font-bold text-slate-400 uppercase tracking-wider truncate">
                                                        {{ formatLabel(key) }}:
                                                    </span>
                                                    <span class="text-xs font-bold text-slate-800 truncate">
                                                        {{ val ?? '-' }}
                                                    </span>
                                                </div>
                                            </div>
                                            <p v-if="item.tailoring_notes" class="mt-1.5 text-[0.7rem] text-slate-500 line-clamp-2 italic">
                                                {{ item.tailoring_notes }}
                                            </p>
                                        </div>
                                        <div
                                            class="flex-shrink-0 w-8 h-8 rounded-lg bg-white/80 border border-slate-200 flex items-center justify-center text-slate-400 group-hover:bg-blue-600 group-hover:text-white group-hover:border-blue-500 transition-all">
                                            <i class="fa fa-chevron-right text-xs"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div v-if="items.length && !loading"
                            class="bg-gradient-to-r from-slate-50 to-gray-50 px-3 py-2 border-t border-slate-200 flex items-center justify-between gap-3">
                            <button type="button" @click="handleSkip"
                                class="inline-flex items-center justify-center px-3 py-1.5 border border-slate-300 shadow-sm text-xs font-semibold rounded-lg text-slate-700 bg-white hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
                                <i class="fa fa-edit mr-1 text-xs"></i>
                                Enter new measurements instead
                            </button>
                            <p class="text-[0.65rem] font-bold text-slate-400 uppercase tracking-widest">
                                Click a card to apply
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import axios from 'axios'

const props = defineProps({
    show: Boolean,
    accountId: [Number, String],
    categoryId: [Number, String],
    categoryName: {
        type: String,
        default: 'this'
    },
    category: Object, // for active_measurements labels
})

const emit = defineEmits(['select', 'skip'])

const items = ref([])
const loading = ref(false)

// Only show modal when we have items (skip entirely if none)
const showModal = computed(() => props.show && (loading.value || items.value.length > 0))

const previewFields = ['length', 'shoulder', 'chest', 'sleeve', 'waist', 'hip', 'collar', 'cuff', 'neck']

const getPreviewFields = (item) => {
    const result = {}
    const data = { ...(item.data || {}), ...item }
    previewFields.forEach(key => {
        const val = data[key]
        if (val !== undefined && val !== null && val !== '') {
            result[key] = val
        }
    })
    // If no preview fields found, show first few non-meta keys from data
    if (Object.keys(result).length === 0 && item.data && typeof item.data === 'object') {
        const meta = ['id', 'order_no', 'order_date', 'model_name', 'tailoring_category_model_id', 'tailoring_notes']
        let count = 0
        for (const [k, v] of Object.entries(item.data)) {
            if (!meta.includes(k) && v !== undefined && v !== null && v !== '' && count < 6) {
                result[k] = v
                count++
            }
        }
    }
    return result
}

const formatLabel = (key) => {
    if (!props.category?.active_measurements) {
        return String(key).replace(/_/g, ' ')
    }
    const m = props.category.active_measurements.find(x => x.field_key === key)
    return m?.label || String(key).replace(/_/g, ' ')
}

const formatDate = (dateStr) => {
    if (!dateStr) return '-'
    try {
        const d = new Date(dateStr)
        return d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' })
    } catch {
        return dateStr
    }
}

const fetchOldMeasurements = async () => {
    if (!props.accountId || !props.categoryId) return
    loading.value = true
    items.value = []
    try {
        const response = await axios.get(
            `/tailoring/order/old-measurements/${props.accountId}/${props.categoryId}`
        )
        if (response.data.success && Array.isArray(response.data.data)) {
            items.value = response.data.data
        }
    } catch (err) {
        console.error('Failed to load old measurements', err)
    } finally {
        loading.value = false
        if (items.value.length === 0) {
            emit('skip')
        }
    }
}

const handleSelect = (item) => {
    const payload = { ...item.data, ...item }
    delete payload.id
    delete payload.order_no
    delete payload.order_date
    delete payload.data
    emit('select', payload)
}

const handleSkip = () => {
    emit('skip')
}

watch(() => [props.show, props.accountId, props.categoryId], ([show, accountId, categoryId]) => {
    if (show && accountId && categoryId) {
        fetchOldMeasurements()
    } else {
        items.value = []
    }
}, { immediate: true })
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.2s ease;
}
.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
</style>
