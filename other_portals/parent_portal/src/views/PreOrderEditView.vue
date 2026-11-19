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
import ChildSwitcher from '@/components/ChildSwitcher.vue'
import DayMenu from '@/components/DayMenu.vue'
import LoadError from '@/components/LoadError.vue'
import MealCart from '@/components/MealCart.vue'
import MealOption from '@/components/MealOption.vue'
import StudentAvatar from '@/components/StudentAvatar.vue'
import { refreshChild } from '@/children'
import { toast } from '@/toast'
import { date as longDate, dayName, firstName, isToday, money, shortDate, sortWeekdays, time, weekdayName, weekdaysLabel } from '@/utils/format'
import { BOOKING_WINDOW_DAYS, dishesOn, flattenMenu, isoWeekday, mealThumbStyle, servedOn } from '@/utils/meals'
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
    return localStorage.getItem(MEAL_VIEW_KEY) === 'grid' ? 'grid' : 'list'
  } catch {
    return 'list'
  }
}

const mealView = ref(savedMealView())
const cartOpen = ref(false)
const quantities = ref({})
const days = ref([])
const note = ref('')
// 'card': charged to the student card at the canteen. 'bank': saved, then the card is topped up for it by bank card.
const payBy = ref('card')
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
/**
 * A pre-order is paid from money on the card only: the balance must cover the whole
 * order (the school's overdraft does not count). Changing a paid day refunds it
 * first, so its own amount counts towards the new one.
 */
const cardMoney = computed(() => Number(student.value?.balance ?? 0) + (!weekly && day.value?.paid ? Number(day.value.order?.total || 0) : 0))
const cardCovers = computed(() => cardMoney.value + 0.001 >= (weekly ? selectedTotal.value : orderAmount.value))
const lowBalance = computed(() => selectedMeals.value.length > 0 && !cardCovers.value)
const bankCardOffered = computed(() => Boolean(student.value?.topup?.enabled))
/** What the order costs: the day, or one week of the weekly order. */
const orderAmount = computed(() => (weekly ? selectedTotal.value * days.value.length : selectedTotal.value))
/** A bank card payment for a pre-order is exactly the order's total (the top-up range does not apply). */
const bankAmount = computed(() => orderAmount.value)
// Bank card pays a single day; a weekly order is paid from the card day by day when each day closes.
// A card that cannot cover the order leaves bank card as the only way to pay.
const payingByBank = computed(() => !weekly && bankCardOffered.value && (payBy.value === 'bank' || !cardCovers.value))
// Nothing can pay this day: not enough on the card and no online payment.
const cannotPay = computed(() => !weekly && selectedMeals.value.length > 0 && !cardCovers.value && !bankCardOffered.value)
const paid = computed(() => !weekly && Boolean(day.value?.paid))
/** The deadline in words: "6:00 PM" on the day itself, "Sunday 6:00 PM" when it falls the day before. */
const closesAt = computed(() => {
  if (!day.value?.deadline) return ''
  const deadline = new Date(day.value.deadline)
  const sameDay = deadline.toDateString() === new Date(`${date}T00:00:00`).toDateString()
  return sameDay ? time(day.value.deadline) : `${dayName(deadline)} ${time(day.value.deadline)}`
})
const servedOnSelected = (d) => selectedMeals.value.every((m) => servedOn(m, d))
/** A saved day order nobody has paid for yet: kept by "Save" or a bank card payment that never arrived. */
const unpaidSaved = computed(() => !weekly && day.value?.source === 'day' && !paid.value)
// "Save" keeps the cart without paying. Not offered on a paid day, where saving a change pays the new total.
const canSaveOnly = computed(() => !weekly && !paid.value && selectedMeals.value.length > 0)
const orderKey = (lines, text) => JSON.stringify([lines.map((line) => [line.product_id, line.quantity]).sort((a, b) => a[0] - b[0]), (text || '').trim()])
const savedKey = computed(() => (unpaidSaved.value ? orderKey(current.value?.items || [], current.value?.note) : ''))
const unsaved = computed(() => orderKey(selectedMeals.value.map((m) => ({ product_id: m.id, quantity: quantities.value[m.id] })), note.value) !== savedKey.value)

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
    const [s, sch, menu] = await Promise.all([fetchStudent(id), fetchPreOrders(id, weekly ? undefined : BOOKING_WINDOW_DAYS), fetchPreOrderMenu()])
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

async function run(kind, request, message, after = null) {
  busy.value = kind
  error.value = ''
  try {
    await request()
    toast(message)
    if (after) after()
    else goBackOr(router, listRoute)
  } catch (e) {
    error.value = e.message
    window.scrollTo({ top: 0, behavior: 'smooth' })
  } finally {
    busy.value = ''
  }
}

