/**
 * Item Price Calculation Utilities
 * Centralized logic for calculating item prices, taxes, and totals
 */

/**
 * Rounds a number to 2 decimal places
 * @param {number} value - The value to round
 * @returns {number} Rounded value to 2 decimal places
 */
const roundToTwoDecimals = (value) => {
    if (!isFinite(value)) {
        return 0
    }
    return Math.round(value * 100) / 100
}

/**
 * Calculates item totals (gross, net, tax, total)
 * Handles both regular items and combo offer items
 *
 * @param {Object} item - The item object
 * @returns {Object} Item with calculated amounts
 */
export const calculateItemTotals = (item) => {
    const quantity = Number(item.quantity) || 1
    const unitPrice = Number(item.unit_price) || 0
    const discountAmount = Number(item.discount) || 0
    const taxRate = Number(item.tax) || 0
    const comboOfferPrice = Number(item.combo_offer_price) || 0

    // Use combo offer price if available, otherwise use regular unit price
    if (comboOfferPrice > 0) {
        // When combo offer is active, use combo_offer_price as the effective unit price
        // The combo_offer_price is already the discounted price, so we don't subtract discount again
        const grossAmount = roundToTwoDecimals(comboOfferPrice * quantity)
        const netAmount = grossAmount // No additional discount to subtract
        const taxAmount = roundToTwoDecimals(netAmount * (taxRate / 100))
        const total = roundToTwoDecimals(netAmount + taxAmount)

        return {
            ...item,
            gross_amount: grossAmount,
            net_amount: netAmount,
            tax_amount: taxAmount,
            total: total
        }
    } else {
        // Regular calculation without combo offer
        const grossAmount = unitPrice * quantity
        const netAmount = grossAmount - discountAmount
        const taxAmount = roundToTwoDecimals(netAmount * (taxRate / 100))
        const total = roundToTwoDecimals(netAmount + taxAmount)

        return {
            ...item,
            gross_amount: grossAmount,
            net_amount: netAmount,
            tax_amount: taxAmount,
            total: total
        }
    }
}

/**
 * Calculates totals for all items in a cart
 * @param {Object} items - Object containing all cart items
 * @returns {Object} Totals object with gross_amount, item_discount, tax_amount, total
 */
export const calculateCartTotals = (items) => {
    let tax_amount = 0
    let item_discount = 0
    let gross_amount = 0
    let total = 0

    Object.values(items).forEach(item => {
        const calculatedItem = calculateItemTotals(item)

        total += calculatedItem.total
        gross_amount += calculatedItem.gross_amount
        item_discount += calculatedItem.discount
        tax_amount += calculatedItem.tax_amount
    })

    return {
        gross_amount: parseFloat(gross_amount).toFixed(2),
        item_discount: parseFloat(item_discount).toFixed(2),
        tax_amount: parseFloat(tax_amount).toFixed(2),
        total: parseFloat(total).toFixed(2)
    }
}

/**
 * Applies combo offer pricing to cart items
 * @param {Object} cartItems - Current cart items
 * @param {Object} comboOfferItems - Items with combo offer pricing
 * @returns {Object} Updated cart items (same reference for reactivity)
 */
export const applyComboOfferPricing = (cartItems, comboOfferItems) => {
    // Update items in place to maintain reactivity
    let updatedCount = 0
    let missingCount = 0

    Object.entries(comboOfferItems).forEach(([key, comboItem]) => {
        if (cartItems[key]) {
            // Update combo offer related fields
            cartItems[key].combo_offer_price = Number(comboItem.combo_offer_price) || 0
            cartItems[key].discount = Number(comboItem.discount) || 0
            cartItems[key].combo_offer_id = comboItem.combo_offer_id || null
            updatedCount++
        } else {
            // Try to find item by constructing key from comboItem properties
            const constructedKey = comboItem.key || `${comboItem.employee_id}-${comboItem.inventory_id}`
            if (cartItems[constructedKey]) {
                cartItems[constructedKey].combo_offer_price = Number(comboItem.combo_offer_price) || 0
                cartItems[constructedKey].discount = Number(comboItem.discount) || 0
                cartItems[constructedKey].combo_offer_id = comboItem.combo_offer_id || null
                updatedCount++
            } else {
                missingCount++
                console.warn(`Item with key "${key}" not found in cart items. Combo item:`, comboItem)
            }
        }
    })

    if (missingCount > 0) {
        console.warn(`Failed to apply combo offer pricing to ${missingCount} item(s)`)
    }

    return cartItems
}

/**
 * Resets combo offer pricing for items no longer in any combo offer
 * @param {Object} cartItems - Current cart items
 * @param {Set<string>} comboOfferItemKeys - Set of item keys that are in combo offers
 * @returns {Object} Updated cart items with combo offers reset (same reference for reactivity)
 */
export const resetComboOfferPricing = (cartItems, comboOfferItemKeys) => {
    // Update items in place to maintain reactivity
    Object.keys(cartItems).forEach(key => {
        if (!comboOfferItemKeys.has(key) && cartItems[key].combo_offer_price) {
            // Check if discount was from combo offer before resetting
            const hadComboOffer = cartItems[key].combo_offer_id
            cartItems[key].combo_offer_price = 0
            cartItems[key].combo_offer_id = null
            // Reset discount only if it was from combo offer
            if (hadComboOffer) {
                cartItems[key].discount = 0
            }
        }
    })

    return cartItems
}

