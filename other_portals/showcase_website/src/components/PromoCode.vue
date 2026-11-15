<script setup>
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue'

import { field, money, percentOff, t } from '@/i18n'
import { useBagStore } from '@/stores/bag'

// Below this the API refuses, so nobody can walk the codes letter by letter.
const MIN_CHARS = 3
const TYPING_PAUSE_MS = 300

const bag = useBagStore()

const box = ref(null)
const input = ref(null)
const isOpen = ref(false)
const query = ref('')
const phase = ref('idle') // idle | short | loading | done | failed
const results = ref([])
// The customer asked to see every open code, rather than searching for one.
const browsing = ref(false)
let timer = null
let latest = 0

const code = computed(() => query.value.replace(/\s+/g, '').toUpperCase())
const readyCount = computed(() => results.value.filter((r) => r.state === 'ready').length)

const hint = computed(() => {
  if (phase.value === 'short' || phase.value === 'idle') return t('promoHint')
  if (phase.value === 'loading') return t('promoSearching')
  if (phase.value === 'failed') return t('promoFailed')
  if (!results.value.length) return browsing.value ? t('promoNoneAvailable') : t('promoNone', { code: code.value })
  if (!readyCount.value) return t(browsing.value ? 'promoShopMore' : 'promoCantUse')
  if (results.value.length === 1) return t('promoTapOne')
  return t(browsing.value ? 'promoAvailable' : 'promoFound', { n: results.value.length })
})
const hintIsError = computed(() => phase.value === 'failed' || (phase.value === 'done' && !results.value.length && !browsing.value))

/** Why a code cannot be used yet, or was just made unusable by a change to the bag. */
function reason(row) {
  if (row.state === 'locked') return t('promoAdd', { amount: money(row.short) })
  return t(row.state === 'ready' ? 'promo_unavailable' : `promo_${row.state}`)
}

/** The code split around what the customer typed, so the match can be marked safely. */
function parts(rowCode) {
  const at = code.value ? rowCode.indexOf(code.value) : -1
  if (at < 0) return [rowCode, '', '']
  return [rowCode.slice(0, at), code.value, rowCode.slice(at + code.value.length)]
}

/** Ask the API for [what] ('' = every open code) after [delay], keeping only the latest answer. */
function load(what, delay) {
  clearTimeout(timer)
  const request = ++latest
  phase.value = 'loading'
  timer = setTimeout(async () => {
    try {
      const found = await bag.searchCodes(what)
      if (request !== latest) return
      results.value = found.results
      phase.value = 'done'
      await nextTick()
      box.value?.scrollIntoView({ block: 'nearest', behavior: 'smooth' })
    } catch {
      if (request === latest) phase.value = 'failed'
    }
  }, delay)
}

function search(now = false) {
  // Field cleared: back to the list of codes on offer.
  if (!code.value) return browse()

  browsing.value = false

  if (code.value.length < MIN_CHARS) {
    clearTimeout(timer)
    latest++
    phase.value = code.value && now ? 'short' : 'idle'
    results.value = []
    return
  }

  load(code.value, now ? 0 : TYPING_PAUSE_MS)
}

function browse() {
  query.value = ''
  browsing.value = true
  load('', 0)
}

function clear() {
  clearTimeout(timer)
  latest++
  query.value = ''
  phase.value = 'idle'
  results.value = []
  browsing.value = false
}

async function toggle() {
  isOpen.value = !isOpen.value
  clear()
  if (isOpen.value) {
    // Opening shows the codes on offer straight away; typing narrows to a search.
    browse()
    await nextTick()
    input.value?.focus()
  }
}

function select(row) {
  bag.applyCoupon(row)
  isOpen.value = false
  clear()
}

// A quantity changed with results on screen: their savings are out of date.
watch(
  () => bag.total,
  () => {
    if (isOpen.value && phase.value === 'done') browsing.value ? browse() : search(true)
  },
)

onBeforeUnmount(() => clearTimeout(timer))
</script>

