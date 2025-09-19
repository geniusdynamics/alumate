/**
 * BrandGuidelinesService - Manages brand guidelines and design system enforcement
 */

export interface BrandGuideline {
  id: string
  name: string
  type: 'color' | 'font' | 'spacing' | 'shadow' | 'border'
  value: string
  category: string
  description?: string
  usage?: string[]
  restrictions?: string[]
}

export interface BrandCompliance {
  isCompliant: boolean
  violations: BrandViolation[]
  suggestions: BrandSuggestion[]
  score: number // 0-100
}

export interface BrandViolation {
  type: 'color' | 'font' | 'spacing' | 'shadow' | 'border'
  property: string
  currentValue: string
  message: string
  severity: 'error' | 'warning' | 'info'
}

export interface BrandSuggestion {
  type: 'color' | 'font' | 'spacing' | 'shadow' | 'border'
  property: string
  suggestedValue: string
  reason: string
  autoFixable: boolean
}

export class BrandGuidelinesService {
  private guidelines: BrandGuideline[] = []
  private initialized = false

  constructor() {
    this.initializeGuidelines()
  }

  /**
   * Initialize brand guidelines with default values
   */
  private async initializeGuidelines(): Promise<void> {
    try {
      // Load guidelines from API or use defaults
      const response = await fetch('/api/brand-guidelines')
      if (response.ok) {
        this.guidelines = await response.json()
      } else {
        this.loadDefaultGuidelines()
      }
    } catch (error) {
      console.warn('Failed to load brand guidelines from API, using defaults:', error)
      this.loadDefaultGuidelines()
    }
    
    this.initialized = true
  }

