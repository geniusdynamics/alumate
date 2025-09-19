
/**
 * Template Creation Bridge Service for GrapeJS Integration
 *
 * This service provides the bridge between the Template Creation System
 * and GrapeJS Page Builder, handling template conversion, serialization,
 * and synchronization.
 */

import type {
  Template,
  TemplateCategory,
  GrapeJSBlockMetadata,
  GrapeJSComponentDefinition,
  GrapeJSTrait,
  ComponentGrapeJSMetadata,
  GrapeJSSerializationData
} from '@/types/components'

// Enhanced interfaces for template functionality
interface TemplateCategoryData {
  id: string
  name: string
  icon: string
  description: string
  templates: Template[]
  order: number
  isCollapsed: boolean
}

interface TemplateSearchResult {
  template: Template
  relevanceScore: number
  matchedFields: string[]
  highlights: Record<string, string>
}

interface TemplateUsageStats {
  templateId: number
  totalUsage: number
  recentUsage: number
  averageRating: number
  conversionRate: number
  lastUsed: Date
  popularConfigurations: any[]
}

interface TemplateDocumentation {
  title: string
  description: string
  examples: TemplateExample[]
  properties: TemplateProperty[]
  tips: string[]
  troubleshooting: TroubleshootingItem[]
}

interface TemplateExample {
  title: string
  description: string
  config: any
  previewUrl?: string
}

interface TemplateProperty {
  name: string
  type: string
  description: string
  required: boolean
  defaultValue?: any
  options?: Array<{ value: any; label: string }>
}

interface TroubleshootingItem {
  issue: string
  solution: string
  severity: 'low' | 'medium' | 'high'
}

interface SyncEvent {
  type: 'template_updated' | 'template_deleted' | 'template_created'
  templateId: number
  data: any
  timestamp: Date
}

interface TemplateCreationBridgeInterface {
  convertToGrapeJSBlock(template: Template): GrapeJSBlockMetadata
  convertFromGrapeJSData(data: GrapeJSSerializationData): Template[]
  syncTemplateUpdates(templateId: number): Promise<void>
  generatePreviewImage(template: Template): Promise<string>
  validateGrapeJSCompatibility(template: Template): { valid: boolean; errors: string[] }
}

/**
 * Template Category Manager for GrapeJS Block Manager organization
 */
class TemplateCategoryManager {
  private categories: Map<string, TemplateCategoryData> = new Map()

  initializeCategories(): void {
    const defaultCategories: TemplateCategoryData[] = [
      {
        id: 'landing',
        name: 'Landing Pages',
        icon: '🎯',
        description: 'High-converting landing pages optimized for different audiences',
        templates: [],
        order: 1,
        isCollapsed: false
      },
      {
        id: 'homepage',
        name: 'Home Pages',
        icon: '🏠',
        description: 'Comprehensive homepage templates for various industries',
        templates: [],
        order: 2,
        isCollapsed: false
      },
      {
        id: 'form',
        name: 'Form Pages',
        icon: '📝',
        description: 'Lead capture and form-based page templates',
        templates: [],
        order: 3,
        isCollapsed: false
      },
      {
        id: 'email',
        name: 'Email Templates',
        icon: '📧',
        description: 'Professional email templates for campaigns',
        templates: [],
        order: 4,
        isCollapsed: false
      },
      {
        id: 'social',
        name: 'Social Media',
        icon: '📱',
        description: 'Social media post and profile templates',
        templates: [],
        order: 5,
        isCollapsed: false
      }
    ]

    defaultCategories.forEach(category => {
      this.categories.set(category.id, category)
    })
  }

  addTemplateToCategory(template: Template): void {
    const category = this.categories.get(template.category)
    if (category) {
      const existingIndex = category.templates.findIndex(t => t.id === template.id)
      if (existingIndex >= 0) {
        category.templates[existingIndex] = template
      } else {
        category.templates.push(template)
      }
    }
  }

  removeTemplateFromCategory(templateId: number, categoryId: string): void {
    const category = this.categories.get(categoryId)
    if (category) {
      category.templates = category.templates.filter(t => t.id !== templateId)
    }
  }

  getCategoryData(categoryId: string): TemplateCategoryData | undefined {
    return this.categories.get(categoryId)
  }

  getAllCategories(): TemplateCategoryData[] {
    return Array.from(this.categories.values()).sort((a, b) => a.order - b.order)
  }

  getGrapeJSBlockManagerConfig(): any {
    return this.getAllCategories().map(category => ({
      id: category.id,
      label: category.name,
      open: !category.isCollapsed,
      attributes: {
        'data-category-id': category.id,
        'data-category-description': category.description
      }
    }))
  }

  toggleCategoryCollapse(categoryId: string): void {
    const category = this.categories.get(categoryId)
    if (category) {
      category.isCollapsed = !category.isCollapsed
    }
  }

  reorderCategories(newOrder: string[]): void {
    newOrder.forEach((categoryId, index) => {
      const category = this.categories.get(categoryId)
      if (category) {
        category.order = index + 1
      }
    })
  }
}

/**
 * Template Search Index for GrapeJS palette filtering
 */
class TemplateSearchIndex {
  private searchIndex: Map<string, Template[]> = new Map()
  private tagIndex: Map<string, Template[]> = new Map()
  private categoryIndex: Map<string, Template[]> = new Map()

  indexTemplate(template: Template): void {
    // Index by name and description
    const searchTerms = [
      template.name.toLowerCase(),
      ...(template.description?.toLowerCase().split(' ') || []),
      template.category.toLowerCase(),
      template.audienceType.toLowerCase(),
      template.campaignType.toLowerCase()
    ]

    searchTerms.forEach(term => {
      if (!this.searchIndex.has(term)) {
        this.searchIndex.set(term, [])
      }
      const templates = this.searchIndex.get(term)!
      if (!templates.find(t => t.id === template.id)) {
        templates.push(template)
      }
    })

    // Index by category
    if (!this.categoryIndex.has(template.category)) {
      this.categoryIndex.set(template.category, [])
    }
    const categoryTemplates = this.categoryIndex.get(template.category)!
    const existingIndex = categoryTemplates.findIndex(t => t.id === template.id)
    if (existingIndex >= 0) {
      categoryTemplates[existingIndex] = template
    } else {
      categoryTemplates.push(template)
    }

    // Index by tags
    const tags = this.extractTags(template)
    tags.forEach(tag => {
      if (!this.tagIndex.has(tag)) {
        this.tagIndex.set(tag, [])
      }
      const tagTemplates = this.tagIndex.get(tag)!
      if (!tagTemplates.find(t => t.id === template.id)) {
        tagTemplates.push(template)
      }
    })
  }

