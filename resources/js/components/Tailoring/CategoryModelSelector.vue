<template>
    <div class="bg-white rounded-lg shadow-sm p-4">
        <h3 class="text-md font-semibold text-gray-800 mb-4">Select Category & Model</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="form-group">
                <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                <select 
                    v-model="selectedCategoryId"
                    @change="handleCategoryChange"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
                    <option value="">Select Category</option>
                    <option v-for="category in categories" :key="category.id" :value="category.id">
                        {{ category.name }}
                    </option>
                </select>
            </div>
            <div class="form-group">
                <label class="block text-sm font-medium text-gray-700 mb-1">Model</label>
                <div class="flex gap-2">
                    <select 
                        v-model="selectedModelId"
                        @change="handleModelChange"
                        :disabled="!selectedCategoryId"
                        class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-100"
                    >
                        <option value="">Select Model</option>
                        <option v-for="model in availableModels" :key="model.id" :value="model.id">
                            {{ model.name }}
                        </option>
                    </select>
                    <button 
                        type="button"
                        @click="addModel"
                        :disabled="!selectedCategoryId"
                        class="px-3 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-bold disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        +
                    </button>
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
    selectedCategoryId.value = newVal?.id || null
    if (newVal?.id) {
        handleCategoryChange()
    }
})

watch(() => props.selectedModel, (newVal) => {
    selectedModelId.value = newVal?.id || null
})
</script>
