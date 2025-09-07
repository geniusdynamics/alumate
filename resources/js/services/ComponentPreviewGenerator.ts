/**
 * Component Preview Image Generation Service
 * 
 * This service generates preview images for components to be used
 * in the GrapeJS Block Manager and component library interface.
 */

import type {
  Component,
  ComponentCategory,
  HeroComponentConfig,
  FormComponentConfig,
  TestimonialComponentConfig,
  StatisticsComponentConfig,
  CTAComponentConfig,
  MediaComponentConfig
} from '@/types/components'

export interface PreviewOptions {
  width?: number
  height?: number
  scale?: number
  format?: 'png' | 'jpeg' | 'webp'
  quality?: number
  background?: string
  padding?: number
  showBorder?: boolean
  borderColor?: string
  borderWidth?: number
}

export interface PreviewResult {
  success: boolean
  imageUrl?: string
  dataUrl?: string
  error?: string
  metadata?: {
    width: number
    height: number
    format: string
    size: number
  }
}

export class ComponentPreviewGenerator {
  private canvas: HTMLCanvasElement | null = null
  private ctx: CanvasRenderingContext2D | null = null
  private defaultOptions: PreviewOptions

  constructor() {
    this.defaultOptions = {
      width: 300,
      height: 200,
      scale: 1,
      format: 'png',
      quality: 0.9,
      background: '#ffffff',
      padding: 20,
      showBorder: true,
      borderColor: '#e5e7eb',
      borderWidth: 1
    }

    this.initializeCanvas()
  }

  /**
   * Generate preview image for a component
   */
  async generatePreview(component: Component, options: PreviewOptions = {}): Promise<PreviewResult> {
    try {
      const opts = { ...this.defaultOptions, ...options }
      
      // Initialize canvas with specified dimensions
      this.setupCanvas(opts.width!, opts.height!, opts.scale!)
      
      // Clear canvas and set background
      this.clearCanvas(opts.background!)
      
      // Draw border if enabled
      if (opts.showBorder) {
        this.drawBorder(opts.borderColor!, opts.borderWidth!)
      }
      
      // Generate category-specific preview
      await this.drawComponentPreview(component, opts)
      
      // Convert to image
      const result = await this.canvasToImage(opts.format!, opts.quality!)
      
      return {
        success: true,
        dataUrl: result.dataUrl,
        imageUrl: result.imageUrl,
        metadata: {
          width: opts.width!,
          height: opts.height!,
          format: opts.format!,
          size: result.size
        }
      }
    } catch (error) {
      return {
        success: false,
        error: error instanceof Error ? error.message : 'Unknown error occurred'
      }
    }
  }

  /**
   * Generate multiple preview images in batch
   */
  async generateBatchPreviews(
    components: Component[], 
    options: PreviewOptions = {}
  ): Promise<Map<string, PreviewResult>> {
    const results = new Map<string, PreviewResult>()
    
    for (const component of components) {
      const result = await this.generatePreview(component, options)
      results.set(component.id, result)
      
      // Add small delay to prevent blocking the UI
      await new Promise(resolve => setTimeout(resolve, 10))
    }
    
    return results
  }

  /**
   * Generate placeholder preview for unknown component types
   */
  generatePlaceholderPreview(
    componentName: string, 
    category: ComponentCategory, 
    options: PreviewOptions = {}
  ): PreviewResult {
    try {
      const opts = { ...this.defaultOptions, ...options }
      
      this.setupCanvas(opts.width!, opts.height!, opts.scale!)
      this.clearCanvas(opts.background!)
      
      if (opts.showBorder) {
        this.drawBorder(opts.borderColor!, opts.borderWidth!)
      }
      
      this.drawPlaceholder(componentName, category, opts)
      
      const dataUrl = this.canvas!.toDataURL(`image/${opts.format}`, opts.quality)
      
      return {
        success: true,
        dataUrl,
        metadata: {
          width: opts.width!,
          height: opts.height!,
          format: opts.format!,
          size: dataUrl.length
        }
      }
    } catch (error) {
      return {
        success: false,
        error: error instanceof Error ? error.message : 'Failed to generate placeholder'
      }
    }
  }

  // Private methods

  private initializeCanvas(): void {
    if (typeof window !== 'undefined') {
      this.canvas = document.createElement('canvas')
      this.ctx = this.canvas.getContext('2d')
    }
  }