  removeTemplate(templateId: number): void {
    // Remove from all indexes
    this.searchIndex.forEach(templates => {
      const index = templates.findIndex(t => t.id === templateId)
      if (index >= 0) {
        templates.splice(index, 1)
      }
    })

    this.categoryIndex.forEach(templates => {
      const index = templates.findIndex(t => t.id === templateId)
      if (index >= 0) {
        templates.splice(index, 1)
      }
    })

    this.tagIndex.forEach(templates => {
      const index = templates.findIndex(t => t.id === templateId)
      if (index >= 0) {
        templates.splice(index, 1)
      }
    })
  }

  search(query: string, filters?: {
    category?: string
    tags?: string[]
    audienceType?: string
    campaignType?: string
  }): TemplateSearchResult[] {
    const queryTerms = query.toLowerCase().split(' ').filter(term => term.length > 0)

    if (queryTerms.length === 0 && !filters) {
      return []
    }

    // Get all templates that match the search terms
    const matchedTemplates = new Map<number, { template: Template; score: number; matches: string[] }>()

    queryTerms.forEach(term => {
      this.searchIndex.forEach((templates, indexTerm) => {
        if (indexTerm.includes(term)) {
          templates.forEach(template => {
            const existing = matchedTemplates.get(template.id)
            const score = this.calculateRelevanceScore(term, indexTerm, template)

            if (existing) {
              existing.score += score
              existing.matches.push(indexTerm)
            } else {
              matchedTemplates.set(template.id, {
                template,
                score,
                matches: [indexTerm]
              })
            }
          })
        }
      })
    })

    // Apply filters
    let filteredTemplates = Array.from(matchedTemplates.values())

    if (filters?.category) {
      filteredTemplates = filteredTemplates.filter(item =>
        item.template.category === filters.category
      )
    }

    if (filters?.audienceType) {
      filteredTemplates = filteredTemplates.filter(item =>
        item.template.audienceType === filters.audienceType
      )
    }

    if (filters?.campaignType) {
      filteredTemplates = filteredTemplates.filter(item =>
        item.template.campaignType === filters.campaignType
      )
    }

    if (filters?.tags && filters.tags.length > 0) {
      filteredTemplates = filteredTemplates.filter(item => {
        const templateTags = this.extractTags(item.template)
        return filters.tags!.some(tag => templateTags.includes(tag))
      })
    }

    // Convert to search results and sort by relevance
    return filteredTemplates
      .map(item => ({
        template: item.template,
        relevanceScore: item.score,
        matchedFields: item.matches,
        highlights: this.generateHighlights(item.template, queryTerms)
      }))
      .sort((a, b) => b.relevanceScore - a.relevanceScore)
  }

  getTemplatesByCategory(category: string): Template[] {
    return this.categoryIndex.get(category) || []
  }

  getTemplatesByTag(tag: string): Template[] {
    return this.tagIndex.get(tag) || []
  }

  getAllTags(): string[] {
    return Array.from(this.tagIndex.keys())
  }

  private extractTags(template: Template): string[] {
    const tags: string[] = [template.category, template.audienceType, template.campaignType]

    if (template.tags) {
      tags.push(...template.tags)
    }

    return tags.map(tag => tag.toLowerCase())
  }

  private calculateRelevanceScore(searchTerm: string, indexTerm: string, template: Template): number {
    let score = 0

    // Exact match gets highest score
    if (indexTerm === searchTerm) {
      score += 10
    }
    // Starts with search term gets high score
    else if (indexTerm.startsWith(searchTerm)) {
      score += 7
    }
    // Contains search term gets medium score
    else if (indexTerm.includes(searchTerm)) {
      score += 5
    }

    // Boost score for matches in template name
    if (template.name.toLowerCase().includes(searchTerm)) {
      score += 3
    }

    // Boost score for matches in category
    if (template.category.toLowerCase().includes(searchTerm)) {
      score += 2
    }

    return score
  }

  private generateHighlights(template: Template, queryTerms: string[]): Record<string, string> {
    const highlights: Record<string, string> = {}

    queryTerms.forEach(term => {
      if (template.name.toLowerCase().includes(term)) {
        highlights.name = this.highlightText(template.name, term)
      }
      if (template.description?.toLowerCase().includes(term)) {
        highlights.description = this.highlightText(template.description, term)
      }
    })

    return highlights
  }

  private highlightText(text: string, term: string): string {
    const regex = new RegExp(`(${term})`, 'gi')
    return text.replace(regex, '<mark>$1</mark>')
  }
}

/**
 * Template Usage Tracker for GrapeJS analytics
 */
class TemplateUsageTracker {
  private usageStats: Map<number, TemplateUsageStats> = new Map()
  private recentUsage: Array<{ templateId: number; timestamp: Date; context: string }> = []

  trackTemplateUsage(templateId: number, context: string = 'grapeJS'): void {
    // Update usage stats
    const stats = this.usageStats.get(templateId) || {
      templateId,
      totalUsage: 0,
      recentUsage: 0,
      averageRating: 0,
      conversionRate: 0,
      lastUsed: new Date(),
      popularConfigurations: []
    }

    stats.totalUsage++
    stats.lastUsed = new Date()
    this.usageStats.set(templateId, stats)

    // Track recent usage
    this.recentUsage.push({
      templateId,
      timestamp: new Date(),
      context
    })

    // Keep only last 1000 usage records
    if (this.recentUsage.length > 1000) {
      this.recentUsage = this.recentUsage.slice(-1000)
    }

    // Update recent usage count (last 7 days)
    this.updateRecentUsageCounts()
  }

  trackTemplateRating(templateId: number, rating: number): void {
    const stats = this.usageStats.get(templateId)
    if (stats) {
      // Simple moving average for now
      stats.averageRating = (stats.averageRating + rating) / 2
      this.usageStats.set(templateId, stats)
    }
  }

  trackTemplateConfiguration(templateId: number, config: any): void {
    const stats = this.usageStats.get(templateId)
    if (stats) {
      // Track popular configurations
      const configHash = this.hashConfig(config)
      const existingConfig = stats.popularConfigurations.find(c => c.hash === configHash)

      if (existingConfig) {
        existingConfig.count++
      } else {
        stats.popularConfigurations.push({
          hash: configHash,
          config,
          count: 1
        })
      }

      // Keep only top 10 configurations
      stats.popularConfigurations = stats.popularConfigurations
        .sort((a, b) => b.count - a.count)
        .slice(0, 10)

      this.usageStats.set(templateId, stats)
    }
  }

  getTemplateStats(templateId: number): TemplateUsageStats | undefined {
    return this.usageStats.get(templateId)
  }

  getAllStats(): TemplateUsageStats[] {
    return Array.from(this.usageStats.values())
  }

  getMostUsedTemplates(limit: number = 10): TemplateUsageStats[] {
    return Array.from(this.usageStats.values())
      .sort((a, b) => b.totalUsage - a.totalUsage)
      .slice(0, limit)
  }

