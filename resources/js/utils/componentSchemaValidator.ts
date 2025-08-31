/**
 * Component Configuration Schema Validator for GrapeJS Integration
 * 
 * This module provides comprehensive validation for component configurations
 * to ensure compatibility with GrapeJS and maintain data integrity.
 */

import type {
  Component,
  ComponentCategory,
  HeroComponentConfig,
  FormComponentConfig,
  TestimonialComponentConfig,
  StatisticsComponentConfig,
  CTAComponentConfig,
  MediaComponentConfig,
  FormField,
  MediaAsset,
  CTAButton,
  StatisticCounter,
  Testimonial
} from '@/types/components'

export interface ValidationResult {
  valid: boolean
  errors: ValidationError[]
  warnings: ValidationWarning[]
  score: number // 0-100, overall validation score
}

export interface ValidationError {
  field: string
  message: string
  code: string
  severity: 'error' | 'warning'
}

export interface ValidationWarning {
  field: string
  message: string
  code: string
  suggestion?: string
}

export interface ValidationOptions {
  strict?: boolean
  validateAccessibility?: boolean
  validatePerformance?: boolean
  validateSEO?: boolean
  validateGrapeJSCompatibility?: boolean
  customRules?: ValidationRule[]
}

export interface ValidationRule {
  name: string
  category?: ComponentCategory
  validator: (config: any, component: Component) => ValidationError[]
}

export class ComponentSchemaValidator {
  private options: ValidationOptions
  private customRules: Map<string, ValidationRule> = new Map()

  constructor(options: ValidationOptions = {}) {
    this.options = {
      strict: false,
      validateAccessibility: true,
      validatePerformance: true,
      validateSEO: true,
      validateGrapeJSCompatibility: true,
      ...options
    }

    // Register custom rules if provided
    if (this.options.customRules) {
      this.options.customRules.forEach(rule => {
        this.customRules.set(rule.name, rule)
      })
    }
  }

  /**
   * Validate a complete component
   */
  validateComponent(component: Component): ValidationResult {
    const errors: ValidationError[] = []
    const warnings: ValidationWarning[] = []

    // Validate basic component structure
    errors.push(...this.validateBasicStructure(component))

    // Validate category-specific configuration
    errors.push(...this.validateCategoryConfig(component))

    // Validate accessibility if enabled
    if (this.options.validateAccessibility) {
      errors.push(...this.validateAccessibility(component))
    }

    // Validate performance if enabled
    if (this.options.validatePerformance) {
      errors.push(...this.validatePerformance(component))
    }

    // Validate SEO if enabled
    if (this.options.validateSEO) {
      errors.push(...this.validateSEO(component))
    }

    // Validate GrapeJS compatibility if enabled
    if (this.options.validateGrapeJSCompatibility) {
      errors.push(...this.validateGrapeJSCompatibility(component))
    }

    // Apply custom rules
    this.customRules.forEach(rule => {
      if (!rule.category || rule.category === component.category) {
        errors.push(...rule.validator(component.config, component))
      }
    })

    // Separate errors and warnings
    const actualErrors = errors.filter(e => e.severity === 'error')
    const actualWarnings = errors.filter(e => e.severity === 'warning').map(e => ({
      field: e.field,
      message: e.message,
      code: e.code,
      suggestion: this.getSuggestion(e.code, e.field)
    }))

    // Calculate validation score
    const score = this.calculateValidationScore(actualErrors, actualWarnings)

    return {
      valid: actualErrors.length === 0,
      errors: actualErrors,
      warnings: actualWarnings,
      score
    }
  }

