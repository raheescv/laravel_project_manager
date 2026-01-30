<template>
    <div class="stock-check-list-page">
        <div class="card shadow-sm">
            <div class="card-header bg-light py-3">
                <div class="row mt-3">
                    <div class="col-md-6 d-flex flex-wrap gap-2 align-items-center mb-3 mb-md-0">
                        <button class="btn btn-primary d-flex align-items-center shadow-sm" @click="handleOpenCreateModal">
                            <i class="fa fa-plus me-2"></i>
                            Add New Stock Check
                        </button>
                        <div class="btn-group shadow-sm">
                            <button class="btn btn-danger btn-sm d-flex align-items-center" title="Delete Selected"
                                data-bs-toggle="tooltip" @click="handleBulkDelete" :disabled="selected.length === 0">
                                <i class="fa fa-trash me-md-1 fs-5"></i>
                                <span class="d-none d-md-inline">Delete</span>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="row g-2 align-items-center">
                            <div class="col-auto">
                                <label class="form-label mb-0 text-muted small fw-semibold">Show:</label>
                            </div>
                            <div class="col-auto">
                                <select v-model="limit" @change="fetchStockChecks" class="form-select form-select-sm border-secondary-subtle shadow-sm">
                                    <option value="10">10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                            </div>
                            <div class="col">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white border-secondary-subtle">
                                        <i class="fa fa-search"></i>
                                    </span>
                                    <input type="text" v-model="searchQuery" @input="handleSearchInput"
                                        placeholder="Search stock checks..."
                                        class="form-control border-secondary-subtle shadow-sm"
                                        autocomplete="off" aria-label="Search stock checks">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <StockCheckListTable
                    :stock-checks="stockChecks"
                    :loading="loading"
                    :selected="selected"
                    :select-all="selectAll"
                    @view="handleView"
                    @edit="handleEdit"
                    @delete="handleDelete"
                    @select="handleSelect"
                    @select-all="handleSelectAll"
                />
            </div>
            <div v-if="!loading && stockChecks.length > 0" class="p-3 border-top bg-light">
                <div class="row align-items-center">
                    <div class="col-lg-6 mb-2 mb-lg-0">
                        <p class="text-muted small mb-0">
                            Showing {{ stockChecks.length }} {{ stockChecks.length === 1 ? 'entry' : 'entries' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <CreateStockCheckModal :show="showModal" :branch-id="branchId" :stock-check-id="editingStockCheckId"
            @close="handleCloseModal" @created="handleStockCheckCreated" @updated="handleStockCheckUpdated" />
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import StockCheckListTable from '../Components/StockCheckListTable.vue'
import CreateStockCheckModal from '../Form/CreateStockCheckModal.vue'
import GetStockChecksAction from '../Apis/GetStockChecksAction.js'
import DeleteStockCheckAction from '../Apis/DeleteStockCheckAction.js'

const toast = useToast()
const getStockChecksAction = new GetStockChecksAction()
const deleteStockCheckAction = new DeleteStockCheckAction()

const stockChecks = ref([])
const loading = ref(false)
const searchQuery = ref('')
const showModal = ref(false)
const editingStockCheckId = ref(null)
const branchId = ref(null)
const limit = ref(100)
const selected = ref([])
const selectAll = ref(false)
let searchDebounceTimer = null

const fetchStockChecks = async () => {
    loading.value = true
    try {
        const params = {
            limit: limit.value
        }
        if (searchQuery.value) {
            params.search = searchQuery.value
        }

        const result = await getStockChecksAction.execute(params)
        if (result.success) {
            stockChecks.value = result.data
            // Reset selection when data changes
            selected.value = []
            selectAll.value = false
        } else {
            console.error(result.message)
            toast.error(result.message)
        }
    } catch (error) {
        toast.error('Failed to load stock checks')
        console.error(error)
    } finally {
        loading.value = false
    }
}

const handleOpenCreateModal = () => {
    editingStockCheckId.value = null
    showModal.value = true
}

const handleCloseModal = () => {
    showModal.value = false
    editingStockCheckId.value = null
}

const handleStockCheckCreated = (stockCheck) => {
    toast.success('Stock check created successfully')
    showModal.value = false
    editingStockCheckId.value = null
    fetchStockChecks()
    // Navigate to show page
    window.location.href = `/inventory/stock-check/${stockCheck.id}`
}

const handleSearchInput = () => {
    clearTimeout(searchDebounceTimer)
    searchDebounceTimer = setTimeout(() => {
    fetchStockChecks()
    }, 500)
}

const handleSelect = (id, checked) => {
    if (checked) {
        if (!selected.value.includes(id)) {
            selected.value.push(id)
        }
    } else {
        selected.value = selected.value.filter(item => item !== id)
    }
    selectAll.value = selected.value.length === stockChecks.value.length && stockChecks.value.length > 0
}

const handleSelectAll = (checked) => {
    selectAll.value = checked
    if (checked) {
        selected.value = stockChecks.value.map(item => item.id)
    } else {
        selected.value = []
    }
}

const handleBulkDelete = async () => {
    if (selected.value.length === 0) {
        return
    }

    if (!confirm(`Are you sure you want to delete ${selected.value.length} stock check(s)?`)) {
        return
    }

    try {
        const deletePromises = selected.value.map(id => deleteStockCheckAction.execute(id))
        const results = await Promise.all(deletePromises)

        const successCount = results.filter(r => r.success).length
        if (successCount > 0) {
            toast.success(`Successfully deleted ${successCount} stock check(s)`)
            selected.value = []
            selectAll.value = false
    fetchStockChecks()
        } else {
            toast.error('Failed to delete stock checks')
        }
    } catch (error) {
        toast.error('Failed to delete stock checks')
        console.error(error)
    }
}

const handleView = (id) => {
    window.location.href = `/inventory/stock-check/${id}`
}

const handleEdit = (id) => {
    editingStockCheckId.value = id
    showModal.value = true
}

const handleStockCheckUpdated = (stockCheck) => {
    toast.success('Stock check updated successfully')
    showModal.value = false
    editingStockCheckId.value = null
    fetchStockChecks()
}

const handleDelete = async (id) => {
    if (!confirm('Are you sure you want to delete this stock check?')) {
        return
    }

    try {
        const result = await deleteStockCheckAction.execute(id)
        if (result.success) {
            toast.success(result.message)
            fetchStockChecks()
        } else {
            toast.error(result.message || 'Failed to delete stock check')
        }
    } catch (error) {
        toast.error('Failed to delete stock check')
        console.error(error)
    }
}

onMounted(() => {
    // Get branch_id from data attribute
    const element = document.getElementById('stock-check-list')
    if (element) {
        const branchIdAttr = element.getAttribute('data-branch-id')
        if (branchIdAttr) {
            branchId.value = parseInt(branchIdAttr)
        }
    }
    fetchStockChecks()
})
</script>

<style scoped>
.stock-check-list-page {
    padding: 1rem;
}
</style>