  getRecentlyUsedTemplates(limit: number = 10): TemplateUsageStats[] {
    return Array.from(this.usageStats.values())
      .sort((a, b) => b.lastUsed.getTime() - a.lastUsed.getTime())
      .slice(0, limit)
  }

  getTrendingTemplates(limit: number = 10): TemplateUsageStats[] {
    return Array.from(this.usageStats.values())
      .sort((a, b) => b.recentUsage - a.recentUsage)
      .slice(0, limit)
  }

  getAnalyticsData(): {
    totalTemplates: number
    totalUsage: number
    averageRating: number
    mostUsedCategory: string
    usageTrend: Array<{ date: string; count: number }>
  } {
    const stats = Array.from(this.usageStats.values())
    const totalUsage = stats.reduce((sum, stat) => sum + stat.totalUsage, 0)
    const averageRating = stats.reduce((sum, stat) => sum + stat.averageRating, 0) / stats.length

    // Calculate usage trend for last 7 days
    const usageTrend = this.calculateUsageTrend()

    return {
      totalTemplates: stats.length,
      totalUsage,
      averageRating: averageRating || 0,
      mostUsedCategory: this.getMostUsedCategory(),
      usageTrend
    }
  }

  private updateRecentUsageCounts(): void {
    const sevenDaysAgo = new Date()
    sevenDaysAgo.setDate(sevenDaysAgo.getDate() - 7)

    this.usageStats.forEach((stats, templateId) => {
      const recentCount = this.recentUsage.filter(usage =>
        usage.templateId === templateId && usage.timestamp > sevenDaysAgo
      ).length

      stats.recentUsage = recentCount
      this.usageStats.set(templateId, stats)
    })
  }

  private hashConfig(config: any): string {
    return btoa(JSON.stringify(config)).slice(0, 16)
  }

  private getMostUsedCategory(): string {
    // This would need access to template data to determine categories
    // For now, return a placeholder
    return 'landing'
  }

  private calculateUsageTrend(): Array<{ date: string; count: number }> {
    const trend: Array<{ date: string; count: number }> = []
    const today = new Date()

    for (let i = 6; i >= 0; i--) {
      const date = new Date(today)
      date.setDate(date.getDate() - i)
      const dateStr = date.toISOString().split('T')[0]

      const count = this.recentUsage.filter(usage => {
        const usageDate = usage.timestamp.toISOString().split('T')[0]
        return usageDate === dateStr
      }).length

      trend.push({ date: dateStr, count })
    }

    return trend
  }
}

/**
 * Template Documentation Generator for GrapeJS interface
 */
class TemplateDocumentationGenerator {
  generateDocumentation(template: Template): TemplateDocumentation {
    return {
      title: template.name,
      description: template.description || this.generateDescription(template),
      examples: this.generateExamples(template),
      properties: this.generateProperties(template),
      tips: this.generateTips(template),
      troubleshooting: this.generateTroubleshooting(template)
    }
  }

  generateTooltip(template: Template): string {
    const doc = this.generateDocumentation(template)
    return `${doc.title}\n\n${doc.description}\n\nClick to load this template.`
  }

  generatePropertyTooltip(property: TemplateProperty): string {
    let tooltip = `${property.name} (${property.type})`
    if (property.required) {
      tooltip += ' *Required'
    }
    tooltip += `\n\n${property.description}`
    if (property.defaultValue !== undefined) {
      tooltip += `\n\nDefault: ${property.defaultValue}`
    }
    return tooltip
  }

  private generateDescription(template: Template): string {
    const descriptions: Record<TemplateCategory, string> = {
      landing: 'High-converting landing page template optimized for lead generation and user engagement.',
      homepage: 'Comprehensive homepage template designed for brand storytelling and conversion optimization.',
      form: 'Lead capture form template with built-in validation and CRM integration capabilities.',
      email: 'Professional email template for marketing campaigns and communication.',
      social: 'Social media template optimized for engagement and brand consistency.'
    }

    return descriptions[template.category] || 'A reusable template for your pages.'
  }

  private generateExamples(template: Template): TemplateExample[] {
    const examples: TemplateExample[] = []

    switch (template.category) {
      case 'landing':
        examples.push(
          {
            title: 'Lead Generation Landing Page',
            description: 'Optimized for capturing leads with compelling copy and clear CTAs',
            config: {
              audienceType: 'individual',
              headline: 'Transform Your Career',
              subheading: 'Join thousands of professionals who have advanced their careers',
              layout: 'hero-centered'
            }
          },
          {
            title: 'Product Launch Page',
            description: 'Perfect for announcing new products or services',
            config: {
              audienceType: 'institution',
              headline: 'Introducing Our New Solution',
              subheading: 'Revolutionary technology for modern businesses',
              layout: 'feature-showcase'
            }
          }
        )
        break

      case 'homepage':
        examples.push(
          {
            title: 'Corporate Homepage',
            description: 'Professional homepage for established businesses',
            config: {
              audienceType: 'institution',
              sections: ['hero', 'services', 'testimonials', 'contact'],
              layout: 'multi-section'
            }
          }
        )
        break

      case 'form':
        examples.push(
          {
            title: 'Contact Form',
            description: 'Simple contact form for general inquiries',
            config: {
              title: 'Get In Touch',
              layout: 'single-column',
              fields: [
                { type: 'text', name: 'name', label: 'Full Name', required: true },
                { type: 'email', name: 'email', label: 'Email Address', required: true },
                { type: 'textarea', name: 'message', label: 'Message', required: true }
              ]
            }
          }
        )
        break
    }

    return examples
  }

  private generateProperties(template: Template): TemplateProperty[] {
    const properties: TemplateProperty[] = []

    // Add common properties
    properties.push(
      {
        name: 'id',
        type: 'number',
        description: 'Unique identifier for the template',
        required: false
      },
      {
        name: 'name',
        type: 'string',
        description: 'Display name of the template',
        required: true
      },
      {
        name: 'description',
        type: 'string',
        description: 'Description of the template',
        required: false
      }
    )

    // Add category-specific properties
    switch (template.category) {
      case 'landing':
        properties.push(
          {
            name: 'audienceType',
            type: 'select',
            description: 'Target audience for the landing page',
            required: true,
            options: [
              { value: 'individual', label: 'Individual Alumni' },
              { value: 'institution', label: 'Institution' },
              { value: 'employer', label: 'Employer' },
              { value: 'general', label: 'General' }
            ]
          },
          {
            name: 'campaignType',
            type: 'select',
            description: 'Type of campaign this landing page supports',
            required: true,
            options: [
              { value: 'onboarding', label: 'Onboarding' },
              { value: 'event_promotion', label: 'Event Promotion' },
              { value: 'donation', label: 'Donation' },
              { value: 'networking', label: 'Networking' },
              { value: 'career_services', label: 'Career Services' },
              { value: 'recruiting', label: 'Recruiting' },
              { value: 'leadership', label: 'Leadership' },
              { value: 'marketing', label: 'Marketing' }
            ]
          }
        )
        break

      case 'homepage':
        properties.push(
          {
            name: 'sections',
            type: 'array',
            description: 'Sections to include in the homepage',
            required: false,
            defaultValue: ['hero', 'services', 'about', 'contact']
          }
        )
        break
    }

    return properties
  }

