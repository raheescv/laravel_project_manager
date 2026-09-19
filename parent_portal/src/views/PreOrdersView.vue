<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'

import { fetchPreOrderMenu, fetchPreOrders, fetchStudent } from '@/api/parent'
import AppBar from '@/components/AppBar.vue'
import LoadError from '@/components/LoadError.vue'
import { refreshChild } from '@/children'
import { dateTile, dayName, firstName, isToday, money, time, weekdaysLabel } from '@/utils/format'
import { flattenMenu, isoWeekday, servedOn } from '@/utils/meals'
import { desktop } from '@/utils/viewport'

/**
 * A child's canteen meals: the weekly order and what happens on each of the next
 * school days. Nothing is charged here — the meal is paid from the card when the
 * child taps it at the canteen.
 */
const route = useRoute()
const id = Number(route.params.id)

const student = ref(null)
const schedule = ref(null)
const meals = ref([])
const status = ref('loading')
const error = ref('')

const first = computed(() => firstName(student.value?.name))
const mealName = (order) => order?.items?.map((item) => item.name).join(', ') || 'Meal'

async function load() {
  status.value = 'loading'
  try {
    const [s, sch, menu] = await Promise.all([fetchStudent(id), fetchPreOrders(id), fetchPreOrderMenu()])
    ;[student.value, schedule.value, meals.value] = [s, sch, flattenMenu(menu)]
    refreshChild(s)
    status.value = 'ready'
  } catch (e) {
    error.value = e.status === 404 ? "This child isn't linked to your login." : e.message
    status.value = 'error'
  }
}

/** What a day row says. */
function describe(day) {
  const served = meals.value.some((meal) => servedOn(meal, isoWeekday(day.date)))
  if (day.collected) return { sub: `Collected · ${mealName(day.order)}`, tag: ['accent', 'Collected'] }
  if (day.source === 'skipped') return { sub: 'No meal this day', tag: ['muted', 'Skipped'] }
  if (day.order) {
    return {
      sub: `${mealName(day.order)}${day.source === 'weekly' ? ' · Weekly' : ''}`,
      tag: ['pos', 'Ordered'],
      amount: day.order.total,
    }
  }
  if (!served) return { sub: 'No meal served', tag: null }
  return { sub: day.locked ? 'Ordering closed' : 'Not ordered', tag: null }
}

/** The same day as a card on the desktop week board. */
function dayCard(day) {
  const { tag, amount } = describe(day)
  const served = meals.value.some((meal) => servedOn(meal, isoWeekday(day.date)))
  const open = !day.locked
  if (day.collected) return { tag, amount, meal: mealName(day.order), note: 'Collected', kind: 'done' }
  if (day.source === 'skipped') return { tag, meal: 'No meal this day', note: open ? 'You chose not to order' : 'Ordering closed', kind: 'skipped', action: open && 'Order this day' }
  if (day.order) {
    return { tag, amount, meal: mealName(day.order), note: day.source === 'weekly' ? 'Every week' : 'This day only', kind: 'ordered', action: open && 'Change' }
  }
  if (!served) return { meal: 'No meal served', note: 'Nothing on the menu', kind: 'empty' }
  return { meal: 'Not ordered', note: open ? 'Ordering open' : 'Ordering closed', kind: 'empty', action: open && 'Order a meal' }
}
const board = computed(() => (schedule.value?.days || []).map((day) => ({ day, card: dayCard(day) })))

onMounted(load)
</script>

