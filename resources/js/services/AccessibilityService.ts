export interface AccessibilityOptions {
  announcePageChanges?: boolean
  manageFocus?: boolean
  skipLinks?: boolean
  keyboardNavigation?: boolean
  screenReaderOptimizations?: boolean
}

export interface AriaLiveRegion {
  id: string
  politeness: 'polite' | 'assertive' | 'off'
  atomic?: boolean
  relevant?: 'additions' | 'removals' | 'text' | 'all'
}

class AccessibilityService {
  private options: AccessibilityOptions
  private liveRegions = new Map<string, HTMLElement>()
  private focusHistory: HTMLElement[] = []
  private skipLinksContainer?: HTMLElement

  constructor(options: AccessibilityOptions = {}) {
    this.options = {
      announcePageChanges: true,
      manageFocus: true,
      skipLinks: true,
      keyboardNavigation: true,
      screenReaderOptimizations: true,
      ...options
    }

    this.initialize()
  }

  private initialize(): void {
    if (typeof window === 'undefined') return

    // Create live regions for announcements
    this.createLiveRegions()

    // Set up skip links
    if (this.options.skipLinks) {
      this.createSkipLinks()
    }

    // Set up keyboard navigation
    if (this.options.keyboardNavigation) {
      this.setupKeyboardNavigation()
    }

    // Set up focus management
    if (this.options.manageFocus) {
      this.setupFocusManagement()
    }
  }

  /**
   * Create ARIA live regions for screen reader announcements
   */
  private createLiveRegions(): void {
    const regions: AriaLiveRegion[] = [
      { id: 'sr-polite', politeness: 'polite' },
      { id: 'sr-assertive', politeness: 'assertive' },
      { id: 'sr-status', politeness: 'polite', atomic: true }
    ]

    regions.forEach(region => {
      const element = document.createElement('div')
      element.id = region.id
      element.setAttribute('aria-live', region.politeness)
      element.setAttribute('aria-atomic', region.atomic ? 'true' : 'false')
      element.setAttribute('aria-relevant', region.relevant || 'additions text')
      element.className = 'sr-only'
      element.style.cssText = `
        position: absolute !important;
        width: 1px !important;
        height: 1px !important;
        padding: 0 !important;
        margin: -1px !important;
        overflow: hidden !important;
        clip: rect(0, 0, 0, 0) !important;
        white-space: nowrap !important;
        border: 0 !important;
      `

      document.body.appendChild(element)
      this.liveRegions.set(region.id, element)
    })
  }

  /**
   * Create skip links for keyboard navigation
   */
  private createSkipLinks(): void {
    const skipLinks = [
      { href: '#main-content', text: 'Skip to main content' },
      { href: '#navigation', text: 'Skip to navigation' },
      { href: '#footer', text: 'Skip to footer' }
    ]

    const container = document.createElement('div')
    container.className = 'skip-links'
    container.style.cssText = `
      position: absolute;
      top: -40px;
      left: 6px;
      z-index: 1000;
    `

    skipLinks.forEach(link => {
      const anchor = document.createElement('a')
      anchor.href = link.href
      anchor.textContent = link.text
      anchor.className = 'skip-link'
      anchor.style.cssText = `
        position: absolute;
        left: -10000px;
        top: auto;
        width: 1px;
        height: 1px;
        overflow: hidden;
        background: #000;
        color: #fff;
        padding: 8px 16px;
        text-decoration: none;
        border-radius: 4px;
        font-weight: bold;
      `

      // Show on focus
      anchor.addEventListener('focus', () => {
        anchor.style.cssText = `
          position: absolute;
          left: 6px;
          top: 6px;
          width: auto;
          height: auto;
          overflow: visible;
          background: #000;
          color: #fff;
          padding: 8px 16px;
          text-decoration: none;
          border-radius: 4px;
          font-weight: bold;
          z-index: 1001;
        `
      })

      anchor.addEventListener('blur', () => {
        anchor.style.cssText = `
          position: absolute;
          left: -10000px;
          top: auto;
          width: 1px;
          height: 1px;
          overflow: hidden;
          background: #000;
          color: #fff;
          padding: 8px 16px;
          text-decoration: none;
          border-radius: 4px;
          font-weight: bold;
        `
      })

      container.appendChild(anchor)
    })

    document.body.insertBefore(container, document.body.firstChild)
    this.skipLinksContainer = container
  }

