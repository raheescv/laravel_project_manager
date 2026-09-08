<script setup>
import { t } from '@/i18n'

defineProps({
  message: { type: String, default: '' },
})
defineEmits(['retry'])
</script>

<template>
  <div class="error" role="alert">
    <span class="error__rule"></span>
    <p class="error__title">{{ t('errorTitle') }}</p>
    <p v-if="message" class="error__body">{{ message }}</p>
    <button class="btn btn--dark" @click="$emit('retry')">{{ t('retry') }}</button>
  </div>
</template>
