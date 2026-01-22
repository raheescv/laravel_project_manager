<template>
    <div class="card shadow-sm border-0">
        <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex gap-2">
                    <button type="button" @click="$emit('clear')" :disabled="isLoading"
                        class="btn btn-outline-secondary px-4 fw-bold">
                        Clear
                    </button>
                </div>
                <button type="button" @click="$emit('create-order')" :disabled="!canSubmit || isLoading"
                    class="btn btn-primary px-4 fw-bold">
                    <span v-if="isLoading">Processing...</span>
                    <span v-else>Create Order</span>
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    isLoading: Boolean,
    canSubmit: Boolean,
})

defineEmits(['clear', 'create-order', 'payment'])
</script>
