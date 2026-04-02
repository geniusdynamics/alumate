import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

// Extended Insight type with all API properties
interface ApiInsight {
  id: string;
  type: 'trend' | 'anomaly' | 'recommendation' | 'correlation';
  metric?: string;
  description: string;
  title?: string;
  trend_score?: number;
  data_points?: { date: string; value: number }[];
  anomaly?: boolean;
  severity?: 'low' | 'medium' | 'high' | 'critical';
  value?: number;
  baseline?: number;
  z_score?: number;
  recommendation?: {
    type: string;
    target: string;
    description: string;
    expected_impact: string;
    priority: 'low' | 'medium' | 'high' | 'critical';
    action?: string;
    attribution_insights?: any;
  };
  effectiveness?: number;
  status?: 'active' | 'dismissed' | 'implemented';
  timestamp: string;
  [key: string]: any;
}

// State
const insights = ref<ApiInsight[]>([])
const loading = ref(false)
const error = ref<string | null>(null)
const currentFilters = ref({
  period: 'last_30_days' as 'last_7_days' | 'last_14_days' | 'last_30_days' | 'last_90_days' | 'custom',
  startDate: '',
  endDate: '',
  type: '' as 'all' | 'trend' | 'anomaly' | 'recommendation' | 'correlation',
  metricsFilter: [] as string[]
})

// Actions
async function fetchInsights(filters?: Record<string, string>): Promise<ApiInsight[]> {
  loading.value = true
  error.value = null
  
  try {
    const params = new URLSearchParams({
      page: '1',
      limit: '20',
      ...filters
    })
    
    const response = await fetch(`/api/insights?${params}`)
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }
    
    const data = await response.json()
    insights.value = data.data || []
    return insights.value
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to fetch insights'
    console.error('Fetch insights error:', err)
    return []
  } finally {
    loading.value = false
  }
}

async function generateInsights(options: Record<string, any> = {}): Promise<any> {
  loading.value = true
  error.value = null
  
  try {
    const body = JSON.stringify({
      period: options.period || currentFilters.value.period,
      ...(options.startDate && { start_date: options.startDate }),
      ...(options.endDate && { end_date: options.endDate }),
      queue: options.queue || false
    })
    
    const response = await fetch('/api/insights/generate', {
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
      ...metadata
    })
    
    const response = await fetch(`/api/insights/${insightId}/feedback`, {
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

async function dismissInsight(insightId: string): Promise<void> {
  try {
    const response = await fetch(`/api/insights/${insightId}/dismiss`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' }
    })
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }
    
    // Update local insight status
    const insight = insights.value.find(i => i.id === insightId)
    if (insight) {
      insight.status = 'dismissed'
    }
  } catch (err) {
    console.error('Dismiss insight error:', err)
    throw err
  }
}

async function updateInsight(insightId: string, data: Record<string, any>): Promise<void> {
  try {
    const body = JSON.stringify(data)
    
    const response = await fetch(`/api/insights/${insightId}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body
    })
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }
    
    // Update local insight
    const insight = insights.value.find(i => i.id === insightId)
    if (insight) {
      Object.assign(insight, data)
    }
  } catch (err) {
    console.error('Update insight error:', err)
    throw err
  }
}

async function exportInsights(options: {
  format: 'json' | 'csv';
  include_recommendations?: boolean;
  include_data_points?: boolean;
  include_feedback?: boolean;
}): Promise<any> {
  try {
    const body = JSON.stringify(options)
    
    const response = await fetch('/api/insights/export', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body
    })
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }
    
    if (options.format === 'csv') {
      // For CSV, return blob
      return await response.blob()
    }
    
    return await response.json()
  } catch (err) {
    console.error('Export insights error:', err)
    throw err
  }
}

async function getInsight(insightId: string): Promise<ApiInsight | null> {
  try {
    const response = await fetch(`/api/insights/${insightId}`)
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }
    
    const data = await response.json()
    return data.data?.insight || null
  } catch (err) {
    console.error('Get insight error:', err)
    return null
  }
}

async function deleteInsight(insightId: string): Promise<void> {
  try {
    const response = await fetch(`/api/insights/${insightId}`, {
      method: 'DELETE'
    })
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }
    
    // Remove from local state
    const index = insights.value.findIndex(i => i.id === insightId)
    if (index !== -1) {
      insights.value.splice(index, 1)
    }
  } catch (err) {
    console.error('Delete insight error:', err)
    throw err
  }
}

// Getters
const hasInsights = computed(() => insights.value.length > 0)
const anomaliesOnly = computed(() => insights.value.filter(i => i.type === 'anomaly'))
const trendsOnly = computed(() => insights.value.filter(i => i.type === 'trend'))
const recommendationsOnly = computed(() => insights.value.filter(i => i.type === 'recommendation'))
const activeInsights = computed(() => insights.value.filter(i => i.status === 'active'))
const dismissedInsights = computed(() => insights.value.filter(i => i.status === 'dismissed'))

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
    dismissInsight,
    updateInsight,
    exportInsights,
    getInsight,
    deleteInsight,
    
    // Getters
    hasInsights,
    anomaliesOnly,
    recommendationsOnly,
    trendsOnly,
    activeInsights,
    dismissedInsights
  }
})

export type { ApiInsight }
