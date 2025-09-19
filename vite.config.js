import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import react from '@vitejs/plugin-react'
import wayfinder from '@laravel/vite-plugin-wayfinder'

export default defineConfig({
  plugins: [
    laravel({
      input: ['resources/css/app.css', 'resources/js/app.tsx'],
      ssr: 'resources/js/ssr.tsx',
      refresh: true,
    }),
    react(),
    wayfinder({
      // Configuración de Wayfinder para Laravel
      pages: 'resources/js/pages',
      layouts: 'resources/js/layouts',
      components: 'resources/js/components',
    }),
  ],
  server: {
    host: '0.0.0.0',
    port: 5173,
    hmr: {
      host: 'localhost',
    },
  },
  build: {
    outDir: 'public/build',
    sourcemap: true,
    rollupOptions: {
      output: {
        manualChunks: {
          vendor: ['react', 'react-dom'],
          inertia: ['@inertiajs/react'],
        },
      },
    },
  },
  resolve: {
    alias: {
      '@': '/resources/js',
    },
  },
})
