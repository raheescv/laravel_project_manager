import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/inventory-opening-balance.js',
                'resources/js/stock-check.js',
                'resources/js/stock-check-show.js',
                'resources/js/general-voucher-modal.js',
                'resources/js/journal-entries-modal.js',
                'resources/js/purchase-page.js'
            ],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',   // Vue root folder
        },
    },
    server: {
        port: 5173,
        host: '0.0.0.0',
        strictPort: true,
        https: process.env.FORCE_HTTPS || false,
        origin: `${process.env.VITE_APP_URL || 'localhost'}:5173`,
        hmr: {
            host: process.env.VITE_APP_URL || 'localhost',
            port: 5173,
            protocol: process.env.FORCE_HTTPS ? 'wss' : 'ws',
        },
        cors: true,
    },

    build: {
        chunkSizeWarningLimit: 1000,
        rollupOptions: {
            output: {
                manualChunks: {
                    vendor: ['vue', 'alpinejs'],
                },
            },
        },
    },
    optimizeDeps: {
        include: ['vue', 'alpinejs'],
    },
});
