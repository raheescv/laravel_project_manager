import { createApp, h } from 'vue'
import Toast from 'vue-toastification'
import 'vue-toastification/dist/index.css'
import JournalEntriesModal from './components/JournalEntriesModal.vue'


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
let currentJournalId = null
let modalComponent = null

function mountVueComponent(journalId = null) {
    try {
        let container = document.getElementById('JournalEntriesModalContainer')

        // Always create container in body to avoid Livewire interference
        if (container && container.parentNode !== document.body) {
            // Remove from current location if not in body
            container.remove()
            container = null
        }

        // If container doesn't exist, create it in body
        if (!container) {
            container = document.createElement('div')
            container.id = 'JournalEntriesModalContainer'
            document.body.appendChild(container)
        }

        // Unmount existing app if any
        if (vueApp) {
            try {
                vueApp.unmount()
            } catch (e) {
                console.warn('Error unmounting previous app:', e)
            }
            vueApp = null
            modalComponent = null
        }

        // Clear container
        container.innerHTML = ''


        // Create a simple wrapper that uses render function
        const handleClose = () => {
            // Unmount after a short delay to allow animation
            setTimeout(() => {
                if (vueApp) {
                    try {
                        vueApp.unmount()
                    } catch (e) {
                        console.warn('Error unmounting on close:', e)
                    }
                    vueApp = null
                    modalComponent = null
                }
                if (container) {
                    container.innerHTML = ''
                }
                currentJournalId = null
            }, 300)
        }

        // Create new Vue app with render function
        vueApp = createApp({
            render() {
                const vnode = h(JournalEntriesModal, {
                    show: true,
                    journalId: journalId,
                    onClose: handleClose
                })
                return vnode
            }
        })

        // Register toast plugin
        vueApp.use(Toast, toastOptions)

        // Mount the component
        vueApp.mount(container)
        currentJournalId = journalId

        // Debug: Check if modal element exists after mount
        setTimeout(() => {
            const modalEl = container.querySelector('[data-journal-modal]')
            if (!modalEl) {
                console.error('Modal element not found! Container might be empty or component not rendering.')
            }
        }, 100)
    } catch (error) {
        console.error('Error mounting Vue component:', error)
        alert('Error opening journal entries modal. Please check the console for details.')
    }
}

function openJournalModal(journalId) {
    if (!journalId) {
        console.error('Journal ID is required')
        return
    }
    mountVueComponent(journalId)
}

function closeJournalModal() {
    if (vueApp && modalComponent) {
        modalComponent.handleClose()
    }
}

// Make function available globally immediately
window.openJournalModal = openJournalModal
window.closeJournalModal = closeJournalModal

// Also make it available on DOM ready (for initial page load)
document.addEventListener('DOMContentLoaded', () => {
    window.openJournalModal = openJournalModal
    window.closeJournalModal = closeJournalModal
})

// Listen for Livewire initialization (for Livewire components)
document.addEventListener('livewire:initialized', () => {
    window.openJournalModal = openJournalModal
    window.closeJournalModal = closeJournalModal
})

// Listen for Livewire updates (when DOM is updated by Livewire)
document.addEventListener('livewire:load', () => {
    window.openJournalModal = openJournalModal
    window.closeJournalModal = closeJournalModal
})

// Export for manual use if needed
window.JournalEntriesModal = {
    open: openJournalModal,
    close: closeJournalModal
}
