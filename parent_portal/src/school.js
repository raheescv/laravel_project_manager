import { reactive } from 'vue'

import { fetchSchool } from '@/api/parent'

/**
 * The school the portal belongs to: name, logo and currency, from the API.
 * Cached per device so the next open paints instantly.
 *
 * The colours are NOT the school's: the portal wears the canteen theme (cream,
 * apricot, brick) on every tenant, so /school's `accent` is deliberately ignored
 * here. Change the palette in assets/app.css, not in the school's settings.
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
  currency: { code: 'QAR', decimals: 2 },
  contact: { mobile: null, email: null },
  loaded: false,
  error: null,
})

function apply(data) {
  Object.assign(school, {
    name: data.name || '',
    logo: data.logo || null,
    currency: { code: data.currency?.code || 'QAR', decimals: Number.isInteger(data.currency?.decimals) ? data.currency.decimals : 2 },
    contact: { mobile: data.contact?.mobile || null, email: data.contact?.email || null },
  })
  if (school.name) document.title = `${school.name} · Parent Portal`
  if (school.logo) document.querySelector('link[rel="icon"]')?.setAttribute('href', school.logo)
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
