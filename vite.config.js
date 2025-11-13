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
            '@': '/resources/js',
        },
    },
    server: {
    port: 5173,
    host: '0.0.0.0',
    strictPort: true,       // ✅ Changed to true, so it will fail if port 5173 is busy instead of auto-choosing another
    https: false,           // ✅ Force HTTPS removed (was using env variable)
    hmr: {
        host: process.env.VITE_APP_URL || 'localhost',
        port: 5173,
        protocol: 'ws',      // ✅ Changed from 'wss' to 'ws' for local dev
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