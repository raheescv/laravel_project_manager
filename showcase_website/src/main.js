import { createPinia } from 'pinia'
import { createApp } from 'vue'

import App from './App.vue'
import './assets/main.css'
import { loadBranding } from './branding'
import router from './router'

// Apply admin-configured storefront branding before mount; falls back to the
// static SIZE RUN blue in main.css if the API is unreachable.
loadBranding()

createApp(App).use(createPinia()).use(router).mount('#app')
