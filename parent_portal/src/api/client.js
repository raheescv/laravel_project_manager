import axios from 'axios'

import { session } from '@/session'

const baseURL = import.meta.env.VITE_API_BASE_URL || '/api/v1'
const tenant = import.meta.env.VITE_TENANT || ''

/** Origin of the API host — used to resolve relative image paths. */
export const apiOrigin = new URL(baseURL, window.location.origin).origin

export class ApiError extends Error {
  constructor(message, status = 0, errors = null) {
    super(message)
    this.name = 'ApiError'
    this.status = status
    this.errors = errors
  }

  /** First message for a field, e.g. error.field('login'). */
  field(name) {
    const value = this.errors?.[name]
    return Array.isArray(value) ? value[0] : value || null
  }
}

const client = axios.create({
  baseURL: `${baseURL.replace(/\/+$/, '')}/parent`,
  timeout: 30000,
  headers: {
    Accept: 'application/json',
    ...(tenant ? { 'X-Tenant-Subdomain': tenant } : {}),
  },
})

client.interceptors.request.use((config) => {
  // Tenant hint also as ?tenant= for IP / localhost hosts, where the backend
  // falls back to the query param.
  if (tenant) config.params = { tenant, ...(config.params || {}) }
  if (session.token) config.headers.Authorization = `Bearer ${session.token}`
  return config
})

// Unwrap the { success, data, message } envelope so callers get `data` directly.
client.interceptors.response.use(
  (response) => {
    const body = response.data
    if (body && typeof body === 'object' && 'success' in body) {
      if (!body.success) throw new ApiError(body.message || 'Something went wrong.', response.status, body.data)
      return body.data
    }
    return body
  },
  (error) => {
    const res = error.response
    let message
    if (!res) {
      message = "We can't reach the school right now. Check your connection and try again."
    } else if (res.status === 404 && /tenant/i.test(res.data?.message || '')) {
      message = 'This portal is not connected to a school. Please contact the school office.'
    } else if (res.status === 429 && !res.data?.errors && !res.data?.data) {
      message = 'Too many tries. Please wait a minute and try again.'
    } else {
      message = res.data?.message || `Something went wrong (${res.status}).`
    }

    // A token the API no longer accepts (expired, signed out elsewhere, parent
    // disabled): drop it so the router sends the parent to sign in.
    if (res?.status === 401 && session.token) session.expire()

    throw new ApiError(message, res?.status ?? 0, res?.data?.errors ?? res?.data?.data ?? null)
  },
)

/** Resolve a possibly-relative image path against the API origin. */
export function resolveImage(path) {
  if (!path) return null
  if (/^(https?:)?\/\//.test(path) || path.startsWith('data:')) return path
  return `${apiOrigin}/${String(path).replace(/^\/+/, '')}`
}

export default client
