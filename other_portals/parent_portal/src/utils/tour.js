import { reactive } from 'vue'

import { children } from '@/children'
import { session } from '@/session'
import { TOUR_VERSION, tours } from '@/tours'
import { firstName } from '@/utils/format'
import { desktop } from '@/utils/viewport'

/**
 * The page tours (wording in src/tours.js, the card in components/TourGuide.vue).
 *
 * A tour plays once per parent per page — remembered on this device, per
 * guardian, per TOUR_VERSION — and again whenever the parent presses "?".
 * Steps are resolved when the tour starts: only those whose element is on the
 * screen in this layout are kept, so "2 of 4" always counts what they will see.
 */
export const tour = reactive({
  open: false,
  key: null,
  steps: [],
  index: 0,
})

/** Pages the tour never interrupts: payment results and sign-in screens have no tour entry. */
export const hasTour = (name) => Boolean(name && tours[name])

function storageKey() {
  return `pp.tour.v${TOUR_VERSION}.${session.parent?.id ?? 'guest'}`
}

function readSeen() {
  try {
    const list = JSON.parse(localStorage.getItem(storageKey()) || '[]')
    return new Set(Array.isArray(list) ? list : [])
  } catch {
    return new Set()
  }
}

function markSeen(key) {
  const seen = readSeen()
  seen.add(key)
  try {
    localStorage.setItem(storageKey(), JSON.stringify([...seen]))
  } catch {
    // Private mode: the tour may show again next visit, which is harmless.
  }
}

export const hasSeen = (key) => readSeen().has(key)

/** The on-screen element a step points at, or null when it isn't showing. */
export function findTarget(name) {
  if (!name) return null
  for (const element of document.querySelectorAll(`[data-tour~="${name}"]`)) {
    const rect = element.getBoundingClientRect()
    if (rect.width > 0 && rect.height > 0 && getComputedStyle(element).visibility !== 'hidden') return element
  }
  return null
}

/** What the wording can use: layout, the URL query and the open child's first name (`First` starts a sentence). */
function context(route) {
  const id = Number(route?.params?.id)
  const child = children.list.find((item) => item.account_id === id)
  const first = firstName(child?.name)
  return { desktop: desktop.value, query: route?.query || {}, first: first || 'your child', First: first || 'Your child' }
}

function resolveSteps(key) {
  const layout = desktop.value ? 'desktop' : 'phone'
  return (tours[key] || []).filter((step) => (!step.only || step.only === layout) && (step.target === null || findTarget(step.target)))
}

export const words = (value, ctx) => (typeof value === 'function' ? value(ctx) : value)

/** Start a page's tour now. Returns false when nothing on it could be shown. */
export function startTour(route) {
  const key = route?.name
  if (!hasTour(key)) return false
  const steps = resolveSteps(key)
  if (!steps.length) return false
  const ctx = context(route)
  Object.assign(tour, {
    key,
    index: 0,
    steps: steps.map((step) => ({ ...step, title: words(step.title, ctx), text: words(step.text, ctx) })),
    open: true,
  })
  markSeen(key)
  return true
}

export function closeTour() {
  tour.open = false
}

/** The page is settled: nothing loading, no sheet or dialog open, a step to show. */
function pageReady(key) {
  if (document.hidden) return false
  if (document.querySelector('main [aria-busy="true"], main .pp-skel, .pp-sheet.is-open, .is-busy')) return false
  return resolveSteps(key).some((step) => step.target !== null)
}

let attempt = 0

/**
 * First visit to a page: wait for it to finish loading (up to ~10 s), then play
 * its tour. Leaving the page before then cancels the wait.
 */
export function autoStartTour(route) {
  const run = ++attempt
  const key = route?.name
  if (!session.signedIn || !hasTour(key) || hasSeen(key)) return
  let tries = 0
  const check = () => {
    if (run !== attempt || tour.open) return
    if (pageReady(key)) {
      // A beat for the page's own entrance animation to finish.
      setTimeout(() => run === attempt && !tour.open && pageReady(key) && startTour(route), 450)
      return
    }
    if (++tries < 50) setTimeout(check, 200)
  }
  setTimeout(check, 400)
}

/** Leaving the page: any pending first-visit tour is dropped. */
export function cancelAutoStart() {
  attempt++
}
