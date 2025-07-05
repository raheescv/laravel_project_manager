import vue from '@vitejs/plugin-vue';
import fs from 'fs';
import laravel from 'laravel-vite-plugin';
import os from 'os';
import path from 'path';
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
        host: true,
        strictPort: true,
        hmr: {
            host: 'project_manager.test'
        },
        https: {
            key: fs.readFileSync(
                path.join(os.homedir(), '.config/valet/Certificates/project_manager.test.key')
            ),
            cert: fs.readFileSync(
                path.join(os.homedir(), '.config/valet/Certificates/project_manager.test.crt')
            ),
        },
    },
});
