import { fileURLToPath, URL } from 'node:url'

import vue from '@vitejs/plugin-vue'
import { defineConfig } from 'vite'

/**
 * Clean URLs (/collection/women). The folder the site is served from is set at
 * build time — VITE_BASE_PATH=/kashperia_new/ npm run build — and written into
 * index.html's <base href>, which also anchors the content's relative
 * assets/… paths on deep pages. The server must answer unknown paths with
 * index.html (public/.htaccess does this on Apache).
 */
const base = (process.env.VITE_BASE_PATH || '/').replace(/\/?$/, '/')

export default defineConfig({
  plugins: [
    vue(),
    { name: 'kashperia-base-href', transformIndexHtml: (html) => html.replaceAll('%KP_BASE%', base) },
  ],
  base,
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  server: {
    port: 5182,
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
