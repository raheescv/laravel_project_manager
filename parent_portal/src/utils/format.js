import { school } from '@/school'

const numberFormats = new Map()

function numberFormat(decimals) {
  if (!numberFormats.has(decimals)) {
    numberFormats.set(decimals, new Intl.NumberFormat('en-US', { minimumFractionDigits: decimals, maximumFractionDigits: decimals }))
  }
  return numberFormats.get(decimals)
}

/** QAR 1,250.50 · −QAR 12.00 (a real minus sign, so a negative balance reads at a glance). */
export function money(value, { sign = false } = {}) {
  const amount = Number(value) || 0
  const text = `${school.currency.code} ${numberFormat(school.currency.decimals).format(Math.abs(amount))}`
  if (amount < 0) return `−${text}`
  return sign && amount > 0 ? `+${text}` : text
}

/** Just the number: 1,250.50 */
export function amount(value) {
  return numberFormat(school.currency.decimals).format(Math.abs(Number(value) || 0))
}

// Month names spelled out here: browsers disagree on "Sep" vs "Sept".
const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
const MONTHS_LONG = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']
const timeFormat = new Intl.DateTimeFormat('en-US', { hour: 'numeric', minute: '2-digit' })
const pad = (n) => String(n).padStart(2, '0')

const WEEKDAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']

/** ISO weekday (1 = Monday … 7 = Sunday) → "Sun"; `long` → "Sunday". */
export const weekdayName = (iso, long = false) => (long ? WEEKDAYS[iso % 7] : WEEKDAYS[iso % 7].slice(0, 3))

/** ISO weekdays in school-week order, Sunday first. */
export const sortWeekdays = (days) => [...days].sort((a, b) => (a % 7) - (b % 7))

/** "Sun, Mon, Tue" — or "Sun – Thu" for a run of three or more. */
export function weekdaysLabel(days) {
  const sorted = sortWeekdays(days || [])
  const keys = sorted.map((d) => d % 7)
  const consecutive = keys.length > 2 && keys.every((k, i) => i === 0 || k === keys[i - 1] + 1)
  if (consecutive) return `${weekdayName(sorted[0])} – ${weekdayName(sorted[sorted.length - 1])}`
  return sorted.map((d) => weekdayName(d)).join(', ')
}

/** "Sunday" for a 'YYYY-MM-DD' date. */
export function dayName(value) {
  const d = toDate(value)
  return d ? WEEKDAYS[d.getDay()] : ''
}

export function isToday(value) {
  const d = toDate(value)
  const now = new Date()
  return Boolean(d) && d.getFullYear() === now.getFullYear() && d.getMonth() === now.getMonth() && d.getDate() === now.getDate()
}

/** 'YYYY-MM-DD' is a calendar date: parse it as local, never as UTC midnight. */
function toDate(value) {
  if (!value) return null
  if (value instanceof Date) return value
  const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value)
  return match ? new Date(+match[1], +match[2] - 1, +match[3]) : new Date(value)
}

/** 17 Sep 2026 */
export function date(value) {
  const d = toDate(value)
  return d ? `${d.getDate()} ${MONTHS[d.getMonth()]} ${d.getFullYear()}` : ''
}

/** Mon, 21 Sep */
export function shortDate(value) {
  const d = toDate(value)
  return d ? `${WEEKDAYS[d.getDay()].slice(0, 3)}, ${d.getDate()} ${MONTHS[d.getMonth()]}` : ''
}

/** 03 Sep */
export function dayMonth(value) {
  const d = toDate(value)
  return d ? `${pad(d.getDate())} ${MONTHS[d.getMonth()]}` : ''
}

/** { month: 'Sep', day: '03' } — the date tile on a bill row */
export function dateTile(value) {
  const d = toDate(value)
  return d ? { month: MONTHS[d.getMonth()], day: pad(d.getDate()) } : { month: '', day: '' }
}

/** 1:05 PM */
export const time = (value) => (value ? timeFormat.format(new Date(value)) : '')

/** 17 Sep 2026, 1:05 PM */
export const dateTime = (value) => (value ? `${date(value)}, ${time(value)}` : '')

/** 'YYYY-MM' for today. */
export function currentMonth() {
  const now = new Date()
  return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`
}

/** 'YYYY-MM' moved by [delta] months. */
export function shiftMonth(month, delta) {
  const [year, index] = month.split('-').map(Number)
  const next = new Date(year, index - 1 + delta, 1)
  return `${next.getFullYear()}-${String(next.getMonth() + 1).padStart(2, '0')}`
}

/** September 2026 */
export function monthLabel(month) {
  const [year, index] = month.split('-').map(Number)
  return `${MONTHS_LONG[index - 1]} ${year}`
}

export function isMonth(value) {
  return /^\d{4}-(0[1-9]|1[0-2])$/.test(value || '')
}

/** 2 → "2", 1.5 → "1.5" */
export const quantity = (value) => String(Math.round((Number(value) || 0) * 1000) / 1000)

/** "Sara Ahmed" → "SA" */
export function initials(name) {
  return String(name || '')
    .trim()
    .split(/\s+/)
    .slice(0, 2)
    .map((part) => part[0])
    .join('')
    .toUpperCase()
}

/** "Sara Ahmed" → "Sara" */
export const firstName = (name) => String(name || '').trim().split(/\s+/)[0] || ''
