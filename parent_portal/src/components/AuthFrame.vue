<script setup>
import CanteenArt from '@/components/CanteenArt.vue'
import ThemeToggle from '@/components/ThemeToggle.vue'
import { desktop } from '@/utils/viewport'

/**
 * Sign-in pages: one washed canvas with the form floating on it in a frosted
 * card. There is no panel beside anything, so the same layout holds from the
 * narrowest phone to an ultrawide — only the card's measure grows.
 */
defineProps({
  // The light/dark switch, unless the page's own app bar already carries one (phones only).
  bar: { type: Boolean, default: false },
})
</script>

<template>
  <div class="pp-signin">
    <ThemeToggle v-if="desktop || !bar" class="pp-signin__theme" />
    <div class="pp-signin__wash" aria-hidden="true">
      <CanteenArt class="pp-signin__plate pp-signin__plate--lead" />
      <CanteenArt class="pp-signin__plate pp-signin__plate--trail" />
    </div>
    <div class="pp-signin__form"><slot /></div>
  </div>
</template>
