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

   sserver: {
    port: 5174,
    host: '0.0.0.0',
    strictPort: true,
    https: process.env.FORCE_HTTPS === 'true',
    origin: process.env.VITE_REACT_APP_URL || 'http://localhost:5174',
    cors: {
        origin: ['http://localhost:8000', 'http://127.0.0.1:8000'],
        credentials: true,
    },
    hmr: {
        host: 'localhost',
        port: 5174,
        protocol: process.env.FORCE_HTTPS === 'true' ? 'wss' : 'ws',
    },
},



    build: {
        outDir: 'public/react/build',
        emptyOutDir: false,
    },
});
