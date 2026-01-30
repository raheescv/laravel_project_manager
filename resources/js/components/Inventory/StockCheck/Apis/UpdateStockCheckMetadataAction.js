import axios from 'axios'

class UpdateStockCheckMetadataAction {
    async execute(stockCheckId, data) {
        try {
            const response = await axios.put(`/inventory/stock-check/${stockCheckId}/metadata`, data)
            return {
                success: response.data.success,
                data: response.data.data,
                message: response.data.message
            }
        } catch (error) {
            return {
                success: false,
                data: null,
                message: error.response?.data?.message || 'Failed to update stock check'
            }
        }
    }
}

export default UpdateStockCheckMetadataAction
