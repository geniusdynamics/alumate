import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useAnalytics } from './useAnalytics'

export interface StatisticsDataSource {
  id: string
  endpoint: string
  method?: 'GET' | 'POST'
  headers?: Record<string, string>
  params?: Record<string, any>
  transform?: (data: any) => any
  refreshInterval?: number // milliseconds
  retryAttempts?: number
  retryDelay?: number // milliseconds
}

export interface StatisticsDataItem {
  id: string
  value: number | string
  label: string
  source?: string
  lastUpdated?: string
  error?: string
}

interface UseStatisticsDataOptions {
  autoRefresh?: boolean
  defaultRefreshInterval?: number
  maxRetryAttempts?: number
  onError?: (error: Error, source: StatisticsDataSource) => void
  onSuccess?: (data: any, source: StatisticsDataSource) => void
}

export function useStatisticsData(options: UseStatisticsDataOptions = {}) {
  const {
    autoRefresh = true,
    defaultRefreshInterval = 30000, // 30 seconds
    maxRetryAttempts = 3,
    onError,
    onSuccess
  } = options

  const { trackEvent, trackError } = useAnalytics()

  // State
  const dataSources = ref<Map<string, StatisticsDataSource>>(new Map())
  const data = ref<Map<string, StatisticsDataItem>>(new Map())
  const loading = ref<Map<string, boolean>>(new Map())
  const errors = ref<Map<string, string>>(new Map())
  const refreshIntervals = ref<Map<string, NodeJS.Timeout>>(new Map())
  const retryAttempts = ref<Map<string, number>>(new Map())

  // Computed
  const isLoading = computed(() => {
    return Array.from(loading.value.values()).some(Boolean)
  })

  const hasErrors = computed(() => {
    return errors.value.size > 0
  })

  const allData = computed(() => {
    return Array.from(data.value.values())
  })

  // Methods
  const addDataSource = (source: StatisticsDataSource) => {
    dataSources.value.set(source.id, source)
    loading.value.set(source.id, false)
    errors.value.delete(source.id)
    retryAttempts.value.set(source.id, 0)

    if (autoRefresh && source.refreshInterval) {
      setupRefreshInterval(source)
    }

    // Initial fetch
    fetchData(source.id)
  }

  const removeDataSource = (sourceId: string) => {
    dataSources.value.delete(sourceId)
    data.value.delete(sourceId)
    loading.value.delete(sourceId)
    errors.value.delete(sourceId)
    retryAttempts.value.delete(sourceId)
    
    const interval = refreshIntervals.value.get(sourceId)
    if (interval) {
      clearInterval(interval)
      refreshIntervals.value.delete(sourceId)
    }
  }

  const fetchData = async (sourceId: string) => {
    const source = dataSources.value.get(sourceId)
    if (!source) return

    loading.value.set(sourceId, true)
    errors.value.delete(sourceId)

    try {
      const response = await makeRequest(source)
      const transformedData = source.transform ? source.transform(response) : response

      // Update data
      const dataItem: StatisticsDataItem = {
        id: sourceId,
        value: transformedData.value || transformedData,
        label: transformedData.label || source.id,
        source: source.endpoint,
        lastUpdated: new Date().toISOString(),
      }

      data.value.set(sourceId, dataItem)
      retryAttempts.value.set(sourceId, 0)

      trackEvent('statistics_data_fetch_success', {
        source_id: sourceId,
        endpoint: source.endpoint,
        value: dataItem.value
      })

      onSuccess?.(transformedData, source)
    } catch (error) {
      const errorMessage = error instanceof Error ? error.message : 'Unknown error'
      errors.value.set(sourceId, errorMessage)

      trackError(error instanceof Error ? error : new Error(errorMessage), {
        component: 'useStatisticsData',
        source_id: sourceId,
        endpoint: source.endpoint
      })

      onError?.(error instanceof Error ? error : new Error(errorMessage), source)

      // Retry logic
      const currentAttempts = retryAttempts.value.get(sourceId) || 0
      if (currentAttempts < (source.retryAttempts || maxRetryAttempts)) {
        retryAttempts.value.set(sourceId, currentAttempts + 1)
        const retryDelay = source.retryDelay || 1000 * Math.pow(2, currentAttempts) // Exponential backoff
        
        setTimeout(() => {
          fetchData(sourceId)
        }, retryDelay)
      }
    } finally {
      loading.value.set(sourceId, false)
    }
  }

  const makeRequest = async (source: StatisticsDataSource): Promise<any> => {
    const url = new URL(source.endpoint, window.location.origin)
    
    // Add query parameters for GET requests
    if (source.method !== 'POST' && source.params) {
      Object.entries(source.params).forEach(([key, value]) => {
        url.searchParams.append(key, String(value))
      })
    }

    const requestOptions: RequestInit = {
      method: source.method || 'GET',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...source.headers
      }
    }

    // Add CSRF token if available
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
    if (csrfToken) {
      requestOptions.headers = {
        ...requestOptions.headers,
        'X-CSRF-TOKEN': csrfToken
      }
    }

    // Add body for POST requests
    if (source.method === 'POST' && source.params) {
      requestOptions.body = JSON.stringify(source.params)
    }

    const response = await fetch(url.toString(), requestOptions)

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`)
    }

    const contentType = response.headers.get('content-type')
    if (contentType && contentType.includes('application/json')) {
      return await response.json()
    }

    return await response.text()
  }

  const setupRefreshInterval = (source: StatisticsDataSource) => {
    const interval = source.refreshInterval || defaultRefreshInterval
    
    const intervalId = setInterval(() => {
      fetchData(source.id)
    }, interval)

    refreshIntervals.value.set(source.id, intervalId)
  }

  const refreshData = (sourceId?: string) => {
    if (sourceId) {
      fetchData(sourceId)
    } else {
      // Refresh all data sources
      dataSources.value.forEach((_, id) => {
        fetchData(id)
      })
    }
  }

  const clearErrors = (sourceId?: string) => {
    if (sourceId) {
      errors.value.delete(sourceId)
    } else {
      errors.value.clear()
    }
  }

  const getDataBySource = (sourceId: string): StatisticsDataItem | undefined => {
    return data.value.get(sourceId)
  }

  const getErrorBySource = (sourceId: string): string | undefined => {
    return errors.value.get(sourceId)
  }

  const isSourceLoading = (sourceId: string): boolean => {
    return loading.value.get(sourceId) || false
  }

  // Cleanup function
  const cleanup = () => {
    refreshIntervals.value.forEach((interval) => {
      clearInterval(interval)
    })
    refreshIntervals.value.clear()
  }

  // Lifecycle
  onUnmounted(() => {
    cleanup()
  })

  // Predefined data sources for common platform metrics
  const createPlatformMetricsSource = (metric: string): StatisticsDataSource => {
    return {
      id: `platform_${metric}`,
      endpoint: `/api/analytics/metrics/${metric}`,
      method: 'GET',
      refreshInterval: 60000, // 1 minute
      retryAttempts: 3,
      transform: (data) => ({
        value: data.value,
        label: data.label || metric.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())
      })
    }
  }

  const addPlatformMetric = (metric: string) => {
    const source = createPlatformMetricsSource(metric)
    addDataSource(source)
  }

  // Common platform metrics
  const addCommonMetrics = () => {
    const commonMetrics = [
      'total_alumni',
      'active_users',
      'job_placements',
      'success_rate',
      'average_salary_increase',
      'employer_partners',
      'course_completions',
      'user_satisfaction'
    ]

    commonMetrics.forEach(metric => {
      addPlatformMetric(metric)
    })
  }

  return {
    // State
    data: allData,
    isLoading,
    hasErrors,
    errors: computed(() => Array.from(errors.value.entries())),

    // Methods
    addDataSource,
    removeDataSource,
    fetchData,
    refreshData,
    clearErrors,
    getDataBySource,
    getErrorBySource,
    isSourceLoading,
    cleanup,

    // Platform-specific helpers
    addPlatformMetric,
    addCommonMetrics,
    createPlatformMetricsSource
  }
}

// Helper function to create mock data for development/testing
export function createMockStatisticsData(): StatisticsDataItem[] {
  return [
    {
      id: 'total_alumni',
      value: 15420,
      label: 'Total Alumni',
      lastUpdated: new Date().toISOString()
    },
    {
      id: 'job_placements',
      value: 8934,
      label: 'Job Placements',
      lastUpdated: new Date().toISOString()
    },
    {
      id: 'success_rate',
      value: 94,
      label: 'Success Rate',
      lastUpdated: new Date().toISOString()
    },
    {
      id: 'avg_salary_increase',
      value: 45000,
      label: 'Avg. Salary Increase',
      lastUpdated: new Date().toISOString()
    }
  ]
}

// Helper function to create comparison data
export function createComparisonData(type: 'before-after' | 'competitive') {
  if (type === 'before-after') {
    return [
      {
        id: 'job_placement_rate',
        label: 'Job Placement Rate',
        beforeValue: 67,
        afterValue: 94,
        value: 94
      },
      {
        id: 'avg_time_to_hire',
        label: 'Average Time to Hire',
        beforeValue: 180,
        afterValue: 45,
        value: 45
      },
      {
        id: 'salary_increase',
        label: 'Average Salary Increase',
        beforeValue: 25000,
        afterValue: 45000,
        value: 45000
      }
    ]
  }

  // Competitive data
  return [
    {
      id: 'our_platform',
      label: 'Our Platform',
      value: 94,
      highlighted: true,
      color: '#3b82f6'
    },
    {
      id: 'competitor_a',
      label: 'Competitor A',
      value: 78,
      color: '#6b7280'
    },
    {
      id: 'competitor_b',
      label: 'Competitor B',
      value: 82,
      color: '#6b7280'
    },
    {
      id: 'industry_avg',
      label: 'Industry Average',
      value: 71,
      color: '#9ca3af'
    }
  ]
}