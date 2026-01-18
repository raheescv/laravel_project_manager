<template>
    <div class="flex justify-between items-center bg-white rounded-lg shadow-sm p-4">
        <div class="flex gap-4">
            <button 
                type="button"
                @click="$emit('clear')"
                :disabled="isLoading"
                class="px-6 py-2 bg-white border-2 border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 font-semibold disabled:opacity-50"
            >
                Clear
            </button>
            <button 
                type="button"
                @click="$emit('create-order')"
                :disabled="!canSubmit || isLoading"
                class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-semibold disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <span v-if="isLoading">Processing...</span>
                <span v-else>Create Order</span>
            </button>
        </div>
        <button 
            type="button"
            @click="$emit('payment')"
            :disabled="isLoading"
            class="px-8 py-2 bg-orange-500 text-white rounded-md hover:bg-orange-600 font-semibold min-w-[200px] disabled:opacity-50"
        >
            Payment
        </button>
    </div>
</template>

<script setup>
defineProps({
    isLoading: Boolean,
    canSubmit: Boolean,
})

defineEmits(['clear', 'create-order', 'payment'])
</script>
