import { reactive } from 'vue'

/**
 * The parent's sign-in, kept on this device.
 *
 * "Keep me signed in" → localStorage (survives closing the browser, 30-day token);
 * otherwise sessionStorage (gone with the tab, 12-hour token). The API decides
 * when a token really ends — a 401 calls expire().
 */
const KEY = 'pp.session'

function read() {
  for (const store of [localStorage, sessionStorage]) {
    try {
      const saved = JSON.parse(store.getItem(KEY) || 'null')
      if (saved?.token && (!saved.expires_at || new Date(saved.expires_at) > new Date())) return saved
    } catch {
      // Storage blocked (private mode) or corrupt — treat as signed out.
    }
  }
  return null
}

function clearStores() {
  for (const store of [localStorage, sessionStorage]) {
    try {
      store.removeItem(KEY)
    } catch {
      // ignore
    }
  }
}

const saved = read()

export const session = reactive({
  token: saved?.token || null,
  expiresAt: saved?.expires_at || null,
  parent: saved?.parent || null,
  /** Set when the API refused the token, so the sign-in page can say why. */
  expired: false,

  get signedIn() {
    return Boolean(this.token)
  },

  /** Store a login / set-password response. */
  start({ token, expires_at, parent }, remember = true) {
    clearStores()
    const value = JSON.stringify({ token, expires_at, parent })
    try {
      ;(remember ? localStorage : sessionStorage).setItem(KEY, value)
    } catch {
      // Private mode: stays signed in for this page only.
    }
    Object.assign(this, { token, expiresAt: expires_at, parent, expired: false })
  },

  setParent(parent) {
    this.parent = parent
    for (const store of [localStorage, sessionStorage]) {
      try {
        const current = JSON.parse(store.getItem(KEY) || 'null')
        if (current?.token === this.token) store.setItem(KEY, JSON.stringify({ ...current, parent }))
      } catch {
        // ignore
      }
    }
  },

  end() {
    clearStores()
    Object.assign(this, { token: null, expiresAt: null, parent: null })
  },

  expire() {
    this.end()
    this.expired = true
  },
})
