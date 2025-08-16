import { cdnService } from './CDNService'

export interface PreloadResource {
  href: string
  as: 'script' | 'style' | 'image' | 'font' | 'fetch'
  type?: string
  crossorigin?: 'anonymous' | 'use-credentials'
  media?: string
  priority?: 'high' | 'low'
}

class PreloadService {
  private preloadedResources = new Set<string>()

  /**
   * Preload critical resources for faster page loading
   */
  public preloadCriticalResources(): void {
    // Preload critical CSS
    this.preloadCSS('/build/assets/app.css')
    
    // Preload critical fonts
    this.preloadFont('/fonts/inter-var.woff2', 'font/woff2')
    this.preloadFont('/fonts/inter-var-italic.woff2', 'font/woff2')
    
    // Preload hero images
    this.preloadImage('/images/hero-background.webp', { priority: 'high' })
    this.preloadImage('/images/hero-background.jpg', { priority: 'high' })
    
    // Preload critical JavaScript chunks
    this.preloadScript('/build/assets/homepage-core.js')
  }

  /**
   * Preload homepage-specific resources
   */
  public preloadHomepageResources(): void {
    // Preload above-the-fold images
    const criticalImages = [
      '/images/testimonials/featured-testimonial.webp',
      '/images/logos/company-logos-sprite.webp',
      '/images/statistics/platform-stats-bg.webp'
    ]

    criticalImages.forEach(src => {
      this.preloadImage(src, { priority: 'high' })
    })

    // Preload next likely components
    this.prefetchComponent('/build/assets/homepage-features.js')
    this.prefetchComponent('/build/assets/homepage-conversion.js')
  }

  /**
   * Preload CSS file
   */
  public preloadCSS(href: string, media?: string): void {
    if (this.preloadedResources.has(href)) return

    const url = cdnService.getCSSUrl(href)
    const link = this.createPreloadLink({
      href: url,
      as: 'style',
      media
    })

    // Convert to stylesheet after load
    link.onload = () => {
      link.rel = 'stylesheet'
    }

    this.appendToHead(link)
    this.preloadedResources.add(href)
  }

  /**
   * Preload JavaScript file
   */
  public preloadScript(src: string): void {
    if (this.preloadedResources.has(src)) return

    const url = cdnService.getJSUrl(src)
    const link = this.createPreloadLink({
      href: url,
      as: 'script'
    })

    this.appendToHead(link)
    this.preloadedResources.add(src)
  }

  /**
   * Preload image with responsive support
   */
  public preloadImage(src: string, options: { priority?: 'high' | 'low', responsive?: boolean } = {}): void {
    if (this.preloadedResources.has(src)) return

    const url = cdnService.getImageUrl(src, { format: 'auto', quality: 85 })
    const link = this.createPreloadLink({
      href: url,
      as: 'image',
      priority: options.priority
    })

    // Add responsive preload if requested
    if (options.responsive) {
      const srcset = cdnService.getResponsiveImageSrcSet(src)
      if (srcset) {
        link.setAttribute('imagesrcset', srcset)
        link.setAttribute('imagesizes', '(max-width: 768px) 100vw, (max-width: 1200px) 50vw, 33vw')
      }
    }

    this.appendToHead(link)
    this.preloadedResources.add(src)
  }

  /**
   * Preload font file
   */
  public preloadFont(src: string, type: string = 'font/woff2'): void {
    if (this.preloadedResources.has(src)) return

    const url = cdnService.getFontUrl(src, false) // Don't auto-preload in CDN service
    const link = this.createPreloadLink({
      href: url,
      as: 'font',
      type,
      crossorigin: 'anonymous'
    })

    this.appendToHead(link)
    this.preloadedResources.add(src)
  }

  /**
   * Prefetch resource for future navigation
   */
  public prefetchResource(href: string, as?: PreloadResource['as']): void {
    if (this.preloadedResources.has(`prefetch-${href}`)) return

    const link = document.createElement('link')
    link.rel = 'prefetch'
    link.href = href
    
    if (as) {
      link.as = as
    }

    this.appendToHead(link)
    this.preloadedResources.add(`prefetch-${href}`)
  }

