<script setup>
defineProps({
  title: { type: String, default: 'Nothing matches at this branch' },
  subtitle: { type: String, default: 'Try another size or check a different branch.' },
})
</script>

<template>
  <div class="empty">
    <div class="empty__icon">∅</div>
    <div class="empty__title">{{ title }}</div>
    <div class="empty__sub">{{ subtitle }}</div>
  </div>
</template>

<style scoped>
.empty {
  text-align: center;
  padding: 80px 0;
  color: var(--muted);
}

.empty__icon {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background: #edeff3;
  margin: 0 auto 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 26px;
}

.empty__title {
  font-family: var(--font-display);
  font-size: 24px;
  font-weight: 600;
  color: var(--ink);
}

.empty__sub {
  margin-top: 8px;
  font-size: 14px;
}
</style>
