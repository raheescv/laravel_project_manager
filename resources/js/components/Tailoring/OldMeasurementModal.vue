<template>
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="show" class="fixed inset-0 z-[9999] overflow-y-auto" aria-labelledby="modal-title" role="dialog"
                aria-modal="true">
                <!-- Background overlay -->
                <div class="flex items-center justify-center min-h-screen p-4">
                    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @click="handleSkip"></div>

                    <!-- Modal panel -->
                    <div
                        class="relative bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all max-w-2xl w-full mx-auto border border-slate-200 animate-[slideUp_0.3s_ease-out]">
                        <!-- Header -->
                        <div class="px-6 py-5 border-b border-slate-100 bg-gradient-to-r from-indigo-50/80 to-violet-50/60 relative overflow-hidden">
                            <div class="absolute top-0 right-0 w-40 h-40 bg-indigo-200/20 rounded-full -mr-20 -mt-20 blur-2xl"></div>
                            <div class="relative z-10 flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div
                                        class="w-12 h-12 rounded-2xl bg-indigo-600 flex items-center justify-center text-white shadow-lg shadow-indigo-600/30">
                                        <i class="fa fa-history text-lg"></i>
                                    </div>
                                    <div>
                                        <h4 class="text-base font-black text-slate-900 leading-tight">
                                            Use Previous Measurements
                                        </h4>
                                        <p class="text-sm font-medium text-slate-500 mt-0.5">
                                            Select from {{ categoryName }} orders for this customer
                                        </p>
                                    </div>
                                </div>
                                <button type="button" @click="handleSkip"
                                    class="w-10 h-10 rounded-xl flex items-center justify-center text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-all">
                                    <i class="fa fa-times text-lg"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Body -->
                        <div class="p-6 max-h-[60vh] overflow-y-auto">
                            <!-- Loading -->
                            <div v-if="loading"
                                class="flex flex-col items-center justify-center py-16 text-slate-400">
                                <i class="fa fa-spinner fa-spin text-3xl mb-4 text-indigo-500"></i>
                                <span class="text-sm font-bold">Loading previous measurements...</span>
                            </div>

                            <!-- Empty state -->
                            <div v-else-if="!items.length"
                                class="flex flex-col items-center justify-center py-16 text-center">
                                <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center mb-4">
                                    <i class="fa fa-inbox text-2xl text-slate-400"></i>
                                </div>
                                <p class="text-slate-600 font-bold mb-1">No previous measurements found</p>
                                <p class="text-slate-400 text-sm">Enter measurements manually for this customer</p>
                                <button type="button" @click="handleSkip"
                                    class="mt-4 px-6 py-2.5 rounded-xl bg-indigo-600 text-white font-bold text-sm hover:bg-indigo-700 transition-all">
                                    Enter New Measurements
                                </button>
                            </div>

                            <!-- Measurement cards -->
                            <div v-else class="space-y-3">
                                <div v-for="(item, idx) in items" :key="item.id || idx"
                                    @click="handleSelect(item)"
                                    class="group cursor-pointer rounded-2xl border-2 border-slate-100 bg-white p-4 transition-all duration-300 hover:border-indigo-300 hover:shadow-lg hover:shadow-indigo-100/50 hover:-translate-y-0.5 active:scale-[0.99]">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-3 mb-2">
                                                <span
                                                    class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-[0.65rem] font-black uppercase tracking-wider bg-indigo-100 text-indigo-700">
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
                                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-x-4 gap-y-1.5">
                                                <div v-for="(val, key) in getPreviewFields(item)" :key="key"
                                                    class="flex items-center gap-2">
                                                    <span class="text-[0.6rem] font-bold text-slate-400 uppercase tracking-wider truncate">
                                                        {{ formatLabel(key) }}:
                                                    </span>
                                                    <span class="text-xs font-black text-slate-800 truncate">
                                                        {{ val ?? '-' }}
                                                    </span>
                                                </div>
                                            </div>
                                            <p v-if="item.tailoring_notes" class="mt-2 text-[0.7rem] text-slate-500 line-clamp-2 italic">
                                                {{ item.tailoring_notes }}
                                            </p>
                                        </div>
                                        <div
                                            class="flex-shrink-0 w-10 h-10 rounded-xl bg-slate-50 flex items-center justify-center text-slate-400 group-hover:bg-indigo-600 group-hover:text-white transition-all">
                                            <i class="fa fa-chevron-right text-sm"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div v-if="items.length && !loading"
                            class="px-6 py-4 border-t border-slate-100 bg-slate-50/50 flex items-center justify-between gap-4">
                            <button type="button" @click="handleSkip"
                                class="px-4 py-2 text-sm font-bold text-slate-500 hover:text-slate-700 transition-colors">
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
@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(16px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.2s ease;
}
.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
.modal-enter-active .relative,
.modal-leave-active .relative {
    transition: transform 0.2s ease;
}
</style>