  private generateTips(template: Template): string[] {
    const tips: Record<TemplateCategory, string[]> = {
      landing: [
        'Use compelling headlines that speak directly to your audience',
        'Keep forms short to reduce abandonment',
        'Include social proof elements like testimonials',
        'Test different CTAs to optimize conversion rates'
      ],
      homepage: [
        'Structure content hierarchically for better user experience',
        'Include clear navigation and calls-to-action',
        'Optimize for mobile viewing experience',
        'Use high-quality images and consistent branding'
      ],
      form: [
        'Keep forms concise to improve completion rates',
        'Use clear, descriptive field labels',
        'Provide real-time validation feedback',
        'Test different layouts for your audience'
      ],
      email: [
        'Keep subject lines under 50 characters',
        'Use a clear, single call-to-action',
        'Optimize for mobile email clients',
        'Personalize content when possible'
      ],
      social: [
        'Use high-contrast colors for better readability',
        'Keep text concise and impactful',
        'Include your brand elements consistently',
        'Optimize image sizes for different platforms'
      ]
    }

    return tips[template.category] || []
  }

  private generateTroubleshooting(template: Template): TroubleshootingItem[] {
    const troubleshooting: TroubleshootingItem[] = [
      {
        issue: 'Template not loading correctly',
        solution: 'Check that all required properties are set and valid',
        severity: 'medium'
      },
      {
        issue: 'Styling conflicts with theme',
        solution: 'Review custom CSS classes and theme compatibility',
        severity: 'low'
      }
    ]

    switch (template.category) {
      case 'landing':
        troubleshooting.push(
          {
            issue: 'Form submissions not working',
            solution: 'Verify form action URL and ensure proper validation',
            severity: 'high'
          },
          {
            issue: 'CTA buttons not converting',
            solution: 'Test different button colors, text, and positioning',
            severity: 'medium'
          }
        )
        break

      case 'form':
        troubleshooting.push(
          {
            issue: 'Form validation errors',
            solution: 'Check validation rules and user input requirements',
            severity: 'medium'
          }
        )
        break
    }

    return troubleshooting
  }
}

/**
 * Real-time Sync Manager for template updates
 */
class RealTimeSyncManager {
  private eventListeners: Map<string, Array<(event: SyncEvent) => void>> = new Map()
  private websocket: WebSocket | null = null
  private reconnectAttempts = 0
  private maxReconnectAttempts = 5

  initialize(): void {
    this.connectWebSocket()
  }

  onTemplateUpdate(callback: (event: SyncEvent) => void): void {
    this.addEventListener('template_updated', callback)
  }

  onTemplateCreated(callback: (event: SyncEvent) => void): void {
    this.addEventListener('template_created', callback)
  }

  onTemplateDeleted(callback: (event: SyncEvent) => void): void {
    this.addEventListener('template_deleted', callback)
  }

  broadcastTemplateUpdate(templateId: number, data: any): void {
    this.broadcastEvent({
      type: 'template_updated',
      templateId,
      data,
      timestamp: new Date()
    })
  }

  broadcastTemplateCreated(templateId: number, data: any): void {
    this.broadcastEvent({
      type: 'template_created',
      templateId,
      data,
      timestamp: new Date()
    })
  }

  broadcastTemplateDeleted(templateId: number): void {
    this.broadcastEvent({
      type: 'template_deleted',
      templateId,
      data: null,
      timestamp: new Date()
    })
  }

  private addEventListener(eventType: string, callback: (event: SyncEvent) => void): void {
    if (!this.eventListeners.has(eventType)) {
      this.eventListeners.set(eventType, [])
    }
    this.eventListeners.get(eventType)!.push(callback)
  }

  private broadcastEvent(event: SyncEvent): void {
    // Broadcast to local listeners
    const listeners = this.eventListeners.get(event.type) || []
    listeners.forEach(callback => {
      try {
        callback(event)
      } catch (error) {
        console.error('Error in sync event listener:', error)
      }
    })

    // Send via WebSocket if connected
    if (this.websocket && this.websocket.readyState === WebSocket.OPEN) {
      this.websocket.send(JSON.stringify(event))
    }
  }

  private connectWebSocket(): void {
    try {
      // In a real implementation, this would connect to your WebSocket server
      const wsUrl = `${window.location.protocol === 'https:' ? 'wss:' : 'ws:'}//${window.location.host}/ws/templates`
      this.websocket = new WebSocket(wsUrl)

      this.websocket.onopen = () => {
        console.log('Template sync WebSocket connected')
        this.reconnectAttempts = 0
      }

      this.websocket.onmessage = (event) => {
        try {
          const syncEvent: SyncEvent = JSON.parse(event.data)
          this.broadcastEvent(syncEvent)
        } catch (error) {
          console.error('Error parsing sync event:', error)
        }
      }

      this.websocket.onclose = () => {
        console.log('Template sync WebSocket disconnected')
        this.attemptReconnect()
      }

      this.websocket.onerror = (error) => {
        console.error('Template sync WebSocket error:', error)
      }
    } catch (error) {
      console.error('Failed to connect WebSocket:', error)
      this.attemptReconnect()
    }
  }

  private attemptReconnect(): void {
    if (this.reconnectAttempts < this.maxReconnectAttempts) {
      this.reconnectAttempts++
      const delay = Math.pow(2, this.reconnectAttempts) * 1000 // Exponential backoff

      setTimeout(() => {
        console.log(`Attempting to reconnect WebSocket (attempt ${this.reconnectAttempts})`)
        this.connectWebSocket()
      }, delay)
    } else {
      console.error('Max WebSocket reconnection attempts reached')
    }
  }
}

export class TemplateCreationBridge implements TemplateCreationBridgeInterface {
  private templateRegistry: Map<number, ComponentGrapeJSMetadata> = new Map()
  private previewImageCache: Map<string, string> = new Map()
  private categoryManager: TemplateCategoryManager = new TemplateCategoryManager()
  private searchIndex: TemplateSearchIndex = new TemplateSearchIndex()
  private usageTracker: TemplateUsageTracker = new TemplateUsageTracker()
  private documentationGenerator: TemplateDocumentationGenerator = new TemplateDocumentationGenerator()
  private syncManager: RealTimeSyncManager = new RealTimeSyncManager()

