import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

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
        hmr: {
            host: process.env.VITE_REACT_APP_URL || '0.0.0.0',
            port: 5174,
            protocol: 'ws',
        },
    },

    build: {
        outDir: 'public/react/build',
        emptyOutDir: false,
    },
});