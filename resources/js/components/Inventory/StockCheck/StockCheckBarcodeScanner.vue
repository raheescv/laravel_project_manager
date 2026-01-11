<template>
    <div class="card shadow-sm border mb-4 border-primary rounded">
        <div class="card-body">
            <h5 class="card-title">Barcode Scanner</h5>
            <BarcodeInput :value="barcode" :auto-focus="true" @scan="handleScan" @enter="handleScan" />
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { useToast } from 'vue-toastification'
import BarcodeInput from './BarcodeInput.vue'
import ScanBarcodeAction from './ScanBarcodeAction.js'

const props = defineProps({
    stockCheckId: {
        type: Number,
        required: true
    }
})

const emit = defineEmits(['scan-success', 'scan-error'])

const toast = useToast()
const action = new ScanBarcodeAction()
const barcode = ref('')

const handleScan = async (barcodeValue) => {
    if (!barcodeValue || !barcodeValue.trim()) {
        return
    }

    try {
        const result = await action.execute(props.stockCheckId, barcodeValue.trim())
        if (result.success) {
            barcode.value = ''
            emit('scan-success', result.data)
        } else {
            emit('scan-error', result.message || 'Barcode not found')
        }
    } catch (error) {
        emit('scan-error', error.message || 'Barcode scan failed')
    }
}
</script>

<style scoped>
.stock-check-barcode-scanner {
    margin-bottom: 1rem;
}
</style>
