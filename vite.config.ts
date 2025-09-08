import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'

export default defineConfig({
  plugins: [
    laravel({
      input: [
        'resources/css/app.css',
        'resources/css/mobile.css',
        'resources/js/app.ts'
      ],
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
  build: {
    rollupOptions: {
      output: {
        manualChunks: {
          // Vendor chunk for third-party libraries
          vendor: ['vue', '@inertiajs/vue3', 'axios'],

          // Utility libraries
          utils: [
            'lodash-es',
            'date-fns'
          ],

          // UI Components
          ui: [
            '@headlessui/vue',
            'lucide-vue-next',
            '@floating-ui/vue'
          ],

          // Performance monitoring
          performance: []
        },

        // Code splitting for large components
        chunkFileNames: (chunkInfo) => {
          const facadeModuleId = chunkInfo.facadeModuleId
            ? chunkInfo.facadeModuleId.split('/').pop()?.replace(/\.\w+$/, '')
            : null

          // Split large component libraries into separate chunks
          if (facadeModuleId && facadeModuleId.includes('ComponentLibrary')) {
            return `js/components-${facadeModuleId}.[hash].js`
          }

          // Admin pages chunk
          if (facadeModuleId && facadeModuleId.includes('Admin')) {
            return 'js/admin-[hash].js'
          }

          // Analytics components chunk
          if (facadeModuleId && facadeModuleId.includes('Analytics')) {
            return 'js/analytics-[hash].js'
          }

          // Default chunk naming
          return 'js/[name]-[hash].js'
        },

        // Asset chunk naming
        assetFileNames: (assetInfo) => {
          const info = assetInfo.name?.split('.') ?? []
          const extType = info[info.length - 1] ?? ''

          // Images - optimized paths
          if (/\.(png|jpe?g|svg|gif|tiff|bmp|ico)$/i.test(assetInfo.name ?? '')) {
            return `images/[name].[hash][extname]`
          }

          // Fonts - optimized paths
          if (/\.(woff2?|eot|ttf|otf)$/i.test(assetInfo.name ?? '')) {
            return `fonts/[name].[hash][extname]`
          }

          // CSS chunks
          if (/\.css$/.test(assetInfo.name ?? '')) {
            return `css/[name]-[hash][extname]`
          }

          return `[ext]/${extType}/[name]-[hash][extname]`
        }
      }
    },

    // Bundle optimization settings
    chunkSizeWarningLimit: 1000, // Increase to reduce warnings
    minify: 'esbuild',
    cssMinify: true,
    sourcemap: process.env.NODE_ENV === 'development',
    target: 'es2015', // Target modern browsers

    // Compression
    reportCompressedSize: false, // Speed up builds in dev
  },

  resolve: {
    alias: {
      '@': resolve(__dirname, 'resources/js'),
      '@components': resolve(__dirname, 'resources/js/components'),
      '@pages': resolve(__dirname, 'resources/js/Pages'),
      '@services': resolve(__dirname, 'resources/js/services'),
    },
  },

  server: {
    host: '127.0.0.1',
    port: 5176,
    hmr: {
      host: '127.0.0.1',
      port: 5176,
    },
    origin: 'http://127.0.0.1:5176',
    cors: {
      origin: ['http://127.0.0.1:8001', 'http://localhost:8001', 'http://127.0.0.1:8080', 'http://localhost:8080', 'http://127.0.0.1:8081', 'http://localhost:8081'],
      credentials: true,
    },
    proxy: {
      '/api': {
        target: 'http://127.0.0.1:8001',
        changeOrigin: true,
        secure: false,
      },
    },
  },

  optimizeDeps: {
    include: [
      'vue',
      '@inertiajs/vue3',
      'axios',
      '@headlessui/vue',
      'lucide-vue-next'
    ],
    exclude: [
      // Exclude large libraries from pre-bundling
      'tinymce'
    ]
  },

  css: {
    postcss: './postcss.config.js',
    preprocessorOptions: {
      scss: {
        additionalData: `@import "resources/css/variables.scss";`,
      },
    },
  },
})
