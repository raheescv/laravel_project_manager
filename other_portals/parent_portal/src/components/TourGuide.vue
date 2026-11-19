<script setup>
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue'
import { useRoute } from 'vue-router'

import { tourLabels } from '@/tours'
import { autoStartTour, cancelAutoStart, closeTour, findTarget, tour } from '@/utils/tour'
import { desktop } from '@/utils/viewport'

/**
 * The tour card and the spotlight around what it talks about. Phones: a card
 * pinned to the bottom (or the top, when the highlighted part sits low).
 * Computers: a card beside the highlighted part. A step with nothing to
 * highlight is a centred welcome card. Mounted once in App.vue.
 */
const route = useRoute()
const card = ref(null)
const primary = ref(null)
const rect = ref(null)
const cardBox = ref({ width: 0, height: 0 })

const PAD = 8
const GAP = 14
const EDGE = 12

const step = computed(() => tour.steps[tour.index] || null)
const total = computed(() => tour.steps.length)
const last = computed(() => tour.index === total.value - 1)
const centred = computed(() => !step.value?.target || !rect.value)
const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)')

let element = null
let frame = 0

function measure() {
  cancelAnimationFrame(frame)
  frame = requestAnimationFrame(() => {
    if (!tour.open) return
    if (element && !element.isConnected) element = findTarget(step.value?.target)
    const box = element?.getBoundingClientRect()
    rect.value = box && box.width ? { top: box.top - PAD, left: box.left - PAD, width: box.width + PAD * 2, height: box.height + PAD * 2 } : null
    if (card.value) cardBox.value = { width: card.value.offsetWidth, height: card.value.offsetHeight }
  })
}

const spotStyle = computed(() => {
  const r = rect.value
  if (!r || centred.value) return null
  return { top: `${r.top}px`, left: `${r.left}px`, width: `${r.width}px`, height: `${r.height}px` }
})

/** Where the card goes: below / above the highlight, never over it when there is room. */
const placement = computed(() => {
  const r = rect.value
  if (centred.value || !r) return { where: 'centre', style: null }
  const vh = window.innerHeight
  const vw = window.innerWidth
  const height = cardBox.value.height || 220
  const below = vh - (r.top + r.height)
  const above = r.top

  if (!desktop.value) {
    // Phones: a full-width card at the bottom, or at the top when the highlight is low.
    return below >= height + GAP || below >= above ? { where: 'bottom', style: null } : { where: 'top', style: null }
  }

  const width = cardBox.value.width || 360
  const left = Math.min(Math.max(EDGE, r.left), vw - width - EDGE)
  if (below >= height + GAP) return { where: 'below', style: { top: `${r.top + r.height + GAP}px`, left: `${left}px` } }
  if (above >= height + GAP) return { where: 'above', style: { top: `${r.top - height - GAP}px`, left: `${left}px` } }
  // Tall highlight: beside it, on whichever side has room.
  const top = Math.min(Math.max(EDGE, r.top), vh - height - EDGE)
  const right = vw - (r.left + r.width)
  if (right >= width + GAP) return { where: 'right', style: { top: `${top}px`, left: `${r.left + r.width + GAP}px` } }
  if (r.left >= width + GAP) return { where: 'left', style: { top: `${top}px`, left: `${r.left - width - GAP}px` } }
  return { where: 'bottom', style: null }
})

/** Show the current step: find its element (skipping any that left the page), bring it into view. */
async function show(direction = 1) {
  if (!tour.open) return
  let current = step.value
  element = current?.target ? findTarget(current.target) : null
  while (current?.target && !element) {
    const next = tour.index + direction
    if (next < 0 || next >= total.value) return closeTour()
    tour.index = next
    current = step.value
    element = current?.target ? findTarget(current.target) : null
  }
  if (element) {
    const box = element.getBoundingClientRect()
    const tall = box.height > window.innerHeight * 0.45
    element.scrollIntoView({ block: tall ? 'start' : 'center', inline: 'nearest', behavior: reducedMotion.matches ? 'auto' : 'smooth' })
  }
  await nextTick()
  measure()
  // Smooth scrolling lands later than the frame above: measure again once it has.
  setTimeout(measure, reducedMotion.matches ? 0 : 380)
  primary.value?.focus({ preventScroll: true })
}