  /**
   * Validate component configuration schema
   */
  validateConfigSchema(config: any, category: ComponentCategory): ValidationResult {
    const errors: ValidationError[] = []
    const warnings: ValidationWarning[] = []

    switch (category) {
      case 'hero':
        errors.push(...this.validateHeroSchema(config as HeroComponentConfig))
        break
      case 'forms':
        errors.push(...this.validateFormSchema(config as FormComponentConfig))
        break
      case 'testimonials':
        errors.push(...this.validateTestimonialSchema(config as TestimonialComponentConfig))
        break
      case 'statistics':
        errors.push(...this.validateStatisticsSchema(config as StatisticsComponentConfig))
        break
      case 'ctas':
        errors.push(...this.validateCTASchema(config as CTAComponentConfig))
        break
      case 'media':
        errors.push(...this.validateMediaSchema(config as MediaComponentConfig))
        break
    }

    const actualErrors = errors.filter(e => e.severity === 'error')
    const actualWarnings = errors.filter(e => e.severity === 'warning').map(e => ({
      field: e.field,
      message: e.message,
      code: e.code,
      suggestion: this.getSuggestion(e.code, e.field)
    }))

    return {
      valid: actualErrors.length === 0,
      errors: actualErrors,
      warnings: actualWarnings,
      score: this.calculateValidationScore(actualErrors, actualWarnings)
    }
  }

  // Private validation methods

  private validateBasicStructure(component: Component): ValidationError[] {
    const errors: ValidationError[] = []

    // Required fields
    if (!component.id || typeof component.id !== 'string') {
      errors.push({
        field: 'id',
        message: 'Component ID is required and must be a string',
        code: 'MISSING_ID',
        severity: 'error'
      })
    }

    if (!component.name || typeof component.name !== 'string') {
      errors.push({
        field: 'name',
        message: 'Component name is required and must be a string',
        code: 'MISSING_NAME',
        severity: 'error'
      })
    }

    if (!component.category) {
      errors.push({
        field: 'category',
        message: 'Component category is required',
        code: 'MISSING_CATEGORY',
        severity: 'error'
      })
    }

    if (!component.config) {
      errors.push({
        field: 'config',
        message: 'Component configuration is required',
        code: 'MISSING_CONFIG',
        severity: 'error'
      })
    }

    // Validate ID format
    if (component.id && !/^[a-zA-Z0-9-_]+$/.test(component.id)) {
      errors.push({
        field: 'id',
        message: 'Component ID must contain only alphanumeric characters, hyphens, and underscores',
        code: 'INVALID_ID_FORMAT',
        severity: 'error'
      })
    }

    // Validate name length
    if (component.name && component.name.length > 100) {
      errors.push({
        field: 'name',
        message: 'Component name must be 100 characters or less',
        code: 'NAME_TOO_LONG',
        severity: 'warning'
      })
    }

    return errors
  }

  private validateCategoryConfig(component: Component): ValidationError[] {
    switch (component.category) {
      case 'hero':
        return this.validateHeroConfig(component.config as HeroComponentConfig)
      case 'forms':
        return this.validateFormConfig(component.config as FormComponentConfig)
      case 'testimonials':
        return this.validateTestimonialConfig(component.config as TestimonialComponentConfig)
      case 'statistics':
        return this.validateStatisticsConfig(component.config as StatisticsComponentConfig)
      case 'ctas':
        return this.validateCTAConfig(component.config as CTAComponentConfig)
      case 'media':
        return this.validateMediaConfig(component.config as MediaComponentConfig)
      default:
        return [{
          field: 'category',
          message: `Unknown component category: ${component.category}`,
          code: 'UNKNOWN_CATEGORY',
          severity: 'error'
        }]
    }
  }

