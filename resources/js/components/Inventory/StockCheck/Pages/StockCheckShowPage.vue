<template>
    <div class="stock-check-show-page">
        <LoadingOverlay :show="loading" :text="loadingText" />

        <div class="page-container">
            <StockCheckHeader :stock-check="stockCheck" />

            <section class="section-card section-filters" aria-label="Filters">
                <StockCheckFilters :filters="filters" :categories="categories" :brands="brands"
                    @filter-changed="handleFilterChanged" />
            </section>

            <section class="section-card section-scanner" aria-label="Barcode scanner">
                <StockCheckBarcodeScanner :stock-check-id="stockCheckId" @scan-success="handleScanSuccess"
                    @scan-error="handleScanError" />
            </section>

            <section class="section-card section-table" aria-label="Stock check items">
                <div class="section-header">
                    <h2 class="section-title">Items</h2>
                    <span v-if="pagination.total > 0" class="section-badge">{{ pagination.total }} item{{ pagination.total !== 1 ? 's' : '' }}</span>
                </div>
                <StockCheckItemsTable :items="items" :loading="itemsLoading" :filters="filters" :pagination="pagination"
                    @sort="handleSort" @page-change="handlePageChange" @update-quantity="handleUpdateQuantity"
                    @status-change-request="handleStatusChangeRequest" />
            </section>

            <div class="action-bar">
                <div class="action-bar-inner">
                    <SaveStockCheckButton :loading="saving" :disabled="items.length === 0" @save="handleSave" />
                </div>
            </div>
        </div>

        <StatusChangeConfirmationModal :show="showStatusConfirmModal"
            :product-name="statusChangeData?.productName || ''"
            :current-status="statusChangeData?.currentStatus || 'pending'"
            :new-status="statusChangeData?.newStatus || 'completed'" @confirm="handleConfirmStatusChange"
            @cancel="handleCancelStatusChange" />
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useToast } from 'vue-toastification'
import StockCheckHeader from '../Components/StockCheckHeader.vue'
import StockCheckFilters from '../Components/StockCheckFilters.vue'
import StockCheckBarcodeScanner from '../Form/StockCheckBarcodeScanner.vue'
import StockCheckItemsTable from '../Components/StockCheckItemsTable.vue'
import SaveStockCheckButton from '../Form/SaveStockCheckButton.vue'
import LoadingOverlay from '../../../LoadingOverlay.vue'
import StatusChangeConfirmationModal from '../Components/StatusChangeConfirmationModal.vue'
import GetStockCheckAction from '../Apis/GetStockCheckAction.js'
import GetStockCheckItemsAction from '../Apis/GetStockCheckItemsAction.js'
import UpdateStockCheckAction from '../Apis/UpdateStockCheckAction.js'

const toast = useToast()
const getStockCheckAction = new GetStockCheckAction()
const getStockCheckItemsAction = new GetStockCheckItemsAction()
const updateStockCheckAction = new UpdateStockCheckAction()

const stockCheckId = ref(null)
const stockCheck = ref({})
const items = ref([])
const categories = ref([])
const brands = ref([])
const loading = ref(false)
const itemsLoading = ref(false)
const saving = ref(false)
const loadingText = ref('Loading...')
const showStatusConfirmModal = ref(false)
const statusChangeData = ref(null)

const filters = ref({
    category_id: '',
    brand_id: '',
    recorded_qty_condition: '',
    status: 'pending',
    difference_condition: '',
    search: '',
    per_page: 10,
    page: 1,
    sort_field: 'stock_check_items.updated_at',
    sort_direction: 'desc'
})

const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 20,
    total: 0
})

const initializeStockCheckId = () => {
    const metaId = document.querySelector('meta[name="stock-check-id"]')?.content
    if (metaId) {
        stockCheckId.value = parseInt(metaId)
    } else {
        const pathParts = window.location.pathname.split('/')
        const idIndex = pathParts.indexOf('stock-check')
        if (idIndex !== -1 && pathParts[idIndex + 1]) {
            stockCheckId.value = parseInt(pathParts[idIndex + 1])
        }
    }
}

