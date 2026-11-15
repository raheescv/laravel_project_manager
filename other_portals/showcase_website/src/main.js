import { createPinia } from 'pinia'
import { createApp } from 'vue'

import App from './App.vue'
import './assets/app.css'
import { loadBranding } from './branding'
import { initLang } from './i18n'
import router from './router'

// Language + direction come from localStorage (sr.lang) before first paint so
// the Arabic font stack and RTL layout never flash.
initLang()

// Admin-configured accent colour, logo and contact details. Best-effort: the
// static SIZE RUN blue in app.css stays in place if the API is unreachable.
loadBranding()

createApp(App).use(createPinia()).use(router).mount('#app')
