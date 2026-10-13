const DAY = 86400000

/** 120000 → "120,000.00" */
export function money(value, digits = 2) {
    const number = Number(value ?? 0)
    return number.toLocaleString('en-US', { minimumFractionDigits: digits, maximumFractionDigits: digits })
}

/** 120000 → "120,000" */
export function whole(value) {
    return money(Math.round(Number(value ?? 0)), 0)
}

/** "2026-09-01" → a local Date at midnight, or null. */
export function toDate(value) {
    if (!value) return null
    const [year, month, day] = String(value).slice(0, 10).split('-').map(Number)
    return year ? new Date(year, month - 1, day) : null
}

/** "2026-09-01" → "01-09-2026", the app's date style. */
export function date(value) {
    const parsed = toDate(value)
    if (!parsed) return '—'
    return `${String(parsed.getDate()).padStart(2, '0')}-${String(parsed.getMonth() + 1).padStart(2, '0')}-${parsed.getFullYear()}`
}

/** "2026-09-01 10:12:00" → "01-09-2026, 10:12" */
export function dateTime(value) {
    if (!value) return '—'
    const time = String(value).slice(11, 16)
    return time ? `${date(value)}, ${time}` : date(value)
}

export function isoDate(value) {
    return `${value.getFullYear()}-${String(value.getMonth() + 1).padStart(2, '0')}-${String(value.getDate()).padStart(2, '0')}`
}

export function today() {
    return isoDate(new Date())
}

/** Whole days from today to the date (negative when past). */
export function daysFromToday(value) {
    const target = toDate(value)
    if (!target) return null
    const now = toDate(today())
    return Math.round((target - now) / DAY)
}

/** "in 10 days" · "25 days overdue" · "today" */
export function dueText(value) {
    const days = daysFromToday(value)
    if (days === null) return ''
    if (days === 0) return 'due today'
    return days > 0 ? `in ${days} ${days === 1 ? 'day' : 'days'}` : `${-days} ${days === -1 ? 'day' : 'days'} overdue`
}

/** Months between two dates counting the end date as included (01-09 → 31-08 is 12). */
export function contractMonths(start, end) {
    const from = toDate(start)
    const to = toDate(end)
    if (!from || !to || to <= from) return 0
    const after = new Date(to)
    after.setDate(after.getDate() + 1)
    return Math.max(0, (after.getFullYear() - from.getFullYear()) * 12 + after.getMonth() - from.getMonth() + (after.getDate() < from.getDate() ? -1 : 0))
}

export function daysBetween(start, end) {
    const from = toDate(start)
    const to = toDate(end)
    return from && to ? Math.round((to - from) / DAY) : 0
}

/** Add months to a date and step back a day: the end of a period that starts on `start`. */
export function periodEnd(start, months) {
    const from = toDate(start)
    if (!from) return ''
    return isoDate(new Date(from.getFullYear(), from.getMonth() + months, from.getDate() - 1))
}

export function percent(part, total, digits = 1) {
    if (!total) return 0
    return Number(((Number(part) / Number(total)) * 100).toFixed(digits))
}

export function initials(name) {
    return String(name ?? '')
        .replace(/[^\p{L}\p{N} ]/gu, ' ')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0].toUpperCase())
        .join('') || '?'
}

export function fileSize(bytes) {
    if (!bytes) return '0 KB'
    if (bytes < 1024 * 1024) return `${Math.max(1, Math.round(bytes / 1024))} KB`
    return `${(bytes / 1024 / 1024).toFixed(1)} MB`
}

/** Parse "1,200.50" typed into an amount box. */
export function parseAmount(value) {
    const number = parseFloat(String(value ?? '').replace(/,/g, ''))
    return Number.isFinite(number) ? number : 0
}

export const statusTone = {
    draft: 'draft',
    pending_approval: 'partial',
    active: 'active',
    rejected: 'overdue',
    completed: 'completed',
    cancelled: 'pending',
}
