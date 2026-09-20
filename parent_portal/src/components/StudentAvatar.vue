<script setup>
import { computed, ref, watch } from 'vue'

import { resolveImage } from '@/api/client'
import { initials } from '@/utils/format'

/**
 * A person, next to their name: their photo when the school has one, their
 * initials when it does not, and a plain figure when we have neither. The photo
 * is an <img> rather than a background so a broken or removed file falls back to
 * the initials instead of leaving an empty tile.
 */
const props = defineProps({
  name: { type: String, default: '' },
  image: { type: String, default: null },
})

const broken = ref(false)
watch(
  () => props.image,
  () => {
    broken.value = false
  },
)

const url = computed(() => (broken.value ? null : resolveImage(props.image)))
const letters = computed(() => initials(props.name))
</script>

<template>
  <span class="pp-avatar" :class="{ 'pp-avatar--photo': url }" role="img" :aria-label="name">
    <template v-if="letters">{{ letters }}</template>
    <i v-else class="fa fa-user" aria-hidden="true"></i>
    <img v-if="url" :src="url" alt="" @error="broken = true" />
  </span>
</template>
