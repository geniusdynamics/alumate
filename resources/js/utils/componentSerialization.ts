/**
 * Component Serialization Utilities for GrapeJS Integration
 * 
 * This module provides utilities for serializing and deserializing
 * Component Library components to/from GrapeJS data format.
 */

import type {
  Component,
  ComponentCategory,
  GrapeJSSerializationData,
  HeroComponentConfig,
  FormComponentConfig,
  TestimonialComponentConfig,
  StatisticsComponentConfig,
  CTAComponentConfig,
  MediaComponentConfig
} from '@/types/components'

export interface SerializationOptions {
  includeMetadata?: boolean
  preserveIds?: boolean
  validateOnDeserialize?: boolean
  compressData?: boolean
}

export interface SerializationResult {
  success: boolean
  data?: GrapeJSSerializationData
  errors?: string[]
  warnings?: string[]
}

export interface DeserializationResult {
  success: boolean
  components?: Component[]
  errors?: string[]
  warnings?: string[]
}

export class ComponentSerializer {
  private options: SerializationOptions

  constructor(options: SerializationOptions = {}) {
    this.options = {
      includeMetadata: true,
      preserveIds: true,
      validateOnDeserialize: true,
      compressData: false,
      ...options
    }
  }

  /**
   * Serialize Component Library components to GrapeJS format
   */
  serialize(components: Component[]): SerializationResult {
    try {
      const errors: string[] = []
      const warnings: string[] = []

      // Validate components before serialization
      const validComponents = components.filter(component => {
        const validation = this.validateComponent(component)
        if (!validation.valid) {
          errors.push(`Component ${component.id}: ${validation.errors.join(', ')}`)
          return false
        }
        return true
      })

      if (validComponents.length === 0) {
        return {
          success: false,
          errors: ['No valid components to serialize', ...errors]
        }
      }

      // Generate GrapeJS serialization data
      const grapeJSData: GrapeJSSerializationData = {
        html: this.generateHTML(validComponents),
        css: this.generateCSS(validComponents),
        components: this.generateComponents(validComponents),
        styles: this.generateStyles(validComponents),
        assets: this.generateAssets(validComponents)
      }

      // Compress data if requested
      if (this.options.compressData) {
        grapeJSData.html = this.compressHTML(grapeJSData.html)
        grapeJSData.css = this.compressCSS(grapeJSData.css)
      }

      return {
        success: true,
        data: grapeJSData,
        errors: errors.length > 0 ? errors : undefined,
        warnings: warnings.length > 0 ? warnings : undefined
      }
    } catch (error) {
      return {
        success: false,
        errors: [`Serialization failed: ${error instanceof Error ? error.message : 'Unknown error'}`]
      }
    }
  }

  /**
   * Deserialize GrapeJS data back to Component Library format
   */
  deserialize(data: GrapeJSSerializationData): DeserializationResult {
    try {
      const errors: string[] = []
      const warnings: string[] = []
      const components: Component[] = []

      // Validate GrapeJS data structure
      if (!this.validateGrapeJSData(data)) {
        return {
          success: false,
          errors: ['Invalid GrapeJS data structure']
        }
      }

      // Extract components from GrapeJS data
      data.components.forEach((grapeComponent, index) => {
        try {
          const component = this.extractComponent(grapeComponent, data)
          if (component) {
            // Validate deserialized component if requested
            if (this.options.validateOnDeserialize) {
              const validation = this.validateComponent(component)
              if (!validation.valid) {
                warnings.push(`Component ${index}: ${validation.errors.join(', ')}`)
              }
            }
            components.push(component)
          }
        } catch (error) {
          errors.push(`Failed to extract component ${index}: ${error instanceof Error ? error.message : 'Unknown error'}`)
        }
      })

      return {
        success: components.length > 0,
        components,
        errors: errors.length > 0 ? errors : undefined,
        warnings: warnings.length > 0 ? warnings : undefined
      }
    } catch (error) {
      return {
        success: false,
        errors: [`Deserialization failed: ${error instanceof Error ? error.message : 'Unknown error'}`]
      }
    }
  }

