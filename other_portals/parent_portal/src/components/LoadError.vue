<script setup>
defineProps({
  title: { type: String, default: 'Something went wrong' },
  message: { type: String, default: '' },
  retryLabel: { type: String, default: 'Try again' },
})
defineEmits(['retry'])
</script>

<template>
  <section class="pp-state" role="alert">
    <div class="pp-lede">
      <div class="pp-hero-icon pp-hero-icon--neg"><i class="fa fa-exclamation-circle"></i></div>
      <h1>{{ title }}</h1>
      <p v-if="message">{{ message }}</p>
    </div>
    <div class="pp-auth__actions">
      <button class="pp-btn pp-btn--tinted" type="button" @click="$emit('retry')">{{ retryLabel }}</button>
      <slot />
    </div>
  </section>
</template>
