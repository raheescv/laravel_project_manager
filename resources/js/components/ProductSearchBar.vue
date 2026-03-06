<template>
    <div class="product-search-bar">
        <div class="card mb-3">
            <div class="card-body bg-light">
                <h5 class="card-title mb-3">Search Product</h5>
                <div class="row g-3">
                    <!-- Product Name Dropdown -->
                    <div class="col-md-4 position-relative">
                        <label class="form-label fw-semibold mb-2">Product Name</label>
                        <div class="position-relative">
                            <input v-model="productNameSearchTerm" type="text" class="form-control"
                                :class="{ 'is-loading': isDropdownLoading }"
                                placeholder="Search by product name or code..." @input="handleNameSearch"
                                @focus="showNameDropdown = true" @keydown.arrow-down.prevent="navigateDown"
                                @keydown.arrow-up.prevent="navigateUp" @keydown.enter.prevent="selectHighlighted"
                                @keydown.escape="hideNameDropdown" autocomplete="off" />
                            <span v-if="isDropdownLoading"
                                class="position-absolute top-50 end-0 translate-middle-y me-2">
                                <span class="spinner-border spinner-border-sm text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </span>
                            </span>
                            <span v-else class="position-absolute top-50 end-0 translate-middle-y me-2">
                                <i class="fa fa-search text-muted"></i>
                            </span>
                        </div>

                        <!-- Dropdown Results -->
                        <div v-if="showNameDropdown && (nameFilteredProducts.length > 0 || productNameSearchTerm)"
                            class="position-absolute w-100 mt-1 bg-white border border-gray-300 rounded shadow-lg"
                            style="z-index: 1050; max-height: 300px; overflow-y: auto; left: 0;">
                            <div v-if="nameFilteredProducts.length === 0 && productNameSearchTerm && !isDropdownLoading"
                                class="px-3 py-2 text-muted">
                                No products found
                            </div>
                            <div v-for="(product, index) in nameFilteredProducts" :key="product.id"
                                @click="selectNameProduct(product)" class="px-3 py-2 cursor-pointer border-bottom"
                                :class="{ 'bg-primary text-white': index === nameHighlightedIndex, 'hover-bg-light': index !== nameHighlightedIndex }"
                                style="cursor: pointer;">
                                <div class="fw-semibold">{{ product.name }}</div>
                                <small class="text-muted">Code: {{ product.code }}</small> |
                                <small class="text-muted">Barcode: {{ product.barcode }}</small>
                            </div>
                        </div>
                    </div>

                    <!-- Product Code Search -->
                    <div class="col-md-4">
                        <label class="form-label fw-semibold mb-2">Product Code</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="fa fa-barcode text-muted"></i>
                            </span>
                            <input class="form-control border-start-0" type="text" v-model="productCode"
                                @input="handleCodeSearch" placeholder="Enter product code..." />
                        </div>
                    </div>

                    <!-- Barcode Search -->
                    <div class="col-md-4">
                        <label class="form-label fw-semibold mb-2">Barcode</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="fa fa-barcode text-muted"></i>
                            </span>
                            <input class="form-control border-start-0" type="text" v-model="productBarcode"
                                @input="handleBarcodeSearch" placeholder="Enter or scan barcode..." />
                            <button class="btn btn-outline-primary" type="button" @click="openScanner"
                                title="Scan Barcode">
                                <i class="fa fa-camera"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Barcode Scanner Component -->
        <BarcodeScanner
            :isOpen="scannerOpen"
            @barcode-scanned="handleScannedBarcode"
            @close="closeScanner"
        />
    </div>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue'
import axios from 'axios'
import BarcodeScanner from './BarcodeScanner.vue'

const props = defineProps({
    onProductSelect: {
        type: Function,
        default: null
    },
    onBarcodeScan: {
        type: Function,
        default: null
    },
    onCodeSearch: {
        type: Function,
        default: null
    },
    onNameSearch: {
        type: Function,
        default: null
    }
})

const emit = defineEmits(['product-selected', 'barcode-scanned', 'code-searched', 'name-searched'])

const productName = ref('')
const productCode = ref('')
const productBarcode = ref('')
const scannerOpen = ref(false)

// Product Name Dropdown state
const productNameSearchTerm = ref('')
const nameProducts = ref([])
const nameFilteredProducts = ref([])
const showNameDropdown = ref(false)
const isDropdownLoading = ref(false)
const nameHighlightedIndex = ref(0)
const nameSearchTimeout = ref(null)

const handleProductSelect = (product) => {
    if (product) {
        emit('product-selected', product)
        if (props.onProductSelect) {
            props.onProductSelect(product)
        }
        // Clear other fields
        productCode.value = ''
        productBarcode.value = ''
        productName.value = ''
        productNameSearchTerm.value = ''
        showNameDropdown.value = false
    }
}

