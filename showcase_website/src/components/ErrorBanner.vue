<script setup>
defineProps({
  message: { type: String, required: true },
})
defineEmits(['retry'])
</script>

<template>
  <div class="error-banner">
    <span>{{ message }}</span>
    <button class="error-banner__retry" @click="$emit('retry')">Try again</button>
  </div>
</template>

<style scoped>
.error-banner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
  border: 1px solid rgba(196, 85, 59, 0.35);
  background: rgba(196, 85, 59, 0.07);
  color: var(--red);
  border-radius: 16px;
  padding: 18px 22px;
  font-size: 14px;
  font-weight: 500;
  margin: 24px 0;
}

.error-banner__retry {
  border: 1.5px solid var(--red);
  border-radius: 99px;
  padding: 8px 20px;
  font-size: 13px;
  font-weight: 600;
  color: var(--red);
  transition: all 0.15s ease;
}

.error-banner__retry:hover {
  background: var(--red);
  color: #fff;
}
</style>