function orderPayload() {
  if (!selectedMeals.value.length) {
    error.value = 'Choose at least one item.'
    return null
  }
  if (weekly && !days.value.length) {
    error.value = 'Pick at least one day.'
    return null
  }
  return { items: selectedMeals.value.map((m) => ({ product_id: m.id, quantity: quantities.value[m.id] })), note: note.value.trim() || null }
}

/** Keep the cart as it is, unpaid, and stay here to pay it now or later. */
function saveOnly() {
  const payload = orderPayload()
  if (!payload) return
  const refresh = () => fetchPreOrders(id, BOOKING_WINDOW_DAYS).then((sch) => (schedule.value = sch)).catch(() => {})
  run('save-only', () => saveDayPreOrder(id, date, { ...payload, pay: 'later' }), 'Order saved · not paid yet', refresh)
}

function save() {
  const payload = orderPayload()
  if (!payload) return
  // Bank card: the order is kept first, then the top-up page opens with its amount, so
  // a payment the parent abandons never loses the order. Replace, so Back skips this page.
  const amount = bankAmount.value.toFixed(2)
  const thenPay = payingByBank.value ? () => router.replace({ name: 'topup', params: { id }, query: { for: 'pre-order', date, amount } }) : null
  const when = isToday(date) ? 'today' : dayName(date)
  if (weekly) {
    run('save', () => saveWeeklyPreOrder(id, { ...payload, weekdays: sortWeekdays(days.value) }), `${first.value}'s weekly order is set`)
  } else {
    const pay = payingByBank.value ? 'bank' : 'card'
    run('save', () => saveDayPreOrder(id, date, { ...payload, pay }), payingByBank.value ? `Order saved. Now pay by bank card` : `Paid · ordered for ${when}`, thenPay)
  }
}

