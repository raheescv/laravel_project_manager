import { createApp } from 'vue'
import LoginScreen from './components/Auth/LoginScreen.vue'

const el = document.getElementById('login-app')

if (el) {
    createApp(LoginScreen, { screen: JSON.parse(el.dataset.screen) }).mount(el)
}
