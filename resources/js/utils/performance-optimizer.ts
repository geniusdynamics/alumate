/**
 * Performance Optimization Utilities
 */

interface OptimizationConfig {
  enableLazyLoading: boolean
  enableImageOptimization: boolean
  enableCodeSplitting: boolean
  enableCaching: boolean
  enableCompression: boolean
  enablePreloading: boolean
}

class PerformanceOptimizer {
  private config: OptimizationConfig
  private observer: IntersectionObserver | null = null
  private preloadedResources: Set<string> = new Set()

  constructor(config: Partial<OptimizationConfig> = {}) {
    this.config = {
      enableLazyLoading: true,
      enableImageOptimization: true,
      enableCodeSplitting: true,
      enableCaching: true,
      enableCompression: true,
      enablePreloading: true,
      ...config
    }

    this.initialize()
  }

  private initialize(): void {
    if (typeof window === 'undefined') return

    // Initialize lazy loading
    if (this.config.enableLazyLoading) {
      this.initializeLazyLoading()
    }

    // Initialize image optimization
    if (this.config.enableImageOptimization) {
      this.initializeImageOptimization()
    }

    // Initialize preloading
    if (this.config.enablePreloading) {
      this.initializePreloading()
    }

    // Initialize caching
    if (this.config.enableCaching) {
      this.initializeCaching()
    }
  }