// Product Name Dropdown functions
const loadNameProducts = async (query = '') => {
    if (!query || query.length < 2) {
        nameFilteredProducts.value = []
        return
    }

    isDropdownLoading.value = true
    try {
        const response = await axios.get('/inventory/product/getProduct', {
            params: {
                productName: query,
                limit: 10,
                page: 1,
            },
        })

        const items = response.data.data || []
        // Normalize all products to ensure consistent format
        const normalizedItems = items.map(normalizeProduct)
        nameProducts.value = normalizedItems
        nameFilteredProducts.value = normalizedItems.slice(0, 10) // Limit to 10 results
        nameHighlightedIndex.value = 0
    } catch (error) {
        console.error('Error loading products:', error)
        nameFilteredProducts.value = []
    } finally {
        isDropdownLoading.value = false
    }
}

// Handle search input with debounce
const handleNameSearch = () => {
    if (nameSearchTimeout.value) {
        clearTimeout(nameSearchTimeout.value)
    }

    nameSearchTimeout.value = setTimeout(() => {
        if (productNameSearchTerm.value.length >= 2) {
            loadNameProducts(productNameSearchTerm.value)
            showNameDropdown.value = true
        } else {
            nameFilteredProducts.value = []
            showNameDropdown.value = false
        }
    }, 300)
}

// Select a product from name dropdown
const selectNameProduct = (product) => {
    productNameSearchTerm.value = product.name || product.product_name
    showNameDropdown.value = false
    handleProductSelect(product)
}

// Keyboard navigation for name dropdown
const navigateDown = () => {
    if (nameHighlightedIndex.value < nameFilteredProducts.value.length - 1) {
        nameHighlightedIndex.value++
    }
}

const navigateUp = () => {
    if (nameHighlightedIndex.value > 0) {
        nameHighlightedIndex.value--
    }
}

const selectHighlighted = () => {
    if (nameFilteredProducts.value[nameHighlightedIndex.value]) {
        selectNameProduct(nameFilteredProducts.value[nameHighlightedIndex.value])
    }
}

const hideNameDropdown = () => {
    showNameDropdown.value = false
}

// Handle click outside for name dropdown
const handleNameClickOutside = (event) => {
    const dropdownContainer = event.target.closest('.col-md-4.position-relative')
    if (!dropdownContainer) {
        showNameDropdown.value = false
    }
}

const handleCodeSearch = async () => {
    const code = productCode.value.trim()
    if (!code || code.length < 2) {
        return
    }

    try {
        const response = await axios.get('/inventory/product/getProduct', {
            params: {
                productCode: code,
                limit: 1,
                page: 1,
            },
        })

        const products = response.data.data || []
        if (products.length > 0) {
            const product = normalizeProduct(products[0])
            handleProductSelect(product)
        }

        emit('code-searched', code)
        if (props.onCodeSearch) {
            props.onCodeSearch(code)
        }
    } catch (error) {
        console.error('Error searching by code:', error)
        emit('code-searched', code)
        if (props.onCodeSearch) {
            props.onCodeSearch(code)
        }
    }
}

const handleBarcodeSearch = async () => {
    const barcode = productBarcode.value.trim()
    if (!barcode) {
        return
    }

    try {
        const response = await axios.get('/inventory/product/getProduct', {
            params: {
                productBarcode: barcode,
                limit: 1,
                page: 1,
            },
        })

        const products = response.data.data || []
        if (products.length > 0) {
            const product = normalizeProduct(products[0])
            handleProductSelect(product)
            beep() // Play beep sound on successful barcode scan
        }

        emit('barcode-scanned', barcode)
        if (props.onBarcodeScan) {
            props.onBarcodeScan(barcode)
        }
    } catch (error) {
        console.error('Error searching by barcode:', error)
        emit('barcode-scanned', barcode)
        if (props.onBarcodeScan) {
            props.onBarcodeScan(barcode)
        }
    }
}

const normalizeProduct = (product) => {
    return {
        id: product.inventory_id,
        quantity: product.quantity,
        name: product.name,
        code: product.code,
        barcode: product.barcode,
    }
}

const openScanner = () => {
    scannerOpen.value = true
}

const closeScanner = () => {
    scannerOpen.value = false
}

const handleScannedBarcode = ({ code, product }) => {
    if (product) {
        handleProductSelect(product)
        productBarcode.value = code
    }

    emit('barcode-scanned', code)
    if (props.onBarcodeScan) {
        props.onBarcodeScan(code)
    }
}

// Watch for search term changes
watch(productNameSearchTerm, (newValue) => {
    if (!newValue) {
        nameFilteredProducts.value = []
    }
})

// Lifecycle hooks
onMounted(() => {
    document.addEventListener('click', handleNameClickOutside)
})

onUnmounted(() => {
    document.removeEventListener('click', handleNameClickOutside)
    if (nameSearchTimeout.value) {
        clearTimeout(nameSearchTimeout.value)
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
