import type { HeroComponentConfig, CTAButton, StatisticCounter, BackgroundMedia } from '@/types/components'

export interface ValidationError {
  field: string
  message: string
  code: string
}

export interface ValidationResult {
  isValid: boolean
  errors: ValidationError[]
  warnings: ValidationError[]
}

export class HeroConfigValidator {
  private errors: ValidationError[] = []
  private warnings: ValidationError[] = []

  validate(config: Partial<HeroComponentConfig>): ValidationResult {
    this.errors = []
    this.warnings = []

    this.validateRequired(config)
    this.validateHeadline(config.headline)
    this.validateAudienceType(config.audienceType)
    this.validateCTAButtons(config.ctaButtons)
    this.validateStatistics(config.statistics)
    this.validateBackgroundMedia(config.backgroundMedia)
    this.validateLayout(config.layout)
    this.validateTextAlignment(config.textAlignment)
    this.validateContentPosition(config.contentPosition)
    this.validateHeadingLevel(config.headingLevel)
    this.validateAnimations(config.animations)

    return {
      isValid: this.errors.length === 0,
      errors: this.errors,
      warnings: this.warnings
    }
  }

  private validateRequired(config: Partial<HeroComponentConfig>): void {
    if (!config.headline || config.headline.trim() === '') {
      this.addError('headline', 'Headline is required', 'REQUIRED_FIELD')
    }

    if (!config.audienceType) {
      this.addError('audienceType', 'Audience type is required', 'REQUIRED_FIELD')
    }

    if (!config.ctaButtons || config.ctaButtons.length === 0) {
      this.addWarning('ctaButtons', 'At least one CTA button is recommended', 'MISSING_CTA')
    }
  }

  private validateHeadline(headline?: string): void {
    if (!headline) return

    if (headline.length > 100) {
      this.addWarning('headline', 'Headline is quite long. Consider shortening for better readability', 'LONG_HEADLINE')
    }

    if (headline.length < 10) {
      this.addWarning('headline', 'Headline is very short. Consider adding more descriptive text', 'SHORT_HEADLINE')
    }

    // Check for HTML tags (basic validation)
    const htmlTagRegex = /<[^>]*>/g
    if (htmlTagRegex.test(headline)) {
      this.addWarning('headline', 'HTML tags detected in headline. Ensure they are safe and accessible', 'HTML_CONTENT')
    }
  }

  private validateAudienceType(audienceType?: string): void {
    if (!audienceType) return

    const validAudienceTypes = ['individual', 'institution', 'employer']
    if (!validAudienceTypes.includes(audienceType)) {
      this.addError('audienceType', `Invalid audience type. Must be one of: ${validAudienceTypes.join(', ')}`, 'INVALID_AUDIENCE_TYPE')
    }
  }

  private validateCTAButtons(ctaButtons?: CTAButton[]): void {
    if (!ctaButtons) return

    if (ctaButtons.length > 3) {
      this.addWarning('ctaButtons', 'More than 3 CTA buttons may reduce conversion rates', 'TOO_MANY_CTAS')
    }

    ctaButtons.forEach((cta, index) => {
      this.validateCTAButton(cta, index)
    })
  }

  private validateCTAButton(cta: CTAButton, index: number): void {
    const prefix = `ctaButtons[${index}]`

    if (!cta.text || cta.text.trim() === '') {
      this.addError(`${prefix}.text`, 'CTA button text is required', 'REQUIRED_FIELD')
    }

    if (!cta.url || cta.url.trim() === '') {
      this.addError(`${prefix}.url`, 'CTA button URL is required', 'REQUIRED_FIELD')
    }

    if (cta.text && cta.text.length > 25) {
      this.addWarning(`${prefix}.text`, 'CTA button text is quite long. Consider shortening', 'LONG_CTA_TEXT')
    }

    const validStyles = ['primary', 'secondary', 'outline', 'ghost']
    if (cta.style && !validStyles.includes(cta.style)) {
      this.addError(`${prefix}.style`, `Invalid CTA style. Must be one of: ${validStyles.join(', ')}`, 'INVALID_CTA_STYLE')
    }

    const validSizes = ['sm', 'md', 'lg']
    if (cta.size && !validSizes.includes(cta.size)) {
      this.addError(`${prefix}.size`, `Invalid CTA size. Must be one of: ${validSizes.join(', ')}`, 'INVALID_CTA_SIZE')
    }

    // URL validation
    if (cta.url) {
      try {
        new URL(cta.url)
      } catch {
        // Check if it's a relative URL
        if (!cta.url.startsWith('/') && !cta.url.startsWith('#')) {
          this.addWarning(`${prefix}.url`, 'URL format may be invalid', 'INVALID_URL_FORMAT')
        }
      }
    }
  }

  private validateStatistics(statistics?: StatisticCounter[]): void {
    if (!statistics) return

    if (statistics.length > 6) {
      this.addWarning('statistics', 'More than 6 statistics may overwhelm users', 'TOO_MANY_STATS')
    }

    statistics.forEach((stat, index) => {
      this.validateStatistic(stat, index)
    })
  }