  private validateHeroConfig(config: HeroComponentConfig): ValidationError[] {
    const errors: ValidationError[] = []

    // Required fields
    if (!config.headline || typeof config.headline !== 'string') {
      errors.push({
        field: 'headline',
        message: 'Hero headline is required and must be a string',
        code: 'MISSING_HEADLINE',
        severity: 'error'
      })
    }

    if (!config.audienceType) {
      errors.push({
        field: 'audienceType',
        message: 'Hero audience type is required',
        code: 'MISSING_AUDIENCE_TYPE',
        severity: 'error'
      })
    }

    if (!config.ctaButtons || !Array.isArray(config.ctaButtons) || config.ctaButtons.length === 0) {
      errors.push({
        field: 'ctaButtons',
        message: 'Hero must have at least one CTA button',
        code: 'MISSING_CTA_BUTTONS',
        severity: 'error'
      })
    }

    // Validate headline length
    if (config.headline && config.headline.length > 200) {
      errors.push({
        field: 'headline',
        message: 'Hero headline should be 200 characters or less for better readability',
        code: 'HEADLINE_TOO_LONG',
        severity: 'warning'
      })
    }

    // Validate audience type
    if (config.audienceType && !['individual', 'institution', 'employer'].includes(config.audienceType)) {
      errors.push({
        field: 'audienceType',
        message: 'Invalid audience type. Must be individual, institution, or employer',
        code: 'INVALID_AUDIENCE_TYPE',
        severity: 'error'
      })
    }

    // Validate CTA buttons
    if (config.ctaButtons) {
      config.ctaButtons.forEach((cta, index) => {
        errors.push(...this.validateCTAButton(cta, `ctaButtons[${index}]`))
      })
    }

    // Validate statistics if present
    if (config.statistics) {
      config.statistics.forEach((stat, index) => {
        errors.push(...this.validateStatistic(stat, `statistics[${index}]`))
      })
    }

    return errors
  }

  private validateFormConfig(config: FormComponentConfig): ValidationError[] {
    const errors: ValidationError[] = []

    // Required fields
    if (!config.fields || !Array.isArray(config.fields) || config.fields.length === 0) {
      errors.push({
        field: 'fields',
        message: 'Form must have at least one field',
        code: 'MISSING_FORM_FIELDS',
        severity: 'error'
      })
    }

    if (!config.submission || !config.submission.action) {
      errors.push({
        field: 'submission.action',
        message: 'Form submission action URL is required',
        code: 'MISSING_SUBMISSION_ACTION',
        severity: 'error'
      })
    }

    // Validate fields
    if (config.fields) {
      config.fields.forEach((field, index) => {
        errors.push(...this.validateFormField(field, `fields[${index}]`))
      })

      // Check for duplicate field names
      const fieldNames = config.fields.map(f => f.name)
      const duplicates = fieldNames.filter((name, index) => fieldNames.indexOf(name) !== index)
      if (duplicates.length > 0) {
        errors.push({
          field: 'fields',
          message: `Duplicate field names found: ${duplicates.join(', ')}`,
          code: 'DUPLICATE_FIELD_NAMES',
          severity: 'error'
        })
      }
    }

    // Validate submission URL
    if (config.submission?.action && !this.isValidURL(config.submission.action)) {
      errors.push({
        field: 'submission.action',
        message: 'Submission action must be a valid URL',
        code: 'INVALID_SUBMISSION_URL',
        severity: 'error'
      })
    }

    return errors
  }

  private validateTestimonialConfig(config: TestimonialComponentConfig): ValidationError[] {
    const errors: ValidationError[] = []

    // Required fields
    if (!config.testimonials || !Array.isArray(config.testimonials) || config.testimonials.length === 0) {
      errors.push({
        field: 'testimonials',
        message: 'Testimonial component must have at least one testimonial',
        code: 'MISSING_TESTIMONIALS',
        severity: 'error'
      })
    }

    // Validate layout
    if (config.layout && !['single', 'carousel', 'grid', 'masonry'].includes(config.layout)) {
      errors.push({
        field: 'layout',
        message: 'Invalid testimonial layout. Must be single, carousel, grid, or masonry',
        code: 'INVALID_TESTIMONIAL_LAYOUT',
        severity: 'error'
      })
    }

    // Validate testimonials
    if (config.testimonials) {
      config.testimonials.forEach((testimonial, index) => {
        errors.push(...this.validateTestimonial(testimonial, `testimonials[${index}]`))
      })
    }

    return errors
  }

