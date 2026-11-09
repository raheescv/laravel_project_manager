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
import BottomSheet from '@/components/BottomSheet.vue'
import DayMenu from '@/components/DayMenu.vue'
import LoadError from '@/components/LoadError.vue'
import MealCart from '@/components/MealCart.vue'
import MealOption from '@/components/MealOption.vue'
import { refreshChild } from '@/children'
import { toast } from '@/toast'
import { date as longDate, dayName, firstName, isToday, money, shortDate, sortWeekdays, time, weekdayName, weekdaysLabel } from '@/utils/format'
import { dishesOn, flattenMenu, isoWeekday, mealThumbStyle, servedOn } from '@/utils/meals'
import { goBackOr } from '@/utils/nav'

/**
 * Order meals for one day (`pre-order-day`) or every week (`pre-order-weekly`).
 * Any number of items and quantities; those lines become the sale when the child taps the card at the canteen.
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
const menuGroups = ref([])
const mealSearch = ref('')
const mealCategory = ref(null)
const status = ref('loading')
const loadError = ref('')

const MEAL_VIEW_KEY = 'pp.mealView'
function savedMealView() {
  try {
    return localStorage.getItem(MEAL_VIEW_KEY) === 'list' ? 'list' : 'grid'
  } catch {
    return 'grid'
  }
}

const mealView = ref(savedMealView())
const cartOpen = ref(false)
const quantities = ref({})
const days = ref([])
const note = ref('')
const busy = ref('')
const error = ref('')

const first = computed(() => firstName(student.value?.name))
const day = computed(() => (weekly ? null : schedule.value?.days.find((d) => d.date === date) || null))
const current = computed(() => (weekly ? schedule.value?.weekly : day.value?.order) || null)
const maxQuantity = computed(() => schedule.value?.max_quantity || 10)
const selectedMeals = computed(() => meals.value.filter((m) => quantities.value[m.id] > 0))
const selectedTotal = computed(() => selectedMeals.value.reduce((sum, m) => sum + Number(m.mrp) * quantities.value[m.id], 0))
const selectedCount = computed(() => selectedMeals.value.reduce((sum, m) => sum + quantities.value[m.id], 0))
const selectedLabel = computed(() => (selectedCount.value === 1 ? selectedMeals.value[0].name : `${selectedCount.value} items`))
const dayDishes = computed(() => selectedMeals.value.flatMap((m) => dishesOn(m, weekday)))
const weekMenu = computed(() => {
  const dishes = new Map()
  selectedMeals.value.flatMap((m) => m.menu || []).forEach((entry) => dishes.set(entry.weekday, [...(dishes.get(entry.weekday) || []), ...entry.dishes]))
  return sortWeekdays([...dishes.keys()]).map((d) => ({ weekday: d, dishes: dishes.get(d) }))
})
const offered = computed(() => (weekly ? meals.value : meals.value.filter((m) => servedOn(m, weekday))))
const categories = computed(() => menuGroups.value.filter((group) => (group.items || []).some((item) => offered.value.some((m) => m.id === item.id))))
const shownMeals = computed(() => {
  const search = mealSearch.value.trim().toLowerCase()
  const inCategory = mealCategory.value === null ? null : categories.value.find((group) => group.id === mealCategory.value)?.items.map((item) => item.id)
  return offered.value.filter((m) => (!inCategory || inCategory.includes(m.id)) && (!search || m.name.toLowerCase().includes(search)))
})
const schoolDays = computed(() => sortWeekdays(schedule.value?.school_days || []))
const locked = computed(() => !weekly && (day.value?.locked ?? false))
const weeklyCoversDay = computed(() => {
  const w = schedule.value?.weekly
  return !weekly && w?.status === 'active' && w.weekdays.includes(weekday)
})
const lowBalance = computed(() => selectedMeals.value.length > 0 && Number(student.value?.available ?? 0) < selectedTotal.value)
const servedOnSelected = (d) => selectedMeals.value.every((m) => servedOn(m, d))

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
    ;[student.value, schedule.value, meals.value, menuGroups.value] = [s, sch, flattenMenu(menu), menu || []]
    refreshChild(s)

    if (!weekly && !day.value) {
      loadError.value = 'You can only order for the coming school days.'
      status.value = 'error'
      return
    }

    // Start from what is ordered and still offered; a new order starts with nothing ticked.
    const offeredIds = offered.value.map((m) => m.id)
    quantities.value = Object.fromEntries((current.value?.items || []).filter((item) => offeredIds.includes(item.product_id)).map((item) => [item.product_id, item.quantity]))
    note.value = current.value?.note || ''
    days.value = weekly ? [...(current.value?.weekdays || schoolDays.value.filter(servedOnSelected))] : []
    status.value = 'ready'
  } catch (e) {
    loadError.value = e.status === 404 ? "This child isn't linked to your login." : e.message
    status.value = 'error'
  }
}

function setMealView(next) {
  mealView.value = next
  try {
    localStorage.setItem(MEAL_VIEW_KEY, next)
  } catch {
    // Storage blocked: the choice lasts for this visit.
  }
}

function setQuantity(option, quantity) {
  const adding = !quantities.value[option.id] && quantity > 0
  const next = { ...quantities.value }
  if (quantity > 0) next[option.id] = Math.min(quantity, maxQuantity.value)
  else delete next[option.id]
  quantities.value = next
  error.value = ''
  // Days an added meal is not served drop off the weekly order.
  if (weekly && adding) days.value = days.value.filter((d) => servedOn(option, d))
  if (!selectedMeals.value.length) cartOpen.value = false
}

const addMeal = (option) => setQuantity(option, (quantities.value[option.id] || 0) + 1)

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
  if (!selectedMeals.value.length) {
    error.value = 'Choose at least one item.'
    return
  }
  if (weekly && !days.value.length) {
    error.value = 'Pick at least one day.'
    return
  }
  const payload = { items: selectedMeals.value.map((m) => ({ product_id: m.id, quantity: quantities.value[m.id] })), note: note.value.trim() || null }
  if (weekly) {
    run('save', () => saveWeeklyPreOrder(id, { ...payload, weekdays: sortWeekdays(days.value) }), `${first.value}'s weekly order is set`)
  } else {
    run('save', () => saveDayPreOrder(id, date, payload), `Ordered for ${isToday(date) ? 'today' : dayName(date)}`)
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
    <main class="pp-order">
      <div class="pp-largetitle">
        <h1>{{ weekly ? 'Every week' : dayName(date) }}</h1>
        <p v-if="weekly">Pick {{ first }}'s meals and the days. They're ordered automatically until you pause it.</p>
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

      <div v-else class="pp-order__layout">
        <section class="pp-group pp-order__meals">
          <div class="pp-meals-head">
            <h2 class="pp-group__head">Meals · add one or more</h2>
            <div class="pp-viewswitch" role="group" aria-label="Show meals as">
              <button type="button" aria-label="List" title="List" :aria-pressed="mealView === 'list'" @click="setMealView('list')"><i class="fa fa-list"></i></button>
              <button type="button" aria-label="Grid" title="Grid" :aria-pressed="mealView === 'grid'" @click="setMealView('grid')"><i class="fa fa-th-large"></i></button>
            </div>
          </div>
          <div class="pp-meal-tools">
            <label class="pp-meal-search">
              <i class="fa fa-search" aria-hidden="true"></i>
              <input v-model="mealSearch" type="search" placeholder="Search meals" aria-label="Search meals" autocomplete="off" />
            </label>
            <div v-if="categories.length > 1" class="pp-meal-cats" role="group" aria-label="Categories">
              <button class="pp-chip" type="button" :aria-pressed="mealCategory === null" @click="mealCategory = null">All</button>
              <button v-for="group in categories" :key="group.id" class="pp-chip" type="button" :aria-pressed="mealCategory === group.id" @click="mealCategory = group.id">{{ group.name }}</button>
            </div>
          </div>
          <div v-if="shownMeals.length" class="pp-group__body" :class="`pp-meals--${mealView}`" role="group" aria-label="Meals">
            <MealOption v-for="option in shownMeals" :key="option.id" :meal="option" :quantity="quantities[option.id] || 0" :max="maxQuantity" @add="addMeal" />
          </div>
          <p v-else class="pp-meal-none">No meals match your search.</p>
        </section>

        <div class="pp-order__side">
        <section v-if="weekly" class="pp-group">
          <h2 class="pp-group__head">Days</h2>
          <div class="pp-daychips" role="group" aria-label="Days">
            <button
              v-for="d in schoolDays"
              :key="d"
              class="pp-chip"
              type="button"
              :aria-pressed="days.includes(d)"
              :disabled="!servedOnSelected(d)"
              :aria-label="weekdayName(d, true)"
              @click="toggleDay(d)"
            >
              {{ weekdayName(d) }}
            </button>
          </div>
          <p class="pp-group__foot">
            {{ days.length ? `${weekdaysLabel(days)} · ${money(selectedTotal * days.length)} a week` : 'Tap the days to order.' }}
          </p>
        </section>

        <section class="pp-group pp-cart-panel">
          <h2 class="pp-group__head">Your order<span v-if="selectedCount">{{ selectedCount }} {{ selectedCount === 1 ? 'item' : 'items' }}</span></h2>
          <MealCart :meals="selectedMeals" :quantities="quantities" :total="selectedTotal" :max="maxQuantity" :days="weekly ? days.length : null" @quantity="setQuantity" />
        </section>

        <section v-if="!weekly && dayDishes.length" class="pp-group">
          <h2 class="pp-group__head">On the menu</h2>
          <DayMenu :dishes="dayDishes" />
        </section>

        <section v-if="weekly && weekMenu.length" class="pp-group pp-week-menu">
          <h2 class="pp-group__head">This week's dishes</h2>
          <div class="pp-group__body">
            <div v-for="entry in weekMenu" :key="entry.weekday" class="pp-row">
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
            <span class="pp-alert__title">Top up before the day</span>{{ first }}'s card can spend {{ money(student.available) }} now, and the order is
            {{ money(selectedTotal) }}.
          </span>
          <RouterLink class="pp-alert__btn" :to="{ name: 'topup', params: { id } }">Top up</RouterLink>
        </div>
        </div>
      </div>

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
      <button v-if="selectedMeals.length" class="pp-order-sum" type="button" :aria-label="`Review your order: ${selectedLabel}`" @click="cartOpen = true">
        <span class="pp-order-sum__thumbs" aria-hidden="true">
          <span v-for="option in selectedMeals.slice(0, 3)" :key="option.id" class="pp-thumb" :style="mealThumbStyle(option)"><i v-if="!mealThumbStyle(option)" class="fa fa-cutlery"></i></span>
        </span>
        <span class="pp-order-sum__label">
          <span>{{ selectedLabel }} <i class="fa fa-angle-up" aria-hidden="true"></i></span>
          <small>Paid at the canteen</small>
        </span>
      </button>
      <div v-if="selectedMeals.length" class="pp-order-total">
        <span>Grand total</span>
        <b>{{ money(weekly ? selectedTotal * days.length : selectedTotal) }}<small v-if="weekly"> /wk</small></b>
      </div>
      <button class="pp-btn pp-btn--primary" :class="{ 'is-busy': busy === 'save' }" type="button" :disabled="Boolean(busy) || !selectedMeals.length" @click="save">
        <span v-if="busy === 'save'" class="pp-spinner pp-spinner--sm" aria-hidden="true"></span>{{ busy === 'save' ? 'Saving…' : saveLabel }}
      </button>
    </footer>

    <BottomSheet :open="cartOpen" label="Your order" @close="cartOpen = false">
      <div class="pp-sheet__grabber"></div>
      <div class="pp-sheet__bar pp-cart-sheet__bar">
        <div class="pp-sheet__title">Your order</div>
        <button class="pp-navbtn" type="button" @click="cartOpen = false">Done</button>
      </div>
      <MealCart :meals="selectedMeals" :quantities="quantities" :total="selectedTotal" :max="maxQuantity" :days="weekly ? days.length : null" @quantity="setQuantity" />
    </BottomSheet>
  </template>
</template>
