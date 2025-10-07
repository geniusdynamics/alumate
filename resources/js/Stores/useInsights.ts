import { defineStore } from 'pinia';
import { ref } from 'vue';
import type { Insight } from '@/Types/analytics';

/**
 * Pinia store for managing analytics insights
 * 
 * This store handles the state management for analytics insights, including
 * fetching, generating, and tracking feedback for insights.
 */
export const useInsights = defineStore('insights', () => {
  // State
  const insights = ref<Insight[]>([]);
  const loading = ref(false);
  const error = ref<string | null>(null);
  const pagination = ref({
    current_page: 1,
    per_page: 20,
    total: 0,
    last_page: 1,
  });
  const filters = ref({
    period: 'last_30_days',
    type: '',
    metric: '',
    severity: '',
  });

  /**
   * Fetch insights from the API
   */
  const fetchInsights = async (params: Record<string, any> = {}): Promise<Insight[]> => {
    loading.value = true;
    error.value = null;
    
    try {
      const queryParams = new URLSearchParams({
        ...filters.value,
        ...params,
        page: params.page || pagination.value.current_page,
        limit: params.limit || pagination.value.per_page,
      }).toString();
      
      const response = await fetch(`/api/analytics/insights?${queryParams}`, {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('api_token')}`,
          'Content-Type': 'application/json',
        },
      });

      if (!response.ok) {
        throw new Error(`Failed to fetch insights: ${response.status} ${response.statusText}`);
      }

      const data = await response.json();
      
      // Update pagination
      if (data.pagination) {
        pagination.value = data.pagination;
      }
      
      // Update filters
      if (data.filters) {
        filters.value = { ...filters.value, ...data.filters };
      }
      
      insights.value = data.data || [];
      return insights.value;
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Failed to fetch insights';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  /**
   * Generate new insights
   */
  const generateInsights = async (options: Record<string, any> = {}): Promise<any> => {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await fetch('/api/analytics/insights/generate', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('api_token')}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(options),
      });

      if (!response.ok) {
        throw new Error(`Failed to generate insights: ${response.status} ${response.statusText}`);
      }

      const data = await response.json();
      
      // If not queued, refresh insights
      if (response.status === 200) {
        await fetchInsights();
      }
      
      return data;
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Failed to generate insights';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  /**
   * Track feedback for a specific insight
   */
  const trackFeedback = async (insightId: string, effectivenessScore: number, metadata: Record<string, any> = {}): Promise<any> => {
    loading.value = true;
    error.value = null;
    
    try {
      const response = await fetch(`/api/analytics/insights/${insightId}/feedback`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('api_token')}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          effectiveness_score: effectivenessScore,
          ...metadata
        }),
      });

      if (!response.ok) {
        throw new Error(`Failed to track feedback: ${response.status} ${response.statusText}`);
      }

      const data = await response.json();
      
      // Update the local insight with the effectiveness score
      const index = insights.value.findIndex(i => i.id === insightId);
      if (index !== -1) {
        insights.value[index].effectiveness = effectivenessScore;
      }
      
      return data;
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Failed to track feedback';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  /**
   * Get insights by type
   */
  const getInsightsByType = (type: string): Insight[] => {
    return insights.value.filter(insight => insight.type === type);
  };

  /**
   * Get insights by metric
   */
  const getInsightsByMetric = (metric: string): Insight[] => {
    return insights.value.filter(insight => insight.metric === metric);
  };

  /**
   * Get recommendations
   */
  const getRecommendations = (): Insight[] => {
    return insights.value.filter(insight => insight.type === 'recommendation');
  };

  /**
   * Get trends
   */
  const getTrends = (): Insight[] => {
    return insights.value.filter(insight => insight.type === 'trend');
  };

  /**
   * Get anomalies
   */
  const getAnomalies = (): Insight[] => {
    return insights.value.filter(insight => insight.type === 'anomaly');
  };

  /**
   * Reset store
   */
  const reset = (): void => {
    insights.value = [];
    loading.value = false;
    error.value = null;
    pagination.value = {
      current_page: 1,
      per_page: 20,
      total: 0,
      last_page: 1,
    };
    filters.value = {
      period: 'last_30_days',
      type: '',
      metric: '',
      severity: '',
    };
  };

  return {
    // State
    insights: insights,
    loading: loading,
    error: error,
    pagination: pagination,
    filters: filters,
    
    // Getters
    getInsightsByType,
    getInsightsByMetric,
    getRecommendations,
    getTrends,
    getAnomalies,
    
    // Actions
    fetchInsights,
    generateInsights,
    trackFeedback,
    reset
  };
});