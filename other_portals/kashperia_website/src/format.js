export const storeName = import.meta.env.VITE_STORE_NAME || 'KASHPERIA'
const currency = import.meta.env.VITE_CURRENCY || 'QAR'

/** House contact points used across the site (footer, enquiries, PDP assistance). */
export const house = {
  email: 'info@kashperia.com',
  whatsapp: '97433322965',
  whatsappLabel: '+974 3332 2965',
  instagram: 'https://www.instagram.com/kashperia/',
}

const number = new Intl.NumberFormat('en', { maximumFractionDigits: 2 })

/** QAR 1,500 — prices as the reference prints them (currency code first). */
export function money(value) {
  return `${currency} ${number.format(Number(value) || 0)}`
}

/** "Plains by K" → "plains-by-k": the slug the collection / page URLs carry. */
export function slugify(text) {
  return String(text || '')
    .toLowerCase()
    .replace(/&/g, ' and ')
    .normalize('NFD')
    .replace(/[̀-ͯ]/g, '')
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
}

/** WhatsApp link to the House with a prefilled message. */
export function whatsappLink(message = '') {
  return `https://wa.me/${house.whatsapp}${message ? `?text=${encodeURIComponent(message)}` : ''}`
}

/** Short status / validation messages used by the bag and checkout. */
const messages = {
  needShop: 'Please choose the boutique you will collect from.',
  needDetails: 'Please enter your name, email address and mobile number.',
  badEmail: 'Please enter a valid email address.',
  badMobile: 'Please enter a valid mobile number.',
  needAddress: 'Please complete the delivery address.',
  payStartError: 'We could not open the secure payment page. Please try again.',
}

export function t(key) {
  return messages[key] || key
}
