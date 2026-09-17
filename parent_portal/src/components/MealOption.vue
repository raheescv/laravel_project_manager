<script setup>
import { computed } from 'vue'

import { resolveImage } from '@/api/client'
import { money } from '@/utils/format'

/** One meal as a radio row: photo, name, price and a round check. */
const props = defineProps({
  meal: { type: Object, required: true },
  selected: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  note: { type: String, default: '' },
})
defineEmits(['select'])

const image = computed(() => resolveImage(props.meal.thumbnail))
</script>

<template>
  <button
    class="pp-row pp-meal-row"
    type="button"
    role="radio"
    :aria-checked="selected"
    :aria-disabled="disabled || undefined"
    :disabled="disabled"
    @click="$emit('select', meal)"
  >
    <span class="pp-thumb" :style="image ? { backgroundImage: `url(&quot;${image}&quot;)` } : null">
      <i v-if="!image" class="fa fa-cutlery"></i>
    </span>
    <span class="pp-row__main">
      <span class="pp-row__title">{{ meal.name }}</span>
      <span class="pp-row__sub">{{ note || money(meal.mrp) }}</span>
    </span>
    <span class="pp-check" aria-hidden="true"><i class="fa fa-check"></i></span>
  </button>
</template>
