// CRM Integration Service for Form Templates
import type { FormSubmissionConfig } from '@/types/components'

export interface CRMProvider {
  name: string
  endpoint: string
  apiKey?: string
  authToken?: string
  headers?: Record<string, string>
}

export interface LeadData {
  [key: string]: any
}

export interface CRMResponse {
  success: boolean
  leadId?: string
  contactId?: string
  ticketId?: string
  error?: string
  details?: any
}

export interface NotificationConfig {
  enabled: boolean
  recipients: string[]
  template: string
  subject: string
  priority?: 'low' | 'medium' | 'high' | 'urgent'
}

export class CRMIntegrationService {
  private providers: Map<string, CRMProvider> = new Map()

  constructor() {
    this.initializeProviders()
  }

  /**
   * Initialize CRM providers with configuration
   */
  private initializeProviders() {
    // HubSpot configuration
    this.providers.set('hubspot', {
      name: 'HubSpot',
      endpoint: '/api/crm/hubspot',
      headers: {
        'Content-Type': 'application/json'
      }
    })

    // Salesforce configuration
    this.providers.set('salesforce', {
      name: 'Salesforce',
      endpoint: '/api/crm/salesforce',
      headers: {
        'Content-Type': 'application/json'
      }
    })

    // Pipedrive configuration
    this.providers.set('pipedrive', {
      name: 'Pipedrive',
      endpoint: '/api/crm/pipedrive',
      headers: {
        'Content-Type': 'application/json'
      }
    })
  }

  /**
   * Process form submission and send to CRM
   */
  async processFormSubmission(
    formData: LeadData,
    submissionConfig: FormSubmissionConfig
  ): Promise<CRMResponse> {
    try {
      // Validate CRM integration is enabled
      if (!submissionConfig.crmIntegration?.enabled) {
        return { success: true } // Skip CRM integration
      }

      const { crmIntegration } = submissionConfig
      const provider = this.providers.get(crmIntegration.provider)

      if (!provider) {
        throw new Error(`CRM provider ${crmIntegration.provider} not configured`)
      }

      // Map form data to CRM fields
      const mappedData = this.mapFormDataToCRM(formData, crmIntegration.mapping || {})

      // Add lead scoring and tags
      const enrichedData = {
        ...mappedData,
        leadScore: crmIntegration.leadScore || 0,
        tags: crmIntegration.tags || [],
        source: 'form_submission',
        submittedAt: new Date().toISOString()
      }

      // Send to CRM
      const crmResponse = await this.sendToCRM(provider, enrichedData, crmIntegration)

      // Send notifications if configured
      if (submissionConfig.notifications?.enabled) {
        await this.sendNotifications(formData, submissionConfig.notifications, crmResponse)
      }

      return crmResponse

    } catch (error) {
      console.error('CRM integration error:', error)
      return {
        success: false,
        error: error instanceof Error ? error.message : 'Unknown CRM error'
      }
    }
  }

  /**
   * Map form data to CRM fields using field mapping configuration
   */
  private mapFormDataToCRM(
    formData: LeadData,
    mapping: Record<string, string>
  ): LeadData {
    const mappedData: LeadData = {}

    // Apply field mapping
    Object.entries(mapping).forEach(([formField, crmField]) => {
      if (formData[formField] !== undefined) {
        mappedData[crmField] = formData[formField]
      }
    })

    // Include unmapped fields with original names
    Object.entries(formData).forEach(([key, value]) => {
      if (!mapping[key] && !mappedData[key]) {
        mappedData[key] = value
      }
    })

    return mappedData
  }

