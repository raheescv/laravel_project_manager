<template>
    <Transition name="modal">
        <div v-if="show" class="fixed inset-0 z-[60] flex items-center justify-center p-4" role="dialog">
            <!-- Simple Backdrop -->
            <div class="fixed inset-0 bg-black/50 transition-opacity" @click="close"></div>

            <!-- Modal Content -->
            <div class="relative bg-white rounded-xl shadow-lg w-full max-w-4xl max-h-[85vh] flex flex-col transform transition-all overflow-hidden border">
                
                <!-- Simple Header -->
                <div class="px-5 py-3 border-b flex justify-between items-center bg-gray-50">
                    <span class="text-sm font-bold text-gray-700">Measurement Details</span>
                    <button @click="close" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <i class="fa fa-times text-lg"></i>
                    </button>
                </div>

                <!-- Scrollable Content -->
                <div class="flex-1 overflow-y-auto p-5 custom-scrollbar">
                    <MeasurementView :item="item" />
                </div>

                <!-- Footer -->
                <div class="px-5 py-3 bg-gray-50 flex justify-end border-t">
                    <button @click="close" 
                        class="px-6 py-1.5 bg-gray-800 text-white text-sm font-bold rounded-md hover:bg-black transition-all active:scale-95">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>

<script setup>
import MeasurementView from './MeasurementView.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    item: {
        type: Object,
        required: true
    }
})

const emit = defineEmits(['close'])

const close = () => {
    emit('close')
}
</script>

<style scoped>
.modal-enter-active, .modal-leave-active {
    transition: all 0.2s ease-out;
}
.modal-enter-from, .modal-leave-to {
    opacity: 0;
    transform: translateY(10px);
}

.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
</style>
