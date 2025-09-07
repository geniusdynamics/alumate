/**
 * Component Library Bridge Service for GrapeJS Integration
 * 
 * This service provides the bridge between the Component Library System
 * and GrapeJS Page Builder, handling component conversion, serialization,
 * and synchronization.
 */

import type {
  Component,
  ComponentCategory,
  GrapeJSBlockMetadata,
  GrapeJSComponentDefinition,
  GrapeJSTrait,
  ComponentGrapeJSMetadata,
  GrapeJSSerializationData,
  ComponentLibraryBridgeInterface,
  HeroComponentConfig,
  FormComponentConfig,
  TestimonialComponentConfig,
  StatisticsComponentConfig,
  CTAComponentConfig,
  MediaComponentConfig
} from '@/types/components'

// Enhanced interfaces for new functionality
interface ComponentCategoryData {
  id: string
  name: string
  icon: string
  description: string
  components: Component[]
  order: number
  isCollapsed: boolean
}

interface ComponentSearchResult {
  component: Component
  relevanceScore: number
  matchedFields: string[]
  highlights: Record<string, string>
}

interface ComponentUsageStats {
  componentId: string
  totalUsage: number
  recentUsage: number
  averageRating: number
  conversionRate: number
  lastUsed: Date
  popularConfigurations: any[]
}

interface ComponentDocumentation {
  title: string
  description: string
  examples: ComponentExample[]
  properties: ComponentProperty[]
  tips: string[]
  troubleshooting: TroubleshootingItem[]
}

interface ComponentExample {
  title: string
  description: string
  config: any
  previewUrl?: string
}

interface ComponentProperty {
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
  type: 'component_updated' | 'component_deleted' | 'component_created'
  componentId: string
  data: any
  timestamp: Date
}

/**
 * Component Category Manager for GrapeJS Block Manager organization
 */
class ComponentCategoryManager {
  private categories: Map<string, ComponentCategoryData> = new Map()

  initializeCategories(): void {
    const defaultCategories: ComponentCategoryData[] = [
      {
        id: 'hero',
        name: 'Hero Sections',
        icon: '🎯',
        description: 'Compelling page headers optimized for different audiences',
        components: [],
        order: 1,
        isCollapsed: false
      },
      {
        id: 'forms',
        name: 'Forms',
        icon: '📝',
        description: 'Lead capture forms with built-in validation and CRM integration',
        components: [],
        order: 2,
        isCollapsed: false
      },
      {
        id: 'testimonials',
        name: 'Testimonials',
        icon: '💬',
        description: 'Social proof components to build trust and credibility',
        components: [],
        order: 3,
        isCollapsed: false
      },
      {
        id: 'statistics',
        name: 'Statistics',
        icon: '📊',
        description: 'Metrics and data visualization components',
        components: [],
        order: 4,
        isCollapsed: false
      },
      {
        id: 'ctas',
        name: 'Call to Actions',
        icon: '🎯',
        description: 'Conversion-optimized buttons and action elements',
        components: [],
        order: 5,
        isCollapsed: false
      },
      {
        id: 'media',
        name: 'Media',
        icon: '🎬',
        description: 'Images, videos, and interactive content components',
        components: [],
        order: 6,
        isCollapsed: false
      }
    ]

    defaultCategories.forEach(category => {
      this.categories.set(category.id, category)
    })
  }

  addComponentToCategory(component: Component): void {
    const category = this.categories.get(component.category)
    if (category) {
      const existingIndex = category.components.findIndex(c => c.id === component.id)
      if (existingIndex >= 0) {
        category.components[existingIndex] = component
      } else {
        category.components.push(component)
      }
    }
  }

  removeComponentFromCategory(componentId: string, categoryId: string): void {
    const category = this.categories.get(categoryId)
    if (category) {
      category.components = category.components.filter(c => c.id !== componentId)
    }
  }

  getCategoryData(categoryId: string): ComponentCategoryData | undefined {
    return this.categories.get(categoryId)
  }

