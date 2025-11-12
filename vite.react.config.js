import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
  plugins: [
    laravel({
      input: ['resources/react/app.jsx'],
      refresh: true,
    }),
    react(),
  ],
 server: {
  port: 5174,              
  host: 'localhost',
  strictPort: true,         // ✅ Ensures no port conflict
  https: false,
  hmr: {
      host: process.env.VITE_REACT_APP_URL || 'localhost',
      port: 5174,
      protocol: 'ws',       // ✅ Add HMR config so React hot reload works
  },
},

   build: {
        outDir: 'public/react/build',
        emptyOutDir: true,
    },
});
