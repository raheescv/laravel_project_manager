import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/react/app.jsx'],
            refresh: true,
            buildDirectory: 'react/build',
        }),
        react(),
    ],

    resolve: {
        alias: {
            '@react': '/resources/js/react',   // react alias
        },
    },

    server: {
        port: 5174,
        host: '0.0.0.0',
        strictPort: true,
        https: process.env.FORCE_HTTPS || false,
        origin: `${process.env.VITE_APP_URL || 'localhost'}:5174`,
        hmr: {
            host: process.env.VITE_APP_URL || '0.0.0.0',
            port: 5174,
            protocol: process.env.FORCE_HTTPS ? 'wss' : 'ws',
        },
    },

    build: {
        outDir: 'public/react/build',
        emptyOutDir: false,
    },
});