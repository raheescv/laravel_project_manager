<script setup>
import { computed, nextTick, ref, watch } from 'vue'
import { useRouter } from 'vue-router'

import { field, money, t } from '@/i18n'
import { useBagStore } from '@/stores/bag'
import { useCatalogStore } from '@/stores/catalog'
import { useShopsStore } from '@/stores/shops'
import { toast } from '@/toast'
import { initialOf } from '@/utils/catalog'

const bag = useBagStore()
const catalog = useCatalogStore()
const shops = useShopsStore()
const router = useRouter()

const closeBtn = ref(null)
let lastFocus = null

// Focus moves into the drawer on open and back to the trigger on close.
watch(
  () => bag.open,
  async (open) => {
    if (open) {
      // Retry a config that failed at boot (flaky network) before deciding checkout is off.
      bag.loadConfig()
      lastFocus = document.activeElement
      await nextTick()
      closeBtn.value?.focus()
    } else if (lastFocus && typeof lastFocus.focus === 'function') {
      lastFocus.focus()
      lastFocus = null
    }
  },
)

// One shop? Nothing to choose — collect from it.
watch(
  () => [bag.step, shops.shops.length],
  () => {
    if (bag.step === 'details' && !bag.branchId && shops.shops.length === 1) bag.branchId = shops.shops[0].id
  },
  { immediate: true },
)

const busy = computed(() => bag.step === 'redirecting' || bag.step === 'confirming')
const outcome = computed(() => bag.result?.status || 'unknown')
const outcomeBody = computed(() => {
  if (outcome.value === 'review') return t('reviewBody')
  if (bag.result?.fulfilment === 'delivery') return t('paidDelivery')
  return t('paidPickup', { shop: bag.result?.branch?.name || '' })
})

function change(key, delta) {
  if (bag.setQty(key, delta) === 'removed') toast(t('removed'))
}

function remove(key) {
  bag.remove(key)
  toast(t('removed'))
}

async function findSize() {
  bag.close()
  await router.push({ name: 'catalogue', query: catalog.toQuery() })
  setTimeout(() => document.getElementById('size')?.scrollIntoView({ behavior: 'smooth' }), 80)
}

function keepShopping() {
  bag.close()
  bag.reset()
}
</script>

