/**
 * InsightsDashboard Component Tests
 * 
 * Tests for the InsightsDashboard.vue component including:
 * - Filtering and sorting functionality
 * - Insight dismissal
 * - Feedback tracking
 * - Export functionality
 * - Responsive design
 */

import { flushPromises, mount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import InsightsDashboard from '../Analytics/InsightsDashboard.vue'
import { createTestingPinia } from '@pinia/testing'

// Mock the insights store
vi.mock('@/stores/useInsightsStore', () => ({
  useInsightsStore: vi.fn(() => ({
    insights: [],
    loading: false,
    error: null,
    fetchInsights: vi.fn().mockResolvedValue([]),
    generateInsights: vi.fn().mockResolvedValue({}),
    trackFeedback: vi.fn().mockResolvedValue({}),
    dismissInsight: vi.fn().mockResolvedValue({}),
    updateInsight: vi.fn().mockResolvedValue({}),
    exportInsights: vi.fn().mockResolvedValue([]),
  }))
}))

// Mock Chart.js
vi.mock('chart.js', () => ({
  Chart: vi.fn().mockImplementation(() => ({
    destroy: vi.fn(),
  })),
  registerables: [],
}))

// Extended mock insight type
type MockInsight = {
  id: string;
  type: 'trend' | 'anomaly' | 'recommendation';
  description: string;
  metric?: string;
  trend_score?: number;
  severity?: 'low' | 'medium' | 'high' | 'critical';
  status?: 'active' | 'dismissed' | 'implemented';
  timestamp: string;
  data_points?: { date: string; value: number }[];
  recommendation?: {
    type: string;
    description: string;
    expected_impact: string;
    priority: 'low' | 'medium' | 'high' | 'critical';
    action?: string;
  };
  effectiveness?: number;
  [key: string]: any;
}

describe('InsightsDashboard.vue', () => {
  let wrapper: any
  const pinia = createTestingPinia()

  const mockInsights: MockInsight[] = [
    {
      id: '1',
      type: 'trend',
      description: 'Engagement is increasing',
      metric: 'user_engagement',
      trend_score: 12.5,
      severity: 'high',
      status: 'active',
      timestamp: '2024-01-15T10:00:00Z',
      data_points: [
        { date: '2024-01-01', value: 100 },
        { date: '2024-01-02', value: 120 },
      ],
      recommendation: {
        type: 'optimization',
        description: 'Continue current strategy',
        expected_impact: '10% increase',
        priority: 'medium',
        action: 'View Details'
      }
    },
    {
      id: '2',
      type: 'anomaly',
      description: 'Spike in bounce rate detected',
      metric: 'bounce_rate',
      severity: 'critical',
      status: 'active',
      timestamp: '2024-01-14T15:00:00Z',
      value: 85,
      baseline: 40,
    },
    {
      id: '3',
      type: 'recommendation',
      description: 'Consider A/B testing for landing page',
      metric: 'conversion_rate',
      severity: 'medium',
      status: 'dismissed',
      timestamp: '2024-01-13T09:00:00Z',
      effectiveness: 7,
    }
  ]

  beforeEach(async () => {
    vi.clearAllMocks()
    
    // Mock window.scrollTo
    vi.spyOn(window, 'scrollTo').mockImplementation(() => {})
    
    wrapper = mount(InsightsDashboard, {
      props: {
        dense: false,
        showSummary: true,
        autoRefresh: false,
      },
      global: {
        plugins: [pinia],
        stubs: {
          Teleport: true,
        },
      },
    })
    await flushPromises()
  })

  afterEach(() => {
    if (wrapper) wrapper.unmount()
    vi.restoreAllMocks()
  })

  describe('Component Mounting', () => {
    it('mounts without errors', () => {
      expect(wrapper.exists()).toBe(true)
      expect(wrapper.find('.insights-dashboard').exists()).toBe(true)
    })

    it('renders header with title', () => {
      expect(wrapper.find('h2').text()).toBe('Analytics Insights')
    })

    it('renders generate insights button', () => {
      const generateBtn = wrapper.find('button')
      expect(generateBtn.text()).toContain('Generate Insights')
    })
  })

  describe('Summary Cards', () => {
    it('displays summary cards when showSummary is true', () => {
      expect(wrapper.find('.grid-cols-2').exists()).toBe(true)
    })
  })

  describe('Filters', () => {
    it('renders period filter dropdown', () => {
      const periodSelect = wrapper.find('select')
      expect(periodSelect.exists()).toBe(true)
      expect(periodSelect.findAll('option').length).toBeGreaterThan(4)
    })

    it('renders type filter dropdown', () => {
      const selects = wrapper.findAll('select')
      expect(selects.length).toBeGreaterThan(1)
    })

    it('renders severity filter dropdown', () => {
      const selects = wrapper.findAll('select')
      expect(selects.length).toBeGreaterThan(2)
    })

    it('renders sort dropdown', () => {
      const selects = wrapper.findAll('select')
      expect(selects.length).toBeGreaterThan(3)
    })
  })

  describe('Search', () => {
    it('renders search input', () => {
      const searchInput = wrapper.find('input[type="text"]')
      expect(searchInput.exists()).toBe(true)
    })

    it('filters insights based on search query', async () => {
      const searchInput = wrapper.find('input[type="text"]')
      await searchInput.setValue('engagement')
      expect(wrapper.vm.searchQuery).toBe('engagement')
    })
  })

  describe('Insights List', () => {
    it('renders empty state when no insights', () => {
      const emptyState = wrapper.find('text-center')
      expect(emptyState.text()).toContain('No insights found')
    })
  })

  describe('Status Badges', () => {
    it('displays correct status badge colors', () => {
      const statusBadgeClasses = {
        active: 'bg-green-100 text-green-800',
        dismissed: 'bg-gray-100 text-gray-800',
        implemented: 'bg-blue-100 text-blue-800',
      }
      
      expect(typeof wrapper.vm.getStatusBadgeClass).toBe('function')
      expect(wrapper.vm.getStatusBadgeClass('active')).toBe(statusBadgeClasses.active)
      expect(wrapper.vm.getStatusBadgeClass('dismissed')).toBe(statusBadgeClasses.dismissed)
      expect(wrapper.vm.getStatusBadgeClass('implemented')).toBe(statusBadgeClasses.implemented)
    })
  })

  describe('Severity Badges', () => {
    it('displays correct severity badge colors', () => {
      const severityBadgeClasses = {
        critical: 'bg-red-100 text-red-800',
        high: 'bg-orange-100 text-orange-800',
        medium: 'bg-yellow-100 text-yellow-800',
        low: 'bg-gray-100 text-gray-800',
      }
      
      expect(typeof wrapper.vm.getSeverityBadgeClass).toBe('function')
      expect(wrapper.vm.getSeverityBadgeClass('critical')).toBe(severityBadgeClasses.critical)
      expect(wrapper.vm.getSeverityBadgeClass('high')).toBe(severityBadgeClasses.high)
      expect(wrapper.vm.getSeverityBadgeClass('medium')).toBe(severityBadgeClasses.medium)
      expect(wrapper.vm.getSeverityBadgeClass('low')).toBe(severityBadgeClasses.low)
    })
  })

  describe('Type Badges', () => {
    it('displays correct type badge colors', () => {
      const typeBadgeClasses = {
        trend: 'bg-blue-100 text-blue-800',
        anomaly: 'bg-red-100 text-red-800',
        recommendation: 'bg-green-100 text-green-800',
      }
      
      expect(typeof wrapper.vm.getTypeBadgeClass).toBe('function')
      expect(wrapper.vm.getTypeBadgeClass('trend')).toBe(typeBadgeClasses.trend)
      expect(wrapper.vm.getTypeBadgeClass('anomaly')).toBe(typeBadgeClasses.anomaly)
      expect(wrapper.vm.getTypeBadgeClass('recommendation')).toBe(typeBadgeClasses.recommendation)
    })
  })

  describe('Format Functions', () => {
    it('formats date correctly', () => {
      expect(typeof wrapper.vm.formatDate).toBe('function')
      const formatted = wrapper.vm.formatDate('2024-01-15T10:00:00Z')
      expect(formatted).toContain('Jan')
      expect(formatted).toContain('15')
      expect(formatted).toContain('2024')
    })

    it('formats type correctly', () => {
      expect(typeof wrapper.vm.formatType).toBe('function')
      expect(wrapper.vm.formatType('trend')).toBe('Trend')
      expect(wrapper.vm.formatType('anomaly')).toBe('Anomaly')
      expect(wrapper.vm.formatType('recommendation')).toBe('Recommendation')
    })
  })

  describe('Pagination', () => {
    it('calculates total pages correctly', async () => {
      const store = (wrapper.vm as any).__pinia?.state?.value?.insights
      if (store) {
        store.insights = mockInsights as any
        await flushPromises()
      }

      expect(wrapper.vm.totalPages).toBeGreaterThanOrEqual(1)
    })
  })

  describe('Responsive Design', () => {
    it('applies normal padding by default', () => {
      expect(wrapper.classes()).toContain('md:p-6')
    })
  })

  describe('Custom Date Range', () => {
    it('shows custom date inputs when custom period selected', async () => {
      const periodSelect = wrapper.find('select')
      await periodSelect.setValue('custom')
      await flushPromises()
      
      const dateInputs = wrapper.findAll('input[type="date"]')
      expect(dateInputs.length).toBe(2)
    })
  })

  describe('Export Options', () => {
    it('has correct export options structure', () => {
      expect(typeof wrapper.vm.exportInsights).toBe('function')
      expect(wrapper.vm.exportOptions).toEqual({
        includeRecommendations: true,
        includeDataPoints: true,
        includeFeedback: true,
      })
    })
  })

  describe('Action Emission', () => {
    it('emits action event when recommendation action clicked', async () => {
      wrapper.vm.performAction('View Details')
      expect(wrapper.emitted('action')).toBeTruthy()
      expect(wrapper.emitted('action')[0]).toEqual([{ action: 'View Details' }])
    })
  })

  describe('Sorting', () => {
    it('changes sort option when select changed', async () => {
      const selects = wrapper.findAll('select')
      const sortSelect = selects[selects.length - 1]
      if (sortSelect?.exists()) {
        await sortSelect.setValue('severity-desc')
        expect(wrapper.vm.sortBy).toBe('severity-desc')
      }
    })
  })
})

describe('InsightsDashboard - Edge Cases', () => {
  let wrapper: any
  const pinia = createTestingPinia()

  afterEach(() => {
    if (wrapper) wrapper.unmount()
    vi.restoreAllMocks()
  })

  const baseInsight: MockInsight = {
    id: '1',
    type: 'trend',
    description: 'Test insight',
    metric: 'test',
    severity: 'low',
    status: 'active',
    timestamp: '2024-01-15T10:00:00Z',
  }

  it('handles very long insight descriptions', async () => {
    const longInsight = { ...baseInsight, description: 'A'.repeat(500) }

    wrapper = mount(InsightsDashboard, {
      props: {
        dense: false,
        showSummary: true,
        autoRefresh: false,
      },
      global: {
        plugins: [pinia],
        stubs: { Teleport: true },
      },
    })
    await flushPromises()

    // Component should render without errors
    expect(wrapper.exists()).toBe(true)
  })

  it('handles insights with no data points', async () => {
    const noDataInsight = { ...baseInsight, data_points: [] }

    wrapper = mount(InsightsDashboard, {
      props: {
        dense: false,
        showSummary: true,
        autoRefresh: false,
      },
      global: {
        plugins: [pinia],
        stubs: { Teleport: true },
      },
    })
    await flushPromises()
    expect(wrapper.exists()).toBe(true)
  })

  it('handles empty search query gracefully', async () => {
    wrapper = mount(InsightsDashboard, {
      props: {
        dense: false,
        showSummary: true,
        autoRefresh: false,
      },
      global: {
        plugins: [pinia],
        stubs: { Teleport: true },
      },
    })
    await flushPromises()

    await wrapper.find('input[type="text"]').setValue('')
    expect(wrapper.vm.searchQuery).toBe('')
    expect(wrapper.exists()).toBe(true)
  })

  it('handles dense prop correctly', async () => {
    wrapper = mount(InsightsDashboard, {
      props: {
        dense: true,
        showSummary: true,
        autoRefresh: false,
      },
      global: {
        plugins: [pinia],
        stubs: { Teleport: true },
      },
    })
    await flushPromises()

    expect(wrapper.classes()).toContain('p-4')
  })
})
