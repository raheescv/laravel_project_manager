/**
 * Combo Offer Calculation Utilities
 * Matches PHP ComboOffer::calculateComboOfferPrices logic exactly
 */

/**
 * Rounds a number to 2 decimal places (matching PHP's round($value, 2))
 * @param {number} value - The value to round
 * @returns {number} Rounded value to 2 decimal places
 */
export const roundToTwoDecimals = (value) => {
    // Handle edge cases: NaN, Infinity
    if (!isFinite(value)) {
        return 0
    }
    return Math.round(value * 100) / 100
}

/**
 * Calculates combo offer prices for selected services
 * Matches PHP: ComboOffer::calculateComboOfferPrices()
 *
 * @param {Array<string>} selectedServices - Array of service keys
 * @param {number} comboOfferId - The combo offer ID
 * @param {Object} comboOfferItems - Object containing all combo offer items
 * @param {Object} selectedComboOffer - The selected combo offer object with amount
 * @returns {Array<Object>} Array of items with calculated combo offer prices
 */
export const calculateComboOfferPrices = (
    selectedServices,
    comboOfferId,
    comboOfferItems,
    selectedComboOffer
) => {
    // Get services from comboOfferItems (matching PHP: collect($this->comboOfferItems)->only($selectedServices))
    const services = selectedServices
        .map(key => comboOfferItems[key])
        .filter(Boolean)

    // Calculate total original price (matching PHP: $services->sum('unit_price'))
    const totalOriginalPrice = services.reduce(
        (sum, item) => sum + (parseFloat(item.unit_price) || 0),
        0
    )

    // Safety check: prevent division by zero
    if (totalOriginalPrice === 0) {
        throw new Error('Cannot calculate combo offer: total original price is zero')
    }

    // Get combo offer amount (matching PHP: $this->comboOffer->amount)
    const comboOfferAmount = parseFloat(selectedComboOffer.amount) || 0

    // Map each service and calculate prices (matching PHP logic exactly)
    // PHP: $services->map(function ($item) use ($totalOriginalPrice, $comboOfferAmount, $comboOfferId) { ... })
    return services.map(item => {
        const unitPrice = parseFloat(item.unit_price) || 0

        // Calculate combo offer price: round((unit_price / totalOriginalPrice) * comboOfferAmount, 2)
        // This matches PHP: round(($item['unit_price'] / $totalOriginalPrice) * $comboOfferAmount, 2)
        const comboOfferPrice = roundToTwoDecimals(
            (unitPrice / totalOriginalPrice) * comboOfferAmount
        )

        // Calculate discount: round(unit_price - comboOfferPrice, 2)
        // This matches PHP: round($item['unit_price'] - $comboOfferPrice, 2)
        const discount = roundToTwoDecimals(unitPrice - comboOfferPrice)

        // Return updated item (matching PHP structure)
        // Ensure key is preserved for proper item identification
        return {
            ...item,
            key: item.key || item.inventory_id || item.product_id, // Ensure key exists
            combo_offer_price: comboOfferPrice,
            discount: discount,
            combo_offer_id: comboOfferId
        }
    })
}

/**
 * Calculates the discount percentage for a combo offer
 * @param {Object} comboOffer - The combo offer object
 * @returns {number} Discount percentage
 */
export const calculateDiscountPercentage = (comboOffer) => {
    const originalTotal = comboOffer.items.reduce((sum, item) => sum + (parseFloat(item.unit_price) || 0), 0)

    if (originalTotal === 0) {
        return 0
    }

    const discountPercent = roundToTwoDecimals((1 - (parseFloat(comboOffer.amount) || 0) / originalTotal) * 100 * 10) / 10

    return isNaN(discountPercent) ? 0 : discountPercent
}