  /**
   * Load default brand guidelines
   */
  private loadDefaultGuidelines(): void {
    this.guidelines = [
      // Brand Colors
      {
        id: 'primary-blue',
        name: 'Primary Blue',
        type: 'color',
        value: '#3B82F6',
        category: 'primary',
        description: 'Main brand color for primary actions and highlights',
        usage: ['buttons', 'links', 'headers', 'accents'],
        restrictions: ['Do not use for large background areas']
      },
      {
        id: 'primary-dark',
        name: 'Primary Dark',
        type: 'color',
        value: '#1E40AF',
        category: 'primary',
        description: 'Darker variant of primary blue for hover states',
        usage: ['button-hover', 'active-states', 'dark-backgrounds']
      },
      {
        id: 'secondary-green',
        name: 'Secondary Green',
        type: 'color',
        value: '#10B981',
        category: 'secondary',
        description: 'Secondary brand color for success states',
        usage: ['success-messages', 'positive-actions', 'growth-indicators']
      },
      {
        id: 'accent-orange',
        name: 'Accent Orange',
        type: 'color',
        value: '#F59E0B',
        category: 'accent',
        description: 'Accent color for warnings and highlights',
        usage: ['warnings', 'highlights', 'call-to-action']
      },
      {
        id: 'neutral-gray',
        name: 'Neutral Gray',
        type: 'color',
        value: '#6B7280',
        category: 'neutral',
        description: 'Neutral color for text and borders',
        usage: ['body-text', 'borders', 'placeholders', 'disabled-states']
      },
      {
        id: 'success-green',
        name: 'Success Green',
        type: 'color',
        value: '#059669',
        category: 'semantic',
        description: 'Success state color',
        usage: ['success-messages', 'completed-states', 'positive-feedback']
      },
      {
        id: 'warning-yellow',
        name: 'Warning Yellow',
        type: 'color',
        value: '#D97706',
        category: 'semantic',
        description: 'Warning state color',
        usage: ['warning-messages', 'caution-states', 'attention-required']
      },
      {
        id: 'error-red',
        name: 'Error Red',
        type: 'color',
        value: '#DC2626',
        category: 'semantic',
        description: 'Error state color',
        usage: ['error-messages', 'destructive-actions', 'validation-errors']
      },

      // Brand Fonts
      {
        id: 'font-inter',
        name: 'Inter',
        type: 'font',
        value: 'Inter, sans-serif',
        category: 'primary',
        description: 'Primary brand font for body text and UI elements',
        usage: ['body-text', 'ui-elements', 'forms', 'buttons']
      },
      {
        id: 'font-roboto',
        name: 'Roboto',
        type: 'font',
        value: 'Roboto, sans-serif',
        category: 'secondary',
        description: 'Secondary font for headings and emphasis',
        usage: ['headings', 'emphasis', 'navigation']
      },
      {
        id: 'font-playfair',
        name: 'Playfair Display',
        type: 'font',
        value: 'Playfair Display, serif',
        category: 'accent',
        description: 'Accent font for special headings and branding',
        usage: ['hero-headings', 'branding', 'special-occasions'],
        restrictions: ['Do not use for body text or small sizes']
      },

      // Spacing Guidelines
      {
        id: 'spacing-xs',
        name: 'Extra Small',
        type: 'spacing',
        value: '0.5rem',
        category: 'spacing',
        description: '8px spacing for tight layouts',
        usage: ['icon-padding', 'tight-spacing', 'form-elements']
      },
      {
        id: 'spacing-sm',
        name: 'Small',
        type: 'spacing',
        value: '0.75rem',
        category: 'spacing',
        description: '12px spacing for compact layouts',
        usage: ['button-padding', 'card-padding', 'list-spacing']
      },
      {
        id: 'spacing-md',
        name: 'Medium',
        type: 'spacing',
        value: '1rem',
        category: 'spacing',
        description: '16px standard spacing unit',
        usage: ['default-padding', 'section-spacing', 'content-margins']
      },
      {
        id: 'spacing-lg',
        name: 'Large',
        type: 'spacing',
        value: '1.5rem',
        category: 'spacing',
        description: '24px spacing for generous layouts',
        usage: ['section-padding', 'hero-spacing', 'content-blocks']
      },
      {
        id: 'spacing-xl',
        name: 'Extra Large',
        type: 'spacing',
        value: '2rem',
        category: 'spacing',
        description: '32px spacing for major sections',
        usage: ['page-sections', 'hero-padding', 'major-separations']
      },

      // Shadow Guidelines
      {
        id: 'shadow-sm',
        name: 'Small Shadow',
        type: 'shadow',
        value: '0 1px 2px 0 rgb(0 0 0 / 0.05)',
        category: 'elevation',
        description: 'Subtle shadow for slight elevation',
        usage: ['buttons', 'form-inputs', 'subtle-cards']
      },
      {
        id: 'shadow-md',
        name: 'Medium Shadow',
        type: 'shadow',
        value: '0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1)',
        category: 'elevation',
        description: 'Standard shadow for cards and modals',
        usage: ['cards', 'dropdowns', 'tooltips', 'floating-elements']
      },
      {
        id: 'shadow-lg',
        name: 'Large Shadow',
        type: 'shadow',
        value: '0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1)',
        category: 'elevation',
        description: 'Prominent shadow for important elements',
        usage: ['modals', 'overlays', 'important-cards', 'navigation']
      },

      // Border Radius Guidelines
      {
        id: 'radius-sm',
        name: 'Small Radius',
        type: 'border',
        value: '0.125rem',
        category: 'border-radius',
        description: '2px border radius for subtle rounding',
        usage: ['form-inputs', 'small-buttons', 'tags']
      },
      {
        id: 'radius-md',
        name: 'Medium Radius',
        type: 'border',
        value: '0.375rem',
        category: 'border-radius',
        description: '6px border radius for standard elements',
        usage: ['buttons', 'cards', 'images', 'containers']
      },
      {
        id: 'radius-lg',
        name: 'Large Radius',
        type: 'border',
        value: '0.5rem',
        category: 'border-radius',
        description: '8px border radius for prominent elements',
        usage: ['hero-sections', 'feature-cards', 'call-to-action']
      }
    ]
  }

  /**
   * Get all brand guidelines
   */
  public async getGuidelines(): Promise<BrandGuideline[]> {
    if (!this.initialized) {
      await this.initializeGuidelines()
    }
    return this.guidelines
  }

  /**
   * Get guidelines by type
   */
  public async getGuidelinesByType(type: BrandGuideline['type']): Promise<BrandGuideline[]> {
    const guidelines = await this.getGuidelines()
    return guidelines.filter(guideline => guideline.type === type)
  }

  /**
   * Get guidelines by category
   */
  public async getGuidelinesByCategory(category: string): Promise<BrandGuideline[]> {
    const guidelines = await this.getGuidelines()
    return guidelines.filter(guideline => guideline.category === category)
  }

  /**
   * Check if a value complies with brand guidelines
   */
  public async isValueCompliant(type: BrandGuideline['type'], value: string): Promise<boolean> {
    const guidelines = await this.getGuidelinesByType(type)
    return guidelines.some(guideline => 
      guideline.value.toLowerCase() === value.toLowerCase()
    )
  }

