import axios from 'axios'

const http = axios.create({
    baseURL: '/ticket/api',
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
        Accept: 'application/json',
    },
})

/** Tag each request with this tab's socket, so the live broadcast it causes skips this screen. */
http.interceptors.request.use((config) => {
    const socketId = window.Echo?.socketId()
    if (socketId) config.headers['X-Socket-ID'] = socketId
    return config
})

const data = (response) => response.data

/** The message a failed request should toast: the first validation error, else the server's message. */
export function errorMessage(error) {
    if (axios.isCancel(error)) return null
    const body = error?.response?.data
    const firstError = body?.errors ? Object.values(body.errors)[0]?.[0] : null
    return firstError || body?.message || error?.message || 'Something went wrong.'
}

/** Build multipart data for a ticket save; files ride along with the fields. */
export function ticketForm(fields, files = []) {
    const form = new FormData()
    Object.entries(fields).forEach(([key, value]) => form.append(key, value ?? ''))
    files.forEach((file) => form.append('files[]', file))
    return form
}

export const ticketApi = {
    board: (params, signal) => http.get('board', { params, signal }).then(data),
    show: (id) => http.get(`${id}`).then(data),
    store: (form) => http.post('', form).then(data),
    update: (id, form) => http.post(`${id}`, form).then(data),
    status: (id, status) => http.patch(`${id}/status`, { status }).then(data),
    destroy: (id) => http.delete(`${id}`).then(data),
    destroyAttachment: (id, attachmentId) => http.delete(`${id}/attachment/${attachmentId}`).then(data),
    addComment: (id, comment) => http.post(`${id}/comment`, { comment }).then(data),
    updateComment: (id, commentId, comment) => http.put(`${id}/comment/${commentId}`, { comment }).then(data),
    deleteComment: (id, commentId) => http.delete(`${id}/comment/${commentId}`).then(data),
    importUpload: (file, onProgress) => {
        const form = new FormData()
        form.append('file', file)
        return http.post('import/upload', form, { onUploadProgress: onProgress }).then(data)
    },
    importSheet: (payload) => http.post('import/sheet', payload).then(data),
    importReview: (payload) => http.post('import/review', payload).then(data),
    importCommit: (payload) => http.post('import/commit', payload).then(data),
    templateUrl: '/ticket/api/import/template',
}
