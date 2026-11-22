<script setup>
import { computed, onMounted } from 'vue'

import { money, whatsappLink } from '@/format'
import { useBagStore } from '@/stores/bag'
import { useCatalogStore } from '@/stores/catalog'

/**
 * The reference "A considered purchase." checkout, live: the bag is priced and
 * paid on Tap's hosted page through /api/v1/storefront/checkout (Settings →
 * Online Payments in the admin). No card details ever touch this site.
 */
const bag = useBagStore()
const catalog = useCatalogStore()

onMounted(() => {
  document.title = 'Checkout — KASHPERIA'
  bag.loadConfig()
  catalog.loadShops()
  if (bag.step === 'redirecting') bag.resetCheckout()
})

const status = computed(() => bag.result?.status)
const outcome = computed(() => {
  switch (status.value) {
    case 'paid':
      return { title: 'Thank you. Your order is confirmed.', text: 'A confirmation will follow by email. Our Client Services team will be in touch about delivery or collection.' }
    case 'review':
      return { title: 'Payment received.', text: 'Your payment was taken and our team is finalising your order personally. We will contact you shortly.' }
    case 'failed':
      return { title: 'The payment was not completed.', text: 'Nothing has been charged and your bag is just as you left it. You may try again.' }
    case 'pending':
      return { title: 'Your payment is not finished yet.', text: 'Continue to the secure payment page, or check again in a moment.' }
    default:
      return { title: 'We could not confirm this payment.', text: 'Please contact Client Services with your order reference so we can assist.' }
  }
})
const canDeliver = computed(() => bag.config.delivery)
</script>

