<script setup>
import { toasts } from '@/toast'
</script>

<template>
  <div class="pp-toasts" aria-live="polite">
    <div v-for="item in toasts" :key="item.id" class="pp-toast" :class="`pp-toast--${item.tone}`" role="status">
      <i class="fa" :class="item.tone === 'neg' ? 'fa-exclamation-circle' : 'fa-check-circle'"></i>
      <span>{{ item.message }}</span>
    </div>
  </div>
</template>