  /**
   * Set up keyboard navigation enhancements
   */
  private setupKeyboardNavigation(): void {
    // Handle escape key to close modals/dropdowns
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        this.handleEscapeKey()
      }
    })

    // Trap focus in modals
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Tab') {
        this.handleTabKey(event)
      }
    })
  }

  /**
   * Set up focus management
   */
  private setupFocusManagement(): void {
    // Track focus changes
    document.addEventListener('focusin', (event) => {
      const target = event.target as HTMLElement
      if (target && target !== document.body) {
        this.focusHistory.push(target)
        // Keep only last 10 focused elements
        if (this.focusHistory.length > 10) {
          this.focusHistory.shift()
        }
      }
    })
  }

  /**
   * Announce message to screen readers
   */
  public announce(message: string, priority: 'polite' | 'assertive' = 'polite'): void {
    const regionId = priority === 'assertive' ? 'sr-assertive' : 'sr-polite'
    const region = this.liveRegions.get(regionId)
    
    if (region) {
      // Clear previous message
      region.textContent = ''
      
      // Add new message after a brief delay to ensure it's announced
      setTimeout(() => {
        region.textContent = message
      }, 100)
    }
  }

  /**
   * Announce status updates
   */
  public announceStatus(message: string): void {
    const region = this.liveRegions.get('sr-status')
    if (region) {
      region.textContent = message
    }
  }

  /**
   * Focus an element and announce it
   */
  public focusElement(element: HTMLElement | string, announce?: string): void {
    const target = typeof element === 'string' ? document.querySelector(element) as HTMLElement : element
    
    if (target) {
      target.focus()
      
      if (announce) {
        this.announce(announce)
      }
    }
  }

  /**
   * Focus the first focusable element in a container
   */
  public focusFirstIn(container: HTMLElement | string): void {
    const containerElement = typeof container === 'string' ? document.querySelector(container) as HTMLElement : container
    
    if (containerElement) {
      const focusable = this.getFocusableElements(containerElement)
      if (focusable.length > 0) {
        focusable[0].focus()
      }
    }
  }

  /**
   * Focus the last focusable element in a container
   */
  public focusLastIn(container: HTMLElement | string): void {
    const containerElement = typeof container === 'string' ? document.querySelector(container) as HTMLElement : container
    
    if (containerElement) {
      const focusable = this.getFocusableElements(containerElement)
      if (focusable.length > 0) {
        focusable[focusable.length - 1].focus()
      }
    }
  }

  /**
   * Trap focus within a container (for modals)
   */
  public trapFocus(container: HTMLElement | string): () => void {
    const containerElement = typeof container === 'string' ? document.querySelector(container) as HTMLElement : container
    
    if (!containerElement) return () => {}

    const focusable = this.getFocusableElements(containerElement)
    const firstFocusable = focusable[0]
    const lastFocusable = focusable[focusable.length - 1]

    const handleTabKey = (event: KeyboardEvent) => {
      if (event.key !== 'Tab') return

      if (event.shiftKey) {
        // Shift + Tab
        if (document.activeElement === firstFocusable) {
          event.preventDefault()
          lastFocusable.focus()
        }
      } else {
        // Tab
        if (document.activeElement === lastFocusable) {
          event.preventDefault()
          firstFocusable.focus()
        }
      }
    }

    containerElement.addEventListener('keydown', handleTabKey)

    // Focus first element
    if (firstFocusable) {
      firstFocusable.focus()
    }

    // Return cleanup function
    return () => {
      containerElement.removeEventListener('keydown', handleTabKey)
    }
  }

  /**
   * Get all focusable elements in a container
   */
  public getFocusableElements(container: HTMLElement): HTMLElement[] {
    const focusableSelectors = [
      'a[href]',
      'button:not([disabled])',
      'input:not([disabled])',
      'select:not([disabled])',
      'textarea:not([disabled])',
      '[tabindex]:not([tabindex="-1"])',
      '[contenteditable="true"]'
    ].join(', ')

    const elements = Array.from(container.querySelectorAll(focusableSelectors)) as HTMLElement[]
    
    return elements.filter(element => {
      return element.offsetWidth > 0 && 
             element.offsetHeight > 0 && 
             !element.hasAttribute('hidden') &&
             window.getComputedStyle(element).visibility !== 'hidden'
    })
  }

  /**
   * Handle escape key press
   */
  private handleEscapeKey(): void {
    // Close any open modals or dropdowns
    const modals = document.querySelectorAll('[role="dialog"][aria-hidden="false"]')
    modals.forEach(modal => {
      const closeButton = modal.querySelector('[data-dismiss="modal"], .modal-close')
      if (closeButton) {
        (closeButton as HTMLElement).click()
      }
    })

    // Close dropdowns
    const dropdowns = document.querySelectorAll('[aria-expanded="true"]')
    dropdowns.forEach(dropdown => {
      dropdown.setAttribute('aria-expanded', 'false')
    })
  }

  /**
   * Handle tab key for focus trapping
   */
  private handleTabKey(event: KeyboardEvent): void {
    const activeModal = document.querySelector('[role="dialog"][aria-hidden="false"]') as HTMLElement
    if (activeModal) {
      const focusable = this.getFocusableElements(activeModal)
      const firstFocusable = focusable[0]
      const lastFocusable = focusable[focusable.length - 1]

      if (event.shiftKey) {
        if (document.activeElement === firstFocusable) {
          event.preventDefault()
          lastFocusable.focus()
        }
      } else {
        if (document.activeElement === lastFocusable) {
          event.preventDefault()
          firstFocusable.focus()
        }
      }
    }
  }

  /**
   * Add ARIA labels and descriptions to elements
   */
  public enhanceElement(element: HTMLElement, options: {
    label?: string
    description?: string
    role?: string
    expanded?: boolean
    controls?: string
    describedBy?: string
  }): void {
    if (options.label) {
      element.setAttribute('aria-label', options.label)
    }

    if (options.description) {
      const descId = `desc-${Math.random().toString(36).substr(2, 9)}`
      const descElement = document.createElement('div')
      descElement.id = descId
      descElement.className = 'sr-only'
      descElement.textContent = options.description
      element.parentNode?.insertBefore(descElement, element.nextSibling)
      element.setAttribute('aria-describedby', descId)
    }

    if (options.role) {
      element.setAttribute('role', options.role)
    }

    if (options.expanded !== undefined) {
      element.setAttribute('aria-expanded', options.expanded.toString())
    }

    if (options.controls) {
      element.setAttribute('aria-controls', options.controls)
    }

    if (options.describedBy) {
      element.setAttribute('aria-describedby', options.describedBy)
    }
  }

  /**
   * Create accessible headings hierarchy
   */
  public validateHeadingHierarchy(): { valid: boolean; issues: string[] } {
    const headings = Array.from(document.querySelectorAll('h1, h2, h3, h4, h5, h6'))
    const issues: string[] = []
    let previousLevel = 0

    headings.forEach((heading, index) => {
      const level = parseInt(heading.tagName.charAt(1))
      
      if (index === 0 && level !== 1) {
        issues.push('Page should start with an h1 heading')
      }
      
      if (level > previousLevel + 1) {
        issues.push(`Heading level ${level} follows h${previousLevel}, skipping levels`)
      }
      
      previousLevel = level
    })

    return {
      valid: issues.length === 0,
      issues
    }
  }

  /**
   * Cleanup accessibility enhancements
   */
  public cleanup(): void {
    // Remove live regions
    this.liveRegions.forEach(region => {
      region.remove()
    })
    this.liveRegions.clear()

    // Remove skip links
    if (this.skipLinksContainer) {
      this.skipLinksContainer.remove()
    }

    // Clear focus history
    this.focusHistory = []
  }
}

// Singleton instance
export const accessibilityService = new AccessibilityService()

export default AccessibilityService