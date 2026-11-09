import axios from 'axios'

const http = axios.create({
    baseURL: '/sale/coupon/api',
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
        Accept: 'application/json',
    },
})

const dataset = document.getElementById('coupon-console')?.dataset ?? {}

/** What the server told the page: { permissions, lookups, currency }. */
export const consoleConfig = {
    permissions: JSON.parse(dataset.permissions || '{}'),
    lookups: JSON.parse(dataset.lookups || '{}'),
    currency: dataset.currency || '',
}

export const money = (value) => `${consoleConfig.currency} ${Number(value || 0).toLocaleString('en-US', { maximumFractionDigits: 2 })}`.trim()

export const formatDay = (date) => new Date(`${date}T00:00`).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })

export const couponApi = {
    list: (params, signal) => http.get('', { params, signal }).then((response) => response.data.data),
    store: (payload) => http.post('', payload).then((response) => response.data),
    update: (id, payload) => http.put(`${id}`, payload).then((response) => response.data),
    toggle: (id, isActive) => http.patch(`${id}/toggle`, { is_active: isActive }).then((response) => response.data),
    destroy: (id) => http.delete(`${id}`).then((response) => response.data),
    lookup: (url, query, signal) => axios.get(url, { params: { query }, signal }).then((response) => response.data.items),
}
