<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import {
  clearDayPreOrder,
  deleteWeeklyPreOrder,
  fetchPreOrderMenu,
  fetchPreOrders,
  fetchStudent,
  pauseWeeklyPreOrder,
  resumeWeeklyPreOrder,
  saveDayPreOrder,
  saveWeeklyPreOrder,
  skipDayPreOrder,
} from '@/api/parent'
import AppBar from '@/components/AppBar.vue'
import DayMenu from '@/components/DayMenu.vue'
import LoadError from '@/components/LoadError.vue'
import MealOption from '@/components/MealOption.vue'
import { toast } from '@/toast'
import { date as longDate, dayName, firstName, isToday, money, shortDate, sortWeekdays, time, weekdayName, weekdaysLabel } from '@/utils/format'
import { dishesOn, flattenMenu, isoWeekday, servedOn } from '@/utils/meals'
import { goBackOr } from '@/utils/nav'

/**
 * Order a meal for one day (`pre-order-day`) or every week (`pre-order-weekly`).
 * One meal per day; the card is charged when the child taps it at the canteen.
 */
const route = useRoute()
const router = useRouter()
const id = Number(route.params.id)
const weekly = route.name === 'pre-order-weekly'
const date = String(route.params.date || '')
const weekday = weekly ? null : isoWeekday(date)
const listRoute = { name: 'pre-orders', params: { id } }

const student = ref(null)
const schedule = ref(null)
const meals = ref([])
const status = ref('loading')
const loadError = ref('')

const mealId = ref(null)
const days = ref([])
const note = ref('')
const busy = ref('')
const error = ref('')

const first = computed(() => firstName(student.value?.name))
const day = computed(() => (weekly ? null : schedule.value?.days.find((d) => d.date === date) || null))
const current = computed(() => (weekly ? schedule.value?.weekly : day.value?.order) || null)
const meal = computed(() => meals.value.find((m) => m.id === mealId.value) || null)
const offered = computed(() => (weekly ? meals.value : meals.value.filter((m) => servedOn(m, weekday))))
const schoolDays = computed(() => sortWeekdays(schedule.value?.school_days || []))
const locked = computed(() => !weekly && (day.value?.locked ?? false))
const weeklyCoversDay = computed(() => {
  const w = schedule.value?.weekly
  return !weekly && w?.status === 'active' && w.weekdays.includes(weekday)
})
const lowBalance = computed(() => meal.value && Number(student.value?.available ?? 0) < Number(meal.value.mrp))

const title = computed(() => (weekly ? 'Every week' : isToday(date) ? 'Today' : shortDate(date)))
const saveLabel = computed(() => {
  if (weekly) return current.value ? 'Save weekly order' : 'Order every week'
  if (day.value?.source === 'day') return 'Save changes'
  if (day.value?.source === 'weekly') return 'Save for this day only'
  return `Order for ${isToday(date) ? 'today' : dayName(date)}`
})

async function load() {
  status.value = 'loading'
  try {
    const [s, sch, menu] = await Promise.all([fetchStudent(id), fetchPreOrders(id), fetchPreOrderMenu()])
    ;[student.value, schedule.value, meals.value] = [s, sch, flattenMenu(menu)]

    if (!weekly && !day.value) {
      loadError.value = 'You can only order for the coming school days.'
      status.value = 'error'
      return
    }

    // Start from what is ordered; otherwise the first meal served that day.
    mealId.value = current.value?.items?.[0]?.product_id ?? offered.value[0]?.id ?? null
    note.value = current.value?.note || ''
    days.value = weekly ? [...(current.value?.weekdays || schoolDays.value.filter((d) => servedOn(meal.value, d)))] : []
    status.value = 'ready'
  } catch (e) {
    loadError.value = e.status === 404 ? "This child isn't linked to your login." : e.message
    status.value = 'error'
  }
}

function selectMeal(next) {
  mealId.value = next.id
  error.value = ''
  // Days the new meal is not served drop off the weekly order.
  if (weekly) days.value = days.value.filter((d) => servedOn(next, d))
}

function toggleDay(d) {
  days.value = days.value.includes(d) ? days.value.filter((x) => x !== d) : [...days.value, d]
  error.value = ''
}

