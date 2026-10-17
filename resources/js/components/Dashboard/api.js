import axios from 'axios'

const http = axios.create({
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

export const isCancel = (error) => axios.isCancel(error)

/** Endpoints come from the Blade host, so the routes stay named in Laravel. */
export function dashboardApi(urls) {
    return {
        widget: (key, params, signal) => http.get(urls.widget.replace('__KEY__', key), { params, signal }).then(data),
        saveLayout: (layout) => http.put(urls.layout, { layout }).then(data),
        saveOption: (key, option) => http.put(urls.option.replace('__KEY__', key), { option }).then(data),
    }
}
