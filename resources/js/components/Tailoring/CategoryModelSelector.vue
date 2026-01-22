<template>
    <div class="card shadow-sm border-0">
        <div class="card-body p-3">
            <h6 class="fw-bold text-dark mb-3">Select Category & Model</h6>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1 d-block">Category</label>
                    <select v-model="selectedCategoryId" @change="handleCategoryChange"
                        class="form-select form-select-sm">
                        <option :value="null">Select Category</option>
                        <option v-for="category in categories" :key="category.id" :value="category.id">
                            {{ category.name }}
                        </option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-1 d-block">Model</label>
                    <div class="input-group input-group-sm">
                        <select v-model="selectedModelId" @change="handleModelChange" :disabled="!selectedCategoryId"
                            class="form-select">
                            <option :value="null">Select Model</option>
                            <option v-for="model in availableModels" :key="model.id" :value="model.id">
                                {{ model.name }}
                            </option>
                        </select>
                        <button type="button" @click="addModel" :disabled="!selectedCategoryId"
                            class="btn btn-primary fw-bold">
                            <i class="fa fa-plus"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import axios from 'axios'
import { useToast } from 'vue-toastification'

const props = defineProps({
    categories: Array,
    selectedCategory: Object,
    selectedModel: Object,
})

const emit = defineEmits(['update:selectedCategory', 'update:selectedModel', 'add-model'])

const toast = useToast()
const selectedCategoryId = ref(props.selectedCategory?.id || null)
const selectedModelId = ref(props.selectedModel?.id || null)
const availableModels = ref([])

const handleCategoryChange = async () => {
    const category = props.categories.find(c => c.id == selectedCategoryId.value)
    emit('update:selectedCategory', category || null)

    if (selectedCategoryId.value) {
        try {
            const response = await axios.get(`/tailoring/order/category-models/${selectedCategoryId.value}`)
            if (response.data.success) {
                availableModels.value = response.data.data
            }
        } catch (error) {
            console.error('Failed to load models', error)
        }
    } else {
        availableModels.value = []
        selectedModelId.value = null
        emit('update:selectedModel', null)
    }
}

const handleModelChange = () => {
    const model = availableModels.value.find(m => m.id == selectedModelId.value)
    emit('update:selectedModel', model || null)
}

const addModel = () => {
    const name = prompt('Enter new model name:')
    if (name && name.trim() && selectedCategoryId.value) {
        emit('add-model', selectedCategoryId.value, name.trim())
    }
}

watch(() => props.selectedCategory, (newVal) => {
    const newId = newVal?.id || null
    if (selectedCategoryId.value !== newId) {
        selectedCategoryId.value = newId
        if (newId) {
            handleCategoryChange()
        }
    }
})

watch(() => props.selectedModel, (newVal) => {
    selectedModelId.value = newVal?.id || null
})
</script>
