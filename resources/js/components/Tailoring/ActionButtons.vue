<template>
    <div class="bg-gradient-to-r from-slate-50 to-gray-50 rounded-xl border border-slate-200 overflow-hidden shadow-sm">
        <div class="px-3 py-2">
            <div class="flex flex-col sm:flex-row justify-end gap-1.5 sm:justify-between sm:items-center">
                <button type="button" @click="$emit('clear')" :disabled="isLoading"
                    class="inline-flex items-center justify-center px-3 py-1.5 border border-slate-300 shadow-sm text-xs font-semibold rounded-lg text-slate-700 bg-white hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 disabled:opacity-50">
                    <i class="fa fa-times mr-1 text-xs"></i>
                    Reset Page
                </button>
                <button type="button" @click="$emit('create-order')" :disabled="!canSubmit || isLoading" :class="[
                    'inline-flex items-center justify-center px-3 py-1.5 border border-transparent text-xs font-semibold rounded-lg text-white shadow-lg focus:outline-none focus:ring-2 focus:ring-offset-2 transition-all duration-200',
                    (!canSubmit || isLoading) ? 'opacity-75 cursor-not-allowed' : 'hover:scale-105',
                    'bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 focus:ring-green-500'
                ]">
                    <i class="fa mr-1 text-xs" :class="isLoading ? 'fa-spinner fa-spin' : 'fa-check-circle'"></i>
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
