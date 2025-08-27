/**
 * Media Optimization Utilities
 * Handles responsive images, WebP support, CDN integration, and mobile optimization
 */

import type { MediaAsset } from '@/types/components'

export interface MediaOptimizationConfig {
  cdnBaseUrl?: string
  enableWebP?: boolean
  enableAVIF?: boolean
  mobileBreakpoint?: number
  qualitySettings?: {
    high: number
    medium: number
    low: number
  }
}

export interface ResponsiveImageConfig {
  breakpoints: number[]
  formats: ('webp' | 'avif' | 'jpeg' | 'png')[]
  quality: number
  enableLazyLoading: boolean
}

export class MediaOptimizationService {
  private config: MediaOptimizationConfig
  private isMobile: boolean
  private supportsWebP: boolean
  private supportsAVIF: boolean
  private connectionSpeed: 'slow' | 'fast' | 'unknown'

  constructor(config: MediaOptimizationConfig = {}) {
    this.config = {
      cdnBaseUrl: config.cdnBaseUrl || '',
      enableWebP: config.enableWebP ?? true,
      enableAVIF: config.enableAVIF ?? true,
      mobileBreakpoint: config.mobileBreakpoint || 768,
      qualitySettings: {
        high: 90,
        medium: 75,
        low: 60,
        ...config.qualitySettings
      }
    }

    this.isMobile = this.detectMobile()
    this.supportsWebP = false
    this.supportsAVIF = false
    this.connectionSpeed = this.detectConnectionSpeed()

    // Initialize format support detection
    this.detectFormatSupport()
  }

  /**
   * Generate optimized image sources with responsive breakpoints and modern formats
   */
  generateResponsiveImageSources(asset: MediaAsset, config?: Partial<ResponsiveImageConfig>): {
    srcSet: string
    sizes: string
    src: string
    placeholder?: string
  } {
    const defaultConfig: ResponsiveImageConfig = {
      breakpoints: [320, 640, 768, 1024, 1280, 1920],
      formats: ['webp', 'jpeg'],
      quality: this.getOptimalQuality(),
      enableLazyLoading: true,
      ...config
    }

    // Use mobile-specific sources if available and on mobile
    const sourceAsset = this.isMobile && asset.mobileUrl ? {
      ...asset,
      url: asset.mobileUrl,
      srcSet: asset.mobileSrcSet || asset.srcSet
    } : asset

    // Generate srcSet for different breakpoints and formats
    const srcSetEntries: string[] = []
    const sources: { type: string; srcset: string; sizes?: string }[] = []

    // Generate sources for each supported format
    for (const format of defaultConfig.formats) {
      if (!this.isFormatSupported(format)) continue

      const formatSrcSet = defaultConfig.breakpoints
        .map(width => {
          const optimizedUrl = this.generateOptimizedUrl(sourceAsset.url, {
            width,
            format,
            quality: defaultConfig.quality
          })
          return `${optimizedUrl} ${width}w`
        })
        .join(', ')

      sources.push({
        type: `image/${format}`,
        srcset: formatSrcSet,
        sizes: this.generateSizesAttribute(defaultConfig.breakpoints)
      })

      // Use the first supported format for the main srcSet
      if (srcSetEntries.length === 0) {
        srcSetEntries.push(formatSrcSet)
      }
    }

    // Fallback src (original or optimized)
    const fallbackSrc = this.generateOptimizedUrl(sourceAsset.url, {
      width: 1200,
      format: 'jpeg',
      quality: defaultConfig.quality
    })

    return {
      srcSet: srcSetEntries[0] || '',
      sizes: this.generateSizesAttribute(defaultConfig.breakpoints),
      src: fallbackSrc,
      placeholder: asset.placeholder
    }
  }