  /**
   * Validate component styles against brand guidelines
   */
  public async validateComponentStyles(styles: Record<string, any>): Promise<BrandCompliance> {
    const violations: BrandViolation[] = []
    const suggestions: BrandSuggestion[] = []

    // Check colors
    await this.validateColors(styles, violations, suggestions)
    
    // Check fonts
    await this.validateFonts(styles, violations, suggestions)
    
    // Check spacing
    await this.validateSpacing(styles, violations, suggestions)
    
    // Check shadows
    await this.validateShadows(styles, violations, suggestions)
    
    // Check border radius
    await this.validateBorderRadius(styles, violations, suggestions)

    // Calculate compliance score
    const totalChecks = Object.keys(styles).length
    const violationCount = violations.filter(v => v.severity === 'error').length
    const score = totalChecks > 0 ? Math.max(0, Math.round((totalChecks - violationCount) / totalChecks * 100)) : 100

    return {
      isCompliant: violations.filter(v => v.severity === 'error').length === 0,
      violations,
      suggestions,
      score
    }
  }

  /**
   * Validate color properties
   */
  private async validateColors(
    styles: Record<string, any>, 
    violations: BrandViolation[], 
    suggestions: BrandSuggestion[]
  ): Promise<void> {
    const colorProperties = ['color', 'background-color', 'border-color']
    const colorGuidelines = await this.getGuidelinesByType('color')

    for (const property of colorProperties) {
      if (styles[property]) {
        const value = styles[property]
        const isCompliant = await this.isValueCompliant('color', value)
        
        if (!isCompliant) {
          violations.push({
            type: 'color',
            property,
            currentValue: value,
            message: `Color ${value} is not in brand guidelines`,
            severity: 'warning'
          })

          // Find closest brand color
          const closestColor = this.findClosestBrandColor(value, colorGuidelines)
          if (closestColor) {
            suggestions.push({
              type: 'color',
              property,
              suggestedValue: closestColor.value,
              reason: `Use brand color ${closestColor.name} instead`,
              autoFixable: true
            })
          }
        }
      }
    }
  }

  /**
   * Validate font properties
   */
  private async validateFonts(
    styles: Record<string, any>, 
    violations: BrandViolation[], 
    suggestions: BrandSuggestion[]
  ): Promise<void> {
    if (styles['font-family']) {
      const fontFamily = styles['font-family']
      const isCompliant = await this.isValueCompliant('font', fontFamily)
      
      if (!isCompliant) {
        violations.push({
          type: 'font',
          property: 'font-family',
          currentValue: fontFamily,
          message: `Font ${fontFamily} is not in brand guidelines`,
          severity: 'warning'
        })

        const fontGuidelines = await this.getGuidelinesByType('font')
        const primaryFont = fontGuidelines.find(f => f.category === 'primary')
        
        if (primaryFont) {
          suggestions.push({
            type: 'font',
            property: 'font-family',
            suggestedValue: primaryFont.value,
            reason: `Use brand font ${primaryFont.name}`,
            autoFixable: true
          })
        }
      }
    }
  }

  /**
   * Validate spacing properties
   */
  private async validateSpacing(
    styles: Record<string, any>, 
    violations: BrandViolation[], 
    suggestions: BrandSuggestion[]
  ): Promise<void> {
    const spacingProperties = ['padding', 'margin', 'gap', 'padding-top', 'padding-right', 'padding-bottom', 'padding-left', 'margin-top', 'margin-right', 'margin-bottom', 'margin-left']
    const spacingGuidelines = await this.getGuidelinesByType('spacing')

    for (const property of spacingProperties) {
      if (styles[property]) {
        const value = styles[property]
        const isCompliant = await this.isValueCompliant('spacing', value)
        
        if (!isCompliant && !this.isPixelValue(value)) {
          violations.push({
            type: 'spacing',
            property,
            currentValue: value,
            message: `Spacing ${value} should use design system scale`,
            severity: 'info'
          })

          // Find closest spacing value
          const closestSpacing = this.findClosestSpacing(value, spacingGuidelines)
          if (closestSpacing) {
            suggestions.push({
              type: 'spacing',
              property,
              suggestedValue: closestSpacing.value,
              reason: `Use design system spacing ${closestSpacing.name}`,
              autoFixable: true
            })
          }
        }
      }
    }
  }