<template>
  <aside
    id="bag"
    class="bag"
    role="dialog"
    aria-modal="true"
    aria-labelledby="bagTitle"
    :aria-hidden="!bag.open"
  >
    <div class="bag__head">
      <p id="bagTitle" class="h-sm">{{ t('yourBag') }}</p>
      <button ref="closeBtn" class="bag__close" :aria-label="t('close')" @click="bag.close()">
        &times;
      </button>
    </div>

    <div class="bag__body">
      <!-- off to Tap, or back from it and confirming -->
      <div v-if="busy" class="done" aria-live="polite">
        <span class="co__spin" aria-hidden="true"></span>
        <p class="done__body">{{ t(bag.step === 'redirecting' ? 'redirecting' : 'confirming') }}</p>
      </div>

      <!-- payment outcome -->
      <div v-else-if="bag.step === 'result'" class="done" aria-live="polite">
        <template v-if="outcome === 'paid' || outcome === 'review'">
          <span class="done__tick">&#10003;</span>
          <p class="done__title">{{ t(outcome === 'paid' ? 'paidTitle' : 'reviewTitle') }}</p>
          <p class="done__body">{{ outcomeBody }}</p>
          <p v-if="bag.result?.invoice_no" class="done__ref">
            {{ t('orderNo') }} <bdi dir="ltr">{{ bag.result.invoice_no }}</bdi> · {{ money(bag.result.amount) }}
          </p>
          <button class="btn btn--dark" @click="keepShopping">{{ t('keepShopping') }}</button>
        </template>

        <template v-else-if="outcome === 'failed'">
          <span class="done__tick done__tick--bad">&times;</span>
          <p class="done__title">{{ t('failedTitle') }}</p>
          <p class="done__body">{{ t('failedBody') }}</p>
          <button v-if="bag.lines.length" class="btn btn--primary" @click="bag.beginCheckout()">
            {{ t('retry') }}
          </button>
          <button class="linkbtn" @click="bag.backToBag()">{{ t('backToBag') }}</button>
        </template>

        <template v-else>
          <span class="done__tick done__tick--wait">&hellip;</span>
          <p class="done__title">{{ t('pendingTitle') }}</p>
          <p class="done__body">{{ t('pendingBody') }}</p>
          <button v-if="bag.result?.payment_url" class="btn btn--primary" @click="bag.continuePayment()">
            {{ t('continuePayment') }}
          </button>
          <button class="btn btn--dark" @click="bag.recheck()">{{ t('checkAgain') }}</button>
          <button class="linkbtn" @click="bag.backToBag()">{{ t('backToBag') }}</button>
        </template>
      </div>

      <!-- empty -->
      <div v-else-if="!bag.lines.length" class="empty empty--bag">
        <span class="empty__rule"></span>
        <p class="empty__title">{{ t('bagEmptyTitle') }}</p>
        <p class="empty__body">{{ t('bagEmptyBody') }}</p>
        <button class="btn btn--dark" @click="findSize">{{ t('bagEmptyCta') }}</button>
      </div>

      <!-- checkout: where and who -->
      <form v-else-if="bag.step === 'details'" id="checkoutForm" class="co" novalidate @submit.prevent="bag.pay()">
        <button type="button" class="linkbtn co__back" @click="bag.backToBag()">{{ t('backToBag') }}</button>

        <div class="co__group">
          <span class="h-sm">{{ bag.fulfilment === 'delivery' ? t('address') : t('pickShop') }}</span>
          <div v-if="bag.fulfilment === 'pickup'" class="shops" role="radiogroup" :aria-label="t('pickShop')">
            <label v-for="s in shops.shops" :key="s.id" class="shop" :class="{ 'is-on': bag.branchId === s.id }">
              <input v-model="bag.branchId" type="radio" name="shop" :value="s.id" />
              <span class="shop__name">{{ s.name || s.code }}</span>
              <span v-if="s.location" class="shop__meta">{{ s.location }}</span>
            </label>
          </div>
          <label v-else class="fld">
            <span class="sr-only">{{ t('address') }}</span>
            <textarea v-model="bag.customer.address" rows="3" maxlength="500" autocomplete="street-address"></textarea>
          </label>
        </div>

        <div class="co__group">
          <span class="h-sm">{{ t('yourDetails') }}</span>
          <label class="fld">
            <span class="fld__label">{{ t('fullName') }}</span>
            <input v-model="bag.customer.name" type="text" maxlength="100" autocomplete="name" />
          </label>
          <label class="fld">
            <span class="fld__label">{{ t('email') }}</span>
            <input v-model="bag.customer.email" type="email" maxlength="150" autocomplete="email" dir="ltr" />
          </label>
          <div class="fld">
            <span id="mobileLabel" class="fld__label">{{ t('mobile') }}</span>
            <div class="phone" dir="ltr">
              <span class="phone__plus" aria-hidden="true">+</span>
              <input
                v-model="bag.customer.countryCode"
                class="phone__cc"
                inputmode="numeric"
                maxlength="4"
                autocomplete="tel-country-code"
                :aria-label="t('countryCode')"
              />
              <input
                v-model="bag.customer.mobile"
                class="phone__num"
                type="tel"
                inputmode="numeric"
                maxlength="15"
                autocomplete="tel-national"
                aria-labelledby="mobileLabel"
              />
            </div>
          </div>
        </div>

        <p v-if="bag.error" class="co__error" role="alert">{{ bag.error }}</p>
      </form>

      <!-- lines -->
      <template v-else>
        <div class="lines">
          <div v-for="l in bag.lines" :key="l.key" class="line">
            <div class="line__media">
              <img v-if="l.img" :src="l.img" alt="" loading="lazy" />
              <div v-else class="card__ph card__ph--sm"><span>{{ initialOf(l.brand || l.name) }}</span></div>
            </div>
            <div>
              <p class="line__brand">{{ l.brand }}</p>
              <p class="line__name">{{ field(l, 'name') }}</p>
              <p v-if="l.size" class="line__meta">{{ t('size') }} {{ l.size }}</p>
              <div class="qty qty--sm" style="margin-block-start: 8px">
                <button aria-label="−" @click="change(l.key, -1)">&minus;</button>
                <span>{{ l.qty }}</span>
                <button aria-label="+" @click="change(l.key, 1)">+</button>
              </div>
            </div>
            <div class="line__side">
              <span class="price">{{ money(l.price * l.qty) }}</span>
              <button class="linkbtn" @click="remove(l.key)">{{ t('remove') }}</button>
            </div>
          </div>
        </div>

        <!-- Delivery is offered only when the store set a branch to ship from. -->
        <div v-if="bag.config.enabled && bag.config.delivery" class="ful">
          <span class="h-sm">{{ t('fulfilment') }}</span>
          <div class="seg">
            <button
              class="seg__btn"
              :class="{ 'is-on': bag.fulfilment === 'pickup' }"
              @click="bag.fulfilment = 'pickup'"
            >
              {{ t('pickup') }}
            </button>
            <button
              class="seg__btn"
              :class="{ 'is-on': bag.fulfilment === 'delivery' }"
              @click="bag.fulfilment = 'delivery'"
            >
              {{ t('delivery') }}
            </button>
          </div>
        </div>
      </template>
    </div>

    <div v-if="bag.lines.length && (bag.step === 'bag' || bag.step === 'details')" class="bag__foot">
      <div class="total">
        <span>{{ t('total') }}</span>
        <span class="price price--lg">{{ money(bag.total) }}</span>
      </div>
      <template v-if="!bag.config.checked" />
      <p v-else-if="!bag.config.enabled" class="fineprint">{{ t('checkoutOff') }}</p>
      <template v-else-if="bag.step === 'bag'">
        <button class="btn btn--primary btn--block" @click="bag.beginCheckout()">{{ t('checkout') }}</button>
        <p class="fineprint">{{ t('fineprint') }}</p>
      </template>
      <template v-else>
        <button class="btn btn--primary btn--block" type="submit" form="checkoutForm" :disabled="bag.submitting">
          {{ bag.submitting ? t('redirecting') : t('payNow', { total: money(bag.total) }) }}
        </button>
        <p class="fineprint">{{ t('payFine') }}</p>
      </template>
      <span v-if="bag.config.enabled && bag.config.test_mode" class="co__test">{{ t('testMode') }}</span>
    </div>
  </aside>
</template>
