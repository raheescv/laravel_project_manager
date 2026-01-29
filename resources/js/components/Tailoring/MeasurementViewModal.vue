<template>
    <Transition name="modal">
        <div v-if="show" class="fixed inset-0 z-[60] flex items-center justify-center p-4" role="dialog">
            <!-- Simple Backdrop -->
            <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" @click="close"></div>

            <!-- Modal Content -->
            <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col transform transition-all overflow-hidden border border-slate-200">
                
                <!-- Premium Header -->
                <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-white">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center text-blue-600">
                            <i class="fa fa-expand text-sm"></i>
                        </div>
                        <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Measurement Details</h3>
                    </div>
                    <button @click="close" class="w-8 h-8 rounded-full flex items-center justify-center text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-all">
                        <i class="fa fa-times text-sm"></i>
                    </button>
                </div>

                <!-- Scrollable Content -->
                <div class="flex-1 overflow-y-auto p-6 custom-scrollbar bg-slate-50/30">
                    <MeasurementView :item="item" />
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 bg-white flex justify-end gap-3 border-t border-slate-100">
                    <button @click="close" 
                        class="px-6 py-2 bg-slate-950 text-white text-xs font-black uppercase tracking-widest rounded-xl hover:bg-slate-800 transition-all active:scale-95 shadow-lg shadow-slate-200">
                        Dismiss
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