  /**
   * Validate shadow properties
   */
  private async validateShadows(
    styles: Record<string, any>, 
    violations: BrandViolation[], 
    suggestions: BrandSuggestion[]
  ): Promise<void> {
    if (styles['box-shadow'] && styles['box-shadow'] !== 'none') {
      const shadowValue = styles['box-shadow']
      const isCompliant = await this.isValueCompliant('shadow', shadowValue)
      
      if (!isCompliant) {
        violations.push({
          type: 'shadow',
          property: 'box-shadow',
          currentValue: shadowValue,
          message: 'Custom shadow should use design system shadows',
          severity: 'info'
        })

        const shadowGuidelines = await this.getGuidelinesByType('shadow')
        const defaultShadow = shadowGuidelines.find(s => s.category === 'elevation')
        
        if (defaultShadow) {
          suggestions.push({
            type: 'shadow',
            property: 'box-shadow',
            suggestedValue: defaultShadow.value,
            reason: `Use design system shadow ${defaultShadow.name}`,
            autoFixable: true
          })
        }
      }
    }
  }

  /**
   * Validate border radius properties
   */
  private async validateBorderRadius(
    styles: Record<string, any>, 
    violations: BrandViolation[], 
    suggestions: BrandSuggestion[]
  ): Promise<void> {
    if (styles['border-radius']) {
      const radiusValue = styles['border-radius']
      const isCompliant = await this.isValueCompliant('border', radiusValue)
      
      if (!isCompliant && radiusValue !== '0' && radiusValue !== '9999px') {
        violations.push({
          type: 'border',
          property: 'border-radius',
          currentValue: radiusValue,
          message: 'Custom border radius should use design system values',
          severity: 'info'
        })

        const borderGuidelines = await this.getGuidelinesByType('border')
        const defaultRadius = borderGuidelines.find(b => b.category === 'border-radius')
        
        if (defaultRadius) {
          suggestions.push({
            type: 'border',
            property: 'border-radius',
            suggestedValue: defaultRadius.value,
            reason: `Use design system radius ${defaultRadius.name}`,
            autoFixable: true
          })
        }
      }
    }
  }

  /**
   * Find the closest brand color to a given color
   */
  private findClosestBrandColor(targetColor: string, colorGuidelines: BrandGuideline[]): BrandGuideline | null {
    // Simple implementation - in a real app, you might use color distance algorithms
    const primaryColors = colorGuidelines.filter(c => c.category === 'primary')
    return primaryColors.length > 0 ? primaryColors[0] : null
  }

  /**
   * Find the closest spacing value
   */
  private findClosestSpacing(targetSpacing: string, spacingGuidelines: BrandGuideline[]): BrandGuideline | null {
    // Convert to pixels for comparison
    const targetPx = this.convertToPixels(targetSpacing)
    if (targetPx === null) return null

    let closest = spacingGuidelines[0]
    let closestDistance = Math.abs(this.convertToPixels(closest.value) || 0 - targetPx)

    for (const spacing of spacingGuidelines) {
      const spacingPx = this.convertToPixels(spacing.value)
      if (spacingPx !== null) {
        const distance = Math.abs(spacingPx - targetPx)
        if (distance < closestDistance) {
          closest = spacing
          closestDistance = distance
        }
      }
    }

    return closest
  }

  /**
   * Convert CSS value to pixels
   */
  private convertToPixels(value: string): number | null {
    if (value.endsWith('px')) {
      return parseFloat(value)
    } else if (value.endsWith('rem')) {
      return parseFloat(value) * 16 // Assuming 1rem = 16px
    } else if (value.endsWith('em')) {
      return parseFloat(value) * 16 // Simplified assumption
    }
    return null
  }

  /**
   * Check if value is in pixels
   */
  private isPixelValue(value: string): boolean {
    return value.endsWith('px')
  }

  /**
   * Auto-fix compliance issues
   */
  public async autoFixStyles(styles: Record<string, any>): Promise<Record<string, any>> {
    const compliance = await this.validateComponentStyles(styles)
    const fixedStyles = { ...styles }

    for (const suggestion of compliance.suggestions) {
      if (suggestion.autoFixable) {
        fixedStyles[suggestion.property] = suggestion.suggestedValue
      }
    }

    return fixedStyles
  }

  /**
   * Get brand color palette
   */
  public async getBrandColorPalette(): Promise<BrandGuideline[]> {
    return await this.getGuidelinesByType('color')
  }

  /**
   * Get brand font stack
   */
  public async getBrandFontStack(): Promise<BrandGuideline[]> {
    return await this.getGuidelinesByType('font')
  }

  /**
   * Get spacing scale
   */
  public async getSpacingScale(): Promise<BrandGuideline[]> {
    return await this.getGuidelinesByType('spacing')
  }
}