  /**
   * Convert a single component to GrapeJS component format
   */
  componentToGrapeJS(component: Component): any {
    const baseComponent = {
      type: `component-${component.category}`,
      tagName: this.getTagNameForCategory(component.category),
      attributes: {
        'data-component-id': component.id,
        'data-component-type': component.type,
        'data-component-category': component.category,
        'data-tenant-id': component.tenantId,
        class: `component-${component.category} component-${component.type}`
      },
      components: this.generateComponentContent(component),
      style: this.generateComponentStyles(component),
      traits: this.generateComponentTraits(component)
    }

    // Add category-specific properties
    return this.enhanceComponentForCategory(baseComponent, component)
  }

  /**
   * Convert GrapeJS component back to Component Library format
   */
  grapeJSToComponent(grapeComponent: any, grapeJSData: GrapeJSSerializationData): Component | null {
    try {
      // Extract basic component information
      const componentId = grapeComponent.attributes?.['data-component-id']
      const componentType = grapeComponent.attributes?.['data-component-type']
      const componentCategory = grapeComponent.attributes?.['data-component-category'] as ComponentCategory
      const tenantId = grapeComponent.attributes?.['data-tenant-id']

      if (!componentId || !componentCategory) {
        throw new Error('Missing required component attributes')
      }

      // Extract configuration based on category
      const config = this.extractConfigForCategory(grapeComponent, componentCategory, grapeJSData)

      // Create component object
      const component: Component = {
        id: componentId,
        tenantId: tenantId || '',
        name: this.extractComponentName(grapeComponent),
        slug: `component-${componentId}`,
        category: componentCategory,
        type: componentType || 'default',
        description: this.extractComponentDescription(grapeComponent),
        config,
        metadata: this.options.includeMetadata ? this.extractMetadata(grapeComponent) : {},
        version: '1.0.0',
        isActive: true,
        createdAt: new Date().toISOString(),
        updatedAt: new Date().toISOString()
      }

      return component
    } catch (error) {
      console.error('Failed to convert GrapeJS component:', error)
      return null
    }
  }

  // Private methods for serialization

  private generateHTML(components: Component[]): string {
    return components.map(component => {
      const htmlContent = this.generateComponentHTML(component)
      return `<!-- Component: ${component.name} (${component.id}) -->\n${htmlContent}`
    }).join('\n\n')
  }

  private generateCSS(components: Component[]): string {
    const cssRules: string[] = []

    components.forEach(component => {
      const componentCSS = this.generateComponentCSS(component)
      if (componentCSS) {
        cssRules.push(`/* Component: ${component.name} (${component.id}) */`)
        cssRules.push(componentCSS)
      }
    })

    return cssRules.join('\n\n')
  }

  private generateComponents(components: Component[]): any[] {
    return components.map(component => this.componentToGrapeJS(component))
  }

  private generateStyles(components: Component[]): any[] {
    const styles: any[] = []

    components.forEach(component => {
      const componentStyles = this.extractComponentStyleRules(component)
      styles.push(...componentStyles)
    })

    return styles
  }

  private generateAssets(components: Component[]): any[] {
    const assets: any[] = []
    const assetMap = new Map<string, any>()

    components.forEach(component => {
      const componentAssets = this.extractComponentAssets(component)
      componentAssets.forEach(asset => {
        if (!assetMap.has(asset.src)) {
          assetMap.set(asset.src, asset)
          assets.push(asset)
        }
      })
    })

    return assets
  }

