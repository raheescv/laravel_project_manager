import { createApp } from 'vue'
import Toast from 'vue-toastification'
import 'vue-toastification/dist/index.css'

// Toast configuration
const toastOptions = {
    position: 'top-right',
    timeout: 3000,
    closeOnClick: true,
    pauseOnFocusLoss: true,
    pauseOnHover: true,
    draggable: true,
    draggablePercent: 0.6,
    showCloseButtonOnHover: false,
    hideProgressBar: false,
    closeButton: 'button',
    icon: true,
    rtl: false
}

/**
 * Format a date to a localized date string
 * @param {string|Date} date - Date to format
 * @returns {string} Formatted date string or 'N/A' if date is invalid
 */
export const formatDate = (date) => {
    if (!date) return 'N/A'
    return new Date(date).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    })
}

/**
 * Create a Vue app instance with toast plugin configured
 * @param {Component} component - Vue component to mount
 * @param {Object} plugins - Additional plugins to register
 * @returns {App} Configured Vue app instance
 */
export function createVueApp(component, plugins = {}) {
    const app = createApp(component)

    // Register toast plugin
    app.use(Toast, toastOptions)

    // Register global utilities
    app.config.globalProperties.$formatDate = formatDate

    // Register any additional plugins
    if (plugins && typeof plugins === 'object') {
        Object.entries(plugins).forEach(([plugin, options]) => {
            app.use(plugin, options)
        })
    }

    return app
}

/**
 * Mount a Vue component to a DOM element with toast configured
 * @param {Component} component - Vue component to mount
 * @param {string} elementId - ID of the DOM element to mount to
 * @param {Object} plugins - Additional plugins to register
 */
export function mountVueApp(component, elementId, plugins = {}) {
    document.addEventListener('DOMContentLoaded', () => {
        const element = document.getElementById(elementId)
        if (element) {
            const app = createVueApp(component, plugins)
            app.mount(element)
        }
    })
}
