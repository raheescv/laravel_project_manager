<template>
    <div class="flex flex-col gap-4">
        <!-- Main Form Grid -->
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
            <!-- Left Column: Basic & Body Measurements -->
            <div class="flex flex-col gap-4">
                <div
                    class="bg-white rounded-3xl shadow-lg shadow-slate-200/50 border border-slate-200 overflow-hidden flex flex-col h-full relative">
                    <div class="absolute inset-0 bg-gradient-to-br from-blue-50/20 to-transparent pointer-events-none">
                    </div>

                    <div
                        class="px-4 py-3 border-b border-slate-100 flex items-center gap-3 relative z-10 bg-slate-50/30">
                        <div
                            class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center text-blue-600 shadow-sm border border-blue-100">
                            <i class="fa fa-info-circle text-sm"></i>
                        </div>
                        <h6 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-0">Basic & Body</h6>
                    </div>
                    <div class="p-4 relative z-10">
                        <div class="grid grid-cols-6 gap-3">
                            <!-- Model Selection (Explicit) -->
                            <div v-if="categoryModels.length > 0" class="col-span-3">
                                <label
                                    class="block text-[0.65rem] font-bold text-slate-400 uppercase tracking-widest mb-1 px-1">
                                    Model
                                </label>
                                <div class="flex gap-1.5">
                                    <VSelect v-model="measurements.tailoring_category_model_id"
                                        :options="categoryModels.map(m => ({ value: m.id, label: m.name }))"
                                        placeholder="Select Model" @change="updateModelName" class="flex-1" />
                                    <button type="button" @click="addCategoryModel"
                                        class="w-8 h-8 rounded-lg border border-slate-200 bg-white text-blue-600 hover:bg-blue-600 hover:text-white hover:border-blue-600 transition-all flex items-center justify-center">
                                        <i class="fa fa-plus text-[10px]"></i>
                                    </button>
                                </div>
                            </div>

                            <template v-for="m in getFieldsBySection('basic_body')" :key="m.id">

                                <!-- Generic Input -->
                                <div v-if="m.field_type === 'input'"
                                    :class="m.field_key === 'length' ? 'col-span-3' : 'col-span-2'">
                                    <label
                                        class="block text-[0.65rem] font-bold text-slate-400 uppercase tracking-widest mb-1 px-1">{{
                                            m.label }}</label>
                                    <div class="relative">
                                        <div v-if="m.field_key === 'length'"
                                            class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-300">
                                            <i class="fa fa-arrows-v text-[10px]"></i>
                                        </div>
                                        <input v-model="measurements[m.field_key]" type="text" :placeholder="m.label"
                                            :class="m.field_key === 'length' ? 'pl-8' : 'px-3'"
                                            class="w-full bg-slate-50 border border-slate-200 rounded-xl pr-3 py-1.5 text-xs font-bold text-slate-700 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all" />
                                    </div>
                                </div>

                                <!-- Generic Select -->
                                <div v-else-if="m.field_type === 'select'" class="col-span-3">
                                    <label
                                        class="block text-[0.65rem] font-bold text-slate-400 uppercase tracking-widest mb-1 px-1">{{
                                            m.label }}</label>
                                    <div class="flex gap-1.5">
                                        <VSelect v-model="measurements[m.field_key]"
                                            :options="getOptions(m.options_source).map(o => ({ value: o.value, label: o.value }))"
                                            :placeholder="`Select ${m.label}`" class="flex-1" />
                                        <button v-if="m.options_source" type="button"
                                            @click="addOption(m.options_source)"
                                            class="w-8 h-8 rounded-lg border border-slate-200 bg-white text-slate-400 hover:text-blue-600 transition-all flex items-center justify-center shadow-sm">
                                            <i class="fa fa-plus text-[10px]"></i>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Collar & Cuff -->
            <div class="flex flex-col gap-4">
                <div
                    class="bg-white rounded-3xl shadow-lg shadow-slate-200/50 border border-slate-200 overflow-hidden flex flex-col h-full relative">
                    <div
                        class="absolute inset-0 bg-gradient-to-br from-emerald-50/20 to-transparent pointer-events-none">
                    </div>

                    <div
                        class="px-4 py-3 border-b border-slate-100 flex items-center gap-3 relative z-10 bg-slate-50/30">
                        <div
                            class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center text-emerald-600 shadow-sm border border-emerald-100">
                            <i class="fa fa-tag text-sm"></i>
                        </div>
                        <h6 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-0">Collar & Cuff</h6>
                    </div>
                    <div class="p-4 relative z-10">
                        <div class="grid grid-cols-2 gap-x-4 gap-y-3">
                            <template v-for="m in getFieldsBySection('collar_cuff')" :key="m.id">
                                <!-- Generic Input -->
                                <div v-if="m.field_type === 'input'" class="col-span-1">
                                    <label
                                        class="block text-[0.65rem] font-bold text-slate-400 uppercase tracking-widest mb-1 px-1">
                                        {{ m.label }}
                                    </label>
                                    <input v-model="measurements[m.field_key]" type="text" :placeholder="m.label"
                                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-bold text-slate-700 focus:bg-white focus:border-blue-500 transition-all" />
                                </div>

                                <!-- Generic Select -->
                                <div v-else-if="m.field_type === 'select'" class="col-span-1">
                                    <label
                                        class="block text-[0.65rem] font-black text-blue-600 uppercase tracking-widest mb-1 px-1">
                                        {{ m.label }}
                                    </label>
                                    <div class="flex gap-1.5">
                                        
                                        <VSelect v-model="measurements[m.field_key]"
                                            :options="getOptions(m.options_source).map(o => ({ value: o.value, label: o.value }))"
                                            :placeholder="`Select ${m.label}`" class="flex-1" />
                                        <button v-if="m.options_source" type="button"
                                            @click="addOption(m.options_source)"
                                            class="w-8 h-8 rounded-lg border border-blue-100 bg-white text-blue-600 hover:bg-blue-600 hover:text-white transition-all flex items-center justify-center"><i
                                                class="fa fa-plus text-[10px]"></i></button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Sections: Full Width Specifications -->
            <div class="col-span-1 xl:col-span-2">
                <div
                    class="bg-white rounded-3xl shadow-lg shadow-slate-200/50 border border-slate-200 overflow-hidden relative">
                    <div class="absolute inset-0 bg-gradient-to-tr from-amber-50/20 to-transparent pointer-events-none">
                    </div>

                    <div
                        class="px-4 py-3 border-b border-slate-100 flex items-center gap-3 relative z-10 bg-slate-50/30">
                        <div
                            class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center text-amber-600 shadow-sm border border-amber-100">
                            <i class="fa fa-sliders text-sm"></i>
                        </div>
                        <h6 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-0">Specifications</h6>
                    </div>
                    <div class="p-4 relative z-10">
                        <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-8 gap-3">
                            <template v-for="m in getFieldsBySection('specifications')" :key="m.id">
                                <!-- Generic Input -->
                                <div v-if="m.field_type === 'input'" class="col-span-1">
                                    <label
                                        class="block text-[0.65rem] font-bold text-slate-400 uppercase tracking-widest mb-1 px-1">{{
                                            m.label }}</label>
                                    <input v-model="measurements[m.field_key]" type="text" :placeholder="m.label"
                                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-bold text-slate-700 focus:bg-white focus:border-blue-500 transition-all" />
                                </div>

                                <!-- Generic Select -->
                                <div v-else-if="m.field_type === 'select'" class="col-span-1">
                                    <label
                                        class="block text-[0.65rem] font-bold text-slate-400 uppercase tracking-widest mb-1 px-1">{{
                                            m.label }}</label>
                                    <div class="flex gap-1">
                                        <VSelect v-model="measurements[m.field_key]"
                                            :options="getOptions(m.options_source).map(o => ({ value: o.value, label: o.value }))"
                                            placeholder="Select" class="flex-1" />
                                        <button v-if="m.options_source" type="button"
                                            @click="addOption(m.options_source)"
                                            class="w-7 h-7 rounded-lg border border-slate-200 bg-white text-slate-400 hover:text-blue-600 transition-all flex items-center justify-center shrink-0 mt-0.5"><i
                                                class="fa fa-plus text-[8px]"></i></button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notes Section -->
            <div class="col-span-1 xl:col-span-2">
                <div
                    class="bg-white rounded-3xl shadow-lg shadow-slate-200/50 border border-slate-200 overflow-hidden relative">
                    <div class="absolute inset-0 bg-gradient-to-tr from-slate-50/20 to-transparent pointer-events-none">
                    </div>

                    <div
                        class="px-4 py-3 border-b border-slate-100 flex items-center gap-3 relative z-10 bg-slate-50/30">
                        <div
                            class="w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center text-slate-600 shadow-sm border border-slate-100">
                            <i class="fa fa-file-text-o text-sm"></i>
                        </div>
                        <h6 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-0">Tailoring Notes
                        </h6>
                    </div>
                    <div class="p-4 relative z-10">
                        <textarea v-model="measurements.tailoring_notes" rows="2"
                            placeholder="Enter any special instructions or additional notes here..."
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-700 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all placeholder:text-slate-400 resize-none"></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue'
import axios from 'axios'
import { useToast } from 'vue-toastification'
import VSelect from '@/components/VSelect.vue'

