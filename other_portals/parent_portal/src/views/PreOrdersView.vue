<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'

import { clearDayPreOrder, fetchPreOrderMenu, fetchPreOrders, fetchStudent, skipDayPreOrder } from '@/api/parent'
import AppBar from '@/components/AppBar.vue'
import BottomSheet from '@/components/BottomSheet.vue'
import ChildSwitcher from '@/components/ChildSwitcher.vue'
import LoadError from '@/components/LoadError.vue'
import MealCalendar from '@/components/MealCalendar.vue'
import { refreshChild } from '@/children'
import { toast } from '@/toast'
import { dateTile, dayName, firstName, isToday, money, time, weekdaysLabel } from '@/utils/format'
import { flattenMenu, isoWeekday, servedOn } from '@/utils/meals'
import { bigScreen, desktop } from '@/utils/viewport'

/**
 * A child's canteen meals: the weekly order, what happens on each of the next
 * school days, and the paid orders, each of which can be cancelled until that
 * day's orders close (the money goes back on the card).
 */
const route = useRoute()
const id = Number(route.params.id)

const student = ref(null)
const schedule = ref(null)
const meals = ref([])
const status = ref('loading')
const error = ref('')

const first = computed(() => firstName(student.value?.name))
/** "Orders for a day close at 6:00 PM the day before. Balance today: …" — the rule is worded by the API. */
const cutoffNote = computed(() => {
  const rule = schedule.value?.cutoff_label || `${time(schedule.value?.days[0]?.deadline) || schedule.value?.cutoff} that day`
  return `Orders for a day close at ${rule}. Balance today: ${money(student.value?.balance)}.`
})
function mealName(order) {
  const names = (order?.items || []).map((item) => (item.quantity > 1 ? `${item.quantity} × ${item.name}` : item.name))
  if (names.length > 2) return `${names.slice(0, 2).join(', ')} +${names.length - 2} more`
  return names.join(', ') || 'Meal'
}

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

/* ---- Paid orders: cancel before the day closes ---- */
const paidDays = computed(() => (schedule.value?.days || []).filter((day) => day.order && day.paid))
const cancelling = ref(null)
const cancelBusy = ref(false)
const cancelError = ref('')

function askCancel(day) {
  cancelling.value = day
  cancelError.value = ''
}

/**
 * Cancelling a day the weekly order also covers is a skip: clearing it would hand
 * the day back to the weekly order, which would charge it again when orders close.
 */
async function confirmCancel() {
  const day = cancelling.value
  const weekly = schedule.value?.weekly
  const coveredByWeekly = weekly?.status === 'active' && weekly.weekdays.includes(isoWeekday(day.date))
  cancelBusy.value = true
  cancelError.value = ''
  try {
    await (coveredByWeekly ? skipDayPreOrder(id, day.date) : clearDayPreOrder(id, day.date))
    const [s, sch] = await Promise.all([fetchStudent(id), fetchPreOrders(id)])
    ;[student.value, schedule.value] = [s, sch]
    refreshChild(s)
    cancelling.value = null
    toast(`${dayName(day.date)}'s order cancelled. ${money(day.order.total)} is back on the card`)
  } catch (e) {
    cancelError.value = e.message
  } finally {
    cancelBusy.value = false
  }
}

/** What a day row says. */
/**
 * A day as the parent reads it. Paid = the day's sale exists. A weekly day is paid
 * from the card when that day's orders close; a day order waiting for its bank card
 * payment, or one the card could not cover by the cut-off, is not paid.
 */
