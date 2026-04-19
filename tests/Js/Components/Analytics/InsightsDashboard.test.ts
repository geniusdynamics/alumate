
import { describe, it, expect, beforeEach, vi } from '@jest/globals'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import InsightsDashboard from '@/Components/Analytics/InsightsDashboard.vue'
import { useInsightsStore } from '@/Stores/useInsightsStore'
import { useInsights } from '@/Composables/useInsights'
import type { Insight } from '@/Types/analytics'
import Chart from 'chart.js/auto'

// Mock Chart.js
vi.mock('chart.js/auto', () => ({
  Chart: vi.fn().mockImplementation(() => ({
    destroy: vi.fn(),
    data: { labels: [], datasets: [] },
    options: {},
    config: { type: 'line' }
  }))
})

// Mock useInsights composable
vi.mock('@/Composables/useInsights', () => ({
  useInsights: vi.fn(() => ({
    insights: ref([]),
    loading: ref(false),
    error: ref(null),
    fetchInsights: vi.fn(),
    generateInsights: vi.fn(),
    trackFeedback: vi.fn(),
    anomalies: ref([]),
    trendsWithAnomalies: ref([]),
    highPriorityRecommendations: ref([]),
    realtimeInsights: ref([])
  }))
})

describe('InsightsDashboard', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('renders properly with no insights', () => {
    const wrapper = mount(InsightsDashboard, {
      global: {
        plugins: [createPinia()]
      }
    })

    expect(wrapper.exists()).toBe(true)
    expect(wrapper.find('h2').text()).toBe('Analytics Insights')
    expect(wrapper.find('button').text()).toBe('Generate Insights')
  })

  it('shows loading state when loading is true', () => {
    const mockUseInsights = vi.mocked(useInsights)
    mockUseInsights.mockReturnValue({
      loading: ref(true),
      insights: ref([]),
      fetchInsights: vi.fn(),
      generateInsights: vi.fn(),
      trackFeedback: vi.fn(),
      anomalies: ref([]),
      trendsWithAnomalies: ref([]),
      highPriorityRecommendations: ref([]),
      realtimeInsights: ref([])
    })

    const wrapper = mount(InsightsDashboard, {
      global: {
        plugins: [createPinia()]
      }
    })

    expect(wrapper.find('.animate-spin').exists()).toBe(true)
    expect(wrapper.find('p:contains("Loading insights...")').exists()).toBe(true)
  })

  it('shows empty state when no insights and not loading', () => {
    const mockUseInsights = vi.mocked(useInsights)
    mockUseInsights.mockReturnValue({
      loading: ref(false),
      insights: ref([]),
      fetchInsights: vi.fn(),
      generateInsights: vi.fn(),
      trackFeedback: vi.fn(),
      anomalies: ref([]),
      trendsWithAnomalies: ref([]),
      highPriorityRecommendations: ref([]),
      realtimeInsights: ref([])
    })

    const wrapper = mount(InsightsDashboard, {
      global: {
        plugins: [createPinia()]
      }
    })

    expect(wrapper.find('h3:contains("No insights yet")').exists()).toBe(true)
    expect(wrapper.find('button:contains("Generate Insights")').exists()).toBe(true)
  })

  it('renders insights cards when data available', async () => {
    const mockUseInsights = vi.mocked(useInsights)
    mockUseInsights.mockReturnValue({
      loading: ref(false),
      insights: ref([
        {
          id: '1',
          type: 'trend',
          metric: 'engagement',
          description: 'Test trend description',
          trend_score: 15.5,
          data_points: [
            { date: '2023-01-01', value: 10 },
            { date: '2023-01-02', value: 20 }
          ],
          timestamp: '2023-01-01T00:00:00Z'
        }
      ]),
      fetchInsights: vi.fn(),
      generateInsights: vi.fn(),
      trackFeedback: vi.fn(),
      anomalies: ref([]),
      trendsWithAnomalies: ref([]),
      highPriorityRecommendations: ref([]),
      realtimeInsights: ref([])
    })

    const wrapper = mount(InsightsDashboard, {
      global: {
        plugins: [createPinia()]
      }
    })

    await wrapper.vm.$nextTick()

    expect(wrapper.findAll('.border.rounded-lg').length).toBe(1)
    expect(wrapper.find('h3').text()).toContain('Test trend description')
    expect(wrapper.find('span:contains("trend")').exists()).toBe(true)
    expect(wrapper.find('span:contains("+15.50%")').exists()).toBe(true)
  })

  it('opens feedback modal when clicking rate effectiveness', async () => {
    const mockUseInsights = vi.mocked(useInsights)
    mockUseInsights.mockReturnValue({
      loading: ref(false),
      insights: ref([
        {
          id: '1',
          type: 'trend',
          metric: 'engagement',
          description: 'Test trend',
          timestamp: '2023-01-01T00:00:00Z'
        }
      ]),
      fetchInsights: vi.fn(),
      generateInsights: vi.fn(),
      trackFeedback: vi.fn(),
      anomalies: ref([]),
      trendsWithAnomalies: ref([]),
      highPriorityRecommendations: ref([]),
      realtimeInsights: ref([])
    })

    const wrapper = mount(InsightsDashboard, {
      global: {
        plugins: [createPinia()]
      }
    })

    await wrapper.find('button:contains("Rate Effectiveness")').trigger('click')

    expect(wrapper.find('.fixed.inset-0').isVisible()).toBe(true)
    expect(wrapper.find('input[type="range"]').exists()).toBe(true)
    expect(wrapper.find('select').exists()).toBe(true)
  })

  it('submits feedback and closes modal', async () => {
    const mockUseInsights = vi.mocked(useInsights)
    const mockTrackFeedback = vi