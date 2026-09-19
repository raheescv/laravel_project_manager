<script setup>
import { computed } from 'vue'

import { theme, toggleTheme } from '@/theme'

/** The header switch between light and dark. The icon shows where a tap goes, not where we are. */
const dark = computed(() => theme.resolved === 'dark')
const label = computed(() => (dark.value ? 'Switch to light mode' : 'Switch to dark mode'))
</script>

<template>
  <button class="pp-themebtn" type="button" :title="label" :aria-label="label" @click="toggleTheme">
    <i class="fa" :class="dark ? 'fa-sun-o' : 'fa-moon-o'" aria-hidden="true"></i>
  </button>
</template>
