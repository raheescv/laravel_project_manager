/**
 * Offer price arithmetic shared by the editor's bulk tools and row inputs.
 * Prices are kept to 2 decimals; `step` (0 = none) rounds to the nearest step.
 */

export const roundingSteps = [
    { value: 0, label: 'No rounding' },
    { value: 0.25, label: 'Nearest 0.25' },
    { value: 0.5, label: 'Nearest 0.50' },
    { value: 1, label: 'Nearest 1' },
    { value: 5, label: 'Nearest 5' },
]

const cents = (value) => Math.round(value * 100) / 100

export function roundPrice(value, step = 0) {
    const price = Math.max(0, Number(value) || 0)
    if (!step) return cents(price)
    return cents(Math.round(price / step) * step)
}

const clampPercent = (percent) => Math.min(100, Math.max(0, Number(percent) || 0))

/**
 * The offer price for one product under a bulk rule.
 * mode: 'percent' (off MRP), 'amount' (off MRP) or 'price' (fixed).
 */
export function applyRule(mrp, mode, value, step = 0) {
    const base = Number(mrp) || 0
    if (mode === 'amount') return roundPrice(base - (Number(value) || 0), step)
    if (mode === 'price') return roundPrice(value, 0)
    return roundPrice(base * (1 - clampPercent(value) / 100), step)
}

export function percentOff(mrp, price) {
    const base = Number(mrp) || 0
    return base > 0 ? ((base - price) / base) * 100 : 0
}

export function marginPercent(cost, price) {
    return price > 0 ? ((price - (Number(cost) || 0)) / price) * 100 : 0
}

export function formatAmount(value) {
    return (Number(value) || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
