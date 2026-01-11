<template>
    <div class="barcode-input-wrapper">
        <div class="barcode-input-container">
            <div class="input-wrapper">
                <div class="input-icon">
                    <i class="fa fa-barcode"></i>
                </div>
                <input ref="inputRef" type="text" :value="value" @input="$emit('update:value', $event.target.value)"
                    @keyup.enter="handleEnter" class="barcode-input-field" :placeholder="placeholder"
                    autocomplete="off" />
                <button class="btn-clear" type="button" @click="clearInput" v-if="value" title="Clear">
                    <i class="fa fa-times"></i>
                </button>
            </div>
            <button class="btn-scan" type="button" @click="openScanner" title="Open barcode scanner">
                <i class="fa fa-camera me-2"></i>
                <span>Scan</span>
            </button>
        </div>

        <!-- Barcode Scanner Modal -->
        <BarcodeScanner :isOpen="isScannerOpen" @barcode-scanned="handleBarcodeScanned" @close="closeScanner" />
    </div>
</template>

<script setup>
import {
    ref,
    onMounted,
    watch
} from 'vue'
import { useToast } from 'vue-toastification'
import BarcodeScanner from '../../BarcodeScanner.vue'

const props = defineProps({
    value: {
        type: String,
        default: ''
    },
    placeholder: {
        type: String,
        default: 'Scan or enter barcode...'
    },
    autoFocus: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['update:value', 'enter', 'scan', 'barcode-scanned'])

const toast = useToast()

const inputRef = ref(null)
const isScannerOpen = ref(false)

const handleEnter = () => {
    // Read directly from input element to handle fast barcode scanner input
    const inputValue = inputRef.value?.value?.trim() || props.value?.trim()
    if (inputValue) {
        emit('update:value', inputValue)
        emit('enter', inputValue)
        inputRef.value.value = ''
        inputRef.value.focus()
    }
}

const handleScan = () => {
    // Read directly from input element to handle fast barcode scanner input
    const inputValue = inputRef.value?.value?.trim() || props.value?.trim()
    if (inputValue) {
        emit('update:value', inputValue)
        emit('scan', inputValue)
        inputRef.value.value = ''
        inputRef.value.focus()
        // add a toast notification
        toast.success('Barcode scanned successfully')
    }
}

const openScanner = () => {
    isScannerOpen.value = true
}

const closeScanner = () => {
    isScannerOpen.value = false
}

const handleBarcodeScanned = (data) => {
    const {
        code,
        product
    } = data
    emit('update:value', code)
    emit('barcode-scanned', {
        code,
        product
    })
    emit('enter', code)
    closeScanner()
}

const clearInput = () => {
    emit('update:value', '')
    if (inputRef.value) {
        inputRef.value.focus()
    }
}

watch(() => props.value, (newVal) => {
    if (!newVal && inputRef.value) {
        // Clear and refocus when value is cleared
        inputRef.value.focus()
    }
})

onMounted(() => {
    if (props.autoFocus && inputRef.value) {
        inputRef.value.focus()
    }
})
</script>

<style scoped>
.barcode-input-wrapper {
    width: 100%;
}

.barcode-input-container {
    display: flex;
    gap: 0.75rem;
    align-items: stretch;
}

.input-wrapper {
    flex: 1;
    position: relative;
    display: flex;
    align-items: center;
    background: #ffffff;
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    padding: 0.5rem 1rem;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.input-wrapper:focus-within {
    border-color: #4f46e5;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
    background: #fafafa;
}

.input-icon {
    color: #6b7280;
    margin-right: 0.75rem;
    font-size: 1.25rem;
    display: flex;
    align-items: center;
    transition: color 0.3s ease;
}

.input-wrapper:focus-within .input-icon {
    color: #4f46e5;
}

.barcode-input-field {
    flex: 1;
    border: none;
    outline: none;
    background: transparent;
    font-size: 1rem;
    color: #1f2937;
    padding: 0;
    font-weight: 500;
}

.barcode-input-field::placeholder {
    color: #9ca3af;
    font-weight: 400;
}

.btn-clear {
    background: transparent;
    border: none;
    color: #9ca3af;
    cursor: pointer;
    padding: 0.25rem 0.5rem;
    border-radius: 6px;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-left: 0.5rem;
}

.btn-clear:hover {
    background: #f3f4f6;
    color: #6b7280;
}

.btn-scan {
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
    border: none;
    color: white;
    padding: 0.75rem 1.5rem;
    border-radius: 12px;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    box-shadow: 0 4px 6px rgba(79, 70, 229, 0.25);
    white-space: nowrap;
}

.btn-scan:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(79, 70, 229, 0.35);
    background: linear-gradient(135deg, #5b52f0 0%, #8b4ff5 100%);
}

.btn-scan:active {
    transform: translateY(0);
    box-shadow: 0 2px 4px rgba(79, 70, 229, 0.25);
}

.btn-scan i {
    font-size: 1.1rem;
}

/* Responsive design */
@media (max-width: 640px) {
    .barcode-input-container {
        flex-direction: column;
    }

    .btn-scan {
        width: 100%;
        padding: 0.875rem 1.5rem;
    }

    .input-wrapper {
        padding: 0.625rem 1rem;
    }
}

/* Dark mode support (optional) */
@media (prefers-color-scheme: dark) {
    .input-wrapper {
        background: #1f2937;
        border-color: #374151;
    }

    .input-wrapper:focus-within {
        background: #111827;
        border-color: #6366f1;
    }

    .barcode-input-field {
        color: #f9fafb;
    }

    .barcode-input-field::placeholder {
        color: #6b7280;
    }

    .btn-clear:hover {
        background: #374151;
        color: #d1d5db;
    }
}
</style>
