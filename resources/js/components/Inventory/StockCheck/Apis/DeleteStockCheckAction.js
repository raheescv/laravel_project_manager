import axios from 'axios'

class DeleteStockCheckAction {
    async execute(stockCheckId) {
        try {
            const response = await axios.delete(`/inventory/stock-check/${stockCheckId}`)
            // Check if the API response indicates success or failure
            if (response.data?.success === false) {
                return {
                    success: false,
                    data: null,
                    message: response.data?.message || 'Failed to delete stock check'
                }
            }
            return {
                success: response.data?.success !== false,
                data: response.data?.data || null,
                message: response.data?.message || 'Stock check deleted successfully'
            }
        } catch (error) {
            return {
                success: false,
                data: null,
                message: error.response?.data?.message || 'Failed to delete stock check'
            }
        }
    }
}

export default DeleteStockCheckAction
