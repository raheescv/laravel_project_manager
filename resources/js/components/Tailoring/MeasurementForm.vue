<template>
    <div class="measurement-form flex flex-col gap-2">
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-2">
            <!-- Left: Basic & Body -->
            <div class="flex flex-col gap-2">
                <div class="measurement-card card-basic flex flex-col h-full">
                    <div class="card-header header-basic">
                        <i class="fa fa-info-circle"></i>
                        <h6>Basic & Body</h6>
                    </div>
                    <div class="card-body">
                        <div class="grid grid-cols-6 gap-1">
                            <div v-if="categoryModels.length > 0"
                                class="field-wrap col-span-3 flex flex-row justify-between items-end gap-2">
                                <div class="flex flex-col gap-1 flex-1 min-w-0">
                                    <label>Model</label>
                                    <VSelect v-model="measurements.tailoring_category_model_id"
                                        :options="categoryModels.map(m => ({ value: m.id, label: m.name }))"
                                        placeholder="Select Model" @change="updateModelName" />
                                </div>
                                <button type="button" @click="addCategoryModel" class="btn-add-more">
                                    <i class="fa fa-plus text-[10px]"></i>
                                </button>
                            </div>
                            <template v-for="m in getFieldsBySection('basic_body')" :key="m.id">
                                <div v-if="m.field_type === 'input'" :class="[m.field_key === 'length' ? 'col-span-3' : 'col-span-3', 'field-wrap']">
                                    <label> {{ m.label }} </label>
                                    <div class="relative">
                                        <div v-if="m.field_key === 'length'" class="input-icon">
                                            <i class="fa fa-arrows-v text-[10px]"></i>
                                        </div>
                                        <input v-model="measurements[m.field_key]" type="text" :placeholder="m.label"
                                            :class="m.field_key === 'length' ? 'pl-7' : ''" class="field-input" />
                                    </div>
                                </div>
                                <div v-else-if="m.field_type === 'select'" class="field-wrap col-span-3">
                                    <label> {{ m.label }} </label>
                                    <div class="flex gap-1">
                                        <VSelect v-model="measurements[m.field_key]"
                                            :options="getOptions(m.options_source).map(o => ({ value: o.value, label: o.value }))"
                                            :placeholder="`Select ${m.label}`" class="flex-1" />
                                        <button v-if="m.options_source" type="button"
                                            @click="addOption(m.options_source)" class="btn-add-more">
                                            <i class="fa fa-plus text-[10px]"></i>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Collar & Cuff -->
            <div class="flex flex-col gap-2">
                <div class="measurement-card card-collar flex flex-col h-full">
                    <div class="card-header header-collar">
                        <i class="fa fa-tag"></i>
                        <h6>Collar & Cuff</h6>
                    </div>
                    <div class="card-body">
                        <div class="grid grid-cols-2 gap-x-2 gap-y-1">
                            <template v-for="m in getFieldsBySection('collar_cuff')" :key="m.id">
                                <div v-if="m.field_type === 'input'" class="field-wrap col-span-1">
                                    <label> {{ m.label }} </label>
                                    <input v-model="measurements[m.field_key]" type="text" :placeholder="m.label"
                                        class="field-input" />
                                </div>
                                <div v-else-if="m.field_type === 'select'" class="field-wrap col-span-1">
                                    <label> {{ m.label }} </label>
                                    <div class="flex gap-1">
                                        <VSelect v-model="measurements[m.field_key]"
                                            :options="getOptions(m.options_source).map(o => ({ value: o.value, label: o.value }))"
                                            :placeholder="`Select ${m.label}`" class="flex-1" />
                                        <button v-if="m.options_source" type="button"
                                            @click="addOption(m.options_source)" class="btn-add-more">
                                            <i class="fa fa-plus text-[10px]"></i>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Specifications -->
            <div class="col-span-1 xl:col-span-2">
                <div class="measurement-card card-specs">
                    <div class="card-header header-specs">
                        <i class="fa fa-sliders"></i>
                        <h6>Specifications</h6>
                    </div>
                    <div class="card-body">
                        <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-8 gap-1">
                            <template v-for="m in getFieldsBySection('specifications')" :key="m.id">
                                <div v-if="m.field_type === 'input'" class="field-wrap col-span-1">
                                    <label> {{ m.label }} </label>
                                    <input v-model="measurements[m.field_key]" type="text" :placeholder="m.label"
                                        class="field-input" />
                                </div>
                                <div v-else-if="m.field_type === 'select'" class="field-wrap col-span-1">
                                    <label> {{ m.label }} </label>
                                    <div class="flex gap-1">
                                        <VSelect v-model="measurements[m.field_key]"
                                            :options="getOptions(m.options_source).map(o => ({ value: o.value, label: o.value }))"
                                            placeholder="Select" class="flex-1" />
                                        <button v-if="m.options_source" type="button"
                                            @click="addOption(m.options_source)" class="btn-add-more">
                                            <i class="fa fa-plus text-[6px]"></i>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div class="col-span-1 xl:col-span-2">
                <div class="measurement-card card-notes">
                    <div class="card-header header-notes">
                        <i class="fa fa-file-text-o"></i>
                        <h6>Tailoring Notes</h6>
                    </div>
                    <div class="card-body field-wrap">
                        <label>Tailoring Notes</label>
                        <textarea v-model="measurements.tailoring_notes" rows="2"
                            placeholder="Special instructions or notes..."
                            class="field-input field-textarea"></textarea>
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

