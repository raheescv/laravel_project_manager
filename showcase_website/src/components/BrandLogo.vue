<script setup>
import { ref } from 'vue'

import { branding, storeName } from '@/branding'

// A logo URL that 404s falls back to the inline mark instead of a broken image.
const broken = ref(false)

defineProps({
  /** 'head' — full mark with tagline on light; 'foot' — compact white mark. */
  variant: { type: String, default: 'head' },
})
</script>

<template>
  <img
    v-if="branding.logo && !broken"
    :src="branding.logo"
    @error="broken = true"
    :alt="storeName"
    class="logo__img"
    :class="`logo__img--${variant}`"
  />
  <svg v-else-if="variant === 'foot'" class="logo__svg" viewBox="0 0 92 46" role="img" aria-hidden="true">
    <rect x="1.2" y="1.2" width="86" height="43.6" rx="7" fill="none" stroke="#fff" stroke-width="2.4" />
    <text x="10" y="21" font-family="Archivo, Arial Black, sans-serif" font-size="16" font-weight="800" fill="#fff" letter-spacing="-0.5">SIZE</text>
    <text x="10" y="37" font-family="Archivo, Arial Black, sans-serif" font-size="16" font-weight="800" fill="#fff" letter-spacing="-0.5">RUN</text>
    <path d="M70 34 V15 M70 15 l-5.5 5.5 M70 15 l5.5 5.5" fill="none" stroke="#fff" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" />
  </svg>
  <svg v-else class="logo__svg" viewBox="0 0 196 46" role="img" aria-hidden="true">
    <rect x="1.2" y="1.2" width="86" height="43.6" rx="7" fill="none" style="stroke: var(--blue)" stroke-width="2.4" />
    <text x="10" y="21" font-family="Archivo, Arial Black, sans-serif" font-size="16" font-weight="800" fill="#0B0D17" letter-spacing="-0.5">SIZE</text>
    <text x="10" y="37" font-family="Archivo, Arial Black, sans-serif" font-size="16" font-weight="800" fill="#0B0D17" letter-spacing="-0.5">RUN</text>
    <path d="M70 34 V15 M70 15 l-5.5 5.5 M70 15 l5.5 5.5" fill="none" style="stroke: var(--blue)" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" />
    <text x="96" y="20" font-family="IBM Plex Mono, monospace" font-size="6.2" fill="#6B7189" letter-spacing="1.1">SNEAKERS</text>
    <text x="96" y="29" font-family="IBM Plex Mono, monospace" font-size="6.2" fill="#6B7189" letter-spacing="1.1">FOR EVERYONE</text>
  </svg>
</template>
