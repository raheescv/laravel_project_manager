<template>
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-4 py-2.5">
            <div class="flex justify-between items-center">
                <div class="flex gap-2">
                    <button type="button" @click="$emit('clear')" :disabled="isLoading"
                        class="px-5 py-2 rounded-xl border-2 border-slate-200 text-slate-600 font-bold text-xs tracking-wide hover:bg-slate-50 hover:border-slate-300 transition-all duration-300 disabled:opacity-50">
                        Reset Page
                    </button>
                </div>
                <button type="button" @click="$emit('create-order')" :disabled="!canSubmit || isLoading"
                    class="px-6 py-2 rounded-xl bg-indigo-600 text-white font-bold text-xs tracking-wide shadow-lg shadow-indigo-600/20 hover:bg-indigo-700 hover:shadow-xl hover:shadow-indigo-700/30 transition-all duration-300 disabled:bg-slate-300 disabled:shadow-none disabled:cursor-not-allowed">
                    <span v-if="isLoading">Processing...</span>
                    <span v-else>Confirm & Create Order</span>
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
