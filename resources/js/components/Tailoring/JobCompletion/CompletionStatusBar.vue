<template>
    <div class="alert alert-light border shadow-sm p-3 mb-0 text-center">
        <div class="small fw-bold text-dark">
            <span v-if="recordCount > 0" class="badge bg-secondary me-3">{{ recordCount }} record(s)</span>
            <span v-else class="badge bg-secondary me-3">No records</span>
            
            <span v-if="completionStatus">
                Status: <span class="badge bg-primary text-uppercase">{{ completionStatus }}</span>
            </span>
        </div>
    </div>
</template>

<script setup>
defineProps({
    recordCount: {
        type: Number,
        default: 0
    },
    completionStatus: {
        type: String,
        default: null
    }
})
</script>
