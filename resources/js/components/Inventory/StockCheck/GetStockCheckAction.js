import axios from 'axios'

class GetStockCheckAction {
    async execute(stockCheckId) {
        try {
            const response = await axios.get(`/inventory/stock-check/${stockCheckId}`, {
                headers: {
                    'Accept': 'application/json'
                }
            })
            // Check if the API response indicates success or failure
            if (response.data?.success === false) {
                return {
                    success: false,
                    data: null,
                    message: response.data?.message || 'Failed to load stock check'
                }
            }
            return {
                success: response.data?.success !== false,
                data: response.data?.data || response.data || {},
                message: response.data?.message || 'Stock check retrieved successfully'
            }
        } catch (error) {
            return {
                success: false,
                data: null,
                message: error.response?.data?.message || 'Failed to load stock check'
            }
        }
    }
}

export default GetStockCheckAction
