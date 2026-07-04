const currency = import.meta.env.VITE_CURRENCY || 'USD'

const formatter = new Intl.NumberFormat(undefined, {
  style: 'currency',
  currency,
  minimumFractionDigits: 0,
  maximumFractionDigits: 2,
})

export function formatPrice(value) {
  const num = Number(value)
  if (!Number.isFinite(num)) return '—'
  return formatter.format(num)
}

export const storeName = import.meta.env.VITE_STORE_NAME || 'SIZERUN'

export function initialOf(name) {
  return (name || '?').trim().charAt(0).toUpperCase()
}

/** Deterministic tint per name — brand avatars + photoless product blobs.
    Indigo-led editorial palette, matching the SIZERUN concept-store look. */
const TINTS = [
  '#141310',
  '#2f2bd6',
  '#1c4fa0',
  '#8a3b3b',
  '#b12222',
  '#d46a9f',
  '#5a56ff',
  '#4f8a3d',
  '#c9852a',
]
export function tintFor(name) {
  let hash = 0
  for (const ch of String(name || '')) hash = (hash * 31 + ch.charCodeAt(0)) & 0xffff
  return TINTS[hash % TINTS.length]
}

/** Best-effort hex for a human colour name (product.color) → used as the second
    stop of the fallback blob and as a colour swatch. Falls back to a tint. */
const COLOR_MAP = {
  black: '#141310',
  white: '#f2efe6',
  red: '#c8492f',
  blue: '#2f2bd6',
  navy: '#1c2a55',
  green: '#4f8a3d',
  grey: '#9a9384',
  gray: '#9a9384',
  brown: '#7a5d4a',
  pink: '#d46a9f',
  beige: '#d9cdb4',
  cream: '#eee6d6',
  yellow: '#e0b93a',
  orange: '#d97a2b',
  purple: '#6e5d7a',
  gold: '#c9852a',
  silver: '#c3c0b6',
}
export function colorHex(name) {
  if (!name) return null
  const k = String(name).toLowerCase().trim()
  for (const key in COLOR_MAP) if (k.includes(key)) return COLOR_MAP[key]
  return tintFor(name)
}
