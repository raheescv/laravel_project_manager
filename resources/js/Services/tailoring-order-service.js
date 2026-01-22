import axios from 'axios';

export const tailoringOrderService = {
    /**
     * Fetch orders with filters and pagination
     * @param {Object} params - Filter parameters
     * @param {string|null} url - Specific URL to fetch (for pagination)
     * @returns {Promise<Object>} - Response data
     */
    async getOrders(params = {}, url = null) {
        // Determine the endpoint. 
        // If a full URL is provided (e.g. from pagination), use it.
        // Otherwise use the default index route.
        // We assume route() is globally available via Ziggy
        
        // Fix for route name: Check if route exists, otherwise fallback to current location
        let endpoint = url;
        
        if (!endpoint) {
            if (typeof route !== 'undefined' && route().has('tailoring::order::index')) {
                endpoint = route('tailoring::order::index');
            } else {
                endpoint = window.location.pathname;
            }
        }

        try {
            const response = await axios.get(endpoint, {
                params: url ? {} : params, // If url is full (pagination), it usually contains page param. We might need to merge filters if they aren't in the link.
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            return response.data;
        } catch (error) {
            console.error('TailoringOrderService Error:', error);
            throw error;
        }
    }
};