  /**
   * Convert a Template to GrapeJS block format
   */
  convertToGrapeJSBlock(template: Template): GrapeJSBlockMetadata {
    const metadata = this.getTemplateMetadata(template)

    return {
      id: `template-${template.id}`,
      label: template.name,
      category: this.mapCategoryToGrapeJS(template.category),
      media: metadata.previewImage || this.getDefaultPreviewImage(template.category),
      content: metadata.componentDefinition,
      attributes: {
        'data-template-id': template.id,
        'data-template-type': template.category,
        'data-template-category': template.category,
        'data-tenant-id': template.tenantId
      },
      activate: true,
      select: true
    }
  }

  /**
   * Convert GrapeJS serialization data back to Template format
   */
  convertFromGrapeJSData(data: GrapeJSSerializationData): Template[] {
    const templates: Template[] = []

    // Parse templates from GrapeJS data
    data.components.forEach(grapeComponent => {
      if (grapeComponent.attributes?.['data-template-id']) {
        const templateId = parseInt(grapeComponent.attributes['data-template-id'])
        const originalTemplate = this.templateRegistry.get(templateId)

        if (originalTemplate) {
          // Reconstruct template with updated configuration
          const updatedTemplate = this.reconstructTemplate(grapeComponent, originalTemplate)
          if (updatedTemplate) {
            templates.push(updatedTemplate)
          }
        }
      }
    })

    return templates
  }

  /**
   * Synchronize template updates between Template System and GrapeJS
   */
  async syncTemplateUpdates(templateId: number): Promise<void> {
    try {
      // This would typically fetch the latest template data from the API
      const response = await fetch(`/api/templates/${templateId}`)
      const updatedTemplate: Template = await response.json()

      // Update the template registry
      const metadata = this.generateTemplateMetadata(updatedTemplate)
      this.templateRegistry.set(templateId, metadata)

      // Trigger GrapeJS block update if editor is available
      if (typeof window !== 'undefined' && (window as any).grapesjsEditor) {
        const editor = (window as any).grapesjsEditor
        const blockManager = editor.BlockManager

        // Update the block definition
        const blockId = `template-${templateId}`
        const existingBlock = blockManager.get(blockId)

        if (existingBlock) {
          const newBlockData = this.convertToGrapeJSBlock(updatedTemplate)
          existingBlock.set(newBlockData)
        }
      }
    } catch (error) {
      console.error('Failed to sync template updates:', error)
      throw error
    }
  }

  /**
   * Generate preview image for template
   */
  async generatePreviewImage(template: Template): Promise<string> {
    // Check cache first
    const cacheKey = `${template.id}-${template.version}`
    if (this.previewImageCache.has(cacheKey)) {
      return this.previewImageCache.get(cacheKey)!
    }

    try {
      // Generate preview using headless browser or canvas
      const previewUrl = await this.renderTemplatePreview(template)

      // Cache the result
      this.previewImageCache.set(cacheKey, previewUrl)

      return previewUrl
    } catch (error) {
      console.error('Failed to generate preview image:', error)
      return this.getDefaultPreviewImage(template.category)
    }
  }

  /**
   * Validate template compatibility with GrapeJS
   */
  validateGrapeJSCompatibility(template: Template): { valid: boolean; errors: string[] } {
    const errors: string[] = []

    // Check required fields
    if (!template.name || template.name.trim() === '') {
      errors.push('Template name is required')
    }

    if (!template.category) {
      errors.push('Template category is required')
    }

    if (!template.structure) {
      errors.push('Template structure is required')
    }

    // Validate category-specific requirements
    switch (template.category) {
      case 'landing':
        errors.push(...this.validateLandingTemplate(template))
        break
      case 'homepage':
        errors.push(...this.validateHomepageTemplate(template))
        break
      case 'form':
        errors.push(...this.validateFormTemplate(template))
        break
      case 'email':
        errors.push(...this.validateEmailTemplate(template))
        break
      case 'social':
        errors.push(...this.validateSocialTemplate(template))
        break
    }

    return {
      valid: errors.length === 0,
      errors
    }
  }

  /**
   * Register template in the bridge registry
   */
  registerTemplate(template: Template): void {
    const metadata = this.generateTemplateMetadata(template)
    this.templateRegistry.set(template.id, metadata)
  }

  /**
   * Get all registered templates
   */
  getRegisteredTemplates(): ComponentGrapeJSMetadata[] {
    return Array.from(this.templateRegistry.values())
  }

  /**
   * Clear template registry
   */
  clearRegistry(): void {
    this.templateRegistry.clear()
    this.previewImageCache.clear()
  }

  // Enhanced methods for template management

  /**
   * Initialize the bridge with all supporting services
   */
  initialize(): void {
    this.categoryManager.initializeCategories()
    this.syncManager.initialize()

    // Set up real-time sync event handlers
    this.syncManager.onTemplateUpdate((event) => {
      this.handleTemplateUpdate(event)
    })

    this.syncManager.onTemplateCreated((event) => {
      this.handleTemplateCreated(event)
    })

    this.syncManager.onTemplateDeleted((event) => {
      this.handleTemplateDeleted(event)
    })
  }

  /**
   * Get organized categories for GrapeJS Block Manager
   */
  getGrapeJSCategories(): any[] {
    return this.categoryManager.getGrapeJSBlockManagerConfig()
  }

  /**
   * Search templates with advanced filtering for GrapeJS palette
   */
  searchTemplates(query: string, filters?: {
    category?: string
    tags?: string[]
    audienceType?: string
    campaignType?: string
  }): TemplateSearchResult[] {
    return this.searchIndex.search(query, filters)
  }

  /**
   * Get templates by category for GrapeJS organization
   */
  getTemplatesByCategory(category: string): Template[] {
    return this.searchIndex.getTemplatesByCategory(category)
  }

  /**
   * Track template usage for analytics
   */
  trackTemplateUsage(templateId: number, context: string = 'grapeJS'): void {
    this.usageTracker.trackTemplateUsage(templateId, context)
  }

  /**
   * Track template rating for quality metrics
   */
  trackTemplateRating(templateId: number, rating: number): void {
    this.usageTracker.trackTemplateRating(templateId, rating)
  }

  /**
   * Track template configuration usage patterns
   */
  trackTemplateConfiguration(templateId: number, config: any): void {
    this.usageTracker.trackTemplateConfiguration(templateId, config)
  }

  /**
   * Get usage statistics for a specific template
   */
  getTemplateUsageStats(templateId: number): TemplateUsageStats | undefined {
    return this.usageTracker.getTemplateStats(templateId)
  }

  /**
   * Get most used templates for GrapeJS recommendations
   */
  getMostUsedTemplates(limit: number = 10): TemplateUsageStats[] {
    return this.usageTracker.getMostUsedTemplates(limit)
  }

