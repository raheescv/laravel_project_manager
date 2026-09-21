<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'

import { fetchTopup } from '@/api/parent'
import AppBar from '@/components/AppBar.vue'
import LoadError from '@/components/LoadError.vue'
import StudentAvatar from '@/components/StudentAvatar.vue'
import { children, loadChildren } from '@/children'
import { school } from '@/school'
import { amount, dateTime, firstName, money } from '@/utils/format'

/**
 * Where QPay brings the parent back. Shows what QPay certification asks for —
 * reference, amount, status, date and time — and keeps checking while the
 * payment is still being confirmed.
 */
const route = useRoute()
const pun = String(route.params.pun || '')

const topup = ref(null)
const status = ref('loading')
const error = ref('')
let timer = null
let polls = 0

const first = computed(() => firstName(topup.value?.student?.name))
const studentRoute = computed(() => ({ name: 'student', params: { id: topup.value?.student?.account_id } }))
// The top-up answer carries no photo; the wallet list has it.
const child = computed(() => children.list.find((student) => student.account_id === topup.value?.student?.account_id) || null)
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
      pending: { tone: 'confirming', spinner: true, title: 'Confirming your payment…', tag: ['accent', 'Confirming'], msg: "Please don't pay again. This usually takes a few seconds." },
      review: {
        tone: 'review',
        icon: 'fa-clock-o',
        title: 'Payment received, being checked',
        tag: ['warn', 'Being checked'],
        msg: `QPay has your payment. The school is checking it, and the money will reach ${who} card once it's confirmed. Please don't pay again.`,
      },
      failed: { tone: 'failed', icon: 'fa-times', title: 'Payment failed', tag: ['neg', 'Failed'], msg: t.message ? `${t.message} No money was taken.` : 'No money was taken. You can try again.' },
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

onMounted(load)
onBeforeUnmount(() => clearTimeout(timer))
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
        <div class="is-wide"><dt>Payment reference</dt><dd class="pp-receipt__code">{{ topup.pun }}</dd></div>
        <div class="is-wide">
          <dt>QPay confirmation</dt>
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
      <template v-if="topup.status === 'failed'">
        <RouterLink class="pp-btn pp-btn--primary" :to="{ name: 'topup', params: { id: topup.student.account_id } }">Try again</RouterLink>
        <RouterLink class="pp-btn pp-btn--tinted" :to="studentRoute">Back to {{ first || 'my child' }}</RouterLink>
      </template>
      <RouterLink v-else class="pp-btn pp-btn--primary" :to="studentRoute">Back to {{ first || 'my child' }}</RouterLink>
    </div>
  </main>
</template>
