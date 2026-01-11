import axios from 'axios'

class GetStockChecksAction {
    async execute(params = {}) {
        try {
            const response = await axios.get('/inventory/stock-check/list', {
                params,
                headers: {
                    'Accept': 'application/json'
                }
            })
            // Check if the API response indicates success or failure
            if (response.data?.success === false) {
                return {
                    success: false,
                    data: [],
                    message: response.data?.message || 'Failed to load stock checks'
                }
            }
            return {
                success: response.data?.success !== false,
                data: response.data?.data || [],
                message: response.data?.message || 'Stock checks retrieved successfully'
            }
        } catch (error) {
            return {
                success: false,
                data: [],
                message: error.response?.data?.message || 'Failed to load stock checks'
            }
        }
    }
}

export default GetStockChecksAction