  /**
   * Get recently used templates for GrapeJS quick access
   */
  getRecentlyUsedTemplates(limit: number = 10): TemplateUsageStats[] {
    return this.usageTracker.getRecentlyUsedTemplates(limit)
  }

  /**
   * Get trending templates for GrapeJS suggestions
   */
  getTrendingTemplates(limit: number = 10): TemplateUsageStats[] {
    return this.usageTracker.getTrendingTemplates(limit)
  }

  /**
   * Get comprehensive analytics data for GrapeJS dashboard
   */
  getAnalyticsData(): {
    totalTemplates: number
    totalUsage: number
    averageRating: number
    mostUsedCategory: string
    usageTrend: Array<{ date: string; count: number }>
  } {
    return this.usageTracker.getAnalyticsData()
  }

  /**
   * Generate comprehensive documentation for a template
   */
  generateTemplateDocumentation(template: Template): TemplateDocumentation {
    return this.documentationGenerator.generateDocumentation(template)
  }

  /**
   * Generate tooltip text for GrapeJS interface
   */
  generateTemplateTooltip(template: Template): string {
    return this.documentationGenerator.generateTooltip(template)
  }

  /**
   * Generate property tooltip for GrapeJS trait panels
   */
  generatePropertyTooltip(property: TemplateProperty): string {
    return this.documentationGenerator.generatePropertyTooltip(property)
  }

  /**
   * Toggle category collapse state in GrapeJS Block Manager
   */
  toggleCategoryCollapse(categoryId: string): void {
    this.categoryManager.toggleCategoryCollapse(categoryId)
  }

  /**
   * Reorder categories in GrapeJS Block Manager
   */
  reorderCategories(newOrder: string[]): void {
    this.categoryManager.reorderCategories(newOrder)
  }

  /**
   * Get all available tags for filtering
   */
  getAllTags(): string[] {
    return this.searchIndex.getAllTags()
  }

  /**
   * Get templates by tag for advanced filtering
   */
  getTemplatesByTag(tag: string): Template[] {
    return this.searchIndex.getTemplatesByTag(tag)
  }

  /**
   * Enhanced register template with full indexing and categorization
   */
  registerTemplateEnhanced(template: Template): void {
    // Register in the main registry
    this.registerTemplate(template)

    // Add to category manager
    this.categoryManager.addTemplateToCategory(template)

    // Index for search
    this.searchIndex.indexTemplate(template)

    // Initialize usage tracking
    if (!this.usageTracker.getTemplateStats(template.id)) {
      this.usageTracker.trackTemplateUsage(template.id, 'registration')
    }

    // Broadcast creation event
    this.syncManager.broadcastTemplateCreated(template.id, template)
  }

  /**
   * Enhanced template update with real-time sync
   */
  updateTemplateEnhanced(template: Template): void {
    // Update in registry
    this.registerTemplate(template)

    // Update in category manager
    this.categoryManager.addTemplateToCategory(template)

    // Re-index for search
    this.searchIndex.removeTemplate(template.id)
    this.searchIndex.indexTemplate(template)

    // Clear preview cache
    const cacheKeys = Array.from(this.previewImageCache.keys())
    cacheKeys.forEach(key => {
      if (key.startsWith(template.id.toString())) {
        this.previewImageCache.delete(key)
      }
    })

    // Broadcast update event
    this.syncManager.broadcastTemplateUpdate(template.id, template)
  }

  /**
   * Enhanced template removal with cleanup
   */
  removeTemplateEnhanced(templateId: number, categoryId: string): void {
    // Remove from registry
    this.templateRegistry.delete(templateId)

    // Remove from category manager
    this.categoryManager.removeTemplateFromCategory(templateId, categoryId)

    // Remove from search index
    this.searchIndex.removeTemplate(templateId)

    // Clear preview cache
    const cacheKeys = Array.from(this.previewImageCache.keys())
    cacheKeys.forEach(key => {
      if (key.startsWith(templateId.toString())) {
        this.previewImageCache.delete(key)
      }
    })

    // Broadcast deletion event
    this.syncManager.broadcastTemplateDeleted(templateId)
  }

  /**
   * Get GrapeJS-ready template data with all metadata
   */
  getGrapeJSTemplateData(templateId: number): {
    block: GrapeJSBlockMetadata
    documentation: TemplateDocumentation
    usage: TemplateUsageStats | undefined
    tooltip: string
  } | null {
    const metadata = this.templateRegistry.get(templateId)
    if (!metadata) {
      return null
    }

    const template = this.findTemplateById(templateId)
    if (!template) {
      return null
    }

    return {
      block: metadata.blockDefinition,
      documentation: this.generateTemplateDocumentation(template),
      usage: this.getTemplateUsageStats(templateId),
      tooltip: this.generateTemplateTooltip(template)
    }
  }

  /**
   * Bulk register templates for initial GrapeJS setup
   */
  bulkRegisterTemplates(templates: Template[]): void {
    templates.forEach(template => {
      this.registerTemplateEnhanced(template)
    })
  }

  // Private helper methods for real-time sync

  private handleTemplateUpdate(event: SyncEvent): void {
    if (event.data) {
      this.updateTemplateEnhanced(event.data as Template)
    }
  }

  private handleTemplateCreated(event: SyncEvent): void {
    if (event.data) {
      this.registerTemplateEnhanced(event.data as Template)
    }
  }

  private handleTemplateDeleted(event: SyncEvent): void {
    // Find the template's category before deletion
    const metadata = this.templateRegistry.get(event.templateId)
    if (metadata) {
      const template = this.findTemplateById(event.templateId)
      if (template) {
        this.removeTemplateEnhanced(event.templateId, template.category)
      }
    }
  }

  private findTemplateById(templateId: number): Template | null {
    // This would typically fetch from the API or local cache
    // For now, we'll try to reconstruct from metadata
    const metadata = this.templateRegistry.get(templateId)
    if (metadata && metadata.blockDefinition.attributes) {
      const attrs = metadata.blockDefinition.attributes
      return {
        id: templateId,
        tenantId: parseInt(attrs['data-tenant-id'] || '0'),
        name: metadata.blockDefinition.label,
        slug: metadata.blockDefinition.id,
        category: attrs['data-template-category'] as TemplateCategory,
        audienceType: 'general',
        campaignType: 'marketing',
        description: metadata.documentation?.description || '',
        structure: {},
        defaultConfig: {},
        performanceMetrics: {},
        version: 1,
        isActive: true,
        isPremium: false,
        usageCount: 0,
        tags: [],
        createdAt: new Date().toISOString(),
        updatedAt: new Date().toISOString()
      }
    }
    return null
  }

  // Private methods

  private getTemplateMetadata(template: Template): ComponentGrapeJSMetadata {
    if (this.templateRegistry.has(template.id)) {
      return this.templateRegistry.get(template.id)!
    }

    const metadata = this.generateTemplateMetadata(template)
    this.templateRegistry.set(template.id, metadata)
    return metadata
  }

