import { reactive } from 'vue'

/**
 * Light or dark. The portal follows the device's own setting until the parent
 * picks a side with the header switch; that choice is remembered per device, and
 * landing back on what the device says hands the portal back to it.
 */
const KEY = 'pp.theme'
const query = window.matchMedia('(prefers-color-scheme:dark)')

function saved() {
  try {
    const value = localStorage.getItem(KEY)
    return value === 'light' || value === 'dark' ? value : 'system'
  } catch {
    return 'system'
  }
}

export const theme = reactive({
  mode: saved(), // 'system' | 'light' | 'dark'
  resolved: 'light', // what is actually painted
})

/** What is painted right now — read from the device, never from a cached value. */
function resolve() {
  return theme.mode === 'system' ? (query.matches ? 'dark' : 'light') : theme.mode
}

function apply() {
  theme.resolved = resolve()
  // No attribute on 'system': app.css then falls back to prefers-color-scheme.
  if (theme.mode === 'system') document.documentElement.removeAttribute('data-theme')
  else document.documentElement.setAttribute('data-theme', theme.mode)
}

export function setTheme(mode) {
  theme.mode = mode
  try {
    if (mode === 'system') localStorage.removeItem(KEY)
    else localStorage.setItem(KEY, mode)
  } catch {
    // ignore
  }
  apply()
}

/** The header switch: flip to the other side, or back to the device's setting when that is where we land. */
export function toggleTheme() {
  const next = resolve() === 'dark' ? 'light' : 'dark'
  setTheme(query.matches === (next === 'dark') ? 'system' : next)
}

query.addEventListener('change', () => {
  if (theme.mode === 'system') apply()
})

apply()
