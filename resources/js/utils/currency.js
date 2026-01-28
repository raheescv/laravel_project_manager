/**
 * Get currency code based on country_id from System Configurations
 * @returns {string} Currency code (INR for India, QAR for Qatar)
 */
function getCurrencyCode() {
    // Get country_id from meta tag
    const metaTag = document.querySelector('meta[name="country-id"]')
    const countryId = metaTag ? parseInt(metaTag.getAttribute('content')) : null

    // Country constants match app/Models/Country.php
    const INDIA = 105
    const QATAR = 187

    // Determine currency based on country_id
    if (countryId === INDIA) {
        return 'INR'
    } else if (countryId === QATAR) {
        return 'QAR'
    }

    // Default to QAR if country_id is not set or unknown
    return 'QAR'
}

/**
 * Format currency value
 * @param {number} amount - The amount to format
 * @returns {string} Formatted currency string
 */
export function formatCurrency(amount) {
    if (amount === null || amount === undefined || isNaN(amount)) {
        return '0.00'
    }

    // Get currency code from System Configurations
    const currencyCode = getCurrencyCode()

    // Use the same format as Laravel's currency helper
    return new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: currencyCode,
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(amount)
}

/**
 * Format currency without symbol (just number with commas)
 * @param {number} amount - The amount to format
 * @returns {string} Formatted number string
 */
export function formatNumber(amount) {
    if (amount === null || amount === undefined || isNaN(amount)) {
        return '0.00'
    }

    return new Intl.NumberFormat('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(amount)
}
