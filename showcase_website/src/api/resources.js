import client from './client'

/**
 * GET /products — paginated list.
 * Returns { data: Product[], pagination: {...}, filters_applied: {...} }
 */
export function fetchProducts(params = {}) {
  return client.get('/products', { params: prune(params) })
}

/** GET /products/{id} — full detail (images360, related_sizes…). */
export function fetchProduct(id) {
  return client.get(`/products/${id}`)
}

/** GET /products/single?barcode= — full detail by barcode. */
export function fetchProductByBarcode(barcode) {
  return client.get('/products/single', { params: { barcode } })
}

/** GET /categories — [{ id, name, product_count }] */
export function fetchCategories() {
  return client.get('/categories')
}

/** GET /brands — [{ id, name, product_count }] */
export function fetchBrands(params = {}) {
  return client.get('/brands', { params: prune(params) })
}

/**
 * GET /sizes?code= — backend returns { young_sizes: [{size}], adult_sizes: [{size}] }
 * (older shapes: { kids_sizes, other_sizes } or a flat array). Normalized to
 * [{ size, group }] where group is 'young' | 'adult'.
 */
export async function fetchSizes(params = {}) {
  const data = await client.get('/sizes', { params: prune(params) })
  if (Array.isArray(data)) return data.map((s) => ({ ...s, group: 'adult' }))
  const young = data?.young_sizes || data?.kids_sizes || []
  const adult = data?.adult_sizes || data?.other_sizes || []
  return [
    ...young.map((s) => ({ ...s, group: 'young' })),
    ...adult.map((s) => ({ ...s, group: 'adult' })),
  ]
}

/** GET /colors?code= — [{ color, product_count }] */
export function fetchColors(params = {}) {
  return client.get('/colors', { params: prune(params) })
}

/** GET /branches?query= — [{ id, name, code, location, mobile }] */
export function fetchBranches(params = {}) {
  return client.get('/branches', { params: prune(params) })
}

/**
 * GET /settings/branding — storefront branding configured in the admin
 * (Settings → Storefront). Returns { primary_color } as a hex string. The
 * accent color drives the whole theme via CSS variables (see branding.js).
 */
export function fetchBranding() {
  return client.get('/settings/branding')
}

/** Drop null / undefined / '' params so URLs stay clean. */
function prune(params) {
  return Object.fromEntries(
    Object.entries(params).filter(([, v]) => v !== null && v !== undefined && v !== ''),
  )
}
