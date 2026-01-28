import { InertiaProgress } from '@inertiajs/progress';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import Toast from 'vue-toastification';
import 'vue-toastification/dist/index.css';
import { ZiggyVue } from 'ziggy-js';
import '../css/app.css';
import './bootstrap';

// Initialize Inertia Progress
InertiaProgress.init({
    color: '#4B5563',
    showSpinner: true,
});

// Toast configuration
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
    rtl: false
};

// Initialize Inertia App
createInertiaApp({
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        // Get Ziggy config from Inertia props
        const ziggy = props.initialPage?.props?.ziggy;

        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue, ziggy)
            .use(Toast, toastOptions)
            .mount(el);
    },
});

// Initialize Alpine.js
import Alpine from 'alpinejs';
window.Alpine = Alpine;
Alpine.start();
