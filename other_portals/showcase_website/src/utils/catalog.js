import { resolveImage } from '@/api/client'
import { t } from '@/i18n'

/** Stock badge for a quantity: sold out / last pair / only n left / in stock. */
export function stockPill(n) {
  const qty = Number(n) || 0
  if (qty <= 0) return { cls: 'pill--out', text: t('soldOut') }
  if (qty === 1) return { cls: 'pill--low', text: t('lastOne') }
  if (qty <= 5) return { cls: 'pill--low', text: t('lowStock', { n: qty }) }
  return { cls: 'pill--ok', text: t('inStock') }
}

/**
 * What the online store sells a product at. `online_price` is set by the API
 * only while a Product Offer runs below MRP; `was` is then the struck-out MRP.
 * Display only — checkout re-prices every line on the server.
 */
export function priceOf(p) {
  const mrp = Number(p?.mrp) || 0
  const online = Number(p?.online_price)
  if (online > 0 && online < mrp) {
    const percent = Number(p.online_discount_percent) || Math.round((1 - online / mrp) * 100)
    return { now: online, was: mrp, percent }
  }
  return { now: mrp, was: null, percent: null }
}

/** "36.5" / "41.5" style half sizes get the shorter ruler tick. */
export function isHalfSize(size) {
  return /\.\d+$/.test(String(size))
}

const collator = new Intl.Collator('en', { numeric: true, sensitivity: 'base' })

/**
 * Sort sizes the way a size run reads: numbers ascending (36, 36.5, 37 …),
 * then letter sizes (S, M, L …) after them. `pick` extracts the size string.
 */
export function sortSizes(list, pick = (x) => x) {
  const numeric = (v) => /^\d+(\.\d+)?$/.test(String(v).trim())
  return [...list].sort((a, b) => {
    const sa = String(pick(a)).trim()
    const sb = String(pick(b)).trim()
    const na = numeric(sa)
    const nb = numeric(sb)
    if (na && nb) return parseFloat(sa) - parseFloat(sb)
    if (na !== nb) return na ? -1 : 1
    return collator.compare(sa, sb)
  })
}

const LETTER_SIZES = ['XXXS', 'XXS', 'XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'XXXXL']

/** XS < S < M < L < XL …; "2XL"/"3XL" read as XXL/XXXL. Unknown letters return -1. */
function letterRank(size) {
  const s = String(size).trim().toUpperCase().replace(/^(\d)XL$/, (_, n) => 'X'.repeat(Number(n)) + 'L')
  return LETTER_SIZES.indexOf(s)
}

/**
 * The size ruler's order: largest number first (50, 49.5, 49 … 16), then
 * letter sizes from large to small (XXL, XL, L, M, S, XS), then anything else.
 */
export function sortSizesDescending(list, pick = (x) => x) {
  const numeric = (v) => /^\d+(\.\d+)?$/.test(v)
  const group = (v) => (numeric(v) ? 0 : letterRank(v) >= 0 ? 1 : 2)
  return [...list].sort((a, b) => {
    const sa = String(pick(a)).trim()
    const sb = String(pick(b)).trim()
    const ga = group(sa)
    const gb = group(sb)
    if (ga !== gb) return ga - gb
    if (ga === 0) return parseFloat(sb) - parseFloat(sa)
    if (ga === 1) return letterRank(sb) - letterRank(sa)
    return collator.compare(sa, sb)
  })
}

/** Text mark for a brand without a logo: initials for multi-word names, else the first letters. */
export function brandMark(name) {
  const words = String(name || '')
    .trim()
    .split(/\s+/)
    .filter(Boolean)
  if (!words.length) return '?'
  if (words.length > 1) return words.map((w) => w[0]).join('').slice(0, 3).toUpperCase()
  return words[0].slice(0, 4).toUpperCase()
}

export function initialOf(name) {
  return (String(name || '?').trim().charAt(0) || '?').toUpperCase()
}

/** Card photo — explicit thumbnail, else the first product image. */
export function productImage(p) {
  if (!p) return null
  return resolveImage(p.thumbnail) || resolveImage(p.images?.[0]?.url) || null
}

/**
 * Route to a product page by name: #/product/nike-air-max-90 (a name that can't make
 * a slug, e.g. Arabic, goes in as itself). The URL never carries an id; the exact row
 * (this size) rides along in history state so a click still opens that size.
 */
export function productRoute(p) {
  const slug = p.slug || p.name
  return { name: 'product', params: { slug }, state: { productId: p.id, productSlug: slug } }
}

/** What to ask the API for on a product page: the clicked row when known, else the slug. */
export function productReference(slug) {
  const state = window.history.state
  return state?.productSlug === slug && state?.productId ? state.productId : slug
}

/** What the catalogue URL carries for a brand (?brand=new-balance): its slug, else its name. */
export function brandLink(b) {
  return b?.slug || b?.name || ''
}

/**
 * An old store link ("/collections/onitsuka-tiger", "new-balance-530") as the
 * catalogue's own filters: the brand whose name is inside the handle (the
 * longest one wins — "Tiger" in "onitsuka-tiger"), and the rest as a search
 * when it names a model ("530", "1 low"). No brand inside → the handle's words
 * become the search.
 * Handles that mean "everything" give no filter at all.
 *
 * @param {string} handle
 * @param {Array<{name?: string, slug?: string}>} brands
 * @returns {{ brand: string|null, brandName: string, q: string }}
 */
export function collectionFilters(handle, brands) {
  const words = (s) => String(s || '').toLowerCase().split(/[^a-z0-9]+/).filter(Boolean)
  const wanted = words(decodeURIComponent(String(handle || '')))
  const none = { brand: null, brandName: '', q: '' }
  if (!wanted.length || /^(all|frontpage|shop|catalog|catalogue|new|new-arrivals?|sale|sneakers|shoes)$/.test(wanted.join('-'))) {
    return none
  }

  let best = null
  for (const b of brands || []) {
    const name = words(b?.name)
    if (!name.length || !name.every((w) => wanted.includes(w))) continue
    if (!best || name.length > best.words.length) best = { brand: b, words: name }
  }
  if (!best) return { ...none, q: wanted.join(' ') }

  const rest = wanted.filter((w) => !best.words.includes(w))
  const model = rest.some((w) => /\d/.test(w)) ? rest.join(' ') : ''
  return { brand: brandLink(best.brand), brandName: best.brand.name || '', q: model }
}
