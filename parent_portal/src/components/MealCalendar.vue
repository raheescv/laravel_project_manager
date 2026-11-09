<script setup>
import { computed } from 'vue'

import { dateTile, isToday, money } from '@/utils/format'

/** The whole booking window as weeks, Sunday first: each school day opens its own order. */
const props = defineProps({
  board: { type: Array, required: true },
  studentId: { type: Number, required: true },
})

const WEEKDAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']

const parseDate = (value) => {
  const [year, month, day] = value.split('-').map(Number)
  return new Date(year, month - 1, day)
}
const pad = (number) => String(number).padStart(2, '0')
const formatDate = (date) => `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`

const cells = computed(() => {
  if (!props.board.length) return []
  const entries = new Map(props.board.map((entry) => [entry.day.date, entry]))
  const last = parseDate(props.board[props.board.length - 1].day.date)
  const cursor = parseDate(props.board[0].day.date)
  cursor.setDate(cursor.getDate() - cursor.getDay())

  const result = []
  while (cursor <= last || cursor.getDay() !== 0) {
    const date = formatDate(cursor)
    const tile = dateTile(date)
    result.push({ date, entry: entries.get(date) || null, day: tile.day, month: !result.length || cursor.getDate() === 1 ? tile.month : '' })
    cursor.setDate(cursor.getDate() + 1)
  }
  return result
})

const isOpen = (cell) => Boolean(cell.entry && !cell.entry.day.locked)
</script>

<template>
  <div class="pp-cal">
    <div class="pp-cal__weekdays" aria-hidden="true">
      <span v-for="weekday in WEEKDAYS" :key="weekday">{{ weekday }}</span>
    </div>
    <div class="pp-cal__grid">
      <component
        :is="isOpen(cell) ? 'RouterLink' : 'div'"
        v-for="cell in cells"
        :key="cell.date"
        class="pp-cal__day"
        :class="[cell.entry ? `pp-cal__day--${cell.entry.card.kind}` : 'pp-cal__day--off', { 'is-today': isToday(cell.date) }]"
        :aria-current="isToday(cell.date) ? 'date' : undefined"
        :to="isOpen(cell) ? { name: 'pre-order-day', params: { id: studentId, date: cell.date } } : undefined"
      >
        <span class="pp-cal__top">
          <span class="pp-cal__num">{{ cell.day }}<small v-if="cell.month">{{ cell.month }}</small></span>
          <span v-if="cell.entry?.card.tag" class="pp-tag" :class="`pp-tag--${cell.entry.card.tag[0]}`">{{ cell.entry.card.tag[1] }}</span>
        </span>
        <template v-if="cell.entry">
          <span class="pp-cal__meal" :title="cell.entry.card.meal">{{ cell.entry.card.meal }}</span>
          <span class="pp-cal__foot">
            <span v-if="cell.entry.card.amount" class="pp-num">{{ money(cell.entry.card.amount) }}</span>
            <span v-else-if="cell.entry.card.action" class="pp-cal__action">{{ cell.entry.card.action }}</span>
            <span v-else-if="cell.entry.day.locked" class="pp-cal__closed">Closed</span>
            <span class="pp-cal__marks">
              <i v-if="cell.entry.day.source === 'weekly'" class="fa fa-refresh" title="Every week"></i>
              <i v-if="cell.entry.day.locked" class="fa fa-lock" title="Ordering closed"></i>
            </span>
          </span>
        </template>
      </component>
    </div>
  </div>
</template>