<template>
  <main class="k-checkout">
    <span class="k-steps">YOUR BAG / CHECKOUT</span>
    <h1 class="serif">A considered purchase.</h1>

    <!-- Back from Tap -->
    <div v-if="bag.step === 'confirming'" class="k-demo-note">Confirming your payment with the bank…</div>
    <section v-else-if="bag.step === 'result'" class="k-checkout-result" :class="`is-${status || 'unknown'}`">
      <span class="eyebrow">ORDER {{ bag.result?.invoice_no || bag.result?.reference || '' }}</span>
      <h2 class="serif">{{ outcome.title }}</h2>
      <p>{{ outcome.text }}</p>
      <p v-if="bag.result?.amount" class="pdp-hint">{{ bag.result.currency || 'QAR' }} {{ bag.result.amount }}</p>
      <div class="k-bag-actions">
        <button v-if="status === 'pending'" type="button" @click="bag.continuePayment()">CONTINUE TO PAYMENT</button>
        <button v-if="status === 'pending'" type="button" class="secondary" @click="bag.recheck()">CHECK AGAIN</button>
        <button v-if="status === 'failed'" type="button" @click="bag.resetCheckout()">TRY AGAIN</button>
        <RouterLink v-if="status === 'paid' || status === 'review'" to="/" custom v-slot="{ navigate }">
          <button type="button" @click="bag.resetCheckout(); navigate()">CONTINUE TO THE HOUSE</button>
        </RouterLink>
      </div>
    </section>

    <div v-else-if="!bag.lines.length" class="k-curated-empty">
      <h2 class="serif">Your shopping bag is empty.</h2>
      <p>Discover the collections and add a creation to begin.</p>
      <RouterLink to="/collection/women" custom v-slot="{ navigate }"><button type="button" class="k-side-clear" @click="navigate">EXPLORE COLLECTIONS →</button></RouterLink>
    </div>

    <template v-else>
      <div v-if="bag.config.checked && !bag.config.enabled" class="k-demo-note">
        Online payment is being prepared. Our Client Services team will gladly complete your purchase personally —
        <a :href="whatsappLink('Hello Kashperia, I would like to purchase: ' + bag.lines.map((l) => `${l.name} × ${l.qty}`).join(', '))" target="_blank" rel="noopener noreferrer">continue on WhatsApp</a>.
      </div>
      <div v-else-if="bag.config.test_mode" class="k-demo-note">Test mode — payments are simulated and no card is charged.</div>

      <div class="k-checkout-grid">
        <div>
          <form id="kCheckoutForm" @submit.prevent="bag.pay()">
            <h2 class="serif">Your selection</h2>
            <div class="k-fulfil" role="radiogroup" aria-label="Delivery or collection">
              <label :class="{ active: bag.fulfilment === 'pickup' }">
                <input v-model="bag.fulfilment" type="radio" value="pickup" /> Collect from a boutique
              </label>
              <label v-if="canDeliver" :class="{ active: bag.fulfilment === 'delivery' }">
                <input v-model="bag.fulfilment" type="radio" value="delivery" /> Deliver to me
              </label>
            </div>

            <template v-if="bag.fulfilment === 'pickup'">
              <label for="kcShop">Boutique</label>
              <select id="kcShop" v-model="bag.branchId">
                <option :value="null" disabled>Choose a boutique</option>
                <option v-for="s in catalog.shops" :key="s.id" :value="s.id">{{ s.name || s.location }}</option>
              </select>
            </template>

            <h2 class="serif">Your details</h2>
            <div class="k-checkout-fields">
              <div><label for="kcName">Full name</label><input id="kcName" v-model="bag.customer.name" autocomplete="name" placeholder="Full name" /></div>
              <div><label for="kcEmail">Email</label><input id="kcEmail" v-model="bag.customer.email" type="email" autocomplete="email" placeholder="Email address" /></div>
              <div><label for="kcCode">Country code</label><input id="kcCode" v-model="bag.customer.countryCode" inputmode="numeric" autocomplete="tel-country-code" placeholder="974" /></div>
              <div><label for="kcPhone">Mobile number</label><input id="kcPhone" v-model="bag.customer.mobile" type="tel" autocomplete="tel-national" placeholder="Mobile number" /></div>
            </div>

            <template v-if="bag.fulfilment === 'delivery'">
              <h2 class="serif">Delivery address</h2>
              <div class="k-checkout-fields">
                <div><label for="kcZone">Zone</label><input id="kcZone" v-model="bag.customer.zoneNumber" inputmode="numeric" placeholder="Zone number" /></div>
                <div><label for="kcStreet">Street</label><input id="kcStreet" v-model="bag.customer.streetNumber" inputmode="numeric" placeholder="Street number" /></div>
                <div><label for="kcBuilding">Building</label><input id="kcBuilding" v-model="bag.customer.buildingNumber" inputmode="numeric" placeholder="Building number" /></div>
                <div><label for="kcCity">City</label><input id="kcCity" v-model="bag.customer.city" autocomplete="address-level2" placeholder="City" /></div>
              </div>
            </template>

            <p v-if="bag.error" class="k-checkout-error" role="alert">{{ bag.error }}</p>
            <p class="pdp-hint">You will complete payment on our secure payment partner's page. Card details are never stored by Kashperia.</p>
            <button class="k-checkout-submit" type="submit" :disabled="bag.submitting || bag.step === 'redirecting' || !bag.config.enabled">
              {{ bag.step === 'redirecting' ? 'TAKING YOU TO SECURE PAYMENT…' : bag.submitting ? 'PREPARING YOUR ORDER…' : `PAY ${money(bag.total)}` }}
            </button>
          </form>
        </div>

        <aside class="k-summary">
          <span class="eyebrow">YOUR SELECTION</span>
          <div v-for="line in bag.lines" :key="line.key" class="k-bag-item">
            <img :src="line.img || ''" alt="" />
            <div>
              <h3 class="serif">{{ line.name }}</h3>
              <p v-if="line.variant" class="pdp-hint">{{ line.variant }}</p>
              <p class="pdp-hint">{{ line.qty }} × {{ money(line.price) }}</p>
            </div>
          </div>
          <hr class="pdp-rule" />
          <p class="k-bag-total"><span>Subtotal</span><strong>{{ money(bag.total) }}</strong></p>
          <p class="pdp-hint">Final prices and availability are confirmed when the secure payment opens.</p>
          <button type="button" class="btn light" @click="bag.openDrawer('cart')">EDIT BAG</button>
        </aside>
      </div>
    </template>
  </main>
</template>