  /**
   * Send data to specific CRM provider
   */
  private async sendToCRM(
    provider: CRMProvider,
    data: LeadData,
    config: NonNullable<FormSubmissionConfig['crmIntegration']>
  ): Promise<CRMResponse> {
    const endpoint = config.endpoint || provider.endpoint
    
    try {
      const response = await fetch(endpoint, {
        method: 'POST',
        headers: {
          ...provider.headers,
          'X-CRM-Provider': provider.name,
          'Authorization': `Bearer ${provider.authToken || ''}`
        },
        body: JSON.stringify({
          provider: config.provider,
          data,
          config: {
            leadScore: config.leadScore,
            tags: config.tags
          }
        })
      })

      if (!response.ok) {
        throw new Error(`CRM API error: ${response.status} ${response.statusText}`)
      }

      const result = await response.json()
      return {
        success: true,
        ...result
      }

    } catch (error) {
      throw new Error(`Failed to send data to ${provider.name}: ${error}`)
    }
  }

  /**
   * Send email notifications for form submissions
   */
  private async sendNotifications(
    formData: LeadData,
    notificationConfig: NotificationConfig,
    crmResponse: CRMResponse
  ): Promise<void> {
    try {
      // Determine recipients based on form data and configuration
      const recipients = this.determineNotificationRecipients(formData, notificationConfig)

      const notificationData = {
        recipients,
        template: notificationConfig.template,
        subject: this.processTemplate(notificationConfig.subject, formData),
        priority: notificationConfig.priority || 'medium',
        formData,
        crmResponse,
        timestamp: new Date().toISOString()
      }

      await fetch('/api/notifications/form-submission', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(notificationData)
      })

    } catch (error) {
      console.error('Notification sending failed:', error)
      // Don't throw error for notification failures
    }
  }

  /**
   * Determine notification recipients based on form data and routing rules
   */
  private determineNotificationRecipients(
    formData: LeadData,
    config: NotificationConfig
  ): string[] {
    const recipients = [...config.recipients]

    // Dynamic routing based on form data
    if (formData.inquiry_category) {
      const routingMap: Record<string, string[]> = {
        'technical_support': ['support@company.com', 'tech@company.com'],
        'sales': ['sales@company.com'],
        'demo_request': ['sales@company.com', 'demos@company.com'],
        'partnership': ['partnerships@company.com'],
        'media': ['press@company.com'],
        'privacy': ['privacy@company.com', 'legal@company.com'],
        'bug_report': ['support@company.com', 'dev@company.com'],
        'feature_request': ['product@company.com'],
        'general': ['info@company.com']
      }

      const categoryRecipients = routingMap[formData.inquiry_category as string]
      if (categoryRecipients) {
        recipients.push(...categoryRecipients)
      }
    }

    // Priority-based routing
    if (formData.priority_level === 'urgent') {
      recipients.push('urgent@company.com', 'management@company.com')
    } else if (formData.priority_level === 'high') {
      recipients.push('priority@company.com')
    }

    // Institution-specific routing for demo requests
    if (formData.institution_type && formData.alumni_count) {
      const alumniCount = formData.alumni_count as string
      if (alumniCount.includes('>50000') || alumniCount.includes('>100000')) {
        recipients.push('enterprise@company.com', 'vp-sales@company.com')
      }
    }

    // Remove duplicates
    return [...new Set(recipients)]
  }

  /**
   * Process template strings with form data
   */
  private processTemplate(template: string, data: LeadData): string {
    return template.replace(/\{\{(\w+)(?:\|(\w+))?\}\}/g, (match, key, filter) => {
      let value = data[key] || ''
      
      // Apply filters
      if (filter) {
        switch (filter) {
          case 'upper':
            value = value.toString().toUpperCase()
            break
          case 'lower':
            value = value.toString().toLowerCase()
            break
          case 'title':
            value = value.toString().replace(/\w\S*/g, (txt) => 
              txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase()
            )
            break
        }
      }
      
      return value
    })
  }

  /**
   * Validate CRM configuration
   */
  validateCRMConfig(config: FormSubmissionConfig): { valid: boolean; errors: string[] } {
    const errors: string[] = []

    if (!config.crmIntegration) {
      return { valid: true, errors: [] } // CRM integration is optional
    }

    const { crmIntegration } = config

    if (!crmIntegration.provider) {
      errors.push('CRM provider is required when CRM integration is enabled')
    } else if (!this.providers.has(crmIntegration.provider)) {
      errors.push(`Unsupported CRM provider: ${crmIntegration.provider}`)
    }

    if (crmIntegration.leadScore && (crmIntegration.leadScore < 0 || crmIntegration.leadScore > 100)) {
      errors.push('Lead score must be between 0 and 100')
    }

    if (crmIntegration.mapping) {
      const mappingKeys = Object.keys(crmIntegration.mapping)
      if (mappingKeys.length === 0) {
        errors.push('Field mapping cannot be empty when specified')
      }
    }

    return {
      valid: errors.length === 0,
      errors
    }
  }

  /**
   * Test CRM connection
   */
  async testCRMConnection(provider: string): Promise<{ success: boolean; message: string }> {
    try {
      const crmProvider = this.providers.get(provider)
      if (!crmProvider) {
        return { success: false, message: `Provider ${provider} not found` }
      }

      const response = await fetch(`${crmProvider.endpoint}/test`, {
        method: 'GET',
        headers: crmProvider.headers
      })

      if (response.ok) {
        return { success: true, message: `Connection to ${provider} successful` }
      } else {
        return { success: false, message: `Connection failed: ${response.statusText}` }
      }

    } catch (error) {
      return { 
        success: false, 
        message: `Connection error: ${error instanceof Error ? error.message : 'Unknown error'}` 
      }
    }
  }

  /**
   * Get available CRM providers
   */
  getAvailableProviders(): Array<{ id: string; name: string }> {
    return Array.from(this.providers.entries()).map(([id, provider]) => ({
      id,
      name: provider.name
    }))
  }
}

