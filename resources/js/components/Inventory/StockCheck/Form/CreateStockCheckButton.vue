<template>
    <button class="btn btn-primary" @click="$emit('open-create-modal')">
        <i class="fa fa-plus me-1"></i>
        Create Stock Check
    </button>
</template>

<script setup>
defineEmits(['open-create-modal'])
</script>
