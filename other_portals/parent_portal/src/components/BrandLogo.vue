<script setup>
import { brand } from '@/brand'
import { school } from '@/school'

/**
 * The caterer's logo. `plate` sets it on a white tile, for coloured surfaces such
 * as a card face; on dark pages it always gets one, since the wordmark is dark.
 * With no bundled logo the school's own logo or name stands in.
 */
defineProps({
  plate: { type: Boolean, default: false },
})
</script>

<template>
  <span v-if="brand.logo" class="pp-brand-logo" :class="{ 'pp-brand-logo--plate': plate }">
    <img :src="brand.logo" :alt="brand.name" />
  </span>
  <span v-else-if="school.logo" class="pp-brand-logo pp-brand-logo--plate"><img :src="school.logo" :alt="school.name" /></span>
  <span v-else class="pp-brand-logo pp-brand-logo--text">{{ school.name || 'Parent Portal' }}</span>
</template>