<template>
  <AppBar :back="{ name: 'student', params: { id } }" :back-label="first || 'Back'" title="Meals" />

  <LoadError v-if="status === 'error'" title="We couldn't load meals" :message="error" @retry="load" />

  <main v-else-if="status === 'loading'" aria-busy="true">
    <div class="pp-largetitle"><span class="pp-skel pp-skel--title"></span></div>
    <div class="pp-group">
      <div class="pp-group__body">
        <div v-for="n in 4" :key="n" class="pp-row pp-day-row">
          <span class="pp-skel" style="width: 44px; height: 44px"></span>
          <span class="pp-row__main"><span class="pp-skel pp-skel--line" style="width: 40%"></span><span class="pp-skel pp-skel--line" style="width: 70%"></span></span>
        </div>
      </div>
    </div>
  </main>

  <main v-else>
    <div class="pp-largetitle">
      <h1>Canteen meals</h1>
      <p>Order {{ first }}'s meal ahead. It's added when {{ first }} taps the card at the canteen, and paid from the card then.</p>
    </div>

    <div v-if="!schedule.enabled" class="pp-alert pp-alert--warn" role="alert">
      <i class="fa fa-exclamation-triangle"></i>
      <span class="pp-alert__main"><span class="pp-alert__title">Not taking orders right now</span>The school has paused meal pre-orders.</span>
    </div>

    <template v-else-if="desktop">
      <section class="pp-panel pp-weekly">
        <span
          class="pp-row__icon"
          :class="!schedule.weekly ? 'pp-row__icon--accent' : schedule.weekly.status === 'paused' ? 'pp-row__icon--muted' : 'pp-row__icon--pos'"
        >
          <i class="fa" :class="schedule.weekly ? 'fa-refresh' : 'fa-plus'"></i>
        </span>
        <div class="pp-weekly__main">
          <small>Every week</small>
          <b>{{ schedule.weekly ? mealName(schedule.weekly) : 'No weekly order' }}</b>
          <span v-if="schedule.weekly">{{ weekdaysLabel(schedule.weekly.weekdays) }} · {{ money(schedule.weekly.total) }} a day</span>
          <span v-else>Pick the meal and the days once, and it's ordered for you each week.</span>
        </div>
        <span v-if="schedule.weekly?.status === 'paused'" class="pp-tag pp-tag--warn">Paused</span>
        <RouterLink class="pp-btn pp-btn--ghost pp-btn--auto" :to="{ name: 'pre-order-weekly', params: { id } }">
          {{ schedule.weekly ? 'Change' : 'Order every week' }}
        </RouterLink>
      </section>

      <section>
        <h2 class="pp-group__head">Next school days</h2>
        <div class="pp-week">
          <component
            :is="day.locked ? 'div' : 'RouterLink'"
            v-for="{ day, card } in board"
            :key="day.date"
            class="pp-dayc"
            :class="[`pp-dayc--${card.kind}`, { 'is-today': isToday(day.date) }]"
            :to="day.locked ? undefined : { name: 'pre-order-day', params: { id, date: day.date } }"
          >
            <span class="pp-dayc__head">
              <span>
                <span class="pp-dayc__wd">{{ isToday(day.date) ? 'Today' : dayName(day.date).slice(0, 3) }}</span>
                <span class="pp-dayc__num">{{ dateTile(day.date).day }}<small>{{ dateTile(day.date).month }}</small></span>
              </span>
              <span v-if="card.tag" class="pp-tag" :class="`pp-tag--${card.tag[0]}`">{{ card.tag[1] }}</span>
            </span>
            <span class="pp-dayc__meal">{{ card.meal }}</span>
            <span class="pp-dayc__sub">{{ card.note }}</span>
            <span class="pp-dayc__foot" :class="{ 'is-muted': !card.action }">
              {{ card.action || (day.locked ? 'Closed' : '') }}
              <span v-if="card.amount" class="pp-num">{{ money(card.amount) }}</span>
            </span>
          </component>
        </div>
        <p class="pp-group__foot">
          <i class="fa fa-clock-o"></i>Orders for a day close at {{ time(schedule.days[0]?.deadline) || schedule.cutoff }} that day. Balance today: {{ money(student.balance) }}.
        </p>
      </section>
    </template>

    <template v-else>
      <section class="pp-group">
        <h2 class="pp-group__head">Every week</h2>
        <div class="pp-group__body">
          <RouterLink class="pp-row pp-row--icon" :to="{ name: 'pre-order-weekly', params: { id } }">
            <template v-if="schedule.weekly">
              <span class="pp-row__icon" :class="schedule.weekly.status === 'paused' ? 'pp-row__icon--muted' : 'pp-row__icon--pos'"><i class="fa fa-refresh"></i></span>
              <span class="pp-row__main">
                <span class="pp-row__title">{{ mealName(schedule.weekly) }}</span>
                <span class="pp-row__sub">{{ weekdaysLabel(schedule.weekly.weekdays) }} · {{ money(schedule.weekly.total) }} a day</span>
              </span>
              <span class="pp-row__end">
                <span v-if="schedule.weekly.status === 'paused'" class="pp-tag pp-tag--warn">Paused</span>
                <i class="fa fa-angle-right pp-chev"></i>
              </span>
            </template>
            <template v-else>
              <span class="pp-row__icon pp-row__icon--accent"><i class="fa fa-plus"></i></span>
              <span class="pp-row__main">
                <span class="pp-row__title">Order every week</span>
                <span class="pp-row__sub">Pick the meal and days once</span>
              </span>
              <i class="fa fa-angle-right pp-chev"></i>
            </template>
          </RouterLink>
        </div>
      </section>

      <section class="pp-group">
        <h2 class="pp-group__head">Next school days</h2>
        <div class="pp-group__body">
          <component
            :is="day.locked ? 'div' : 'RouterLink'"
            v-for="day in schedule.days"
            :key="day.date"
            class="pp-row pp-day-row"
            :to="day.locked ? undefined : { name: 'pre-order-day', params: { id, date: day.date } }"
          >
            <span class="pp-date" :class="{ 'is-today': isToday(day.date) }">
              <small>{{ dateTile(day.date).month }}</small><b>{{ dateTile(day.date).day }}</b>
            </span>
            <span class="pp-row__main">
              <span class="pp-row__title">{{ isToday(day.date) ? 'Today' : dayName(day.date) }}</span>
              <span class="pp-row__sub">{{ describe(day).sub }}</span>
            </span>
            <span class="pp-row__end">
              <span v-if="describe(day).tag" class="pp-tag" :class="`pp-tag--${describe(day).tag[0]}`">{{ describe(day).tag[1] }}</span>
              <i v-if="!day.locked" class="fa fa-angle-right pp-chev"></i>
            </span>
          </component>
        </div>
        <p class="pp-group__foot">
          <i class="fa fa-clock-o"></i>Orders for a day close at {{ time(schedule.days[0]?.deadline) || schedule.cutoff }} that day. Balance today: {{ money(student.balance) }}.
        </p>
      </section>
    </template>
  </main>
</template>
