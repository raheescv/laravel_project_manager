import { createApp } from 'vue'
import OpeningBalancePage from './components/Inventory/OpeningBalance/OpeningBalancePage.vue'

// Mount the component when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    const element = document.getElementById('opening-balance-form')
    if (element) {
        const app = createApp(OpeningBalancePage)
        app.mount(element)
    }
})

