import { inject, reactive } from 'vue'
import { contractApi } from '../api.js'

export const CONTEXT_KEY = Symbol('project-contract-context')

/** Shared lookups (payment methods, enums), fetched once per page. */
const options = reactive({ loaded: false, loading: null, payment_methods: [], frequencies: [], statuses: [], security_forms: [], security_purposes: [], security_directions: [], vendor_types: [], document_types: [], approval_levels: [] })

export function loadOptions() {
    if (options.loaded) return Promise.resolve(options)
    if (!options.loading) {
        options.loading = contractApi.options().then((response) => {
            Object.assign(options, response.data, { loaded: true })
            return options
        })
    }
    return options.loading
}

export function useOptions() {
    loadOptions()
    return options
}

/** Permissions, URLs and the current user, from the page's data attributes. */
export function useContext() {
    return inject(CONTEXT_KEY)
}

export function urlFor(urls, name, id = null) {
    const url = urls?.[name] ?? '#'
    return id === null ? url : url.replace('__ID__', id)
}

/** A SweetAlert confirm when the app layout loaded it, the browser's otherwise. */
export async function confirmAction({ title, text, confirm = 'Yes', danger = false }) {
    if (window.Swal) {
        const result = await window.Swal.fire({
            title,
            text,
            icon: danger ? 'warning' : 'question',
            showCancelButton: true,
            confirmButtonText: confirm,
            cancelButtonText: 'Cancel',
            confirmButtonColor: danger ? '#c45c56' : undefined,
            reverseButtons: true,
        })
        return result.isConfirmed
    }
    return window.confirm(`${title}\n\n${text ?? ''}`)
}
