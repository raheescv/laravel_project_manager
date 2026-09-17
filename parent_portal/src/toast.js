import { reactive } from 'vue'

/** Short confirmations ("Card blocked") shown at the top for a few seconds. */
export const toasts = reactive([])

let next = 0

export function toast(message, tone = 'pos') {
  const id = ++next
  toasts.push({ id, message, tone })
  setTimeout(() => {
    const index = toasts.findIndex((item) => item.id === id)
    if (index >= 0) toasts.splice(index, 1)
  }, 3600)
}
