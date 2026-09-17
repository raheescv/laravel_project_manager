import { fileURLToPath, URL } from 'node:url'

import vue from '@vitejs/plugin-vue'
import { defineConfig, loadEnv } from 'vite'

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '')
  const target = env.VITE_PROXY_TARGET || 'https://project_manager.test'

  return {
    plugins: [vue()],
    // Relative asset URLs + hash routing: the built bundle works from any
    // folder (https://parents.school.qa/ or /portal/) with no rewrite rules.
    base: './',
    resolve: {
      alias: {
        '@': fileURLToPath(new URL('./src', import.meta.url)),
      },
    },
    server: {
      port: 5190,
      host: true,
      proxy: {
        // Same-origin /api in dev: no CORS, no self-signed TLS trouble.
        '/api': { target, changeOrigin: true, secure: false },
        '/storage': { target, changeOrigin: true, secure: false },
      },
    },
  }
})
