<script setup>
import { nextTick, onMounted, ref, watch } from 'vue'

import { i18n } from '@/i18n'
import { useCatalogStore } from '@/stores/catalog'
import { isHalfSize } from '@/utils/catalog'

const props = defineProps({
  label: { type: String, required: true },
  sizes: { type: Array, required: true }, // [{ size, in_stock }]
})
const emit = defineEmits(['pick'])

const catalog = useCatalogStore()
const rail = ref(null)
const track = ref(null)

const anySelected = () => props.sizes.some((s) => s.size === catalog.size)

function tabindexFor(s, i) {
  const on = s.size === catalog.size
  const firstUsable = props.sizes.findIndex((x) => x.in_stock) === i
  return on || (!anySelected() && firstUsable) ? 0 : -1
}

function scrollRail(dir) {
  const rtl = i18n.dir === 'rtl' ? -1 : 1
  rail.value?.scrollBy({ left: 320 * dir * rtl, behavior: 'smooth' })
}

/** Roving focus along the ruler: arrows, Home, End. */
function onKey(e) {
  const ticks = Array.from(track.value?.querySelectorAll('.tick:not(:disabled)') || [])
  const i = ticks.indexOf(document.activeElement)
  if (i < 0) return
  const step = i18n.dir === 'rtl' ? -1 : 1
  let next = null
  if (e.key === 'ArrowRight') next = ticks[i + step]
  if (e.key === 'ArrowLeft') next = ticks[i - step]
  if (e.key === 'Home') next = ticks[0]
  if (e.key === 'End') next = ticks[ticks.length - 1]
  if (next) {
    e.preventDefault()
    next.focus()
    next.scrollIntoView({ block: 'nearest', inline: 'nearest' })
  }
}

/** Bring the chosen tick into the middle of the rail when it overflows. */
async function centreSelected() {
  await nextTick()
  const el = rail.value
  const on = el?.querySelector('.tick.is-on')
  if (on && el.scrollWidth > el.clientWidth + 4) {
    on.scrollIntoView({ block: 'nearest', inline: 'center' })
  }
}

onMounted(centreSelected)
watch(() => [catalog.size, props.sizes.length], centreSelected)
</script>

<template>
  <div class="size__rail">
    <div class="rail__meta">
      <span class="rail__label">{{ label }}</span>
      <div class="rail__nav">
        <button class="rail__arrow" aria-label="◀" @click="scrollRail(-1)">&#8249;</button>
        <button class="rail__arrow" aria-label="▶" @click="scrollRail(1)">&#8250;</button>
      </div>
    </div>
    <div ref="rail" class="rail">
      <div ref="track" class="rail__track" role="radiogroup" :aria-label="label" @keydown="onKey">
        <button
          v-for="(s, i) in sizes"
          :key="s.size"
          class="tick"
          :class="{
            'tick--half': isHalfSize(s.size),
            'is-on': catalog.size === s.size,
            'is-out': !s.in_stock,
          }"
          role="radio"
          :aria-checked="catalog.size === s.size"
          :disabled="!s.in_stock"
          :tabindex="tabindexFor(s, i)"
          @click="emit('pick', s.size)"
        >
          <span class="tick__mark"></span>
          <span class="tick__num">{{ s.size }}</span>
        </button>
      </div>
    </div>
  </div>
</template>
