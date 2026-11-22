import { defineStore } from 'pinia'
import { computed, reactive, ref } from 'vue'

import { fetchCheckout, fetchCheckoutConfig, startCheckout } from '@/api/resources'
import { t } from '@/format'

const STORAGE_KEY = 'kp.bag'
// Checkout form and the payment in flight live in sessionStorage: they must
// survive the round trip to Tap, but a shared device forgets them with the tab.
const CUSTOMER_KEY = 'kp.checkout.customer'
const PENDING_KEY = 'kp.checkout.pending'
const FULFILMENT_KEY = 'kp.checkout.fulfilment'
const MAX_QTY = 9
const ADDRESS_PARTS = ['zoneNumber', 'streetNumber', 'buildingNumber', 'city']
// Tap can report a just-paid charge as still in flight for a moment; re-ask a
// few times before showing "not finished yet".
const POLL_MS = 2500
const POLL_TRIES = 5
const COUNTRY_CODE = String(import.meta.env.VITE_COUNTRY_CODE || '974')

function load(kind, key, fallback) {
  try {
    const storage = kind === 'session' ? sessionStorage : localStorage
    const value = JSON.parse(storage.getItem(key) || 'null')
    return value ?? fallback
  } catch {
    return fallback
  }
}

function keep(kind, key, value) {
  try {
    const storage = kind === 'session' ? sessionStorage : localStorage
    if (value == null) storage.removeItem(key)
    else storage.setItem(key, JSON.stringify(value))
  } catch {
    /* private mode */
  }
}

const digits = (v) => String(v ?? '').replace(/\D/g, '')
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms))

function restore() {
  const raw = load('local', STORAGE_KEY, [])
  return Array.isArray(raw) ? raw.filter((l) => l && l.key && l.qty > 0) : []
}

/**
 * The bag and its checkout (same flow as the showcase storefront). Lines carry a
 * display snapshot of the product; the API re-prices every line from the
 * catalogue when a checkout starts.
 *
 * Checkout runs through Tap's hosted payment page:
 *   details → redirecting (off to Tap) … back with ?checkout=REF
 *   → confirming (the API re-checks the charge) → result
 * The bag is emptied only once the API confirms the money was taken.
 */
