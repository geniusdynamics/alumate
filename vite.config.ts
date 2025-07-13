import tailwindcss from '@tailwindcss/vite';
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
        }),
        tailwindcss(),
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
                './resources/js/components/**',
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
});
