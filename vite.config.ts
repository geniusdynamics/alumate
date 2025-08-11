import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { resolve } from 'node:path';
import path from 'path';
import AutoImport from 'unplugin-auto-import/vite';
import Components from 'unplugin-vue-components/vite';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.ts'],
            ssr: 'resources/js/ssr.ts',
            refresh: true,
            detectTls: false,
            valetTls: false,
        }),

        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        AutoImport({
            imports: ['vue', '@vueuse/core'],
            dirs: [
                './resources/js/services/**',
                './resources/js/utils/**',
                './resources/js/layouts/**',
                './resources/js/stores/**',
                './resources/js/composables/**',
            ],
            viteOptimizeDeps: true,
            dts: true,
            vueTemplate: true,
            dirsScanOptions: {
                types: true,
            },
            // Exclude problematic auto-form index exports to prevent duplicates
            exclude: [
                /\/auto-form\/index\.ts$/,
                /\/form\/index\.ts$/,
                /\/sidebar\/index\.ts$/
            ]
        }),
        Components({
            dts: true,
            dirs: ['resources/js/components/**', 'resources/js/layouts/**'],
            deep: true,
        }),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './resources/js'),
            'ziggy-js': resolve(__dirname, 'vendor/tightenco/ziggy'),
        },
    },
    build: {
        // Code splitting configuration
        rollupOptions: {
            output: {
                manualChunks: {
                    // Vendor chunks
                    'vendor-vue': ['vue', '@inertiajs/vue3'],
                    'vendor-ui': ['@headlessui/vue', '@heroicons/vue', 'lucide-vue-next'],
                    'vendor-utils': ['lodash-es', 'date-fns', 'clsx'],
                    'vendor-charts': ['chart.js'],
                    'vendor-maps': ['leaflet', 'vue-leaflet'],
                    
                    // Homepage chunks
                    'homepage-core': [
                        './resources/js/components/homepage/HeroSection.vue',
                        './resources/js/components/homepage/AudienceSelector.vue',
                        './resources/js/components/homepage/SocialProofSection.vue'
                    ],
                    'homepage-features': [
                        './resources/js/components/homepage/FeaturesShowcase.vue',
                        './resources/js/components/homepage/PlatformPreview.vue',
                        './resources/js/components/homepage/ValueCalculator.vue'
                    ],
                    'homepage-institutional': [
                        './resources/js/components/homepage/InstitutionalFeatures.vue',
                        './resources/js/components/homepage/AdminDashboardPreview.vue',
                        './resources/js/components/homepage/BrandedAppsShowcase.vue'
                    ],
                    'homepage-conversion': [
                        './resources/js/components/homepage/ConversionCTAs.vue',
                        './resources/js/components/homepage/PricingSection.vue',
                        './resources/js/components/homepage/TrustIndicators.vue'
                    ]
                },
                // Optimize chunk sizes
                chunkFileNames: (chunkInfo) => {
                    const facadeModuleId = chunkInfo.facadeModuleId
                    if (facadeModuleId) {
                        if (facadeModuleId.includes('homepage')) {
                            return 'assets/homepage/[name]-[hash].js'
                        }
                        if (facadeModuleId.includes('components')) {
                            return 'assets/components/[name]-[hash].js'
                        }
                    }
                    return 'assets/[name]-[hash].js'
                }
            }
        },
        // Asset optimization
        assetsInlineLimit: 4096, // Inline assets smaller than 4kb
        cssCodeSplit: true, // Split CSS into separate files
        sourcemap: process.env.NODE_ENV === 'development',
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: process.env.NODE_ENV === 'production',
                drop_debugger: process.env.NODE_ENV === 'production',
            },
        },
    },
    // Performance optimizations
    optimizeDeps: {
        include: [
            'vue',
            '@inertiajs/vue3',
            '@headlessui/vue',
            '@heroicons/vue',
            'lodash-es',
            'date-fns',
            'clsx'
        ],
        exclude: [
            // Exclude large libraries that should be loaded on demand
            'chart.js',
            'leaflet',
            'vue-leaflet'
        ]
    },
    // Server configuration for development
    server: {
        host: '127.0.0.1', // Use 127.0.0.1 to match Laravel
        port: 5100,
        strictPort: true,
        hmr: {
            overlay: false,
            port: 5100,
            host: '127.0.0.1'
        },
        cors: {
            origin: ['http://127.0.0.1:8080', 'http://localhost:8080'],
            credentials: true
        },
        origin: 'http://127.0.0.1:5100',
        watch: {
            usePolling: true
        }
    }
});
