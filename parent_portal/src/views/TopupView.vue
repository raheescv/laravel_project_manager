<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useRoute } from 'vue-router'

import { fetchStudent, startTopup } from '@/api/parent'
import AppBar from '@/components/AppBar.vue'
import LoadError from '@/components/LoadError.vue'
import StudentAvatar from '@/components/StudentAvatar.vue'
import { refreshChild } from '@/children'
import { school } from '@/school'
import { amount as plainAmount, classLabel, firstName, money } from '@/utils/format'
import { goToPayment } from '@/utils/checkout'
import { desktop } from '@/utils/viewport'

const route = useRoute()
const id = Number(route.params.id)

const student = ref(null)
const status = ref('loading')
const loadError = ref('')

const input = ref('')
const touched = ref(false)
// 'debit' (QPay) or 'credit' (Mastercard Gateway) — whichever the school offers.
const method = ref('')
const paying = ref(false)
const payError = ref('')

// An unfinished top-up blocks a new one until QPay can be asked about it. The API
// says when that is, so the wait is a ticking clock here rather than a time to watch for.
const retryAt = ref(0)
const freed = ref(false)
// The reference of the top-up that is holding this one up: its page says where it stands.
const pendingPun = ref('')
const now = ref(Date.now())
let ticker = 0

const waiting = computed(() => Math.max(0, retryAt.value - now.value))
const blocked = computed(() => waiting.value > 0)
const countdown = computed(() => {
  const seconds = Math.ceil(waiting.value / 1000)
  return `${Math.floor(seconds / 60)}:${String(seconds % 60).padStart(2, '0')}`
})

const first = computed(() => firstName(student.value?.name))
const limits = computed(() => student.value?.topup || { min: 0, max: 0, suggestions: [], methods: [], enabled: false })
const methods = computed(() => limits.value.methods || [])
const chosen = computed(() => methods.value.find((option) => option.key === method.value) || methods.value[0] || null)
const methodIcon = { debit: 'fa-university', credit: 'fa-credit-card' }
const trust = computed(() =>
  chosen.value?.key === 'credit'
    ? "You'll pay on your bank's secure Mastercard Gateway page, not in this app."
    : "You'll pay on QPay's secure page, not in this app.",
)
const value = computed(() => {
  const cleaned = String(input.value).replace(/,/g, '').trim()
  return /^\d+(\.\d{0,2})?$/.test(cleaned) ? Number(cleaned) : null
})
const valid = computed(() => value.value !== null && value.value >= limits.value.min && value.value <= limits.value.max)
const newBalance = computed(() => Number(student.value?.balance || 0) + (value.value || 0))
const cardState = computed(() => (!student.value?.has_card ? 'none' : student.value.card_blocked ? 'blocked' : 'active'))
const payLabel = computed(() => {
  if (blocked.value) return `Try again in ${countdown.value}`
  if (paying.value) return 'Opening the payment page…'
  const by = chosen.value ? ` by ${chosen.value.label.toLowerCase()}` : ''
  return valid.value ? `Pay ${money(value.value)}${by}` : `Pay${by}`
})

const hint = computed(() => {
  if (touched.value && input.value !== '' && !valid.value) {
    return { text: `Enter an amount between ${money(limits.value.min)} and ${money(limits.value.max)}.`, error: true }
  }
  return { text: `Between ${money(limits.value.min)} and ${money(limits.value.max)}.`, error: false }
})

function stopTicker() {
  clearInterval(ticker)
  ticker = 0
}

/** Count down to `at` (ISO 8601 from the API); at zero the parent may pay again. */
function holdUntil(at) {
  stopTicker()
  const until = at ? new Date(at).getTime() : 0
  now.value = Date.now()
  retryAt.value = Number.isFinite(until) ? until : 0
  if (!blocked.value) {
    retryAt.value = 0
    return
  }
  ticker = setInterval(() => {
    now.value = Date.now()
    if (blocked.value) return
    stopTicker()
    retryAt.value = 0
    payError.value = ''
    freed.value = true
  }, 1000)
}