<style scoped>
/* Eye-friendly palette: crisp cards, clear text, no blur */
.measurement-form {
    --bg-card: #ffffff;
    --bg-input: #f1f3f5;
    --border: #e2e6ea;
    --text: #1e293b;
    --text-muted: #475569;
    --label-color: #1e293b;
    --accent-blue: #4f7cac;
    --accent-teal: #3d7a6e;
    --accent-amber: #b8860b;
    --focus-ring: rgba(79, 124, 172, 0.2);
}

.measurement-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
    backdrop-filter: none;
    -webkit-font-smoothing: antialiased;
}

.card-header {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 6px 10px;
    border-bottom: 1px solid var(--border);
    font-size: 0.7rem;
    font-weight: 600;
    letter-spacing: 0.02em;
}

.card-header i {
    font-size: 10px;
    opacity: 0.9;
}

.header-basic {
    color: var(--accent-blue);
}

.header-collar {
    color: var(--accent-teal);
}

.header-specs {
    color: var(--accent-amber);
}

.header-notes {
    color: var(--text-muted);
}

.card-body {
    padding: 8px 10px;
}

.field-wrap {
    padding: 4px 6px;
    border-radius: 6px;
    transition: background 0.15s ease, box-shadow 0.15s ease;
}

.field-wrap:focus-within {
    background: rgba(79, 124, 172, 0.06);
    box-shadow: inset 0 0 0 1px rgba(79, 124, 172, 0.2);
}

.field-wrap label {
    display: block;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--label-color);
    margin-bottom: 4px;
    padding-left: 2px;
    -webkit-font-smoothing: antialiased;
}

.field-input {
    width: 100%;
    background: var(--bg-input);
    border: 1px solid var(--border);
    border-radius: 6px;
    padding: 6px 10px;
    font-size: 0.75rem;
    font-weight: 500;
    color: var(--text);
    transition: background 0.15s, border-color 0.15s, box-shadow 0.15s;
}

.field-input::placeholder {
    color: #94a3b0;
}

.field-input:focus {
    outline: none;
    background: #fff;
    border-color: var(--accent-blue);
    box-shadow: 0 0 0 2px var(--focus-ring);
}

.field-textarea {
    min-height: 48px;
    resize: none;
}

.input-icon {
    position: absolute;
    inset: 0;
    left: 0;
    display: flex;
    align-items: center;
    padding-left: 10px;
    pointer-events: none;
    color: #94a3b0;
}

.btn-add {
    width: 14px;
    height: 14px;
    min-width: 14px;
    flex-shrink: 0;
    border-radius: 3px;
    border: 1px solid var(--border);
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.15s, color 0.15s, border-color 0.15s;
}

.btn-add-more {
    flex-shrink: 0;
    border-radius: 6px;
    border: 1px solid var(--border);
    background: #fff;
    padding: 6px 12px;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--accent-blue);
    white-space: nowrap;
    transition: background 0.15s, color 0.15s, border-color 0.15s;
}

.btn-add-more:hover {
    background: var(--accent-blue);
    color: #fff;
    border-color: var(--accent-blue);
}

.btn-primary {
    color: var(--accent-blue);
}

.btn-primary:hover {
    background: var(--accent-blue);
    color: #fff;
    border-color: var(--accent-blue);
}

.btn-secondary {
    color: var(--text-muted);
}

.btn-secondary:hover {
    color: var(--accent-blue);
}

.btn-small {
    width: 14px;
    height: 14px;
    min-width: 14px;
    font-size: 8px;
    margin-top: 0;
    align-self: center;
    color: var(--text-muted);
}

.btn-small:hover {
    color: var(--accent-blue);
}
</style>
