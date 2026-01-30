import StockCheckShowPage from './components/Inventory/StockCheck/Pages/StockCheckShowPage.vue'
import { mountVueApp } from './utils/createVueApp.js'

// Mount the component with toast configured globally
mountVueApp(StockCheckShowPage, 'stock-check-show')