  private generateComponentHTML(component: Component): string {
    switch (component.category) {
      case 'hero':
        return this.generateHeroHTML(component, component.config as HeroComponentConfig)
      case 'forms':
        return this.generateFormHTML(component, component.config as FormComponentConfig)
      case 'testimonials':
        return this.generateTestimonialHTML(component, component.config as TestimonialComponentConfig)
      case 'statistics':
        return this.generateStatisticsHTML(component, component.config as StatisticsComponentConfig)
      case 'ctas':
        return this.generateCTAHTML(component, component.config as CTAComponentConfig)
      case 'media':
        return this.generateMediaHTML(component, component.config as MediaComponentConfig)
      default:
        return `<div class="component-placeholder" data-component-id="${component.id}">
          <h3>${component.name}</h3>
          <p>${component.description || 'Component content'}</p>
        </div>`
    }
  }

  private generateComponentCSS(component: Component): string {
    const baseCSS = `
      .component-${component.id} {
        position: relative;
        margin: 1rem 0;
      }
    `

    const categoryCSS = this.generateCategoryCSSRules(component)
    
    return baseCSS + categoryCSS
  }

  private generateHeroHTML(component: Component, config: HeroComponentConfig): string {
    const backgroundHTML = config.backgroundMedia ? this.generateBackgroundHTML(config.backgroundMedia) : ''
    const ctaButtons = config.ctaButtons.map(cta => 
      `<a href="${cta.url}" class="cta-button cta-${cta.style} cta-${cta.size}">${cta.text}</a>`
    ).join('')

    return `
      <section class="hero-section hero-${config.audienceType} hero-${config.layout}" data-component-id="${component.id}">
        ${backgroundHTML}
        <div class="hero-content">
          <h${config.headingLevel} class="hero-headline">${config.headline}</h${config.headingLevel}>
          ${config.subheading ? `<p class="hero-subheading">${config.subheading}</p>` : ''}
          ${config.description ? `<p class="hero-description">${config.description}</p>` : ''}
          ${ctaButtons ? `<div class="hero-cta-container">${ctaButtons}</div>` : ''}
        </div>
      </section>
    `
  }

  private generateFormHTML(component: Component, config: FormComponentConfig): string {
    const fields = config.fields.map(field => `
      <div class="form-field form-field-${field.type}">
        <label for="${field.id}" class="form-label">${field.label}</label>
        <${this.getInputTagName(field.type)} 
          id="${field.id}" 
          name="${field.name}" 
          type="${field.type}"
          ${field.placeholder ? `placeholder="${field.placeholder}"` : ''}
          ${field.required ? 'required' : ''}
          class="form-input"
        ${field.type === 'textarea' ? '></textarea>' : '/>'}
      </div>
    `).join('')

    return `
      <form class="form-component form-${config.layout}" data-component-id="${component.id}" method="${config.submission.method}" action="${config.submission.action}">
        ${config.title ? `<h2 class="form-title">${config.title}</h2>` : ''}
        ${config.description ? `<p class="form-description">${config.description}</p>` : ''}
        <div class="form-fields">${fields}</div>
        <button type="submit" class="form-submit">Submit</button>
      </form>
    `
  }

  private generateTestimonialHTML(component: Component, config: TestimonialComponentConfig): string {
    const testimonials = config.testimonials.slice(0, 3).map(testimonial => `
      <div class="testimonial-item" data-testimonial-id="${testimonial.id}">
        <blockquote class="testimonial-quote">"${testimonial.content.quote}"</blockquote>
        <div class="testimonial-author">
          ${config.showAuthorPhoto && testimonial.author.photo ? 
            `<img src="${testimonial.author.photo.url}" alt="${testimonial.author.name}" class="author-photo" />` : ''}
          <div class="author-info">
            <div class="author-name">${testimonial.author.name}</div>
            ${config.showAuthorTitle && testimonial.author.title ? 
              `<div class="author-title">${testimonial.author.title}</div>` : ''}
          </div>
        </div>
      </div>
    `).join('')

    return `
      <section class="testimonial-section testimonial-${config.layout}" data-component-id="${component.id}">
        <div class="testimonial-container">${testimonials}</div>
      </section>
    `
  }

