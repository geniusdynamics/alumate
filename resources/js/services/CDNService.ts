export interface CDNConfig {
  baseUrl: string
  imageOptimization: boolean
  webpSupport: boolean
  lazyLoading: boolean
  cacheBusting: boolean
}

export interface ImageOptions {
  width?: number
  height?: number
  quality?: number
  format?: 'webp' | 'jpg' | 'png' | 'auto'
  fit?: 'cover' | 'contain' | 'fill' | 'inside' | 'outside'
  blur?: number
  sharpen?: boolean
}

class CDNService {
  private config: CDNConfig
  private cache = new Map<string, string>()

  constructor(config: Partial<CDNConfig> = {}) {
    this.config = {
      baseUrl: import.meta.env.VITE_CDN_URL || '',
      imageOptimization: true,
      webpSupport: this.detectWebPSupport(),
      lazyLoading: true,
      cacheBusting: import.meta.env.NODE_ENV === 'development',
      ...config
    }
  }

  private detectWebPSupport(): boolean {
    if (typeof window === 'undefined') return false
    
    const canvas = document.createElement('canvas')
    canvas.width = 1
    canvas.height = 1
    const dataURL = canvas.toDataURL('image/webp')
    return dataURL.indexOf('data:image/webp') === 0
  }

  /**
   * Get optimized image URL with CDN transformations
   */
  public getImageUrl(src: string, options: ImageOptions = {}): string {
    if (!src) return ''
    
    // Return original if no CDN configured
    if (!this.config.baseUrl) {
      return this.addCacheBusting(src)
    }

    const cacheKey = `${src}-${JSON.stringify(options)}`
    
    if (this.cache.has(cacheKey)) {
      return this.cache.get(cacheKey)!
    }

    let url = this.buildCDNUrl(src)
    
    if (this.config.imageOptimization) {
      url = this.addImageOptimizations(url, options)
    }

    if (this.config.cacheBusting) {
      url = this.addCacheBusting(url)
    }

    this.cache.set(cacheKey, url)
    return url
  }

  /**
   * Get responsive image srcset for different screen sizes
   */
  public getResponsiveImageSrcSet(src: string, sizes: number[] = [320, 640, 768, 1024, 1280, 1920]): string {
    if (!src || !this.config.baseUrl) return ''

    const srcsetEntries = sizes.map(width => {
      const url = this.getImageUrl(src, { width, format: 'auto' })
      return `${url} ${width}w`
    })

    return srcsetEntries.join(', ')
  }

  /**
   * Get WebP version of image with fallback
   */
  public getWebPWithFallback(src: string, options: ImageOptions = {}): { webp: string; fallback: string } {
    const webpOptions = { ...options, format: 'webp' as const }
    const fallbackOptions = { ...options, format: options.format || 'auto' as const }

    return {
      webp: this.getImageUrl(src, webpOptions),
      fallback: this.getImageUrl(src, fallbackOptions)
    }
  }

  /**
   * Preload critical images
   */
  public preloadImage(src: string, options: ImageOptions = {}): void {
    if (typeof window === 'undefined') return

    const url = this.getImageUrl(src, options)
    
    const link = document.createElement('link')
    link.rel = 'preload'
    link.as = 'image'
    link.href = url
    
    // Add responsive preload if width is specified
    if (options.width) {
      link.setAttribute('imagesrcset', this.getResponsiveImageSrcSet(src))
      link.setAttribute('imagesizes', '(max-width: 768px) 100vw, 50vw')
    }

    document.head.appendChild(link)
  }

  /**
   * Preload critical CSS
   */
  public preloadCSS(href: string): void {
    if (typeof window === 'undefined') return

    const link = document.createElement('link')
    link.rel = 'preload'
    link.as = 'style'
    link.href = this.getCSSUrl(href)
    link.onload = () => {
      link.rel = 'stylesheet'
    }
    
    document.head.appendChild(link)
  }

  /**
   * Get CSS URL with CDN
   */
  public getCSSUrl(href: string): string {
    if (!this.config.baseUrl) {
      return this.addCacheBusting(href)
    }

    return this.buildCDNUrl(href)
  }

  /**
   * Get JavaScript URL with CDN
   */
  public getJSUrl(src: string): string {
    if (!this.config.baseUrl) {
      return this.addCacheBusting(src)
    }

    return this.buildCDNUrl(src)
  }

  /**
   * Prefetch resources for next page
   */
  public prefetchResource(url: string, type: 'image' | 'script' | 'style' | 'document' = 'document'): void {
    if (typeof window === 'undefined') return

    const link = document.createElement('link')
    link.rel = 'prefetch'
    link.href = url
    
    if (type !== 'document') {
      link.as = type
    }

    document.head.appendChild(link)
  }

  /**
   * Get font URL with CDN and preload
   */
  public getFontUrl(src: string, preload = true): string {
    const url = this.config.baseUrl ? this.buildCDNUrl(src) : src

    if (preload && typeof window !== 'undefined') {
      const link = document.createElement('link')
      link.rel = 'preload'
      link.as = 'font'
      link.type = 'font/woff2'
      link.crossOrigin = 'anonymous'
      link.href = url
      document.head.appendChild(link)
    }

    return url
  }

  private buildCDNUrl(src: string): string {
    // Handle absolute URLs
    if (src.startsWith('http://') || src.startsWith('https://')) {
      return src
    }

    // Remove leading slash for CDN concatenation
    const cleanSrc = src.startsWith('/') ? src.slice(1) : src
    
    return `${this.config.baseUrl.replace(/\/$/, '')}/${cleanSrc}`
  }

  private addImageOptimizations(url: string, options: ImageOptions): string {
    const params = new URLSearchParams()

    if (options.width) params.set('w', options.width.toString())
    if (options.height) params.set('h', options.height.toString())
    if (options.quality) params.set('q', options.quality.toString())
    if (options.format && options.format !== 'auto') params.set('f', options.format)
    if (options.fit) params.set('fit', options.fit)
    if (options.blur) params.set('blur', options.blur.toString())
    if (options.sharpen) params.set('sharpen', 'true')

    // Auto-detect WebP support
    if (options.format === 'auto' && this.config.webpSupport) {
      params.set('f', 'webp')
    }

    const queryString = params.toString()
    return queryString ? `${url}?${queryString}` : url
  }

  private addCacheBusting(url: string): string {
    if (!this.config.cacheBusting) return url

    const separator = url.includes('?') ? '&' : '?'
    const timestamp = Date.now()
    return `${url}${separator}v=${timestamp}`
  }

  /**
   * Clear URL cache
   */
  public clearCache(): void {
    this.cache.clear()
  }

  /**
   * Update configuration
   */
  public updateConfig(newConfig: Partial<CDNConfig>): void {
    this.config = { ...this.config, ...newConfig }
    this.clearCache()
  }
}

// Singleton instance
export const cdnService = new CDNService()

export default CDNService