const fetchStockCheck = async () => {
    if (!stockCheckId.value) return

    loading.value = true
    loadingText.value = 'Loading stock check...'
    try {
        const result = await getStockCheckAction.execute(stockCheckId.value)
        if (result.success) {
            stockCheck.value = result.data
        } else {
            toast.error(result.message)
        }
    } catch (error) {
        toast.error('Failed to load stock check')
        console.error(error)
    } finally {
        loading.value = false
    }
}

const fetchCategories = async () => {
    try {
        const response = await fetch('/api/v1/categories', {
            headers: {
                'Accept': 'application/json'
            }
        })
        const data = await response.json()
        categories.value = data?.data || []
    } catch (error) {
        console.error('Failed to fetch categories:', error)
        categories.value = []
    }
}

const fetchBrands = async () => {
    try {
        const response = await fetch('/api/v1/brands', {
            headers: {
                'Accept': 'application/json'
            }
        })
        const data = await response.json()
        brands.value = data?.data || []
    } catch (error) {
        console.error('Failed to fetch brands:', error)
        brands.value = []
    }
}

const fetchItems = async () => {
    if (!stockCheckId.value) return

    itemsLoading.value = true
    try {
        const params = {
            ...filters.value
        }

        const result = await getStockCheckItemsAction.execute(stockCheckId.value, params)

        if (result.success) {
            const data = result.data
            if (data.data) {
                // Replace items only after successful fetch to prevent jumping
                items.value = data.data
                pagination.value = {
                    current_page: data.current_page,
                    last_page: data.last_page,
                    per_page: data.per_page,
                    total: data.total
                }
            } else {
                items.value = Array.isArray(data) ? data : []
            }
        } else {
            toast.error(result.message)
            // Only clear items on error, not during loading
            items.value = []
        }
    } catch (error) {
        toast.error('Failed to load items')
        console.error(error)
        // Only clear items on error, not during loading
        items.value = []
    } finally {
        itemsLoading.value = false
    }
}

const handleFilterChanged = (newFilters) => {
    filters.value = { ...filters.value, ...newFilters, page: 1 }
    fetchItems()
}

const handleSort = (field, direction) => {
    filters.value.sort_field = field
    filters.value.sort_direction = direction
    fetchItems()
}

const handlePageChange = (page) => {
    filters.value.page = page
    fetchItems()
}

const handleUpdateQuantity = (itemId, quantity) => {
    const item = items.value.find(i => i.id === itemId)
    if (item) {
        item.physical_quantity = quantity
        item.difference = item.physical_quantity - item.recorded_quantity
    }
}

const handleStatusChangeRequest = (data) => {
    statusChangeData.value = data
    showStatusConfirmModal.value = true
}

const handleConfirmStatusChange = async () => {
    if (!statusChangeData.value || !stockCheckId.value) {
        showStatusConfirmModal.value = false
        statusChangeData.value = null
        return
    }

    try {
        // Update the item status instantly in the database
        const item = items.value.find(i => i.id === statusChangeData.value.itemId)
        if (!item) {
            toast.error('Item not found')
            showStatusConfirmModal.value = false
            statusChangeData.value = null
            return
        }

        const result = await updateStockCheckAction.execute(stockCheckId.value, [{
            id: statusChangeData.value.itemId,
            physical_quantity: item.physical_quantity,
            status: statusChangeData.value.newStatus
        }])

        if (result.success) {
            // Update the item status locally for immediate UI feedback
            item.status = statusChangeData.value.newStatus
            toast.success(`Status changed to ${statusChangeData.value.newStatus === 'completed' ? 'Completed' : 'Pending'}`)
        } else {
            toast.error(result.message || 'Failed to update status')
        }
    } catch (error) {
        toast.error(error.message || 'Failed to update status')
        console.error(error)
    } finally {
        showStatusConfirmModal.value = false
        statusChangeData.value = null
    }
}

const handleCancelStatusChange = () => {
    showStatusConfirmModal.value = false
    statusChangeData.value = null
}