  private validateStatistic(stat: StatisticCounter, index: number): void {
    const prefix = `statistics[${index}]`

    if (!stat.label || stat.label.trim() === '') {
      this.addError(`${prefix}.label`, 'Statistic label is required', 'REQUIRED_FIELD')
    }

    if (stat.value === undefined || stat.value === null) {
      this.addError(`${prefix}.value`, 'Statistic value is required', 'REQUIRED_FIELD')
    }

    if (typeof stat.value === 'number' && stat.value < 0) {
      this.addWarning(`${prefix}.value`, 'Negative statistic values may confuse users', 'NEGATIVE_STAT')
    }

    const validSources = ['manual', 'api']
    if (stat.source && !validSources.includes(stat.source)) {
      this.addError(`${prefix}.source`, `Invalid source. Must be one of: ${validSources.join(', ')}`, 'INVALID_SOURCE')
    }

    if (stat.source === 'api' && !stat.apiEndpoint) {
      this.addWarning(`${prefix}.apiEndpoint`, 'API endpoint should be specified when source is "api"', 'MISSING_API_ENDPOINT')
    }
  }

  private validateBackgroundMedia(backgroundMedia?: BackgroundMedia): void {
    if (!backgroundMedia) return

    const validTypes = ['image', 'video', 'gradient']
    if (!validTypes.includes(backgroundMedia.type)) {
      this.addError('backgroundMedia.type', `Invalid background media type. Must be one of: ${validTypes.join(', ')}`, 'INVALID_MEDIA_TYPE')
    }

    if (backgroundMedia.type === 'image' && !backgroundMedia.image) {
      this.addError('backgroundMedia.image', 'Image configuration is required when type is "image"', 'MISSING_IMAGE_CONFIG')
    }

    if (backgroundMedia.type === 'video' && !backgroundMedia.video) {
      this.addError('backgroundMedia.video', 'Video configuration is required when type is "video"', 'MISSING_VIDEO_CONFIG')
    }

    if (backgroundMedia.type === 'gradient' && !backgroundMedia.gradient) {
      this.addError('backgroundMedia.gradient', 'Gradient configuration is required when type is "gradient"', 'MISSING_GRADIENT_CONFIG')
    }

    // Validate overlay
    if (backgroundMedia.overlay) {
      if (backgroundMedia.overlay.opacity < 0 || backgroundMedia.overlay.opacity > 1) {
        this.addError('backgroundMedia.overlay.opacity', 'Overlay opacity must be between 0 and 1', 'INVALID_OPACITY')
      }
    }

    // Validate gradient
    if (backgroundMedia.gradient) {
      if (!backgroundMedia.gradient.colors || backgroundMedia.gradient.colors.length < 2) {
        this.addError('backgroundMedia.gradient.colors', 'Gradient must have at least 2 colors', 'INSUFFICIENT_GRADIENT_COLORS')
      }

      backgroundMedia.gradient.colors?.forEach((color, index) => {
        if (color.stop < 0 || color.stop > 100) {
          this.addError(`backgroundMedia.gradient.colors[${index}].stop`, 'Color stop must be between 0 and 100', 'INVALID_COLOR_STOP')
        }
      })
    }
  }

  private validateLayout(layout?: string): void {
    if (!layout) return

    const validLayouts = ['centered', 'left-aligned', 'right-aligned', 'split']
    if (!validLayouts.includes(layout)) {
      this.addError('layout', `Invalid layout. Must be one of: ${validLayouts.join(', ')}`, 'INVALID_LAYOUT')
    }
  }

  private validateTextAlignment(textAlignment?: string): void {
    if (!textAlignment) return

    const validAlignments = ['left', 'center', 'right']
    if (!validAlignments.includes(textAlignment)) {
      this.addError('textAlignment', `Invalid text alignment. Must be one of: ${validAlignments.join(', ')}`, 'INVALID_TEXT_ALIGNMENT')
    }
  }

  private validateContentPosition(contentPosition?: string): void {
    if (!contentPosition) return

    const validPositions = ['top', 'center', 'bottom']
    if (!validPositions.includes(contentPosition)) {
      this.addError('contentPosition', `Invalid content position. Must be one of: ${validPositions.join(', ')}`, 'INVALID_CONTENT_POSITION')
    }
  }

  private validateHeadingLevel(headingLevel?: number): void {
    if (!headingLevel) return

    if (headingLevel < 1 || headingLevel > 6) {
      this.addError('headingLevel', 'Heading level must be between 1 and 6', 'INVALID_HEADING_LEVEL')
    }
  }

  private validateAnimations(animations?: HeroComponentConfig['animations']): void {
    if (!animations) return

    if (animations.duration && (animations.duration < 100 || animations.duration > 5000)) {
      this.addWarning('animations.duration', 'Animation duration should be between 100ms and 5000ms for good UX', 'INVALID_ANIMATION_DURATION')
    }

    if (animations.delay && animations.delay > 2000) {
      this.addWarning('animations.delay', 'Animation delay over 2000ms may feel sluggish', 'LONG_ANIMATION_DELAY')
    }

    const validEntrances = ['fade', 'slide', 'zoom']
    if (animations.entrance && !validEntrances.includes(animations.entrance)) {
      this.addError('animations.entrance', `Invalid entrance animation. Must be one of: ${validEntrances.join(', ')}`, 'INVALID_ENTRANCE_ANIMATION')
    }
  }

  private addError(field: string, message: string, code: string): void {
    this.errors.push({ field, message, code })
  }

  private addWarning(field: string, message: string, code: string): void {
    this.warnings.push({ field, message, code })
  }
}

export const validateHeroConfig = (config: Partial<HeroComponentConfig>): ValidationResult => {
  const validator = new HeroConfigValidator()
  return validator.validate(config)
}

export const isValidHeroConfig = (config: Partial<HeroComponentConfig>): boolean => {
  return validateHeroConfig(config).isValid
}