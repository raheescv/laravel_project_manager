import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
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
        hmr: {
            host: process.env.VITE_APP_URL || 'localhost',
            port: 5173,
            protocol: 'ws',
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