const props = defineProps({
    modelValue: Object,
    category: Object,
    model: Object,
    measurementOptions: Object,
})

const emit = defineEmits(['update:modelValue', 'add-option'])

const toast = useToast()
const measurements = ref(props.modelValue || {})
const categoryModels = ref([])

const getFieldsBySection = (sectionId) => {
    if (!props.category?.active_measurements) return []
    return props.category.active_measurements
        .filter(m => m.section === sectionId)
        .sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0))
}

const getOptions = (type) => {
    if (!type || !props.measurementOptions || !props.measurementOptions[type]) return []
    return Object.entries(props.measurementOptions[type]).map(([id, value]) => ({
        id,
        value
    }))
}

const addOption = async (type) => {
    const value = prompt(`Add new ${type.replace('_', ' ')}:`)
    if (value && value.trim()) {
        emit('add-option', type, value.trim())
    }
}

const addCategoryModel = async () => {
    if (!props.category?.id) {
        toast.error('Please select an item type first')
        return
    }

    const name = prompt(`Add new ${props.category.name} Model:`)
    if (!name || !name.trim()) return

    try {
        const response = await axios.post('/tailoring/order/category-models', {
            tailoring_category_id: props.category.id,
            name: name.trim()
        })

        if (response.data.success) {
            toast.success('Model added successfully')
            // Add to list and select it
            const newModel = response.data.data
            categoryModels.value.push(newModel)
            measurements.value.tailoring_category_model_id = newModel.id
            measurements.value.tailoring_category_model_name = newModel.name
        }
    } catch (error) {
        console.error('Failed to add model', error)
        toast.error(error.response?.data?.message || 'Failed to add model')
    }
}

