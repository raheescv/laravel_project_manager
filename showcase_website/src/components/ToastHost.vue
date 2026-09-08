<script setup>
import { toastState } from '@/toast'
</script>

<template>
  <div id="toast" role="status" :class="{ 'is-on': toastState.on }">{{ toastState.message }}</div>
</template>
