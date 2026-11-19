<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'

import { blockCard, fetchStudent } from '@/api/parent'
import AppBar from '@/components/AppBar.vue'
import BottomSheet from '@/components/BottomSheet.vue'
import ChildCard from '@/components/ChildCard.vue'
import LoadError from '@/components/LoadError.vue'
import StudentActivity from '@/components/StudentActivity.vue'
import StudentAvatar from '@/components/StudentAvatar.vue'
import { refreshChild } from '@/children'
import { toast } from '@/toast'
import { classLabel, date, firstName, money, weekdaysLabel } from '@/utils/format'
import { desktop } from '@/utils/viewport'

const route = useRoute()
const id = computed(() => Number(route.params.id))

const student = ref(null)
const status = ref('loading')
const error = ref('')

const first = computed(() => firstName(student.value?.name))

const mealsToday = computed(() => {
  const today = student.value?.pre_orders?.today
  if (!today) return 'Nothing ordered for today'
  if (today.source === 'skipped') return 'No meal today'
  return today.collected ? "Today's meal is paid" : 'Meal ordered for today · not paid'
})
const mealsSummary = computed(() => {
  const weekly = student.value?.pre_orders?.weekly
  return mealsToday.value + (weekly ? (weekly.status === 'paused' ? ' · weekly paused' : ' · weekly on') : '')
})
const cardState = computed(() => (!student.value?.has_card ? 'none' : student.value.card_blocked ? 'blocked' : 'active'))

/* Desktop: the card is in the wallet, so the page leads with the numbers. */
const details = computed(() => [classLabel(student.value?.class), student.value?.admission_no].filter(Boolean).join(' · '))
const balanceNote = computed(() => {
  if (cardState.value === 'none') return ''
  if (cardState.value === 'blocked') return 'Card blocked'
  return student.value.balance < 0 ? 'In overdraft. Please top up.' : 'Card active'
})
const weeklyMeals = computed(() => {
  const weekly = student.value?.pre_orders?.weekly
  if (!weekly) return 'No weekly order'
  return weekly.status === 'paused' ? 'Weekly order paused' : `Weekly · ${weekdaysLabel(weekly.weekdays)}`
})

async function loadStudent() {
  try {
    student.value = await fetchStudent(id.value)
    refreshChild(student.value)
    status.value = 'ready'
  } catch (e) {
    error.value = e.status === 404 ? "This child isn't linked to your login." : e.message
    status.value = 'error'
  }
}

/* ---- Lost card ---- */
const sheetOpen = ref(false)
const reason = ref('')
const blocking = ref(false)
const blockError = ref('')

async function block() {
  blocking.value = true
  blockError.value = ''
  try {
    await blockCard(id.value, reason.value.trim() || null)
    sheetOpen.value = false
    reason.value = ''
    toast(`${first.value}'s card is blocked`)
    await loadStudent()
  } catch (e) {
    blockError.value = e.message
  } finally {
    blocking.value = false
  }
}

onMounted(loadStudent)
</script>

