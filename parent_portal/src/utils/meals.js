/**
 * Helpers over the pre-order menu (GET /pre-order-menu): every meal flattened out
 * of its category, with the weekly dishes the school entered.
 */
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
