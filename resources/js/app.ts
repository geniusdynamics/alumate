import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import { initializeTheme } from './composables/useAppearance';
import { performanceService } from './services/PerformanceService';
import { preloadService } from './services/PreloadService';
import './pwa.js';

// Extend ImportMeta interface for Vite...
declare module 'vite/client' {
    interface ImportMetaEnv {
        readonly VITE_APP_NAME: string;
        readonly VITE_CDN_URL?: string;
        [key: string]: string | boolean | undefined;
    }

    interface ImportMeta {
        readonly env: ImportMetaEnv;
        readonly glob: <T>(pattern: string) => Record<string, () => Promise<T>>;
    }
}

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Start performance monitoring
performanceService.markStart('app-initialization')

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => {
        // Mark component resolution start
        performanceService.markStart(`resolve-${name}`)
        
        return resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob<DefineComponent>('./Pages/**/*.vue')).then(component => {
            // Mark component resolution end
            performanceService.markEnd(`resolve-${name}`)
            
            // Preload next likely pages based on current page
            const pageName = name.toLowerCase()
            if (pageName.includes('homepage')) {
                preloadService.preloadNextPageResources('homepage')
            }
            
            return component
        })
    },
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)

        // Mount the app
        performanceService.markStart('app-mount')
        app.mount(el)
        performanceService.markEnd('app-mount')
        
        // Complete app initialization
        performanceService.markEnd('app-initialization')
        
        return app
    },
    progress: {
        color: '#4B5563',
        showSpinner: true,
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// Report performance metrics after page is fully loaded
window.addEventListener('load', () => {
    // Wait a bit for all resources to finish loading
    setTimeout(() => {
        performanceService.reportMetrics()
    }, 1000)
});
