import { reactive } from 'vue'

import { fetchSchool } from '@/api/parent'

/**
 * The school the portal belongs to: name, logo, theme colour and currency, from
 * the API. The theme colour becomes --accent, so every shade in the UI follows
 * the school's own colour. Cached per device so the next open paints instantly.
 */
const KEY = 'pp.school'

function cached() {
  try {
    return JSON.parse(localStorage.getItem(KEY) || 'null')
  } catch {
    return null
  }
}

export const school = reactive({
  name: '',
  logo: null,
  accent: '#1D4ED8',
  currency: { code: 'QAR', decimals: 2 },
  contact: { mobile: null, email: null },
  loaded: false,
  error: null,
})

function apply(data) {
  Object.assign(school, {
    name: data.name || '',
    logo: data.logo || null,
    accent: /^#[0-9a-f]{3,8}$/i.test(data.accent || '') ? data.accent : '#1D4ED8',
    currency: { code: data.currency?.code || 'QAR', decimals: Number.isInteger(data.currency?.decimals) ? data.currency.decimals : 2 },
    contact: { mobile: data.contact?.mobile || null, email: data.contact?.email || null },
  })
  document.documentElement.style.setProperty('--accent', school.accent)
  // A pale school colour (yellow, mint…) needs dark text on its buttons.
  document.documentElement.style.setProperty('--on-accent', isLight(school.accent) ? '#0B0B0F' : '#FFFFFF')
  document.querySelector('meta[name="theme-color"]')?.setAttribute('content', school.accent)
  if (school.name) document.title = `${school.name} · Parent Portal`
  if (school.logo) document.querySelector('link[rel="icon"]')?.setAttribute('href', school.logo)
}

function isLight(hex) {
  let value = hex.replace('#', '')
  if (value.length === 3) value = [...value].map((c) => c + c).join('')
  const [r, g, b] = [0, 2, 4].map((i) => parseInt(value.slice(i, i + 2), 16) / 255)
  const linear = (c) => (c <= 0.03928 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4)
  return 0.2126 * linear(r) + 0.7152 * linear(g) + 0.0722 * linear(b) > 0.45
}

export async function loadSchool() {
  const saved = cached()
  if (saved) apply(saved)

  try {
    const data = await fetchSchool()
    apply(data)
    school.loaded = true
    school.error = null
    try {
      localStorage.setItem(KEY, JSON.stringify(data))
    } catch {
      // ignore
    }
  } catch (error) {
    school.error = error.message
    school.loaded = Boolean(saved)
  }
}
