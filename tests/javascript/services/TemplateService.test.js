import { describe, it, expect, beforeEach, vi } from 'vitest'
import { templateService } from '@/services/TemplateService'

// Mock httpService
vi.mock('@/services/httpService', () => ({
  httpService: {
    get: vi.fn(),
    post: vi.fn(),
    patch: vi.fn(),
    delete: vi.fn()
  }
}))

// Import the mocked httpService
import { httpService } from '@/services/httpService'

describe('TemplateService', () => {
  let mockGet, mockPost

  beforeEach(() => {
    mockGet = httpService.get.mockClear()
    mockPost = httpService.post.mockClear()
    httpService.get.mockImplementation(() => {
      return Promise.resolve({
        data: ['mocked', 'response'],
        status: 200
      })
    })
    httpService.post.mockImplementation(() => {
      return Promise.resolve({
        data: ['mocked', 'response'],
        status: 200
      })
    })
  })

  describe('fetchTemplates', () => {
    it('makes correct API call with filters', async () => {
      const params = {
        filters: {
          category: ['landing'],
          searchQuery: 'test'
        },
        page: 1,
        perPage: 12,
        sortBy: 'name',
        sortOrder: 'asc'
      }

      await templateService.fetchTemplates(params)

      expect(mockGet).toHaveBeenCalledWith(
        '/api/templates?page=1&per_page=12&category=landing&search=test&sort_by=name&sort_order=asc',
        undefined
      )
    })

    it('handles empty filters', async () => {
      const params = {
        filters: {},
        page: 1,
        perPage: 10,
        sortBy: 'created_at',
        sortOrder: 'desc'
      }

      await templateService.fetchTemplates(params)

      expect(mockGet).toHaveBeenCalledWith(
        '/api/templates?page=1&per_page=10&sort_by=created_at&sort_order=desc',
        undefined
      )
    })

    it('returns template collection', async () => {
      const mockResponse = {
        data: [
          { id: 1, name: 'Template 1' },
          { id: 2, name: 'Template 2' }
        ],
        total: 25,
        page: 1,
        perPage: 10,
        lastPage: 3
      }

      mockGet.mockResolvedValue({ data: mockResponse })

      const result = await templateService.fetchTemplates({
        filters: {},
        page: 1,
        perPage: 10,
        sortBy: 'name',
        sortOrder: 'asc'
      })

      expect(result).toEqual(mockResponse)
    })
  })

  describe('fetchTemplate', () => {
    it('fetches single template by ID', async () => {
      const templateId = 42

      await templateService.fetchTemplate(templateId)

      expect(mockGet).toHaveBeenCalledWith(`/api/templates/${templateId}`)
    })

    it('returns template data', async () => {
      const mockTemplate = {
        id: 42,
        name: 'Test Template',
        description: 'A test template'
      }

      mockGet.mockResolvedValue({ data: mockTemplate })

      const result = await templateService.fetchTemplate(42)

      expect(result).toEqual(mockTemplate)
    })
  })

  describe('searchTemplates', () => {
    it('searches templates with query', async () => {
      const query = 'landing page'
      const filters = { category: ['landing'] }

      await templateService.searchTemplates(query, filters)

      expect(mockGet).toHaveBeenCalledWith(
        '/api/templates/search?search=landing%20page&category=landing&search_only=true'
      )
    })

    it('handles search without filters', async () => {
      const query = 'simple search'

      await templateService.searchTemplates(query)

      expect(mockGet).toHaveBeenCalledWith(
        '/api/templates/search?search=simple%20search&search_only=true'
      )
    })
  })

  describe('fetchCategories', () => {
    it('fetches available categories', async () => {
      await templateService.fetchCategories()

      expect(mockGet).toHaveBeenCalledWith('/api/templates/categories')
    })

    it('returns category data', async () => {
      const mockCategories = [
        { value: 'landing', label: 'Landing Pages', count: 5 },
        { value: 'form', label: 'Forms', count: 3 }
      ]

      mockGet.mockResolvedValue({ data: { data: mockCategories } })

      const result = await templateService.fetchCategories()

      expect(result).toEqual(mockCategories)
    })
  })

  describe('fetchTags', () => {
    it('fetches available tags', async () => {
      await templateService.fetchTags()

      expect(mockGet).toHaveBeenCalledWith('/api/templates/tags')
    })

    it('returns tag data', async () => {
      const mockTags = [
        { value: 'marketing', label: 'Marketing', count: 10 },
        { value: 'lead-gen', label: 'Lead Generation', count: 8 }
      ]

      mockGet.mockResolvedValue({ data: { data: mockTags } })

      const result = await templateService.fetchTags()

      expect(result).toEqual(mockTags)
    })
  })

  describe('updateTemplateUsage', () => {
    it('posts usage update for template', async () => {
      const templateId = 123

      await templateService.updateTemplateUsage(templateId)

      expect(mockPost).toHaveBeenCalledWith(`/api/templates/${templateId}/usage`)
    })
  })

  describe('generatePreview', () => {
    it('generates preview with config', async () => {
      const templateId = 999
      const config = {
        templateId: 999,
        viewport: 'desktop',
        showControls: false,
        interactive: false
      }

      await templateService.generatePreview(templateId, config)

      expect(mockPost).toHaveBeenCalledWith(
        `/api/templates/${templateId}/preview`,
        config
      )
    })

    it('returns preview data', async () => {
      const mockPreview = {
        html: '<div>Preview content</div>',
        css: '.preview { background: white; }'
      }

      mockPost.mockResolvedValue({ data: mockPreview })

      const result = await templateService.generatePreview(1, {
        templateId: 1,
        viewport: 'mobile',
        showControls: true,
        interactive: true
      })

      expect(result).toEqual(mockPreview)
    })
  })

  describe('toggleFavorite', () => {
    it('toggles favorite status', async () => {
      const templateId = 456
      const mockResponse = { is_favorited: true }

      mockPost.mockResolvedValue({ data: mockResponse })

      const result = await templateService.toggleFavorite(templateId)

      expect(mockPost).toHaveBeenCalledWith(`/api/templates/${templateId}/favorite`)
      expect(result).toEqual(mockResponse)
    })
  })

  describe('fetchFavoritedTemplates', () => {
    it('fetches favorited templates', async () => {
      await templateService.fetchFavoritedTemplates()

      expect(mockGet).toHaveBeenCalledWith('/api/templates/favorites')
    })

    it('returns favorited templates', async () => {
      const mockFavorites = [
        { id: 1, name: 'Favorite Template 1' },
        { id: 2, name: 'Favorite Template 2' }
      ]

      mockGet.mockResolvedValue({ data: { data: mockFavorites } })

      const result = await templateService.fetchFavoritedTemplates()

      expect(result).toEqual(mockFavorites)
    })
  })

  describe('fetchRecentlyUsed', () => {
    it('fetches recently used templates', async () => {
      const limit = 5

      await templateService.fetchRecentlyUsed(limit)

      expect(mockGet).toHaveBeenCalledWith('/api/templates/recent?limit=5')
    })

    it('uses default limit of 10', async () => {
      await templateService.fetchRecentlyUsed()

      expect(mockGet).toHaveBeenCalledWith('/api/templates/recent?limit=10')
    })

    it('returns recent templates', async () => {
      const mockRecent = [
        { id: 1, name: 'Recent Template 1' },
        { id: 2, name: 'Recent Template 2' }
      ]

      mockGet.mockResolvedValue({ data: { data: mockRecent } })

      const result = await templateService.fetchRecentlyUsed()

      expect(result).toEqual(mockRecent)
    })
  })

  describe('validateTemplate', () => {
    it('validates template configuration', async () => {
      const templateId = 789
      const config = { title: 'Test Config' }
      const expectedValidation = { valid: true, errors: [] }

      mockPost.mockResolvedValue({ data: expectedValidation })

      const result = await templateService.validateTemplate(templateId, config)

      expect(mockPost).toHaveBeenCalledWith(
        `/api/templates/${templateId}/validate`,
        { config }
      )
      expect(result).toEqual(expectedValidation)
    })
  })

  describe('checkTemplateAccess', () => {
    it('returns true for accessible templates', async () => {
      const result = await templateService.checkTemplateAccess(123)

      expect(mockGet).toHaveBeenCalledWith('/api/templates/123/access')
      expect(result).toBe(true)
    })

    it('returns false for inaccessible templates', async () => {
      mockGet.mockRejectedValue({ status: 404 })

      const result = await templateService.checkTemplateAccess(456)

      expect(result).toBe(false)
    })

    it('handles other errors gracefully', async () => {
      mockGet.mockRejectedValue(new Error('Network error'))

      const result = await templateService.checkTemplateAccess(789)

      expect(result).toBe(false)
    })
  })

  describe('Error Handling', () => {
    it('propagates API errors', async () => {
      const errorMessage = 'API Error'
      mockGet.mockRejectedValue(new Error(errorMessage))

      await expect(templateService.fetchTemplates({
        filters: {},
        page: 1,
        perPage: 10,
        sortBy: 'name',
        sortOrder: 'asc'
      })).rejects.toThrow(errorMessage)
    })

    it('handles network errors', async () => {
      mockPost.mockRejectedValue(new Error('Network failure'))

      await expect(templateService.updateTemplateUsage(999))
        .rejects.toThrow('Network failure')
    })
  })

  describe('URL Construction', () => {
    it('properly encodes search parameters', async () => {
      const query = 'special chars & symbols'

      await templateService.searchTemplates(query)

      expect(mockGet).toHaveBeenCalledWith(
        '/api/templates/search?search=special%20chars%20%26%20symbols&search_only=true'
      )
    })

    it('handles multiple filter values', async () => {
      const filters = {
        category: ['landing', 'homepage'],
        audienceType: ['individual', 'institution']
      }

      await templateService.searchTemplates('test', filters)

      expect(mockGet).toHaveBeenCalledWith(
        '/api/templates/search?search=test&category=landing&category=homepage&audience_type=individual&audience_type=institution&search_only=true'
      )
    })
  })

  describe('Service Instance', () => {
    it('exports default instance', () => {
      expect(templateService).toBeDefined()
      expect(typeof templateService.fetchTemplates).toBe('function')
      expect(typeof templateService.fetchTemplate).toBe('function')
      expect(typeof templateService.searchTemplates).toBe('function')
    })

    it('has correct base URL', () => {
      expect(templateService.baseUrl).toBe('/api/templates')
    })
  })
})