/** Typing or picking an amount dismisses a stale message — never a wait that is still running. */
function dismiss() {
  freed.value = false
  if (!blocked.value) payError.value = ''
}

const METHOD_KEY = 'pp.topupMethod'

/** The card type chosen last time on this device, if the school still offers it. */
function rememberedMethod(options) {
  let saved = ''
  try {
    saved = localStorage.getItem(METHOD_KEY) || ''
  } catch {
    saved = ''
  }
  return (options.find((option) => option.key === saved) || options[0])?.key || ''
}

function chooseMethod(key) {
  method.value = key
  dismiss()
  try {
    localStorage.setItem(METHOD_KEY, key)
  } catch {
    // Remembering the choice is a convenience only.
  }
}

function pick(suggestion) {
  input.value = plainAmount(suggestion)
  touched.value = true
  dismiss()
}

async function load() {
  status.value = 'loading'
  try {
    student.value = await fetchStudent(id)
    refreshChild(student.value)
    const suggestions = student.value.topup.suggestions
    if (!input.value && suggestions.length) input.value = plainAmount(suggestions[Math.min(1, suggestions.length - 1)])
    if (!methods.value.some((option) => option.key === method.value)) method.value = rememberedMethod(methods.value)
    status.value = 'ready'
  } catch (e) {
    loadError.value = e.status === 404 ? "This child isn't linked to your login." : e.message
    status.value = 'error'
  }
}

async function pay() {
  touched.value = true
  dismiss()
  if (!valid.value || blocked.value) return

  paying.value = true
  try {
    const { payment } = await startTopup(id, value.value, chosen.value?.key)
    // Leaves the portal for the gateway's page; it brings the parent back to #/topups/{pun}.
    await goToPayment(payment)
  } catch (e) {
    payError.value = e.message
    pendingPun.value = e.errors?.pun || ''
    holdUntil(e.errors?.retry_at)
    paying.value = false
  }
}

