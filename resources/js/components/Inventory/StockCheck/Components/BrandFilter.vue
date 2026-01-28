<template>
    <div class="brand-filter position-relative" ref="container">
        <label class="form-label small">Brand</label>
        <div class="position-relative">
            <input type="text" class="form-control form-control-sm pe-4" :value="displayValue"
                placeholder="Search Brand..." @input="handleInput" @focus="handleFocus"
                @keydown.arrow-down.prevent="navigateDown" @keydown.arrow-up.prevent="navigateUp"
                @keydown.enter.prevent="selectHighlighted" @keydown.escape="hideDropdown" autocomplete="off" />
            <span v-if="isLoading" class="position-absolute top-50 end-0 translate-middle-y me-2">
                <span class="spinner-border spinner-border-sm text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </span>
            </span>
            <span v-else-if="value" class="position-absolute top-50 end-0 translate-middle-y me-2" role="button"
                @click="clearSelection">
                <i class="fa fa-times text-muted"></i>
            </span>
            <span v-else class="position-absolute top-50 end-0 translate-middle-y me-2">
                <i class="fa fa-search text-muted"></i>
            </span>
        </div>

        <!-- Dropdown Results -->
        <div v-if="showDropdown" class="list-group position-absolute w-100 mt-1 shadow-lg overflow-auto bg-white"
            style="z-index: 1050; max-height: 200px;">

            <div v-if="filteredBrands.length === 0 && !isLoading"
                class="list-group-item list-group-item-light text-muted small">
                No brands found
            </div>

            <button v-for="(brand, index) in filteredBrands" :key="brand.id" @click="selectBrand(brand)" type="button"
                class="list-group-item list-group-item-action border-start-0 border-end-0 border-top-0 px-3 py-2 small"
                :class="{ 'active': index === highlightedIndex }">
                <div class="fw-semibold">{{ brand.name }}</div>
                <small v-if="brand.product_count !== undefined"
                    :class="index === highlightedIndex ? 'text-white-50' : 'text-muted'">
                    {{ brand.product_count }} products
                </small>
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'

const props = defineProps({
    value: {
        type: [String, Number],
        default: ''
    },
    brands: {
        type: Array,
        default: () => []
    }
})

const emit = defineEmits(['update:value'])

const container = ref(null)
const searchTerm = ref('')
const filteredBrands = ref([])
const showDropdown = ref(false)
const isLoading = ref(false)
const highlightedIndex = ref(0)
const searchTimeout = ref(null)
const isTyping = ref(false)

// Display value logic: show selected brand name or current search term
const displayValue = computed(() => {
    if (isTyping.value) return searchTerm.value

    if (props.value) {
        const selected = props.brands.find(b => b.id == props.value) ||
            filteredBrands.value.find(b => b.id == props.value)
        if (selected) return selected.name
    }

    return searchTerm.value
})

const handleInput = (event) => {
    searchTerm.value = event.target.value
    isTyping.value = true

    if (searchTimeout.value) {
        clearTimeout(searchTimeout.value)
    }

    searchTimeout.value = setTimeout(() => {
        fetchBrands(searchTerm.value)
        showDropdown.value = true
    }, 300)
}

const handleFocus = () => {
    isTyping.value = true
    searchTerm.value = ''
    fetchBrands('')
    showDropdown.value = true
}

const fetchBrands = async (query) => {
    isLoading.value = true
    try {
        const response = await fetch(`/api/v1/brands?query=${encodeURIComponent(query)}`, {
            headers: {
                'Accept': 'application/json'
            }
        })
        const data = await response.json()
        filteredBrands.value = data.data || []
        highlightedIndex.value = 0
    } catch (error) {
        console.error('Failed to search brands:', error)
        filteredBrands.value = []
    } finally {
        isLoading.value = false
    }
}

const selectBrand = (brand) => {
    emit('update:value', brand.id)
    searchTerm.value = brand.name
    isTyping.value = false
    showDropdown.value = false
}

const clearSelection = () => {
    emit('update:value', '')
    searchTerm.value = ''
    isTyping.value = false
    fetchBrands('')
}

const navigateDown = () => {
    if (highlightedIndex.value < filteredBrands.value.length - 1) {
        highlightedIndex.value++
    }
}

const navigateUp = () => {
    if (highlightedIndex.value > 0) {
        highlightedIndex.value--
    }
}

const selectHighlighted = () => {
    if (filteredBrands.value[highlightedIndex.value]) {
        selectBrand(filteredBrands.value[highlightedIndex.value])
    }
}

const hideDropdown = () => {
    showDropdown.value = false
    isTyping.value = false
}

const handleClickOutside = (event) => {
    if (container.value && !container.value.contains(event.target)) {
        hideDropdown()
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside)
    if (props.brands.length > 0) {
        filteredBrands.value = props.brands
    }
})

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside)
})
</script>
