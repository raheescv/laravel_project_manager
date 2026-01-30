import axios from 'axios'

class GetStockCheckItemsAction {
    async execute(stockCheckId, params = {}) {
        try {
            const response = await axios.get(`/inventory/stock-check/${stockCheckId}/items`, { params })
            return {
                success: response.data?.success !== false,
                data: response.data?.data || response.data || [],
                message: response.data?.message || 'Items retrieved successfully'
            }
        } catch (error) {
            return {
                success: false,
                data: [],
                message: error.response?.data?.message || 'Failed to load items'
            }
        }
    }
}

export default GetStockCheckItemsAction
