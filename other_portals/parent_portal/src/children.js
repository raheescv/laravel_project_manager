import { reactive, watch } from 'vue'

import { fetchStudents } from '@/api/parent'
import { session } from '@/session'

/**
 * The parent's children, shared by the home page and the desktop wallet, so a
 * balance read on one screen shows on the other. Emptied when the parent signs out.
 */
export const children = reactive({
  list: [],
  status: 'idle', // idle · loading · ready · error
  error: '',
})

let pending = null

export function loadChildren() {
  if (pending) return pending
  if (!children.list.length) children.status = 'loading'
  const token = session.token
  pending = fetchStudents()
    .then((list) => {
      if (token === session.token) Object.assign(children, { list, status: 'ready', error: '' })
    })
    .catch((e) => {
      if (token !== session.token) return
      children.error = e.message
      if (!children.list.length) children.status = 'error'
    })
    .finally(() => {
      pending = null
    })
  return pending
}

/** A page fetched one child afresh (balance, card): update that child in the list too. */
export function refreshChild(student) {
  const index = children.list.findIndex((child) => child.account_id === student?.account_id)
  if (index === -1) return
  const current = children.list[index]
  children.list[index] = Object.fromEntries(Object.keys(current).map((key) => [key, key in student ? student[key] : current[key]]))
}

watch(
  () => session.token,
  () => Object.assign(children, { list: [], status: 'idle', error: '' }),
)
