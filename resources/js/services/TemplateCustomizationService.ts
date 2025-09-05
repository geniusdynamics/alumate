import { httpService } from './httpService'
import type {
  TemplateCustomizationConfig,
  BrandCustomization,
  ContentCustomization,
  TemplateCustomizationAPIResponse
} from '@/types/components'

class TemplateCustomizationService {
  private readonly baseUrl = '/api/template-customizer'

  /**
   * Get template customization configuration
   */
  async getConfig(templateId: number): Promise<TemplateCustomizationAPIResponse> {
    const response = await httpService.get<TemplateCustomizationAPIResponse>(
      `${this.baseUrl}/templates/${templateId}/config`
    )
    return response.data
  }

  /**
   * Save template customization configuration
   */
  async saveConfig(
    templateId: number,
    config: Partial<TemplateCustomizationConfig>
  ): Promise<TemplateCustomizationAPIResponse> {
    const response = await httpService.post<TemplateCustomizationAPIResponse>(
      `${this.baseUrl}/templates/${templateId}/config`,
      config
    )
    return response.data
  }

  /**
   * Get brand customization for template
   */
  async getBrandCustomization(templateId: number): Promise<BrandCustomization> {
    const response = await httpService.get<BrandCustomization>(
      `${this.baseUrl}/templates/${templateId}/brand`
    )
    return response.data
  }

  /**
   * Save brand customization
   */
  async saveBrandCustomization(
    templateId: number,
    brand: Partial<BrandCustomization>
  ): Promise<BrandCustomization> {
    const response = await httpService.put<BrandCustomization>(
      `${this.baseUrl}/templates/${templateId}/brand`,
      brand
    )
    return response.data
  }

  /**
   * Get content customization for template
   */
  async getContentCustomization(templateId: number): Promise<ContentCustomization> {
    const response = await httpService.get<ContentCustomization>(
      `${this.baseUrl}/templates/${templateId}/content`
    )
    return response.data
  }

  /**
   * Save content customization
   */
  async saveContentCustomization(
    templateId: number,
    content: Partial<ContentCustomization>
  ): Promise<ContentCustomization> {
    const response = await httpService.put<ContentCustomization>(
      `${this.baseUrl}/templates/${templateId}/content`,
      content
    )
    return response.data
  }

  /**
   * Generate live preview with current customization
   */
  async generatePreview(
    templateId: number,
    customization: Partial<TemplateCustomizationConfig>,
    viewport: 'desktop' | 'tablet' | 'mobile' = 'desktop'
  ): Promise<{
    html: string
    css: string
    data: any
    responsive_previews?: {
      desktop: { html: string; width: number }
      tablet: { html: string; width: number }
      mobile: { html: string; width: number }
    }
  }> {
    const response = await httpService.post<{
      html: string
      css: string
      data: any
      responsive_previews?: {
        desktop: { html: string; width: number }
        tablet: { html: string; width: number }
        mobile: { html: string; width: number }
      }
    }>(
      `${this.baseUrl}/templates/${templateId}/preview`,
      {
        customization,
        viewport
      }
    )
    return response.data
  }

  /**
   * Export customization as different formats
   */
  async exportCustomization(
    templateId: number,
    format: 'html' | 'css' | 'json' | 'pdf',
    customization: Partial<TemplateCustomizationConfig>
  ): Promise<Blob> {
    const response = await httpService.post(
      `${this.baseUrl}/templates/${templateId}/export`,
      {
        format,
        customization
      },
      {
        responseType: 'blob'
      }
    )
    return response.data
  }

  /**
   * Validate customization configuration
   */
  async validateCustomization(
    templateId: number,
    customization: Partial<TemplateCustomizationConfig>
  ): Promise<{
    valid: boolean
    errors: Array<{ field: string; message: string; severity: 'error' | 'warning' | 'info' }>
    score: {
      accessibility: number
      performance: number
      seo: number
      overall: number
    }
  }> {
    const response = await httpService.post<{
      valid: boolean
      errors: Array<{ field: string; message: string; severity: 'error' | 'warning' | 'info' }>
      score: {
        accessibility: number
        performance: number
        seo: number
        overall: number
      }
    }>(
      `${this.baseUrl}/templates/${templateId}/validate`,
      customization
    )
    return response.data
  }

