import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { Insight } from '../Types/analytics'

// State
const insights = ref<Insight[]>([])
const loading = ref(false)
const error = ref<string | null>(null)
const currentFilters = ref({
  period: 'last_30_days' as 'last_7_days' | 'last_14_days' | 'last_30_days' | 'last_90_days' | 'custom',
  startDate: '',
  endDate: '',
  type: '' as 'all' | 'trend' | 'anomaly' | 'recommendation',
  metricsFilter: [] as string[]
})

// Actions
async function fetchInsights(filters?: Partial<typeof currentFilters>): Promise<Insight[]> {
  loading.value = true
  error.value = null
  
  try {
    if (filters) {
      Object.assign(currentFilters.value, filters)
    }
    
    const params = new URLSearchParams({
      page: '1',
      limit: '20',
      period: currentFilters.value.period,
      ...(currentFilters.value.startDate && { start_date: currentFilters.value.startDate }),
      ...(currentFilters.value.endDate && { end_date: currentFilters.value.endDate }),
      ...(currentFilters.value.type !== 'all' && { type: currentFilters.value.type }),
      ...(currentFilters.value.metricsFilter.length > 0 && { metrics_filter: JSON.stringify(currentFilters.value.metricsFilter) })
    })
    
    const response = await fetch(`/api/analytics/insights?${params}`)
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }
    
    const data = await response.json()
    insights.value = data.data
    return data.data
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to fetch insights'
    console.error('Fetch insights error:', err)
    return []
  } finally {
    loading.value = false
  }
}

async function generateInsights(options: Partial<typeof currentFilters> & { queue?: boolean } = {}): Promise<any> {
  loading.value = true
  error.value = null
  
  try {
    const body = JSON.stringify({
      period: options.period || currentFilters.value.period,
      ...(options.startDate && { start_date: options.startDate }),
      ...(options.endDate && { end_date: options.endDate }),
      ...(options.metricsFilter && options.metricsFilter.length > 0 && { metrics_filter: options.metricsFilter }),
      queue: options.queue || false
    })
    
    const response = await fetch('/api/analytics/insights/generate', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body
    })
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }
    
    const data = await response.json()
    
    if (!options.queue) {
      // If not queued, refetch insights
      await fetchInsights()
    }
    
    return data
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to generate insights'
    console.error('Generate insights error:', err)
    throw err
  } finally {
    loading.value = false
  }
}

async function trackFeedback(insightId: string, effectivenessScore: number, metadata: Record<string, any> = {}) {
  try {
    const body = JSON.stringify({
      effectiveness_score: effectivenessScore,
      metadata
    })
    
    const response = await fetch(`/api/analytics/insights/${insightId}/feedback`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body
    })
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }
    
    const data = await response.json()
    
    // Update local effectiveness if successful
    const insight = insights.value.find(i => i.id === insightId)
    if (insight) {
      insight.effectiveness = effectivenessScore
    }
    
    return data
  } catch (err) {
    console.error('Track feedback error:', err)
    throw err
  }
}

// Getters
const hasInsights = computed(() => insights.value.length > 0)
const anomaliesOnly = computed(() => insights.value.filter(i => i.type === 'anomaly'))
const trendsOnly = computed(() => insights.value.filter(i => i.type === 'trend'))

// Export store
export const useInsightsStore = defineStore('insights', () => {
  return {
    // State
    insights,
    loading,
    error,
    currentFilters,
    
    // Actions
    fetchInsights,
    generateInsights,
    trackFeedback,
    
    // Getters
    hasInsights,
    anomaliesOnly,
    recommendationsOnly,
    trendsOnly
  }
})