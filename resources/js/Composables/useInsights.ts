import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useInsightsStore } from '../Stores/useInsightsStore'
import type { Insight, Trend, Anomaly } from '../types/analytics'
import { useWebSocket } from './useWebSocket'

// Ref for real-time updates
const realtimeInsights = ref<Insight[]>([])

// WebSocket for insights updates
const { subscribe, unsubscribe } = useWebSocket()

// Computed for anomalies detection
const anomalies = computed(() => {
  const store = useInsightsStore()
  return store.insights.filter((insight: Insight) => 
    insight.type === 'anomaly' || 
    (insight.type === 'trend' && insight.anomaly)
  )
})

// Computed for trends with anomaly flags
const trendsWithAnomalies = computed(() => {
  const store = useInsightsStore()
  return store.insights
    .filter((insight: Insight) => insight.type === 'trend')
    .map((insight: Insight) => ({
      ...insight,
      anomaly: Math.abs((insight.trend_score || 0)) > 25 // Threshold for anomaly
    })) as Trend[]
})

// Computed for high-priority recommendations
const highPriorityRecommendations = computed(() => {
  const store = useInsightsStore()
  return store.insights
    .filter((insight: Insight) => 
      insight.recommendation?.priority === 'high' || 
      insight.recommendation?.priority === 'critical'
    )
    .map((insight: Insight) => insight.recommendation)
})

// Function to compute trend anomalies
const computeTrendAnomalies = (trends: Insight[]): Anomaly[] => {
  return trends
    .filter((t: Insight) => t.type === 'trend' && Math.abs(t.trend_score || 0) > 25)
    .map((t: Insight) => ({
      metric: t.metric || 'unknown',
      date: new Date().toISOString().split('T')[0],
      description: `Significant ${t.trend_score! > 0 ? 'increase' : 'decrease'} in ${t.metric}`,
      severity: Math.abs(t.trend_score || 0) > 50 ? 'high' : 'medium',
      value: Math.abs(t.trend_score || 0),
      baseline: 0,
      z_score: (t.trend_score || 0) / 25 // Simplified z-score
    })) as Anomaly[]
}

// Subscribe to WebSocket for real-time insights updates
const subscribeToInsightsUpdates = () => {
  subscribe('insights-updated', (data: Insight[]) => {
    realtimeInsights.value = data
    // Refetch from store to sync
    const store = useInsightsStore()
    store.fetchInsights()
  })
}

// Initialize composable
onMounted(() => {
  subscribeToInsightsUpdates()
})

onUnmounted(() => {
  unsubscribe('insights-updated')
})

export function useInsights() {
  const store = useInsightsStore()
  
  return {
    // Store access
    insights: store.insights,
    loading: store.loading,
    error: store.error,
    currentFilters: store.currentFilters,
    
    // Computed properties
    anomalies,
    trendsWithAnomalies,
    highPriorityRecommendations,
    realtimeInsights,
    
    // Actions
    fetchInsights: store.fetchInsights,
    generateInsights: store.generateInsights,
    trackFeedback: store.trackFeedback,
    
    // Custom functions
    computeTrendAnomalies
  }
}
