<template>
  <div class="skel">
    <div class="skeleton skel__media"></div>
    <div class="skel__body">
      <div class="skeleton" style="height: 12px; width: 40%"></div>
      <div class="skeleton" style="height: 20px; width: 75%"></div>
      <div class="skeleton" style="height: 16px; width: 30%"></div>
    </div>
  </div>
</template>

<style scoped>
.skel {
  border-radius: 22px;
  overflow: hidden;
  background: var(--card);
  border: 1px solid var(--line);
}

.skel__media {
  height: 232px;
  border-radius: 0;
}

.skel__body {
  padding: 20px 22px 22px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}
</style>
