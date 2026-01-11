<template>
    <div class="card shadow-sm border mb-4 border-primary rounded">
        <div class="card-body p-4">
            <div class="row align-items-center">
                <div class="col-md-7">
                    <h3 class="mb-2 fw-bold text-dark text-capitalize">{{ stockCheck.title || 'N/A' }}</h3>
                    <p class="text-muted mb-3 small">{{ stockCheck.description || 'No description' }}</p>
                    <div class="d-flex flex-wrap gap-4">
                        <div>
                            <span class="text-muted small d-block mb-1">Branch</span>
                            <span class="fw-medium text-dark">{{ stockCheck.branch?.name || 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="text-muted small d-block mb-1">Created by</span>
                            <span class="fw-medium text-dark">{{ stockCheck.created_by?.name || 'N/A' }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-5 mt-3 mt-md-0">
                    <div class="d-flex flex-column align-items-md-end align-items-start gap-3">
                        <div class="text-md-end">
                            <div class="text-muted small text-uppercase mb-1 fw-normal">Date</div>
                            <div class="fw-semibold text-dark">{{ formatDate(stockCheck.date) }}</div>
                        </div>
                        <div class="text-md-end">
                            <div class="text-muted small text-uppercase mb-2 fw-normal">Status</div>
                            <span :class="getStatusClass(stockCheck.status)" class="px-3 py-2 text-uppercase">
                                {{ stockCheck.status }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    stockCheck: {
        type: Object,
        default: () => ({})
    }
})

const formatDate = (date) => {
    if (!date) return 'N/A'
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    })
}

const getStatusClass = (status) => {
    const classes = {
        pending: 'badge bg-warning text-dark',
        completed: 'badge bg-success',
        cancelled: 'badge bg-danger'
    }
    return classes[status] || 'badge bg-secondary'
}
</script>

