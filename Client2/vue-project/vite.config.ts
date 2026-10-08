import { fileURLToPath, URL } from 'node:url'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueJsx from '@vitejs/plugin-vue-jsx'
import vueDevTools from 'vite-plugin-vue-devtools'

export default defineConfig({
  plugins: [
    vue({
      template: {
        compilerOptions: {
          // Enable production mode optimizations
          isCustomElement: (tag) => tag.startsWith('ion-'),
        },
      },
    }),
    vueJsx(),
    // Only enable DevTools when explicitly requested via env variable
    ...(process.env.VITE_DEVTOOLS === 'true' ? [vueDevTools()] : []),
  ],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  build: {
    target: 'esnext',
    minify: 'esbuild',
    rollupOptions: {
      output: {
        manualChunks: (id) => {
          if (id.includes('node_modules')) {
            if (id.includes('vue') || id.includes('pinia') || id.includes('vue-router')) {
              return 'vendor'
            }
            if (id.includes('lucide-vue-next')) {
              return 'ui-libs'
            }
          }
        },
      },
    },
    chunkSizeWarningLimit: 1200,
  },
  server: {
    warmup: {
      clientFiles: ['./src/main.ts', './src/App.vue', './src/router/index.ts'],
    },
    fs: {
      strict: false,
    },
  },
  optimizeDeps: {
    include: ['vue', 'vue-router', 'pinia', 'axios'],
  },
})
