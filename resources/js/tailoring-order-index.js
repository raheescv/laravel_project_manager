import { createApp } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import Toast from 'vue-toastification';
import 'vue-toastification/dist/index.css';
import Index from './Pages/Tailoring/Order/Index.vue';

document.addEventListener('DOMContentLoaded', () => {
    const mountEl = document.getElementById('orderList');

    if (mountEl) {
        const orders = window.ordersData || {};
        
        const app = createApp(Index, {
            orders: orders
        });

        // Register plugins and components
        app.use(ZiggyVue, window.Ziggy);
        app.use(Toast);
        
        app.mount(mountEl);
    } else {
        console.error('Target element #orderList not found.');
    }
});
