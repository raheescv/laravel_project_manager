import axios from 'axios'

const http = axios.create({
    baseURL: '/project-contract/api',
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
        Accept: 'application/json',
    },
})

const data = (response) => response.data

/** The message a failed request should toast: the first validation error, else the server's message. */
export function errorMessage(error) {
    if (axios.isCancel(error)) return null
    const body = error?.response?.data
    const firstError = body?.errors ? Object.values(body.errors)[0]?.[0] : null
    return firstError || body?.message || error?.message || 'Something went wrong.'
}

/** Field errors keyed by name, for showing under inputs. */
export function fieldErrors(error) {
    const errors = error?.response?.data?.errors ?? {}
    return Object.fromEntries(Object.entries(errors).map(([key, messages]) => [key, messages[0]]))
}

/** Multipart body; files ride along with the fields. */
export function formData(fields, files = {}) {
    const form = new FormData()
    Object.entries(fields).forEach(([key, value]) => {
        if (value === null || value === undefined || value === '') return
        if (Array.isArray(value)) value.forEach((item) => form.append(`${key}[]`, item))
        else form.append(key, value)
    })
    Object.entries(files).forEach(([key, value]) => {
        if (Array.isArray(value)) value.forEach((file) => form.append(`${key}[]`, file))
        else if (value) form.append(key, value)
    })
    return form
}

export const contractApi = {
    options: () => http.get('options').then(data),
    list: (params, signal) => http.get('contracts', { params, signal }).then(data),
    show: (id) => http.get(`contracts/${id}`).then(data),
    store: (payload) => http.post('contracts', payload).then(data),
    update: (id, payload) => http.put(`contracts/${id}`, payload).then(data),
    destroy: (id) => http.delete(`contracts/${id}`).then(data),
    submit: (id) => http.post(`contracts/${id}/submit`).then(data),
    status: (id, status) => http.post(`contracts/${id}/status`, { status }).then(data),
    decide: (payload) => http.post('approvals/decide', payload).then(data),

    addTerm: (id, payload) => http.post(`contracts/${id}/terms`, payload).then(data),
    updateTerm: (id, termId, payload) => http.put(`contracts/${id}/terms/${termId}`, payload).then(data),
    deleteTerm: (id, termId) => http.delete(`contracts/${id}/terms/${termId}`).then(data),
    pay: (id, payload) => http.post(`contracts/${id}/payments`, payload).then(data),
    reversePayment: (id, paymentId, reason) => http.post(`contracts/${id}/payments/${paymentId}/reverse`, { reason }).then(data),

    extend: (id, form) => http.post(`contracts/${id}/extensions`, form).then(data),
    addSecurity: (id, form) => http.post(`contracts/${id}/securities`, form).then(data),
    deleteSecurity: (id, securityId) => http.delete(`contracts/${id}/securities/${securityId}`).then(data),
    settleSecurity: (id, securityId, payload) => http.post(`contracts/${id}/securities/${securityId}/settle`, payload).then(data),

    uploadDocuments: (id, form) => http.post(`contracts/${id}/documents`, form).then(data),
    deleteDocument: (id, documentId) => http.delete(`contracts/${id}/documents/${documentId}`).then(data),
    addNote: (id, note) => http.post(`contracts/${id}/notes`, { note }).then(data),
    updateNote: (id, noteId, note) => http.put(`contracts/${id}/notes/${noteId}`, { note }).then(data),
    deleteNote: (id, noteId) => http.delete(`contracts/${id}/notes/${noteId}`).then(data),

    pendingReport: (params, signal) => http.get('reports/pending', { params, signal }).then(data),
    paidReport: (params, signal) => http.get('reports/paid', { params, signal }).then(data),
    exportUrl: (report, params) => `/project-contract/api/reports/${report}/export?${new URLSearchParams(Object.entries(params).filter(([, value]) => value !== '' && value !== null && value !== undefined))}`,

    vendors: (params, signal) => http.get('vendors', { params, signal }).then(data),
    vendorSearch: (q, signal) => http.get('vendors/search', { params: { q }, signal }).then(data),
    vendor: (id) => http.get(`vendors/${id}`).then(data),
    storeVendor: (payload) => http.post('vendors', payload).then(data),
    updateVendor: (id, payload) => http.put(`vendors/${id}`, payload).then(data),
    deleteVendor: (id) => http.delete(`vendors/${id}`).then(data),
    deleteVendors: (ids) => http.post('vendors/delete', { ids }).then(data),
    vendorExportUrl: () => '/project-contract/api/vendors/export',
}