  private generateStatisticsHTML(component: Component, config: StatisticsComponentConfig): string {
    return `
      <section class="statistics-section statistics-${config.displayType}" data-component-id="${component.id}">
        <div class="statistics-container">
          <div class="statistic-item">
            <div class="statistic-value">10,000+</div>
            <div class="statistic-label">Alumni Connected</div>
          </div>
          <div class="statistic-item">
            <div class="statistic-value">95%</div>
            <div class="statistic-label">Success Rate</div>
          </div>
        </div>
      </section>
    `
  }

  private generateCTAHTML(component: Component, config: CTAComponentConfig): string {
    const ctaConfig = config.buttonConfig || config.bannerConfig || config.inlineLinkConfig

    if (config.type === 'banner' && config.bannerConfig) {
      return `
        <section class="cta-banner" data-component-id="${component.id}">
          <div class="cta-content">
            ${config.bannerConfig.title ? `<h2 class="cta-title">${config.bannerConfig.title}</h2>` : ''}
            ${config.bannerConfig.subtitle ? `<p class="cta-subtitle">${config.bannerConfig.subtitle}</p>` : ''}
            <a href="${ctaConfig?.url || '#'}" class="cta-button">${ctaConfig?.text || 'Click Here'}</a>
          </div>
        </section>
      `
    }

    return `
      <div class="cta-component cta-${config.type}" data-component-id="${component.id}">
        <a href="${ctaConfig?.url || '#'}" class="cta-${config.type}">${ctaConfig?.text || 'Click Here'}</a>
      </div>
    `
  }

  private generateMediaHTML(component: Component, config: MediaComponentConfig): string {
    const mediaItems = config.mediaAssets.slice(0, 6).map((asset, index) => {
      if (asset.type === 'image') {
        return `<img src="${asset.url}" alt="${asset.alt || `Media item ${index + 1}`}" class="media-image" />`
      } else {
        return `<video src="${asset.url}" class="media-video" controls></video>`
      }
    }).join('')

    return `
      <section class="media-section media-${config.type}" data-component-id="${component.id}">
        <div class="media-container">${mediaItems}</div>
      </section>
    `
  }

  // Private methods for deserialization

  private validateGrapeJSData(data: GrapeJSSerializationData): boolean {
    return !!(data.html && data.css && data.components && Array.isArray(data.components))
  }

  private extractComponent(grapeComponent: any, grapeJSData: GrapeJSSerializationData): Component | null {
    return this.grapeJSToComponent(grapeComponent, grapeJSData)
  }

  private extractConfigForCategory(grapeComponent: any, category: ComponentCategory, grapeJSData: GrapeJSSerializationData): any {
    switch (category) {
      case 'hero':
        return this.extractHeroConfig(grapeComponent, grapeJSData)
      case 'forms':
        return this.extractFormConfig(grapeComponent, grapeJSData)
      case 'testimonials':
        return this.extractTestimonialConfig(grapeComponent, grapeJSData)
      case 'statistics':
        return this.extractStatisticsConfig(grapeComponent, grapeJSData)
      case 'ctas':
        return this.extractCTAConfig(grapeComponent, grapeJSData)
      case 'media':
        return this.extractMediaConfig(grapeComponent, grapeJSData)
      default:
        return {}
    }
  }

  private extractHeroConfig(grapeComponent: any, grapeJSData: GrapeJSSerializationData): HeroComponentConfig {
    // Extract hero configuration from GrapeJS component
    return {
      headline: this.extractTextContent(grapeComponent, '.hero-headline') || 'Hero Headline',
      subheading: this.extractTextContent(grapeComponent, '.hero-subheading'),
      description: this.extractTextContent(grapeComponent, '.hero-description'),
      audienceType: grapeComponent.attributes?.['data-audience-type'] || 'individual',
      layout: grapeComponent.attributes?.['data-layout'] || 'centered',
      textAlignment: 'center',
      contentPosition: 'center',
      headingLevel: 1,
      ctaButtons: this.extractCTAButtons(grapeComponent),
      statistics: this.extractStatistics(grapeComponent)
    } as HeroComponentConfig
  }

