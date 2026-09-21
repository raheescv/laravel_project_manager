import client from './client'

/**
 * The parent portal API (routes/api_v1_parent.php on the school's server).
 * Every function resolves to the envelope's `data`.
 */

/** { name, logo, accent, currency: { code, decimals }, contact: { mobile, email } } */
export const fetchSchool = () => client.get('/school')

/** { token, token_type, expires_at, parent } */
export const login = (payload) => client.post('/login', payload)

/**
 * `login` is the mobile number or the email the school has for the parent. A number
 * also goes as `mobile`, the only key a server from before email was accepted reads.
 */
export const forgotPassword = (login) => client.post('/forgot-password', login.includes('@') ? { login } : { login, mobile: login })

/** { valid, name, valid_days } */
export const checkPasswordLink = (token) => client.get(`/set-password/${encodeURIComponent(token)}`)

/** Same shape as login. */
export const setPassword = (payload) => client.post('/set-password', payload)

export const fetchMe = () => client.get('/me')

export const logout = () => client.post('/logout')

/** { current_password, password, password_confirmation } — other sign-ins end, this one stays. */
export const changePassword = (payload) => client.post('/password', payload)

/** [{ account_id, name, image_url, class, has_card, card_blocked, balance, available, … }] */
export const fetchStudents = () => client.get('/students')

/** One child + overdraft_limit, card_blocked_at, topup: { enabled, methods: [{ key, label, detail }], min, max, suggestions } */
export const fetchStudent = (id) => client.get(`/students/${id}`)

/** { data: Bill[], pagination, period } — month = 'YYYY-MM' */
export const fetchBills = (id, month, page = 1) => client.get(`/students/${id}/bills`, { params: { month, page } })

export const fetchBill = (id, saleId) => client.get(`/students/${id}/bills/${saleId}`)

/** { opening, closing, credit, debit, rows, period } */
export const fetchStatement = (id, month) => client.get(`/students/${id}/statement`, { params: { month } })

export const blockCard = (id, reason) => client.post(`/students/${id}/card/block`, { reason })

/**
 * `method`: 'debit' (QPay) or 'credit' (Mastercard Gateway).
 * → { topup, payment } where payment is { type: 'qpay', url, method, fields } or { type: 'mpgs', script, session_id }.
 */
export const startTopup = (id, amount, method) => client.post(`/students/${id}/topups`, { amount, lang: 'En', method })

export const fetchTopup = (pun) => client.get(`/topups/${encodeURIComponent(pun)}`)

/**
 * For a parent who left QPay's page without paying: QPay is asked, and the top-up
 * comes back `failed` (cancelled — QPay never got it) or `success` (it was paid, so
 * it is on the card). Refused with `errors.retry_at` while it is too early or QPay
 * gave no answer.
 */
export const cancelTopup = (pun) => client.post(`/topups/${encodeURIComponent(pun)}/cancel`)

/* ---- Canteen pre-orders ---- */

/** [{ id, name, items: [{ id, name, mrp, thumbnail, … }] }] grouped by category */
export const fetchPreOrderMenu = () => client.get('/pre-order-menu')

/** { enabled, cutoff, school_days, max_quantity, weekly, days: [{ date, source, locked, deadline, collected, order }] } */
export const fetchPreOrders = (id) => client.get(`/students/${id}/pre-orders`)

/** Each save / change answers with the refreshed schedule. */
export const saveWeeklyPreOrder = (id, payload) => client.put(`/students/${id}/pre-orders/weekly`, payload)
export const pauseWeeklyPreOrder = (id) => client.post(`/students/${id}/pre-orders/weekly/pause`)
export const resumeWeeklyPreOrder = (id) => client.post(`/students/${id}/pre-orders/weekly/resume`)
export const deleteWeeklyPreOrder = (id) => client.delete(`/students/${id}/pre-orders/weekly`)
export const saveDayPreOrder = (id, date, payload) => client.put(`/students/${id}/pre-orders/days/${date}`, payload)
export const skipDayPreOrder = (id, date) => client.post(`/students/${id}/pre-orders/days/${date}/skip`)
export const clearDayPreOrder = (id, date) => client.delete(`/students/${id}/pre-orders/days/${date}`)
