import { createApp } from 'vue'

import App from './App.vue'
import './assets/app.css'
import router from './router'
import { loadSchool } from './school'

// School name, logo and theme colour (cached per device, refreshed in the background).
loadSchool()

createApp(App).use(router).mount('#app')