  /**
   * Prefetch component chunk
   */
  public prefetchComponent(src: string): void {
    this.prefetchResource(src, 'script')
  }

  /**
   * Preload API data
   */
  public preloadData(url: string): void {
    if (this.preloadedResources.has(`data-${url}`)) return

    const link = this.createPreloadLink({
      href: url,
      as: 'fetch',
      crossorigin: 'anonymous'
    })

    this.appendToHead(link)
    this.preloadedResources.add(`data-${url}`)
  }

  /**
   * Preload resources based on user interaction hints
   */
  public preloadOnHover(element: HTMLElement, resources: string[]): void {
    let hasPreloaded = false

    const preloadResources = () => {
      if (hasPreloaded) return
      hasPreloaded = true

      resources.forEach(resource => {
        if (resource.endsWith('.js')) {
          this.preloadScript(resource)
        } else if (resource.endsWith('.css')) {
          this.preloadCSS(resource)
        } else if (this.isImageFile(resource)) {
          this.preloadImage(resource)
        } else {
          this.prefetchResource(resource)
        }
      })
    }

    // Preload on hover with debounce
    let hoverTimeout: number
    element.addEventListener('mouseenter', () => {
      hoverTimeout = window.setTimeout(preloadResources, 100)
    })

    element.addEventListener('mouseleave', () => {
      if (hoverTimeout) {
        clearTimeout(hoverTimeout)
      }
    })

    // Also preload on focus for keyboard users
    element.addEventListener('focus', preloadResources)
  }

  /**
   * Preload resources for next page based on current page
   */
  public preloadNextPageResources(currentPage: string): void {
    const nextPageMap: Record<string, string[]> = {
      'homepage': [
        '/build/assets/auth.js',
        '/build/assets/dashboard.js',
        '/api/user/profile'
      ],
      'features': [
        '/build/assets/pricing.js',
        '/build/assets/testimonials.js'
      ],
      'pricing': [
        '/build/assets/checkout.js',
        '/build/assets/payment.js'
      ]
    }

    const resources = nextPageMap[currentPage]
    if (resources) {
      resources.forEach(resource => {
        if (resource.startsWith('/api/')) {
          this.preloadData(resource)
        } else {
          this.prefetchResource(resource, 'script')
        }
      })
    }
  }

  /**
   * Create preload link element
   */
  private createPreloadLink(resource: PreloadResource): HTMLLinkElement {
    const link = document.createElement('link')
    link.rel = 'preload'
    link.href = resource.href
    link.as = resource.as

    if (resource.type) {
      link.type = resource.type
    }

    if (resource.crossorigin) {
      link.crossOrigin = resource.crossorigin
    }

    if (resource.media) {
      link.media = resource.media
    }

    if (resource.priority) {
      link.setAttribute('fetchpriority', resource.priority)
    }

    return link
  }

  /**
   * Append link to document head
   */
  private appendToHead(link: HTMLLinkElement): void {
    if (typeof document !== 'undefined') {
      document.head.appendChild(link)
    }
  }

  /**
   * Check if file is an image
   */
  private isImageFile(src: string): boolean {
    return /\.(jpg|jpeg|png|gif|webp|svg)$/i.test(src)
  }

  /**
   * Clear all preloaded resources
   */
  public clearPreloadedResources(): void {
    this.preloadedResources.clear()
  }

  /**
   * Get preloaded resources count
   */
  public getPreloadedCount(): number {
    return this.preloadedResources.size
  }
}

// Singleton instance
export const preloadService = new PreloadService()

// Auto-preload critical resources when service is imported
if (typeof window !== 'undefined') {
  // Preload critical resources immediately
  preloadService.preloadCriticalResources()
  
  // Preload homepage resources after a short delay
  setTimeout(() => {
    preloadService.preloadHomepageResources()
  }, 100)
}

export default PreloadService