  private validateStatisticsConfig(config: StatisticsComponentConfig): ValidationError[] {
    const errors: ValidationError[] = []

    // Required fields
    if (!config.displayType) {
      errors.push({
        field: 'displayType',
        message: 'Statistics display type is required',
        code: 'MISSING_DISPLAY_TYPE',
        severity: 'error'
      })
    }

    // Validate display type
    if (config.displayType && !['counters', 'progress', 'charts', 'mixed'].includes(config.displayType)) {
      errors.push({
        field: 'displayType',
        message: 'Invalid display type. Must be counters, progress, charts, or mixed',
        code: 'INVALID_DISPLAY_TYPE',
        severity: 'error'
      })
    }

    // Validate layout
    if (config.layout && !['grid', 'row', 'column'].includes(config.layout)) {
      errors.push({
        field: 'layout',
        message: 'Invalid layout. Must be grid, row, or column',
        code: 'INVALID_STATISTICS_LAYOUT',
        severity: 'error'
      })
    }

    return errors
  }

  private validateCTAConfig(config: CTAComponentConfig): ValidationError[] {
    const errors: ValidationError[] = []

    // Required fields
    if (!config.type) {
      errors.push({
        field: 'type',
        message: 'CTA type is required',
        code: 'MISSING_CTA_TYPE',
        severity: 'error'
      })
    }

    // Validate type
    if (config.type && !['button', 'banner', 'inline-link'].includes(config.type)) {
      errors.push({
        field: 'type',
        message: 'Invalid CTA type. Must be button, banner, or inline-link',
        code: 'INVALID_CTA_TYPE',
        severity: 'error'
      })
    }

    // Validate type-specific configuration
    if (config.type === 'button' && config.buttonConfig) {
      errors.push(...this.validateCTAButton(config.buttonConfig, 'buttonConfig'))
    }

    if (config.type === 'banner' && config.bannerConfig) {
      if (!config.bannerConfig.primaryCTA) {
        errors.push({
          field: 'bannerConfig.primaryCTA',
          message: 'Banner CTA must have a primary CTA button',
          code: 'MISSING_BANNER_PRIMARY_CTA',
          severity: 'error'
        })
      }
    }

    if (config.type === 'inline-link' && config.inlineLinkConfig) {
      if (!config.inlineLinkConfig.text || !config.inlineLinkConfig.url) {
        errors.push({
          field: 'inlineLinkConfig',
          message: 'Inline link must have both text and URL',
          code: 'MISSING_INLINE_LINK_DATA',
          severity: 'error'
        })
      }
    }

    return errors
  }

  private validateMediaConfig(config: MediaComponentConfig): ValidationError[] {
    const errors: ValidationError[] = []

    // Required fields
    if (!config.type) {
      errors.push({
        field: 'type',
        message: 'Media type is required',
        code: 'MISSING_MEDIA_TYPE',
        severity: 'error'
      })
    }

    if (!config.mediaAssets || !Array.isArray(config.mediaAssets) || config.mediaAssets.length === 0) {
      errors.push({
        field: 'mediaAssets',
        message: 'Media component must have at least one media asset',
        code: 'MISSING_MEDIA_ASSETS',
        severity: 'error'
      })
    }

    // Validate type
    if (config.type && !['image-gallery', 'video-embed', 'interactive-demo'].includes(config.type)) {
      errors.push({
        field: 'type',
        message: 'Invalid media type. Must be image-gallery, video-embed, or interactive-demo',
        code: 'INVALID_MEDIA_TYPE',
        severity: 'error'
      })
    }

    // Validate media assets
    if (config.mediaAssets) {
      config.mediaAssets.forEach((asset, index) => {
        errors.push(...this.validateMediaAsset(asset, `mediaAssets[${index}]`))
      })
    }

    return errors
  }

  // Schema validation methods

