import { createApp } from 'vue'
import Toast from 'vue-toastification'
import 'vue-toastification/dist/index.css'
import GeneralVoucherPage from './components/Account/GeneralVoucher/GeneralVoucherPage.vue'

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

let vueApp = null
let currentTableId = null

function mountVueComponent(tableId = null) {
    const container = document.getElementById('GeneralVoucherModalContent')
    if (!container) {
        console.error('GeneralVoucherModalContent container not found')
        return
    }

    // Unmount existing app if any
    if (vueApp) {
        vueApp.unmount()
        vueApp = null
    }

    // Clear container
    container.innerHTML = ''

    // Create new Vue app
    vueApp = createApp(GeneralVoucherPage, {
        tableId: tableId
    })

    // Register toast plugin
    vueApp.use(Toast, toastOptions)

    // Handle close event
    vueApp.config.globalProperties.$modal = {
        close: () => {
            const modal = document.getElementById('GeneralVoucherModal')
            if (modal) {
                const bsModal = bootstrap.Modal.getInstance(modal)
                if (bsModal) {
                    bsModal.hide()
                }
            }
        }
    }

    // Mount the component
    vueApp.mount(container)
    currentTableId = tableId
}

function handleCreate() {
    mountVueComponent(null)
    const modal = document.getElementById('GeneralVoucherModal')
    if (modal) {
        const bsModal = new bootstrap.Modal(modal)
        bsModal.show()
    }
}

function handleEdit(id) {
    mountVueComponent(id)
    const modal = document.getElementById('GeneralVoucherModal')
    if (modal) {
        const bsModal = new bootstrap.Modal(modal)
        bsModal.show()
    }
}

function handleRefresh() {
    // Dispatch event to refresh the table
    window.dispatchEvent(new CustomEvent('RefreshGeneralVoucherTable'))
}

// Listen for Livewire events
document.addEventListener('DOMContentLoaded', () => {
    // Listen for create event
    window.addEventListener('GeneralVoucher-Page-Create-Component', () => {
        handleCreate()
    })

    // Listen for edit event
    window.addEventListener('GeneralVoucher-Page-Update-Component', (event) => {
        const id = event.detail?.id || event.detail?.[0]?.id
        if (id) {
            handleEdit(id)
        }
    })

    // Listen for modal toggle (for backward compatibility)
    window.addEventListener('ToggleGeneralVoucherModal', () => {
        const modal = document.getElementById('GeneralVoucherModal')
        if (modal) {
            const bsModal = bootstrap.Modal.getInstance(modal) || new bootstrap.Modal(modal)
            bsModal.toggle()
        }
    })

    // Listen for reset-to-new event (when "Save and New" is clicked after update)
    window.addEventListener('GeneralVoucher-Reset-To-New', () => {
        // Remount as new entry
        mountVueComponent(null)
    })

    // Handle modal close to unmount Vue app
    const modal = document.getElementById('GeneralVoucherModal')
    if (modal) {
        modal.addEventListener('hidden.bs.modal', () => {
            if (vueApp) {
                vueApp.unmount()
                vueApp = null
            }
            const container = document.getElementById('GeneralVoucherModalContent')
            if (container) {
                container.innerHTML = ''
            }
        })
    }
})

// Export for manual use if needed
window.GeneralVoucherModal = {
    create: handleCreate,
    edit: handleEdit,
    refresh: handleRefresh
}
