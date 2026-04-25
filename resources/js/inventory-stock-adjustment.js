import StockAdjustmentPage from './components/Inventory/StockAdjustment/StockAdjustmentPage.vue'
import { mountVueApp } from './utils/createVueApp.js'

// Mount the component with toast configured globally
mountVueApp(StockAdjustmentPage, 'stock-adjustment-form')