  private validateHeroSchema(config: HeroComponentConfig): ValidationError[] {
    const errors: ValidationError[] = []

    // Type checking
    if (typeof config.headline !== 'string') {
      errors.push({
        field: 'headline',
        message: 'Headline must be a string',
        code: 'INVALID_HEADLINE_TYPE',
        severity: 'error'
      })
    }

    if (config.subheading && typeof config.subheading !== 'string') {
      errors.push({
        field: 'subheading',
        message: 'Subheading must be a string',
        code: 'INVALID_SUBHEADING_TYPE',
        severity: 'error'
      })
    }

    if (typeof config.headingLevel !== 'number' || config.headingLevel < 1 || config.headingLevel > 6) {
      errors.push({
        field: 'headingLevel',
        message: 'Heading level must be a number between 1 and 6',
        code: 'INVALID_HEADING_LEVEL',
        severity: 'error'
      })
    }

    return errors
  }

  private validateFormSchema(config: FormComponentConfig): ValidationError[] {
    const errors: ValidationError[] = []

    if (!Array.isArray(config.fields)) {
      errors.push({
        field: 'fields',
        message: 'Fields must be an array',
        code: 'INVALID_FIELDS_TYPE',
        severity: 'error'
      })
    }

    if (typeof config.submission !== 'object') {
      errors.push({
        field: 'submission',
        message: 'Submission must be an object',
        code: 'INVALID_SUBMISSION_TYPE',
        severity: 'error'
      })
    }

    return errors
  }

  private validateTestimonialSchema(config: TestimonialComponentConfig): ValidationError[] {
    const errors: ValidationError[] = []

    if (!Array.isArray(config.testimonials)) {
      errors.push({
        field: 'testimonials',
        message: 'Testimonials must be an array',
        code: 'INVALID_TESTIMONIALS_TYPE',
        severity: 'error'
      })
    }

    return errors
  }

  private validateStatisticsSchema(config: StatisticsComponentConfig): ValidationError[] {
    const errors: ValidationError[] = []

    if (typeof config.displayType !== 'string') {
      errors.push({
        field: 'displayType',
        message: 'Display type must be a string',
        code: 'INVALID_DISPLAY_TYPE_TYPE',
        severity: 'error'
      })
    }

    return errors
  }

  private validateCTASchema(config: CTAComponentConfig): ValidationError[] {
    const errors: ValidationError[] = []

    if (typeof config.type !== 'string') {
      errors.push({
        field: 'type',
        message: 'CTA type must be a string',
        code: 'INVALID_CTA_TYPE_TYPE',
        severity: 'error'
      })
    }

    return errors
  }

  private validateMediaSchema(config: MediaComponentConfig): ValidationError[] {
    const errors: ValidationError[] = []

    if (typeof config.type !== 'string') {
      errors.push({
        field: 'type',
        message: 'Media type must be a string',
        code: 'INVALID_MEDIA_TYPE_TYPE',
        severity: 'error'
      })
    }

    if (!Array.isArray(config.mediaAssets)) {
      errors.push({
        field: 'mediaAssets',
        message: 'Media assets must be an array',
        code: 'INVALID_MEDIA_ASSETS_TYPE',
        severity: 'error'
      })
    }

    return errors
  }

  // Helper validation methods

  private validateCTAButton(cta: CTAButton, fieldPrefix: string): ValidationError[] {
    const errors: ValidationError[] = []

    if (!cta.text || typeof cta.text !== 'string') {
      errors.push({
        field: `${fieldPrefix}.text`,
        message: 'CTA button text is required and must be a string',
        code: 'MISSING_CTA_TEXT',
        severity: 'error'
      })
    }

    if (!cta.url || typeof cta.url !== 'string') {
      errors.push({
        field: `${fieldPrefix}.url`,
        message: 'CTA button URL is required and must be a string',
        code: 'MISSING_CTA_URL',
        severity: 'error'
      })
    }

    if (cta.url && !this.isValidURL(cta.url)) {
      errors.push({
        field: `${fieldPrefix}.url`,
        message: 'CTA button URL must be a valid URL',
        code: 'INVALID_CTA_URL',
        severity: 'error'
      })
    }

    return errors
  }