  /**
   * Generate optimized video sources with bandwidth considerations
   */
  generateVideoSources(asset: MediaAsset & { 
    autoplay?: boolean
    muted?: boolean
    loop?: boolean
    poster?: string
    preload?: 'none' | 'metadata' | 'auto'
    mobileVideo?: MediaAsset
    disableOnMobile?: boolean
    quality?: 'low' | 'medium' | 'high' | 'auto'
  }): {
    sources: Array<{ src: string; type: string }>
    poster?: string
    shouldLoad: boolean
    attributes: Record<string, boolean | string>
  } {
    // Check if video should be disabled on mobile
    if (this.isMobile && asset.disableOnMobile) {
      return {
        sources: [],
        shouldLoad: false,
        attributes: {}
      }
    }

    // Use mobile-specific video if available
    const videoAsset = this.isMobile && asset.mobileVideo ? asset.mobileVideo : asset

    // Determine quality based on connection speed and settings
    const quality = this.getOptimalVideoQuality(asset.quality)

    const sources = [{
      src: this.generateOptimizedUrl(videoAsset.url, { quality }),
      type: videoAsset.mimeType || 'video/mp4'
    }]

    // Generate poster image if provided
    const poster = asset.poster ? this.generateOptimizedUrl(asset.poster, {
      width: 1920,
      format: this.supportsWebP ? 'webp' : 'jpeg',
      quality: this.config.qualitySettings!.medium
    }) : undefined

    return {
      sources,
      poster,
      shouldLoad: !this.isMobile || !asset.disableOnMobile,
      attributes: {
        autoplay: asset.autoplay ?? true,
        muted: asset.muted ?? true,
        loop: asset.loop ?? true,
        playsinline: true,
        preload: asset.preload || (this.connectionSpeed === 'slow' ? 'none' : 'metadata')
      }
    }
  }

  /**
   * Generate CDN-optimized URL with transformation parameters
   */
  private generateOptimizedUrl(originalUrl: string, options: {
    width?: number
    height?: number
    format?: string
    quality?: number
  } = {}): string {
    // If no CDN configured, return original URL
    if (!this.config.cdnBaseUrl) {
      return originalUrl
    }

    // Build transformation parameters
    const params = new URLSearchParams()
    
    if (options.width) params.set('w', options.width.toString())
    if (options.height) params.set('h', options.height.toString())
    if (options.format) params.set('f', options.format)
    if (options.quality) params.set('q', options.quality.toString())

    // Add auto-optimization flags
    params.set('auto', 'compress,format')

    // Construct CDN URL
    const cdnUrl = `${this.config.cdnBaseUrl}/${originalUrl.replace(/^https?:\/\/[^\/]+/, '')}`
    return `${cdnUrl}?${params.toString()}`
  }

  /**
   * Generate sizes attribute for responsive images
   */
  private generateSizesAttribute(breakpoints: number[]): string {
    const sizes = breakpoints
      .sort((a, b) => b - a) // Sort descending
      .map((bp, index) => {
        if (index === breakpoints.length - 1) {
          return `${bp}px` // Last breakpoint without media query
        }
        return `(max-width: ${bp}px) ${bp}px`
      })

    return sizes.join(', ')
  }

