<template>
    <div class="barcode-input-wrapper">
        <div class="d-flex gap-2 align-items-stretch flex-column flex-sm-row">
            <div class="input-group flex-grow-1">
                <span class="input-group-text bg-white text-muted">
                    <i class="fa fa-barcode"></i>
                </span>
                <input ref="inputRef" type="text" :value="value" @input="handleInput" @keyup.enter="handleEnter"
                    class="form-control border-start-0 border-end-0" :placeholder="placeholder" autocomplete="off" />
                <button class="btn btn-outline-secondary border-start-0" type="button" @click="clearInput" v-if="value"
                    title="Clear">
                    <i class="fa fa-times"></i>
                </button>
            </div>
            <button class="btn btn-primary d-flex align-items-center justify-content-center gap-2" type="button"
                @click="openScanner" title="Open barcode scanner">
                <i class="fa fa-camera"></i>
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
import BarcodeScanner from '../../../BarcodeScanner.vue'

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
    },
    autoEnterDelay: {
        type: Number,
        default: 1000
    }
})

const emit = defineEmits(['update:value', 'enter', 'scan', 'barcode-scanned'])

const toast = useToast()

const inputRef = ref(null)
const isScannerOpen = ref(false)
const autoEnterTimer = ref(null)

const handleInput = (event) => {
    const value = event.target.value
    emit('update:value', value)

    if (autoEnterTimer.value) {
        clearTimeout(autoEnterTimer.value)
    }

    if (value) {
        autoEnterTimer.value = setTimeout(() => {
            handleEnter()
        }, props.autoEnterDelay)
    }
}

const handleEnter = () => {
    if (autoEnterTimer.value) {
        clearTimeout(autoEnterTimer.value)
        autoEnterTimer.value = null
    }

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
