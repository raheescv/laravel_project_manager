import StockCheckListPage from './components/Inventory/StockCheck/Pages/StockCheckListPage.vue'
import { mountVueApp } from './utils/createVueApp.js'

// Mount the component with toast configured globally
mountVueApp(StockCheckListPage, 'stock-check-list')
