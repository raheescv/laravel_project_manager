<template>
    <!-- Product Search Dropdown -->
    <div class="col-md-12 position-relative">
        <label class="form-label fw-semibold mb-2">Product Name</label>
        <div class="position-relative">
            <input v-model="searchTerm" type="text" class="form-control" :class="{ 'is-loading': isLoading }"
                placeholder="Search by product name or code..." @input="handleSearch" @focus="showDropdown = true"
                @keydown.arrow-down.prevent="navigateDown" @keydown.arrow-up.prevent="navigateUp"
                @keydown.enter.prevent="selectHighlighted" @keydown.escape="hideDropdown" autocomplete="off" />
            <span v-if="isLoading" class="position-absolute top-50 end-0 translate-middle-y me-2">
                <span class="spinner-border spinner-border-sm text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </span>
            </span>
            <span v-else class="position-absolute top-50 end-0 translate-middle-y me-2">
                <i class="fa fa-search text-muted"></i>
            </span>
        </div>

        <!-- Dropdown Results -->
        <div v-if="showDropdown && (filteredProducts.length > 0 || searchTerm)"
            class="position-absolute w-100 mt-1 bg-white border border-gray-300 rounded shadow-lg"
            style="z-index: 1050; max-height: 300px; overflow-y: auto; left: 0;">
            <div v-if="filteredProducts.length === 0 && searchTerm && !isLoading" class="px-3 py-2 text-muted">
                No products found
            </div>
            <div v-for="(product, index) in filteredProducts" :key="product.id" @click="selectProduct(product)"
                class="px-3 py-2 cursor-pointer border-bottom" :class="{
                    'bg-primary text-white': index === highlightedIndex,
                    'hover-bg-light': index !== highlightedIndex
                }" style="cursor: pointer;">
                <div class="fw-semibold">{{ product.name || product.product_name }}</div>
                <small class="text-muted">Code: {{ product.code || product.product_code }}</small>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import axios from 'axios'

const props = defineProps({
    onProductSelect: {
        type: Function,
        default: null
    }
})

const emit = defineEmits(['product-selected'])

const searchTerm = ref('')
const products = ref([])
const filteredProducts = ref([])
const selectedProduct = ref(null)
const showDropdown = ref(false)
const isLoading = ref(false)
const highlightedIndex = ref(0)
const searchTimeout = ref(null)

// Normalize product from getProduct API to match consistent format
const normalizeProduct = (product) => {
    // ProductSearchDropdown expects products from /inventory/product/getProduct
    // which returns: inventory_id, id (product.id), code, name, size, barcode, mrp, branch_id, branch_name, quantity
    // We normalize to: id (inventory.id), product_id (inventory.product_id), barcode, batch, quantity, name, code, size, mrp, type

    return {
        id: product.inventory_id, // inventory.id to match consistent format
        product_id: product.id, // product.id (the actual product ID)
        inventory_id: product.inventory_id, // Keep for reference
        barcode: product.barcode,
        batch: product.batch || 'General', // Not available from getProduct, but included for consistency
        quantity: product.quantity,
        name: product.name,
        product_name: product.name, // For compatibility (uses product.name || product.product_name)
        code: product.code,
        product_code: product.code, // For compatibility (uses product.code || product.product_code)
        size: product.size,
        mrp: product.mrp,
        type: 'product', // Default type to match consistent format
        branch_id: product.branch_id, // Additional field from getProduct
        branch_name: product.branch_name, // Additional field from getProduct
    }
}

// Load products from API
const loadProducts = async (query = '') => {
    if (!query || query.length < 2) {
        filteredProducts.value = []
        return
    }

    isLoading.value = true
    try {
        const response = await axios.get('/inventory/product/getProduct', {
            params: {
                productCode: query,
                limit: 10,
                page: 1,
            },
        })

        const items = response.data.data || []
        // Normalize all products to ensure consistent format
        const normalizedItems = items.map(normalizeProduct)
        products.value = normalizedItems
        filteredProducts.value = normalizedItems.slice(0, 10) // Limit to 10 results
        highlightedIndex.value = 0
    } catch (error) {
        console.error('Error loading products:', error)
        filteredProducts.value = []
    } finally {
        isLoading.value = false
    }
}

// Handle search input with debounce
const handleSearch = () => {
    if (searchTimeout.value) {
        clearTimeout(searchTimeout.value)
    }

    searchTimeout.value = setTimeout(() => {
        if (searchTerm.value.length >= 2) {
            loadProducts(searchTerm.value)
            showDropdown.value = true
        } else {
            filteredProducts.value = []
            showDropdown.value = false
        }
    }, 300)
}

// Select a product
const selectProduct = (product) => {
    selectedProduct.value = product
    searchTerm.value = product.name || product.product_name
    showDropdown.value = false

    // Emit event
    emit('product-selected', product)

    // Call callback if provided
    if (props.onProductSelect) {
        props.onProductSelect(product)
    }
}

// Clear selection
const clearSelection = () => {
    selectedProduct.value = null
    searchTerm.value = ''
    filteredProducts.value = []
    showDropdown.value = false
    emit('product-selected', null)
}

// Keyboard navigation
const navigateDown = () => {
    if (highlightedIndex.value < filteredProducts.value.length - 1) {
        highlightedIndex.value++
    }
}

const navigateUp = () => {
    if (highlightedIndex.value > 0) {
        highlightedIndex.value--
    }
}

const selectHighlighted = () => {
    if (filteredProducts.value[highlightedIndex.value]) {
        selectProduct(filteredProducts.value[highlightedIndex.value])
    }
}

const hideDropdown = () => {
    showDropdown.value = false
}

// Watch for search term changes
watch(searchTerm, (newValue) => {
    if (!newValue) {
        filteredProducts.value = []
    }
})

// Handle click outside
const handleClickOutside = (event) => {
    const element = event.target.closest('.product-search-dropdown')
    if (!element) {
        showDropdown.value = false
    }
}

// Lifecycle hooks
onMounted(() => {
    document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside)
    if (searchTimeout.value) {
        clearTimeout(searchTimeout.value)
    }
})
</script>

<style scoped>
.cursor-pointer {
    cursor: pointer;
}

.hover-bg-light:hover {
    background-color: #f8f9fa;
}

.is-loading {
    background-image: none;
}
</style>
