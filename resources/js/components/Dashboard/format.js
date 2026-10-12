/**
 * Number formatting for widget values. The server sends raw numbers with a
 * format (money, number, percent, days); figures shown large drop decimals
 * once they are big enough that cents are noise.
 */
export function createFormatter(currency = {}) {
    const decimals = Number.isInteger(currency.decimals) ? currency.decimals : 2
    const code = currency.code || ''

    const number = (value, digits = 0) =>
        Number(value || 0).toLocaleString('en-US', { minimumFractionDigits: digits, maximumFractionDigits: digits })

    /** Split a value into an optional small prefix and the figure, e.g. { prefix: 'QAR', text: '184,320' }. */
    function parts(value, format = 'number', { big = false } = {}) {
        const numeric = Number(value || 0)
        switch (format) {
            case 'money':
                return { prefix: code, text: number(numeric, big && Math.abs(numeric) >= 10000 ? 0 : decimals) }
            case 'percent':
                return { prefix: '', text: `${number(numeric, 1)}%` }
            case 'days':
                return { prefix: '', text: number(numeric, 1), suffix: 'days' }
            default:
                return { prefix: '', text: number(numeric, Number.isInteger(numeric) ? 0 : 1) }
        }
    }

    /** One plain string, for tooltips and legends. */
    function text(value, format = 'number') {
        const { prefix, text: figure, suffix } = parts(value, format)
        return [prefix, figure, suffix].filter(Boolean).join(' ')
    }

    /** Compact money/number for tight spots: 1.24M, 312k. */
    function compact(value, format = 'number') {
        const figure = Number(value || 0).toLocaleString('en-US', { notation: 'compact', maximumFractionDigits: 2 })
        return format === 'money' && code ? `${code} ${figure}` : figure
    }

    return { parts, text, compact, number }
}
