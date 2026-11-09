<script setup>
/** − count + for a picked meal. Going below one asks to remove it. */
defineProps({
  value: { type: Number, required: true },
  max: { type: Number, default: 10 },
  label: { type: String, default: 'item' },
})
defineEmits(['change'])
</script>

<template>
  <span class="pp-qty" role="group" :aria-label="`Quantity of ${label}`">
    <button type="button" :aria-label="value === 1 ? `Remove ${label}` : `One less ${label}`" @click="$emit('change', value - 1)">
      <i class="fa" :class="value === 1 ? 'fa-trash-o' : 'fa-minus'"></i>
    </button>
    <b aria-live="polite">{{ value }}</b>
    <button type="button" :aria-label="`One more ${label}`" :disabled="value >= max" @click="$emit('change', value + 1)"><i class="fa fa-plus"></i></button>
  </span>
</template>