  private generateTemplateMetadata(template: Template): ComponentGrapeJSMetadata {
    const componentDefinition = this.createTemplateDefinition(template)
    const blockDefinition = this.convertToGrapeJSBlock(template)

    return {
      blockDefinition,
      componentDefinition,
      previewImage: this.getDefaultPreviewImage(template.category),
      category: this.mapCategoryToGrapeJS(template.category),
      tags: this.generateTemplateTags(template),
      usageCount: template.usageCount || 0,
      documentation: {
        description: template.description || '',
        examples: [],
        properties: this.extractConfigProperties(template.defaultConfig)
      }
    }
  }

  private createTemplateDefinition(template: Template): GrapeJSComponentDefinition {
    const baseDefinition: GrapeJSComponentDefinition = {
      type: `template-${template.category}`,
      tagName: 'div',
      attributes: {
        'data-template-id': template.id,
        'data-template-type': template.category,
        'data-template-category': template.category,
        class: `template-${template.category} template-${template.audienceType}`
      },
      traits: this.generateTraits(template),
      style: this.generateDefaultStyles(template),
      droppable: false,
      draggable: true,
      copyable: true,
      removable: true,
      badgable: true,
      stylable: true,
      highlightable: true,
      selectable: true,
      hoverable: true,
      layerable: true
    }

    // Add category-specific content
    baseDefinition.components = this.generateTemplateContent(template)

    return baseDefinition
  }

  private generateTraits(template: Template): GrapeJSTrait[] {
    const commonTraits: GrapeJSTrait[] = [
      {
        type: 'text',
        name: 'id',
        label: 'ID',
        placeholder: 'Template ID'
      },
      {
        type: 'select',
        name: 'class',
        label: 'CSS Classes',
        options: this.getCSSClassOptions(template.category)
      }
    ]

    // Add category-specific traits
    const categoryTraits = this.generateCategoryTraits(template)

    return [...commonTraits, ...categoryTraits]
  }

  private generateCategoryTraits(template: Template): GrapeJSTrait[] {
    switch (template.category) {
      case 'landing':
        return this.generateLandingTraits(template)
      case 'homepage':
        return this.generateHomepageTraits(template)
      case 'form':
        return this.generateFormTraits(template)
      case 'email':
        return this.generateEmailTraits(template)
      case 'social':
        return this.generateSocialTraits(template)
      default:
        return []
    }
  }

  private generateLandingTraits(template: Template): GrapeJSTrait[] {
    return [
      {
        type: 'text',
        name: 'headline',
        label: 'Headline',
        default: template.structure?.headline || '',
        changeProp: true
      },
      {
        type: 'text',
        name: 'subheading',
        label: 'Subheading',
        default: template.structure?.subheading || '',
        changeProp: true
      },
      {
        type: 'select',
        name: 'audienceType',
        label: 'Audience Type',
        options: [
          { id: 'individual', name: 'Individual' },
          { id: 'institution', name: 'Institution' },
          { id: 'employer', name: 'Employer' },
          { id: 'general', name: 'General' }
        ],
        default: template.audienceType,
        changeProp: true
      }
    ]
  }

  private generateHomepageTraits(template: Template): GrapeJSTrait[] {
    return [
      {
        type: 'select',
        name: 'layout',
        label: 'Layout',
        options: [
          { id: 'single-column', name: 'Single Column' },
          { id: 'multi-column', name: 'Multi Column' },
          { id: 'grid', name: 'Grid' }
        ],
        default: template.structure?.layout || 'single-column',
        changeProp: true
      }
    ]
  }

  private generateFormTraits(template: Template): GrapeJSTrait[] {
    return [
      {
        type: 'text',
        name: 'title',
        label: 'Form Title',
        default: template.structure?.title || '',
        changeProp: true
      },
      {
        type: 'select',
        name: 'layout',
        label: 'Layout',
        options: [
          { id: 'single-column', name: 'Single Column' },
          { id: 'two-column', name: 'Two Column' },
          { id: 'grid', name: 'Grid' }
        ],
        default: template.structure?.layout || 'single-column',
        changeProp: true
      }
    ]
  }

  private generateEmailTraits(template: Template): GrapeJSTrait[] {
    return [
      {
        type: 'text',
        name: 'subject',
        label: 'Subject Line',
        default: template.structure?.subject || '',
        changeProp: true
      },
      {
        type: 'select',
        name: 'layout',
        label: 'Layout',
        options: [
          { id: 'single-column', name: 'Single Column' },
          { id: 'two-column', name: 'Two Column' },
          { id: 'hero', name: 'Hero Layout' }
        ],
        default: template.structure?.layout || 'single-column',
        changeProp: true
      }
    ]
  }

  private generateSocialTraits(template: Template): GrapeJSTrait[] {
    return [
      {
        type: 'select',
        name: 'platform',
        label: 'Social Platform',
        options: [
          { id: 'facebook', name: 'Facebook' },
          { id: 'twitter', name: 'Twitter' },
          { id: 'instagram', name: 'Instagram' },
          { id: 'linkedin', name: 'LinkedIn' }
        ],
        default: template.structure?.platform || 'facebook',
        changeProp: true
      },
      {
        type: 'select',
        name: 'aspectRatio',
        label: 'Aspect Ratio',
        options: [
          { id: 'square', name: 'Square (1:1)' },
          { id: 'portrait', name: 'Portrait (4:5)' },
          { id: 'landscape', name: 'Landscape (16:9)' }
        ],
        default: template.structure?.aspectRatio || 'square',
        changeProp: true
      }
    ]
  }

  private generateTemplateContent(template: Template): string {
    // Generate the actual HTML content for the template
    // This would typically render the Vue template to HTML
    return `<div class="template-placeholder">
      <h2>${template.name}</h2>
      <p>${template.description || 'Template content will be rendered here'}</p>
    </div>`
  }

  private generateDefaultStyles(template: Template): Record<string, any> {
    const baseStyles = {
      padding: '20px',
      margin: '10px 0',
      border: '1px dashed #ccc',
      'min-height': '200px',
      position: 'relative'
    }

    // Add category-specific styles
    switch (template.category) {
      case 'landing':
        return {
          ...baseStyles,
          'min-height': '600px',
          'background-color': '#f8f9fa',
          'display': 'flex',
          'align-items': 'center',
          'justify-content': 'center'
        }
      case 'homepage':
        return {
          ...baseStyles,
          'background-color': '#ffffff',
          'min-height': '800px'
        }
      case 'form':
        return {
          ...baseStyles,
          'background-color': '#ffffff',
          'border-radius': '8px',
          'box-shadow': '0 2px 4px rgba(0,0,0,0.1)',
          'max-width': '600px',
          'margin': '20px auto'
        }
      case 'email':
        return {
          ...baseStyles,
          'background-color': '#ffffff',
          'max-width': '600px',
          'margin': '0 auto',
          'font-family': 'Arial, sans-serif'
        }
      case 'social':
        return {
          ...baseStyles,
          'background-color': '#ffffff',
          'aspect-ratio': '1/1',
          'max-width': '400px',
          'margin': '20px auto'
        }
      default:
        return baseStyles
    }
  }

