<template>
    <!-- Barcode Scanner Modal -->
    <div v-if="isOpen"
        class="scanner-modal position-fixed top-0 start-0 w-100 h-100 bg-dark bg-opacity-75 d-flex justify-content-center align-items-center"
        style="z-index: 9999;">
        <div class="position-relative bg-white rounded p-2" style="width: 400px; max-width: 90%;">
            <!-- Scanner Video -->
            <div style="width: 100%; height: 300px; overflow: hidden; position: relative;">
                <div ref="scannerContainer" :id="scannerContainerId" style="width: 100%; height: 100%;"></div>
                <div
                    style=" position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 2px dashed red; box-sizing: border-box; pointer-events: none; ">
                </div>
            </div>

            <!-- Manual Input -->
            <div class="mt-2">
                <label class="form-label small">Enter barcode manually</label>
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="Type barcode and press Enter"
                        v-model="manualBarcode" @keydown.enter="handleManualBarcode" ref="manualBarcodeInput" />
                </div>
            </div>

            <button class="btn btn-danger btn-sm mt-2 w-100" @click="close">
                Close Scanner
            </button>
        </div>
    </div>
</template>

<script setup>
import {
    ref,
    watch,
    onMounted,
    onUnmounted,
    nextTick
} from 'vue'
import axios from 'axios'
import {
    Html5Qrcode
} from 'html5-qrcode'
import { useToast } from 'vue-toastification'

const props = defineProps({
    isOpen: {
        type: Boolean,
        default: false
    },
    onBarcodeScanned: {
        type: Function,
        default: null
    },
    /** When true, emit raw barcode only (no API lookup). Parent handles lookup. */
    emitRawBarcode: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['barcode-scanned', 'close'])

const toast = useToast()

const manualBarcode = ref('')
const scannerContainer = ref(null)
const html5QrCode = ref(null)
const scannedCode = ref('')
const scannerContainerId = 'barcode-scanner-' + Math.random().toString(36).slice(2, 11)

const close = () => {
    stopScanner()
    emit('close')
    manualBarcode.value = ''
    scannedCode.value = ''
}

const startScanner = () => {
    if (!scannerContainer.value) return

    const containerId = scannerContainer.value?.id || scannerContainerId

    try {
        html5QrCode.value = new Html5Qrcode(containerId)

        html5QrCode.value.start({
            facingMode: 'environment'
        }, {
            fps: 10,
            qrbox: {
                width: 250,
                height: 250
            },
            aspectRatio: 1.0,
        },
            (decodedText, decodedResult) => {
                console.log('📸 SCANNER RESULT RAW:', decodedResult)

                if (!decodedText) {
                    console.log('❌ No decoded text from scanner')
                    return
                }

                const code = decodedText
                console.log('📦 RAW BARCODE:', code)

                const clean = code.replace(/[^a-zA-Z0-9]/g, '')
                console.log('🔎 CLEAN BARCODE:', clean)

                if (clean.length >= 4 && clean.length <= 30) {
                    console.log('✅ Valid barcode scanned:', clean)
                    applyScannedCode(clean).then(() => {
                        close()
                    })
                } else {
                    console.log('❌ Barcode invalid length:', clean.length)
                }
            },
            (errorMessage) => {
                // Ignore errors - scanner will keep trying
            }
        ).catch((err) => {
            console.error('Error starting scanner:', err)
        })
    } catch (err) {
        console.error('Error initializing scanner:', err)
    }
}

const stopScanner = () => {
    if (html5QrCode.value) {
        html5QrCode.value.stop().then(() => {
            html5QrCode.value.clear()
            html5QrCode.value = null
        }).catch((err) => {
            console.error('Error stopping scanner:', err)
            html5QrCode.value = null
        })
    }
}

const applyScannedCode = async (code) => {
    beep()

    if (!code) return

    scannedCode.value = code

    // Emit raw barcode for parent to handle lookup (e.g. tailoring products)
    if (props.emitRawBarcode) {
        emit('barcode-scanned', { code })
        if (props.onBarcodeScanned) props.onBarcodeScanned({ code })
        return Promise.resolve()
    }

    try {
        const checkRes = await axios.get('/inventory/product/getProduct', {
            params: { productBarcode: code, limit: 1, page: 1 }
        })

        const found = Array.isArray(checkRes.data.data) && checkRes.data.data.length > 0

        if (!found) {
            showNotification(`❌ Barcode not found: ${code}`, 'danger')
            scannedCode.value = ''
            return Promise.resolve()
        }

        const product = normalizeProduct(checkRes.data.data[0])
        emit('barcode-scanned', { code, product })
        if (props.onBarcodeScanned) props.onBarcodeScanned({ code, product })
        showNotification(`✅ Barcode found: ${code}`, 'success')
        return Promise.resolve()

    } catch (err) {
        console.error('Error checking barcode:', err)
        showNotification(`❌ Error checking barcode: ${code}`, 'danger')
        scannedCode.value = ''
        return Promise.resolve()
    }
}

const normalizeProduct = (product) => {
    return {
        id: product.inventory_id,
        quantity: product.quantity,
        name: product.name,
        code: product.code,
        barcode: product.barcode,
    }
}

const handleManualBarcode = () => {
    const code = manualBarcode.value.trim().replace(/[^a-zA-Z0-9]/g, '')
    if (code) {
        applyScannedCode(code).then(() => {
            close()
        })
    }
}

const showNotification = (message, type = 'info') => {
    if (type === 'danger' || type === 'error') {
        toast.error(message)
    } else if (type === 'success') {
        toast.success(message)
    } else if (type === 'warning') {
        toast.warning(message)
    } else {
        toast.info(message)
    }
}

const beep = () => {
    try {
        const audio = new Audio("/audio/beep_short.ogg")
        audio.play().catch(err => console.log('Audio play failed:', err))
    } catch (err) {
        console.log('Audio not available:', err)
    }
}

// Watch for isOpen prop changes
watch(() => props.isOpen, async (newValue) => {
    if (newValue) {
        manualBarcode.value = ''
        scannedCode.value = ''

        await nextTick()

        // Start scanner
        if (scannerContainer.value) {
            startScanner()
        }

        setTimeout(() => {
            const input = document.querySelector('.scanner-modal input[type="text"]')
            if (input) input.focus()
        }, 100)
    } else {
        stopScanner()
    }
})

// Lifecycle hooks
onUnmounted(() => {
    stopScanner()
})
</script>

<style scoped>
.scanner-modal {
    z-index: 9999;
}
</style>
