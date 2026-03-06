<template>
    <div v-if="show" class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ stockCheckId ? 'Edit Stock Check' : 'Create Stock Check' }}</h5>
                    <button type="button" class="btn-close" @click="$emit('close')"></button>
                </div>
                <div class="modal-body">
                    <CreateStockCheckForm ref="formRef" :branch-id="branchId" :stock-check-id="stockCheckId" @submit="handleSubmit" @cancel="$emit('close')" />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, nextTick } from 'vue'
import CreateStockCheckForm from './CreateStockCheckForm.vue'

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    branchId: {
        type: Number,
        default: null
    },
    stockCheckId: {
        type: Number,
        default: null
    }
})

const emit = defineEmits(['close', 'created', 'updated'])
const formRef = ref(null)

// Focus title input when modal opens
watch(() => props.show, async (isVisible) => {
    if (isVisible) {
        await nextTick()
        // Small delay to ensure modal animation completes
        setTimeout(() => {
            const titleInput = document.getElementById('title')
            if (titleInput) {
                titleInput.focus()
            }
        }, 100)
    }
})

const handleSubmit = async (formData) => {
    if (props.stockCheckId) {
        emit('updated', formData)
    } else {
        emit('created', formData)
    }
}
</script>

<style scoped>
.modal.show {
    display: block;
}
</style>
