<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { cancelTopup, fetchTopup } from '@/api/parent'
import AppBar from '@/components/AppBar.vue'
import LoadError from '@/components/LoadError.vue'
import StudentAvatar from '@/components/StudentAvatar.vue'
import { children, loadChildren } from '@/children'
import { school } from '@/school'
import { toast } from '@/toast'
import { amount, dateTime, firstName, money, time } from '@/utils/format'

/**
 * Where the payment page brings the parent back — QPay for a debit card, the
 * bank's Mastercard Gateway page for a credit card. Shows what QPay certification
 * asks for — reference, amount, status, date and time — and keeps checking while
 * the payment is still being confirmed.
 */
const route = useRoute()
const router = useRouter()
const pun = String(route.params.pun || '')

const topup = ref(null)
const status = ref('loading')
const error = ref('')
let timer = null
let polls = 0

// Cancelling a QPay payment the parent walked away from. The API says from when
// (`cancellable_at`, once QPay can be asked); a refusal says when to try again.
const now = ref(Date.now())
const cancelling = ref(false)
const cancelError = ref('')
const retryAt = ref(0)
let ticker = 0

const cancelFrom = computed(() => (topup.value?.status === 'pending' && topup.value.cancellable_at ? new Date(topup.value.cancellable_at).getTime() || 0 : 0))
const cancelAt = computed(() => Math.max(cancelFrom.value, retryAt.value))
const cancelOpen = computed(() => cancelFrom.value > 0 && now.value >= cancelAt.value)
// Past the point QPay should have answered: "a few seconds" is no longer true.
const overdue = computed(() => cancelFrom.value > 0 && now.value >= cancelFrom.value)
const countdown = computed(() => {
  const seconds = Math.max(0, Math.ceil((cancelAt.value - now.value) / 1000))
  return `${Math.floor(seconds / 60)}:${String(seconds % 60).padStart(2, '0')}`
})

const first = computed(() => firstName(topup.value?.student?.name))
const studentRoute = computed(() => ({ name: 'student', params: { id: topup.value?.student?.account_id } }))
// The top-up answer carries no photo; the wallet list has it.
const child = computed(() => children.list.find((student) => student.account_id === topup.value?.student?.account_id) || null)
const credit = computed(() => topup.value?.method === 'credit')
// "Credit card · Visa ····0008" once the gateway has said which card was used.
const paidWith = computed(() => [topup.value?.method_label || 'Debit card', topup.value?.card].filter(Boolean).join(' · '))
const newBalance = computed(() => {
  const status = topup.value?.status
  if (status === 'success') return money(topup.value.balance)
  return ['pending', 'review', 'unresolved'].includes(status) ? 'Not added yet' : 'No change'
})

const view = computed(() => {
  const t = topup.value
  if (!t) return null
  const who = first.value ? `${first.value}'s` : 'the'
  return (
    {
      success: { tone: 'success', icon: 'fa-check', title: 'Payment successful', tag: ['pos', 'Paid'] },
      pending: {
        tone: 'confirming',
        spinner: true,
        title: 'Confirming your payment…',
        tag: ['accent', 'Confirming'],
        msg: overdue.value
          ? "QPay hasn't confirmed this payment yet. If you left QPay's page without paying, you can cancel it below."
          : "Please don't pay again. This usually takes a few seconds.",
      },
      review: {
        tone: 'review',
        icon: 'fa-clock-o',
        title: 'Payment received, being checked',
        tag: ['warn', 'Being checked'],
        msg: `${credit.value ? 'Your card was charged' : 'QPay has your payment'}. The school is checking it, and the money will reach ${who} card once it's confirmed. Please don't pay again.`,
      },
      failed: { tone: 'failed', icon: 'fa-times', title: 'Payment failed', tag: ['neg', 'Failed'], msg: t.message ? `${t.message} No money was taken.` : 'No money was taken. You can try again.' },
      cancelled: { tone: 'failed', icon: 'fa-times', title: 'Payment cancelled', tag: ['muted', 'Cancelled'], msg: 'You left the card payment page, so no money was taken. You can try again.' },
      refund_pending: { tone: 'review', icon: 'fa-reply', title: 'Refund in progress', tag: ['warn', 'Refund pending'], msg: 'The school is refunding this payment to your bank card.' },
      refunded: { tone: 'review', icon: 'fa-reply', title: 'Payment refunded', tag: ['warn', 'Refunded'], msg: 'This payment was refunded to your bank card.' },
      // QPay never said what happened and the school released it so the card could
      // be used again. Nothing was added, but we cannot promise nothing was taken —
      // which is exactly why this points at the bank statement and the office.
      unresolved: {
        tone: 'review',
        icon: 'fa-question-circle',
        title: 'Payment not confirmed',
        tag: ['warn', 'Not confirmed'],
        msg: `QPay never confirmed this payment, so nothing was added to ${who} card. If your bank shows the amount was taken, contact the school office with the reference below.`,
      },
    }[t.status] || { tone: 'review', icon: 'fa-clock-o', title: t.status_label, tag: ['warn', t.status_label] }
  )
})

