import { reactive } from 'vue'

export const toastState = reactive({ message: '', on: false })

let timer = null

/** Bottom-centre status toast, auto-hides after 3.2s. */
export function toast(message) {
  toastState.message = message
  toastState.on = true
  clearTimeout(timer)
  timer = setTimeout(() => {
    toastState.on = false
  }, 3200)
}
