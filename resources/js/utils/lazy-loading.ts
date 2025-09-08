/**
 * Lazy Loading Utilities for Performance Optimization
 */

import { defineAsyncComponent, type AsyncComponentLoader, type Component } from 'vue'

/**
 * Create a lazy-loaded component with loading and error states
 */
export function createLazyComponent(
    loader: AsyncComponentLoader,
    options: {
        loadingComponent?: Component
        errorComponent?: Component
        delay?: number
        timeout?: number
        suspensible?: boolean
    } = {}
) {
    return defineAsyncComponent({
        loader,
        loadingComponent: options.loadingComponent,
        errorComponent: options.errorComponent,
        delay: options.delay ?? 200,
        timeout: options.timeout ?? 10000,
        suspensible: options.suspensible ?? false,
    })
}

/**
 * Preload a component for better UX
 */
export function preloadComponent(loader: AsyncComponentLoader): Promise<Component> {
    return loader()
}

/**
 * Lazy load route components with automatic chunk naming
 */
export function lazyRoute(path: string, chunkName?: string) {
    return () => {
        const componentPath = path.startsWith('./') ? path : `./Pages/${path}.vue`
        
        return import(/* @vite-ignore */ componentPath)
    }
}

/**
 * Create intersection observer for lazy loading elements
 */
export function createIntersectionObserver(
    callback: IntersectionObserverCallback,
    options: IntersectionObserverInit = {}
): IntersectionObserver {
    const defaultOptions: IntersectionObserverInit = {
        root: null,
        rootMargin: '50px',
        threshold: 0.1,
        ...options
    }
    
    return new IntersectionObserver(callback, defaultOptions)
}

/**
 * Lazy load images with intersection observer
 */
export function lazyLoadImage(img: HTMLImageElement, src: string): void {
    const observer = createIntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                const image = entry.target as HTMLImageElement
                image.src = src
                image.classList.remove('lazy-loading')
                image.classList.add('lazy-loaded')
                observer.unobserve(image)
            }
        })
    })
    
    img.classList.add('lazy-loading')
    observer.observe(img)
}

/**
 * Preload critical resources
 */
export function preloadCriticalResources(): void {
    // Preload critical CSS
    const criticalCSS = document.createElement('link')
    criticalCSS.rel = 'preload'
    criticalCSS.as = 'style'
    criticalCSS.href = '/build/assets/app.css'
    document.head.appendChild(criticalCSS)
    
    // Note: Font preloading removed as Inter fonts don't exist in this project
    // The project uses 'Instrument Sans' and system fonts instead
}

/**
 * Dynamic import with retry logic
 */
export async function dynamicImportWithRetry<T>(
    importFn: () => Promise<T>,
    retries: number = 3,
    delay: number = 1000
): Promise<T> {
    try {
        return await importFn()
    } catch (error) {
        if (retries > 0) {
            await new Promise(resolve => setTimeout(resolve, delay))
            return dynamicImportWithRetry(importFn, retries - 1, delay * 2)
        }
        throw error
    }
}