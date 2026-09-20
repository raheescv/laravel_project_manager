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

/** One child + overdraft_limit, card_blocked_at, topup: { enabled, min, max, suggestions } */
export const fetchStudent = (id) => client.get(`/students/${id}`)

/** { data: Bill[], pagination, period } — month = 'YYYY-MM' */
export const fetchBills = (id, month, page = 1) => client.get(`/students/${id}/bills`, { params: { month, page } })

export const fetchBill = (id, saleId) => client.get(`/students/${id}/bills/${saleId}`)

/** { opening, closing, credit, debit, rows, period } */
export const fetchStatement = (id, month) => client.get(`/students/${id}/statement`, { params: { month } })

export const blockCard = (id, reason) => client.post(`/students/${id}/card/block`, { reason })

/** { topup, payment: { url, method, fields } } */
export const startTopup = (id, amount) => client.post(`/students/${id}/topups`, { amount, lang: 'En' })

export const fetchTopup = (pun) => client.get(`/topups/${encodeURIComponent(pun)}`)

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
