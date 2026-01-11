import axios from 'axios'

class ScanBarcodeAction {
    async execute(stockCheckId, barcode) {
        try {
            const response = await axios.post(`/inventory/stock-check/${stockCheckId}/scan-barcode`, {
                barcode: barcode
            })
            return {
                success: response.data.success,
                data: response.data.data,
                message: response.data.message
            }
        } catch (error) {
            return {
                success: false,
                data: null,
                message: error.response?.data?.message || 'Barcode not found'
            }
        }
    }
}

export default ScanBarcodeAction
