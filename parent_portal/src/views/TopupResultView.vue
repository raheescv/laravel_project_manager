<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'

import { fetchTopup } from '@/api/parent'
import AppBar from '@/components/AppBar.vue'
import LoadError from '@/components/LoadError.vue'
import { loadChildren } from '@/children'
import { dateTime, firstName, money } from '@/utils/format'

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

  <main v-else-if="status === 'loading'" aria-busy="true">
    <section class="pp-result pp-result--confirming">
      <div class="pp-result__icon"><span class="pp-spinner" aria-label="Loading"></span></div>
      <h1 class="pp-result__title">Checking your payment…</h1>
    </section>
  </main>

  <main v-else class="pp-narrow">
    <section class="pp-result" :class="`pp-result--${view.tone}`" aria-live="polite">
      <div class="pp-result__icon">
        <span v-if="view.spinner" class="pp-spinner" aria-hidden="true"></span><i v-else class="fa" :class="view.icon"></i>
      </div>
      <h1 class="pp-result__title">{{ view.title }}</h1>
      <p v-if="topup.status === 'success'" class="pp-result__msg">
        <b>{{ money(topup.amount) }}</b> has been added to {{ first ? `${first}'s` : 'the' }} card.
      </p>
      <p v-else-if="view.msg" class="pp-result__msg">{{ view.msg }}</p>
    </section>

    <section class="pp-group">
      <h2 class="pp-group__head">Receipt</h2>
      <dl class="pp-group__body">
        <div class="pp-row"><dt>Payment reference</dt><dd class="pp-mono">{{ topup.pun }}</dd></div>
        <div class="pp-row"><dt>Amount</dt><dd>{{ money(topup.amount) }}</dd></div>
        <div class="pp-row">
          <dt>Status</dt>
          <dd><span class="pp-tag" :class="`pp-tag--${view.tag[0]}`">{{ view.tag[1] }}</span></dd>
        </div>
        <div class="pp-row"><dt>Date &amp; time</dt><dd>{{ dateTime(topup.completed_at || topup.created_at) }}</dd></div>
        <div class="pp-row"><dt>QPay confirmation</dt><dd :class="{ 'pp-mono': topup.confirmation_id }">{{ topup.confirmation_id || '—' }}</dd></div>
        <div v-if="topup.student?.name" class="pp-row"><dt>Card</dt><dd>{{ topup.student.name }}</dd></div>
        <div class="pp-row">
          <dt>New card balance</dt>
          <dd>{{ topup.status === 'success' ? money(topup.balance) : ['pending', 'review', 'unresolved'].includes(topup.status) ? 'Not added yet' : 'No change' }}</dd>
        </div>
      </dl>
    </section>

    <div class="pp-result-actions">
      <template v-if="topup.status === 'failed'">
        <RouterLink class="pp-btn pp-btn--primary" :to="{ name: 'topup', params: { id: topup.student.account_id } }">Try again</RouterLink>
        <RouterLink class="pp-btn pp-btn--tinted" :to="studentRoute">Back to {{ first || 'my child' }}</RouterLink>
      </template>
      <RouterLink v-else class="pp-btn pp-btn--primary" :to="studentRoute">Back to {{ first || 'my child' }}</RouterLink>
    </div>
  </main>
</template>
