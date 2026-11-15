<script setup>
import { computed } from 'vue'

import { money } from '@/utils/format'
import { mealThumbStyle } from '@/utils/meals'

/** One meal as a row or card, as on the POS: each tap adds one to the cart. */
const props = defineProps({
  meal: { type: Object, required: true },
  quantity: { type: Number, default: 0 },
  max: { type: Number, default: 10 },
  disabled: { type: Boolean, default: false },
  note: { type: String, default: '' },
})
defineEmits(['add'])

const thumbStyle = computed(() => mealThumbStyle(props.meal))
</script>

<template>
  <div class="pp-row pp-meal-row" :class="{ 'is-selected': quantity > 0, 'is-disabled': disabled, 'is-max': quantity >= max }">
    <button
      class="pp-meal-row__hit"
      type="button"
      :aria-label="quantity > 0 ? `Add one more ${meal.name}, ${quantity} in cart` : `Add ${meal.name}`"
      :disabled="disabled || quantity >= max"
      @click="$emit('add', meal)"
    ></button>
    <span class="pp-thumb" :style="thumbStyle">
      <i v-if="!thumbStyle" class="fa fa-cutlery"></i>
    </span>
    <span class="pp-row__main">
      <span class="pp-row__title">{{ meal.name }}</span>
      <span class="pp-row__sub">{{ note || money(meal.mrp) }}</span>
    </span>
    <span v-if="quantity > 0" class="pp-meal-count" aria-hidden="true">{{ quantity }}</span>
  </div>
</template>
