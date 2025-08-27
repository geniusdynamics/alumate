/**
 * Vue composable for managing statistics with real-time updates
 */

import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { statisticsService, type StatisticData } from '@/services/StatisticsService'
import type { StatisticCounter } from '@/types/components'

export interface UseStatisticsOptions {
  refreshInterval?: number
  enableRealTime?: boolean
  retryAttempts?: number
  cacheEnabled?: boolean
}

export interface StatisticState {
  data: StatisticData | null
  isLoading: boolean
  error: Error | null
  lastUpdated: Date | null
  retryCount: number
}

export function useStatistics(
  statistics: StatisticCounter[],
  options: UseStatisticsOptions = {}
) {
  const {
    refreshInterval = 300000, // 5 minutes
    enableRealTime = true,
    retryAttempts = 3,
    cacheEnabled = true
  } = options

  // State management
  const statisticStates = ref<Map<string, StatisticState>>(new Map())
  const isInitialized = ref(false)
  const globalError = ref<Error | null>(null)
  
  // Timers and cleanup functions
  const refreshTimer = ref<number>()
  const realtimeCleanup = ref<(() => void) | null>(null)

  // Initialize state for each statistic
  const initializeStates = () => {
    statistics.forEach(stat => {
      if (!statisticStates.value.has(stat.id)) {
        statisticStates.value.set(stat.id, {
          data: null,
          isLoading: false,
          error: null,
          lastUpdated: null,
          retryCount: 0
        })
      }
    })
  }

  // Computed properties
  const allStatistics = computed(() => {
    return Array.from(statisticStates.value.entries()).map(([id, state]) => ({
      id,
      ...state
    }))
  })

  const isAnyLoading = computed(() => {
    return Array.from(statisticStates.value.values()).some(state => state.isLoading)
  })

  const hasAnyError = computed(() => {
    return globalError.value !== null || 
           Array.from(statisticStates.value.values()).some(state => state.error !== null)
  })

  const loadingProgress = computed(() => {
    const total = statisticStates.value.size
    const loaded = Array.from(statisticStates.value.values()).filter(
      state => state.data !== null || state.error !== null
    ).length
    return total > 0 ? (loaded / total) * 100 : 0
  })

  // Get state for a specific statistic
  const getStatisticState = (id: string): StatisticState | null => {
    return statisticStates.value.get(id) || null
  }

  // Get value for a specific statistic
  const getStatisticValue = (id: string): number | null => {
    const state = statisticStates.value.get(id)
    return state?.data?.value || null
  }

  // Update state for a specific statistic
  const updateStatisticState = (id: string, updates: Partial<StatisticState>) => {
    const currentState = statisticStates.value.get(id)
    if (currentState) {
      statisticStates.value.set(id, { ...currentState, ...updates })
    }
  }

  // Load data for a single statistic
  const loadStatistic = async (statistic: StatisticCounter, retryCount = 0): Promise<void> => {
    const state = statisticStates.value.get(statistic.id)
    if (!state) return

    // Skip if already loading
    if (state.isLoading) return

    updateStatisticState(statistic.id, { 
      isLoading: true, 
      error: null,
      retryCount 
    })

    try {
      let data: StatisticData

      if (statistic.source === 'api' && statistic.apiEndpoint) {
        // Use the statistics service for API data
        data = await statisticsService.getStatistic(statistic.id, cacheEnabled)
      } else {
        // Use manual value
        data = {
          id: statistic.id,
          value: typeof statistic.value === 'number' ? statistic.value : 0,
          lastUpdated: new Date().toISOString(),
          source: 'manual'
        }
      }

      updateStatisticState(statistic.id, {
        data,
        isLoading: false,
        error: null,
        lastUpdated: new Date(),
        retryCount: 0
      })

    } catch (error) {
      const errorObj = error instanceof Error ? error : new Error(String(error))
      
      updateStatisticState(statistic.id, {
        isLoading: false,
        error: errorObj,
        retryCount
      })

      // Retry logic
      if (retryCount < retryAttempts) {
        const delay = Math.pow(2, retryCount) * 1000 // Exponential backoff
        setTimeout(() => {
          loadStatistic(statistic, retryCount + 1)
        }, delay)
      }
    }
  }

  // Load all statistics
  const loadAllStatistics = async (): Promise<void> => {
    globalError.value = null
    
    try {
      // Group statistics by source type
      const apiStatistics = statistics.filter(s => s.source === 'api' && s.apiEndpoint)
      const manualStatistics = statistics.filter(s => s.source !== 'api' || !s.apiEndpoint)

      // Load manual statistics immediately
      manualStatistics.forEach(stat => {
        const data: StatisticData = {
          id: stat.id,
          value: typeof stat.value === 'number' ? stat.value : 0,
          lastUpdated: new Date().toISOString(),
          source: 'manual'
        }

        updateStatisticState(stat.id, {
          data,
          isLoading: false,
          error: null,
          lastUpdated: new Date(),
          retryCount: 0
        })
      })

      // Load API statistics
      if (apiStatistics.length > 0) {
        // Try batch loading first
        try {
          const ids = apiStatistics.map(s => s.id)
          const results = await statisticsService.getStatistics(ids, cacheEnabled)
          
          results.forEach(data => {
            updateStatisticState(data.id, {
              data,
              isLoading: false,
              error: null,
              lastUpdated: new Date(),
              retryCount: 0
            })
          })
        } catch (batchError) {
          // Fall back to individual loading
          console.warn('Batch loading failed, falling back to individual requests:', batchError)
          await Promise.allSettled(
            apiStatistics.map(stat => loadStatistic(stat))
          )
        }
      }

    } catch (error) {
      globalError.value = error instanceof Error ? error : new Error(String(error))
    }
  }

  // Refresh all statistics
  const refresh = async (): Promise<void> => {
    // Clear cache if enabled
    if (cacheEnabled) {
      statisticsService.clearCache()
    }
    
    await loadAllStatistics()
  }

  // Retry failed statistics
  const retryFailed = async (): Promise<void> => {
    const failedStatistics = statistics.filter(stat => {
      const state = statisticStates.value.get(stat.id)
      return state?.error !== null
    })

    await Promise.allSettled(
      failedStatistics.map(stat => loadStatistic(stat))
    )
  }

  // Setup real-time updates
  const setupRealTimeUpdates = () => {
    if (!enableRealTime) return

    const apiStatistics = statistics.filter(s => s.source === 'api' && s.apiEndpoint)
    if (apiStatistics.length === 0) return

    const ids = apiStatistics.map(s => s.id)
    
    realtimeCleanup.value = statisticsService.subscribeToUpdates(ids, (data) => {
      updateStatisticState(data.id, {
        data,
        lastUpdated: new Date(),
        error: null
      })
    })
  }

  // Setup refresh timer
  const setupRefreshTimer = () => {
    if (refreshInterval <= 0) return

    refreshTimer.value = window.setInterval(async () => {
      if (!isAnyLoading.value) {
        await loadAllStatistics()
      }
    }, refreshInterval)
  }

  // Watch for changes in statistics array
  watch(() => statistics, (newStats) => {
    // Remove states for statistics that are no longer present
    const currentIds = new Set(newStats.map(s => s.id))
    const statesToRemove = Array.from(statisticStates.value.keys())
      .filter(id => !currentIds.has(id))
    
    statesToRemove.forEach(id => {
      statisticStates.value.delete(id)
    })

    // Initialize states for new statistics
    initializeStates()

    // Reload data
    if (isInitialized.value) {
      loadAllStatistics()
    }
  }, { deep: true })

  // Initialize
  onMounted(async () => {
    initializeStates()
    await loadAllStatistics()
    setupRealTimeUpdates()
    setupRefreshTimer()
    isInitialized.value = true
  })

  // Cleanup
  onUnmounted(() => {
    if (refreshTimer.value) {
      clearInterval(refreshTimer.value)
    }
    
    if (realtimeCleanup.value) {
      realtimeCleanup.value()
    }
  })

  return {
    // State
    statisticStates: computed(() => statisticStates.value),
    allStatistics,
    isAnyLoading,
    hasAnyError,
    globalError: computed(() => globalError.value),
    loadingProgress,
    isInitialized: computed(() => isInitialized.value),

    // Methods
    getStatisticState,
    getStatisticValue,
    loadStatistic,
    loadAllStatistics,
    refresh,
    retryFailed,

    // Utilities
    formatError: (error: unknown) => statisticsService.formatError(error)
  }
}

// Utility composable for a single statistic
export function useStatistic(statistic: StatisticCounter, options: UseStatisticsOptions = {}) {
  const { statisticStates, getStatisticState, getStatisticValue, loadStatistic } = useStatistics([statistic], options)

  return {
    state: computed(() => getStatisticState(statistic.id)),
    value: computed(() => getStatisticValue(statistic.id)),
    isLoading: computed(() => getStatisticState(statistic.id)?.isLoading || false),
    error: computed(() => getStatisticState(statistic.id)?.error || null),
    lastUpdated: computed(() => getStatisticState(statistic.id)?.lastUpdated || null),
    retryCount: computed(() => getStatisticState(statistic.id)?.retryCount || 0),
    reload: () => loadStatistic(statistic)
  }
}