import { defineStore } from 'pinia'
import { computed, ref } from 'vue'

const STORAGE_KEY = 'sr.bag'
const MAX_QTY = 9

function restore() {
  try {
    const raw = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]')
    return Array.isArray(raw) ? raw.filter((l) => l && l.key && l.qty > 0) : []
  } catch {
    return []
  }
}

/**
 * Demo bag — a reservation list that never charges anyone. Lines carry a
 * snapshot of the product (name, brand, image, price) so the drawer renders
 * without a lookup, and survive reloads via localStorage.
 */
export const useBagStore = defineStore('bag', () => {
  const lines = ref(restore())
  const open = ref(false)
  const fulfilment = ref('pickup') // pickup | delivery
  const done = ref(null) // { total } after a demo checkout

  const count = computed(() => lines.value.reduce((n, l) => n + l.qty, 0))
  const total = computed(() => lines.value.reduce((n, l) => n + l.qty * (Number(l.price) || 0), 0))

  function save() {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(lines.value))
    } catch {
      /* ignore */
    }
  }

  /** item: { id, size, price, name, name_arabic, brand, img } */
  function add(item, qty = 1) {
    const key = `${item.id}|${item.size || ''}`
    const line = lines.value.find((l) => l.key === key)
    if (line) line.qty = Math.min(MAX_QTY, line.qty + qty)
    else lines.value.push({ key, qty: Math.min(MAX_QTY, Math.max(1, qty)), ...item })
    save()
    done.value = null
    open.value = true
  }

  /** Returns 'removed' when the line hit zero, 'changed' otherwise, false if unknown. */
  function setQty(key, delta) {
    const i = lines.value.findIndex((l) => l.key === key)
    if (i < 0) return false
    const line = lines.value[i]
    line.qty = Math.min(MAX_QTY, line.qty + delta)
    let outcome = 'changed'
    if (line.qty <= 0) {
      lines.value.splice(i, 1)
      outcome = 'removed'
    }
    save()
    return outcome
  }

  function remove(key) {
    lines.value = lines.value.filter((l) => l.key !== key)
    save()
  }

  function checkout() {
    done.value = { total: total.value }
    lines.value = []
    save()
  }

  function show() {
    done.value = null
    open.value = true
  }
  function close() {
    open.value = false
  }
  function acknowledge() {
    done.value = null
  }

  return { lines, open, fulfilment, done, count, total, add, setQty, remove, checkout, show, close, acknowledge }
})
