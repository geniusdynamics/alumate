/**
 * ABOUTME: Asset URL helper utility for handling development vs production asset URLs
 * ABOUTME: Detects environment and returns correct URLs for Vite dev server or built assets
 */

/**
 * Determines if we're in development mode with Vite dev server running
 */
function isDevelopment(): boolean {
  return import.meta.env.DEV || import.meta.env.NODE_ENV === 'development'
}

/**
 * Gets the Vite dev server URL from environment or defaults
 */
function getViteDevServerUrl(): string {
  return import.meta.env.VITE_DEV_SERVER_URL || 'http://127.0.0.1:5176'
}

/**
 * Checks if Vite dev server is available by looking for the hot file
 */
function isViteDevServerAvailable(): boolean {
  if (typeof window === 'undefined') return false
  
  // Check if we're in development and if the hot file exists
  return isDevelopment() && document.querySelector('link[rel="preload"][href*="127.0.0.1:5176"]') !== null
}

/**
 * Converts a build asset path to the appropriate URL based on environment
 * @param assetPath - The asset path (e.g., '/build/assets/app.css')
 * @returns The correct URL for the current environment
 */
export function getAssetUrl(assetPath: string): string {
  // In development, return null for build assets since Vite handles module loading
  // This prevents 404 errors when trying to preload non-existent build assets
  if (isDevelopment() && assetPath.startsWith('/build/assets/')) {
    return ''
  }
  
  // Return the original path for production use or non-build assets
  return assetPath
}

/**
 * Gets the correct CSS URL for the current environment
 */
export function getCSSUrl(href: string): string {
  return getAssetUrl(href)
}

/**
 * Gets the correct JavaScript URL for the current environment
 */
export function getJSUrl(src: string): string {
  return getAssetUrl(src)
}

/**
 * Gets the correct font URL for the current environment
 */
export function getFontUrl(src: string): string {
  return getAssetUrl(src)
}

/**
 * Gets the correct image URL for the current environment
 */
export function getImageUrl(src: string): string {
  return getAssetUrl(src)
}

/**
 * Checks if we should skip preloading in development
 * (since Vite handles module loading differently)
 */
export function shouldSkipPreloading(): boolean {
  return isDevelopment()
}

/**
 * Checks if we should skip asset preloading for build assets in development
 * @param assetPath - The asset path to check
 */
export function shouldSkipAssetPreloading(assetPath: string): boolean {
  // Skip preloading build assets in development
  if (isDevelopment() && assetPath.startsWith('/build/assets/')) {
    return true
  }
  return false
}

/**
 * Gets environment info for debugging
 */
export function getEnvironmentInfo() {
  return {
    isDevelopment: isDevelopment(),
    viteDevServerUrl: getViteDevServerUrl(),
    isViteAvailable: isViteDevServerAvailable(),
    nodeEnv: import.meta.env.NODE_ENV,
    mode: import.meta.env.MODE
  }
}