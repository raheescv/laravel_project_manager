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
