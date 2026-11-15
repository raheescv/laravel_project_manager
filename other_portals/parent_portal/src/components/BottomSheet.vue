<script setup>
import { nextTick, onBeforeUnmount, ref, watch } from 'vue'

/** A sheet that slides up from the bottom. `variant="action"` is the iOS action sheet. */
const props = defineProps({
  open: { type: Boolean, default: false },
  label: { type: String, default: '' },
  variant: { type: String, default: '' },
})
const emit = defineEmits(['close'])

const panel = ref(null)

function onKey(event) {
  if (event.key === 'Escape' && props.open) emit('close')
}

watch(
  () => props.open,
  async (open) => {
    document.documentElement.classList.toggle('pp-lock', open)
    if (open) {
      await nextTick()
      panel.value?.focus({ preventScroll: true })
    }
  },
)

window.addEventListener('keydown', onKey)
onBeforeUnmount(() => {
  window.removeEventListener('keydown', onKey)
  document.documentElement.classList.remove('pp-lock')
})
</script>

<template>
  <Teleport to="body">
    <div class="pp-sheet" :class="[variant && `pp-sheet--${variant}`, { 'is-open': open }]" :aria-hidden="!open">
      <button class="pp-sheet__backdrop" type="button" tabindex="-1" aria-label="Close" @click="emit('close')"></button>
      <div ref="panel" class="pp-sheet__panel" role="dialog" aria-modal="true" :aria-label="label" tabindex="-1">
        <slot />
      </div>
    </div>
  </Teleport>
</template>
