import axios from 'axios';
import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import type {
    CustomEventDefinition,
    CustomEvent,
    CustomEventAnalytics,
    DefineEventData,
    TrackEventData,
    CustomEventListResponse,
    CustomEventAnalyticsResponse,
    OptimizationSuggestion,
    OptimizationResponse
} from '../types/analytics';

export const useCustomEventStore = defineStore('customEvent', () => {
    // State
    const definitions = ref<CustomEventDefinition[]>([]);
    const events = ref<CustomEvent[]>([]);
    const analyticsData = ref<CustomEventAnalytics | null>(null);
    const loading = ref(false);
    const error = ref('');

    // Pagination
    const pagination = ref({
        current_page: 1,
        per_page: 20,
        total: 0,
        last_page: 1,
    });

    // Getters
    const isLoading = computed(() => loading.value);
    const hasError = computed(() => !!error.value);
    const hasDefinitions = computed(() => definitions.value.length > 0);
    const hasAnalytics = computed(() => !!analyticsData.value);

    // Actions
    const loadDefinitions = async (page = 1): Promise<CustomEventListResponse> => {
        loading.value = true;
        error.value = '';

        try {
            const params = new URLSearchParams({
                page: page.toString(),
                per_page: pagination.value.per_page.toString(),
            });

            const response = await axios.get<CustomEventListResponse>(`/api/analytics/custom-events/definitions?${params}`);
            const data = response.data;

            if (data.success) {
                definitions.value = data.data;
                pagination.value = data.pagination;
                
                // Add aggregate data for each definition
                definitions.value.forEach(definition => {
                    if (!definition.aggregates) {
                        loadAnalytics(definition.id);
                    }
                });
            }

            return data;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to load custom event definitions';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const defineEvent = async (eventData: DefineEventData): Promise<CustomEventDefinition> => {
        loading.value = true;
        error.value = '';

        try {
            const response = await axios.post<{ success: boolean; data: CustomEventDefinition; message: string }>(
                '/api/analytics/custom-events/definitions',
                eventData
            );

            if (response.data.success) {
                // Add to definitions list
                definitions.value.push(response.data.data);
                pagination.value.total++;

                return response.data.data;
            }

            throw new Error(response.data.message || 'Failed to define custom event');
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to define custom event';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const trackEvent = async (eventData: TrackEventData): Promise<CustomEvent> => {
        loading.value = true;
        error.value = '';

        try {
            const response = await axios.post<{ success: boolean; data: CustomEvent; message: string }>(
                '/api/analytics/custom-events/track',
                eventData
            );

            if (response.data.success) {
                // Add to events list
                events.value.push(response.data.data);

                return response.data.data;
            }

            throw new Error(response.data.message || 'Failed to track custom event');
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to track custom event';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const loadAnalytics = async (definitionId: number): Promise<CustomEventAnalyticsResponse> => {
        loading.value = true;
        error.value = '';

        try {
            const response = await axios.get<CustomEventAnalyticsResponse>(`/api/analytics/custom-events/${definitionId}/analytics`);
            const data = response.data;

            if (data.success) {
                analyticsData.value = data.data;
                
                // Update definition with analytics data
                const definition = definitions.value.find(d => d.id === definitionId);
                if (definition) {
                    definition.aggregates = data.data;
                }
            }

            return data;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to load custom event analytics';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const loadOptimizationSuggestions = async (definitionId: number): Promise<OptimizationResponse> => {
        loading.value = true;
        error.value = '';

        try {
            const response = await axios.get<OptimizationResponse>(`/api/analytics/custom-events/${definitionId}/optimization`);
            const data = response.data;

            if (data.success) {
                return data;
            }

            throw new Error('Failed to load optimization suggestions');
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to load optimization suggestions';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const clearData = () => {
        definitions.value = [];
        events.value = [];
        analyticsData.value = null;
        error.value = '';
        pagination.value = {
            current_page: 1,
            per_page: 20,
            total: 0,
            last_page: 1,
        };
    };

    const refreshData = async () => {
        try {
            await loadDefinitions(pagination.value.current_page);
        } catch (err) {
            console.error('Failed to refresh custom event data:', err);
        }
    };

    return {
        // State
        definitions,
        events,
        analyticsData,
        loading,
        error,
        pagination,

        // Getters
        isLoading,
        hasError,
        hasDefinitions,
        hasAnalytics,

        // Actions
        loadDefinitions,
        defineEvent,
        trackEvent,
        loadAnalytics,
        loadOptimizationSuggestions,
        clearData,
        refreshData,
    };
});