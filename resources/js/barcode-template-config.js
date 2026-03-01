import { createApp } from 'vue'
import Toast from 'vue-toastification'
import 'vue-toastification/dist/index.css'
import BarcodeTemplateDesigner from './components/Inventory/Barcode/BarcodeTemplateDesigner.vue'

const toastOptions = {
    position: 'top-right',
    timeout: 3000,
    closeOnClick: true,
    pauseOnFocusLoss: true,
    pauseOnHover: true,
    draggable: true,
    draggablePercent: 0.6,
    showCloseButtonOnHover: false,
    hideProgressBar: false,
    closeButton: 'button',
    icon: true,
    rtl: false,
}

function initBarcodeTemplateDesigner() {
    const element = document.getElementById('barcode-template-designer')

    if (!element || window.barcodeTemplateDesignerApp) {
        return
    }

    const app = createApp(BarcodeTemplateDesigner, {
        templateKey: element.dataset.templateKey,
        listUrl: element.dataset.listUrl,
        dataUrl: element.dataset.dataUrl,
        saveUrl: element.dataset.saveUrl,
        resetUrl: element.dataset.resetUrl,
        csrf: element.dataset.csrf,
    })

    app.use(Toast, toastOptions)
    window.barcodeTemplateDesignerApp = app.mount(element)
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initBarcodeTemplateDesigner)
} else {
    initBarcodeTemplateDesigner()
}