const refunded = () => (paid.value ? `. ${money(current.value?.total || 0)} is back on the card` : '')
const skip = () => run('skip', () => skipDayPreOrder(id, date), `No meal on ${dayName(date)}${refunded()}`)
const clear = () =>
  run('clear', () => clearDayPreOrder(id, date), (weeklyCoversDay.value ? `${dayName(date)} is back on the weekly order` : `${dayName(date)}'s order removed`) + refunded())
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
      <ChildSwitcher label="Ordering for" :current="id" :unsaved="!weekly && selectedMeals.length > 0 && unsaved" />
      <div class="pp-largetitle">
        <h1>{{ weekly ? 'Every week' : dayName(date) }}</h1>
        <p v-if="weekly">Pick {{ first }}'s meals and the days. They're ordered automatically until you pause it.</p>
        <p v-else>{{ longDate(date) }} · orders close {{ closesAt }}</p>
      </div>

      <div v-if="error" class="pp-alert" role="alert">
        <i class="fa fa-exclamation-circle"></i><span class="pp-alert__main">{{ error }}</span>
      </div>

      <div v-if="weekly && current?.status === 'paused'" class="pp-alert pp-alert--warn">
        <i class="fa fa-pause"></i>
        <span class="pp-alert__main"><span class="pp-alert__title">Weekly order paused</span>Nothing is ordered until you switch it back on.</span>
        <button class="pp-alert__btn" type="button" :disabled="Boolean(busy)" @click="resume">Resume</button>
      </div>

      <div v-if="paid" class="pp-alert pp-alert--info">
        <i class="fa fa-check-circle"></i>
        <span class="pp-alert__main">
          <span class="pp-alert__title">Paid · {{ money(current?.total || 0) }}</span>Changing the order before it closes puts this back on {{ first }}'s card and takes the new total. Removing it gives the money back.
        </span>
      </div>

      <div v-if="unpaidSaved" class="pp-alert pp-alert--warn">
        <i class="fa fa-clock-o"></i>
        <span class="pp-alert__main">
          <span class="pp-alert__title">Saved · not paid yet</span>Pay now to book it. If it is still unpaid when orders close {{ closesAt }}, it is taken from {{ first }}'s card, and not served if the card is short.
        </span>
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
        <section class="pp-group pp-order__meals" data-tour="meals">
          <div class="pp-meals-head">
            <h2 class="pp-group__head">Meals · add one or more</h2>
            <div class="pp-viewswitch" role="group" aria-label="Show meals as">
              <button type="button" aria-label="List" title="List" :aria-pressed="mealView === 'list'" @click="setMealView('list')"><i class="fa fa-list"></i></button>
              <button type="button" aria-label="Grid" title="Grid" :aria-pressed="mealView === 'grid'" @click="setMealView('grid')"><i class="fa fa-th-large"></i></button>
            </div>
          </div>
          <div class="pp-meal-tools" data-tour="meal-search">
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
        <section v-if="weekly" class="pp-group" data-tour="weekdays">
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

        <section class="pp-group pp-cart-panel" data-tour="cart">
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

        <div v-if="weekly ? lowBalance : cannotPay" class="pp-alert pp-alert--warn">
          <i class="fa fa-exclamation-triangle"></i>
          <span class="pp-alert__main">
            <template v-if="weekly"><span class="pp-alert__title">Keep the card topped up</span>Each day is paid from {{ first }}'s card when its orders close. It has {{ money(student.balance) }} now, and a day costs {{ money(selectedTotal) }}; a day the balance cannot cover is not ordered.</template>
            <template v-else><span class="pp-alert__title">Not enough on the card</span>{{ first }}'s card has {{ money(student.balance) }}, and the order is {{ money(orderAmount) }}. Online payment isn't available right now, so please top up at the school office first.</template>
          </span>
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

    <footer v-if="offered.length && !locked" class="pp-actionbar" data-tour="order-bar">
      <button v-if="selectedMeals.length" class="pp-order-sum" type="button" :aria-label="`Review your order: ${selectedLabel}`" @click="cartOpen = true">
        <span class="pp-order-sum__thumbs" aria-hidden="true">
          <span v-for="option in selectedMeals.slice(0, 3)" :key="option.id" class="pp-thumb" :style="mealThumbStyle(option)"><i v-if="!mealThumbStyle(option)" class="fa fa-cutlery"></i></span>
        </span>
        <span class="pp-order-sum__label">
          <span>{{ selectedLabel }} <i class="fa fa-angle-up" aria-hidden="true"></i></span>
          <small>{{ weekly ? 'Paid from the card each day' : payingByBank ? 'Pay now by bank card' : 'Paid now from the card' }}</small>
        </span>
      </button>
      <div v-if="selectedMeals.length" class="pp-order-total">
        <span>Grand total</span>
        <b>{{ money(weekly ? selectedTotal * days.length : selectedTotal) }}<small v-if="weekly"> /wk</small></b>
      </div>
      <div v-if="!weekly && selectedMeals.length" class="pp-paywith" role="radiogroup" aria-label="Pay with">
        <button
          class="pp-paywith__opt"
          type="button"
          role="radio"
          :aria-checked="!payingByBank && cardCovers"
          :disabled="!cardCovers"
          :title="cardCovers ? `Pay from ${first}'s card` : 'Not enough on the card for this order'"
          @click="payBy = 'card'"
        >
          <StudentAvatar :name="student.name" :image="student.image_url" />
          <span class="pp-paywith__text">
            <b>{{ first }}'s card</b>
            <small>{{ cardCovers ? money(student.balance) : `Short · ${money(student.balance)}` }}</small>
          </span>
          <i class="fa fa-check-circle pp-paywith__check" aria-hidden="true"></i>
        </button>
        <button v-if="bankCardOffered" class="pp-paywith__opt" type="button" role="radio" :aria-checked="payingByBank" title="Pay on the bank's secure page" @click="payBy = 'bank'">
          <span class="pp-paywith__icon"><i class="fa fa-university" aria-hidden="true"></i></span>
          <span class="pp-paywith__text">
            <b>Bank card</b>
            <small>Secure bank page</small>
          </span>
          <i class="fa fa-check-circle pp-paywith__check" aria-hidden="true"></i>
        </button>
      </div>
      <div class="pp-order-actions">
        <button v-if="canSaveOnly" class="pp-btn pp-btn--tinted pp-order-actions__save" :class="{ 'is-busy': busy === 'save-only' }" type="button" :disabled="Boolean(busy) || !unsaved" :title="unsaved ? 'Save the order without paying' : 'No changes to save'" @click="saveOnly">
          <span v-if="busy === 'save-only'" class="pp-spinner pp-spinner--sm" aria-hidden="true"></span>{{ busy === 'save-only' ? 'Saving…' : unsaved ? 'Save' : 'Saved' }}
        </button>
        <button class="pp-btn pp-btn--primary" :class="{ 'is-busy': busy === 'save' }" type="button" :disabled="Boolean(busy) || !selectedMeals.length || cannotPay" @click="save">
          <span v-if="busy === 'save'" class="pp-spinner pp-spinner--sm" aria-hidden="true"></span>{{ busy === 'save' ? 'Saving…' : weekly ? saveLabel : payingByBank ? 'Save and pay by bank card' : paid ? 'Save changes' : `Pay ${money(orderAmount)}` }}
        </button>
      </div>
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
