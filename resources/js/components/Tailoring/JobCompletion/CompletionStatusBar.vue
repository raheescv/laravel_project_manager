<template>
    <div class="bg-gray-100 rounded-lg p-4 text-center">
        <div class="text-sm font-semibold text-gray-700">
            <span v-if="recordCount > 0">{{ recordCount }} record(s)</span>
            <span v-else>No records</span>
            <span v-if="completionStatus" class="ml-4">
                Status: <span class="text-blue-600">{{ completionStatus }}</span>
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
