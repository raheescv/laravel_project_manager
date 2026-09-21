import { goToQPay } from '@/utils/qpay'

/**
 * Leave the portal for the payment page of the card type the parent chose:
 * a debit card goes to QPay (a signed form post), a credit card to the bank's
 * Mastercard Gateway page. Either way the gateway brings the parent back through
 * the API, which forwards to #/topups/{pun}.
 */
export function goToPayment(payment) {
  if (payment?.type === 'mpgs') return goToCardCheckout(payment)
  goToQPay(payment)
  return Promise.resolve()
}

let loadedScript = ''

/**
 * Credit card: load the gateway's Hosted Checkout library (checkout.min.js, from
 * the bank's own host) and open its payment page for the session the API opened.
 * Rejects when the library cannot load or refuses the session, so the page can
 * say so instead of spinning.
 */
export function goToCardCheckout({ script, session_id: sessionId }) {
  return new Promise((resolve, reject) => {
    const fail = (message) => reject(new Error(message || "The card payment page couldn't be opened. Please try again."))

    // Named on window: the library looks its callbacks up by the names in data-* attributes.
    window.ppCheckoutError = (error) => fail(error?.explanation || error?.error?.explanation)

    const open = () => {
      try {
        window.Checkout.configure({ session: { id: sessionId } })
        window.Checkout.showPaymentPage()
        resolve()
      } catch {
        fail()
      }
    }

    if (window.Checkout && loadedScript === script) {
      open()
      return
    }

    const tag = document.createElement('script')
    tag.src = script
    tag.async = true
    tag.dataset.error = 'ppCheckoutError'
    tag.onload = () => {
      loadedScript = script
      open()
    }
    tag.onerror = () => fail("The card payment page couldn't be reached. Please check your connection and try again.")
    document.head.appendChild(tag)
  })
}