async function run(kind, request, message) {
  busy.value = kind
  error.value = ''
  try {
    await request()
    toast(message)
    goBackOr(router, listRoute)
  } catch (e) {
    error.value = e.message
    window.scrollTo({ top: 0, behavior: 'smooth' })
  } finally {
    busy.value = ''
  }
}

function save() {
  if (!meal.value) {
    error.value = 'Choose a meal.'
    return
  }
  if (weekly && !days.value.length) {
    error.value = 'Pick at least one day.'
    return
  }
  const payload = { items: [{ product_id: meal.value.id, quantity: 1 }], note: note.value.trim() || null }
  if (weekly) {
    run('save', () => saveWeeklyPreOrder(id, { ...payload, weekdays: sortWeekdays(days.value) }), `${first.value}'s weekly meal is set`)
  } else {
    run('save', () => saveDayPreOrder(id, date, payload), `Meal ordered for ${isToday(date) ? 'today' : dayName(date)}`)
  }
}

const skip = () => run('skip', () => skipDayPreOrder(id, date), `No meal on ${dayName(date)}`)
const clear = () =>
  run('clear', () => clearDayPreOrder(id, date), weeklyCoversDay.value ? `${dayName(date)} is back on the weekly order` : `${dayName(date)}'s order removed`)
const pause = () => run('pause', () => pauseWeeklyPreOrder(id), 'Weekly order paused')
const resume = () => run('resume', () => resumeWeeklyPreOrder(id), 'Weekly order is on again')
function removeWeekly() {
  if (!window.confirm(`Remove ${first.value}'s weekly order? Days you ordered one by one stay ordered.`)) return
  run('remove', () => deleteWeeklyPreOrder(id), 'Weekly order removed')
}

onMounted(load)
</script>

