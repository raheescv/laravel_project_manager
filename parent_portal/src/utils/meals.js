import { resolveImage } from '@/api/client'

/**
 * Helpers over the pre-order menu (GET /pre-order-menu): every meal flattened out
 * of its category, with the weekly dishes the school entered.
 */

/** Every day a parent can book: today plus the 30 days after it. */
export const BOOKING_WINDOW_DAYS = 31

/** Inline style that shows a meal's photo on a .pp-thumb; null when it has none. */
export function mealThumbStyle(meal) {
  const image = resolveImage(meal.thumbnail)
  return image ? { backgroundImage: `url("${image}")` } : null
}

export const flattenMenu = (groups) => (groups || []).flatMap((group) => group.items || [])

/** Whether a meal is served on an ISO weekday (no menu = every school day). */
export const servedOn = (meal, weekday) => !meal?.served_weekdays || meal.served_weekdays.includes(weekday)

/** The meal's dishes on an ISO weekday. */
export const dishesOn = (meal, weekday) => (meal?.menu || []).find((day) => day.weekday === weekday)?.dishes || []

/** ISO weekday (1 = Monday … 7 = Sunday) of a 'YYYY-MM-DD' date. */
export function isoWeekday(value) {
  const [y, m, d] = String(value).split('-').map(Number)
  const day = new Date(y, m - 1, d).getDay()
  return day === 0 ? 7 : day
}
