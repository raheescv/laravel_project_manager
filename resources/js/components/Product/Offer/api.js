import axios from 'axios'

const http = axios.create({
    baseURL: '/product/offer/api',
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
        Accept: 'application/json',
    },
})

const data = (response) => response.data.data

/** The message a failed request should toast: the first validation error, else the server's message. */
export function errorMessage(error) {
    if (axios.isCancel(error)) return null
    const body = error?.response?.data
    const firstError = body?.errors ? Object.values(body.errors)[0]?.[0] : null
    return firstError || body?.message || error?.message || 'Something went wrong.'
}

export const offerApi = {
    list: (params, signal) => http.get('', { params, signal }).then(data),
    show: (id) => http.get(`${id}`).then(data),
    categories: (params) => http.get('categories', { params }).then(data),
    products: (params, signal) => http.get('products', { params, signal }).then(data),
    store: (payload) => http.post('', payload).then((response) => response.data),
    update: (id, payload) => http.put(`${id}`, payload).then((response) => response.data),
    destroy: (id) => http.delete(`${id}`).then((response) => response.data),
}