  private setupCanvas(width: number, height: number, scale: number): void {
    if (!this.canvas || !this.ctx) {
      throw new Error('Canvas not initialized')
    }

    this.canvas.width = width * scale
    this.canvas.height = height * scale
    this.canvas.style.width = `${width}px`
    this.canvas.style.height = `${height}px`
    
    this.ctx.scale(scale, scale)
    this.ctx.imageSmoothingEnabled = true
    this.ctx.imageSmoothingQuality = 'high'
  }

  private clearCanvas(background: string): void {
    if (!this.ctx || !this.canvas) return

    this.ctx.fillStyle = background
    this.ctx.fillRect(0, 0, this.canvas.width, this.canvas.height)
  }

  private drawBorder(color: string, width: number): void {
    if (!this.ctx || !this.canvas) return

    this.ctx.strokeStyle = color
    this.ctx.lineWidth = width
    this.ctx.strokeRect(0, 0, this.canvas.width, this.canvas.height)
  }

  private async drawComponentPreview(component: Component, options: PreviewOptions): Promise<void> {
    switch (component.category) {
      case 'hero':
        await this.drawHeroPreview(component, component.config as HeroComponentConfig, options)
        break
      case 'forms':
        await this.drawFormPreview(component, component.config as FormComponentConfig, options)
        break
      case 'testimonials':
        await this.drawTestimonialPreview(component, component.config as TestimonialComponentConfig, options)
        break
      case 'statistics':
        await this.drawStatisticsPreview(component, component.config as StatisticsComponentConfig, options)
        break
      case 'ctas':
        await this.drawCTAPreview(component, component.config as CTAComponentConfig, options)
        break
      case 'media':
        await this.drawMediaPreview(component, component.config as MediaComponentConfig, options)
        break
      default:
        this.drawPlaceholder(component.name, component.category, options)
    }
  }

  private async drawHeroPreview(
    component: Component, 
    config: HeroComponentConfig, 
    options: PreviewOptions
  ): Promise<void> {
    if (!this.ctx) return

    const padding = options.padding || 20
    const width = (options.width || 300) - (padding * 2)
    const height = (options.height || 200) - (padding * 2)

    // Draw background gradient
    const gradient = this.ctx.createLinearGradient(padding, padding, padding + width, padding + height)
    gradient.addColorStop(0, '#3b82f6')
    gradient.addColorStop(1, '#1e40af')
    
    this.ctx.fillStyle = gradient
    this.ctx.fillRect(padding, padding, width, height)

    // Draw content overlay
    this.ctx.fillStyle = 'rgba(0, 0, 0, 0.3)'
    this.ctx.fillRect(padding, padding, width, height)

    // Draw headline
    this.ctx.fillStyle = '#ffffff'
    this.ctx.font = 'bold 16px Arial, sans-serif'
    this.ctx.textAlign = 'center'
    
    const headlineText = this.truncateText(config.headline, 30)
    this.ctx.fillText(headlineText, padding + width / 2, padding + height / 2 - 20)

    // Draw subheading if present
    if (config.subheading) {
      this.ctx.font = '12px Arial, sans-serif'
      const subheadingText = this.truncateText(config.subheading, 40)
      this.ctx.fillText(subheadingText, padding + width / 2, padding + height / 2)
    }

    // Draw CTA button
    if (config.ctaButtons && config.ctaButtons.length > 0) {
      const buttonWidth = 100
      const buttonHeight = 30
      const buttonX = padding + (width - buttonWidth) / 2
      const buttonY = padding + height / 2 + 20

      this.ctx.fillStyle = '#ffffff'
      this.ctx.fillRect(buttonX, buttonY, buttonWidth, buttonHeight)
      
      this.ctx.fillStyle = '#1e40af'
      this.ctx.font = '12px Arial, sans-serif'
      this.ctx.textAlign = 'center'
      this.ctx.fillText(
        this.truncateText(config.ctaButtons[0].text, 12),
        buttonX + buttonWidth / 2,
        buttonY + buttonHeight / 2 + 4
      )
    }

    // Add category label
    this.drawCategoryLabel('Hero', options)
  }