  private mapCategoryToGrapeJS(category: TemplateCategory): string {
    const categoryMap: Record<TemplateCategory, string> = {
      landing: 'Landing Pages',
      homepage: 'Home Pages',
      form: 'Forms',
      email: 'Email Templates',
      social: 'Social Media'
    }

    return categoryMap[category] || 'Templates'
  }

  private getDefaultPreviewImage(category: TemplateCategory): string {
    // Return base64 encoded placeholder images or URLs
    const placeholders: Record<TemplateCategory, string> = {
      landing: '/images/template-previews/landing-placeholder.svg',
      homepage: '/images/template-previews/homepage-placeholder.svg',
      form: '/images/template-previews/form-placeholder.svg',
      email: '/images/template-previews/email-placeholder.svg',
      social: '/images/template-previews/social-placeholder.svg'
    }

    return placeholders[category] || '/images/template-previews/default-placeholder.svg'
  }

  private generateTemplateTags(template: Template): string[] {
    const tags: string[] = [template.category, template.audienceType, template.campaignType]

    if (template.tags) {
      tags.push(...template.tags)
    }

    return tags.map(tag => tag.toLowerCase())
  }

  private extractConfigProperties(config: any): Record<string, string> {
    const properties: Record<string, string> = {}

    Object.keys(config).forEach(key => {
      const value = config[key]
      if (typeof value === 'string' || typeof value === 'number' || typeof value === 'boolean') {
        properties[key] = String(value)
      }
    })

    return properties
  }

  private getCSSClassOptions(category: TemplateCategory): Array<{ id: string; name: string }> {
    const commonClasses = [
      { id: 'mb-4', name: 'Margin Bottom 4' },
      { id: 'mb-8', name: 'Margin Bottom 8' },
      { id: 'p-4', name: 'Padding 4' },
      { id: 'p-8', name: 'Padding 8' },
      { id: 'rounded', name: 'Rounded' },
      { id: 'shadow', name: 'Shadow' }
    ]

    // Add category-specific classes
    const categoryClasses: Record<TemplateCategory, Array<{ id: string; name: string }>> = {
      landing: [
        { id: 'landing-hero', name: 'Hero Layout' },
        { id: 'landing-centered', name: 'Centered Content' },
        { id: 'landing-fullscreen', name: 'Full Screen' }
      ],
      homepage: [
        { id: 'homepage-grid', name: 'Grid Layout' },
        { id: 'homepage-sections', name: 'Sectioned Layout' }
      ],
      form: [
        { id: 'form-compact', name: 'Compact Form' },
        { id: 'form-wide', name: 'Wide Form' },
        { id: 'form-card', name: 'Card Style' }
      ],
      email: [
        { id: 'email-responsive', name: 'Responsive Email' },
        { id: 'email-newsletter', name: 'Newsletter Style' }
      ],
      social: [
        { id: 'social-square', name: 'Square Format' },
        { id: 'social-story', name: 'Story Format' },
        { id: 'social-post', name: 'Post Format' }
      ]
    }

    return [...commonClasses, ...(categoryClasses[category] || [])]
  }

  private async renderTemplatePreview(template: Template): Promise<string> {
    // This would typically use a headless browser or server-side rendering
    // to generate a preview image of the template

    // For now, return a placeholder
    return this.getDefaultPreviewImage(template.category)
  }

  private reconstructTemplate(grapeComponent: any, originalMetadata: ComponentGrapeJSMetadata): Template | null {
    try {
      // Extract updated configuration from GrapeJS component
      const updatedConfig = this.extractConfigFromGrapeJSComponent(grapeComponent)

      // Create updated template
      const updatedTemplate: Template = {
        id: parseInt(grapeComponent.attributes['data-template-id']),
        tenantId: parseInt(grapeComponent.attributes['data-tenant-id']),
        name: originalMetadata.blockDefinition.label,
        slug: originalMetadata.blockDefinition.id,
        category: grapeComponent.attributes['data-template-category'] as TemplateCategory,
        audienceType: 'general',
        campaignType: 'marketing',
        description: originalMetadata.documentation?.description || '',
        structure: updatedConfig,
        defaultConfig: updatedConfig,
        performanceMetrics: {},
        version: 1,
        isActive: true,
        isPremium: false,
        usageCount: 0,
        tags: [],
        createdAt: new Date().toISOString(),
        updatedAt: new Date().toISOString()
      }

      return updatedTemplate
    } catch (error) {
      console.error('Failed to reconstruct template:', error)
      return null
    }
  }

  private extractConfigFromGrapeJSComponent(grapeComponent: any): any {
    // Extract configuration from GrapeJS component attributes and traits
    const config: any = {}

    // Extract from attributes
    if (grapeComponent.attributes) {
      Object.keys(grapeComponent.attributes).forEach(key => {
        if (!key.startsWith('data-') && key !== 'class' && key !== 'id') {
          config[key] = grapeComponent.attributes[key]
        }
      })
    }

    // Extract from traits (if available)
    if (grapeComponent.traits) {
      grapeComponent.traits.forEach((trait: any) => {
        if (trait.changeProp && trait.value !== undefined) {
          config[trait.name] = trait.value
        }
      })
    }

    return config
  }

  // Validation methods

  private validateLandingTemplate(template: Template): string[] {
    const errors: string[] = []

    if (!template.structure) {
      errors.push('Landing template must have a structure defined')
    }

    if (!template.audienceType) {
      errors.push('Landing template must have an audience type')
    }

    return errors
  }

  private validateHomepageTemplate(template: Template): string[] {
    const errors: string[] = []

    if (!template.structure) {
      errors.push('Homepage template must have a structure defined')
    }

    return errors
  }

  private validateFormTemplate(template: Template): string[] {
    const errors: string[] = []

    if (!template.structure) {
      errors.push('Form template must have a structure defined')
    }

    return errors
  }

  private validateEmailTemplate(template: Template): string[] {
    const errors: string[] = []

    if (!template.structure) {
      errors.push('Email template must have a structure defined')
    }

    return errors
  }

  private validateSocialTemplate(template: Template): string[] {
    const errors: string[] = []

    if (!template.structure) {
      errors.push('Social template must have a structure defined')
    }

    return errors
  }
}

// Export singleton instance
export const templateCreationBridge = new TemplateCreationBridge()