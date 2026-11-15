<script setup>
/** The dishes a meal serves on one day, course by course. */
defineProps({
  dishes: { type: Array, default: () => [] },
})
</script>

<template>
  <div class="pp-group__body">
    <div v-for="(dish, index) in dishes" :key="index" class="pp-dish">
      <span class="pp-dish__course">{{ dish.course }}<span v-if="dish.note"> · {{ dish.note }}</span></span>
      <span class="pp-dish__name">{{ dish.name }}</span>
      <span v-if="dish.description" class="pp-dish__desc">{{ dish.description }}</span>
    </div>
    <div v-if="!dishes.length" class="pp-row pp-row--empty">The school hasn't added this day's dishes yet.</div>
  </div>
</template>