  /**
   * Detect mobile device
   */
  private detectMobile(): boolean {
    if (typeof window === 'undefined') return false
    
    return window.innerWidth <= this.config.mobileBreakpoint! ||
           /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)
  }

  /**
   * Detect connection speed
   */
  private detectConnectionSpeed(): 'slow' | 'fast' | 'unknown' {
    if (typeof navigator === 'undefined' || !('connection' in navigator)) {
      return 'unknown'
    }

    const connection = (navigator as any).connection
    if (!connection) return 'unknown'

    // Check effective connection type
    if (connection.effectiveType) {
      return ['slow-2g', '2g', '3g'].includes(connection.effectiveType) ? 'slow' : 'fast'
    }

    // Fallback to downlink speed
    if (connection.downlink) {
      return connection.downlink < 1.5 ? 'slow' : 'fast'
    }

    return 'unknown'
  }

  /**
   * Detect format support (WebP, AVIF)
   */
  private async detectFormatSupport(): Promise<void> {
    if (typeof window === 'undefined') return

    // Test WebP support
    if (this.config.enableWebP) {
      this.supportsWebP = await this.testImageFormat('webp')
    }

    // Test AVIF support
    if (this.config.enableAVIF) {
      this.supportsAVIF = await this.testImageFormat('avif')
    }
  }

  /**
   * Test if browser supports specific image format
   */
  private testImageFormat(format: 'webp' | 'avif'): Promise<boolean> {
    return new Promise((resolve) => {
      const testImages = {
        webp: 'data:image/webp;base64,UklGRjoAAABXRUJQVlA4IC4AAACyAgCdASoCAAIALmk0mk0iIiIiIgBoSygABc6WWgAA/veff/0PP8bA//LwYAAA',
        avif: 'data:image/avif;base64,AAAAIGZ0eXBhdmlmAAAAAGF2aWZtaWYxbWlhZk1BMUIAAADybWV0YQAAAAAAAAAoaGRscgAAAAAAAAAAcGljdAAAAAAAAAAAAAAAAGxpYmF2aWYAAAAADnBpdG0AAAAAAAEAAAAeaWxvYwAAAABEAAABAAEAAAABAAABGgAAAB0AAAAoaWluZgAAAAAAAQAAABppbmZlAgAAAAABAABhdjAxQ29sb3IAAAAAamlwcnAAAABLaXBjbwAAABRpc3BlAAAAAAAAAAIAAAACAAAAEHBpeGkAAAAAAwgICAAAAAxhdjFDgQ0MAAAAABNjb2xybmNseAACAAIAAYAAAAAXaXBtYQAAAAAAAAABAAEEAQKDBAAAACVtZGF0EgAKCBgABogQEAwgMg8f8D///8WfhwB8+ErK42A='
      }

      const img = new Image()
      img.onload = () => resolve(img.width === 2 && img.height === 2)
      img.onerror = () => resolve(false)
      img.src = testImages[format]
    })
  }

  /**
   * Check if format is supported
   */
  private isFormatSupported(format: string): boolean {
    switch (format) {
      case 'webp':
        return this.supportsWebP
      case 'avif':
        return this.supportsAVIF
      case 'jpeg':
      case 'png':
        return true
      default:
        return false
    }
  }

  /**
   * Get optimal quality based on connection and device
   */
  private getOptimalQuality(): number {
    if (this.connectionSpeed === 'slow') {
      return this.config.qualitySettings!.low
    }
    
    if (this.isMobile) {
      return this.config.qualitySettings!.medium
    }

    return this.config.qualitySettings!.high
  }

  /**
   * Get optimal video quality
   */
  private getOptimalVideoQuality(requestedQuality?: 'low' | 'medium' | 'high' | 'auto'): number {
    if (requestedQuality && requestedQuality !== 'auto') {
      return this.config.qualitySettings![requestedQuality]
    }

    // Auto-determine based on connection and device
    if (this.connectionSpeed === 'slow' || this.isMobile) {
      return this.config.qualitySettings!.low
    }

    return this.config.qualitySettings!.medium
  }

  /**
   * Create intersection observer for lazy loading
   */
  createLazyLoadObserver(callback: (entries: IntersectionObserverEntry[]) => void): IntersectionObserver | null {
    if (typeof window === 'undefined' || !('IntersectionObserver' in window)) {
      return null
    }

    return new IntersectionObserver(callback, {
      rootMargin: '50px 0px',
      threshold: 0.1
    })
  }

  /**
   * Preload critical images
   */
  preloadImage(url: string): Promise<void> {
    return new Promise((resolve, reject) => {
      const img = new Image()
      img.onload = () => resolve()
      img.onerror = reject
      img.src = url
    })
  }

  /**
   * Generate placeholder for progressive loading
   */
  generatePlaceholder(asset: MediaAsset): string {
    if (asset.placeholder) {
      return asset.placeholder
    }

    // Generate a simple gradient placeholder based on dominant colors
    return 'data:image/svg+xml;base64,' + btoa(`
      <svg width="100" height="60" xmlns="http://www.w3.org/2000/svg">
        <defs>
          <linearGradient id="grad" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" style="stop-color:#f3f4f6;stop-opacity:1" />
            <stop offset="100%" style="stop-color:#e5e7eb;stop-opacity:1" />
          </linearGradient>
        </defs>
        <rect width="100%" height="100%" fill="url(#grad)" />
      </svg>
    `)
  }
}

// Export singleton instance
export const mediaOptimizer = new MediaOptimizationService({
  cdnBaseUrl: import.meta.env.VITE_CDN_BASE_URL,
  enableWebP: true,
  enableAVIF: true,
  mobileBreakpoint: 768
})

// Export utility functions
export const generateResponsiveImageSources = (asset: MediaAsset, config?: Partial<ResponsiveImageConfig>) => 
  mediaOptimizer.generateResponsiveImageSources(asset, config)

export const generateVideoSources = (asset: MediaAsset & any) => 
  mediaOptimizer.generateVideoSources(asset)

export const createLazyLoadObserver = (callback: (entries: IntersectionObserverEntry[]) => void) => 
  mediaOptimizer.createLazyLoadObserver(callback)

export const preloadImage = (url: string) => 
  mediaOptimizer.preloadImage(url)