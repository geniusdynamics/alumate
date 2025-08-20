import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { resolve } from 'node:path';
import path from 'path';
import AutoImport from 'unplugin-auto-import/vite';
import Components from 'unplugin-vue-components/vite';
import { defineConfig } from 'vite';
import { visualizer } from 'rollup-plugin-visualizer';

export default defineConfig({
    define: {
        // Handle any global defines if needed
        __VUE_OPTIONS_API__: true,
        __VUE_PROD_DEVTOOLS__: false,
    },
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
            resolvers: [
                // Custom resolver to handle remaining naming conflicts
                (componentName) => {
                    // Handle specific component conflicts by prioritizing certain directories
                    const componentMappings = {
                        'InputError': 'resources/js/components/InputError.vue',
                        'AppHeader': 'resources/js/components/layout/AppHeader.vue',
                        'GuidedTour': 'resources/js/components/onboarding/GuidedTour.vue',
                        'SuccessStoryCard': 'resources/js/components/SuccessStories/SuccessStoryCard.vue',
                        'Skeleton': 'resources/js/components/ui/skeleton/Skeleton.vue',
                    }

                    if (componentMappings[componentName]) {
                        return componentMappings[componentName]
                    }
                }
            ]
        }),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './resources/js'),
            'ziggy-js': resolve(__dirname, 'vendor/tightenco/ziggy'),
        },
        // Fix for vue-leaflet package resolution issues
        dedupe: ['vue'],
    },
    build: {
        // Code splitting configuration
        rollupOptions: {
            external: (id) => {
                // Externalize problematic packages
                if (id.includes('vue-leaflet')) {
                    return true;
                }
                if (id.includes('laravel-echo')) {
                    return true;
                }
                return false;
            },
            plugins: [
                // Add visualizer plugin to analyze bundle
                visualizer({
                    filename: 'dist/stats.html',
                    open: false,
                    gzipSize: true,
                    brotliSize: true,
                }),
            ],
            output: {
                manualChunks: (id) => {
                    // Vendor chunks
                    if (id.includes('node_modules')) {
                        if (id.includes('vue') || id.includes('@inertiajs')) {
                            return 'vendor-vue'
                        }
                        if (id.includes('@headlessui') || id.includes('@heroicons') || id.includes('lucide-vue-next')) {
                            return 'vendor-ui'
                        }
                        if (id.includes('lodash') || id.includes('date-fns') || id.includes('clsx')) {
                            return 'vendor-utils'
                        }
                        if (id.includes('chart.js')) {
                            return 'vendor-charts'
                        }
                        if (id.includes('leaflet')) {
                            return 'vendor-maps'
                        }
                        if (id.includes('elasticsearch') || id.includes('search')) {
                            return 'vendor-search'
                        }
                        return 'vendor-misc'
                    }
                    
                    // Feature-based chunks
                    if (id.includes('homepage')) {
                        return 'feature-homepage'
                    }
                    if (id.includes('Social') || id.includes('Timeline') || id.includes('Post')) {
                        return 'feature-social'
                    }
                    if (id.includes('Alumni') || id.includes('Directory')) {
                        return 'feature-alumni'
                    }
                    if (id.includes('Career') || id.includes('Job') || id.includes('Mentorship')) {
                        return 'feature-career'
                    }
                    if (id.includes('Event') || id.includes('Calendar')) {
                        return 'feature-events'
                    }
                    if (id.includes('Search') || id.includes('Filter')) {
                        return 'feature-search'
                    }
                    if (id.includes('Performance') || id.includes('Analytics')) {
                        return 'feature-analytics'
                    }
                    if (id.includes('Mobile') || id.includes('PWA')) {
                        return 'feature-mobile'
                    }
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
            'clsx',
            'leaflet'
        ],
        exclude: [
            // Exclude large libraries that should be loaded on demand
            'chart.js',
            'elasticsearch',
            'pdf-lib'
        ]
    },
    // Tree shaking configuration
    esbuild: {
        treeShaking: true,
        // Remove console.log in production
        drop: process.env.NODE_ENV === 'production' ? ['console', 'debugger'] : [],
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
