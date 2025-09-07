// Form Template Customization System
import type { FormTemplate, FormField, FormComponentConfig } from '@/types/components'

export interface TemplateCustomization {
  id: string
  templateId: string
  name: string
  description?: string
  modifications: {
    addedFields?: FormField[]
    removedFieldIds?: string[]
    modifiedFields?: Partial<FormField>[]
    configChanges?: Partial<FormComponentConfig>
  }
  createdAt: string
  updatedAt: string
}

export interface FieldCustomizationOptions {
  allowAdd: boolean
  allowRemove: boolean
  allowModify: boolean
  allowReorder: boolean
  requiredFields?: string[] // Field IDs that cannot be removed
  maxFields?: number
  allowedFieldTypes?: string[]
}

export class TemplateCustomizer {
  private template: FormTemplate
  private customizations: TemplateCustomization[]

  constructor(template: FormTemplate) {
    this.template = { ...template }
    this.customizations = []
  }

  /**
   * Add a new field to the template
   */
  addField(field: FormField, position?: number): FormTemplate {
    const newTemplate = { ...this.template }
    const fields = [...newTemplate.config.fields]
    
    if (position !== undefined && position >= 0 && position <= fields.length) {
      fields.splice(position, 0, field)
    } else {
      fields.push(field)
    }
    
    newTemplate.config.fields = fields
    return newTemplate
  }

  /**
   * Remove a field from the template
   */
  removeField(fieldId: string, options?: FieldCustomizationOptions): FormTemplate {
    if (options?.requiredFields?.includes(fieldId)) {
      throw new Error(`Field ${fieldId} is required and cannot be removed`)
    }

    const newTemplate = { ...this.template }
    newTemplate.config.fields = newTemplate.config.fields.filter(field => field.id !== fieldId)
    return newTemplate
  }

  /**
   * Modify an existing field
   */
  modifyField(fieldId: string, modifications: Partial<FormField>): FormTemplate {
    const newTemplate = { ...this.template }
    const fieldIndex = newTemplate.config.fields.findIndex(field => field.id === fieldId)
    
    if (fieldIndex === -1) {
      throw new Error(`Field ${fieldId} not found`)
    }

    newTemplate.config.fields[fieldIndex] = {
      ...newTemplate.config.fields[fieldIndex],
      ...modifications
    }
    
    return newTemplate
  }

  /**
   * Reorder fields in the template
   */
  reorderFields(fieldOrder: string[]): FormTemplate {
    const newTemplate = { ...this.template }
    const fieldsMap = new Map(newTemplate.config.fields.map(field => [field.id, field]))
    
    const reorderedFields = fieldOrder
      .map(fieldId => fieldsMap.get(fieldId))
      .filter(field => field !== undefined) as FormField[]
    
    // Add any fields not in the order array at the end
    const orderedFieldIds = new Set(fieldOrder)
    const remainingFields = newTemplate.config.fields.filter(field => !orderedFieldIds.has(field.id))
    
    newTemplate.config.fields = [...reorderedFields, ...remainingFields]
    return newTemplate
  }

  /**
   * Update template configuration
   */
  updateConfig(configChanges: Partial<FormComponentConfig>): FormTemplate {
    const newTemplate = { ...this.template }
    newTemplate.config = {
      ...newTemplate.config,
      ...configChanges
    }
    return newTemplate
  }

  /**
   * Apply multiple customizations at once
   */
  applyCustomizations(customization: TemplateCustomization): FormTemplate {
    let customizedTemplate = { ...this.template }
    const { modifications } = customization

    // Remove fields first
    if (modifications.removedFieldIds) {
      modifications.removedFieldIds.forEach(fieldId => {
        customizedTemplate.config.fields = customizedTemplate.config.fields.filter(
          field => field.id !== fieldId
        )
      })
    }

    // Modify existing fields
    if (modifications.modifiedFields) {
      modifications.modifiedFields.forEach(fieldMod => {
        if (fieldMod.id) {
          const fieldIndex = customizedTemplate.config.fields.findIndex(
            field => field.id === fieldMod.id
          )
          if (fieldIndex !== -1) {
            customizedTemplate.config.fields[fieldIndex] = {
              ...customizedTemplate.config.fields[fieldIndex],
              ...fieldMod
            }
          }
        }
      })
    }

    // Add new fields
    if (modifications.addedFields) {
      customizedTemplate.config.fields.push(...modifications.addedFields)
    }

    // Apply config changes
    if (modifications.configChanges) {
      customizedTemplate.config = {
        ...customizedTemplate.config,
        ...modifications.configChanges
      }
    }

    return customizedTemplate
  }

  /**
   * Validate template customization
   */
  validateCustomization(
    customization: TemplateCustomization,
    options?: FieldCustomizationOptions
  ): { valid: boolean; errors: string[] } {
    const errors: string[] = []

    // Check field limits
    if (options?.maxFields) {
      const totalFields = this.template.config.fields.length +
        (customization.modifications.addedFields?.length || 0) -
        (customization.modifications.removedFieldIds?.length || 0)
      
      if (totalFields > options.maxFields) {
        errors.push(`Template cannot have more than ${options.maxFields} fields`)
      }
    }

    // Check required fields
    if (options?.requiredFields && customization.modifications.removedFieldIds) {
      const removedRequired = customization.modifications.removedFieldIds.filter(
        fieldId => options.requiredFields!.includes(fieldId)
      )
      if (removedRequired.length > 0) {
        errors.push(`Cannot remove required fields: ${removedRequired.join(', ')}`)
      }
    }

    // Check allowed field types
    if (options?.allowedFieldTypes && customization.modifications.addedFields) {
      const invalidTypes = customization.modifications.addedFields.filter(
        field => !options.allowedFieldTypes!.includes(field.type)
      )
      if (invalidTypes.length > 0) {
        errors.push(`Invalid field types: ${invalidTypes.map(f => f.type).join(', ')}`)
      }
    }

    // Validate field IDs are unique
    const allFieldIds = new Set<string>()
    const existingFields = this.template.config.fields.filter(
      field => !customization.modifications.removedFieldIds?.includes(field.id)
    )
    
    existingFields.forEach(field => allFieldIds.add(field.id))
    
    if (customization.modifications.addedFields) {
      customization.modifications.addedFields.forEach(field => {
        if (allFieldIds.has(field.id)) {
          errors.push(`Duplicate field ID: ${field.id}`)
        }
        allFieldIds.add(field.id)
      })
    }

    return {
      valid: errors.length === 0,
      errors
    }
  }