async function load() {
  try {
    const before = topup.value?.status
    topup.value = await fetchTopup(pun)
    status.value = 'ready'
    // The money reached the card: the balances in the wallet are out of date.
    if (topup.value.status === 'success' && before !== 'success') loadChildren()
    schedule()
  } catch (e) {
    if (topup.value) {
      schedule()
      return
    }
    error.value = e.status === 404 ? "We couldn't find this payment for your children." : e.message
    status.value = 'error'
  }
}

function schedule() {
  clearTimeout(timer)
  if (topup.value?.status !== 'pending') return
  polls += 1
  // Quick at first (QPay usually answers within seconds), then ease off.
  timer = setTimeout(load, polls < 15 ? 4000 : 15000)
}

/**
 * Nothing is cancelled on our word: the API asks QPay first. Never received → the
 * payment closes and the parent goes straight back to top up; paid → it is on the
 * card and this page shows so; no answer → the API says when to try again.
 */
async function cancel() {
  cancelling.value = true
  cancelError.value = ''
  try {
    const result = await cancelTopup(pun)
    topup.value = result
    if (result.status === 'failed') {
      toast('Payment cancelled. No money was taken.')
      router.replace({ name: 'topup', params: { id: result.student.account_id } })
      return
    }
    if (result.status === 'success') {
      loadChildren()
      toast('QPay says this payment went through. It is on the card.')
    }
  } catch (e) {
    cancelError.value = e.message
    const at = e.errors?.retry_at ? new Date(e.errors.retry_at).getTime() : 0
    retryAt.value = Number.isFinite(at) ? at : 0
    now.value = Date.now()
  } finally {
    cancelling.value = false
  }
}

onMounted(() => {
  load()
  // The cancel's countdown; idle once the payment has an outcome.
  ticker = setInterval(() => {
    if (topup.value?.status === 'pending') now.value = Date.now()
  }, 1000)
})
onBeforeUnmount(() => {
  clearTimeout(timer)
  clearInterval(ticker)
})
</script>

