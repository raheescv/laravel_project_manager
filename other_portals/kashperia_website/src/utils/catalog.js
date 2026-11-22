import { resolveImage } from '@/api/client'
import editorialCopy from '@/content/products.json'
import editorialPhotos from '@/content/product-photos.json'
import { slugify } from '@/format'

/**
 * What the online store sells a product at. `online_price` is set by the API
 * only while a Product Offer runs below MRP; `was` is then the struck-out MRP.
 * Display only — checkout re-prices every line on the server.
 */
export function priceOf(p) {
  const mrp = Number(p?.mrp) || 0
  const online = Number(p?.online_price)
  if (online > 0 && online < mrp) {
    return { now: online, was: mrp }
  }
  return { now: mrp, was: null }
}

/** A family card's price range (group_by_family lists), else the row's own price. */
export function priceRange(p) {
  const family = p?.family
  const min = Number(family?.min_price)
  const max = Number(family?.max_price)
  if (family && min > 0 && max > min) return { from: min, to: max }
  return null
}

/** Longest editorial key contained in the product name: "Emerald Shawl – Ivory" → "emerald". */
function editorialKey(name, table) {
  const n = ` ${String(name || '').toLowerCase().replace(/[’']/g, "'")} `
  let best = null
  for (const key of Object.keys(table)) {
    if (n.includes(` ${key} `) || n.trim() === key) {
      if (!best || key.length > best.length) best = key
    }
  }
  return best
}

/** The reference's story, passport and care copy for a product, matched by name. */
export function editorialFor(p) {
  const key = editorialKey(p?.name, editorialCopy)
  return key ? editorialCopy[key] : null
}

/** The client's photographs for a product, matched by name — used until the ERP has images. */
export function editorialPhotosFor(p) {
  const key = editorialKey(p?.name, editorialPhotos)
  return key ? editorialPhotos[key] : []
}

/** Card photo — catalogue thumbnail, else first image, else the client's photograph. */
export function productImage(p) {
  if (!p) return null
  return resolveImage(p.thumbnail) || resolveImage(p.images?.[0]?.url) || editorialPhotosFor(p)[0] || null
}

/** Card line under the name: the family's colours / sizes on offer. */
export function familyLine(p) {
  const colours = p?.family?.colours?.length || 0
  const sizes = p?.family?.sizes?.length || 0
  if (colours > 1 && sizes > 1) return `${colours} colours · ${sizes} sizes`
  if (colours > 1) return `${colours} colours`
  if (sizes > 1) return `${sizes} sizes`
  return 'Explore the creation'
}

/** Route to a product page by name (#/product/emerald); the clicked row rides in history state. */
export function productRoute(p) {
  const slug = p.slug || slugify(p.name) || String(p.id)
  return { name: 'product', params: { slug }, state: { productId: p.id, productSlug: slug } }
}

/** What to ask the API for on a product page: the clicked row when known, else the slug. */
export function productReference(slug) {
  const state = window.history.state
  return state?.productSlug === slug && state?.productId ? state.productId : slug
}

/** Numbers first (54, 56 …), then letter sizes, then the rest. */
export function sortSizes(list) {
  const collator = new Intl.Collator('en', { numeric: true, sensitivity: 'base' })
  return [...list].sort((a, b) => collator.compare(String(a), String(b)))
}

/** Pointer-follow magnifier from the reference (fine pointers only). */
export function zoomMove(event) {
  if (!window.matchMedia('(hover:hover) and (pointer:fine)').matches) return
  const el = event.currentTarget
  const r = el.getBoundingClientRect()
  el.style.setProperty('--kx', (((event.clientX - r.left) / r.width) * 100).toFixed(1) + '%')
  el.style.setProperty('--ky', (((event.clientY - r.top) / r.height) * 100).toFixed(1) + '%')
  el.classList.add('k-zooming')
}

export function zoomReset(event) {
  const el = event.currentTarget
  if (!el.classList.contains('k-zoom-locked')) el.classList.remove('k-zooming')
}
