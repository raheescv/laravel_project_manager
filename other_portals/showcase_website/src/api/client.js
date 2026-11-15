import axios from 'axios'

const baseURL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api/v1'
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
}

const client = axios.create({
  baseURL,
  timeout: 30000,
  headers: {
    Accept: 'application/json',
    ...(tenant ? { 'X-Tenant-Subdomain': tenant } : {}),
  },
})

// Belt-and-braces tenant hint: also append ?tenant= for IP/localhost hosts
// where the backend falls back to the query param.
client.interceptors.request.use((config) => {
  if (tenant) {
    config.params = { tenant, ...(config.params || {}) }
  }
  return config
})

// Unwrap the { success, data, message } envelope so callers get `data` directly.
client.interceptors.response.use(
  (response) => {
    const body = response.data
    if (body && typeof body === 'object' && 'success' in body) {
      if (!body.success) {
        throw new ApiError(body.message || 'Request failed', response.status, body.data)
      }
      return body.data
    }
    return body
  },
  (error) => {
    const res = error.response
    let message
    if (!res) {
      message = 'Cannot reach the store API. Check your connection and try again.'
    } else if (res.status === 404 && !res.data?.success && /tenant/i.test(res.data?.message || '')) {
      message = 'Store not found — the tenant could not be identified. Check VITE_TENANT.'
    } else {
      message = res.data?.message || `Request failed (${res.status})`
    }
    throw new ApiError(message, res?.status ?? 0, res?.data?.data ?? null)
  },
)

/** Resolve a possibly-relative image path against the API origin. */
export function resolveImage(path) {
  if (!path) return null
  if (/^(https?:)?\/\//.test(path) || path.startsWith('data:')) return path
  return `${apiOrigin}/${String(path).replace(/^\/+/, '')}`
}

export default client