export const useBagStore = defineStore('bag', () => {
  const lines = ref(restore())
  const drawer = ref(null) // menu | search | cart | null
  const savedFulfilment = load('session', FULFILMENT_KEY, {})
  const fulfilment = ref(savedFulfilment.fulfilment === 'delivery' ? 'delivery' : 'pickup')
  const branchId = ref(savedFulfilment.branchId ?? null)

  const step = ref('details') // details | redirecting | confirming | result
  const config = reactive({ loaded: false, checked: false, enabled: false, delivery: false, test_mode: false })
  const customer = reactive({
    name: '',
    email: '',
    countryCode: COUNTRY_CODE,
    mobile: '',
    zoneNumber: '',
    streetNumber: '',
    buildingNumber: '',
    city: '',
    ...load('session', CUSTOMER_KEY, {}),
  })
  const submitting = ref(false)
  const error = ref('')
  const reference = ref(null)
  const result = ref(null)

  const count = computed(() => lines.value.reduce((n, l) => n + l.qty, 0))
  const total = computed(() => lines.value.reduce((n, l) => n + l.qty * (Number(l.price) || 0), 0))

  function save() {
    keep('local', STORAGE_KEY, lines.value)
  }

  /** item: { id, price, was?, name, variant, img, slug } */
  function add(item, qty = 1) {
    const key = String(item.id)
    const line = lines.value.find((l) => l.key === key)
    if (line) line.qty = Math.min(MAX_QTY, line.qty + qty)
    else lines.value.push({ key, qty: Math.min(MAX_QTY, Math.max(1, qty)), ...item })
    save()
  }

  function setQty(key, delta) {
    const i = lines.value.findIndex((l) => l.key === key)
    if (i < 0) return
    const line = lines.value[i]
    line.qty = Math.min(MAX_QTY, line.qty + delta)
    if (line.qty <= 0) lines.value.splice(i, 1)
    save()
  }

  function remove(key) {
    lines.value = lines.value.filter((l) => l.key !== key)
    save()
  }

  /** Whether (and how) this store takes payment. Best-effort: checkout stays off if it fails. */
  async function loadConfig() {
    if (config.loaded) return
    try {
      Object.assign(config, await fetchCheckoutConfig(), { loaded: true })
      if (!config.delivery) fulfilment.value = 'pickup'
    } catch {
      /* API unreachable, or one that predates checkout */
    } finally {
      config.checked = true
    }
  }

  /** A reason the form can't be sent yet, or '' when it can. */
  function problem() {
    if (fulfilment.value === 'pickup' && !branchId.value) return t('needShop')
    if (!customer.name.trim() || !customer.email.trim() || !customer.mobile.trim()) return t('needDetails')
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(customer.email.trim())) return t('badEmail')
    const cc = digits(customer.countryCode)
    const mobile = digits(customer.mobile)
    if (!cc || cc.length > 4 || mobile.length < 6 || mobile.length > 15) return t('badMobile')
    if (fulfilment.value === 'delivery' && ADDRESS_PARTS.some((k) => !String(customer[k] || '').trim())) return t('needAddress')
    return ''
  }

  /** Open a checkout for the bag and hand the customer to Tap's payment page. */
  async function pay() {
    if (submitting.value) return
    error.value = problem()
    if (error.value) return

    submitting.value = true
    keep('session', CUSTOMER_KEY, { ...customer })
    keep('session', FULFILMENT_KEY, { fulfilment: fulfilment.value, branchId: branchId.value })
    try {
      const checkout = await startCheckout({
        fulfilment: fulfilment.value,
        branchId: fulfilment.value === 'pickup' ? branchId.value : null,
        customerName: customer.name.trim(),
        customerEmail: customer.email.trim(),
        countryCode: digits(customer.countryCode),
        customerMobile: digits(customer.mobile),
        ...Object.fromEntries(
          ADDRESS_PARTS.map((k) => [k, fulfilment.value === 'delivery' ? String(customer[k]).trim() : null]),
        ),
        items: lines.value.map((l) => ({ productId: l.id, quantity: l.qty })),
        // Tap brings the customer back here. No hash: the API appends
        // ?checkout=REF, which a #/route would swallow.
        returnUrl: window.location.origin + window.location.pathname,
      })
      if (!checkout?.payment_url) throw new Error(t('payStartError'))
      keep('session', PENDING_KEY, checkout.reference)
      step.value = 'redirecting'
      window.location.assign(checkout.payment_url)
    } catch (e) {
      error.value = e?.message || t('payStartError')
      submitting.value = false
    }
  }

  /** Ask the API where the payment stands, re-asking briefly while Tap still has it in flight. */
  async function confirm(tries = POLL_TRIES) {
    if (!reference.value) return
    step.value = 'confirming'
    error.value = ''
    for (let i = 0; i < tries; i++) {
      try {
        result.value = await fetchCheckout(reference.value)
        if (result.value?.status !== 'pending') break
      } catch (e) {
        if (e?.status === 404) break
      }
      if (i < tries - 1) await sleep(POLL_MS)
    }

    if (result.value?.fulfilment === 'delivery' || result.value?.fulfilment === 'pickup') {
      fulfilment.value = result.value.fulfilment
      if (result.value.fulfilment === 'pickup' && result.value.branch?.id) branchId.value = result.value.branch.id
    }

    const status = result.value?.status
    if (status === 'paid' || status === 'review') {
      lines.value = []
      save()
      keep('session', PENDING_KEY, null)
    }
    step.value = 'result'
  }

  /**
   * Called once at boot. Tap sends the customer back to this page with
   * ?checkout=REF&tap_id=…; returns true when there is a payment to confirm.
   */
  function takeRedirect() {
    const params = new URLSearchParams(window.location.search)
    const ref_ = params.get('checkout') || (params.get('tap_id') && load('session', PENDING_KEY, null))
    if (!ref_) return false

    params.delete('checkout')
    params.delete('tap_id')
    const query = params.toString()
    window.history.replaceState(
      window.history.state,
      '',
      `${window.location.pathname}${query ? `?${query}` : ''}${window.location.hash}`,
    )
    reference.value = ref_
    return true
  }

  function recheck() {
    return confirm(2)
  }

  /** The customer backed out of Tap's page without paying — send them back to it. */
  function continuePayment() {
    if (result.value?.payment_url) window.location.assign(result.value.payment_url)
  }

  /** Back to the details form, forgetting a finished or abandoned checkout. */
  function resetCheckout() {
    step.value = 'details'
    error.value = ''
    result.value = null
    submitting.value = false
  }

  function openDrawer(type) {
    drawer.value = type
  }
  function closeDrawer() {
    drawer.value = null
  }

  // Browser Back from Tap's page can restore this page from the bfcache with the
  // "taking you to payment" state still up; put the form back instead.
  window.addEventListener('pageshow', (e) => {
    if (e.persisted && step.value === 'redirecting') resetCheckout()
  })

  return {
    lines,
    drawer,
    fulfilment,
    branchId,
    step,
    config,
    customer,
    submitting,
    error,
    result,
    count,
    total,
    add,
    setQty,
    remove,
    loadConfig,
    pay,
    confirm,
    takeRedirect,
    recheck,
    continuePayment,
    resetCheckout,
    openDrawer,
    closeDrawer,
  }
})
