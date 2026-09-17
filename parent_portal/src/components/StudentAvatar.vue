<script setup>
import { computed } from 'vue'

import { resolveImage } from '@/api/client'
import { initials } from '@/utils/format'

const props = defineProps({
  name: { type: String, default: '' },
  image: { type: String, default: null },
})

const url = computed(() => resolveImage(props.image))
</script>

<template>
  <span
    class="pp-avatar"
    :class="{ 'pp-avatar--photo': url }"
    :style="url ? { backgroundImage: `url(&quot;${url}&quot;)` } : null"
    role="img"
    :aria-label="name"
    >{{ initials(name) }}</span
  >
</template>
