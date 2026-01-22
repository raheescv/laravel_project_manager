<template>
    <div class="measurement-form-container">
        <!-- Main Form Grid -->
        <div class="row g-4">
            <!-- Left Column: Primary & Body Measurements -->
            <div class="col-xl-6">
                <!-- Section 1: Basic Information -->
                <div class="measurement-card card shadow-sm border-0 mb-3 h-100">
                    <div class="card-header bg-white border-0 py-2 d-flex align-items-center">
                        <div class="icon-box me-2 bg-primary-light">
                            <i class="fa fa-info-circle text-primary"></i>
                        </div>
                        <h6 class="mb-0 fw-bold text-gray-800">Basic & Body</h6>
                    </div>
                    <div class="card-body pt-0 pb-2">
                        <div class="row g-2">
                            <!-- Model Selection -->
                            <div class="col-md-6">
                                <label class="form-label-custom">
                                    {{ category?.name || 'Item' }} Model
                                </label>
                                <div class="input-group-custom">
                                    <div class="select-wrapper">
                                        <select v-model="measurements.tailoring_category_model_id"
                                            @change="updateModelName" class="form-select-custom">
                                            <option :value="null">Select Model</option>
                                            <option v-for="model in categoryModels" :key="model.id" :value="model.id">
                                                {{ model.name }}
                                            </option>
                                        </select>
                                        <i class="fa fa-chevron-down select-chevron"></i>
                                    </div>
                                    <button type="button" @click="addCategoryModel" class="btn-add-option"
                                        title="Add New Model">
                                        <i class="fa fa-plus"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Length -->
                            <div class="col-md-6">
                                <label class="form-label-custom">Length</label>
                                <div class="input-wrapper">
                                    <i class="fa fa-arrows-v input-icon"></i>
                                    <input v-model.number="measurements.length" type="number" step="0.01"
                                        placeholder="0.00" class="form-control-custom" />
                                </div>
                            </div>

                            <!-- Body Measurements Grid -->
                            <div class="col-md-4">
                                <label class="form-label-custom">Shoulder</label>
                                <input v-model.number="measurements.shoulder" type="number" step="0.01"
                                    placeholder="0.00" class="form-control-custom" />
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-custom">Sleeve</label>
                                <input v-model.number="measurements.sleeve" type="number" step="0.01" placeholder="0.00"
                                    class="form-control-custom" />
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-custom">Chest</label>
                                <input v-model.number="measurements.chest" type="number" step="0.01" placeholder="0.00"
                                    class="form-control-custom" />
                            </div>

                            <div class="col-md-4">
                                <label class="form-label-custom">Stomach</label>
                                <input v-model="measurements.stomach" type="text" placeholder="Size..."
                                    class="form-control-custom" />
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-custom">Neck</label>
                                <input v-model.number="measurements.neck" type="number" step="0.01" placeholder="0.00"
                                    class="form-control-custom" />
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-custom">Bottom</label>
                                <input v-model="measurements.bottom" type="text" placeholder="Size..."
                                    class="form-control-custom" />
                            </div>

                            <div class="col-md-6">
                                <label class="form-label-custom">S.L Chest</label>
                                <input v-model.number="measurements.sl_chest" type="number" step="0.01"
                                    placeholder="0.00" class="form-control-custom" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-custom">S.L So</label>
                                <input v-model.number="measurements.sl_so" type="number" step="0.01" placeholder="0.00"
                                    class="form-control-custom" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Collar, Cuff & Additional -->
            <div class="col-xl-6">
                <!-- Section 2: Collar & Cuff -->
                <div class="measurement-card card shadow-sm border-0 mb-3 h-100">
                    <div class="card-header bg-white border-0 py-2 d-flex align-items-center">
                        <div class="icon-box me-2 bg-success-light">
                            <i class="fa fa-tag text-success"></i>
                        </div>
                        <h6 class="mb-0 fw-bold text-gray-800">Collar & Cuff</h6>
                    </div>
                    <div class="card-body pt-0 pb-2">
                        <div class="row g-2">
                            <!-- Collar Group -->
                            <div class="col-md-6">
                                <label class="form-label-custom text-primary fw-bold">Collar Type</label>
                                <div class="input-group-custom">
                                    <div class="select-wrapper">
                                        <select v-model="measurements.collar" class="form-select-custom">
                                            <option value="">Select Collar</option>
                                            <option v-for="option in getOptions('collar')" :key="option.id"
                                                :value="option.value">
                                                {{ option.value }}
                                            </option>
                                        </select>
                                        <i class="fa fa-chevron-down select-chevron"></i>
                                    </div>
                                    <button type="button" @click="addOption('collar')" class="btn-add-option">
                                        <i class="fa fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-custom">Collar Size</label>
                                <input v-model="measurements.collar_size" type="text" placeholder="Collar size..."
                                    class="form-control-custom" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-custom">Collar Cloth</label>
                                <div class="input-group-custom">
                                    <div class="select-wrapper">
                                        <select v-model="measurements.collar_cloth" class="form-select-custom">
                                            <option value="">Select Cloth</option>
                                            <option v-for="option in getOptions('collar_cloth')" :key="option.id"
                                                :value="option.value">
                                                {{ option.value }}
                                            </option>
                                        </select>
                                        <i class="fa fa-chevron-down select-chevron"></i>
                                    </div>
                                    <button type="button" @click="addOption('collar_cloth')" class="btn-add-option">
                                        <i class="fa fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-custom">Collar Model</label>
                                <div class="input-group-custom">
                                    <div class="select-wrapper">
                                        <select v-model="measurements.collar_model" class="form-select-custom">
                                            <option value="">Select Model</option>
                                            <option v-for="option in getOptions('collar_model')" :key="option.id"
                                                :value="option.value">
                                                {{ option.value }}
                                            </option>
                                        </select>
                                        <i class="fa fa-chevron-down select-chevron"></i>
                                    </div>
                                    <button type="button" @click="addOption('collar_model')" class="btn-add-option">
                                        <i class="fa fa-plus"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="col-12">
                                <hr class="my-2 opacity-10">
                            </div>

                            <!-- Cuff Group -->
                            <div class="col-md-6">
                                <label class="form-label-custom text-primary fw-bold">Cuff Type</label>
                                <div class="input-group-custom">
                                    <div class="select-wrapper">
                                        <select v-model="measurements.cuff" class="form-select-custom">
                                            <option value="">Select Cuff</option>
                                            <option v-for="option in getOptions('cuff')" :key="option.id"
                                                :value="option.value">
                                                {{ option.value }}
                                            </option>
                                        </select>
                                        <i class="fa fa-chevron-down select-chevron"></i>
                                    </div>
                                    <button type="button" @click="addOption('cuff')" class="btn-add-option">
                                        <i class="fa fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-custom">Cuff Size</label>
                                <input v-model="measurements.cuff_size" type="text" placeholder="Cuff size..."
                                    class="form-control-custom" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-custom">Cuff Cloth</label>
                                <div class="input-group-custom">
                                    <div class="select-wrapper">
                                        <select v-model="measurements.cuff_cloth" class="form-select-custom">
                                            <option value="">Select Cloth</option>
                                            <option v-for="option in getOptions('cuff_cloth')" :key="option.id"
                                                :value="option.value">
                                                {{ option.value }}
                                            </option>
                                        </select>
                                        <i class="fa fa-chevron-down select-chevron"></i>
                                    </div>
                                    <button type="button" @click="addOption('cuff_cloth')" class="btn-add-option">
                                        <i class="fa fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-custom">Cuff Model</label>
                                <div class="input-group-custom">
                                    <div class="select-wrapper">
                                        <select v-model="measurements.cuff_model" class="form-select-custom">
                                            <option value="">Select Model</option>
                                            <option v-for="option in getOptions('cuff_model')" :key="option.id"
                                                :value="option.value">
                                                {{ option.value }}
                                            </option>
                                        </select>
                                        <i class="fa fa-chevron-down select-chevron"></i>
                                    </div>
                                    <button type="button" @click="addOption('cuff_model')" class="btn-add-option">
                                        <i class="fa fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Sections: Full Width -->
            <div class="col-12">
                <div class="measurement-card card shadow-sm border-0">
                    <div class="card-header bg-white border-0 py-2 d-flex align-items-center">
                        <div class="icon-box me-2 bg-warning-light">
                            <i class="fa fa-sliders text-warning"></i>
                        </div>
                        <h6 class="mb-0 fw-bold text-gray-800">Specifications</h6>
                    </div>
                    <div class="card-body pt-0 pb-2">
                        <div class="row g-2">
                            <!-- Mar Group -->
                            <div class="col-md-3">
                                <label class="form-label-custom">Mar Size</label>
                                <input v-model="measurements.mar_size" type="text" placeholder="Size..."
                                    class="form-control-custom" />
                            </div>
                            <div class="col-md-3">
                                <label class="form-label-custom">Mar Model</label>
                                <div class="input-group-custom">
                                    <div class="select-wrapper">
                                        <select v-model="measurements.mar_model" class="form-select-custom">
                                            <option value="">Select Model</option>
                                            <option v-for="option in getOptions('mar_model')" :key="option.id"
                                                :value="option.value">
                                                {{ option.value }}
                                            </option>
                                        </select>
                                        <i class="fa fa-chevron-down select-chevron"></i>
                                    </div>
                                    <button type="button" @click="addOption('mar_model')" class="btn-add-option">
                                        <i class="fa fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label-custom">Neck D Button</label>
                                <input v-model="measurements.neck_d_button" type="text" placeholder="..."
                                    class="form-control-custom" />
                            </div>
                            <div class="col-md-3">
                                <label class="form-label-custom">Mobile Pocket</label>
                                <div class="select-wrapper">
                                    <select v-model="measurements.mobile_pocket" class="form-select-custom">
                                        <option value="No">No Pocket</option>
                                        <option value="Yes">Yes, Include</option>
                                    </select>
                                    <i class="fa fa-chevron-down select-chevron"></i>
                                </div>
                            </div>

                            <!-- Side PT Group -->
                            <div class="col-md-3">
                                <label class="form-label-custom">Side PT Size</label>
                                <input v-model="measurements.side_pt_size" type="text" placeholder="Size..."
                                    class="form-control-custom" />
                            </div>
                            <div class="col-md-3">
                                <label class="form-label-custom">Side PT Model</label>
                                <div class="input-group-custom">
                                    <div class="select-wrapper">
                                        <select v-model="measurements.side_pt_model" class="form-select-custom">
                                            <option value="">Select Model</option>
                                            <option v-for="option in getOptions('side_pt_model')" :key="option.id"
                                                :value="option.value">
                                                {{ option.value }}
                                            </option>
                                        </select>
                                        <i class="fa fa-chevron-down select-chevron"></i>
                                    </div>
                                    <button type="button" @click="addOption('side_pt_model')" class="btn-add-option">
                                        <i class="fa fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label-custom">Regal Size</label>
                                <input v-model="measurements.regal_size" type="text" placeholder="Size..."
                                    class="form-control-custom" />
                            </div>
                            <div class="col-md-3">
                                <label class="form-label-custom">Knee Loose</label>
                                <input v-model="measurements.knee_loose" type="text" placeholder="Loose..."
                                    class="form-control-custom" />
                            </div>

                            <!-- FP Group -->
                            <div class="col-md-3">
                                <label class="form-label-custom">FP Down</label>
                                <input v-model="measurements.fp_down" type="text" placeholder="Down..."
                                    class="form-control-custom" />
                            </div>
                            <div class="col-md-3">
                                <label class="form-label-custom">FP Size</label>
                                <input v-model="measurements.fp_size" type="text" placeholder="Size..."
                                    class="form-control-custom" />
                            </div>
                            <div class="col-md-3">
                                <label class="form-label-custom">FP Model</label>
                                <div class="input-group-custom">
                                    <div class="select-wrapper">
                                        <select v-model="measurements.fp_model" class="form-select-custom">
                                            <option value="">Select Model</option>
                                            <option v-for="option in getOptions('fp_model')" :key="option.id"
                                                :value="option.value">
                                                {{ option.value }}
                                            </option>
                                        </select>
                                        <i class="fa fa-chevron-down select-chevron"></i>
                                    </div>
                                    <button type="button" @click="addOption('fp_model')" class="btn-add-option">
                                        <i class="fa fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label-custom">Pen Pocket</label>
                                <div class="input-group-custom">
                                    <div class="select-wrapper">
                                        <select v-model="measurements.pen" class="form-select-custom">
                                            <option value="">Select Pen</option>
                                            <option v-for="option in getOptions('pen')" :key="option.id"
                                                :value="option.value">
                                                {{ option.value }}
                                            </option>
                                        </select>
                                        <i class="fa fa-chevron-down select-chevron"></i>
                                    </div>
                                    <button type="button" @click="addOption('pen')" class="btn-add-option">
                                        <i class="fa fa-plus"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Stitching & Buttons -->
                            <div class="col-md-3">
                                <label class="form-label-custom">Stitching Type</label>
                                <div class="input-group-custom">
                                    <div class="select-wrapper">
                                        <select v-model="measurements.stitching" class="form-select-custom">
                                            <option value="">Select Type</option>
                                            <option v-for="option in getOptions('stitching')" :key="option.id"
                                                :value="option.value">
                                                {{ option.value }}
                                            </option>
                                        </select>
                                        <i class="fa fa-chevron-down select-chevron"></i>
                                    </div>
                                    <button type="button" @click="addOption('stitching')" class="btn-add-option">
                                        <i class="fa fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label-custom">Button Type</label>
                                <div class="input-group-custom">
                                    <div class="select-wrapper">
                                        <select v-model="measurements.button" class="form-select-custom">
                                            <option value="">Select Button</option>
                                            <option v-for="option in getOptions('button')" :key="option.id"
                                                :value="option.value">
                                                {{ option.value }}
                                            </option>
                                        </select>
                                        <i class="fa fa-chevron-down select-chevron"></i>
                                    </div>
                                    <button type="button" @click="addOption('button')" class="btn-add-option">
                                        <i class="fa fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label-custom">Button No</label>
                                <input v-model="measurements.button_no" type="text" placeholder="#"
                                    class="form-control-custom" />
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-custom">Special Notes</label>
                                <div class="input-wrapper">
                                    <i class="fa fa-sticky-note-o input-icon"></i>
                                    <input v-model="measurements.tailoring_notes" type="text"
                                        placeholder="Extra instructions..." class="form-control-custom" />
                                </div>
                            </div>
                        </div>
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