// Export singleton instance
export const crmIntegrationService = new CRMIntegrationService()

// Utility functions for form submission handling
export const formSubmissionUtils = {
  /**
   * Process complete form submission with CRM integration and notifications
   */
  async handleFormSubmission(
    formData: LeadData,
    submissionConfig: FormSubmissionConfig
  ): Promise<{ success: boolean; message: string; crmResponse?: CRMResponse }> {
    try {
      // Validate submission configuration
      const validation = crmIntegrationService.validateCRMConfig(submissionConfig)
      if (!validation.valid) {
        return {
          success: false,
          message: `Configuration error: ${validation.errors.join(', ')}`
        }
      }

      // Process CRM integration
      const crmResponse = await crmIntegrationService.processFormSubmission(
        formData,
        submissionConfig
      )

      if (!crmResponse.success && crmResponse.error) {
        console.error('CRM integration failed:', crmResponse.error)
        // Continue with form submission even if CRM fails
      }

      return {
        success: true,
        message: submissionConfig.successMessage || 'Form submitted successfully',
        crmResponse
      }

    } catch (error) {
      console.error('Form submission error:', error)
      return {
        success: false,
        message: submissionConfig.errorMessage || 'Form submission failed. Please try again.'
      }
    }
  },

  /**
   * Generate lead score based on form data
   */
  calculateLeadScore(formData: LeadData, baseScore: number = 0): number {
    let score = baseScore

    // Score based on completeness
    const totalFields = Object.keys(formData).length
    const completedFields = Object.values(formData).filter(value => 
      value !== null && value !== undefined && value !== ''
    ).length
    
    const completenessScore = (completedFields / totalFields) * 20
    score += completenessScore

    // Score based on specific field values
    if (formData.email && formData.email.includes('@')) {
      score += 10
    }

    if (formData.phone) {
      score += 5
    }

    if (formData.company || formData.organization || formData.institution_name) {
      score += 15
    }

    if (formData.job_title || formData.contact_title) {
      score += 10
    }

    // Higher scores for qualified leads
    if (formData.budget_range && !formData.budget_range.includes('<')) {
      score += 20
    }

    if (formData.timeline && ['immediate', '1-3months'].includes(formData.timeline)) {
      score += 15
    }

    if (formData.decision_role === 'decision_maker') {
      score += 25
    }

    return Math.min(Math.max(score, 0), 100) // Clamp between 0-100
  }
}