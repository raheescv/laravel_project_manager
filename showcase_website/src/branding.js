import { reactive } from 'vue'

import { fetchBranding } from '@/api/resources'

export const storeName = import.meta.env.VITE_STORE_NAME || 'SIZE RUN'

/**
 * Admin-configured branding (Settings → Storefront / Company Profile).
 * `logo` replaces the inline SIZE RUN mark when the tenant uploaded one.
 */
export const branding = reactive({
  logo: null,
  company: { name: null, mobile: null, email: null, google_review_url: null },
})

/** Parse a #rrggbb / #rgb string to [r, g, b] (0–255), or null if invalid. */
function hexToRgb(hex) {
  if (typeof hex !== 'string') return null
  let h = hex.trim().replace(/^#/, '')
  if (h.length === 3) h = h.split('').map((c) => c + c).join('')
  if (!/^[0-9a-f]{6}$/i.test(h)) return null
  return [parseInt(h.slice(0, 2), 16), parseInt(h.slice(2, 4), 16), parseInt(h.slice(4, 6), 16)]
}

const clamp = (n) => Math.max(0, Math.min(255, Math.round(n)))
const toHex = (rgb) => '#' + rgb.map((c) => clamp(c).toString(16).padStart(2, '0')).join('')

/** Mix a colour toward white (amt > 0) or black (amt < 0), amt in [-1, 1]. */
function shade([r, g, b], amt) {
  const target = amt >= 0 ? 255 : 0
  const k = Math.abs(amt)
  return [r + (target - r) * k, g + (target - g) * k, b + (target - b) * k]
}

/**
 * Push the accent into the --blue* tokens the stylesheet is built on: the
 * size stage field, selected states, buttons and the bag count all follow it.
 */
export function applyPrimaryColor(hex) {
  const base = hexToRgb(hex)
  if (!base) return
  const root = document.documentElement.style
  root.setProperty('--blue', toHex(base))
  root.setProperty('--blue-600', toHex(shade(base, 0.14)))
  root.setProperty('--blue-900', toHex(shade(base, -0.5)))
  tintFavicon(toHex(base))
}

/** Re-ink the tab icon and browser chrome in the store's accent. */
function tintFavicon(hex) {
  const svg =
    `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><rect width="64" height="64" rx="14" fill="${hex}"/>` +
    '<rect x="10" y="10" width="44" height="44" rx="8" fill="none" stroke="#fff" stroke-width="4"/>' +
    '<path d="M32 43V21M32 21l-8 8M32 21l8 8" fill="none" stroke="#fff" stroke-width="5.5" stroke-linecap="round" stroke-linejoin="round"/></svg>'
  const icon = document.getElementById('favicon')
  if (icon) icon.href = `data:image/svg+xml,${encodeURIComponent(svg)}`
  document.querySelector('meta[name="theme-color"]')?.setAttribute('content', hex)
}

/** Load branding from the API and apply it. Best-effort — CSS defaults stay otherwise. */
export async function loadBranding() {
  try {
    const data = await fetchBranding()
    if (data?.primary_color) applyPrimaryColor(data.primary_color)
    branding.logo = data?.logo || null
    Object.assign(branding.company, data?.company || {})
  } catch {
    /* keep CSS defaults */
  }
}