  getAllCategories(): ComponentCategoryData[] {
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
 * Component Search Index for GrapeJS palette filtering
 */
class ComponentSearchIndex {
  private searchIndex: Map<string, Component[]> = new Map()
  private tagIndex: Map<string, Component[]> = new Map()
  private categoryIndex: Map<string, Component[]> = new Map()

  indexComponent(component: Component): void {
    // Index by name and description
    const searchTerms = [
      component.name.toLowerCase(),
      ...(component.description?.toLowerCase().split(' ') || []),
      component.category.toLowerCase(),
      component.type.toLowerCase()
    ]

    searchTerms.forEach(term => {
      if (!this.searchIndex.has(term)) {
        this.searchIndex.set(term, [])
      }
      const components = this.searchIndex.get(term)!
      if (!components.find(c => c.id === component.id)) {
        components.push(component)
      }
    })

    // Index by category
    if (!this.categoryIndex.has(component.category)) {
      this.categoryIndex.set(component.category, [])
    }
    const categoryComponents = this.categoryIndex.get(component.category)!
    const existingIndex = categoryComponents.findIndex(c => c.id === component.id)
    if (existingIndex >= 0) {
      categoryComponents[existingIndex] = component
    } else {
      categoryComponents.push(component)
    }

    // Index by tags
    const tags = this.extractTags(component)
    tags.forEach(tag => {
      if (!this.tagIndex.has(tag)) {
        this.tagIndex.set(tag, [])
      }
      const tagComponents = this.tagIndex.get(tag)!
      if (!tagComponents.find(c => c.id === component.id)) {
        tagComponents.push(component)
      }
    })
  }

  removeComponent(componentId: string): void {
    // Remove from all indexes
    this.searchIndex.forEach(components => {
      const index = components.findIndex(c => c.id === componentId)
      if (index >= 0) {
        components.splice(index, 1)
      }
    })

    this.categoryIndex.forEach(components => {
      const index = components.findIndex(c => c.id === componentId)
      if (index >= 0) {
        components.splice(index, 1)
      }
    })

    this.tagIndex.forEach(components => {
      const index = components.findIndex(c => c.id === componentId)
      if (index >= 0) {
        components.splice(index, 1)
      }
    })
  }

  search(query: string, filters?: {
    category?: string
    tags?: string[]
    type?: string
  }): ComponentSearchResult[] {
    const results: ComponentSearchResult[] = []
    const queryTerms = query.toLowerCase().split(' ').filter(term => term.length > 0)
    
    if (queryTerms.length === 0 && !filters) {
      return []
    }

    // Get all components that match the search terms
    const matchedComponents = new Map<string, { component: Component; score: number; matches: string[] }>()

    queryTerms.forEach(term => {
      this.searchIndex.forEach((components, indexTerm) => {
        if (indexTerm.includes(term)) {
          components.forEach(component => {
            const existing = matchedComponents.get(component.id)
            const score = this.calculateRelevanceScore(term, indexTerm, component)
            
            if (existing) {
              existing.score += score
              existing.matches.push(indexTerm)
            } else {
              matchedComponents.set(component.id, {
                component,
                score,
                matches: [indexTerm]
              })
            }
          })
        }
      })
    })

    // Apply filters
    let filteredComponents = Array.from(matchedComponents.values())

    if (filters?.category) {
      filteredComponents = filteredComponents.filter(item => 
        item.component.category === filters.category
      )
    }

    if (filters?.type) {
      filteredComponents = filteredComponents.filter(item => 
        item.component.type === filters.type
      )
    }

    if (filters?.tags && filters.tags.length > 0) {
      filteredComponents = filteredComponents.filter(item => {
        const componentTags = this.extractTags(item.component)
        return filters.tags!.some(tag => componentTags.includes(tag))
      })
    }

    // Convert to search results and sort by relevance
    return filteredComponents
      .map(item => ({
        component: item.component,
        relevanceScore: item.score,
        matchedFields: item.matches,
        highlights: this.generateHighlights(item.component, queryTerms)
      }))
      .sort((a, b) => b.relevanceScore - a.relevanceScore)
  }

  getComponentsByCategory(category: string): Component[] {
    return this.categoryIndex.get(category) || []
  }

  getComponentsByTag(tag: string): Component[] {
    return this.tagIndex.get(tag) || []
  }

  getAllTags(): string[] {
    return Array.from(this.tagIndex.keys())
  }

  private extractTags(component: Component): string[] {
    const tags = [component.category, component.type]
    
    if (component.metadata?.tags) {
      tags.push(...(component.metadata.tags as string[]))
    }

    // Add audience-specific tags for hero components
    if (component.category === 'hero') {
      const config = component.config as HeroComponentConfig
      if (config.audienceType) {
        tags.push(config.audienceType)
      }
    }

    return tags.map(tag => tag.toLowerCase())
  }

  private calculateRelevanceScore(searchTerm: string, indexTerm: string, component: Component): number {
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

    // Boost score for matches in component name
    if (component.name.toLowerCase().includes(searchTerm)) {
      score += 3
    }

    // Boost score for matches in category
    if (component.category.toLowerCase().includes(searchTerm)) {
      score += 2
    }

    return score
  }

  private generateHighlights(component: Component, queryTerms: string[]): Record<string, string> {
    const highlights: Record<string, string> = {}

    queryTerms.forEach(term => {
      if (component.name.toLowerCase().includes(term)) {
        highlights.name = this.highlightText(component.name, term)
      }
      if (component.description?.toLowerCase().includes(term)) {
        highlights.description = this.highlightText(component.description, term)
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
 * Component Usage Tracker for GrapeJS analytics
 */
class ComponentUsageTracker {
  private usageStats: Map<string, ComponentUsageStats> = new Map()
  private recentUsage: Array<{ componentId: string; timestamp: Date; context: string }> = []

  trackComponentUsage(componentId: string, context: string = 'grapeJS'): void {
    // Update usage stats
    const stats = this.usageStats.get(componentId) || {
      componentId,
      totalUsage: 0,
      recentUsage: 0,
      averageRating: 0,
      conversionRate: 0,
      lastUsed: new Date(),
      popularConfigurations: []
    }

    stats.totalUsage++
    stats.lastUsed = new Date()
    this.usageStats.set(componentId, stats)

    // Track recent usage
    this.recentUsage.push({
      componentId,
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

  trackComponentRating(componentId: string, rating: number): void {
    const stats = this.usageStats.get(componentId)
    if (stats) {
      // Simple moving average for now
      stats.averageRating = (stats.averageRating + rating) / 2
      this.usageStats.set(componentId, stats)
    }
  }

  trackComponentConfiguration(componentId: string, config: any): void {
    const stats = this.usageStats.get(componentId)
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

      this.usageStats.set(componentId, stats)
    }
  }

  getComponentStats(componentId: string): ComponentUsageStats | undefined {
    return this.usageStats.get(componentId)
  }

  getAllStats(): ComponentUsageStats[] {
    return Array.from(this.usageStats.values())
  }

  getMostUsedComponents(limit: number = 10): ComponentUsageStats[] {
    return Array.from(this.usageStats.values())
      .sort((a, b) => b.totalUsage - a.totalUsage)
      .slice(0, limit)
  }

  getRecentlyUsedComponents(limit: number = 10): ComponentUsageStats[] {
    return Array.from(this.usageStats.values())
      .sort((a, b) => b.lastUsed.getTime() - a.lastUsed.getTime())
      .slice(0, limit)
  }

  getTrendingComponents(limit: number = 10): ComponentUsageStats[] {
    return Array.from(this.usageStats.values())
      .sort((a, b) => b.recentUsage - a.recentUsage)
      .slice(0, limit)
  }

  getAnalyticsData(): {
    totalComponents: number
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
      totalComponents: stats.length,
      totalUsage,
      averageRating: averageRating || 0,
      mostUsedCategory: this.getMostUsedCategory(),
      usageTrend
    }
  }

  private updateRecentUsageCounts(): void {
    const sevenDaysAgo = new Date()
    sevenDaysAgo.setDate(sevenDaysAgo.getDate() - 7)

    this.usageStats.forEach((stats, componentId) => {
      const recentCount = this.recentUsage.filter(usage => 
        usage.componentId === componentId && usage.timestamp > sevenDaysAgo
      ).length

      stats.recentUsage = recentCount
      this.usageStats.set(componentId, stats)
    })
  }

  private hashConfig(config: any): string {
    return btoa(JSON.stringify(config)).slice(0, 16)
  }

  private getMostUsedCategory(): string {
    // This would need access to component data to determine categories
    // For now, return a placeholder
    return 'hero'
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
 * Component Documentation Generator for GrapeJS interface
 */
class ComponentDocumentationGenerator {
  generateDocumentation(component: Component): ComponentDocumentation {
    return {
      title: component.name,
      description: component.description || this.generateDescription(component),
      examples: this.generateExamples(component),
      properties: this.generateProperties(component),
      tips: this.generateTips(component),
      troubleshooting: this.generateTroubleshooting(component)
    }
  }

  generateTooltip(component: Component): string {
    const doc = this.generateDocumentation(component)
    return `${doc.title}\n\n${doc.description}\n\nClick to add to your page.`
  }

  generatePropertyTooltip(property: ComponentProperty): string {
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

  private generateDescription(component: Component): string {
    const descriptions: Record<ComponentCategory, string> = {
      hero: 'Create compelling page headers that capture attention and drive engagement. Perfect for landing pages and key conversion points.',
      forms: 'Capture leads and collect user information with built-in validation and CRM integration. Optimized for conversion rates.',
      testimonials: 'Build trust and credibility with social proof from satisfied users. Supports text, images, and video testimonials.',
      statistics: 'Showcase key metrics and achievements with animated counters and visual displays. Great for building confidence.',
      ctas: 'Drive user actions with strategically designed call-to-action elements. Includes buttons, banners, and inline links.',
      media: 'Enhance your content with images, videos, and interactive elements. Optimized for performance and accessibility.'
    }

    return descriptions[component.category] || 'A reusable component for your pages.'
  }

  private generateExamples(component: Component): ComponentExample[] {
    const examples: ComponentExample[] = []

    switch (component.category) {
      case 'hero':
        examples.push(
          {
            title: 'Individual Alumni Hero',
            description: 'Hero section targeting individual alumni with personal success messaging',
            config: {
              audienceType: 'individual',
              headline: 'Advance Your Career',
              subheading: 'Connect with opportunities that match your goals',
              layout: 'centered'
            }
          },
          {
            title: 'Institution Partnership Hero',
            description: 'Hero section for institutional partnerships',
            config: {
              audienceType: 'institution',
              headline: 'Partner With Us',
              subheading: 'Strengthen your alumni network and outcomes',
              layout: 'split'
            }
          }
        )
        break

      case 'forms':
        examples.push(
          {
            title: 'Lead Capture Form',
            description: 'Simple form for capturing basic contact information',
            config: {
              title: 'Get Started Today',
              layout: 'single-column',
              fields: [
                { type: 'text', name: 'name', label: 'Full Name', required: true },
                { type: 'email', name: 'email', label: 'Email Address', required: true }
              ]
            }
          }
        )
        break

      case 'testimonials':
        examples.push(
          {
            title: 'Carousel Testimonials',
            description: 'Rotating testimonials with smooth transitions',
            config: {
              layout: 'carousel',
              showAuthorPhoto: true,
              showRating: true,
              autoplay: true
            }
          }
        )
        break

      case 'statistics':
        examples.push(
          {
            title: 'Animated Counters',
            description: 'Eye-catching animated statistics',
            config: {
              displayType: 'counters',
              layout: 'grid',
              animationEnabled: true
            }
          }
        )
        break

      case 'ctas':
        examples.push(
          {
            title: 'Primary Action Button',
            description: 'Main conversion button with tracking',
            config: {
              type: 'button',
              buttonConfig: {
                text: 'Get Started',
                style: 'primary',
                size: 'large'
              }
            }
          }
        )
        break

      case 'media':
        examples.push(
          {
            title: 'Image Gallery',
            description: 'Responsive image gallery with lightbox',
            config: {
              type: 'image-gallery',
              layout: 'grid',
              optimization: {
                lazyLoading: true,
                webpSupport: true
              }
            }
          }
        )
        break
    }

    return examples
  }

  private generateProperties(component: Component): ComponentProperty[] {
    const properties: ComponentProperty[] = []

    // Add common properties
    properties.push(
      {
        name: 'id',
        type: 'string',
        description: 'Unique identifier for the component',
        required: false
      },
      {
        name: 'className',
        type: 'string',
        description: 'Additional CSS classes to apply',
        required: false
      }
    )

    // Add category-specific properties
    switch (component.category) {
      case 'hero':
        properties.push(
          {
            name: 'headline',
            type: 'string',
            description: 'Main headline text',
            required: true
          },
          {
            name: 'subheading',
            type: 'string',
            description: 'Supporting text below the headline',
            required: false
          },
          {
            name: 'audienceType',
            type: 'select',
            description: 'Target audience for the hero section',
            required: true,
            options: [
              { value: 'individual', label: 'Individual Alumni' },
              { value: 'institution', label: 'Institution' },
              { value: 'employer', label: 'Employer' }
            ]
          }
        )
        break

      case 'forms':
        properties.push(
          {
            name: 'title',
            type: 'string',
            description: 'Form title displayed above fields',
            required: false
          },
          {
            name: 'layout',
            type: 'select',
            description: 'Form field layout style',
            required: false,
            defaultValue: 'single-column',
            options: [
              { value: 'single-column', label: 'Single Column' },
              { value: 'two-column', label: 'Two Column' },
              { value: 'grid', label: 'Grid' }
            ]
          }
        )
        break

      case 'testimonials':
        properties.push(
          {
            name: 'layout',
            type: 'select',
            description: 'How testimonials are displayed',
            required: false,
            defaultValue: 'single',
            options: [
              { value: 'single', label: 'Single' },
              { value: 'carousel', label: 'Carousel' },
              { value: 'grid', label: 'Grid' }
            ]
          },
          {
            name: 'showAuthorPhoto',
            type: 'boolean',
            description: 'Display author profile photos',
            required: false,
            defaultValue: true
          }
        )
        break
    }

    return properties
  }

  private generateTips(component: Component): string[] {
    const tips: Record<ComponentCategory, string[]> = {
      hero: [
        'Use compelling headlines that speak directly to your audience',
        'Keep subheadings concise and benefit-focused',
        'Include a clear call-to-action button',
        'Test different audience variants for better conversion'
      ],
      forms: [
        'Keep forms short to reduce abandonment',
        'Use clear, descriptive field labels',
        'Provide real-time validation feedback',
        'Test different layouts for your audience'
      ],
      testimonials: [
        'Use testimonials from similar user types',
        'Include specific details and outcomes',
        'Mix text and video testimonials for variety',
        'Update testimonials regularly to keep content fresh'
      ],
      statistics: [
        'Use real data when possible for credibility',
        'Animate numbers to draw attention',
        'Provide context for what the numbers mean',
        'Keep the number of statistics manageable'
      ],
      ctas: [
        'Use action-oriented language',
        'Make buttons visually prominent',
        'Test different colors and text',
        'Place CTAs at natural decision points'
      ],
      media: [
        'Optimize images for web performance',
        'Provide alt text for accessibility',
        'Use consistent aspect ratios',
        'Consider mobile viewing experience'
      ]
    }

    return tips[component.category] || []
  }

  private generateTroubleshooting(component: Component): TroubleshootingItem[] {
    const troubleshooting: TroubleshootingItem[] = [
      {
        issue: 'Component not displaying correctly',
        solution: 'Check that all required properties are set and valid',
        severity: 'medium'
      },
      {
        issue: 'Styling conflicts with theme',
        solution: 'Review custom CSS classes and theme compatibility',
        severity: 'low'
      }
    ]

    switch (component.category) {
      case 'hero':
        troubleshooting.push(
          {
            issue: 'Background image not loading',
            solution: 'Verify image URL is accessible and properly formatted',
            severity: 'medium'
          },
          {
            issue: 'Text not readable over background',
            solution: 'Add overlay or adjust text colors for better contrast',
            severity: 'high'
          }
        )
        break

      case 'forms':
        troubleshooting.push(
          {
            issue: 'Form submissions not working',
            solution: 'Check form action URL and ensure proper validation',
            severity: 'high'
          },
          {
            issue: 'Validation errors not showing',
            solution: 'Verify validation rules are properly configured',
            severity: 'medium'
          }
        )
        break

      case 'media':
        troubleshooting.push(
          {
            issue: 'Images loading slowly',
            solution: 'Enable lazy loading and optimize image sizes',
            severity: 'medium'
          },
          {
            issue: 'Video not playing on mobile',
            solution: 'Check video format compatibility and autoplay settings',
            severity: 'medium'
          }
        )
        break
    }

    return troubleshooting
  }
}

/**
 * Real-time Sync Manager for component updates
 */
class RealTimeSyncManager {
  private eventListeners: Map<string, Array<(event: SyncEvent) => void>> = new Map()
  private websocket: WebSocket | null = null
  private reconnectAttempts = 0
  private maxReconnectAttempts = 5

  initialize(): void {
    this.connectWebSocket()
  }

  onComponentUpdate(callback: (event: SyncEvent) => void): void {
    this.addEventListener('component_updated', callback)
  }

  onComponentCreated(callback: (event: SyncEvent) => void): void {
    this.addEventListener('component_created', callback)
  }

  onComponentDeleted(callback: (event: SyncEvent) => void): void {
    this.addEventListener('component_deleted', callback)
  }

  broadcastComponentUpdate(componentId: string, data: any): void {
    this.broadcastEvent({
      type: 'component_updated',
      componentId,
      data,
      timestamp: new Date()
    })
  }

  broadcastComponentCreated(componentId: string, data: any): void {
    this.broadcastEvent({
      type: 'component_created',
      componentId,
      data,
      timestamp: new Date()
    })
  }

  broadcastComponentDeleted(componentId: string): void {
    this.broadcastEvent({
      type: 'component_deleted',
      componentId,
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
      const wsUrl = `${window.location.protocol === 'https:' ? 'wss:' : 'ws:'}//${window.location.host}/ws/components`
      this.websocket = new WebSocket(wsUrl)

      this.websocket.onopen = () => {
        console.log('Component sync WebSocket connected')
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
        console.log('Component sync WebSocket disconnected')
        this.attemptReconnect()
      }

      this.websocket.onerror = (error) => {
        console.error('Component sync WebSocket error:', error)
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

export class ComponentLibraryBridge implements ComponentLibraryBridgeInterface {
  private componentRegistry: Map<string, ComponentGrapeJSMetadata> = new Map()
  private previewImageCache: Map<string, string> = new Map()
  private categoryManager: ComponentCategoryManager = new ComponentCategoryManager()
  private searchIndex: ComponentSearchIndex = new ComponentSearchIndex()
  private usageTracker: ComponentUsageTracker = new ComponentUsageTracker()
  private documentationGenerator: ComponentDocumentationGenerator = new ComponentDocumentationGenerator()
  private syncManager: RealTimeSyncManager = new RealTimeSyncManager()

  /**
   * Convert a Component Library component to GrapeJS block format
   */
  convertToGrapeJSBlock(component: Component): GrapeJSBlockMetadata {
    const metadata = this.getComponentMetadata(component)
    
    return {
      id: `component-${component.id}`,
      label: component.name,
      category: this.mapCategoryToGrapeJS(component.category),
      media: metadata.previewImage || this.getDefaultPreviewImage(component.category),
      content: metadata.componentDefinition,
      attributes: {
        'data-component-id': component.id,
        'data-component-type': component.type,
        'data-component-category': component.category,
        'data-tenant-id': component.tenantId
      },
      activate: true,
      select: true
    }
  }

  /**
   * Convert GrapeJS serialization data back to Component Library format
   */
  convertFromGrapeJSData(data: GrapeJSSerializationData): Component[] {
    const components: Component[] = []
    
    // Parse components from GrapeJS data
    data.components.forEach(grapeComponent => {
      if (grapeComponent.attributes?.['data-component-id']) {
        const componentId = grapeComponent.attributes['data-component-id']
        const originalComponent = this.componentRegistry.get(componentId)
        
        if (originalComponent) {
          // Reconstruct component with updated configuration
          const updatedComponent = this.reconstructComponent(grapeComponent, originalComponent)
          if (updatedComponent) {
            components.push(updatedComponent)
          }
        }
      }
    })
    
    return components
  }

  /**
   * Synchronize component updates between Component Library and GrapeJS
   */
  async syncComponentUpdates(componentId: string): Promise<void> {
    try {
      // This would typically fetch the latest component data from the API
      const response = await fetch(`/api/components/${componentId}`)
      const updatedComponent: Component = await response.json()
      
      // Update the component registry
      const metadata = this.generateComponentMetadata(updatedComponent)
      this.componentRegistry.set(componentId, metadata)
      
      // Trigger GrapeJS block update if editor is available
      if (typeof window !== 'undefined' && (window as any).grapesjsEditor) {
        const editor = (window as any).grapesjsEditor
        const blockManager = editor.BlockManager
        
        // Update the block definition
        const blockId = `component-${componentId}`
        const existingBlock = blockManager.get(blockId)
        
        if (existingBlock) {
          const newBlockData = this.convertToGrapeJSBlock(updatedComponent)
          existingBlock.set(newBlockData)
        }
      }
    } catch (error) {
      console.error('Failed to sync component updates:', error)
      throw error
    }
  }

  /**
   * Generate preview image for component
   */
  async generatePreviewImage(component: Component): Promise<string> {
    // Check cache first
    const cacheKey = `${component.id}-${component.version}`
    if (this.previewImageCache.has(cacheKey)) {
      return this.previewImageCache.get(cacheKey)!
    }

    try {
      // Generate preview using headless browser or canvas
      const previewUrl = await this.renderComponentPreview(component)
      
      // Cache the result
      this.previewImageCache.set(cacheKey, previewUrl)
      
      return previewUrl
    } catch (error) {
      console.error('Failed to generate preview image:', error)
      return this.getDefaultPreviewImage(component.category)
    }
  }

  /**
   * Validate component compatibility with GrapeJS
   */
  validateGrapeJSCompatibility(component: Component): { valid: boolean; errors: string[] } {
    const errors: string[] = []

    // Check required fields
    if (!component.name || component.name.trim() === '') {
      errors.push('Component name is required')
    }

    if (!component.category) {
      errors.push('Component category is required')
    }

    if (!component.config) {
      errors.push('Component configuration is required')
    }

    // Validate category-specific requirements
    switch (component.category) {
      case 'hero':
        errors.push(...this.validateHeroComponent(component.config as HeroComponentConfig))
        break
      case 'forms':
        errors.push(...this.validateFormComponent(component.config as FormComponentConfig))
        break
      case 'testimonials':
        errors.push(...this.validateTestimonialComponent(component.config as TestimonialComponentConfig))
        break
      case 'statistics':
        errors.push(...this.validateStatisticsComponent(component.config as StatisticsComponentConfig))
        break
      case 'ctas':
        errors.push(...this.validateCTAComponent(component.config as CTAComponentConfig))
        break
      case 'media':
        errors.push(...this.validateMediaComponent(component.config as MediaComponentConfig))
        break
    }

    return {
      valid: errors.length === 0,
      errors
    }
  }

  /**
   * Register component in the bridge registry
   */
  registerComponent(component: Component): void {
    const metadata = this.generateComponentMetadata(component)
    this.componentRegistry.set(component.id, metadata)
  }

  /**
   * Get all registered components
   */
  getRegisteredComponents(): ComponentGrapeJSMetadata[] {
    return Array.from(this.componentRegistry.values())
  }

  /**
   * Clear component registry
   */
  clearRegistry(): void {
    this.componentRegistry.clear()
    this.previewImageCache.clear()
  }

  // Enhanced methods for task 32 requirements

  /**
   * Initialize the bridge with all supporting services
   */
  initialize(): void {
    this.categoryManager.initializeCategories()
    this.syncManager.initialize()
    
    // Set up real-time sync event handlers
    this.syncManager.onComponentUpdate((event) => {
      this.handleComponentUpdate(event)
    })
    
    this.syncManager.onComponentCreated((event) => {
      this.handleComponentCreated(event)
    })
    
    this.syncManager.onComponentDeleted((event) => {
      this.handleComponentDeleted(event)
    })
  }

  /**
   * Get organized categories for GrapeJS Block Manager
   */
  getGrapeJSCategories(): any[] {
    return this.categoryManager.getGrapeJSBlockManagerConfig()
  }

  /**
   * Search components with advanced filtering for GrapeJS palette
   */
  searchComponents(query: string, filters?: {
    category?: string
    tags?: string[]
    type?: string
  }): ComponentSearchResult[] {
    return this.searchIndex.search(query, filters)
  }

  /**
   * Get components by category for GrapeJS organization
   */
  getComponentsByCategory(category: string): Component[] {
    return this.searchIndex.getComponentsByCategory(category)
  }

  /**
   * Track component usage for analytics
   */
  trackComponentUsage(componentId: string, context: string = 'grapeJS'): void {
    this.usageTracker.trackComponentUsage(componentId, context)
  }

  /**
   * Track component rating for quality metrics
   */
  trackComponentRating(componentId: string, rating: number): void {
    this.usageTracker.trackComponentRating(componentId, rating)
  }

  /**
   * Track component configuration usage patterns
   */
  trackComponentConfiguration(componentId: string, config: any): void {
    this.usageTracker.trackComponentConfiguration(componentId, config)
  }

  /**
   * Get usage statistics for a specific component
   */
  getComponentUsageStats(componentId: string): ComponentUsageStats | undefined {
    return this.usageTracker.getComponentStats(componentId)
  }

  /**
   * Get most used components for GrapeJS recommendations
   */
  getMostUsedComponents(limit: number = 10): ComponentUsageStats[] {
    return this.usageTracker.getMostUsedComponents(limit)
  }

  /**
   * Get recently used components for GrapeJS quick access
   */
  getRecentlyUsedComponents(limit: number = 10): ComponentUsageStats[] {
    return this.usageTracker.getRecentlyUsedComponents(limit)
  }

  /**
   * Get trending components for GrapeJS suggestions
   */
  getTrendingComponents(limit: number = 10): ComponentUsageStats[] {
    return this.usageTracker.getTrendingComponents(limit)
  }

  /**
   * Get comprehensive analytics data for GrapeJS dashboard
   */
  getAnalyticsData(): {
    totalComponents: number
    totalUsage: number
    averageRating: number
    mostUsedCategory: string
    usageTrend: Array<{ date: string; count: number }>
  } {
    return this.usageTracker.getAnalyticsData()
  }

  /**
   * Generate comprehensive documentation for a component
   */
  generateComponentDocumentation(component: Component): ComponentDocumentation {
    return this.documentationGenerator.generateDocumentation(component)
  }

  /**
   * Generate tooltip text for GrapeJS interface
   */
  generateComponentTooltip(component: Component): string {
    return this.documentationGenerator.generateTooltip(component)
  }

  /**
   * Generate property tooltip for GrapeJS trait panels
   */
  generatePropertyTooltip(property: ComponentProperty): string {
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
   * Get components by tag for advanced filtering
   */
  getComponentsByTag(tag: string): Component[] {
    return this.searchIndex.getComponentsByTag(tag)
  }

  /**
   * Enhanced register component with full indexing and categorization
   */
  registerComponentEnhanced(component: Component): void {
    // Register in the main registry
    this.registerComponent(component)
    
    // Add to category manager
    this.categoryManager.addComponentToCategory(component)
    
    // Index for search
    this.searchIndex.indexComponent(component)
    
    // Initialize usage tracking
    if (!this.usageTracker.getComponentStats(component.id)) {
      this.usageTracker.trackComponentUsage(component.id, 'registration')
    }
    
    // Broadcast creation event
    this.syncManager.broadcastComponentCreated(component.id, component)
  }

  /**
   * Enhanced component update with real-time sync
   */
  updateComponentEnhanced(component: Component): void {
    // Update in registry
    this.registerComponent(component)
    
    // Update in category manager
    this.categoryManager.addComponentToCategory(component)
    
    // Re-index for search
    this.searchIndex.removeComponent(component.id)
    this.searchIndex.indexComponent(component)
    
    // Clear preview cache
    const cacheKeys = Array.from(this.previewImageCache.keys())
    cacheKeys.forEach(key => {
      if (key.startsWith(component.id)) {
        this.previewImageCache.delete(key)
      }
    })
    
    // Broadcast update event
    this.syncManager.broadcastComponentUpdate(component.id, component)
  }

  /**
   * Enhanced component removal with cleanup
   */
  removeComponentEnhanced(componentId: string, categoryId: string): void {
    // Remove from registry
    this.componentRegistry.delete(componentId)
    
    // Remove from category manager
    this.categoryManager.removeComponentFromCategory(componentId, categoryId)
    
    // Remove from search index
    this.searchIndex.removeComponent(componentId)
    
    // Clear preview cache
    const cacheKeys = Array.from(this.previewImageCache.keys())
    cacheKeys.forEach(key => {
      if (key.startsWith(componentId)) {
        this.previewImageCache.delete(key)
      }
    })
    
    // Broadcast deletion event
    this.syncManager.broadcastComponentDeleted(componentId)
  }

  /**
   * Get GrapeJS-ready component data with all metadata
   */
  getGrapeJSComponentData(componentId: string): {
    block: GrapeJSBlockMetadata
    documentation: ComponentDocumentation
    usage: ComponentUsageStats | undefined
    tooltip: string
  } | null {
    const metadata = this.componentRegistry.get(componentId)
    if (!metadata) {
      return null
    }

    const component = this.findComponentById(componentId)
    if (!component) {
      return null
    }

    return {
      block: metadata.blockDefinition,
      documentation: this.generateComponentDocumentation(component),
      usage: this.getComponentUsageStats(componentId),
      tooltip: this.generateComponentTooltip(component)
    }
  }

  /**
   * Bulk register components for initial GrapeJS setup
   */
  bulkRegisterComponents(components: Component[]): void {
    components.forEach(component => {
      this.registerComponentEnhanced(component)
    })
  }

  // Private helper methods for real-time sync

  private handleComponentUpdate(event: SyncEvent): void {
    if (event.data) {
      this.updateComponentEnhanced(event.data as Component)
    }
  }

  private handleComponentCreated(event: SyncEvent): void {
    if (event.data) {
      this.registerComponentEnhanced(event.data as Component)
    }
  }

  private handleComponentDeleted(event: SyncEvent): void {
    // Find the component's category before deletion
    const metadata = this.componentRegistry.get(event.componentId)
    if (metadata) {
      const component = this.findComponentById(event.componentId)
      if (component) {
        this.removeComponentEnhanced(event.componentId, component.category)
      }
    }
  }

  private findComponentById(componentId: string): Component | null {
    // This would typically fetch from the API or local cache
    // For now, we'll try to reconstruct from metadata
    const metadata = this.componentRegistry.get(componentId)
    if (metadata && metadata.blockDefinition.attributes) {
      const attrs = metadata.blockDefinition.attributes
      return {
        id: componentId,
        tenantId: attrs['data-tenant-id'] || '',
        name: metadata.blockDefinition.label,
        slug: metadata.blockDefinition.id,
        category: attrs['data-component-category'] as ComponentCategory,
        type: attrs['data-component-type'] || '',
        description: metadata.documentation?.description || '',
        config: {},
        metadata: {},
        version: '1.0.0',
        isActive: true,
        createdAt: new Date().toISOString(),
        updatedAt: new Date().toISOString()
      }
    }
    return null
  }

  // Private methods

  private getComponentMetadata(component: Component): ComponentGrapeJSMetadata {
    if (this.componentRegistry.has(component.id)) {
      return this.componentRegistry.get(component.id)!
    }
    
    const metadata = this.generateComponentMetadata(component)
    this.componentRegistry.set(component.id, metadata)
    return metadata
  }

  private generateComponentMetadata(component: Component): ComponentGrapeJSMetadata {
    const componentDefinition = this.createComponentDefinition(component)
    const blockDefinition = this.convertToGrapeJSBlock(component)

    return {
      blockDefinition,
      componentDefinition,
      previewImage: this.getDefaultPreviewImage(component.category),
      category: this.mapCategoryToGrapeJS(component.category),
      tags: this.generateComponentTags(component),
      usageCount: 0,
      documentation: {
        description: component.description || '',
        examples: [],
        properties: this.extractConfigProperties(component.config)
      }
    }
  }

  private createComponentDefinition(component: Component): GrapeJSComponentDefinition {
    const baseDefinition: GrapeJSComponentDefinition = {
      type: `component-${component.category}`,
      tagName: 'div',
      attributes: {
        'data-component-id': component.id,
        'data-component-type': component.type,
        'data-component-category': component.category,
        class: `component-${component.category} component-${component.type}`
      },
      traits: this.generateTraits(component),
      style: this.generateDefaultStyles(component),
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
    baseDefinition.components = this.generateComponentContent(component)

    return baseDefinition
  }

  private generateTraits(component: Component): GrapeJSTrait[] {
    const commonTraits: GrapeJSTrait[] = [
      {
        type: 'text',
        name: 'id',
        label: 'ID',
        placeholder: 'Component ID'
      },
      {
        type: 'select',
        name: 'class',
        label: 'CSS Classes',
        options: this.getCSSClassOptions(component.category)
      }
    ]

    // Add category-specific traits
    const categoryTraits = this.generateCategoryTraits(component)
    
    return [...commonTraits, ...categoryTraits]
  }

  private generateCategoryTraits(component: Component): GrapeJSTrait[] {
    switch (component.category) {
      case 'hero':
        return this.generateHeroTraits(component.config as HeroComponentConfig)
      case 'forms':
        return this.generateFormTraits(component.config as FormComponentConfig)
      case 'testimonials':
        return this.generateTestimonialTraits(component.config as TestimonialComponentConfig)
      case 'statistics':
        return this.generateStatisticsTraits(component.config as StatisticsComponentConfig)
      case 'ctas':
        return this.generateCTATraits(component.config as CTAComponentConfig)
      case 'media':
        return this.generateMediaTraits(component.config as MediaComponentConfig)
      default:
        return []
    }
  }

  private generateHeroTraits(config: HeroComponentConfig): GrapeJSTrait[] {
    return [
      {
        type: 'text',
        name: 'headline',
        label: 'Headline',
        default: config.headline,
        changeProp: true
      },
      {
        type: 'text',
        name: 'subheading',
        label: 'Subheading',
        default: config.subheading,
        changeProp: true
      },
      {
        type: 'select',
        name: 'audienceType',
        label: 'Audience Type',
        options: [
          { id: 'individual', name: 'Individual' },
          { id: 'institution', name: 'Institution' },
          { id: 'employer', name: 'Employer' }
        ],
        default: config.audienceType,
        changeProp: true
      },
      {
        type: 'select',
        name: 'layout',
        label: 'Layout',
        options: [
          { id: 'centered', name: 'Centered' },
          { id: 'left-aligned', name: 'Left Aligned' },
          { id: 'right-aligned', name: 'Right Aligned' },
          { id: 'split', name: 'Split' }
        ],
        default: config.layout,
        changeProp: true
      },
      {
        type: 'select',
        name: 'textAlignment',
        label: 'Text Alignment',
        options: [
          { id: 'left', name: 'Left' },
          { id: 'center', name: 'Center' },
          { id: 'right', name: 'Right' }
        ],
        default: config.textAlignment,
        changeProp: true
      }
    ]
  }

  private generateFormTraits(config: FormComponentConfig): GrapeJSTrait[] {
    return [
      {
        type: 'text',
        name: 'title',
        label: 'Form Title',
        default: config.title,
        changeProp: true
      },
      {
        type: 'text',
        name: 'description',
        label: 'Description',
        default: config.description,
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
        default: config.layout,
        changeProp: true
      },
      {
        type: 'select',
        name: 'theme',
        label: 'Theme',
        options: [
          { id: 'default', name: 'Default' },
          { id: 'minimal', name: 'Minimal' },
          { id: 'modern', name: 'Modern' },
          { id: 'classic', name: 'Classic' }
        ],
        default: config.theme,
        changeProp: true
      }
    ]
  }

  private generateTestimonialTraits(config: TestimonialComponentConfig): GrapeJSTrait[] {
    return [
      {
        type: 'select',
        name: 'layout',
        label: 'Layout',
        options: [
          { id: 'single', name: 'Single' },
          { id: 'carousel', name: 'Carousel' },
          { id: 'grid', name: 'Grid' },
          { id: 'masonry', name: 'Masonry' }
        ],
        default: config.layout,
        changeProp: true
      },
      {
        type: 'checkbox',
        name: 'showAuthorPhoto',
        label: 'Show Author Photo',
        default: config.showAuthorPhoto,
        changeProp: true
      },
      {
        type: 'checkbox',
        name: 'showRating',
        label: 'Show Rating',
        default: config.showRating,
        changeProp: true
      },
      {
        type: 'checkbox',
        name: 'enableFiltering',
        label: 'Enable Filtering',
        default: config.enableFiltering,
        changeProp: true
      }
    ]
  }

  private generateStatisticsTraits(config: StatisticsComponentConfig): GrapeJSTrait[] {
    return [
      {
        type: 'select',
        name: 'displayType',
        label: 'Display Type',
        options: [
          { id: 'counters', name: 'Counters' },
          { id: 'progress', name: 'Progress Bars' },
          { id: 'charts', name: 'Charts' },
          { id: 'mixed', name: 'Mixed' }
        ],
        default: config.displayType,
        changeProp: true
      },
      {
        type: 'select',
        name: 'layout',
        label: 'Layout',
        options: [
          { id: 'grid', name: 'Grid' },
          { id: 'row', name: 'Row' },
          { id: 'column', name: 'Column' }
        ],
        default: config.layout,
        changeProp: true
      },
      {
        type: 'checkbox',
        name: 'animationEnabled',
        label: 'Enable Animation',
        default: config.animation.enabled,
        changeProp: true
      }
    ]
  }

  private generateCTATraits(config: CTAComponentConfig): GrapeJSTrait[] {
    return [
      {
        type: 'select',
        name: 'type',
        label: 'CTA Type',
        options: [
          { id: 'button', name: 'Button' },
          { id: 'banner', name: 'Banner' },
          { id: 'inline-link', name: 'Inline Link' }
        ],
        default: config.type,
        changeProp: true
      },
      {
        type: 'text',
        name: 'text',
        label: 'Button Text',
        default: config.buttonConfig?.text || config.inlineLinkConfig?.text,
        changeProp: true
      },
      {
        type: 'text',
        name: 'url',
        label: 'URL',
        default: config.buttonConfig?.url || config.inlineLinkConfig?.url,
        changeProp: true
      }
    ]
  }

  private generateMediaTraits(config: MediaComponentConfig): GrapeJSTrait[] {
    return [
      {
        type: 'select',
        name: 'type',
        label: 'Media Type',
        options: [
          { id: 'image-gallery', name: 'Image Gallery' },
          { id: 'video-embed', name: 'Video Embed' },
          { id: 'interactive-demo', name: 'Interactive Demo' }
        ],
        default: config.type,
        changeProp: true
      },
      {
        type: 'select',
        name: 'layout',
        label: 'Layout',
        options: [
          { id: 'grid', name: 'Grid' },
          { id: 'masonry', name: 'Masonry' },
          { id: 'carousel', name: 'Carousel' },
          { id: 'single', name: 'Single' }
        ],
        default: config.layout,
        changeProp: true
      },
      {
        type: 'checkbox',
        name: 'lazyLoading',
        label: 'Lazy Loading',
        default: config.optimization.lazyLoading,
        changeProp: true
      }
    ]
  }

  private generateComponentContent(component: Component): string {
    // Generate the actual HTML content for the component
    // This would typically render the Vue component to HTML
    return `<div class="component-placeholder">
      <h3>${component.name}</h3>
      <p>${component.description || 'Component content will be rendered here'}</p>
    </div>`
  }

  private generateDefaultStyles(component: Component): Record<string, any> {
    const baseStyles = {
      padding: '20px',
      margin: '10px 0',
      border: '1px dashed #ccc',
      'min-height': '100px',
      position: 'relative'
    }

    // Add category-specific styles
    switch (component.category) {
      case 'hero':
        return {
          ...baseStyles,
          'min-height': '400px',
          'background-color': '#f8f9fa',
          'display': 'flex',
          'align-items': 'center',
          'justify-content': 'center'
        }
      case 'forms':
        return {
          ...baseStyles,
          'background-color': '#ffffff',
          'border-radius': '8px',
          'box-shadow': '0 2px 4px rgba(0,0,0,0.1)'
        }
      case 'testimonials':
        return {
          ...baseStyles,
          'background-color': '#f8f9fa',
          'border-radius': '8px'
        }
      case 'statistics':
        return {
          ...baseStyles,
          'text-align': 'center',
          'background-color': '#ffffff'
        }
      case 'ctas':
        return {
          ...baseStyles,
          'text-align': 'center',
          'background-color': '#e3f2fd'
        }
      case 'media':
        return {
          ...baseStyles,
          'background-color': '#fafafa',
          'text-align': 'center'
        }
      default:
        return baseStyles
    }
  }

  private mapCategoryToGrapeJS(category: ComponentCategory): string {
    const categoryMap: Record<ComponentCategory, string> = {
      hero: 'Hero Sections',
      forms: 'Forms',
      testimonials: 'Testimonials',
      statistics: 'Statistics',
      ctas: 'Call to Actions',
      media: 'Media'
    }
    
    return categoryMap[category] || 'Components'
  }

  private getDefaultPreviewImage(category: ComponentCategory): string {
    // Return base64 encoded placeholder images or URLs
    const placeholders: Record<ComponentCategory, string> = {
      hero: '/images/component-previews/hero-placeholder.svg',
      forms: '/images/component-previews/form-placeholder.svg',
      testimonials: '/images/component-previews/testimonial-placeholder.svg',
      statistics: '/images/component-previews/statistics-placeholder.svg',
      ctas: '/images/component-previews/cta-placeholder.svg',
      media: '/images/component-previews/media-placeholder.svg'
    }
    
    return placeholders[category] || '/images/component-previews/default-placeholder.svg'
  }

  private generateComponentTags(component: Component): string[] {
    const tags = [component.category, component.type]
    
    // Add audience-specific tags for hero components
    if (component.category === 'hero') {
      const config = component.config as HeroComponentConfig
      tags.push(config.audienceType)
    }
    
    // Add metadata tags if available
    if (component.metadata?.tags) {
      tags.push(...(component.metadata.tags as string[]))
    }
    
    return tags
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

  private getCSSClassOptions(category: ComponentCategory): Array<{ id: string; name: string }> {
    const commonClasses = [
      { id: 'mb-4', name: 'Margin Bottom 4' },
      { id: 'mb-8', name: 'Margin Bottom 8' },
      { id: 'p-4', name: 'Padding 4' },
      { id: 'p-8', name: 'Padding 8' },
      { id: 'rounded', name: 'Rounded' },
      { id: 'shadow', name: 'Shadow' }
    ]
    
    // Add category-specific classes
    const categoryClasses: Record<ComponentCategory, Array<{ id: string; name: string }>> = {
      hero: [
        { id: 'hero-fullscreen', name: 'Full Screen' },
        { id: 'hero-centered', name: 'Centered' },
        { id: 'hero-split', name: 'Split Layout' }
      ],
      forms: [
        { id: 'form-compact', name: 'Compact' },
        { id: 'form-wide', name: 'Wide' },
        { id: 'form-card', name: 'Card Style' }
      ],
      testimonials: [
        { id: 'testimonials-grid', name: 'Grid Layout' },
        { id: 'testimonials-carousel', name: 'Carousel' }
      ],
      statistics: [
        { id: 'stats-horizontal', name: 'Horizontal' },
        { id: 'stats-vertical', name: 'Vertical' }
      ],
      ctas: [
        { id: 'cta-primary', name: 'Primary Style' },
        { id: 'cta-secondary', name: 'Secondary Style' }
      ],
      media: [
        { id: 'media-gallery', name: 'Gallery' },
        { id: 'media-single', name: 'Single Media' }
      ]
    }
    
    return [...commonClasses, ...(categoryClasses[category] || [])]
  }

  private async renderComponentPreview(component: Component): Promise<string> {
    // This would typically use a headless browser or server-side rendering
    // to generate a preview image of the component
    
    // For now, return a placeholder
    return this.getDefaultPreviewImage(component.category)
  }

  private reconstructComponent(grapeComponent: any, originalMetadata: ComponentGrapeJSMetadata): Component | null {
    try {
      // Extract updated configuration from GrapeJS component
      const updatedConfig = this.extractConfigFromGrapeJSComponent(grapeComponent)
      
      // Create updated component
      const updatedComponent: Component = {
        id: grapeComponent.attributes['data-component-id'],
        tenantId: grapeComponent.attributes['data-tenant-id'],
        name: originalMetadata.blockDefinition.label,
        slug: originalMetadata.blockDefinition.id,
        category: grapeComponent.attributes['data-component-category'] as ComponentCategory,
        type: grapeComponent.attributes['data-component-type'],
        description: originalMetadata.documentation?.description,
        config: updatedConfig,
        metadata: {},
        version: '1.0.0',
        isActive: true,
        createdAt: new Date().toISOString(),
        updatedAt: new Date().toISOString()
      }
      
      return updatedComponent
    } catch (error) {
      console.error('Failed to reconstruct component:', error)
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

  private validateHeroComponent(config: HeroComponentConfig): string[] {
    const errors: string[] = []
    
    if (!config.headline || config.headline.trim() === '') {
      errors.push('Hero headline is required')
    }
    
    if (!config.audienceType) {
      errors.push('Hero audience type is required')
    }
    
    if (!config.ctaButtons || config.ctaButtons.length === 0) {
      errors.push('Hero must have at least one CTA button')
    }
    
    return errors
  }

  private validateFormComponent(config: FormComponentConfig): string[] {
    const errors: string[] = []
    
    if (!config.fields || config.fields.length === 0) {
      errors.push('Form must have at least one field')
    }
    
    if (!config.submission || !config.submission.action) {
      errors.push('Form submission action is required')
    }
    
    return errors
  }

  private validateTestimonialComponent(config: TestimonialComponentConfig): string[] {
    const errors: string[] = []
    
    if (!config.testimonials || config.testimonials.length === 0) {
      errors.push('Testimonial component must have at least one testimonial')
    }
    
    return errors
  }

  private validateStatisticsComponent(config: StatisticsComponentConfig): string[] {
    const errors: string[] = []
    
    if (!config.displayType) {
      errors.push('Statistics display type is required')
    }
    
    return errors
  }

  private validateCTAComponent(config: CTAComponentConfig): string[] {
    const errors: string[] = []
    
    if (!config.type) {
      errors.push('CTA type is required')
    }
    
    return errors
  }

  private validateMediaComponent(config: MediaComponentConfig): string[] {
    const errors: string[] = []
    
    if (!config.type) {
      errors.push('Media type is required')
    }
    
    if (!config.mediaAssets || config.mediaAssets.length === 0) {
      errors.push('Media component must have at least one media asset')
    }
    
    return errors
  }
}

// Export singleton instance
export const componentLibraryBridge = new ComponentLibraryBridge()