<template>
  <AppBar title="Top-up" />

  <LoadError v-if="status === 'error'" title="Payment not found" :message="error" @retry="load">
    <RouterLink class="pp-link" :to="{ name: 'home' }">Go to my children</RouterLink>
  </LoadError>

  <main v-else-if="status === 'loading'" class="pp-receipt-page" aria-busy="true">
    <article class="pp-receipt pp-receipt--confirming">
      <header class="pp-receipt__head">
        <div class="pp-result__icon"><span class="pp-spinner" aria-label="Loading"></span></div>
        <h1 class="pp-receipt__title">Checking your payment…</h1>
        <span class="pp-skel pp-receipt__skel" aria-hidden="true"></span>
      </header>
    </article>
  </main>

  <!-- One ticket: the outcome and the amount on top, the proof below the tear. -->
  <main v-else class="pp-receipt-page">
    <article class="pp-receipt" :class="`pp-receipt--${view.tone}`" aria-live="polite">
      <header class="pp-receipt__head">
        <div class="pp-result__icon">
          <span v-if="view.spinner" class="pp-spinner" aria-hidden="true"></span><i v-else class="fa" :class="view.icon"></i>
        </div>
        <h1 class="pp-receipt__title">{{ view.title }}</h1>
        <p class="pp-receipt__amount pp-num"><small>{{ school.currency.code }}</small>{{ amount(topup.amount) }}</p>
        <p v-if="topup.status === 'success'" class="pp-receipt__msg">Added to {{ first ? `${first}'s` : 'the' }} card.</p>
        <p v-else-if="view.msg" class="pp-receipt__msg">{{ view.msg }}</p>
      </header>

      <div class="pp-tear" aria-hidden="true"></div>

      <dl class="pp-receipt__fields">
        <div><dt>Date &amp; time</dt><dd>{{ dateTime(topup.completed_at || topup.created_at) }}</dd></div>
        <div class="is-end">
          <dt>Status</dt>
          <dd><span class="pp-tag" :class="`pp-tag--${view.tag[0]}`">{{ view.tag[1] }}</span></dd>
        </div>
        <div class="is-wide"><dt>Payment method</dt><dd>{{ paidWith }}</dd></div>
        <div class="is-wide"><dt>Payment reference</dt><dd class="pp-receipt__code">{{ topup.pun }}</dd></div>
        <div class="is-wide">
          <dt>{{ credit ? 'Card receipt' : 'QPay confirmation' }}</dt>
          <dd :class="topup.confirmation_id ? 'pp-receipt__code' : 'pp-text-muted'">{{ topup.confirmation_id || 'Not received yet' }}</dd>
        </div>
      </dl>

      <footer class="pp-receipt__foot">
        <StudentAvatar :name="topup.student?.name" :image="child?.image_url" />
        <span class="pp-row__main">
          <b class="pp-receipt__who">{{ topup.student?.name || 'Card' }}</b>
          <span class="pp-row__sub">New card balance</span>
        </span>
        <b class="pp-receipt__balance" :class="{ 'is-muted': topup.status !== 'success' }">{{ newBalance }}</b>
      </footer>
    </article>

    <div class="pp-receipt__actions">
      <template v-if="['failed', 'cancelled'].includes(topup.status)">
        <RouterLink class="pp-btn pp-btn--primary" :to="{ name: 'topup', params: { id: topup.student.account_id } }">Try again</RouterLink>
        <RouterLink class="pp-btn pp-btn--tinted" :to="studentRoute">Back to {{ first || 'my child' }}</RouterLink>
      </template>
      <RouterLink v-else class="pp-btn pp-btn--primary" :to="studentRoute">Back to {{ first || 'my child' }}</RouterLink>

      <!-- Left QPay's page without paying: cancel it, once QPay can be asked. -->
      <template v-if="cancelFrom">
        <div v-if="cancelError" class="pp-alert pp-alert--warn" role="alert">
          <i class="fa fa-clock-o"></i><span class="pp-alert__main">{{ cancelError }}</span>
        </div>
        <button class="pp-btn pp-btn--neg-soft" :class="{ 'is-busy': cancelling }" type="button" :disabled="!cancelOpen || cancelling" @click="cancel">
          <span v-if="cancelling" class="pp-spinner pp-spinner--sm" aria-hidden="true"></span><i v-else class="fa fa-times-circle"></i>
          {{ cancelling ? 'Checking with QPay…' : cancelOpen ? 'Cancel this payment' : `Cancel available in ${countdown}` }}
        </button>
        <p class="pp-receipt__note">
          <template v-if="cancelOpen">Only if you left QPay's page without paying. We check with QPay first: if the money was taken, it goes on the card instead.</template>
          <template v-else>Left QPay's page without paying? You can cancel this payment after {{ time(cancelAt) }}, once QPay can tell us nothing was taken.</template>
        </p>
      </template>
    </div>
  </main>
</template>