<template>
  <div class="promo">
    <!-- a code is on the bag -->
    <div v-if="bag.coupon" class="promo__on" :class="{ 'is-warn': !bag.discount }">
      <span class="promo__mark" aria-hidden="true">
        <svg v-if="bag.discount" viewBox="0 0 18 18"><path d="M4 9.5l3.2 3.2L14 5.5" /></svg>
        <svg v-else viewBox="0 0 18 18"><path d="M9 3l7 12H2zM9 8v3M9 13.2v.1" /></svg>
      </span>
      <span class="promo__txt">
        <span><bdi dir="ltr" class="promo__code">{{ bag.coupon.code }}</bdi> {{ t(bag.discount ? 'promoApplied' : 'promoPaused') }}</span>
        <small>
          <template v-if="bag.discount">{{ t('promoYouSave', { amount: money(bag.discount) }) }} <bdi dir="ltr">{{ percentOff(bag.coupon) }}</bdi></template>
          <template v-else>{{ reason(bag.coupon) }}</template>
        </small>
      </span>
      <button type="button" class="linkbtn" @click="bag.applyCoupon(null)">{{ t('remove') }}</button>
    </div>

    <template v-else>
      <button type="button" class="promo__ask" :class="{ 'is-open': isOpen }" :aria-expanded="isOpen" @click="toggle">
        <span>{{ t('promoAsk') }}</span>
        <i class="promo__plus" aria-hidden="true"></i>
      </button>

      <div v-if="isOpen" ref="box" class="promo__box">
        <form class="promo__form" role="search" novalidate @submit.prevent="search(true)">
          <svg class="promo__glass" viewBox="0 0 20 20" aria-hidden="true"><circle cx="9" cy="9" r="6" /><path d="M14 14l4 4" /></svg>
          <input
            ref="input"
            v-model="query"
            type="text"
            maxlength="24"
            dir="ltr"
            autocomplete="off"
            autocapitalize="characters"
            spellcheck="false"
            enterkeyhint="search"
            :placeholder="t('promoPlaceholder')"
            :aria-label="t('promoPlaceholder')"
            aria-describedby="promoHint"
            @input="search()"
          />
          <button class="promo__search" type="submit" :disabled="!code">{{ t('promoSearch') }}</button>
        </form>

        <div class="promo__meta">
          <p id="promoHint" class="promo__hint" :class="{ 'is-err': hintIsError }" aria-live="polite">{{ hint }}</p>
          <button v-if="!browsing" type="button" class="promo__browse" @click="browse">{{ t('promoBrowse') }}</button>
        </div>

        <div v-if="phase === 'loading'" class="promo__list" aria-hidden="true">
          <div v-for="n in 2" :key="n" class="promo__row is-sk"><span></span><span></span></div>
        </div>

        <div v-else-if="phase === 'done' && results.length" class="promo__list">
          <template v-for="(row, index) in results" :key="row.code">
            <button
              v-if="row.state === 'ready'"
              type="button"
              class="promo__row"
              :style="{ '--i': index }"
              :aria-label="t('promoUse', { code: row.code, amount: money(row.saving) })"
              @click="select(row)"
            >
              <span class="promo__code" dir="ltr">{{ parts(row.code)[0] }}<mark v-if="parts(row.code)[1]">{{ parts(row.code)[1] }}</mark>{{ parts(row.code)[2] }}<em v-if="index === 0 && readyCount > 1" class="promo__tag">{{ t('promoBest') }}</em><em v-if="row.is_private" class="promo__tag promo__tag--dark">{{ t('promoPrivate') }}</em></span>
              <span class="promo__amt">&minus; {{ money(row.saving) }} <bdi dir="ltr">{{ percentOff(row) }}</bdi></span>
              <span class="promo__title"><bdi>{{ field(row, 'title') }}</bdi></span>
              <span class="promo__after">{{ t('promoNewTotal', { amount: money(row.new_total) }) }}</span>
            </button>

            <div v-else class="promo__row is-off" :style="{ '--i': index }">
              <span class="promo__code" dir="ltr">{{ parts(row.code)[0] }}<mark v-if="parts(row.code)[1]">{{ parts(row.code)[1] }}</mark>{{ parts(row.code)[2] }}</span>
              <span class="promo__amt">{{ reason(row) }}</span>
              <span class="promo__title"><bdi>{{ field(row, 'title') }}</bdi></span>
            </div>
          </template>
        </div>
      </div>
    </template>
  </div>
</template>
