import client from './client'

/**
 * GET /categories — online-visible main categories that hold products:
 * [{ id, name, image_url, product_count }]. These are the collections
 * (Men, Women, Kids, Gift Boxes …).
 */
export function fetchCategories(params = {}) {
  return client.get('/categories', { params: prune(params) })
}

/**
 * GET /products — paginated list.
 * Returns { data: Product[], pagination: {...}, filters_applied: {...} }
 */
export function fetchProducts(params = {}) {
  return client.get('/products', { params: prune(params) })
}

/** GET /products/{id|slug} — full detail (images, inventories, family …). */
export function fetchProduct(id) {
  return client.get(`/products/${id}`)
}

/** GET /branches — [{ id, name, code, location, mobile }] (showcase-visible shops only). */
export function fetchBranches(params = {}) {
  return client.get('/branches', { params: prune(params) })
}

/**
 * GET /storefront/checkout/config — { enabled, delivery, test_mode }
 * `enabled` stays false until the admin completes Settings → Online Payments.
 */
export function fetchCheckoutConfig() {
  return client.get('/storefront/checkout/config')
}

/**
 * POST /storefront/checkout — prices the bag server-side and opens a Tap charge.
 * Body: { fulfilment, branchId, customerName, customerEmail, countryCode,
 * customerMobile, zoneNumber, streetNumber, buildingNumber, city, items: [{ productId, quantity }], returnUrl }.
 * Returns the checkout; send the customer to its `payment_url`.
 */
export function startCheckout(payload) {
  return client.post('/storefront/checkout', payload)
}

/**
 * GET /storefront/checkout/{reference} — re-checks the charge with Tap (recording
 * the sale once it is paid) and returns { reference, status: pending|paid|failed|review,
 * gateway_status, fulfilment, amount, currency, payment_url, invoice_no, branch }.
 */
export function fetchCheckout(reference) {
  return client.get(`/storefront/checkout/${encodeURIComponent(reference)}`)
}

/** Drop null / undefined / '' params so URLs stay clean. */
function prune(params) {
  return Object.fromEntries(
    Object.entries(params).filter(([, v]) => v !== null && v !== undefined && v !== ''),
  )
}
