<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { fetchMe } from '@/api/parent'
import AppBar from '@/components/AppBar.vue'
import BottomSheet from '@/components/BottomSheet.vue'
import ChildCard from '@/components/ChildCard.vue'
import LoadError from '@/components/LoadError.vue'
import { children, loadChildren } from '@/children'
import { school } from '@/school'
import { session } from '@/session'
import { signOut } from '@/utils/account'
import { firstName, initials, money } from '@/utils/format'
import { desktop } from '@/utils/viewport'

const route = useRoute()
const router = useRouter()

const menuOpen = ref(false)
const signingOut = ref(false)

const students = computed(() => children.list)
const loading = computed(() => ['idle', 'loading'].includes(children.status))
const parent = computed(() => session.parent || {})
const overdrawn = computed(() => students.value.filter((student) => Number(student.balance) < 0))
const unmatchedPayment = computed(() => route.query.payment === 'unmatched')

/* The desktop overview: what the cards add up to (the cards themselves are in the wallet). */
const total = computed(() => students.value.reduce((sum, student) => sum + Number(student.balance || 0), 0))
const working = computed(() => students.value.filter((student) => student.has_card && !student.card_blocked).length)
const cardsNote = computed(() => {
  const blocked = students.value.filter((student) => student.has_card && student.card_blocked).length
  const none = students.value.filter((student) => !student.has_card).length
  const notes = [blocked && `${blocked} blocked`, none && `${none} without a card`].filter(Boolean)
  return notes.length ? notes.join(' · ') : 'Every card is working'
})
const overdraftNote = computed(() => {
  const names = overdrawn.value.map((student) => firstName(student.name))
  return names.length ? `${names.join(', ')} ${names.length === 1 ? 'is' : 'are'} in overdraft` : 'No card is in overdraft'
})
const cardNote = (student) => (!student.has_card ? 'No card yet' : student.card_blocked ? 'Card blocked' : '')

async function leave() {
  signingOut.value = true
  await signOut(router)
  menuOpen.value = false
}

onMounted(() => {
  loadChildren()
  // Keep the name and mobile in the menu current (the school may have edited them).
  fetchMe()
    .then((me) => session.setParent(me))
    .catch(() => {})
})
</script>

