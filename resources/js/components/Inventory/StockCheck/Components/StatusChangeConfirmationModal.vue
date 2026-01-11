<template>
    <Teleport to="body">
        <template v-if="show">
            <!-- Backdrop -->
            <div class="modal-backdrop fade show" style="z-index: 1050;" @click="handleCancel"></div>

            <!-- Modal -->
            <div class="modal fade show" style="display: block; z-index: 1055;" tabindex="-1" role="dialog"
                aria-modal="true" @click.self="handleCancel">
                <div class="modal-dialog modal-dialog-centered" role="document" @click.stop>
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fa fa-exclamation-circle text-primary me-2"></i>
                                Are you sure to change?
                            </h5>
                            <button type="button" class="btn-close" @click="handleCancel" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>
                                <strong>{{ productName }}</strong>
                                <br>
                                This will change the status from <strong class="text-primary">{{ currentStatusLabel
                                    }}</strong> to <strong class="text-success">{{ newStatusLabel }}</strong>.
                            </p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-primary" @click="handleConfirm">
                                <i class="fa fa-check me-1"></i>
                                Yes, {{ newStatusLabel }} it!
                            </button>
                            <button type="button" class="btn btn-danger" @click="handleCancel">
                                <i class="fa fa-times me-1"></i>
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </Teleport>
</template>

<script setup>
import {
    computed,
    watch
} from 'vue'

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    productName: {
        type: String,
        default: ''
    },
    currentStatus: {
        type: String,
        default: 'pending'
    },
    newStatus: {
        type: String,
        default: 'completed'
    }
})

const emit = defineEmits(['confirm', 'cancel'])

const currentStatusLabel = computed(() => {
    return props.currentStatus === 'pending' ? 'Pending' : 'Completed'
})

const newStatusLabel = computed(() => {
    return props.newStatus === 'pending' ? 'Pending' : 'Completed'
})

const handleConfirm = () => {
    emit('confirm')
}

const handleCancel = () => {
    emit('cancel')
}

watch(() => props.show, (newVal) => {
    if (newVal) {
        // Prevent body scroll when modal is open
        document.body.style.overflow = 'hidden'
    } else {
        document.body.style.overflow = ''
    }
})
</script>

<style>
/* Global styles for modal - not scoped so they apply to teleported content */
.modal.show {
    display: block !important;
}

.modal-backdrop.show {
    opacity: 0.75;
    background-color: #1a1d21;
}
</style>
