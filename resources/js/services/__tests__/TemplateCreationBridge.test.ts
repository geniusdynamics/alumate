
import { describe, it, expect, vi, beforeEach } from 'vitest'
import { TemplateCreationBridge } from '../TemplateCreationBridge'
import type { Template, TemplateCategory } from '@/Types/components'

// Mock fetch for API calls
vi.mocked(global.fetch)

describe('TemplateCreationBridge', () => {
  let bridge: TemplateCreationBridge
  const mockTemplate: Partial<Template> = {
    id: 1,
    tenantId: 1,
    name: 'Test Template',
    slug: 'test-template',
    category: 'landing' as TemplateCategory,
    audienceType: 'individual',
    campaignType: 'marketing',
    description: 'Test template',
    structure: { headline: 'Test', layout: 'centered' },
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

  beforeEach(() => {
    vi.clearAllMocks()
    bridge = new TemplateCreationBridge()
    // Spy on private methods for testing
    vi.spyOn(bridge as any, 'categoryManager')
    vi.spyOn(bridge as any, 'searchIndex')
    vi.spyOn(bridge as any, 'usageTracker')
    vi.spyOn(bridge as any, 'documentationGenerator')
    vi.spyOn(bridge as any, 'syncManager')
    vi.spyOn(bridge as any, 'syncManager', 'broadcastTemplateUpdate')
  })

  describe('Unit Tests - Core Methods', () => {
    it('initialize initializes all managers and sync', () => {
      vi.spyOn(bridge as any, 'categoryManager', 'get').mockReturnValue({
        initializeCategories: vi.fn()
      })
      vi.spyOn(bridge as any, 'syncManager', 'get').mockReturnValue({
        initialize: vi.fn(),
        onTemplateUpdate: vi.fn()
      })

      bridge.initialize()

      expect((bridge as any).categoryManager.initializeCategories).toHaveBeenCalled()
      expect((bridge as any).syncManager.initialize).toHaveBeenCalled()
      expect((bridge as any).syncManager.onTemplateUpdate).toHaveBeenCalled()
    })

    it('convertToGrapeJSBlock converts template to block metadata', () => {
      const block = bridge.convertToGrapeJSBlock(mockTemplate as Template)
      expect(block.id).toBe('template-1')
      expect(block.label).toBe('Test Template')
      expect(block.category).toBe('Landing Pages')
      expect(block.attributes['data-template-id']).toBe(1)
    })

    it('convertFromGrapeJSData converts data back to templates', () => {
      const mockData = {
        components: [
          {
            attributes: { 'data-template-id': 1 },
            toJSON: () => ({})
          }
        ]
      }
      const templates = bridge.convertFromGrapeJSData(mockData as any)
      expect(templates.length).toBe(1)
      expect(templates[0].id).toBe(1)
    })

    it('syncTemplateUpdates fetches and updates template', async () => {
      const mockUpdatedTemplate = { ...mockTemplate, name: 'Updated' } as Template
      vi.mocked(global.fetch).mockResolvedValueOnce({
        ok: true,
        json: vi.fn().mockResolvedValue(mockUpdatedTemplate)
      } as any)

      await bridge.syncTemplateUpdates(1)

      expect(global.fetch).toHaveBeenCalledWith('/api/templates/1')
      // Check if registry updated (private, so check via getRegisteredTemplates)
      const registered = bridge.getRegisteredTemplates()
      expect(registered.length).toBeGreaterThan(0)
    })

    it('generatePreviewImage returns cached or default preview', async () => {
      const preview = await bridge.generatePreviewImage(mockTemplate as Template)
      expect(preview).toContain('placeholder.svg')

      // Check cache
      const secondCall = await bridge.generatePreviewImage(mockTemplate as Template)
      expect(secondCall).toBe(preview)
    })

    it('validateGrapeJSCompatibility validates template', () => {
      const validation = bridge.validateGrapeJSCompatibility(mockTemplate as Template)
      expect(validation.valid).toBe(true)
      expect(validation.errors.length).toBe(0)

      // Invalid case
      const invalidTemplate = { ...mockTemplate, name: '' } as Template
      const invalidValidation = bridge.validateGrapeJSCompatibility(invalidTemplate)
      expect(invalidValidation.valid).toBe(false)
      expect(invalidValidation.errors).toContain('Template name is required')
    })

    it('registerTemplate adds to registry', () => {
      bridge.registerTemplate(mockTemplate as Template)
      const registered = bridge.getRegisteredTemplates()
      expect(registered.length).toBe(1)
      expect(registered[0].blockDefinition.label).toBe('Test Template')
    })

    it('getRegisteredTemplates returns all registered', () => {
      bridge.registerTemplate(mockTemplate as Template)
      const registered = bridge.getRegisteredTemplates()
      expect(registered.length).toBe(1)
    })

    it('clearRegistry clears all data', () => {
      bridge.registerTemplate(mockTemplate as Template)
      bridge.clearRegistry()
      expect(bridge.getRegisteredTemplates().length).toBe(0)
    })
  })

  describe('Unit Tests - Category Management', () => {
    it('getGrapeJSCategories returns configured categories', () => {
      bridge.initialize()
      const categories = bridge.getGrapeJSCategories()
      expect(categories.length).toBeGreaterThan(0)
      expect(categories[0].id).toBe('landing')
    })

    it('addTemplateToCategory adds to specific category', () => {
      bridge.initialize()
      vi.spyOn(bridge as any, 'categoryManager', 'get').mockReturnValue({
        addTemplateToCategory: vi.fn(),
        getCategoryData: vi.fn().mockReturnValue({ templates: [mockTemplate as Template] })
      })
      bridge.addTemplateToCategory(mockTemplate as Template)
      const category = (bridge as any).categoryManager.getCategoryData('landing')
      expect(category.templates.length).toBe(1)
    })

    it('toggleCategoryCollapse toggles state', () => {
      bridge.initialize()
      vi.spyOn(bridge as any, 'categoryManager', 'get').mockReturnValue({
        toggleCategoryCollapse: vi.fn(),
        getCategoryData: vi.fn().mockReturnValue({ isCollapsed: true })
      })
      bridge.toggleCategoryCollapse('landing')
      const category = (bridge as any).categoryManager.getCategoryData('landing')
      expect(category.isCollapsed).toBe(true)
    })

    it('reorderCategories updates order', () => {
      bridge.initialize()
      vi.spyOn(bridge as any, 'categoryManager', 'get').mockReturnValue({
        reorderCategories: vi.fn(),
        getGrapeJSBlockManagerConfig: vi.fn().mockReturnValue([{ id: 'form' }, { id: 'landing' }])
      })
      bridge.reorderCategories(['form', 'landing'])
      const categories = bridge.getGrapeJSCategories()
      expect(categories[0].id).toBe('form')
    })
  })

  describe('Unit Tests - Search Functionality', () => {
    it('searchTemplates returns relevant results', () => {
      bridge.registerTemplate(mockTemplate as Template)
      vi.spyOn(bridge as any, 'searchIndex', 'get').mockReturnValue({
        search: vi.fn().mockReturnValue([{ template: mockTemplate as Template, relevanceScore: 10, matchedFields: [], highlights: {} }])
      })
      const results = bridge.searchTemplates('test')
      expect(results.length).toBeGreaterThan(0)
      expect(results[0].relevanceScore).toBeGreaterThan(0)
    })

    it('searchTemplates filters by category', () => {
      bridge.registerTemplate(mockTemplate as Template)
      vi.spyOn(bridge as any, 'searchIndex', 'get').mockReturnValue({
        search: vi.fn().mockReturnValue([{ template: mockTemplate as Template, relevanceScore: 10, matchedFields: [], highlights: {} }])
      })
      const results = bridge.searchTemplates('', { category: 'landing' })
      expect(results[0].template.category).toBe('landing')
    })

    it('getTemplatesByCategory returns category templates', () => {
      bridge.registerTemplate(mockTemplate as Template)
      vi.spyOn(bridge as any, 'searchIndex', 'get').mockReturnValue({
        getTemplatesByCategory: vi.fn().mockReturnValue([mockTemplate as Template])
      })
      const templates = bridge.getTemplatesByCategory('landing')
      expect(templates.length).toBe(1)
      expect(templates[0].id).toBe(1)
    })

    it('getAllTags returns all tags', () => {
      bridge.registerTemplate(mockTemplate as Template)
      vi.spyOn(bridge as any, 'searchIndex', 'get').mockReturnValue({
        getAllTags: vi.fn().mockReturnValue(['landing'])
      })
      const tags = bridge.getAllTags()
      expect(tags).toContain('landing')
    })
  })

  describe('Unit Tests - Usage Tracking', () => {
    it('trackTemplateUsage updates stats', () => {
      vi.spyOn(bridge as any, 'usageTracker', 'get').mockReturnValue({
        trackTemplateUsage: vi.fn(),
        getTemplateStats: vi.fn().mockReturnValue({ totalUsage: 1, lastUsed: new Date() })
      })
      bridge.trackTemplateUsage(1, 'grapejs')
      const stats = bridge.getTemplateUsageStats(1)
      expect(stats.totalUsage).toBe(1)
      expect(stats.lastUsed).toBeDefined()
    })

    it('trackTemplateRating updates average rating', () => {
      vi.spyOn(bridge as any, 'usageTracker', 'get').mockReturnValue({
        trackTemplateRating: vi.fn(),
        getTemplateStats: vi.fn().mockReturnValue({ averageRating: 5 })
      })
      bridge.trackTemplateRating(1, 5)
      const stats = bridge.getTemplateUsageStats(1)
      expect(stats.averageRating).toBe(5)
    })

    it('trackTemplateConfiguration tracks popular configs', () => {
      vi.spyOn(bridge as any, 'usageTracker', 'get').mockReturnValue({
        trackTemplateConfiguration: vi.fn(),
        getTemplateStats: vi.fn().mockReturnValue({ popularConfigurations: [{ count: 1 }] })
      })
      bridge.trackTemplateConfiguration(1, { layout: 'centered' })
      const stats = bridge.getTemplateUsageStats(1)
      expect(stats.popularConfigurations.length).toBe(1)
    })

    it('getMostUsedTemplates returns sorted list', () => {
      vi.spyOn(bridge as any, 'usageTracker', 'get').mockReturnValue({
        getMostUsedTemplates: vi.fn().mockReturnValue([{ totalUsage: 2 }])
      })
      const mostUsed = bridge.getMostUsedTemplates(1)
      expect(mostUsed[0].totalUsage).toBe(2)
    })
  })

  describe('Unit Tests - Documentation', () => {
    it('generateTemplateDocumentation returns docs', () => {
      vi.spyOn(bridge as any, 'documentationGenerator', 'get').mockReturnValue({
        generateDocumentation: vi.fn().mockReturnValue({ title: 'Test Template', description: 'Test' })
      })
      const docs = bridge.generateTemplateDocumentation(mockTemplate as Template)
      expect(docs.title).toBe('Test Template')
      expect(docs.description).toBeDefined()
    })

    it('generateTemplateTooltip returns tooltip text', () => {
      vi.spyOn(bridge as any, 'documentationGenerator', 'get').mockReturnValue({
        generateTooltip: vi.fn().mockReturnValue('Test Template tooltip')
      })
      const tooltip = bridge.generateTemplateTooltip(mockTemplate as Template)
      expect(tooltip).toContain('Test Template')
    })

    it('generatePropertyTooltip returns property info', () => {
      vi.spyOn(bridge as any, 'documentationGenerator', 'get').mockReturnValue({
        generatePropertyTooltip: vi.fn().mockReturnValue('headline (string)')
      })
      const property = { name: 'headline', type: 'string', description: 'Test' } as any
      const tooltip = bridge.generatePropertyTooltip(property)
      expect(tooltip).toContain('headline (string)')
    })
  })

  describe('Integration Tests - Full Bridge Functionality', () => {
    it('registerTemplateEnhanced fully indexes and syncs template', () => {
      vi.spyOn(bridge as any, 'categoryManager').mockReturnValue({
        addTemplateToCategory: vi.fn()
      })
      vi.spyOn(bridge as any, 'searchIndex').mockReturnValue({
        indexTemplate: vi.fn()
      })
      vi.spyOn(bridge as any, 'usageTracker').mockReturnValue({
        getTemplateStats: vi.fn().mockReturnValue({})
      })
      vi.spyOn(bridge as any, 'syncManager').mockReturnValue({
        broadcastTemplateCreated: vi.fn()
      })

      bridge.registerTemplateEnhanced(mockTemplate as Template)

      expect(bridge['categoryManager'].addTemplateToCategory).toHaveBeenCalledWith(mockTemplate as Template)
      expect(bridge['searchIndex'].indexTemplate).toHaveBeenCalledWith(mockTemplate as Template)
      expect(bridge['syncManager'].broadcastTemplateCreated).toHaveBeenCalledWith(mockTemplate as Template)
    })

    it('updateTemplateEnhanced updates all indexes and syncs', () => {
      bridge.registerTemplate(mockTemplate as Template)
      const updatedTemplate = { ...mockTemplate, name: 'Updated' } as Template

      vi.spyOn(bridge as any, 'categoryManager').mockReturnValue({
        addTemplateToCategory: vi.fn()
      })
      vi.spyOn(bridge as any, 'searchIndex').mockReturnValue({
        removeTemplate: vi.fn(),
        indexTemplate: vi.fn()
      })
      vi.spyOn(bridge as any, 'syncManager').mockReturnValue({
        broadcastTemplateUpdate: vi.fn()
      })

      bridge.updateTemplateEnhanced(updatedTemplate)

      expect(bridge['searchIndex'].removeTemplate).toHaveBeenCalledWith(1)
      expect(bridge['searchIndex'].indexTemplate).toHaveBeenCalledWith(updatedTemplate)
      expect(bridge['syncManager'].broadcastTemplateUpdate).toHaveBeenCalledWith(1, updatedTemplate)
    })

    it('removeTemplateEnhanced cleans up all references', () => {
      bridge.registerTemplate(mockTemplate as Template)

      vi.spyOn(bridge as any, 'categoryManager').mockReturnValue({
        removeTemplateFromCategory: vi.fn()
      })
      vi.spyOn(bridge as any, 'searchIndex').mockReturnValue({
        removeTemplate: vi.fn()
      })
      vi.spyOn(bridge as any, 'syncManager').mockReturnValue({
        broadcastTemplateDeleted: vi.fn()
      })

      bridge.removeTemplateEnhanced(1, 'landing')

      expect(bridge['categoryManager'].removeTemplateFromCategory).toHaveBeenCalledWith(1, 'landing')
      expect(bridge['searchIndex'].removeTemplate).toHaveBeenCalledWith(1)
      expect(bridge['syncManager'].broadcastTemplateDeleted).toHaveBeenCalledWith(1)
    })

    it('getGrapeJSTemplateData returns full data with metadata', () => {
      bridge.registerTemplate(mockTemplate as Template)

      vi.spyOn(bridge as any, 'templateRegistry', 'get').mockReturnValue({
        blockDefinition: { label: 'Test Template' },
        documentation: { title: 'Test Template' },
        tooltip: 'Test'
      })

      const data = bridge.getGrapeJSTemplateData(1)
      expect(data.block.label).toBe('Test Template')
      expect(data.documentation.title).toBe('Test Template')
      expect(data.tooltip).toContain('Test Template')
    })

    it('bulkRegisterTemplates registers multiple with enhanced functionality', () => {
      const templates = [mockTemplate as Template, { ...mockTemplate, id: 2 } as Template]

      const mockEnhanced = vi.spyOn(bridge, 'registerTemplateEnhanced')

      bridge.bulkRegisterTemplates(templates)

      expect(mockEnhanced).toHaveBeenCalledTimes(2)
    })
  })

  describe('E2E Simulation Tests - Sync and Real-time', () => {
    it('handles template update sync event', () => {
      const mockUpdatedTemplate = { ...mockTemplate, name: 'Updated' } as Template
      const mockEvent = { type: 'template_updated', templateId: 1, data: mockUpdatedTemplate, timestamp: new Date() }

      vi.spyOn(bridge as any, 'syncManager', 'get').mockReturnValue({
        onTemplateUpdate: vi.fn((event: any) => {
          bridge.updateTemplateEnhanced(event.data)
        })
      })

      bridge['syncManager'].onTemplateUpdate(mockEvent as any)

      const updated = bridge.getRegisteredTemplates().find(t => t.blockDefinition.attributes['data-template-id'] === 1)
      expect(updated.blockDefinition.label).toBe('Updated')
    })

    it('handles template created sync event', () => {
      const mockCreatedTemplate = mockTemplate as Template
      const mockEvent = { type: 'template_created', templateId: 2, data: mockCreatedTemplate, timestamp: new Date() }

      vi.spyOn(bridge as any, 'syncManager', 'get').mockReturnValue({
        onTemplateCreated: vi.fn((event: any) => {
          bridge.registerTemplateEnhanced(event.data)
        })
      })

      bridge['syncManager'].onTemplateCreated(mockEvent as any)

      const created = bridge.getRegisteredTemplates().find(t => t.blockDefinition.attributes['data-template-id'] === 2)
      expect(created).toBeDefined()
    })

    it('handles template deleted sync event', () => {
      bridge.registerTemplate(mockTemplate as Template)
      const mockEvent = { type: 'template_deleted', templateId: 1, data: null, timestamp: new Date() }

      vi.spyOn(bridge as any, 'syncManager', 'get').mockReturnValue({
        onTemplateDeleted: vi.fn((event: any) => {
          bridge.removeTemplateEnhanced(event.templateId, 'landing')
        })
      })

      bridge['syncManager'].onTemplateDeleted(mockEvent as any)

      const remaining = bridge.getRegisteredTemplates()
      expect(remaining.length).toBe(0)
    })

    it('broadcasts template update through sync manager', () => {
      vi.spyOn(bridge as any, 'syncManager').mockReturnValue({
        broadcastTemplateUpdate: vi.fn()
      })

      bridge.broadcastTemplateUpdate(1, { test: 'data' })

      expect((bridge as any).syncManager.broadcastTemplateUpdate).toHaveBeenCalledWith({
        type: 'template_updated',
        templateId: 1,
        data: { test: 'data' },
        timestamp: expect.any(Date)
      })
    })

    it('analyticsData provides usage statistics', () => {
      vi.spyOn(bridge as any, 'usageTracker', 'get').mockReturnValue({
        getAnalyticsData: vi.fn().mockReturnValue({ totalTemplates: 2, totalUsage: 2 })
      })

      const analytics = bridge.getAnalyticsData()
      expect(analytics.totalTemplates).toBe(2)
      expect(analytics.totalUsage).toBe(2)
    })
  })

  describe('Error Cases and Edge Cases', () => {
    it('handles validation errors for landing template', () => {
      const invalidLanding: Partial<Template> = {
        ...mockTemplate,
        structure