  private async drawFormPreview(
    component: Component, 
    config: FormComponentConfig, 
    options: PreviewOptions
  ): Promise<void> {
    if (!this.ctx) return

    const padding = options.padding || 20
    const width = (options.width || 300) - (padding * 2)
    const height = (options.height || 200) - (padding * 2)

    // Draw form background
    this.ctx.fillStyle = '#f9fafb'
    this.ctx.fillRect(padding, padding, width, height)

    // Draw form border
    this.ctx.strokeStyle = '#e5e7eb'
    this.ctx.lineWidth = 1
    this.ctx.strokeRect(padding, padding, width, height)

    // Draw title if present
    let yOffset = padding + 20
    if (config.title) {
      this.ctx.fillStyle = '#111827'
      this.ctx.font = 'bold 14px Arial, sans-serif'
      this.ctx.textAlign = 'left'
      this.ctx.fillText(this.truncateText(config.title, 25), padding + 15, yOffset)
      yOffset += 25
    }

    // Draw form fields
    const fieldHeight = 20
    const fieldSpacing = 25
    const maxFields = Math.floor((height - (yOffset - padding) - 40) / fieldSpacing)
    const fieldsToShow = Math.min(config.fields?.length || 0, maxFields)

    for (let i = 0; i < fieldsToShow; i++) {
      const field = config.fields[i]
      const fieldY = yOffset + (i * fieldSpacing)

      // Draw field label
      this.ctx.fillStyle = '#374151'
      this.ctx.font = '11px Arial, sans-serif'
      this.ctx.fillText(this.truncateText(field.label, 20), padding + 15, fieldY)

      // Draw field input
      this.ctx.fillStyle = '#ffffff'
      this.ctx.fillRect(padding + 15, fieldY + 5, width - 30, fieldHeight)
      this.ctx.strokeStyle = '#d1d5db'
      this.ctx.strokeRect(padding + 15, fieldY + 5, width - 30, fieldHeight)

      // Draw placeholder text
      if (field.placeholder) {
        this.ctx.fillStyle = '#9ca3af'
        this.ctx.font = '10px Arial, sans-serif'
        this.ctx.fillText(
          this.truncateText(field.placeholder, 25),
          padding + 20,
          fieldY + 17
        )
      }
    }

    // Draw submit button
    const buttonY = height + padding - 35
    this.ctx.fillStyle = '#3b82f6'
    this.ctx.fillRect(padding + 15, buttonY, 80, 25)
    
    this.ctx.fillStyle = '#ffffff'
    this.ctx.font = '11px Arial, sans-serif'
    this.ctx.textAlign = 'center'
    this.ctx.fillText('Submit', padding + 55, buttonY + 16)

    // Add category label
    this.drawCategoryLabel('Form', options)
  }

  private async drawTestimonialPreview(
    component: Component, 
    config: TestimonialComponentConfig, 
    options: PreviewOptions
  ): Promise<void> {
    if (!this.ctx) return

    const padding = options.padding || 20
    const width = (options.width || 300) - (padding * 2)
    const height = (options.height || 200) - (padding * 2)

    // Draw testimonial background
    this.ctx.fillStyle = '#f8fafc'
    this.ctx.fillRect(padding, padding, width, height)

    if (config.testimonials && config.testimonials.length > 0) {
      const testimonial = config.testimonials[0]
      
      // Draw quote
      this.ctx.fillStyle = '#1f2937'
      this.ctx.font = '12px Arial, sans-serif'
      this.ctx.textAlign = 'left'
      
      const quoteText = `"${this.truncateText(testimonial.content.quote, 80)}"`
      this.wrapText(quoteText, padding + 15, padding + 30, width - 30, 16)

      // Draw author info
      const authorY = height + padding - 40
      
      // Draw author photo placeholder if enabled
      if (config.showAuthorPhoto) {
        this.ctx.fillStyle = '#e5e7eb'
        this.ctx.beginPath()
        this.ctx.arc(padding + 25, authorY, 15, 0, 2 * Math.PI)
        this.ctx.fill()
        
        this.ctx.fillStyle = '#9ca3af'
        this.ctx.font = '10px Arial, sans-serif'
        this.ctx.textAlign = 'center'
        this.ctx.fillText('👤', padding + 25, authorY + 3)
      }

      // Draw author name
      this.ctx.fillStyle = '#374151'
      this.ctx.font = 'bold 11px Arial, sans-serif'
      this.ctx.textAlign = 'left'
      const nameX = config.showAuthorPhoto ? padding + 45 : padding + 15
      this.ctx.fillText(this.truncateText(testimonial.author.name, 20), nameX, authorY - 5)

      // Draw author title if enabled
      if (config.showAuthorTitle && testimonial.author.title) {
        this.ctx.fillStyle = '#6b7280'
        this.ctx.font = '10px Arial, sans-serif'
        this.ctx.fillText(this.truncateText(testimonial.author.title, 25), nameX, authorY + 8)
      }
    }

    // Add category label
    this.drawCategoryLabel('Testimonials', options)
  }