<template>
  <AppBar :back="{ name: 'home' }" back-label="Children" :title="first" :desktop-back="false" />

  <LoadError v-if="status === 'error'" title="We couldn't open this card" :message="error" @retry="loadStudent">
    <RouterLink class="pp-link" :to="{ name: 'home' }">Back to my children</RouterLink>
  </LoadError>

  <main v-else>
    <template v-if="desktop">
      <header class="pp-detail__head">
        <StudentAvatar v-if="student" :name="student.name" :image="student.image_url" />
        <span v-else class="pp-skel pp-avatar" aria-hidden="true"></span>
        <div class="pp-detail__id">
          <h1 v-if="student">{{ student.name }}</h1>
          <span v-else class="pp-skel pp-skel--title"></span>
          <p v-if="details">{{ details }}</p>
        </div>
        <template v-if="student">
          <RouterLink v-if="student.topup.enabled" class="pp-btn pp-btn--primary pp-btn--auto" :to="{ name: 'topup', params: { id } }">
            <i class="fa fa-plus-circle"></i>Top up card
          </RouterLink>
          <button v-else class="pp-btn pp-btn--primary pp-btn--auto" type="button" disabled><i class="fa fa-plus-circle"></i>Top up card</button>
          <RouterLink v-if="student.pre_orders?.enabled" class="pp-btn pp-btn--preorder pp-btn--auto" :to="{ name: 'pre-orders', params: { id } }">Pre-Order</RouterLink>
        </template>
      </header>
      <p v-if="student && !student.topup.enabled" class="pp-cta__note pp-cta__note--start">
        Online top-up isn't available right now. Please contact the school office.
      </p>

      <div v-if="student" class="pp-stats">
        <div class="pp-stat" :class="{ 'pp-stat--neg': student.balance < 0 }">
          <small>Card balance</small><b>{{ money(student.balance) }}</b><span>{{ balanceNote }}</span>
        </div>
        <div v-if="cardState === 'active'" class="pp-stat">
          <small>Can spend</small><b>{{ money(student.available) }}</b>
          <span>{{ student.overdraft_limit > 0 ? `Includes ${money(student.overdraft_limit)} overdraft` : 'No overdraft' }}</span>
        </div>
        <div v-if="student.pre_orders?.enabled" class="pp-stat pp-stat--text">
          <small>Pre-orders</small><b>{{ weeklyMeals }}</b><span>{{ mealsToday }}</span>
        </div>
      </div>
    </template>

    <template v-else>
      <section class="pp-balance-hero">
        <span v-if="!student" class="pp-skel pp-skel--card" aria-busy="true"></span>
        <ChildCard v-else :student="student" hero />

        <p v-if="student && cardState === 'active' && student.overdraft_limit > 0" class="pp-spend">
          <i class="fa fa-info-circle"></i>
          <span><b>Can spend {{ money(student.available) }}</b><small>Includes an overdraft of {{ money(student.overdraft_limit) }}</small></span>
        </p>
        <p v-else-if="student && student.balance < 0" class="pp-spend">
          <i class="fa fa-exclamation-triangle pp-text-neg"></i>
          <span><b>The card is in overdraft</b><small>Please top up so {{ first }} can keep paying.</small></span>
        </p>
        <div v-else class="pp-spend-gap"></div>
      </section>

      <div v-if="student" class="pp-cta">
        <RouterLink v-if="student.topup.enabled" class="pp-btn pp-btn--primary" :to="{ name: 'topup', params: { id } }">
          <i class="fa fa-plus-circle"></i>Top up card
        </RouterLink>
        <template v-else>
          <button class="pp-btn pp-btn--primary" type="button" disabled><i class="fa fa-plus-circle"></i>Top up card</button>
          <p class="pp-cta__note">Online top-up isn't available right now. Please contact the school office.</p>
        </template>
        <RouterLink v-if="student.pre_orders?.enabled" class="pp-btn pp-btn--preorder" :to="{ name: 'pre-orders', params: { id } }">Pre-Order</RouterLink>
        <p v-if="student.pre_orders?.enabled" class="pp-cta__note">{{ mealsSummary }}</p>
      </div>
    </template>

    <StudentActivity v-if="desktop" :student-id="id" tabs />

    <section v-else class="pp-group">
      <div class="pp-group__body">
        <RouterLink class="pp-row pp-row--icon" :to="{ name: 'student-bills', params: { id } }">
          <span class="pp-row__icon pp-row__icon--accent"><i class="fa fa-file-text-o"></i></span>
          <span class="pp-row__main"><span class="pp-row__title">Bills</span><span class="pp-row__sub">Every purchase at the canteen</span></span>
          <i class="fa fa-angle-right pp-chev"></i>
        </RouterLink>
        <RouterLink class="pp-row pp-row--icon" :to="{ name: 'student-statement', params: { id } }">
          <span class="pp-row__icon pp-row__icon--pos"><i class="fa fa-list-alt"></i></span>
          <span class="pp-row__main"><span class="pp-row__title">Statement</span><span class="pp-row__sub">Top-ups, purchases and refunds, month by month</span></span>
          <i class="fa fa-angle-right pp-chev"></i>
        </RouterLink>
      </div>
    </section>

    <!-- Only for reporting a lost card: the card's day-to-day state is on its face above. -->
    <section v-if="student && cardState !== 'none'" class="pp-group pp-lost">
      <div class="pp-group__body">
        <div v-if="cardState === 'blocked'" class="pp-row pp-row--icon">
          <span class="pp-row__icon pp-row__icon--neg"><i class="fa fa-ban"></i></span>
          <span class="pp-row__main">
            <span class="pp-row__title pp-text-neg">Card blocked</span>
            <span class="pp-row__sub">{{ student.card_blocked_at ? `Reported lost on ${date(student.card_blocked_at)}` : 'The canteen will refuse it' }}</span>
          </span>
        </div>
        <button v-else class="pp-row pp-row--icon" type="button" @click="sheetOpen = true">
          <span class="pp-row__icon pp-row__icon--neg"><i class="fa fa-ban"></i></span>
          <span class="pp-row__main"><span class="pp-row__title pp-text-neg">Lost card?</span><span class="pp-row__sub">Report it and block it straight away</span></span>
          <i class="fa fa-angle-right pp-chev"></i>
        </button>
      </div>
      <p v-if="cardState === 'blocked'" class="pp-group__foot">Only the school office can switch it back on or issue a new card. The balance stays with {{ first }}.</p>
    </section>
  </main>

  <BottomSheet :open="sheetOpen" label="Block card" @close="sheetOpen = false">
    <div class="pp-sheet__grabber"></div>
    <div class="pp-sheet__bar">
      <button class="pp-navbtn" type="button" @click="sheetOpen = false">Cancel</button>
      <div class="pp-sheet__title">Lost card</div>
    </div>
    <div class="pp-lede pp-lede--sheet">
      <div class="pp-hero-icon pp-hero-icon--neg"><i class="fa fa-ban"></i></div>
      <h2>Block {{ first }}'s card?</h2>
      <p>
        Blocking stops the card at the canteen straight away. Only the school office can switch it back on or issue a new card; the balance
        stays with your child.
      </p>
    </div>
    <div v-if="blockError" class="pp-alert" role="alert">
      <i class="fa fa-exclamation-circle"></i><span class="pp-alert__main">{{ blockError }}</span>
    </div>
    <form novalidate @submit.prevent="block">
      <div class="pp-group">
        <div class="pp-group__body">
          <div class="pp-field">
            <label class="pp-field__body">
              <span class="pp-field__label">Reason (optional)</span>
              <input v-model="reason" class="pp-input" type="text" maxlength="200" placeholder="For example, lost on the school bus" />
            </label>
          </div>
        </div>
      </div>
      <div class="pp-sheet__actions">
        <button class="pp-btn pp-btn--danger" :class="{ 'is-busy': blocking }" type="submit" :disabled="blocking">
          <span v-if="blocking" class="pp-spinner pp-spinner--sm" aria-hidden="true"></span><i v-else class="fa fa-ban"></i>
          {{ blocking ? 'Blocking…' : 'Block card' }}
        </button>
      </div>
    </form>
  </BottomSheet>
</template>