  private validateStatistic(stat: StatisticCounter, fieldPrefix: string): ValidationError[] {
    const errors: ValidationError[] = []

    if (!stat.label || typeof stat.label !== 'string') {
      errors.push({
        field: `${fieldPrefix}.label`,
        message: 'Statistic label is required and must be a string',
        code: 'MISSING_STATISTIC_LABEL',
        severity: 'error'
      })
    }

    if (stat.value === undefined || stat.value === null) {
      errors.push({
        field: `${fieldPrefix}.value`,
        message: 'Statistic value is required',
        code: 'MISSING_STATISTIC_VALUE',
        severity: 'error'
      })
    }

    return errors
  }

  private validateFormField(field: FormField, fieldPrefix: string): ValidationError[] {
    const errors: ValidationError[] = []

    if (!field.name || typeof field.name !== 'string') {
      errors.push({
        field: `${fieldPrefix}.name`,
        message: 'Form field name is required and must be a string',
        code: 'MISSING_FIELD_NAME',
        severity: 'error'
      })
    }

    if (!field.label || typeof field.label !== 'string') {
      errors.push({
        field: `${fieldPrefix}.label`,
        message: 'Form field label is required and must be a string',
        code: 'MISSING_FIELD_LABEL',
        severity: 'error'
      })
    }

    if (!field.type || typeof field.type !== 'string') {
      errors.push({
        field: `${fieldPrefix}.type`,
        message: 'Form field type is required and must be a string',
        code: 'MISSING_FIELD_TYPE',
        severity: 'error'
      })
    }

    // Validate field name format
    if (field.name && !/^[a-zA-Z][a-zA-Z0-9_]*$/.test(field.name)) {
      errors.push({
        field: `${fieldPrefix}.name`,
        message: 'Field name must start with a letter and contain only letters, numbers, and underscores',
        code: 'INVALID_FIELD_NAME_FORMAT',
        severity: 'error'
      })
    }

    return errors
  }

  private validateTestimonial(testimonial: Testimonial, fieldPrefix: string): ValidationError[] {
    const errors: ValidationError[] = []

    if (!testimonial.content?.quote || typeof testimonial.content.quote !== 'string') {
      errors.push({
        field: `${fieldPrefix}.content.quote`,
        message: 'Testimonial quote is required and must be a string',
        code: 'MISSING_TESTIMONIAL_QUOTE',
        severity: 'error'
      })
    }

    if (!testimonial.author?.name || typeof testimonial.author.name !== 'string') {
      errors.push({
        field: `${fieldPrefix}.author.name`,
        message: 'Testimonial author name is required and must be a string',
        code: 'MISSING_TESTIMONIAL_AUTHOR',
        severity: 'error'
      })
    }

    return errors
  }

  private validateMediaAsset(asset: MediaAsset, fieldPrefix: string): ValidationError[] {
    const errors: ValidationError[] = []

    if (!asset.url || typeof asset.url !== 'string') {
      errors.push({
        field: `${fieldPrefix}.url`,
        message: 'Media asset URL is required and must be a string',
        code: 'MISSING_MEDIA_URL',
        severity: 'error'
      })
    }

    if (asset.url && !this.isValidURL(asset.url)) {
      errors.push({
        field: `${fieldPrefix}.url`,
        message: 'Media asset URL must be a valid URL',
        code: 'INVALID_MEDIA_URL',
        severity: 'error'
      })
    }

    if (!asset.type || !['image', 'video'].includes(asset.type)) {
      errors.push({
        field: `${fieldPrefix}.type`,
        message: 'Media asset type must be either image or video',
        code: 'INVALID_MEDIA_ASSET_TYPE',
        severity: 'error'
      })
    }

    return errors
  }

  // Validation helper methods

  private validateAccessibility(component: Component): ValidationError[] {
    const errors: ValidationError[] = []

    // Check for accessibility-related configuration
    if (component.category === 'hero') {
      const config = component.config as HeroComponentConfig
      if (!config.headingLevel || config.headingLevel < 1 || config.headingLevel > 6) {
        errors.push({
          field: 'headingLevel',
          message: 'Hero components should have a valid heading level (1-6) for accessibility',
          code: 'ACCESSIBILITY_HEADING_LEVEL',
          severity: 'warning'
        })
      }
    }

    if (component.category === 'media') {
      const config = component.config as MediaComponentConfig
      if (config.mediaAssets) {
        config.mediaAssets.forEach((asset, index) => {
          if (asset.type === 'image' && (!asset.alt || asset.alt.trim() === '')) {
            errors.push({
              field: `mediaAssets[${index}].alt`,
              message: 'Images should have alt text for accessibility',
              code: 'ACCESSIBILITY_MISSING_ALT_TEXT',
              severity: 'warning'
            })
          }
        })
      }
    }

    return errors
  }