onMounted(load)
onUnmounted(stopTicker)
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

      <div v-if="!desktop" class="pp-topup-card">
        <StudentAvatar v-if="student" :name="student.name" :image="student.image_url" />
        <span v-else class="pp-skel pp-avatar" aria-hidden="true"></span>
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

      <div v-else-if="student" class="pp-checkout">
        <form id="topup-form" novalidate @submit.prevent="pay">
          <div v-if="freed" class="pp-alert pp-alert--info" role="alert">
            <i class="fa fa-check-circle"></i><span class="pp-alert__main">The earlier top-up has waited long enough — you can try again now.</span>
          </div>

          <div v-else-if="payError" class="pp-alert" :class="{ 'pp-alert--warn': blocked }" role="alert">
            <i class="fa" :class="blocked ? 'fa-clock-o' : 'fa-exclamation-circle'"></i>
            <span class="pp-alert__main">
              {{ payError }}
              <!-- The sentence above already names the time; the clock ticks silently for screen readers. -->
              <span v-if="blocked" class="pp-countdown" aria-hidden="true">
                <span class="pp-countdown__pulse"></span>
                <b class="pp-countdown__time">{{ countdown }}</b>
                <span>until you can try again</span>
              </span>
              <RouterLink v-if="pendingPun" class="pp-alert__btn pp-alert__btn--below" :to="{ name: 'topup-result', params: { pun: pendingPun } }">
                See that top-up<i class="fa fa-angle-right"></i>
              </RouterLink>
            </span>
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
                  @input="dismiss()"
                  @blur="touched = true"
                />
              </label>
            </div>
            <p class="pp-group__foot" :class="{ 'is-error': hint.error }" aria-live="polite">{{ hint.text }}</p>
          </section>

          <!-- Debit (QPay) or credit (Mastercard Gateway): shown as a choice only when the school offers both. -->
          <section v-if="methods.length" class="pp-group">
            <h2 class="pp-group__head">Pay with</h2>
            <div class="pp-group__body" :role="methods.length > 1 ? 'radiogroup' : null" aria-label="Card type">
              <template v-if="methods.length > 1">
                <button
                  v-for="option in methods"
                  :key="option.key"
                  class="pp-row pp-row--icon pp-method-row"
                  type="button"
                  role="radio"
                  :aria-checked="chosen?.key === option.key"
                  @click="chooseMethod(option.key)"
                >
                  <span class="pp-row__icon"><i class="fa" :class="methodIcon[option.key] || 'fa-credit-card'"></i></span>
                  <span class="pp-row__main">
                    <span class="pp-row__title">{{ option.label }}</span>
                    <span class="pp-row__sub">{{ option.detail }}</span>
                  </span>
                  <span class="pp-check" aria-hidden="true"><i class="fa fa-check"></i></span>
                </button>
              </template>
              <div v-else class="pp-row pp-row--icon">
                <span class="pp-row__icon"><i class="fa" :class="methodIcon[chosen.key] || 'fa-credit-card'"></i></span>
                <span class="pp-row__main">
                  <span class="pp-row__title">{{ chosen.label }}</span>
                  <span class="pp-row__sub">{{ chosen.detail }}</span>
                </span>
              </div>
            </div>
            <!-- Before they go: leaving QPay's page half-way holds the next top-up (the API words it). -->
            <p v-if="chosen?.notice" class="pp-notice"><i class="fa fa-clock-o"></i><span>{{ chosen.notice }}</span></p>
          </section>

          <section v-if="!desktop" class="pp-group">
            <div class="pp-group__body">
              <div class="pp-row">
                <span class="pp-row__main"><span class="pp-row__title">New balance after top-up</span></span>
                <b class="pp-row__value">{{ valid ? money(newBalance) : '—' }}</b>
              </div>
            </div>
          </section>
        </form>

        <!-- Desktop: the phone's pay bar becomes a summary beside the form -->
        <aside v-if="desktop" class="pp-panel pp-summary">
          <div class="pp-summary__who">
            <StudentAvatar :name="student.name" :image="student.image_url" />
            <span class="pp-row__main"><b>{{ student.name }}</b><small v-if="student.class">{{ classLabel(student.class) }}</small></span>
            <span v-if="cardState === 'active'" class="pp-status pp-status--soft pp-status--active">Card active</span>
            <span v-else-if="cardState === 'blocked'" class="pp-tag pp-tag--neg">Card blocked</span>
          </div>
          <dl class="pp-summary__rows">
            <div><dt>Current balance</dt><dd :class="{ 'pp-text-neg': student.balance < 0 }">{{ money(student.balance) }}</dd></div>
            <div><dt>Top-up</dt><dd>{{ valid ? money(value, { sign: true }) : '—' }}</dd></div>
            <div class="is-total"><dt>New balance</dt><dd>{{ valid ? money(newBalance) : '—' }}</dd></div>
          </dl>
          <button class="pp-btn pp-btn--pay" :class="{ 'is-busy': paying }" type="submit" form="topup-form" :disabled="paying || blocked">
            <span v-if="paying" class="pp-spinner pp-spinner--sm" aria-hidden="true"></span><i v-else class="fa" :class="blocked ? 'fa-clock-o' : 'fa-lock'"></i>
            {{ payLabel }}
          </button>
          <p class="pp-trust"><i class="fa fa-shield"></i>{{ trust }}</p>
        </aside>
      </div>
    </main>

    <footer v-if="!desktop && student && limits.enabled" class="pp-actionbar">
      <button class="pp-btn pp-btn--pay" :class="{ 'is-busy': paying }" type="submit" form="topup-form" :disabled="paying || blocked">
        <span v-if="paying" class="pp-spinner pp-spinner--sm" aria-hidden="true"></span><i v-else class="fa" :class="blocked ? 'fa-clock-o' : 'fa-lock'"></i>
        {{ payLabel }}
      </button>
      <p class="pp-trust"><i class="fa fa-shield"></i>{{ trust }}</p>
    </footer>
  </template>
</template>
