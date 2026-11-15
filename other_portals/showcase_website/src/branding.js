import { reactive } from 'vue'

import { fetchBranding } from '@/api/resources'

export const storeName = import.meta.env.VITE_STORE_NAME || 'SIZE RUN'

/** The store's own logo, shipped with the site: square mark for the header, full lock-up for the footer. */
export const storeLogo = { mark: './logo-mark.png', full: './logo.png' }

/**
 * Admin-configured branding (Settings → Storefront / Company Profile).
 * `logo` is the tenant upload; the bundled store logo takes precedence.
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
  tintThemeColor(toHex(base))
}

/** Re-ink the browser chrome in the store's accent (the tab icon is the store logo). */
function tintThemeColor(hex) {
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
