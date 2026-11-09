<script setup>
import QuantityStepper from '@/components/QuantityStepper.vue'
import { money } from '@/utils/format'
import { mealThumbStyle } from '@/utils/meals'

/** The meals in an order with how many of each, and the day's total or, given `days`, the week's. */
defineProps({
  meals: { type: Array, required: true },
  quantities: { type: Object, required: true },
  total: { type: Number, required: true },
  max: { type: Number, default: 10 },
  days: { type: Number, default: null },
})
defineEmits(['quantity'])
</script>

<template>
  <div class="pp-cart">
    <p v-if="!meals.length" class="pp-cart__empty">
      <i class="fa fa-shopping-cart" aria-hidden="true"></i>
      <span>Nothing here yet. Add the meals you'd like.</span>
    </p>
    <template v-else>
      <ul class="pp-cart__lines">
        <li v-for="meal in meals" :key="meal.id" class="pp-cart__line">
          <span class="pp-thumb" :style="mealThumbStyle(meal)"><i v-if="!mealThumbStyle(meal)" class="fa fa-cutlery"></i></span>
          <span class="pp-cart__name">{{ meal.name }}</span>
          <button class="pp-cart__remove" type="button" :aria-label="`Remove ${meal.name}`" @click="$emit('quantity', meal, 0)"><i class="fa fa-times"></i></button>
          <span class="pp-cart__qty">
            <QuantityStepper :value="quantities[meal.id]" :max="max" :label="meal.name" @change="$emit('quantity', meal, $event)" />
            <span class="pp-cart__price">{{ money(meal.mrp * quantities[meal.id]) }}</span>
          </span>
        </li>
      </ul>
      <dl class="pp-cart__totals">
        <template v-if="days !== null">
          <div><dt>Each day</dt><dd>{{ money(total) }}</dd></div>
          <div><dt>Days a week</dt><dd>× {{ days }}</dd></div>
        </template>
        <div class="pp-cart__total">
          <dt>{{ days === null ? 'Total' : 'Total a week' }}</dt>
          <dd>{{ money(days === null ? total : total * days) }}</dd>
        </div>
      </dl>
      <p class="pp-cart__note">Paid from the card at the canteen.</p>
    </template>
  </div>
</template>