function next() {
  if (last.value) return closeTour()
  tour.index++
  show(1)
}

function back() {
  if (tour.index === 0) return
  tour.index--
  show(-1)
}

function onKey(event) {
  if (!tour.open) return
  if (event.key === 'Escape') {
    event.preventDefault()
    closeTour()
  } else if (event.key === 'ArrowRight') next()
  else if (event.key === 'ArrowLeft') back()
  else if (event.key === 'Tab' && card.value) {
    // Keep keyboard focus inside the card while the tour is open.
    const focusable = [...card.value.querySelectorAll('button:not(:disabled)')]
    if (!focusable.length) return
    const first = focusable[0]
    const lastButton = focusable[focusable.length - 1]
    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault()
      lastButton.focus()
    } else if (!event.shiftKey && document.activeElement === lastButton) {
      event.preventDefault()
      first.focus()
    } else if (!card.value.contains(document.activeElement)) {
      event.preventDefault()
      first.focus()
    }
  }
}

let returnFocus = null

function listen(on) {
  const method = on ? 'addEventListener' : 'removeEventListener'
  window[method]('keydown', onKey, true)
  window[method]('resize', measure)
  window[method]('scroll', measure, true)
  document.documentElement.classList.toggle('pp-touring', on)
}

watch(
  () => tour.open,
  async (open) => {
    listen(open)
    if (open) {
      returnFocus = document.activeElement
      await nextTick()
      show(1)
    } else {
      rect.value = null
      element = null
      if (returnFocus?.isConnected) returnFocus.focus({ preventScroll: true })
      returnFocus = null
    }
  },
)

watch(desktop, () => tour.open && show(1))

// A new page: close a tour from the last one, and play this page's first-visit tour.
watch(
  () => route.name,
  () => {
    if (tour.open) closeTour()
    cancelAutoStart()
    autoStartTour(route)
  },
  { immediate: true },
)

onBeforeUnmount(() => {
  listen(false)
  cancelAnimationFrame(frame)
})
</script>

<template>
  <Teleport to="body">
    <Transition name="pp-tour">
      <div v-if="tour.open && step" class="pp-tour" :class="[`pp-tour--${placement.where}`, { 'pp-tour--centre': centred }]">
        <div class="pp-tour__veil" :class="{ 'is-full': centred }" aria-hidden="true"></div>
        <div v-if="spotStyle" class="pp-tour__spot" :style="spotStyle" aria-hidden="true"></div>

        <div
          ref="card"
          class="pp-tour__card"
          :style="placement.style"
          role="dialog"
          aria-modal="true"
          aria-labelledby="pp-tour-title"
          aria-describedby="pp-tour-text"
        >
          <div class="pp-tour__top">
            <span class="pp-tour__count" aria-live="polite">{{ tourLabels.of(tour.index + 1, total) }}</span>
            <button v-if="!last" class="pp-tour__skip" type="button" @click="closeTour">{{ tourLabels.skip }}</button>
          </div>

          <div v-if="centred && step.icon" class="pp-tour__icon" aria-hidden="true"><i class="fa" :class="step.icon"></i></div>
          <h2 id="pp-tour-title" class="pp-tour__title">{{ step.title }}</h2>
          <p id="pp-tour-text" class="pp-tour__text">{{ step.text }}</p>

          <div class="pp-tour__foot">
            <span v-if="total > 1" class="pp-tour__dots" aria-hidden="true">
              <span v-for="n in total" :key="n" class="pp-tour__dot" :class="{ 'is-on': n - 1 === tour.index }"></span>
            </span>
            <span class="pp-tour__buttons">
              <button v-if="tour.index > 0" class="pp-tour__btn pp-tour__btn--ghost" type="button" @click="back">
                <i class="fa fa-angle-left" aria-hidden="true"></i>{{ tourLabels.back }}
              </button>
              <button ref="primary" class="pp-tour__btn" type="button" @click="next">
                {{ last ? tourLabels.done : tourLabels.next }}<i v-if="!last" class="fa fa-angle-right" aria-hidden="true"></i>
              </button>
            </span>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
