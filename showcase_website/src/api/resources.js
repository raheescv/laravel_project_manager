import client from './client'

/**
 * GET /products — paginated list.
 * Returns { data: Product[], pagination: {...}, filters_applied: {...} }
 */
export function fetchProducts(params = {}) {
  return client.get('/products', { params: prune(params) })
}

/** GET /products/{id} — full detail (images, images360, inventories, related_sizes…). */
export function fetchProduct(id) {
  return client.get(`/products/${id}`)
}

/**
 * GET /brands?size= — [{ id, name, image_path, product_count }]
 * Counts are already scoped to in-stock products (and to `size` when given);
 * brands with nothing behind them are dropped server-side.
 */
export function fetchBrands(params = {}) {
  return client.get('/brands', { params: prune(params) })
}

/**
 * GET /sizes — backend returns { young_sizes: [...], adult_sizes: [...] } where
 * each entry is { size, stock_total, in_stock } (older shapes: kids_sizes /
 * other_sizes, or a flat array). Normalised to { adult: [], young: [] }.
 */
export async function fetchSizes(params = {}) {
  const data = await client.get('/sizes', { params: prune(params) })
  const norm = (list) =>
    (Array.isArray(list) ? list : [])
      .filter((s) => s && s.size !== null && s.size !== undefined && String(s.size) !== '')
      .map((s) => ({
        size: String(s.size),
        stock_total: Number(s.stock_total) || 0,
        in_stock: s.in_stock === undefined ? true : Boolean(s.in_stock),
      }))
  if (Array.isArray(data)) return { adult: norm(data), young: [] }
  return {
    adult: norm(data?.adult_sizes || data?.other_sizes),
    young: norm(data?.young_sizes || data?.kids_sizes),
  }
}

/** GET /branches — [{ id, name, code, location, mobile }] (showcase-visible shops only). */
export function fetchBranches(params = {}) {
  return client.get('/branches', { params: prune(params) })
}

/**
 * GET /settings/branding — { primary_color, logo, company: { name, mobile, email } }
 * configured in the admin (Settings → Storefront / Company Profile).
 */
export function fetchBranding() {
  return client.get('/settings/branding')
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
 * customerMobile, address, items: [{ productId, quantity }], returnUrl }.
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