const updateModelName = () => {
    const selectedModel = categoryModels.value.find(m => m.id == measurements.value.tailoring_category_model_id)
    if (selectedModel) {
        measurements.value.tailoring_category_model_name = selectedModel.name
    } else {
        measurements.value.tailoring_category_model_name = null
    }
}

watch(() => props.category, async (newCategory) => {
    if (newCategory?.id) {
        try {
            const response = await axios.get(`/tailoring/order/category-models/${newCategory.id}`)
            if (response.data.success) {
                categoryModels.value = response.data.data
                // Removed aggressive clearing of tailoring_category_model_id 
                // to prevent data loss during edit and tab switching
            }
        } catch (error) {
            console.error('Failed to load category models', error)
        }
    } else {
        categoryModels.value = []
    }
}, { immediate: true })

// Watch measurements and emit updates, but prevent infinite loops
let isUpdatingFromProps = false
watch(measurements, (newVal) => {
    if (!isUpdatingFromProps) {
        emit('update:modelValue', { ...newVal })
    }
}, { deep: true })

watch(categoryModels, () => {
    updateModelName()
}, { deep: true })

watch(() => props.modelValue, (newVal) => {
    if (newVal && JSON.stringify(newVal) !== JSON.stringify(measurements.value)) {
        isUpdatingFromProps = true
        measurements.value = { ...newVal }
        nextTick(() => {
            isUpdatingFromProps = false
        })
    }
}, { deep: true })
</script>
