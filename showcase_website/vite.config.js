import { fileURLToPath, URL } from 'node:url'

import vue from '@vitejs/plugin-vue'
import { defineConfig } from 'vite'

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  server: {
    port: 5180,
    proxy: {
      // Avoid CORS + self-signed TLS issues in dev: the app calls same-origin
      // /api/... and Vite forwards to the Valet host.
      '/api': {
        target: process.env.VITE_PROXY_TARGET || 'https://project_manager.test',
        changeOrigin: true,
        secure: false,
      },
      '/storage': {
        target: process.env.VITE_PROXY_TARGET || 'https://project_manager.test',
        changeOrigin: true,
        secure: false,
      },
    },
  },
})
