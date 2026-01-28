import { createApp } from 'vue'
import PurchasePage from './components/Purchase/PurchasePage.vue'

// Initialize Vue app for purchase page
function initPurchasePage() {
    const purchasePageElement = document.getElementById('purchase-page-vue')
    
    if (purchasePageElement && !window.purchasePageVueInstance) {
        const tableId = purchasePageElement.dataset.tableId || null
        
        // Get initial data from Livewire if available
        const initialData = window.purchasePageData || {}
        
        const app = createApp(PurchasePage, {
            tableId: tableId,
            initialData: initialData
        })
        
        window.purchasePageVueInstance = app.mount(purchasePageElement)
    }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPurchasePage)
} else {
    initPurchasePage()
}

// Re-initialize on Livewire updates
document.addEventListener('livewire:init', () => {
    setTimeout(initPurchasePage, 100)
})

document.addEventListener('livewire:update', () => {
    if (window.purchasePageVueInstance) {
        // Component will handle updates via Livewire get() calls
    }
})
