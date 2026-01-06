<template>
    <!-- Barcode Scanner Modal -->
    <div v-if="isOpen"
        class="scanner-modal position-fixed top-0 start-0 w-100 h-100 bg-dark bg-opacity-75 d-flex justify-content-center align-items-center"
        style="z-index: 9999;">
        <div class="position-relative bg-white rounded p-2" style="width: 400px; max-width: 90%;">
            <!-- Scanner Video -->
            <div style="width: 100%; height: 300px; overflow: hidden; position: relative;">
                <div ref="scannerContainer" id="barcode-scanner-container" style="width: 100%; height: 100%;"></div>
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

const props = defineProps({
    isOpen: {
        type: Boolean,
        default: false
    },
    onBarcodeScanned: {
        type: Function,
        default: null
    }
})

const emit = defineEmits(['barcode-scanned', 'close'])

const manualBarcode = ref('')
const scannerContainer = ref(null)
const html5QrCode = ref(null)
const scannedCode = ref('')

const close = () => {
    stopScanner()
    emit('close')
    manualBarcode.value = ''
    scannedCode.value = ''
}

const startScanner = () => {
    if (!scannerContainer.value) return

    const containerId = scannerContainer.value.id || 'barcode-scanner-container'

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
    console.log('🚀 applyScannedCode() START:', code)
    beep()

    if (!code) return

    scannedCode.value = code

    const checkParams = {
        productBarcode: code,
        limit: 1,
        page: 1,
    }

    try {
        console.log('📡 Checking barcode with API:', checkParams)
        const checkRes = await axios.get('/inventory/product/getProduct', {
            params: checkParams
        })
        console.log('📥 API response:', checkRes.data)

        const found = Array.isArray(checkRes.data.data) && checkRes.data.data.length > 0

        if (!found) {
            showNotification(`❌ Barcode not found: ${code}`, 'danger')
            console.log('❌ Barcode not found in database:', code)
            scannedCode.value = ''
            return Promise.resolve()
        }

        // If found, emit the scanned barcode
        const product = normalizeProduct(checkRes.data.data[0])
        emit('barcode-scanned', {
            code,
            product
        })

        if (props.onBarcodeScanned) {
            props.onBarcodeScanned({
                code,
                product
            })
        }

        console.log('✅ Barcode found, product data:', product)
        showNotification(`✅ Barcode found: ${code}`, 'success')

        return Promise.resolve()

    } catch (err) {
        console.error('🔥 ERROR IN applyScannedCode():', err)
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
    const el = document.createElement('div')
    el.className = `alert alert-${type} alert-dismissible fade show position-fixed`
    el.style.cssText = 'top:20px; right:20px; z-index:9999; min-width:260px;'
    el.innerHTML = `${message} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>`
    document.body.appendChild(el)
    setTimeout(() => {
        if (el.parentNode) el.remove()
    }, 3500)
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