const handleScanSuccess = (item) => {
    // Always refresh the items table to get the latest data from the server
    fetchItems()
}

const handleScanError = (error) => {
    toast.error(error || 'Barcode scan failed')
}

const handleSave = async () => {
    saving.value = true
    try {
        const itemsToUpdate = items.value.map(item => ({
            id: item.id,
            physical_quantity: item.physical_quantity,
            status: item.status
        }))

        const result = await updateStockCheckAction.execute(stockCheckId.value, itemsToUpdate)

        if (result.success) {
            toast.success('Stock check updated successfully')
            fetchItems()
        } else {
            toast.error(result.message || 'Failed to update stock check')
        }
    } catch (error) {
        toast.error(error.message || 'Failed to update stock check')
        console.error(error)
    } finally {
        saving.value = false
    }
}

onMounted(() => {
    initializeStockCheckId()
    fetchStockCheck()
    fetchItems()
    fetchCategories()
    fetchBrands()
})
</script>

<style scoped>
.stock-check-show-page {
    --page-bg: linear-gradient(165deg, #f0f4f8 0%, #e2e8f0 35%, #f8fafc 100%);
    --section-bg: #ffffff;
    --section-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
    --section-shadow-hover: 0 4px 12px rgba(0, 0, 0, 0.08);
    --border-subtle: rgba(0, 0, 0, 0.06);
    --accent: #0d6efd;
    --accent-muted: rgba(13, 110, 253, 0.12);
    min-height: 100vh;
    padding: 1.5rem 1rem 5rem;
    background: var(--page-bg);
    background-attachment: fixed;
}

.page-container {
    max-width: 1400px;
    margin: 0 auto;
}

.section-card {
    background: var(--section-bg);
    border-radius: 12px;
    box-shadow: var(--section-shadow);
    border: 1px solid var(--border-subtle);
    margin-bottom: 1.25rem;
    overflow: hidden;
    transition: box-shadow 0.2s ease, transform 0.2s ease;
}

.section-card:hover {
    box-shadow: var(--section-shadow-hover);
}

.section-card :deep(.card) {
    border: none !important;
    box-shadow: none !important;
    margin-bottom: 0 !important;
    border-radius: 0 !important;
}

.section-table :deep(.stock-check-items-table .card) {
    border: none !important;
    box-shadow: none !important;
    background: transparent !important;
}

.section-filters :deep(.card-body),
.section-scanner :deep(.card-body) {
    padding: 1rem 1.25rem;
}

.section-table {
    padding: 1.25rem 1.25rem 1rem;
}

.section-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid var(--border-subtle);
}

.section-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
    letter-spacing: -0.01em;
}

.section-badge {
    font-size: 0.8125rem;
    font-weight: 500;
    color: #64748b;
    background: var(--accent-muted);
    color: var(--accent);
    padding: 0.25rem 0.625rem;
    border-radius: 999px;
}

.action-bar {
    position: sticky;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 1rem 0 0;
    margin: 0.5rem 0 -1rem;
    background: linear-gradient(to top, rgba(240, 244, 248, 0.98) 60%, transparent);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}

.action-bar-inner {
    max-width: 1400px;
    margin: 0 auto;
    display: flex;
    justify-content: flex-end;
}

.action-bar-inner :deep(button) {
    border-radius: 10px;
    font-weight: 600;
    padding: 0.625rem 1.5rem;
    box-shadow: 0 2px 8px rgba(13, 110, 253, 0.25);
    transition: transform 0.15s ease, box-shadow 0.2s ease;
}

.action-bar-inner :deep(button:hover:not(:disabled)) {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.35);
}

.action-bar-inner :deep(button:active:not(:disabled)) {
    transform: translateY(0);
}

@media (max-width: 576px) {
    .stock-check-show-page {
        padding: 0.75rem 0.5rem 4.5rem;
    }

    .section-table {
        padding: 1rem;
    }

    .section-title {
        font-size: 1rem;
    }
}
</style>