  /**
   * Create a preset customization for common use cases
   */
  static createPreset(presetType: string, templateId: string): TemplateCustomization {
    const baseCustomization: TemplateCustomization = {
      id: `${templateId}-${presetType}-${Date.now()}`,
      templateId,
      name: '',
      modifications: {},
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString()
    }

    switch (presetType) {
      case 'minimal':
        return {
          ...baseCustomization,
          name: 'Minimal Form',
          description: 'Simplified form with only essential fields',
          modifications: {
            removedFieldIds: ['phone', 'location', 'interests', 'newsletter'],
            configChanges: {
              layout: 'single-column',
              spacing: 'compact'
            }
          }
        }

      case 'detailed':
        return {
          ...baseCustomization,
          name: 'Detailed Form',
          description: 'Comprehensive form with additional information fields',
          modifications: {
            addedFields: [
              {
                id: 'linkedin-profile',
                type: 'url',
                name: 'linkedin_profile',
                label: 'LinkedIn Profile',
                placeholder: 'https://linkedin.com/in/yourprofile',
                required: false,
                width: 'full',
                validation: [
                  { rule: 'url', message: 'Please enter a valid LinkedIn URL' }
                ]
              },
              {
                id: 'bio',
                type: 'textarea',
                name: 'bio',
                label: 'Professional Bio',
                placeholder: 'Tell us about your professional background...',
                required: false,
                width: 'full',
                rows: 4
              }
            ]
          }
        }

      case 'gdpr-compliant':
        return {
          ...baseCustomization,
          name: 'GDPR Compliant',
          description: 'Form with enhanced privacy and consent options',
          modifications: {
            addedFields: [
              {
                id: 'data-processing-consent',
                type: 'checkbox',
                name: 'data_processing_consent',
                label: 'I consent to the processing of my personal data as described in the privacy policy',
                required: true,
                width: 'full',
                validation: [
                  { rule: 'required', message: 'Data processing consent is required' }
                ]
              },
              {
                id: 'marketing-consent',
                type: 'checkbox',
                name: 'marketing_consent',
                label: 'I consent to receiving marketing communications (optional)',
                required: false,
                width: 'full'
              }
            ],
            configChanges: {
              honeypot: true,
              recaptcha: { enabled: true, theme: 'light' }
            }
          }
        }

      default:
        return baseCustomization
    }
  }

  /**
   * Export template customization as JSON
   */
  exportCustomization(): string {
    return JSON.stringify(this.template, null, 2)
  }

  /**
   * Import template from JSON
   */
  static importCustomization(jsonData: string): FormTemplate {
    try {
      return JSON.parse(jsonData) as FormTemplate
    } catch (error) {
      throw new Error('Invalid template JSON format')
    }
  }
}

// Utility functions for template management
export const templateCustomizationUtils = {
  /**
   * Get default customization options for a template category
   */
  getDefaultOptions(category: string): FieldCustomizationOptions {
    const baseOptions: FieldCustomizationOptions = {
      allowAdd: true,
      allowRemove: true,
      allowModify: true,
      allowReorder: true,
      maxFields: 20
    }

    switch (category) {
      case 'lead-capture':
        return {
          ...baseOptions,
          requiredFields: ['email', 'privacy-consent'],
          allowedFieldTypes: ['text', 'email', 'phone', 'select', 'checkbox', 'textarea']
        }

      case 'demo-request':
        return {
          ...baseOptions,
          requiredFields: ['contact-name', 'email', 'institution-name'],
          maxFields: 25
        }

      case 'contact':
        return {
          ...baseOptions,
          requiredFields: ['name', 'email', 'message', 'follow-up-consent']
        }

      case 'newsletter':
        return {
          ...baseOptions,
          requiredFields: ['email'],
          maxFields: 10,
          allowedFieldTypes: ['text', 'email', 'select', 'checkbox']
        }

      default:
        return baseOptions
    }
  },

  /**
   * Generate field validation rules based on field type
   */
  generateValidationRules(fieldType: string, required: boolean = false) {
    const rules = []

    if (required) {
      rules.push({ rule: 'required', message: 'This field is required' })
    }

    switch (fieldType) {
      case 'email':
        rules.push({ rule: 'email', message: 'Please enter a valid email address' })
        break
      case 'phone':
        rules.push({ rule: 'phone', message: 'Please enter a valid phone number' })
        break
      case 'url':
        rules.push({ rule: 'url', message: 'Please enter a valid URL' })
        break
      case 'text':
        if (required) {
          rules.push({ rule: 'minLength', value: 2, message: 'Must be at least 2 characters' })
        }
        break
      case 'textarea':
        if (required) {
          rules.push({ rule: 'minLength', value: 10, message: 'Must be at least 10 characters' })
        }
        break
    }

    return rules
  }
}