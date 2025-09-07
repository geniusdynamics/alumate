/**
 * Statistics Service for real-time data integration
 * Handles API calls, caching, and error management for statistics counters
 */

export interface StatisticData {
  id: string
  value: number
  lastUpdated: string
  source: 'api' | 'manual'
  metadata?: Record<string, unknown>
}

export interface StatisticsResponse {
  data: StatisticData[]
  timestamp: string
  success: boolean
  errors?: string[]
}

export interface CacheEntry {
  data: StatisticData
  timestamp: number
  expiresAt: number
}

class StatisticsService {
  private cache = new Map<string, CacheEntry>()
  private readonly defaultCacheDuration = 5 * 60 * 1000 // 5 minutes
  private readonly baseUrl = '/api/statistics'

  /**
   * Fetch a single statistic by ID
   */
  async getStatistic(id: string, useCache = true): Promise<StatisticData> {
    // Check cache first
    if (useCache) {
      const cached = this.getCachedData(id)
      if (cached) {
        return cached
      }
    }

    try {
      const response = await fetch(`${this.baseUrl}/${id}`, {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      })

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`)
      }

      const result = await response.json()
      
      if (!result.success) {
        throw new Error(result.errors?.join(', ') || 'Unknown error occurred')
      }

      const data = result.data
      
      // Validate data structure
      if (!this.isValidStatisticData(data)) {
        throw new Error('Invalid data format received from API')
      }

      // Cache the result
      this.setCachedData(id, data)

      return data
    } catch (error) {
      console.error(`Failed to fetch statistic ${id}:`, error)
      throw error
    }
  }

  /**
   * Fetch multiple statistics by IDs
   */
  async getStatistics(ids: string[], useCache = true): Promise<StatisticData[]> {
    const results: StatisticData[] = []
    const uncachedIds: string[] = []

    // Check cache for each ID
    if (useCache) {
      for (const id of ids) {
        const cached = this.getCachedData(id)
        if (cached) {
          results.push(cached)
        } else {
          uncachedIds.push(id)
        }
      }
    } else {
      uncachedIds.push(...ids)
    }

    // Fetch uncached data
    if (uncachedIds.length > 0) {
      try {
        const response = await fetch(`${this.baseUrl}/batch`, {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify({ ids: uncachedIds })
        })

        if (!response.ok) {
          throw new Error(`HTTP ${response.status}: ${response.statusText}`)
        }

        const result: StatisticsResponse = await response.json()
        
        if (!result.success) {
          throw new Error(result.errors?.join(', ') || 'Unknown error occurred')
        }

        // Validate and cache each result
        for (const data of result.data) {
          if (this.isValidStatisticData(data)) {
            results.push(data)
            this.setCachedData(data.id, data)
          }
        }
      } catch (error) {
        console.error('Failed to fetch statistics batch:', error)
        throw error
      }
    }

    // Sort results to match original order
    return ids.map(id => results.find(r => r.id === id)).filter(Boolean) as StatisticData[]
  }

  /**
   * Get platform metrics (commonly used statistics)
   */
  async getPlatformMetrics(): Promise<Record<string, number>> {
    try {
      const response = await fetch(`${this.baseUrl}/platform-metrics`, {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      })

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`)
      }

      const result = await response.json()
      
      if (!result.success) {
        throw new Error(result.errors?.join(', ') || 'Unknown error occurred')
      }