<style scoped>
.measurement-form-container {
    padding: 2px 0;
}

.measurement-card {
    border-radius: 12px;
    background-color: #ffffff;
    border: 1px solid rgba(0, 0, 0, 0.05) !important;
}

.icon-box {
    width: 32px;
    height: 32px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.95rem;
}

.bg-primary-light { background-color: rgba(37, 99, 235, 0.1); }
.bg-success-light { background-color: rgba(5, 150, 105, 0.1); }
.bg-warning-light { background-color: rgba(217, 119, 6, 0.1); }

.form-label-custom {
    font-size: 0.72rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    color: #64748b;
    margin-bottom: 2px;
    display: block;
}

.input-wrapper,
.select-wrapper {
    position: relative;
    width: 100%;
}

.input-icon {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    pointer-events: none;
    font-size: 0.8rem;
}

.select-chevron {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    pointer-events: none;
    font-size: 0.7rem;
}

.form-control-custom,
.form-select-custom {
    width: 100%;
    padding: 6px 10px;
    font-size: 0.88rem;
    border: 1.5px solid #e2e8f0;
    border-radius: 8px;
    color: #1e293b;
    transition: all 0.2s ease;
    background-color: #f8fafc;
}

.input-wrapper .form-control-custom {
    padding-left: 30px;
}

.form-select-custom {
    -webkit-appearance: none;
    appearance: none;
    padding-right: 32px;
}

.form-control-custom:focus,
.form-select-custom:focus {
    outline: none;
    border-color: #3b82f6;
    background-color: #ffffff;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.input-group-custom {
    display: flex;
    gap: 6px;
}

.btn-add-option {
    flex-shrink: 0;
    width: 36px;
    height: 36px;
    border: 1.5px solid #e2e8f0;
    background-color: #ffffff;
    border-radius: 8px;
    color: #3b82f6;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.btn-add-option:hover {
    background-color: #3b82f6;
    color: #ffffff;
    border-color: #3b82f6;
    transform: translateY(-1px);
}

.text-gray-800 {
    color: #1e293b;
}

input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    appearance: none;
    margin: 0;
}

input[type=number] {
    -moz-appearance: textfield;
}

@media (max-width: 1199px) {
    .h-100 {
        height: auto !important;
    }
}
</style>