<template>
  <AppBar>
    <template #start>
      <span class="pp-appbar__school">
        <img v-if="school.logo" class="pp-appbar__logo" :src="school.logo" alt="" />
        <i v-else class="fa fa-graduation-cap pp-mark"></i>
        {{ school.name || 'Parent Portal' }}
      </span>
    </template>
    <template #end>
      <button class="pp-avatar-btn" type="button" aria-label="Account menu" @click="menuOpen = true">
        <span class="pp-avatar">{{ initials(parent.name) }}</span>
      </button>
    </template>
  </AppBar>

  <LoadError v-if="children.status === 'error'" title="We couldn't load your children" :message="children.error" @retry="loadChildren" />

  <main v-else>
    <div class="pp-largetitle">
      <h1>{{ desktop ? 'Your children' : `Hello, ${firstName(parent.name) || 'there'}` }}</h1>
      <p v-if="loading">Getting your children's cards…</p>
      <p v-else-if="desktop && students.length">Balances and cards at a glance. Open a card on the left for its bills, statement and meals.</p>
      <p v-else-if="students.length === 1">Your child's card. Tap it to open.</p>
      <p v-else-if="students.length">Your {{ students.length }} children. Tap a card to open it.</p>
    </div>

    <div v-if="desktop && students.length" class="pp-stats">
      <div class="pp-stat">
        <small><i class="fa fa-credit-card"></i>On the cards</small>
        <b :class="{ 'pp-text-neg': total < 0 }">{{ money(total) }}</b>
        <span>Across {{ students.length }} {{ students.length === 1 ? 'child' : 'children' }}</span>
      </div>
      <div class="pp-stat">
        <small><i class="fa fa-check-circle"></i>Cards working</small>
        <b>{{ working }} of {{ students.length }}</b>
        <span>{{ cardsNote }}</span>
      </div>
      <div class="pp-stat" :class="{ 'pp-stat--neg': overdrawn.length }">
        <small><i class="fa fa-exclamation-triangle"></i>Needs a top-up</small>
        <b>{{ overdrawn.length }}</b>
        <span>{{ overdraftNote }}</span>
      </div>
    </div>

    <div v-if="unmatchedPayment" class="pp-alert" role="alert">
      <i class="fa fa-exclamation-circle"></i>
      <span class="pp-alert__main">
        <span class="pp-alert__title">We couldn't match that payment</span>If money was taken, please contact the school office.
      </span>
    </div>

    <div v-for="student in overdrawn" :key="`od-${student.account_id}`" class="pp-alert pp-alert--warn">
      <i class="fa fa-exclamation-triangle"></i>
      <span class="pp-alert__main"><span class="pp-alert__title">{{ student.name }}</span>The card is in overdraft. Please top up.</span>
      <RouterLink class="pp-alert__btn" :to="{ name: 'topup', params: { id: student.account_id } }">Top up</RouterLink>
    </div>

    <template v-if="!desktop">
      <section v-if="loading" class="pp-wallet-stack" aria-busy="true" aria-label="Loading">
        <span class="pp-skel pp-skel--card"></span>
        <span class="pp-skel pp-skel--card"></span>
      </section>

      <section v-else-if="students.length" class="pp-wallet-stack" aria-label="Your children">
        <ChildCard v-for="student in students" :key="student.account_id" :student="student" :to="{ name: 'student', params: { id: student.account_id } }" />
      </section>
    </template>

    <section v-else-if="students.length" class="pp-panel" aria-label="Your children">
      <h2 class="pp-panel__head pp-panel__title">Children</h2>
      <div class="pp-group__body">
        <div v-for="student in students" :key="student.account_id" class="pp-row pp-kid-row">
          <span class="pp-minicard" :class="{ 'pp-minicard--none': !student.has_card, 'pp-minicard--blocked': student.has_card && student.card_blocked }"></span>
          <span class="pp-row__main">
            <span class="pp-row__title">{{ student.name }}</span>
            <span class="pp-row__sub">{{ [student.class || student.admission_no, cardNote(student)].filter(Boolean).join(' · ') }}</span>
          </span>
          <span class="pp-amt" :class="{ 'pp-text-neg': student.balance < 0 }">{{ money(student.balance) }}</span>
          <span class="pp-kid-row__actions">
            <RouterLink v-if="student.has_card && !student.card_blocked" class="pp-btn pp-btn--ghost pp-btn--sm" :to="{ name: 'topup', params: { id: student.account_id } }">
              Top up
            </RouterLink>
            <RouterLink class="pp-btn pp-btn--tinted pp-btn--sm" :to="{ name: 'student', params: { id: student.account_id } }">Open</RouterLink>
          </span>
        </div>
      </div>
    </section>

    <div v-if="!loading && !students.length" class="pp-lede pp-lede--empty">
      <div class="pp-hero-icon"><i class="fa fa-users"></i></div>
      <h2>No children linked yet</h2>
      <p>The school hasn't linked a child to your login. Please contact the school office.</p>
    </div>

    <p v-if="!desktop && students.length" class="pp-hint">Balances change each time your child pays at the canteen.</p>
  </main>

  <BottomSheet :open="menuOpen" variant="action" label="Account" @close="menuOpen = false">
    <div class="pp-action-group">
      <div class="pp-action-profile">
        <span class="pp-avatar">{{ initials(parent.name) }}</span>
        <b>{{ parent.name }}</b>
        <span v-if="parent.mobile">{{ parent.mobile }}</span>
        <span v-if="parent.email">{{ parent.email }}</span>
      </div>
      <button class="pp-action pp-action--danger" type="button" :disabled="signingOut" @click="leave">
        <i class="fa fa-sign-out"></i>{{ signingOut ? 'Signing out…' : 'Sign out' }}
      </button>
    </div>
    <div class="pp-action-group">
      <button class="pp-action pp-action--cancel" type="button" @click="menuOpen = false">Cancel</button>
    </div>
  </BottomSheet>
</template>
