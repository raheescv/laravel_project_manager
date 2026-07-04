<script setup>
defineProps({
  step: { type: Number, required: true },
  label: { type: String, required: true },
})
</script>

<template>
  <div class="steps">
    <div class="steps__bars">
      <span v-for="n in 3" :key="n" class="steps__bar" :class="{ 'steps__bar--on': n <= step }" />
    </div>
    <span class="steps__label">Step {{ step }} of 3 · {{ label }}</span>
  </div>
</template>

<style scoped>
.steps {
  display: flex;
  align-items: center;
  gap: 16px;
}

.steps__bars {
  display: flex;
  gap: 6px;
}

.steps__bar {
  width: 30px;
  height: 4px;
  border-radius: 2px;
  background: var(--line);
  display: inline-block;
}

.steps__bar--on {
  background: var(--gold);
}

.steps__label {
  font-family: var(--font-display);
  font-size: 12px;
  font-weight: 600;
  letter-spacing: 0.18em;
  text-transform: uppercase;
  color: var(--gold);
}
</style>
