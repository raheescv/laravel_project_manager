import { createPinia } from 'pinia'
import { createApp } from 'vue'

import App from './App.vue'
import './assets/reference.css'
import './assets/app.css'
import router from './router'

createApp(App).use(createPinia()).use(router).mount('#app')