  /**
   * Get customization presets/templates
   */
  async getPresets(category?: string): Promise<BrandCustomization[]> {
    const queryParams = category ? `?category=${category}` : ''
    const response = await httpService.get<BrandCustomization[]>(
      `${this.baseUrl}/presets${queryParams}`
    )
    return response.data
  }

  /**
   * Apply preset to customization
   */
  async applyPreset(
    templateId: number,
    presetId: string
  ): Promise<TemplateCustomizationConfig> {
    const response = await httpService.post<TemplateCustomizationConfig>(
      `${this.baseUrl}/templates/${templateId}/presets/${presetId}/apply`
    )
    return response.data
  }

  /**
   * Get real-time preview updates
   */
  async getRealtimePreview(
    templateId: number,
    changes: {
      type: 'brand' | 'content' | 'settings'
      data: any
    }[]
  ): Promise<{
    diffs: Array<{
      change: string
      impact: 'low' | 'medium' | 'high'
      suggestion?: string
    }>
    preview: {
      html: string
      css: string
    }
  }> {
    const response = await httpService.post<{
      diffs: Array<{
        change: string
        impact: 'low' | 'medium' | 'high'
        suggestion?: string
      }>
      preview: {
        html: string
        css: string
      }
    }>(
      `${this.baseUrl}/templates/${templateId}/preview/realtime`,
      { changes }
    )
    return response.data
  }

  /**
   * Create shareable link for customization
   */
  async createShareableLink(
    templateId: number,
    customization: TemplateCustomizationConfig
  ): Promise<{ shareUrl: string; expiryDate: string }> {
    const response = await httpService.post<{ shareUrl: string; expiryDate: string }>(
      `${this.baseUrl}/templates/${templateId}/share`,
      {
        customization
      }
    )
    return response.data
  }

  /**
   * Import customization from JSON/config
   */
  async importCustomization(
    templateId: number,
    configJson: string
  ): Promise<TemplateCustomizationConfig> {
    const response = await httpService.post<TemplateCustomizationConfig>(
      `${this.baseUrl}/templates/${templateId}/import`,
      {
        config: JSON.parse(configJson)
      }
    )
    return response.data
  }

  /**
   * Auto-fix accessibility and validation issues
   */
  async autoFixIssues(
    templateId: number,
    customization: Partial<TemplateCustomizationConfig>,
    fixTypes: ('contrast' | 'accessibility' | 'performance' | 'seo')[]
  ): Promise<{
    fixedCustomization: TemplateCustomizationConfig
    appliedFixes: Array<{
      type: string
      description: string
      before: any
      after: any
    }>
    remainingIssues: Array<{
      field: string
      message: string
      severity: 'error' | 'warning' | 'info'
    }>
  }> {
    const response = await httpService.post<{
      fixedCustomization: TemplateCustomizationConfig
      appliedFixes: Array<{
        type: string
        description: string
        before: any
        after: any
      }>
      remainingIssues: Array<{
        field: string
        message: string
        severity: 'error' | 'warning' | 'info'
      }>
    }>(
      `${this.baseUrl}/templates/${templateId}/auto-fix`,
      {
        customization,
        fixTypes
      }
    )
    return response.data
  }

  /**
   * Get customization analytics
   */
  async getAnalytics(
    templateId: number
  ): Promise<{
    views: number
    saves: number
    exports: number
    shares: number
    popularConfigurations: Array<{
      config: Partial<TemplateCustomizationConfig>
      usageCount: number
      lastUsed: string
    }>
    performance: {
      averageLoadTime: number
      averageInteractionTime: number
      conversionRate: number
    }
  }> {
    const response = await httpService.get<{
      views: number
      saves: number
      exports: number
      shares: number
      popularConfigurations: Array<{
        config: Partial<TemplateCustomizationConfig>
        usageCount: number
        lastUsed: string
      }>
      performance: {
        averageLoadTime: number
        averageInteractionTime: number
        conversionRate: number
      }
    }>(
      `${this.baseUrl}/templates/${templateId}/analytics`
    )
    return response.data
  }