  private extractFormConfig(grapeComponent: any, grapeJSData: GrapeJSSerializationData): FormComponentConfig {
    return {
      title: this.extractTextContent(grapeComponent, '.form-title'),
      description: this.extractTextContent(grapeComponent, '.form-description'),
      fields: this.extractFormFields(grapeComponent),
      layout: grapeComponent.attributes?.['data-form-layout'] || 'single-column',
      submission: {
        method: grapeComponent.attributes?.method || 'POST',
        action: grapeComponent.attributes?.action || '/submit'
      },
      theme: 'default'
    } as FormComponentConfig
  }

  private extractTestimonialConfig(grapeComponent: any, grapeJSData: GrapeJSSerializationData): TestimonialComponentConfig {
    return {
      layout: grapeComponent.attributes?.['data-layout'] || 'grid',
      testimonials: this.extractTestimonials(grapeComponent),
      showAuthorPhoto: true,
      showAuthorTitle: true,
      showAuthorCompany: true,
      showRating: false
    } as TestimonialComponentConfig
  }

  private extractStatisticsConfig(grapeComponent: any, grapeJSData: GrapeJSSerializationData): StatisticsComponentConfig {
    return {
      displayType: grapeComponent.attributes?.['data-display-type'] || 'counters',
      layout: 'grid',
      theme: 'default',
      spacing: 'default',
      counterSize: 'md',
      progressSize: 'md',
      chartSize: 'md',
      showLabels: true,
      showValues: true,
      showTargets: false,
      gridColumns: { desktop: 4, tablet: 2, mobile: 1 },
      animation: {
        enabled: true,
        trigger: 'scroll',
        duration: 2000,
        delay: 0,
        stagger: 200,
        easing: 'ease-out'
      },
      realTimeData: {
        enabled: false,
        sources: [],
        refreshInterval: 300000
      },
      accessibility: {
        announceUpdates: false,
        respectReducedMotion: true
      },
      dataRefresh: {
        enabled: false,
        interval: 300000,
        retryAttempts: 3
      },
      errorHandling: {
        showErrors: false,
        errorMessage: 'Unable to load statistics',
        allowRetry: true
      }
    } as StatisticsComponentConfig
  }

  private extractCTAConfig(grapeComponent: any, grapeJSData: GrapeJSSerializationData): CTAComponentConfig {
    const ctaType = grapeComponent.attributes?.['data-cta-type'] || 'button'
    
    const baseConfig: CTAComponentConfig = {
      type: ctaType as any,
      theme: 'default',
      trackingEnabled: false
    }

    if (ctaType === 'button') {
      baseConfig.buttonConfig = {
        text: this.extractTextContent(grapeComponent, '.cta-button') || 'Click Here',
        url: this.extractHref(grapeComponent, '.cta-button') || '#',
        style: 'primary',
        size: 'md'
      }
    }

    return baseConfig
  }

  private extractMediaConfig(grapeComponent: any, grapeJSData: GrapeJSSerializationData): MediaComponentConfig {
    return {
      type: grapeComponent.attributes?.['data-media-type'] || 'image-gallery',
      layout: 'grid',
      theme: 'default',
      spacing: 'default',
      mediaAssets: this.extractMediaAssets(grapeComponent),
      optimization: {
        webpSupport: true,
        avifSupport: false,
        lazyLoading: true,
        responsiveImages: true,
        cdnEnabled: false,
        compressionLevel: 'medium'
      },
      performance: {
        lazyLoading: true,
        preloading: false,
        caching: true,
        compressionEnabled: true,
        cdnDelivery: false,
        bandwidthAdaptive: false,
        mobileOptimization: true
      },
      accessibility: {
        altTextRequired: true,
        captionsRequired: false,
        keyboardNavigation: true,
        screenReaderSupport: true,
        highContrastMode: false,
        focusManagement: true
      },
      mobileOptimized: true
    } as MediaComponentConfig
  }

