<template>
    <div class="opening-balance-page">
        <!-- Loading Overlay -->
        <LoadingOverlay :show="loading" :text="loadingText" />

        <!-- Product Search Bar -->
        <ProductSearchBar @product-selected="handleProductSelect" @barcode-scanned="handleBarcodeScan"
            @code-searched="handleCodeSearch" />

        <!-- Selected Items Table -->
        <SelectedItemsTable :items="selectedItems" @remove="handleRemoveItem" @update-quantity="handleUpdateQuantity"
            @update-cost="handleUpdateCost" />

        <!-- Opening Balance Form -->
        <OpeningBalanceForm v-if="selectedItems.length > 0" :items="selectedItems" :branch-id="branchId"
            @success="handleSuccess" @error="handleError" @loading="handleFormLoading" />
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import axios from 'axios'
import ProductSearchBar from '../../ProductSearchBar.vue'
import SelectedItemsTable from './SelectedItemsTable.vue'
import OpeningBalanceForm from './OpeningBalanceForm.vue'
import LoadingOverlay from '../../LoadingOverlay.vue'

// Constants
const LOADING_TEXTS = {
    PRODUCT_DETAILS: 'Loading product details...',
    SCANNING: 'Scanning barcode...',
    SAVING: 'Saving opening balance...',
    DEFAULT: 'Loading...',
}

// Composables
const toast = useToast()

// State
const selectedItems = ref([])
const loading = ref(false)
const loadingText = ref(LOADING_TEXTS.DEFAULT)
const branchId = ref(null)

// Initialize branch ID
onMounted(() => {
    const metaBranchId = document.querySelector('meta[name="branch-id"]')?.content
    branchId.value = metaBranchId ? parseInt(metaBranchId) : (window.branchId || null)
})

// Helper: Get item identifier
const getItemId = (item) => item.inventory_id

// Helper: Find item by ID
const findItemById = (itemId) => {
    return selectedItems.value.find(item => getItemId(item) === itemId)
}

// Helper: Check if product already exists
const isProductAlreadyAdded = (product) => {
    const inventoryId = product.inventory_id
    return selectedItems.value.some(item => getItemId(item) === inventoryId)
}

// Helper: Transform inventory data to product format
const transformInventoryToProduct = (inventoryData) => ({
    id: inventoryData.id,
    product_id: inventoryData.product_id,
    inventory_id: inventoryData.inventory_id,
    code: inventoryData.code,
    name: inventoryData.name,
    barcode: inventoryData.barcode,
    cost: inventoryData.cost || 0,
    mrp: inventoryData.mrp || 0,
})

// Helper: Fetch inventory data
const fetchInventoryData = async (params) => {
    const response = await axios.get('/inventory/product/getProduct', {
        params: { ...params, limit: 1 },
    })
    return response.data?.data?.[0] || null
}

// Helper: Create opening balance item
const createOpeningBalanceItem = (product, inventoryData) => ({
    id: product.inventory_id,
    product_id: product.product_id,
    inventory_id: product.inventory_id,
    code: product.code,
    name: product.name,
    barcode: inventoryData?.barcode || product.barcode,
    current_quantity: inventoryData?.quantity || 0,
    opening_quantity: inventoryData?.quantity || 0,
    cost: product.cost || inventoryData?.cost || 0,
    product_cost: product.cost,
    mrp: product.mrp,
    batch: inventoryData?.batch || 'General',
    branch_id: branchId.value || inventoryData?.branch_id,
})

// Helper: Add product with loading state
const addProductWithLoading = async (loadingMessage, fetchFn) => {
    loading.value = true
    loadingText.value = loadingMessage

    try {
        const inventoryData = await fetchFn()

        if (!inventoryData) {
            return null
        }

        const product = transformInventoryToProduct(inventoryData)

        if (isProductAlreadyAdded(product)) {
            toast.warning('Product already added to the list')
            return null
        }

        const newItem = createOpeningBalanceItem(product, inventoryData)
        selectedItems.value.push(newItem)
        toast.success(`Added: ${newItem.name}`)

        return newItem
    } catch (error) {
        console.error('Error fetching inventory:', error)
        toast.error('Error loading product inventory')
        return null
    } finally {
        loading.value = false
    }
}

// Handlers
const handleProductSelect = async (product) => {
    if (!product) return

    if (isProductAlreadyAdded(product)) {
        toast.warning('Product already added to the list')
        return
    }

    await addProductWithLoading( LOADING_TEXTS.PRODUCT_DETAILS, () => fetchInventoryData({ productCode: product.code }) )
}

const handleBarcodeScan = async (barcode) => {
    if (!barcode) return

    const result = await addProductWithLoading( LOADING_TEXTS.SCANNING, () => fetchInventoryData({ productBarcode: barcode }) )

    if (!result) {
        toast.warning(`Barcode not found: ${barcode}`)
    }
}

const handleCodeSearch = async (code) => {
    if (!code || code.length < 2) return

    await addProductWithLoading( LOADING_TEXTS.DEFAULT, () => fetchInventoryData({ productCode: code }) )
}

const handleRemoveItem = (itemId) => {
    selectedItems.value = selectedItems.value.filter(
        item => getItemId(item) !== itemId
    )
    toast.info('Item removed')
}

const handleUpdateQuantity = (itemId, quantity) => {
    const item = findItemById(itemId)
    if (item) {
        item.opening_quantity = quantity
    }
}

const handleUpdateCost = (itemId, cost) => {
    const item = findItemById(itemId)
    if (item) {
        item.cost = cost
        item.product_cost = cost
    }
}

const handleSuccess = (message) => {
    toast.success(message || 'Opening balance saved successfully!')
    setTimeout(() => {
        selectedItems.value = []
    }, 2000)
}

const handleError = (message) => {
    toast.error(message || 'Error saving opening balance')
}

const handleFormLoading = (isLoading) => {
    loading.value = isLoading
    loadingText.value = isLoading ? LOADING_TEXTS.SAVING : LOADING_TEXTS.DEFAULT
}
</script>

<style scoped>
/* Component-specific styles if needed */
</style>