      return result.data
    } catch (error) {
      console.error('Failed to fetch platform metrics:', error)
      throw error
    }
  }

  /**
   * Subscribe to real-time updates via WebSocket (if available)
   */
  subscribeToUpdates(statisticIds: string[], callback: (data: StatisticData) => void): () => void {
    // Check if WebSocket is available
    if (typeof window === 'undefined' || !window.WebSocket) {
      console.warn('WebSocket not available, falling back to polling')
      return () => {} // Return empty cleanup function
    }

    try {
      const wsUrl = `${window.location.protocol === 'https:' ? 'wss:' : 'ws:'}//${window.location.host}/ws/statistics`
      const ws = new WebSocket(wsUrl)

      ws.onopen = () => {
        // Subscribe to specific statistics
        ws.send(JSON.stringify({
          type: 'subscribe',
          statisticIds
        }))
      }

      ws.onmessage = (event) => {
        try {
          const data = JSON.parse(event.data)
          if (data.type === 'statistic_update' && this.isValidStatisticData(data.data)) {
            // Update cache
            this.setCachedData(data.data.id, data.data)
            // Notify callback
            callback(data.data)
          }
        } catch (error) {
          console.error('Error parsing WebSocket message:', error)
        }
      }

      ws.onerror = (error) => {
        console.error('WebSocket error:', error)
      }

      // Return cleanup function
      return () => {
        if (ws.readyState === WebSocket.OPEN) {
          ws.send(JSON.stringify({
            type: 'unsubscribe',
            statisticIds
          }))
        }
        ws.close()
      }
    } catch (error) {
      console.error('Failed to establish WebSocket connection:', error)
      return () => {}
    }
  }

  /**
   * Clear cache for specific statistic or all statistics
   */
  clearCache(id?: string): void {
    if (id) {
      this.cache.delete(id)
    } else {
      this.cache.clear()
    }
  }

  /**
   * Get cached data if available and not expired
   */
  private getCachedData(id: string): StatisticData | null {
    const entry = this.cache.get(id)
    if (!entry) {
      return null
    }

    if (Date.now() > entry.expiresAt) {
      this.cache.delete(id)
      return null
    }

    return entry.data
  }

  /**
   * Cache data with expiration
   */
  private setCachedData(id: string, data: StatisticData, cacheDuration?: number): void {
    const duration = cacheDuration || this.defaultCacheDuration
    const entry: CacheEntry = {
      data,
      timestamp: Date.now(),
      expiresAt: Date.now() + duration
    }
    this.cache.set(id, entry)
  }

  /**
   * Validate statistic data structure
   */
  private isValidStatisticData(data: unknown): data is StatisticData {
    if (!data || typeof data !== 'object') {
      return false
    }

    const obj = data as Record<string, unknown>
    
    return (
      typeof obj.id === 'string' &&
      typeof obj.value === 'number' &&
      !isNaN(obj.value) &&
      typeof obj.lastUpdated === 'string' &&
      (obj.source === 'api' || obj.source === 'manual')
    )
  }

  /**
   * Format error messages for user display
   */
  formatError(error: unknown): string {
    if (error instanceof Error) {
      return error.message
    }
    
    if (typeof error === 'string') {
      return error
    }
    
    return 'An unexpected error occurred'
  }

  /**
   * Check if the service is available (network connectivity)
   */
  async checkAvailability(): Promise<boolean> {
    try {
      const response = await fetch(`${this.baseUrl}/health`, {
        method: 'GET',
        headers: {
          'Accept': 'application/json'
        }
      })
      return response.ok
    } catch {
      return false
    }
  }
}

// Export singleton instance
export const statisticsService = new StatisticsService()

// Export utility functions
export const formatStatisticValue = (
  value: number, 
  locale = 'en-US',
  options: Intl.NumberFormatOptions = {}
): string => {
  try {
    return new Intl.NumberFormat(locale, {
      notation: 'compact',
      compactDisplay: 'short',
      maximumFractionDigits: 1,
      ...options
    }).format(value)
  } catch (error) {
    console.warn('Locale not supported, using fallback formatting:', error)
    // Fallback formatting
    if (value >= 1000000000) {
      return `${(value / 1000000000).toFixed(1)}B`
    } else if (value >= 1000000) {
      return `${(value / 1000000).toFixed(1)}M`
    } else if (value >= 1000) {
      return `${(value / 1000).toFixed(1)}K`
    }
    return value.toString()
  }
}

export const createStatisticEndpoint = (id: string): string => {
  return `/api/statistics/${encodeURIComponent(id)}`
}