<template>
    <div class="bg-white rounded-lg shadow-sm p-4 md:p-6">
        <!-- <h2 class="text-lg font-semibold text-gray-800 mb-4 border-l-4 border-blue-600 pl-3">Measurements</h2> -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Left Column -->
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ category?.name || 'Item' }}
                            Model</label>
                        <div class="flex gap-2">
                            <select v-model="measurements.tailoring_category_model_id" @change="updateModelName"
                                class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option :value="null">Select Model</option>
                                <option v-for="model in categoryModels" :key="model.id" :value="model.id">
                                    {{ model.name }}
                                </option>
                            </select>
                            <button type="button" @click="addCategoryModel"
                                class="px-3 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-bold">
                                +
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Length</label>
                        <input v-model.number="measurements.length" type="number" step="0.01"
                            placeholder="Enter length..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Shoulder</label>
                        <input v-model.number="measurements.shoulder" type="number" step="0.01"
                            placeholder="Enter shoulder..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                    </div>
                    <div class="form-group">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sleeve</label>
                        <input v-model.number="measurements.sleeve" type="number" step="0.01"
                            placeholder="Enter sleeve..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Chest</label>
                        <input v-model.number="measurements.chest" type="number" step="0.01"
                            placeholder="Enter chest..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                    </div>
                    <div class="form-group">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Stomach</label>
                        <input v-model="measurements.stomach" type="text" placeholder="Enter stomach..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="block text-sm font-medium text-gray-700 mb-1">S.L Chest</label>
                        <input v-model.number="measurements.sl_chest" type="number" step="0.01"
                            placeholder="Enter S.L Chest..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                    </div>
                    <div class="form-group">
                        <label class="block text-sm font-medium text-gray-700 mb-1">S.L So</label>
                        <input v-model.number="measurements.sl_so" type="number" step="0.01"
                            placeholder="Enter S.L So..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Neck</label>
                        <input v-model.number="measurements.neck" type="number" step="0.01" placeholder="Enter neck..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                    </div>
                    <div class="form-group">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Bottom</label>
                        <input v-model="measurements.bottom" type="text" placeholder="Enter bottom..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mar Size</label>
                        <input v-model="measurements.mar_size" type="text" placeholder="Enter mar size..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                    </div>
                    <div class="form-group">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mar Model</label>
                        <div class="flex gap-2">
                            <select v-model="measurements.mar_model"
                                class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select Model</option>
                                <option v-for="option in getOptions('mar_model')" :key="option.id"
                                    :value="option.value">
                                    {{ option.value }}
                                </option>
                            </select>
                            <button type="button" @click="addOption('mar_model')"
                                class="px-3 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-bold">
                                +
                            </button>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cuff</label>
                        <div class="flex gap-2">
                            <select v-model="measurements.cuff"
                                class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select Cuff</option>
                                <option v-for="option in getOptions('cuff')" :key="option.id" :value="option.value">
                                    {{ option.value }}
                                </option>
                            </select>
                            <button type="button" @click="addOption('cuff')"
                                class="px-3 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-bold">
                                +
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cuff Size</label>
                        <input v-model="measurements.cuff_size" type="text" placeholder="Enter cuff size..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cuff Cloth</label>
                        <div class="flex gap-2">
                            <select v-model="measurements.cuff_cloth"
                                class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select Cloth</option>
                                <option v-for="option in getOptions('cuff_cloth')" :key="option.id"
                                    :value="option.value">
                                    {{ option.value }}
                                </option>
                            </select>
                            <button type="button" @click="addOption('cuff_cloth')"
                                class="px-3 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-bold">
                                +
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cuff Model</label>
                        <div class="flex gap-2">
                            <select v-model="measurements.cuff_model"
                                class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select Model</option>
                                <option v-for="option in getOptions('cuff_model')" :key="option.id"
                                    :value="option.value">
                                    {{ option.value }}
                                </option>
                            </select>
                            <button type="button" @click="addOption('cuff_model')"
                                class="px-3 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-bold">
                                +
                            </button>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Neck D Button</label>
                        <input v-model="measurements.neck_d_button" type="text" placeholder="Enter neck d button..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                    </div>
                    <div class="form-group">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Side PT Size</label>
                        <input v-model="measurements.side_pt_size" type="text" placeholder="Enter side PT size..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Collar</label>
                        <div class="flex gap-2">
                            <select v-model="measurements.collar"
                                class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select Collar</option>
                                <option v-for="option in getOptions('collar')" :key="option.id" :value="option.value">
                                    {{ option.value }}
                                </option>
                            </select>
                            <button type="button" @click="addOption('collar')"
                                class="px-3 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-bold">
                                +
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Collar Size</label>
                        <input v-model="measurements.collar_size" type="text" placeholder="Enter collar size..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Collar Cloth</label>
                        <div class="flex gap-2">
                            <select v-model="measurements.collar_cloth"
                                class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select Cloth</option>
                                <option v-for="option in getOptions('collar_cloth')" :key="option.id"
                                    :value="option.value">
                                    {{ option.value }}
                                </option>
                            </select>
                            <button type="button" @click="addOption('collar_cloth')"
                                class="px-3 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-bold">
                                +
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Collar Model</label>
                        <div class="flex gap-2">
                            <select v-model="measurements.collar_model"
                                class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select Model</option>
                                <option v-for="option in getOptions('collar_model')" :key="option.id"
                                    :value="option.value">
                                    {{ option.value }}
                                </option>
                            </select>
                            <button type="button" @click="addOption('collar_model')"
                                class="px-3 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-bold">
                                +
                            </button>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Regal Size</label>
                        <input v-model="measurements.regal_size" type="text" placeholder="Enter regal size..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                    </div>
                    <div class="form-group">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Knee Loose</label>
                        <input v-model="measurements.knee_loose" type="text" placeholder="Enter knee loose..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="block text-sm font-medium text-gray-700 mb-1">FP Down</label>
                        <input v-model="measurements.fp_down" type="text" placeholder="Enter FP down..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                    </div>
                    <div class="form-group">
                        <label class="block text-sm font-medium text-gray-700 mb-1">FP Model</label>
                        <div class="flex gap-2">
                            <select v-model="measurements.fp_model"
                                class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select Model</option>
                                <option v-for="option in getOptions('fp_model')" :key="option.id" :value="option.value">
                                    {{ option.value }}
                                </option>
                            </select>
                            <button type="button" @click="addOption('fp_model')"
                                class="px-3 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-bold">
                                +
                            </button>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="block text-sm font-medium text-gray-700 mb-1">FP Size</label>
                        <input v-model="measurements.fp_size" type="text" placeholder="Enter FP size..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                    </div>
                    <div class="form-group">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Pen</label>
                        <div class="flex gap-2">
                            <select v-model="measurements.pen"
                                class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select Pen</option>
                                <option v-for="option in getOptions('pen')" :key="option.id" :value="option.value">
                                    {{ option.value }}
                                </option>
                            </select>
                            <button type="button" @click="addOption('pen')"
                                class="px-3 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-bold">
                                +
                            </button>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Side PT Model</label>
                        <div class="flex gap-2">
                            <select v-model="measurements.side_pt_model"
                                class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select Model</option>
                                <option v-for="option in getOptions('side_pt_model')" :key="option.id"
                                    :value="option.value">
                                    {{ option.value }}
                                </option>
                            </select>
                            <button type="button" @click="addOption('side_pt_model')"
                                class="px-3 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-bold">
                                +
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Stitching</label>
                        <div class="flex gap-2">
                            <select v-model="measurements.stitching"
                                class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select Stitching</option>
                                <option v-for="option in getOptions('stitching')" :key="option.id"
                                    :value="option.value">
                                    {{ option.value }}
                                </option>
                            </select>
                            <button type="button" @click="addOption('stitching')"
                                class="px-3 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-bold">
                                +
                            </button>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Button</label>
                        <div class="flex gap-2">
                            <select v-model="measurements.button"
                                class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select Button</option>
                                <option v-for="option in getOptions('button')" :key="option.id" :value="option.value">
                                    {{ option.value }}
                                </option>
                            </select>
                            <button type="button" @click="addOption('button')"
                                class="px-3 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-bold">
                                +
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Button No</label>
                        <input v-model="measurements.button_no" type="text" placeholder="Enter button no..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mobile Pocket</label>
                        <select v-model="measurements.mobile_pocket"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="No">No</option>
                            <option value="Yes">Yes</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                        <input v-model="measurements.tailoring_notes" type="text" placeholder="Enter notes..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
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

const getOptions = (type) => {
    if (!props.measurementOptions || !props.measurementOptions[type]) return []
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
    const selectedModel = categoryModels.value.find(m => m.id === measurements.value.tailoring_category_model_id)
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
                // Clear selected model when category changes
                measurements.value.tailoring_category_model_id = null
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
