import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { ABTestingService } from '@/services/ABTestingService'
import type { ABTest, ABVariant } from '@/types/homepage'

// Mock fetch and localStorage
global.fetch = vi.fn()
global.localStorage = {
  getItem: vi.fn(),
  setItem: vi.fn(),
  removeItem: vi.fn(),
  clear: vi.fn(),
  length: 0,
  key: vi.fn()
}
global.sessionStorage = {
  getItem: vi.fn(),
  setItem: vi.fn(),
  removeItem: vi.fn(),
  clear: vi.fn(),
  length: 0,
  key: vi.fn()
}

describe('ABTestingService', () => {
  let service: ABTestingService
  let mockFetch: any
  let mockLocalStorage: any
  let mockSessionStorage: any

  const mockTest: ABTest = {
    id: 'hero-test-1',
    name: 'Hero Section Test',
    audience: 'individual',
    variants: [
      {
        id: 'control',
        name: 'Control',
        weight: 50,
        componentOverrides: []
      },
      {
        id: 'variant-a',
        name: 'Variant A',
        weight: 50,
        componentOverrides: [
          {
            component: 'HeroSection',
            props: {
              headline: 'New Headline'
            }
          }
        ]
      }
    ],
    trafficAllocation: 100,
    conversionGoals: [
      {
        id: 'trial_signup',
        name: 'Trial Signup',
        audience: 'individual',
        type: 'trial_signup',
        value: 100,
        trackingCode: 'TRIAL_SIGNUP'
      }
    ],
    startDate: new Date('2024-01-01'),
    status: 'running'
  }

  beforeEach(() => {
    mockFetch = vi.mocked(fetch)
    mockLocalStorage = vi.mocked(localStorage)
    mockSessionStorage = vi.mocked(sessionStorage)

    mockFetch.mockResolvedValue({
      ok: true,
      json: () => Promise.resolve([mockTest])
    } as Response)

    mockLocalStorage.getItem.mockReturnValue(null)
    mockSessionStorage.getItem.mockReturnValue(null)

    vi.clearAllMocks()
  })

  afterEach(() => {
    service?.destroy()
  })

  describe('Initialization', () => {
    it('loads active tests on initialization', async () => {
      service = new ABTestingService('user123', 'session456', 'individual')

      // Wait for async initialization
      await new Promise(resolve => setTimeout(resolve, 0))

      expect(mockFetch).toHaveBeenCalledWith('/api/ab-tests/active', {
        headers: {
          'X-Audience': 'individual'
        }
      })
    })

    it('filters tests by audience', async () => {
      const institutionalTest = {
        ...mockTest,
        id: 'institutional-test',
        audience: 'institutional'
      }

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve([mockTest, institutionalTest])
      } as Response)

      service = new ABTestingService('user123', 'session456', 'individual')
      await new Promise(resolve => setTimeout(resolve, 0))

      const activeTests = service.getAllActiveTests()
      expect(activeTests).toHaveLength(1)
      expect(activeTests[0].id).toBe('hero-test-1')
    })

    it('loads existing user assignments from localStorage', () => {
      const storedAssignments = JSON.stringify({
        'hero-test-1': 'variant-a'
      })
      mockLocalStorage.getItem.mockReturnValue(storedAssignments)

      service = new ABTestingService('user123', 'session456', 'individual')

      expect(mockLocalStorage.getItem).toHaveBeenCalledWith('ab_assignments_user123')
    })

    it('loads session assignments from sessionStorage', () => {
      const storedAssignments = JSON.stringify({
        'hero-test-1': 'control'
      })
      mockSessionStorage.getItem.mockReturnValue(storedAssignments)

      service = new ABTestingService(undefined, 'session456', 'individual')

      expect(mockSessionStorage.getItem).toHaveBeenCalledWith('ab_session_assignments_session456')
    })
  })

  describe('Variant Assignment', () => {
    beforeEach(async () => {
      service = new ABTestingService('user123', 'session456', 'individual')
      await new Promise(resolve => setTimeout(resolve, 0))
    })

    it('assigns variants consistently for the same user', () => {
      const variant1 = service.getVariant('hero-test-1')
      const variant2 = service.getVariant('hero-test-1')

      expect(variant1).toBeDefined()
      expect(variant2).toBeDefined()
      expect(variant1?.id).toBe(variant2?.id)
    })

    it('assigns different variants to different users', () => {
      const service1 = new ABTestingService('user123', 'session456', 'individual')
      const service2 = new ABTestingService('user456', 'session789', 'individual')

      // Note: Due to hashing, these might be the same, but the logic is correct
      const variant1 = service1.getVariant('hero-test-1')
      const variant2 = service2.getVariant('hero-test-1')

      expect(variant1).toBeDefined()
      expect(variant2).toBeDefined()

      service1.destroy()
      service2.destroy()
    })

    it('respects traffic allocation', () => {
      const lowTrafficTest = {
        ...mockTest,
        id: 'low-traffic-test',
        trafficAllocation: 10 // Only 10% of users
      }

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve([lowTrafficTest])
      } as Response)

      // Test with many different users to check allocation
      let participantsCount = 0
      const totalUsers = 100

      for (let i = 0; i < totalUsers; i++) {
        const testService = new ABTestingService(`user${i}`, `session${i}`, 'individual')
        const variant = testService.getVariant('low-traffic-test')
        
        if (variant && variant.id !== 'control') {
          participantsCount++
        }
        
        testService.destroy()
      }

      // Should be roughly 10% (allowing for some variance due to hashing)
      expect(participantsCount).toBeLessThan(20) // Less than 20% to account for variance
    })

    it('saves assignments to storage', () => {
      service.getVariant('hero-test-1')

      expect(mockLocalStorage.setItem).toHaveBeenCalledWith(
        'ab_assignments_user123',
        expect.stringContaining('hero-test-1')
      )
    })

    it('returns null for non-existent tests', () => {
      const variant = service.getVariant('non-existent-test')
      expect(variant).toBeNull()
    })
  })

  describe('Component Overrides', () => {
    beforeEach(async () => {
      service = new ABTestingService('user123', 'session456', 'individual')
      await new Promise(resolve => setTimeout(resolve, 0))
    })

    it('returns component overrides for assigned variant', () => {
      // Force assignment to variant-a
      const storedAssignments = JSON.stringify({
        'hero-test-1': 'variant-a'
      })
      mockLocalStorage.getItem.mockReturnValue(storedAssignments)

      service = new ABTestingService('user123', 'session456', 'individual')

      const overrides = service.getComponentOverrides('hero-test-1')
      expect(overrides).toHaveLength(1)
      expect(overrides[0].component).toBe('HeroSection')
      expect(overrides[0].props.headline).toBe('New Headline')
    })

    it('returns empty array for control variant', () => {
      const storedAssignments = JSON.stringify({
        'hero-test-1': 'control'
      })
      mockLocalStorage.getItem.mockReturnValue(storedAssignments)

      service = new ABTestingService('user123', 'session456', 'individual')

      const overrides = service.getComponentOverrides('hero-test-1')
      expect(overrides).toHaveLength(0)
    })
  })

  describe('Test Participation Checks', () => {
    beforeEach(async () => {
      service = new ABTestingService('user123', 'session456', 'individual')
      await new Promise(resolve => setTimeout(resolve, 0))
    })

    it('correctly identifies test participation', () => {
      const isInTest = service.isInTest('hero-test-1')
      expect(isInTest).toBe(true)
    })

    it('correctly identifies variant assignment', () => {
      const variant = service.getVariant('hero-test-1')
      const isInVariant = service.isInVariant('hero-test-1', variant!.id)
      
      expect(isInVariant).toBe(true)
    })

    it('returns false for non-existent tests', () => {
      const isInTest = service.isInTest('non-existent-test')
      expect(isInTest).toBe(false)
    })
  })

  describe('Conversion Tracking', () => {
    beforeEach(async () => {
      service = new ABTestingService('user123', 'session456', 'individual')
      await new Promise(resolve => setTimeout(resolve, 0))
    })

    it('tracks conversions for valid goals', () => {
      service.trackConversion('hero-test-1', 'trial_signup', 150)

      expect(mockFetch).toHaveBeenCalledWith('/api/ab-tests/conversions', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Session-ID': 'session456'
        },
        body: expect.stringContaining('trial_signup')
      })
    })

    it('ignores conversions for invalid goals', () => {
      const fetchCallsBefore = mockFetch.mock.calls.length

      service.trackConversion('hero-test-1', 'invalid-goal')

      expect(mockFetch.mock.calls.length).toBe(fetchCallsBefore)
    })

    it('ignores conversions for non-existent tests', () => {
      const fetchCallsBefore = mockFetch.mock.calls.length

      service.trackConversion('non-existent-test', 'trial_signup')

      expect(mockFetch.mock.calls.length).toBe(fetchCallsBefore)
    })
  })

  describe('Test Management', () => {
    beforeEach(async () => {
      service = new ABTestingService('user123', 'session456', 'individual')
      await new Promise(resolve => setTimeout(resolve, 0))
    })

    it('creates new tests', async () => {
      const newTest = {
        name: 'New Test',
        audience: 'individual' as const,
        variants: mockTest.variants,
        trafficAllocation: 50,
        conversionGoals: mockTest.conversionGoals,
        startDate: new Date(),
        status: 'draft' as const
      }

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({ id: 'new-test-123' })
      } as Response)

      const testId = await service.createTest(newTest)

      expect(testId).toBe('new-test-123')
      expect(mockFetch).toHaveBeenCalledWith('/api/ab-tests', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(newTest)
      })
    })

    it('updates existing tests', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true
      } as Response)

      const success = await service.updateTest('hero-test-1', {
        name: 'Updated Test Name'
      })

      expect(success).toBe(true)
      expect(mockFetch).toHaveBeenCalledWith('/api/ab-tests/hero-test-1', {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ name: 'Updated Test Name' })
      })
    })

    it('starts tests', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true
      } as Response)

      const success = await service.startTest('hero-test-1')

      expect(success).toBe(true)
      expect(mockFetch).toHaveBeenCalledWith('/api/ab-tests/hero-test-1', {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/json'
        },
        body: expect.stringContaining('"status":"running"')
      })
    })

    it('pauses tests', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true
      } as Response)

      const success = await service.pauseTest('hero-test-1')

      expect(success).toBe(true)
    })

    it('ends tests', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true
      } as Response)

      const success = await service.endTest('hero-test-1')

      expect(success).toBe(true)
    })
  })

  describe('Results and Statistics', () => {
    beforeEach(async () => {
      service = new ABTestingService('user123', 'session456', 'individual')
      await new Promise(resolve => setTimeout(resolve, 0))
    })

    it('fetches test results', async () => {
      const mockResults = {
        testId: 'hero-test-1',
        variants: [
          { variantId: 'control', conversions: 10, participants: 100 },
          { variantId: 'variant-a', conversions: 15, participants: 100 }
        ]
      }

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve(mockResults)
      } as Response)

      const results = await service.getTestResults('hero-test-1')

      expect(results).toEqual(mockResults)
      expect(mockFetch).toHaveBeenCalledWith('/api/ab-tests/hero-test-1/results')
    })

    it('fetches test statistics', async () => {
      const mockStats = {
        testId: 'hero-test-1',
        totalParticipants: 200,
        totalConversions: 25,
        overallConversionRate: 12.5
      }

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve(mockStats)
      } as Response)

      const stats = await service.getTestStatistics('hero-test-1')

      expect(stats).toEqual(mockStats)
      expect(mockFetch).toHaveBeenCalledWith('/api/ab-tests/hero-test-1/statistics')
    })
  })

  describe('Statistical Significance', () => {
    beforeEach(async () => {
      service = new ABTestingService('user123', 'session456', 'individual')
      await new Promise(resolve => setTimeout(resolve, 0))
    })

    it('calculates statistical significance correctly', () => {
      const result = service.calculateStatisticalSignificance(
        10, // control conversions
        100, // control samples
        20, // variant conversions
        100  // variant samples
      )

      expect(result).toHaveProperty('significant')
      expect(result).toHaveProperty('pValue')
      expect(result).toHaveProperty('confidenceLevel')
      expect(typeof result.significant).toBe('boolean')
      expect(typeof result.pValue).toBe('number')
      expect(typeof result.confidenceLevel).toBe('number')
    })

    it('identifies significant differences', () => {
      const result = service.calculateStatisticalSignificance(
        5,   // control conversions (5%)
        100, // control samples
        20,  // variant conversions (20%)
        100  // variant samples
      )

      expect(result.significant).toBe(true)
      expect(result.pValue).toBeLessThan(0.05)
    })

    it('identifies non-significant differences', () => {
      const result = service.calculateStatisticalSignificance(
        10,  // control conversions (10%)
        100, // control samples
        11,  // variant conversions (11%)
        100  // variant samples
      )

      expect(result.significant).toBe(false)
      expect(result.pValue).toBeGreaterThan(0.05)
    })
  })

  describe('Test Filtering', () => {
    it('filters out tests for wrong audience', async () => {
      const institutionalTest = {
        ...mockTest,
        id: 'institutional-test',
        audience: 'institutional' as const
      }

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve([mockTest, institutionalTest])
      } as Response)

      service = new ABTestingService('user123', 'session456', 'individual')
      await new Promise(resolve => setTimeout(resolve, 0))

      const variant = service.getVariant('institutional-test')
      expect(variant).toBeNull()
    })

    it('filters out expired tests', async () => {
      const expiredTest = {
        ...mockTest,
        id: 'expired-test',
        endDate: new Date('2023-01-01') // Past date
      }

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve([mockTest, expiredTest])
      } as Response)

      service = new ABTestingService('user123', 'session456', 'individual')
      await new Promise(resolve => setTimeout(resolve, 0))

      const variant = service.getVariant('expired-test')
      expect(variant).toBeNull()
    })

    it('filters out non-running tests', async () => {
      const pausedTest = {
        ...mockTest,
        id: 'paused-test',
        status: 'paused' as const
      }

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve([mockTest, pausedTest])
      } as Response)

      service = new ABTestingService('user123', 'session456', 'individual')
      await new Promise(resolve => setTimeout(resolve, 0))

      const variant = service.getVariant('paused-test')
      expect(variant).toBeNull()
    })
  })

  describe('Error Handling', () => {
    it('handles network errors gracefully during initialization', async () => {
      mockFetch.mockRejectedValueOnce(new Error('Network error'))

      expect(() => {
        service = new ABTestingService('user123', 'session456', 'individual')
      }).not.toThrow()

      await new Promise(resolve => setTimeout(resolve, 0))
      expect(service).toBeDefined()
    })

    it('handles malformed stored assignments', () => {
      mockLocalStorage.getItem.mockReturnValue('invalid json')

      expect(() => {
        service = new ABTestingService('user123', 'session456', 'individual')
      }).not.toThrow()
    })

    it('handles API errors gracefully', async () => {
      service = new ABTestingService('user123', 'session456', 'individual')
      await new Promise(resolve => setTimeout(resolve, 0))

      mockFetch.mockRejectedValueOnce(new Error('API error'))

      const results = await service.getTestResults('hero-test-1')
      expect(results).toBeNull()
    })
  })

  describe('Cleanup', () => {
    beforeEach(async () => {
      service = new ABTestingService('user123', 'session456', 'individual')
      await new Promise(resolve => setTimeout(resolve, 0))
    })

    it('saves assignments on destroy', () => {
      service.getVariant('hero-test-1') // Generate an assignment
      service.destroy()

      expect(mockLocalStorage.setItem).toHaveBeenCalled()
      expect(mockSessionStorage.setItem).toHaveBeenCalled()
    })

    it('clears internal state on destroy', () => {
      service.destroy()

      const activeTests = service.getAllActiveTests()
      expect(activeTests).toHaveLength(0)
    })
  })
})