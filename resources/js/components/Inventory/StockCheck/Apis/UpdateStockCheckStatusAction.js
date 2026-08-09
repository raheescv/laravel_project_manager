import axios from 'axios'

class UpdateStockCheckStatusAction {
    async execute(stockCheckId, status) {
        try {
            const response = await axios.put(`/inventory/stock-check/${stockCheckId}/status`, {
                status: status
            })
            return {
                success: response.data?.success !== false,
                data: response.data?.data || null,
                message: response.data?.message || 'Status updated successfully'
            }
        } catch (error) {
            return {
                success: false,
                data: null,
                message: error.response?.data?.message || 'Failed to update status'
            }
        }
    }
}

export default UpdateStockCheckStatusAction
