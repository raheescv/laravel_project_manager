import axios from 'axios'

class CreateStockCheckAction {
    async execute(formData) {
        try {
            const response = await axios.post('/inventory/stock-check/create', formData)
            return {
                success: response.data.success,
                data: response.data.data,
                message: response.data.message
            }
        } catch (error) {
            return {
                success: false,
                data: null,
                message: error.response?.data?.message || 'Failed to create stock check'
            }
        }
    }
}

export default CreateStockCheckAction
