import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

interface DashboardFilters {
  dateRange: string
  dateFrom: string
  dateTo: string
  templateId: string
}

interface Template {
  id: number
  name: string
  category: string
  audience_type: string
}

interface DashboardOverview {
  summary: {
    total_templates: number
    total_conversions: number
    conversion_rate: number
    unique_users: number
    period: {
      from: string
      to: string
    }
  }
  performance: Record<string, any>
  trends: Array<{
    date: string
    page_views: number
    conversions: number
    unique_users: number
  }>
  insights: Array<{
    id?: string
    type: 'success' | 'warning' | 'info' | 'error'
    title: string
    description: string
    data?: any[]
    priority?: 'high' | 'medium' | 'low'
  }>
  generated_at: string
}

interface ComparisonData {
  templates: Array<{
    template: {
      id: number
      name: string
      category: string
      audience_type: string
    }
    metrics: {
      usage_count: number
      conversion_rate: number
      load_time: number
      last_used: string
    }
    performance_score: number
  }>
  summary: {
    total_templates: number
    average_conversion_rate: number
    best_performer: any
    worst_performer: any
  }
  recommendations: Array<{
    type: string
    description: string
    priority: string
  }>
  generated_at: string
}

export const useDashboardStore = defineStore('dashboard', () => {
  // State
  const overviewData = ref<DashboardOverview | null>(null)
  const comparisonData = ref<ComparisonData | null>(null)
  const realTimeData = ref<any>(null)
  const templates = ref<Template[]>([])
  const loading = ref(false)
  const error = ref('')

  // Getters
  const isLoading = computed(() => loading.value)
  const hasError = computed(() => !!error.value)
  const hasOverviewData = computed(() => !!overviewData.value)
  const hasComparisonData = computed(() => !!comparisonData.value)

  // Actions
  const fetchOverview = async (tenantId: number, filters: DashboardFilters) => {
    loading.value = true
    error.value = ''

    try {
      const params = new URLSearchParams()
      Object.entries(filters).forEach(([key, value]) => {
        if (value) params.append(key, value)
      })

      const response = await axios.get(`/api/dashboard/overview?${params}`)
      overviewData.value = response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch dashboard overview'
      throw err
    } finally {
      loading.value = false
    }
  }

  const fetchComparison = async (templateIds: number[], filters: DashboardFilters) => {
    loading.value = true
    error.value = ''

    try {
      const params = new URLSearchParams()
      templateIds.forEach(id => params.append('template_ids[]', id.toString()))
      Object.entries(filters).forEach(([key, value]) => {
        if (value) params.append(key, value)
      })

      const response = await axios.get(`/api/dashboard/comparison?${params}`)
      comparisonData.value = response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch template comparison'
      throw err
    } finally {
      loading.value = false
    }
  }

  const fetchRealTimeMetrics = async () => {
    try {
      const response = await axios.get('/api/dashboard/realtime')
      realTimeData.value = response.data.data
      return response.data.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to fetch real-time metrics'
      throw err
    }
  }

  const fetchTemplates = async () => {
    try {
      const response = await axios.get('/api/templates')
      templates.value = response.data.data || []
    } catch (err: any) {
      console.error('Failed to fetch templates:', err)
    }
  }

  const exportData = async (tenantId: number, format: string, filters: DashboardFilters) => {
    try {
      const params = new URLSearchParams()
      params.append('format', format)
      Object.entries(filters).forEach(([key, value]) => {
        if (value) params.append(key, value)
      })

      const response = await axios.get(`/api/dashboard/export?${params}`)
      return response.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to export data'
      throw err
    }
  }

  const generateReport = async (parameters: any) => {
    try {
      const response = await axios.post('/api/dashboard/reports', parameters)
      return response.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Failed to generate report'
      throw err
    }
  }

  const clearCache = () => {
    overviewData.value = null
    comparisonData.value = null
    realTimeData.value = null
    error.value = ''
  }

  // Initialize
  const initialize = async (tenantId: number) => {
    await Promise.all([
      fetchTemplates(),
      fetchOverview(tenantId, {
        dateRange: 'last_30_days',
        dateFrom: '',
        dateTo: '',
        templateId: ''
      })
    ])
  }

  return {
    // State
    overviewData,
    comparisonData,
    realTimeData,
    templates,
    loading,
    error,

    // Getters
    isLoading,
    hasError,
    hasOverviewData,
    hasComparisonData,

    // Actions
    fetchOverview,
    fetchComparison,
    fetchRealTimeMetrics,
    fetchTemplates,
    exportData,
    generateReport,
    clearCache,
    initialize
  }
})