  // Helper methods

  private validateComponent(component: Component): { valid: boolean; errors: string[] } {
    const errors: string[] = []

    if (!component.id) errors.push('Component ID is required')
    if (!component.name) errors.push('Component name is required')
    if (!component.category) errors.push('Component category is required')
    if (!component.config) errors.push('Component configuration is required')

    return {
      valid: errors.length === 0,
      errors
    }
  }

  private getTagNameForCategory(category: ComponentCategory): string {
    const tagMap: Record<ComponentCategory, string> = {
      hero: 'section',
      forms: 'form',
      testimonials: 'section',
      statistics: 'section',
      ctas: 'div',
      media: 'section'
    }

    return tagMap[category] || 'div'
  }

  private generateComponentContent(component: Component): string {
    return `Component: ${component.name}`
  }

  private generateComponentStyles(component: Component): Record<string, any> {
    return {
      position: 'relative',
      margin: '1rem 0'
    }
  }

  private generateComponentTraits(component: Component): any[] {
    return [
      {
        type: 'text',
        name: 'id',
        label: 'Component ID',
        default: component.id
      }
    ]
  }

  private enhanceComponentForCategory(baseComponent: any, component: Component): any {
    // Add category-specific enhancements
    return baseComponent
  }

  private extractComponentName(grapeComponent: any): string {
    return grapeComponent.attributes?.['data-component-name'] || 'Untitled Component'
  }

  private extractComponentDescription(grapeComponent: any): string {
    return grapeComponent.attributes?.['data-component-description'] || ''
  }

  private extractMetadata(grapeComponent: any): Record<string, any> {
    const metadata: Record<string, any> = {}
    
    Object.keys(grapeComponent.attributes || {}).forEach(key => {
      if (key.startsWith('data-meta-')) {
        const metaKey = key.replace('data-meta-', '')
        metadata[metaKey] = grapeComponent.attributes[key]
      }
    })
    
    return metadata
  }

  private extractTextContent(grapeComponent: any, selector: string): string | undefined {
    // This would typically parse the component tree to find text content
    // For now, return undefined as a placeholder
    return undefined
  }

  private extractHref(grapeComponent: any, selector: string): string | undefined {
    // This would typically parse the component tree to find href attributes
    // For now, return undefined as a placeholder
    return undefined
  }

  private extractCTAButtons(grapeComponent: any): any[] {
    // Extract CTA buttons from component
    return []
  }

  private extractStatistics(grapeComponent: any): any[] {
    // Extract statistics from component
    return []
  }

  private extractFormFields(grapeComponent: any): any[] {
    // Extract form fields from component
    return []
  }

  private extractTestimonials(grapeComponent: any): any[] {
    // Extract testimonials from component
    return []
  }

  private extractMediaAssets(grapeComponent: any): any[] {
    // Extract media assets from component
    return []
  }

  private generateBackgroundHTML(backgroundMedia: any): string {
    // Generate background HTML based on media type
    return ''
  }

  private getInputTagName(fieldType: string): string {
    const tagMap: Record<string, string> = {
      textarea: 'textarea',
      select: 'select'
    }

    return tagMap[fieldType] || 'input'
  }

  private generateCategoryCSSRules(component: Component): string {
    // Generate category-specific CSS rules
    return ''
  }

  private extractComponentStyleRules(component: Component): any[] {
    // Extract style rules for component
    return []
  }

  private extractComponentAssets(component: Component): any[] {
    // Extract assets (images, videos, etc.) from component
    return []
  }

  private compressHTML(html: string): string {
    // Compress HTML by removing unnecessary whitespace
    return html.replace(/\s+/g, ' ').trim()
  }

  private compressCSS(css: string): string {
    // Compress CSS by removing unnecessary whitespace and comments
    return css.replace(/\s+/g, ' ').replace(/\/\*.*?\*\//g, '').trim()
  }
}

// Export singleton instance
export const componentSerializer = new ComponentSerializer()