  /**
   * Bulk update multiple templates
   */
  async bulkUpdate(
    templateIds: number[],
    updates: Partial<TemplateCustomizationConfig>
  ): Promise<{
    successful: number[]
    failed: Array<{ templateId: number; error: string }>
    totalChanges: number
  }> {
    const response = await httpService.post<{
      successful: number[]
      failed: Array<{ templateId: number; error: string }>
      totalChanges: number
    }>(
      `${this.baseUrl}/bulk-update`,
      {
        templateIds,
        updates
      }
    )
    return response.data
  }

  /**
   * Check tenant access for template customization
   */
  async checkTenantAccess(templateId: number): Promise<{
    canAccess: boolean
    tenantId: string | null
    permissions: {
      canEdit: boolean
      canDelete: boolean
      canShare: boolean
      canExport: boolean
    }
  }> {
    const response = await httpService.get<{
      canAccess: boolean
      tenantId: string | null
      permissions: {
        canEdit: boolean
        canDelete: boolean
        canShare: boolean
        canExport: boolean
      }
    }>(
      `${this.baseUrl}/templates/${templateId}/access`
    )
    return response.data
  }

  /**
   * Get customization history/versions
   */
  async getHistory(
    templateId: number,
    limit: number = 20,
    offset: number = 0
  ): Promise<{
    versions: Array<{
      version: number
      createdAt: string
      userId: number
      userName: string
      changes: string[]
      config: TemplateCustomizationConfig
    }>
    total: number
    hasMore: boolean
  }> {
    const response = await httpService.get<{
      versions: Array<{
        version: number
        createdAt: string
        userId: number
        userName: string
        changes: string[]
        config: TemplateCustomizationConfig
      }>
      total: number
      hasMore: boolean
    }>(
      `${this.baseUrl}/templates/${templateId}/history?limit=${limit}&offset=${offset}`
    )
    return response.data
  }

  /**
   * Restore customization from specific version
   */
  async restoreVersion(
    templateId: number,
    version: number
  ): Promise<TemplateCustomizationConfig> {
    const response = await httpService.post<TemplateCustomizationConfig>(
      `${this.baseUrl}/templates/${templateId}/restore/${version}`
    )
    return response.data
  }

  /**
   * Get customization recommendations
   */
  async getRecommendations(
    templateId: number,
    customization: Partial<TemplateCustomizationConfig>
  ): Promise<{
    score: {
      overall: number
      categories: {
        accessibility: number
        usability: number
        performance: number
        seo: number
        branding: number
      }
    }
    recommendations: Array<{
      category: string
      priority: 'low' | 'medium' | 'high' | 'critical'
      title: string
      description: string
      impact: string
      effort: 'low' | 'medium' | 'high'
      suggestedFix?: any
    }>
    bestPractices: Array<{
      category: string
      rule: string
      passed: boolean
      explanation: string
    }>
  }> {
    const response = await httpService.post<{
      score: {
        overall: number
        categories: {
          accessibility: number
          usability: number
          performance: number
          seo: number
          branding: number
        }
      }
      recommendations: Array<{
        category: string
        priority: 'low' | 'medium' | 'high' | 'critical'
        title: string
        description: string
        impact: string
        effort: 'low' | 'medium' | 'high'
        suggestedFix?: any
      }>
      bestPractices: Array<{
        category: string
        rule: string
        passed: boolean
        explanation: string
      }>
    }>(
      `${this.baseUrl}/templates/${templateId}/recommendations`,
      customization
    )
    return response.data
  }
}

export const templateCustomizationService = new TemplateCustomizationService()
export default templateCustomizationService