  private async drawStatisticsPreview(
    component: Component, 
    config: StatisticsComponentConfig, 
    options: PreviewOptions
  ): Promise<void> {
    if (!this.ctx) return

    const padding = options.padding || 20
    const width = (options.width || 300) - (padding * 2)
    const height = (options.height || 200) - (padding * 2)

    // Draw statistics background
    this.ctx.fillStyle = '#ffffff'
    this.ctx.fillRect(padding, padding, width, height)

    // Draw sample statistics
    const stats = [
      { value: '10K+', label: 'Alumni' },
      { value: '95%', label: 'Success' },
      { value: '500+', label: 'Companies' },
      { value: '50+', label: 'Countries' }
    ]

    const statWidth = width / 2
    const statHeight = height / 2

    stats.slice(0, 4).forEach((stat, index) => {
      const x = padding + (index % 2) * statWidth
      const y = padding + Math.floor(index / 2) * statHeight

      // Draw stat container
      this.ctx.strokeStyle = '#e5e7eb'
      this.ctx.lineWidth = 1
      this.ctx.strokeRect(x + 5, y + 5, statWidth - 10, statHeight - 10)

      // Draw stat value
      this.ctx.fillStyle = '#3b82f6'
      this.ctx.font = 'bold 18px Arial, sans-serif'
      this.ctx.textAlign = 'center'
      this.ctx.fillText(stat.value, x + statWidth / 2, y + statHeight / 2 - 5)

      // Draw stat label
      this.ctx.fillStyle = '#6b7280'
      this.ctx.font = '11px Arial, sans-serif'
      this.ctx.fillText(stat.label, x + statWidth / 2, y + statHeight / 2 + 15)
    })

    // Add category label
    this.drawCategoryLabel('Statistics', options)
  }

  private async drawCTAPreview(
    component: Component, 
    config: CTAComponentConfig, 
    options: PreviewOptions
  ): Promise<void> {
    if (!this.ctx) return

    const padding = options.padding || 20
    const width = (options.width || 300) - (padding * 2)
    const height = (options.height || 200) - (padding * 2)

    if (config.type === 'banner' && config.bannerConfig) {
      // Draw banner background
      const gradient = this.ctx.createLinearGradient(padding, padding, padding + width, padding + height)
      gradient.addColorStop(0, '#3b82f6')
      gradient.addColorStop(1, '#1e40af')
      
      this.ctx.fillStyle = gradient
      this.ctx.fillRect(padding, padding, width, height)

      // Draw banner content
      this.ctx.fillStyle = '#ffffff'
      this.ctx.font = 'bold 14px Arial, sans-serif'
      this.ctx.textAlign = 'center'
      
      if (config.bannerConfig.title) {
        this.ctx.fillText(
          this.truncateText(config.bannerConfig.title, 25),
          padding + width / 2,
          padding + height / 2 - 20
        )
      }

      if (config.bannerConfig.subtitle) {
        this.ctx.font = '11px Arial, sans-serif'
        this.ctx.fillText(
          this.truncateText(config.bannerConfig.subtitle, 35),
          padding + width / 2,
          padding + height / 2
        )
      }

      // Draw CTA button
      const buttonWidth = 100
      const buttonHeight = 30
      const buttonX = padding + (width - buttonWidth) / 2
      const buttonY = padding + height / 2 + 20

      this.ctx.fillStyle = '#ffffff'
      this.ctx.fillRect(buttonX, buttonY, buttonWidth, buttonHeight)
      
      this.ctx.fillStyle = '#1e40af'
      this.ctx.font = '12px Arial, sans-serif'
      const buttonText = config.bannerConfig.primaryCTA?.text || 'Click Here'
      this.ctx.fillText(this.truncateText(buttonText, 12), buttonX + buttonWidth / 2, buttonY + buttonHeight / 2 + 4)
    } else {
      // Draw simple button
      this.ctx.fillStyle = '#f9fafb'
      this.ctx.fillRect(padding, padding, width, height)

      const buttonWidth = 120
      const buttonHeight = 40
      const buttonX = padding + (width - buttonWidth) / 2
      const buttonY = padding + (height - buttonHeight) / 2

      this.ctx.fillStyle = '#3b82f6'
      this.ctx.fillRect(buttonX, buttonY, buttonWidth, buttonHeight)
      
      this.ctx.fillStyle = '#ffffff'
      this.ctx.font = '12px Arial, sans-serif'
      this.ctx.textAlign = 'center'
      
      const buttonText = config.buttonConfig?.text || config.inlineLinkConfig?.text || 'Click Here'
      this.ctx.fillText(this.truncateText(buttonText, 15), buttonX + buttonWidth / 2, buttonY + buttonHeight / 2 + 4)
    }

    // Add category label
    this.drawCategoryLabel('CTA', options)
  }

