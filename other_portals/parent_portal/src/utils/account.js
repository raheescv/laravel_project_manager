import { logout } from '@/api/parent'
import { session } from '@/session'

/** Sign out on the server (best effort) and on this device, then back to sign in. */
export async function signOut(router) {
  try {
    await logout()
  } catch {
    // Signed out on this device either way.
  }
  session.end()
  router.replace({ name: 'login' })
}