  private initializeLazyLoading(): void {
    if (!('IntersectionObserver' in window)) return

    this.observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            this.loadElement(entry.target as HTMLElement)
            this.observer?.unobserve(entry.target)
          }
        })
      },
      {
        root: null,
        rootMargin: '50px',
        threshold: 0.1
      }
    )

    // Observe existing lazy elements
    this.observeLazyElements()

    // Watch for new lazy elements
    this.watchForNewElements()
  }

  private observeLazyElements(): void {
    const lazyElements = document.querySelectorAll('[data-lazy], img[loading="lazy"]')
    lazyElements.forEach((element) => {
      this.observer?.observe(element)
    })
  }

  private watchForNewElements(): void {
    if (!('MutationObserver' in window)) return

    const mutationObserver = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        mutation.addedNodes.forEach((node) => {
          if (node.nodeType === Node.ELEMENT_NODE) {
            const element = node as HTMLElement
            
            // Check if the element itself is lazy
            if (element.hasAttribute('data-lazy') || element.getAttribute('loading') === 'lazy') {
              this.observer?.observe(element)
            }
            
            // Check for lazy elements within the added node
            const lazyChildren = element.querySelectorAll('[data-lazy], img[loading="lazy"]')
            lazyChildren.forEach((child) => {
              this.observer?.observe(child)
            })
          }
        })
      })
    })

    mutationObserver.observe(document.body, {
      childList: true,
      subtree: true
    })
  }

  private loadElement(element: HTMLElement): void {
    if (element.tagName === 'IMG') {
      this.loadImage(element as HTMLImageElement)
    } else if (element.hasAttribute('data-lazy-component')) {
      this.loadComponent(element)
    } else if (element.hasAttribute('data-lazy-src')) {
      this.loadResource(element)
    }

    element.classList.add('lazy-loaded')
    element.removeAttribute('data-lazy')
  }

  private loadImage(img: HTMLImageElement): void {
    const src = img.getAttribute('data-src') || img.getAttribute('data-lazy-src')
    const srcset = img.getAttribute('data-srcset')

    if (src) {
      img.src = src
      img.removeAttribute('data-src')
      img.removeAttribute('data-lazy-src')
    }

    if (srcset) {
      img.srcset = srcset
      img.removeAttribute('data-srcset')
    }

    // Add loading animation
    img.style.opacity = '0'
    img.style.transition = 'opacity 0.3s ease'

    img.onload = () => {
      img.style.opacity = '1'
    }

    img.onerror = () => {
      img.style.opacity = '1'
      img.classList.add('lazy-error')
    }
  }

  private loadComponent(element: HTMLElement): void {
    const componentName = element.getAttribute('data-lazy-component')
    if (!componentName) return

    // Dynamically import and mount component
    import(`../Components/${componentName}.vue`)
      .then((module) => {
        // Component loading logic would go here
        console.log(`Lazy loaded component: ${componentName}`)
      })
      .catch((error) => {
        console.error(`Failed to lazy load component ${componentName}:`, error)
      })
  }

  private loadResource(element: HTMLElement): void {
    const src = element.getAttribute('data-lazy-src')
    if (!src) return

    if (element.tagName === 'SCRIPT') {
      const script = document.createElement('script')
      script.src = src
      script.async = true
      document.head.appendChild(script)
    } else if (element.tagName === 'LINK') {
      const link = element as HTMLLinkElement
      link.href = src
    }
  }

  private initializeImageOptimization(): void {
    // Add responsive image support
    this.addResponsiveImageSupport()

    // Add WebP support detection
    this.detectWebPSupport()

    // Add image compression
    this.addImageCompression()
  }

  private addResponsiveImageSupport(): void {
    const images = document.querySelectorAll('img:not([srcset])')
    images.forEach((img) => {
      const src = (img as HTMLImageElement).src
      if (src && !src.includes('?')) {
        // Add responsive parameters
        const responsiveSrc = this.generateResponsiveImageUrl(src, img as HTMLImageElement)
        if (responsiveSrc !== src) {
          (img as HTMLImageElement).src = responsiveSrc
        }
      }
    })
  }

  private generateResponsiveImageUrl(src: string, img: HTMLImageElement): string {
    const rect = img.getBoundingClientRect()
    const devicePixelRatio = window.devicePixelRatio || 1
    
    const width = Math.ceil(rect.width * devicePixelRatio)
    const height = Math.ceil(rect.height * devicePixelRatio)

    const params = new URLSearchParams()
    if (width > 0) params.set('w', width.toString())
    if (height > 0) params.set('h', height.toString())
    params.set('q', '85') // Quality
    params.set('f', 'webp') // Format

    return `${src}?${params.toString()}`
  }

  private detectWebPSupport(): void {
    const webP = new Image()
    webP.onload = webP.onerror = () => {
      const support = webP.height === 2
      document.documentElement.classList.toggle('webp-support', support)
      document.documentElement.classList.toggle('no-webp-support', !support)
    }
    webP.src = 'data:image/webp;base64,UklGRjoAAABXRUJQVlA4IC4AAACyAgCdASoCAAIALmk0mk0iIiIiIgBoSygABc6WWgAA/veff/0PP8bA//LwYAAA'
  }

  private addImageCompression(): void {
    // Add automatic image compression for uploads
    const fileInputs = document.querySelectorAll('input[type="file"][accept*="image"]')
    fileInputs.forEach((input) => {
      input.addEventListener('change', this.handleImageUpload.bind(this))
    })
  }

  private handleImageUpload(event: Event): void {
    const input = event.target as HTMLInputElement
    const files = input.files
    if (!files) return

    Array.from(files).forEach((file) => {
      if (file.type.startsWith('image/')) {
        this.compressImage(file).then((compressedFile) => {
          // Replace the original file with compressed version
          const dataTransfer = new DataTransfer()
          dataTransfer.items.add(compressedFile)
          input.files = dataTransfer.files
        })
      }
    })
  }

  private compressImage(file: File): Promise<File> {
    return new Promise((resolve) => {
      const canvas = document.createElement('canvas')
      const ctx = canvas.getContext('2d')
      const img = new Image()

      img.onload = () => {
        // Calculate new dimensions
        const maxWidth = 1920
        const maxHeight = 1080
        let { width, height } = img

        if (width > height) {
          if (width > maxWidth) {
            height = (height * maxWidth) / width
            width = maxWidth
          }
        } else {
          if (height > maxHeight) {
            width = (width * maxHeight) / height
            height = maxHeight
          }
        }

        canvas.width = width
        canvas.height = height

        // Draw and compress
        ctx?.drawImage(img, 0, 0, width, height)
        canvas.toBlob(
          (blob) => {
            if (blob) {
              const compressedFile = new File([blob], file.name, {
                type: 'image/jpeg',
                lastModified: Date.now()
              })
              resolve(compressedFile)
            } else {
              resolve(file)
            }
          },
          'image/jpeg',
          0.85
        )
      }

      img.src = URL.createObjectURL(file)
    })
  }

  private initializePreloading(): void {
    // Preload critical resources
    this.preloadCriticalResources()

    // Preload on hover
    this.initializeHoverPreloading()

    // Preload based on user behavior
    this.initializePredictivePreloading()
  }

  private preloadCriticalResources(): void {
    const criticalResources = [
      '/build/assets/app.css',
      '/build/assets/app.js',
      '/fonts/inter-var.woff2'
    ]

    criticalResources.forEach((resource) => {
      if (!this.preloadedResources.has(resource)) {
        this.preloadResource(resource)
      }
    })
  }

  private preloadResource(href: string, as: string = 'script'): void {
    if (this.preloadedResources.has(href)) return

    const link = document.createElement('link')
    link.rel = 'preload'
    link.href = href
    link.as = as

    if (as === 'font') {
      link.crossOrigin = 'anonymous'
    }

    document.head.appendChild(link)
    this.preloadedResources.add(href)
  }

  private initializeHoverPreloading(): void {
    document.addEventListener('mouseover', (event) => {
      const target = event.target as HTMLElement
      const link = target.closest('a[href]') as HTMLAnchorElement
      
      if (link && link.hostname === window.location.hostname) {
        const href = link.href
        if (!this.preloadedResources.has(href)) {
          this.preloadResource(href, 'document')
        }
      }
    })
  }

  private initializePredictivePreloading(): void {
    // Preload based on scroll position and user behavior
    let scrollTimeout: number

    window.addEventListener('scroll', () => {
      clearTimeout(scrollTimeout)
      scrollTimeout = window.setTimeout(() => {
        this.predictivePreload()
      }, 150)
    })
  }

  private predictivePreload(): void {
    // Get visible links and preload likely next pages
    const visibleLinks = this.getVisibleLinks()
    const likelyNext = this.predictNextNavigation(visibleLinks)
    
    likelyNext.forEach((href) => {
      if (!this.preloadedResources.has(href)) {
        this.preloadResource(href, 'document')
      }
    })
  }

  private getVisibleLinks(): HTMLAnchorElement[] {
    const links = Array.from(document.querySelectorAll('a[href]')) as HTMLAnchorElement[]
    return links.filter((link) => {
      const rect = link.getBoundingClientRect()
      return rect.top >= 0 && rect.top <= window.innerHeight
    })
  }

  private predictNextNavigation(links: HTMLAnchorElement[]): string[] {
    // Simple prediction based on link position and content
    return links
      .filter((link) => {
        const text = link.textContent?.toLowerCase() || ''
        return text.includes('next') || text.includes('continue') || text.includes('more')
      })
      .map((link) => link.href)
      .slice(0, 3) // Limit to 3 predictions
  }

  private initializeCaching(): void {
    // Initialize service worker for caching
    if ('serviceWorker' in navigator) {
      navigator.serviceWorker.register('/sw.js')
        .then((registration) => {
          console.log('SW registered:', registration)
        })
        .catch((error) => {
          console.log('SW registration failed:', error)
        })
    }

    // Initialize memory caching for API responses
    this.initializeMemoryCache()
  }

  private initializeMemoryCache(): void {
    const cache = new Map()
    const maxCacheSize = 50
    const cacheTimeout = 5 * 60 * 1000 // 5 minutes

    // Intercept fetch requests
    const originalFetch = window.fetch
    window.fetch = async (input, init) => {
      const url = typeof input === 'string' ? input : input.url
      
      // Only cache GET requests to API endpoints
      if ((!init || init.method === 'GET') && url.includes('/api/')) {
        const cacheKey = url
        const cached = cache.get(cacheKey)
        
        if (cached && Date.now() - cached.timestamp < cacheTimeout) {
          return new Response(JSON.stringify(cached.data), {
            headers: { 'Content-Type': 'application/json' }
          })
        }
      }

      const response = await originalFetch(input, init)
      
      // Cache successful GET responses
      if (response.ok && (!init || init.method === 'GET') && url.includes('/api/')) {
        const clonedResponse = response.clone()
        clonedResponse.json().then((data) => {
          // Manage cache size
          if (cache.size >= maxCacheSize) {
            const firstKey = cache.keys().next().value
            cache.delete(firstKey)
          }
          
          cache.set(url, {
            data,
            timestamp: Date.now()
          })
        })
      }

      return response
    }
  }

  public optimizePage(): void {
    // Apply all optimizations to current page
    this.observeLazyElements()
    this.addResponsiveImageSupport()
    this.preloadCriticalResources()
    
    // Add performance hints
    this.addPerformanceHints()
  }

  private addPerformanceHints(): void {
    // Add resource hints to document head
    const hints = [
      { rel: 'dns-prefetch', href: '//fonts.googleapis.com' },
      { rel: 'dns-prefetch', href: '//api.example.com' },
      { rel: 'preconnect', href: 'https://fonts.gstatic.com', crossorigin: true }
    ]

    hints.forEach((hint) => {
      const link = document.createElement('link')
      link.rel = hint.rel
      link.href = hint.href
      if (hint.crossorigin) {
        link.crossOrigin = 'anonymous'
      }
      document.head.appendChild(link)
    })
  }

  public destroy(): void {
    if (this.observer) {
      this.observer.disconnect()
    }
  }
}

// Global instance
export const performanceOptimizer = new PerformanceOptimizer()

// Export for manual usage
export { PerformanceOptimizer }