<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'

import { fetchStudent, startTopup } from '@/api/parent'
import AppBar from '@/components/AppBar.vue'
import LoadError from '@/components/LoadError.vue'
import { school } from '@/school'
import { amount as plainAmount, firstName, money } from '@/utils/format'
import { goToQPay } from '@/utils/qpay'

const route = useRoute()
const id = Number(route.params.id)

const student = ref(null)
const status = ref('loading')
const loadError = ref('')

const input = ref('')
const touched = ref(false)
const paying = ref(false)
const payError = ref('')

const first = computed(() => firstName(student.value?.name))
const limits = computed(() => student.value?.topup || { min: 0, max: 0, suggestions: [], enabled: false })
const value = computed(() => {
  const cleaned = String(input.value).replace(/,/g, '').trim()
  return /^\d+(\.\d{0,2})?$/.test(cleaned) ? Number(cleaned) : null
})
const valid = computed(() => value.value !== null && value.value >= limits.value.min && value.value <= limits.value.max)
const newBalance = computed(() => Number(student.value?.balance || 0) + (value.value || 0))
const cardState = computed(() => (!student.value?.has_card ? 'none' : student.value.card_blocked ? 'blocked' : 'active'))

const hint = computed(() => {
  if (touched.value && input.value !== '' && !valid.value) {
    return { text: `Enter an amount between ${money(limits.value.min)} and ${money(limits.value.max)}.`, error: true }
  }
  return { text: `Between ${money(limits.value.min)} and ${money(limits.value.max)}. Paid with a Qatar debit card through QPay.`, error: false }
})

function pick(suggestion) {
  input.value = plainAmount(suggestion)
  touched.value = true
  payError.value = ''
}

async function load() {
  status.value = 'loading'
  try {
    student.value = await fetchStudent(id)
    const suggestions = student.value.topup.suggestions
    if (!input.value && suggestions.length) input.value = plainAmount(suggestions[Math.min(1, suggestions.length - 1)])
    status.value = 'ready'
  } catch (e) {
    loadError.value = e.status === 404 ? "This child isn't linked to your login." : e.message
    status.value = 'error'
  }
}

async function pay() {
  touched.value = true
  payError.value = ''
  if (!valid.value) return

  paying.value = true
  try {
    const { payment } = await startTopup(id, value.value)
    // Leaves the portal for QPay's page; QPay brings the parent back to #/topups/{pun}.
    goToQPay(payment)
  } catch (e) {
    payError.value = e.message
    paying.value = false
  }
}

onMounted(load)
</script>

<template>
  <AppBar :back="{ name: 'student', params: { id } }" :back-label="first || 'Back'" title="Top up" />

  <LoadError v-if="status === 'error'" title="We couldn't open top-up" :message="loadError" @retry="load" />

  <template v-else>
    <main>
      <div class="pp-largetitle">
        <h1 v-if="student">Top up {{ first }}'s card</h1>
        <span v-else class="pp-skel pp-skel--title"></span>
      </div>

      <div class="pp-topup-card">
        <span class="pp-minicard" aria-hidden="true"></span>
        <span class="pp-row__main">
          <span class="pp-row__sub">Current balance</span>
          <b v-if="student" :class="{ 'pp-text-neg': student.balance < 0 }">{{ money(student.balance) }}</b>
          <span v-else class="pp-skel pp-skel--line" style="width: 100px; height: 20px"></span>
        </span>
        <span v-if="student && cardState === 'active'" class="pp-status pp-status--soft pp-status--active">Card active</span>
        <span v-else-if="student && cardState === 'blocked'" class="pp-tag pp-tag--neg">Card blocked</span>
      </div>

      <div v-if="student && !limits.enabled" class="pp-alert pp-alert--warn" role="alert">
        <i class="fa fa-exclamation-triangle"></i>
        <span class="pp-alert__main">
          <span class="pp-alert__title">Online top-up isn't available</span>Please contact the school office to add money to {{ first }}'s card.
        </span>
      </div>

      <form v-else-if="student" id="topup-form" novalidate @submit.prevent="pay">
        <div v-if="payError" class="pp-alert" role="alert">
          <i class="fa fa-exclamation-circle"></i><span class="pp-alert__main">{{ payError }}</span>
        </div>

        <section class="pp-group">
          <h2 class="pp-group__head">Amount</h2>
          <div v-if="limits.suggestions.length" class="pp-chips" role="group" aria-label="Quick amounts">
            <button
              v-for="suggestion in limits.suggestions"
              :key="suggestion"
              class="pp-chip"
              type="button"
              :aria-pressed="value === suggestion"
              @click="pick(suggestion)"
            >
              {{ suggestion }}
            </button>
          </div>
          <div class="pp-group__body">
            <label class="pp-amount-field">
              <span class="pp-amount-field__cur">{{ school.currency.code }}</span>
              <input
                v-model="input"
                type="text"
                inputmode="decimal"
                autocomplete="off"
                placeholder="0.00"
                :aria-label="`Amount in ${school.currency.code}`"
                :aria-invalid="hint.error || undefined"
                @input="payError = ''"
                @blur="touched = true"
              />
            </label>
          </div>
          <p class="pp-group__foot" :class="{ 'is-error': hint.error }" aria-live="polite">{{ hint.text }}</p>
        </section>

        <section class="pp-group">
          <div class="pp-group__body">
            <div class="pp-row">
              <span class="pp-row__main"><span class="pp-row__title">New balance after top-up</span></span>
              <b class="pp-row__value">{{ valid ? money(newBalance) : '—' }}</b>
            </div>
          </div>
        </section>
      </form>
    </main>

    <footer v-if="student && limits.enabled" class="pp-actionbar">
      <button class="pp-btn pp-btn--pay" :class="{ 'is-busy': paying }" type="submit" form="topup-form" :disabled="paying">
        <span v-if="paying" class="pp-spinner pp-spinner--sm" aria-hidden="true"></span><i v-else class="fa fa-lock"></i>
        {{ paying ? 'Opening QPay…' : valid ? `Pay ${money(value)} with QPay` : 'Pay with QPay' }}
      </button>
      <p class="pp-trust"><i class="fa fa-shield"></i>You'll pay on QPay's secure page, not in this app.</p>
    </footer>
  </template>
</template>