  private validatePerformance(component: Component): ValidationError[] {
    const errors: ValidationError[] = []

    if (component.category === 'media') {
      const config = component.config as MediaComponentConfig
      
      if (config.mediaAssets && config.mediaAssets.length > 20) {
        errors.push({
          field: 'mediaAssets',
          message: 'Too many media assets may impact performance. Consider pagination or lazy loading.',
          code: 'PERFORMANCE_TOO_MANY_ASSETS',
          severity: 'warning'
        })
      }

      if (!config.optimization?.lazyLoading) {
        errors.push({
          field: 'optimization.lazyLoading',
          message: 'Lazy loading should be enabled for better performance',
          code: 'PERFORMANCE_LAZY_LOADING',
          severity: 'warning'
        })
      }
    }

    return errors
  }

  private validateSEO(component: Component): ValidationError[] {
    const errors: ValidationError[] = []

    if (component.category === 'hero') {
      const config = component.config as HeroComponentConfig
      
      if (config.headline && config.headline.length > 60) {
        errors.push({
          field: 'headline',
          message: 'Hero headlines longer than 60 characters may be truncated in search results',
          code: 'SEO_HEADLINE_LENGTH',
          severity: 'warning'
        })
      }
    }

    return errors
  }

  private validateGrapeJSCompatibility(component: Component): ValidationError[] {
    const errors: ValidationError[] = []

    // Check for GrapeJS-specific requirements
    if (!component.id || component.id.includes(' ')) {
      errors.push({
        field: 'id',
        message: 'Component ID should not contain spaces for GrapeJS compatibility',
        code: 'GRAPEJS_ID_SPACES',
        severity: 'warning'
      })
    }

    // Check for complex nested structures that might not work well in GrapeJS
    if (component.category === 'forms') {
      const config = component.config as FormComponentConfig
      if (config.fields && config.fields.length > 20) {
        errors.push({
          field: 'fields',
          message: 'Forms with more than 20 fields may be difficult to manage in GrapeJS',
          code: 'GRAPEJS_COMPLEX_FORM',
          severity: 'warning'
        })
      }
    }

    return errors
  }

  private isValidURL(url: string): boolean {
    try {
      new URL(url)
      return true
    } catch {
      // Check for relative URLs
      return url.startsWith('/') || url.startsWith('#')
    }
  }

  private calculateValidationScore(errors: ValidationError[], warnings: ValidationWarning[]): number {
    const errorPenalty = errors.length * 20
    const warningPenalty = warnings.length * 5
    const totalPenalty = errorPenalty + warningPenalty
    
    return Math.max(0, 100 - totalPenalty)
  }

  private getSuggestion(code: string, field: string): string | undefined {
    const suggestions: Record<string, string> = {
      'MISSING_HEADLINE': 'Add a compelling headline that clearly communicates your value proposition',
      'HEADLINE_TOO_LONG': 'Consider shortening the headline to improve readability and SEO',
      'MISSING_CTA_BUTTONS': 'Add at least one call-to-action button to guide user behavior',
      'MISSING_ALT_TEXT': 'Add descriptive alt text to improve accessibility for screen readers',
      'PERFORMANCE_LAZY_LOADING': 'Enable lazy loading to improve page load performance',
      'ACCESSIBILITY_HEADING_LEVEL': 'Use proper heading hierarchy (h1-h6) for better accessibility'
    }

    return suggestions[code]
  }
}

// Export singleton instance
export const componentSchemaValidator = new ComponentSchemaValidator()