import StockCheckListPage from './components/Inventory/StockCheck/StockCheckListPage.vue'
import { mountVueApp } from './utils/createVueApp.js'

// Mount the component with toast configured globally
mountVueApp(StockCheckListPage, 'stock-check-list')
