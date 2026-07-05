import { ref } from 'vue'

import { fetchBranding } from '@/api/resources'

/**
 * System logo URL (Settings → General upload), or null to keep the initial-
 * letter monogram. Reactive because branding loads in parallel with mount.
 */
export const brandLogo = ref(null)

/** Parse a #rrggbb / #rgb string to [r, g, b] (0–255), or null if invalid. */
function hexToRgb(hex) {
  if (typeof hex !== 'string') return null
  let h = hex.trim().replace(/^#/, '')
  if (h.length === 3) h = h.split('').map((c) => c + c).join('')
  if (!/^[0-9a-f]{6}$/i.test(h)) return null
  return [parseInt(h.slice(0, 2), 16), parseInt(h.slice(2, 4), 16), parseInt(h.slice(4, 6), 16)]
}

const clamp = (n) => Math.max(0, Math.min(255, Math.round(n)))
const toHex = ([r, g, b]) => '#' + [r, g, b].map((c) => clamp(c).toString(16).padStart(2, '0')).join('')

/** Mix a color toward white (amt > 0) or black (amt < 0), amt in [-1, 1]. */
function shade([r, g, b], amt) {
  const target = amt >= 0 ? 255 : 0
  const t = Math.abs(amt)
  return [r + (target - r) * t, g + (target - g) * t, b + (target - b) * t]
}

/**
 * Push a primary accent color into the document as the --gold* CSS variables
 * the theme is built on. Derives the lighter/darker variants and the rgb
 * triples used by rgba() color mixes.
 */
export function applyPrimaryColor(hex) {
  const base = hexToRgb(hex)
  if (!base) return

  const bright = shade(base, 0.22)
  const deep = shade(base, -0.28)
  const rgb = (c) => c.map(clamp).join(', ')
  const root = document.documentElement.style

  root.setProperty('--gold', toHex(base))
  root.setProperty('--gold-rgb', rgb(base))
  root.setProperty('--gold-bright', toHex(bright))
  root.setProperty('--gold-bright-rgb', rgb(bright))
  root.setProperty('--gold-deep', toHex(deep))
  root.setProperty('--gold-deep-rgb', rgb(deep))
}

/**
 * Load branding from the API and apply it. Best-effort: on any failure the
 * static defaults in main.css (the SIZE RUN blue) stay in place.
 */
export async function loadBranding() {
  try {
    const data = await fetchBranding()
    if (data?.primary_color) applyPrimaryColor(data.primary_color)
    if (data?.logo) brandLogo.value = data.logo
  } catch {
    /* keep CSS defaults */
  }
}