<template>
  <AppBar :back="listRoute" back-label="Meals" :title="status === 'ready' ? title : ''" />

  <LoadError v-if="status === 'error'" title="We couldn't open this" :message="loadError" @retry="load">
    <RouterLink class="pp-link" :to="listRoute">Back to meals</RouterLink>
  </LoadError>

  <main v-else-if="status === 'loading'" aria-busy="true">
    <div class="pp-largetitle"><span class="pp-skel pp-skel--title"></span></div>
    <div class="pp-group"><span class="pp-skel" style="display: block; height: 140px; border-radius: 14px"></span></div>
  </main>

  <template v-else>
    <main>
      <div class="pp-largetitle">
        <h1>{{ weekly ? 'Every week' : dayName(date) }}</h1>
        <p v-if="weekly">Pick {{ first }}'s meal and the days. It's ordered automatically until you pause it.</p>
        <p v-else>{{ longDate(date) }} · orders close at {{ time(day.deadline) }}</p>
      </div>

      <div v-if="error" class="pp-alert" role="alert">
        <i class="fa fa-exclamation-circle"></i><span class="pp-alert__main">{{ error }}</span>
      </div>

      <div v-if="weekly && current?.status === 'paused'" class="pp-alert pp-alert--warn">
        <i class="fa fa-pause"></i>
        <span class="pp-alert__main"><span class="pp-alert__title">Weekly order paused</span>Nothing is ordered until you switch it back on.</span>
        <button class="pp-alert__btn" type="button" :disabled="Boolean(busy)" @click="resume">Resume</button>
      </div>

      <div v-if="!weekly && day.source === 'skipped'" class="pp-alert pp-alert--info">
        <i class="fa fa-info-circle"></i>
        <span class="pp-alert__main"><span class="pp-alert__title">No meal this day</span>You chose not to order on {{ dayName(date) }}.</span>
      </div>

      <div v-if="!offered.length" class="pp-alert pp-alert--warn">
        <i class="fa fa-exclamation-triangle"></i>
        <span class="pp-alert__main">{{ weekly ? 'There are no meals to order right now.' : `No meal is served on ${dayName(date)}.` }}</span>
      </div>

      <template v-else>
        <section class="pp-group">
          <h2 class="pp-group__head">Meal</h2>
          <div class="pp-group__body" role="radiogroup" aria-label="Meal">
            <MealOption v-for="option in offered" :key="option.id" :meal="option" :selected="option.id === mealId" @select="selectMeal" />
          </div>
        </section>

        <section v-if="weekly" class="pp-group">
          <h2 class="pp-group__head">Days</h2>
          <div class="pp-daychips" role="group" aria-label="Days">
            <button
              v-for="d in schoolDays"
              :key="d"
              class="pp-chip"
              type="button"
              :aria-pressed="days.includes(d)"
              :disabled="!servedOn(meal, d)"
              :aria-label="weekdayName(d, true)"
              @click="toggleDay(d)"
            >
              {{ weekdayName(d) }}
            </button>
          </div>
          <p class="pp-group__foot">
            {{ days.length ? `${weekdaysLabel(days)} · ${money((meal?.mrp || 0) * days.length)} a week` : 'Tap the days to order.' }}
          </p>
        </section>

        <section v-if="!weekly && meal" class="pp-group">
          <h2 class="pp-group__head">On the menu</h2>
          <DayMenu :dishes="dishesOn(meal, weekday)" />
        </section>

        <section v-if="weekly && meal?.menu?.length" class="pp-group pp-week-menu">
          <h2 class="pp-group__head">This week's dishes</h2>
          <div class="pp-group__body">
            <div v-for="entry in meal.menu" :key="entry.weekday" class="pp-row">
              <span class="pp-row__main">
                <span class="pp-row__title">{{ weekdayName(entry.weekday, true) }}</span>
                <span class="pp-row__sub">{{ entry.dishes.map((dish) => dish.name).join(' · ') || 'No dishes listed' }}</span>
              </span>
            </div>
          </div>
        </section>

        <section class="pp-group">
          <div class="pp-group__body">
            <div class="pp-field">
              <label class="pp-field__body">
                <span class="pp-field__label">Note for the canteen (optional)</span>
                <textarea v-model="note" class="pp-textarea" rows="2" maxlength="200" placeholder="For example, no sauce"></textarea>
              </label>
            </div>
          </div>
        </section>

        <div v-if="lowBalance" class="pp-alert pp-alert--warn">
          <i class="fa fa-exclamation-triangle"></i>
          <span class="pp-alert__main">
            <span class="pp-alert__title">Top up before the day</span>{{ first }}'s card can spend {{ money(student.available) }} now, and the meal is
            {{ money(meal.mrp) }}.
          </span>
          <RouterLink class="pp-alert__btn" :to="{ name: 'topup', params: { id } }">Top up</RouterLink>
        </div>
      </template>

      <div class="pp-inline-actions">
        <template v-if="weekly && current">
          <button v-if="current.status === 'active'" class="pp-link" type="button" :disabled="Boolean(busy)" @click="pause">
            <i class="fa fa-pause"></i>&nbsp;Pause weekly order
          </button>
          <button class="pp-link pp-text-neg" type="button" :disabled="Boolean(busy)" @click="removeWeekly">Remove weekly order</button>
        </template>
        <template v-if="!weekly && !locked">
          <button v-if="day.source === 'weekly'" class="pp-link pp-text-neg" type="button" :disabled="Boolean(busy)" @click="skip">
            Don't order on {{ dayName(date) }}
          </button>
          <button v-if="day.source === 'day' || day.source === 'skipped'" class="pp-link" :class="{ 'pp-text-neg': !weeklyCoversDay }" type="button" :disabled="Boolean(busy)" @click="clear">
            {{ weeklyCoversDay ? 'Use the weekly order this day' : "Remove this day's order" }}
          </button>
        </template>
      </div>
    </main>

    <footer v-if="offered.length && !locked" class="pp-actionbar">
      <div v-if="meal" class="pp-order-sum">
        <span>{{ meal.name }} · paid at the canteen</span>
        <b>{{ money(weekly ? meal.mrp * days.length : meal.mrp) }}<small v-if="weekly" style="font-weight: 500"> /wk</small></b>
      </div>
      <button class="pp-btn pp-btn--primary" :class="{ 'is-busy': busy === 'save' }" type="button" :disabled="Boolean(busy) || !meal" @click="save">
        <span v-if="busy === 'save'" class="pp-spinner pp-spinner--sm" aria-hidden="true"></span>{{ busy === 'save' ? 'Saving…' : saveLabel }}
      </button>
    </footer>
  </template>
</template>
