<template>
    <Transition name="modal">
        <div v-if="show" class="fixed inset-0 z-[60] overflow-y-auto" aria-labelledby="modal-title" role="dialog"
            aria-modal="true">
            <!-- Background overlay -->
            <div class="flex items-center justify-center min-h-screen p-2 text-center">
                <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm transition-opacity" aria-hidden="true"
                    @click="close">
                </div>

                <!-- Modal positioning -->
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <!-- Modal panel -->
                <div
                    class="relative flex flex-col align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-2 sm:align-middle sm:max-w-4xl w-full max-h-[90vh]">
                    <!-- Header - matches SaleConfirmationModal -->
                    <div
                        class="bg-gradient-to-r from-blue-600 via-blue-700 to-indigo-700 px-3 py-2 text-white flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="bg-white/20 p-1 rounded-md mr-2">
                                <i class="fa fa-expand text-white text-xs"></i>
                            </div>
                            <h4 class="text-base font-bold text-white">
                                Measurement Details
                            </h4>
                        </div>
                        <button type="button" @click="close"
                            class="text-white hover:text-gray-200 focus:outline-none transition-colors">
                            <i class="fa fa-times text-xs"></i>
                        </button>
                    </div>

                    <!-- Scrollable Body -->
                    <div class="flex-1 overflow-y-auto px-3 py-3 custom-scrollbar bg-slate-50/30">
                        <MeasurementView :item="item" />
                    </div>

                    <!-- Footer - matches SaleConfirmationModal -->
                    <div class="bg-gradient-to-r from-slate-50 to-gray-50 px-3 py-2 border-t border-slate-200">
                        <div class="flex justify-end">
                            <button type="button" @click="close"
                                class="inline-flex items-center justify-center px-3 py-1.5 border border-slate-300 shadow-sm text-xs font-semibold rounded-lg text-slate-700 bg-white hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
                                <i class="fa fa-times mr-1 text-xs"></i>
                                Dismiss
                            </button>
                        </div>
                    </div>
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