  private async drawMediaPreview(
    component: Component, 
    config: MediaComponentConfig, 
    options: PreviewOptions
  ): Promise<void> {
    if (!this.ctx) return

    const padding = options.padding || 20
    const width = (options.width || 300) - (padding * 2)
    const height = (options.height || 200) - (padding * 2)

    // Draw media background
    this.ctx.fillStyle = '#f3f4f6'
    this.ctx.fillRect(padding, padding, width, height)

    // Draw media grid
    const cols = config.layout === 'single' ? 1 : 2
    const rows = config.layout === 'single' ? 1 : 2
    const itemWidth = (width - 15) / cols
    const itemHeight = (height - 15) / rows

    for (let row = 0; row < rows; row++) {
      for (let col = 0; col < cols; col++) {
        const x = padding + 5 + col * (itemWidth + 5)
        const y = padding + 5 + row * (itemHeight + 5)

        // Draw media placeholder
        this.ctx.fillStyle = '#e5e7eb'
        this.ctx.fillRect(x, y, itemWidth, itemHeight)

        // Draw media icon
        this.ctx.fillStyle = '#9ca3af'
        this.ctx.font = '20px Arial, sans-serif'
        this.ctx.textAlign = 'center'
        
        const icon = config.type === 'video-embed' ? '▶️' : '🖼️'
        this.ctx.fillText(icon, x + itemWidth / 2, y + itemHeight / 2 + 7)
      }
    }

    // Add category label
    this.drawCategoryLabel('Media', options)
  }

  private drawPlaceholder(componentName: string, category: ComponentCategory, options: PreviewOptions): void {
    if (!this.ctx) return

    const padding = options.padding || 20
    const width = (options.width || 300) - (padding * 2)
    const height = (options.height || 200) - (padding * 2)

    // Draw placeholder background
    this.ctx.fillStyle = '#f9fafb'
    this.ctx.fillRect(padding, padding, width, height)

    // Draw dashed border
    this.ctx.strokeStyle = '#d1d5db'
    this.ctx.lineWidth = 2
    this.ctx.setLineDash([5, 5])
    this.ctx.strokeRect(padding, padding, width, height)
    this.ctx.setLineDash([])

    // Draw component name
    this.ctx.fillStyle = '#374151'
    this.ctx.font = 'bold 14px Arial, sans-serif'
    this.ctx.textAlign = 'center'
    this.ctx.fillText(
      this.truncateText(componentName, 20),
      padding + width / 2,
      padding + height / 2 - 10
    )

    // Draw category
    this.ctx.fillStyle = '#6b7280'
    this.ctx.font = '11px Arial, sans-serif'
    this.ctx.fillText(
      category.charAt(0).toUpperCase() + category.slice(1),
      padding + width / 2,
      padding + height / 2 + 10
    )
  }

  private drawCategoryLabel(category: string, options: PreviewOptions): void {
    if (!this.ctx) return

    const padding = options.padding || 20
    const width = options.width || 300

    // Draw category label background
    this.ctx.fillStyle = 'rgba(59, 130, 246, 0.9)'
    this.ctx.fillRect(width - 60, 5, 55, 20)

    // Draw category label text
    this.ctx.fillStyle = '#ffffff'
    this.ctx.font = '10px Arial, sans-serif'
    this.ctx.textAlign = 'center'
    this.ctx.fillText(category, width - 32.5, 17)
  }

  private truncateText(text: string, maxLength: number): string {
    if (text.length <= maxLength) return text
    return text.substring(0, maxLength - 3) + '...'
  }

  private wrapText(text: string, x: number, y: number, maxWidth: number, lineHeight: number): void {
    if (!this.ctx) return

    const words = text.split(' ')
    let line = ''
    let currentY = y

    for (let n = 0; n < words.length; n++) {
      const testLine = line + words[n] + ' '
      const metrics = this.ctx.measureText(testLine)
      const testWidth = metrics.width

      if (testWidth > maxWidth && n > 0) {
        this.ctx.fillText(line, x, currentY)
        line = words[n] + ' '
        currentY += lineHeight
      } else {
        line = testLine
      }
    }
    this.ctx.fillText(line, x, currentY)
  }

  private async canvasToImage(format: string, quality: number): Promise<{
    dataUrl: string
    imageUrl?: string
    size: number
  }> {
    if (!this.canvas) {
      throw new Error('Canvas not available')
    }

    const dataUrl = this.canvas.toDataURL(`image/${format}`, quality)
    
    return {
      dataUrl,
      size: dataUrl.length
    }
  }
}

// Export singleton instance
export const componentPreviewGenerator = new ComponentPreviewGenerator()