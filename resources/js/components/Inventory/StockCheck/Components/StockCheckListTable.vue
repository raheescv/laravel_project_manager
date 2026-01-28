<template>
    <div class="stock-check-list-table">
        <div v-if="loading" class="text-center p-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>

        <div v-else class="table-responsive">
            <table class="table table-hover align-middle border-bottom mb-0">
                <thead class="table-light text-capitalize">
                    <tr>
                        <th class="border-0">
                            <div class="form-check ms-1">
                                <input class="form-check-input" type="checkbox" :checked="selectAll"
                                    @change="$emit('select-all', $event.target.checked)" id="selectAll" />
                                <label class="form-check-label" for="selectAll">
                                    <div class="d-flex align-items-center">
                                        <i class="fa fa-hashtag me-2 text-secondary small"></i>
                                        ID
                                    </div>
                                </label>
                            </div>
                        </th>
                        <th class="border-0">
                            <div class="d-flex align-items-center">
                                <i class="fa fa-tag me-2 text-secondary small"></i>
                                Title
                            </div>
                        </th>
                        <th class="border-0">
                            <div class="d-flex align-items-center">
                                <i class="fa fa-building me-2 text-secondary small"></i>
                                Branch
                            </div>
                        </th>
                        <th class="border-0">
                            <div class="d-flex align-items-center">
                                <i class="fa fa-calendar me-2 text-secondary small"></i>
                                Date
                            </div>
                        </th>
                        <th class="border-0">
                            <div class="d-flex align-items-center">
                                <i class="fa fa-info-circle me-2 text-secondary small"></i>
                                Status
                            </div>
                        </th>
                        <th class="border-0">
                            <div class="d-flex align-items-center">
                                <i class="fa fa-user me-2 text-secondary small"></i>
                                Created By
                            </div>
                        </th>
                        <th class="border-0 text-end">
                            <div class="d-flex align-items-center justify-content-end">
                                <i class="fa fa-cog me-2 text-secondary small"></i>
                                Actions
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="stockCheck in stockChecks" :key="stockCheck.id" class="align-middle">
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="form-check ms-1">
                                    <input class="form-check-input" type="checkbox" :value="stockCheck.id"
                                        :checked="selected.includes(stockCheck.id)"
                                        @change="$emit('select', stockCheck.id, $event.target.checked)"
                                        :id="`stock-check-${stockCheck.id}`" />
                                </div>
                                <span class="badge bg-secondary rounded-pill">{{ stockCheck.id }}</span>
                            </div>
                        </td>
                        <td>
                            <a href="#" @click.prevent="$emit('view', stockCheck.id)"
                                class="text-decoration-none fw-semibold link-primary d-block">
                                {{ stockCheck.title }}
                            </a>
                        </td>
                        <td>
                            <span class="fw-medium text-primary">{{ stockCheck.branch?.name || '-' }}</span>
                        </td>
                        <td>
                            <span class="text-secondary">{{ formatDate(stockCheck.date) }}</span>
                        </td>
                        <td>
                            <span :class="getStatusClass(stockCheck.status)">
                                {{ formatStatus(stockCheck.status) }}
                            </span>
                        </td>
                        <td>
                            <span class="text-secondary">{{ stockCheck.created_by?.name || '-' }}</span>
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm" role="group">
                                <button class="btn btn-outline-primary" @click="$emit('view', stockCheck.id)"
                                    title="View" data-bs-toggle="tooltip">
                                    <i class="fa fa-eye"></i>
                                </button>
                                <button class="btn btn-outline-secondary" @click="$emit('edit', stockCheck.id)"
                                    title="Edit" data-bs-toggle="tooltip">
                                    <i class="fa fa-edit"></i>
                                </button>
                                <button class="btn btn-outline-danger" @click="$emit('delete', stockCheck.id)"
                                    title="Delete" data-bs-toggle="tooltip">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="stockChecks.length === 0">
                        <td colspan="7" class="text-center py-5">
                            <div class="text-muted my-4">
                                <i
                                    class="fa fa-exclamation-circle fs-1 d-block mb-3 text-secondary-emphasis opacity-50"></i>
                                <h5 class="fw-semibold mb-2">No Stock Checks Found</h5>
                                <p class="mb-0">Try adjusting your search or filter criteria</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { formatDate } from '../../../../utils/createVueApp.js'

defineProps({
    stockChecks: {
        type: Array,
        default: () => []
    },
    loading: {
        type: Boolean,
        default: false
    },
    selected: {
        type: Array,
        default: () => []
    },
    selectAll: {
        type: Boolean,
        default: false
    }
})

defineEmits(['view', 'edit', 'delete', 'select', 'select-all'])

const getStatusClass = (status) => {
    const classes = {
        pending: 'badge bg-warning',
        completed: 'badge bg-success',
        cancelled: 'badge bg-danger'
    }
    return classes[status] || 'badge bg-secondary'
}

const formatStatus = (status) => {
    if (!status) return '-'
    return status.charAt(0).toUpperCase() + status.slice(1)
}
</script>

<style scoped>
.stock-check-list-table {
    width: 100%;
}
</style>
