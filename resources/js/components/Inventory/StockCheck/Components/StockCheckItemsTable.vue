<template>
    <div class="stock-check-items-table">
        <!-- Show spinner only when loading and no items exist (initial load) -->
        <div v-if="loading && items.length === 0" class="text-center p-4">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>

        <!-- Show empty state only when not loading and no items -->
        <div v-else-if="!loading && items.length === 0" class="text-center p-4">
            <p class="text-muted">No items found</p>
        </div>

        <!-- Show table with items, with loading overlay if loading -->
        <div v-else class="card shadow-sm border mb-4 border-primary rounded position-relative">
            <!-- Loading overlay that doesn't hide the table -->
            <div v-if="loading" class="loading-overlay">
                <div class="d-flex justify-content-center align-items-center h-100">
                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>

            <!-- Desktop Table View -->
            <div class="d-none d-lg-block">
                <table class="table table-sm table-striped table-hover">
                    <StockCheckItemsTableHeader :sort-field="filters.sort_field" :sort-direction="filters.sort_direction"
                        @sort="handleSort" />
                    <tbody>
                        <StockCheckItemsTableRow v-for="(item, index) in items" :key="item.id" :item="item" :index="index"
                            @update-quantity="handleUpdateQuantity" @status-change-request="handleStatusChangeRequest" />
                    </tbody>
                </table>
            </div>

            <!-- Mobile Card View -->
            <div class="d-lg-none">
                <div class="row g-3">
                    <StockCheckItemsTableCard v-for="(item, index) in items" :key="item.id" :item="item" :index="index"
                        @update-quantity="handleUpdateQuantity" @status-change-request="handleStatusChangeRequest" />
                </div>
            </div>

            <StockCheckItemsTablePagination :current-page="pagination.current_page" :total-pages="pagination.last_page"
                :total-items="pagination.total" :items-per-page="pagination.per_page" @page-change="handlePageChange" />
        </div>
    </div>
</template>

<script setup>
import StockCheckItemsTableHeader from './StockCheckItemsTableHeader.vue'
import StockCheckItemsTableRow from './StockCheckItemsTableRow.vue'
import StockCheckItemsTableCard from './StockCheckItemsTableCard.vue'
import StockCheckItemsTablePagination from './StockCheckItemsTablePagination.vue'

const props = defineProps({
    items: {
        type: Array,
        default: () => []
    },
    loading: {
        type: Boolean,
        default: false
    },
    filters: {
        type: Object,
        default: () => ({})
    },
    pagination: {
        type: Object,
        default: () => ({
            current_page: 1,
            last_page: 1,
            per_page: 20,
            total: 0
        })
    }
})

const emit = defineEmits(['sort', 'page-change', 'update-quantity', 'status-change-request'])

const handleSort = (field) => {
    const direction = props.filters.sort_field === field && props.filters.sort_direction === 'asc' ? 'desc' : 'asc'
    emit('sort', field, direction)
}

const handlePageChange = (page) => {
    emit('page-change', page)
}

const handleUpdateQuantity = (itemId, quantity) => {
    emit('update-quantity', itemId, quantity)
}

const handleStatusChangeRequest = (data) => {
    emit('status-change-request', data)
}
</script>

<style scoped>
.stock-check-items-table {
    width: 100%;
}

.loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(255, 255, 255, 0.7);
    z-index: 10;
    border-radius: 0.375rem;
}

/* Ensure table is scrollable on medium screens if needed */
@media (max-width: 1199px) {
    .stock-check-items-table .table {
        font-size: 0.875rem;
    }
}

@media (max-width: 991px) {
    .stock-check-items-table .table {
        font-size: 0.8rem;
    }
}
</style>
