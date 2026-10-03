export const STATUSES = [
    { key: 'open', label: 'Open', icon: 'fa-inbox', color: '#2a9fd6' },
    { key: 'in_progress', label: 'In Progress', icon: 'fa-spinner', color: '#d99a17' },
    { key: 'resolved', label: 'Resolved', icon: 'fa-check-circle', color: '#1f9d63' },
    { key: 'closed', label: 'Closed', icon: 'fa-archive', color: '#7b8494' },
]

export const statusOf = (key) => STATUSES.find((s) => s.key === key) ?? STATUSES[0]

/** Filter value for tickets without a group — mirrors Ticket::NO_GROUP. */
export const NO_GROUP = '__none'

const PALETTE = ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899', '#ef4444', '#14b8a6', '#6366f1', '#84cc16', '#f97316']

/** Groups are free text, so the colour is derived from the name: the same group is always the same colour. */
export function groupColor(name) {
    if (!name) return '#94a3b8'
    let hash = 0
    for (const char of name.toLowerCase()) hash = (hash * 31 + char.charCodeAt(0)) >>> 0
    return PALETTE[hash % PALETTE.length]
}

export function initials(name) {
    return (name || '?').split(/\s+/).filter(Boolean).slice(0, 2).map((p) => p[0].toUpperCase()).join('')
}

export function relativeTime(iso) {
    if (!iso) return ''
    const seconds = Math.round((Date.now() - new Date(iso).getTime()) / 1000)
    if (seconds < 60) return 'just now'
    const units = [['y', 31536000], ['mo', 2592000], ['d', 86400], ['h', 3600], ['m', 60]]
    for (const [unit, size] of units) {
        if (seconds >= size) return `${Math.floor(seconds / size)}${unit} ago`
    }
    return 'just now'
}

export function formatDate(iso, withTime = false) {
    if (!iso) return ''
    return new Date(iso).toLocaleString('en-GB', {
        day: '2-digit', month: 'short', year: 'numeric',
        ...(withTime ? { hour: '2-digit', minute: '2-digit' } : {}),
    })
}

export function formatSize(bytes) {
    if (!bytes) return ''
    if (bytes < 1024) return `${bytes} B`
    if (bytes < 1048576) return `${(bytes / 1024).toFixed(0)} KB`
    return `${(bytes / 1048576).toFixed(1)} MB`
}

const URL_CANDIDATE = /\b(?:https?:\/\/|www\.)[^\s<>"']+/gi

/**
 * Split free text into plain and link segments. A candidate only becomes a link
 * if it parses as an http(s) URL; trailing punctuation stays as text.
 * Rendered as text nodes and <a> elements, never as HTML.
 *
 * @returns {Array<{ text: string, href?: string, external?: boolean }>}
 */
export function linkify(text) {
    const value = text ?? ''
    const parts = []
    let last = 0
    for (const match of value.matchAll(URL_CANDIDATE)) {
        const raw = match[0].replace(/[.,;:!?)\]}]+$/, '')
        const href = safeHref(raw)
        if (!href) continue
        if (match.index > last) parts.push({ text: value.slice(last, match.index) })
        parts.push({ text: raw, href, external: new URL(href).origin !== location.origin })
        last = match.index + raw.length
    }
    if (last < value.length) parts.push({ text: value.slice(last) })
    return parts
}

function safeHref(raw) {
    try {
        const url = new URL(raw.startsWith('www.') ? `https://${raw}` : raw)
        return ['http:', 'https:'].includes(url.protocol) && url.hostname ? url.href : null
    } catch {
        return null
    }
}