function describe(day) {
  const served = meals.value.some((meal) => servedOn(meal, isoWeekday(day.date)))
  if (day.source === 'skipped') return { sub: 'No meal this day', tag: ['muted', 'Skipped'] }
  if (day.order && day.paid) {
    return { sub: `${mealName(day.order)}${day.source === 'weekly' ? ' · Weekly' : ''}`, tag: ['pos', 'Paid'], amount: day.order.total }
  }
  if (day.order && day.locked) return { sub: `${mealName(day.order)} · not enough on the card`, tag: ['neg', 'Not paid'], amount: day.order.total }
  if (day.order && day.source === 'weekly') {
    return { sub: `${mealName(day.order)} · paid from the card when orders close`, tag: ['accent', 'Weekly'], amount: day.order.total }
  }
  if (day.order) return { sub: `${mealName(day.order)} · waiting for payment`, tag: ['warn', 'Not paid'], amount: day.order.total }
  if (!served) return { sub: 'No meal served', tag: null }
  return { sub: day.locked ? 'Ordering closed' : 'Not ordered', tag: null }
}

/** The same day as a card on the desktop week board. */
function dayCard(day) {
  const { tag, amount } = describe(day)
  const served = meals.value.some((meal) => servedOn(meal, isoWeekday(day.date)))
  const open = !day.locked
  if (day.order && day.paid) return { tag, amount, meal: mealName(day.order), note: 'Paid', kind: 'done', action: open && 'Change' }
  if (day.source === 'skipped') return { tag, meal: 'No meal this day', note: open ? 'You chose not to order' : 'Ordering closed', kind: 'skipped', action: open && 'Order this day' }
  if (day.order) {
    return { tag, amount, meal: mealName(day.order), note: day.source === 'weekly' ? 'Every week' : 'This day only', kind: 'ordered', action: open && 'Change' }
  }
  if (!served) return { meal: 'No meal served', note: 'Nothing on the menu', kind: 'empty' }
  return { meal: 'Not ordered', note: open ? 'Ordering open' : 'Ordering closed', kind: 'empty', action: open && 'Order a meal' }
}
/** The phone list and the big-screen calendar both show the coming week only: only those days can be ordered from here. */
const LIST_DAYS = 7
const listEnd = (() => {
  const end = new Date()
  end.setDate(end.getDate() + LIST_DAYS)
  return `${end.getFullYear()}-${String(end.getMonth() + 1).padStart(2, '0')}-${String(end.getDate()).padStart(2, '0')}`
})()
const listDays = computed(() => (schedule.value?.days || []).filter((day) => day.date < listEnd))
const board = computed(() => listDays.value.map((day) => ({ day, card: dayCard(day) })))

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
    <ChildSwitcher label="Ordering for" :current="id" />
    <div class="pp-largetitle">
      <h1>Canteen meals</h1>
      <p>Order and pay for {{ first }}'s meals ahead. A paid order can be cancelled until that day's orders close, and the money goes back on the card.</p>
    </div>

    <div v-if="!schedule.enabled" class="pp-alert pp-alert--warn" role="alert">
      <i class="fa fa-exclamation-triangle"></i>
      <span class="pp-alert__main"><span class="pp-alert__title">Not taking orders right now</span>The school has paused meal pre-orders.</span>
    </div>

    <template v-else-if="desktop">
      <section class="pp-panel pp-weekly" data-tour="weekly">
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
        <h2 class="pp-group__head">{{ bigScreen ? 'Meal calendar' : 'Next school days' }}</h2>
        <MealCalendar v-if="bigScreen" :board="board" :student-id="id" data-tour="days" />
        <div v-else class="pp-week" data-tour="days">
          <component
            :is="day.locked ? 'div' : 'RouterLink'"
            v-for="{ day, card } in board"
            :key="day.date"
            class="pp-dayc"
            :class="[`pp-dayc--${card.kind}`, { 'is-today': isToday(day.date) }]"
            :to="day.locked ? undefined : { name: 'pre-order-day', params: { id, date: day.date } }"
          >
            <span class="pp-dayc__head">
              <span class="pp-dayc__wd">{{ isToday(day.date) ? 'Today' : dayName(day.date).slice(0, 3) }}</span>
              <span v-if="card.tag" class="pp-tag" :class="`pp-tag--${card.tag[0]}`">{{ card.tag[1] }}</span>
              <span class="pp-dayc__num">{{ dateTile(day.date).day }}<small>{{ dateTile(day.date).month }}</small></span>
            </span>
            <span class="pp-dayc__meal" :title="card.meal">{{ card.meal }}</span>
            <span class="pp-dayc__sub">{{ card.note }}</span>
            <span class="pp-dayc__foot" :class="{ 'is-muted': !card.action }">
              {{ card.action || (day.locked ? 'Closed' : '') }}
              <span v-if="card.amount" class="pp-num">{{ money(card.amount) }}</span>
            </span>
          </component>
        </div>
        <p class="pp-group__foot" data-tour="cutoff">
          <i class="fa fa-clock-o"></i>{{ cutoffNote }}
        </p>
      </section>
    </template>

    <template v-else>
      <section class="pp-group">
        <h2 class="pp-group__head">Every week</h2>
        <div class="pp-group__body" data-tour="weekly">
          <RouterLink class="pp-row pp-row--icon pp-weekly-row" :to="{ name: 'pre-order-weekly', params: { id } }">
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
        <div class="pp-group__body" data-tour="days">
          <component
            :is="day.locked ? 'div' : 'RouterLink'"
            v-for="day in listDays"
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
        <p class="pp-group__foot" data-tour="cutoff">
          <i class="fa fa-clock-o"></i>{{ cutoffNote }}
        </p>
      </section>
    </template>

    <section v-if="schedule.enabled && paidDays.length" class="pp-group pp-paid" data-tour="paid">
      <h2 class="pp-group__head">Paid orders</h2>
      <div class="pp-group__body">
        <div v-for="day in paidDays" :key="day.date" class="pp-row pp-day-row">
          <span class="pp-date" :class="{ 'is-today': isToday(day.date) }">
            <small>{{ dateTile(day.date).month }}</small><b>{{ dateTile(day.date).day }}</b>
          </span>
          <span class="pp-row__main">
            <span class="pp-row__title">{{ isToday(day.date) ? 'Today' : dayName(day.date) }} · {{ money(day.order.total) }}</span>
            <span class="pp-row__sub">{{ mealName(day.order) }}</span>
          </span>
          <span class="pp-row__end">
            <button v-if="!day.locked" class="pp-paid__cancel" type="button" @click="askCancel(day)">Cancel</button>
            <span v-else class="pp-tag pp-tag--muted" title="Orders for this day have closed">Closed</span>
          </span>
        </div>
      </div>
      <p class="pp-group__foot"><i class="fa fa-undo"></i>Cancelling gives the money back to {{ first }}'s card straight away.</p>
    </section>
  </main>

  <BottomSheet :open="Boolean(cancelling)" label="Cancel order" @close="cancelling = null">
    <div class="pp-sheet__grabber"></div>
    <div class="pp-sheet__bar">
      <button class="pp-navbtn" type="button" @click="cancelling = null">Keep it</button>
      <div class="pp-sheet__title">Cancel order</div>
    </div>
    <div v-if="cancelling" class="pp-lede pp-lede--sheet">
      <div class="pp-hero-icon pp-hero-icon--neg"><i class="fa fa-undo"></i></div>
      <h2>Cancel {{ isToday(cancelling.date) ? "today's" : `${dayName(cancelling.date)}'s` }} order?</h2>
      <p>{{ mealName(cancelling.order) }}. {{ money(cancelling.order.total) }} goes back on {{ first }}'s card, and nothing is ordered that day.</p>
    </div>
    <div v-if="cancelError" class="pp-alert" role="alert">
      <i class="fa fa-exclamation-circle"></i><span class="pp-alert__main">{{ cancelError }}</span>
    </div>
    <div class="pp-sheet__actions">
      <button class="pp-btn pp-btn--danger" :class="{ 'is-busy': cancelBusy }" type="button" :disabled="cancelBusy" @click="confirmCancel">
        <span v-if="cancelBusy" class="pp-spinner pp-spinner--sm" aria-hidden="true"></span><i v-else class="fa fa-undo"></i>
        {{ cancelBusy ? 'Cancelling…' : 'Cancel order and refund' }}
      </button>
    </div>
  </BottomSheet>
</template>
