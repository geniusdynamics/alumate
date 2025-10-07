import axios from 'axios';
import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import type {
    AttributionTouch,
    AttributionReport,
    AttributionSummary,
    TrackTouchData,
    AttributionFilters,
    AttributionTouchListResponse,
    AttributionReportResponse,
    AttributionSummaryResponse
} from '../Types/analytics';

export const useAttributionStore = defineStore('attribution', () => {
    // State
    const touches = ref<AttributionTouch[]>([]);
    const attributionReport = ref<AttributionReport | null>(null);
    const summary = ref<AttributionSummary | null>(null);
    const touchHistory = ref<AttributionTouch[]>([]);
    const loading = ref(false);
    const error = ref('');
    const filters = ref<AttributionFilters>({
        user_id: undefined,
        source: undefined,
        start_date: undefined,
        end_date: undefined,
        model: 'last_touch',
    });

    // Pagination
    const pagination = ref({
        current_page: 1,
        per_page: 50,
        total: 0,
        last_page: 1,
    });

    // Getters
    const isLoading = computed(() => loading.value);
    const hasError = computed(() => !!error.value);
    const hasTouches = computed(() => touches.value.length > 0);
    const hasReport = computed(() => !!attributionReport.value);
    const hasSummary = computed(() => !!summary.value);

    // Actions
    const fetchTouches = async (page = 1): Promise<AttributionTouchListResponse> => {
        loading.value = true;
        error.value = '';

        try {
            const params = new URLSearchParams({
                page: page.toString(),
                per_page: pagination.value.per_page.toString(),
            });

            if (filters.value.user_id) params.append('user_id', filters.value.user_id);
            if (filters.value.source) params.append('source', filters.value.source);
            if (filters.value.start_date) params.append('start_date', filters.value.start_date);
            if (filters.value.end_date) params.append('end_date', filters.value.end_date);

            const response = await axios.get<AttributionTouchListResponse>(`/api/analytics/attribution?${params}`);
            const data = response.data;

            if (data.success) {
                touches.value = data.data;
                pagination.value = data.pagination;
                if (data.attribution) {
                    attributionReport.value = data.attribution;
                }
            }

            return data;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to fetch attribution touches';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const fetchAttribution = async (userId?: string, model?: string): Promise<AttributionReportResponse> => {
        loading.value = true;
        error.value = '';

        try {
            const targetUserId = userId || filters.value.user_id;
            const attributionModel = model || filters.value.model;

            if (!targetUserId) {
                throw new Error('User ID is required');
            }

            const params = new URLSearchParams();
            if (filters.value.start_date) params.append('start_date', filters.value.start_date);
            if (filters.value.end_date) params.append('end_date', filters.value.end_date);
            if (attributionModel) params.append('model', attributionModel);

            const response = await axios.get<AttributionReportResponse>(`/api/analytics/attribution/${targetUserId}?${params}`);
            const data = response.data;

            if (data.success) {
                attributionReport.value = data.data.attribution;
                touchHistory.value = data.data.touch_history;
            }

            return data;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to fetch attribution report';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const trackTouch = async (touchData: TrackTouchData): Promise<AttributionTouch> => {
        loading.value = true;
        error.value = '';

        try {
            const response = await axios.post<{ success: boolean; data: AttributionTouch; message: string }>(
                '/api/analytics/attribution/touches',
                touchData
            );

            if (response.data.success) {
                // Add to touches list if it matches current filters
                const newTouch = response.data.data;
                if (shouldIncludeTouch(newTouch)) {
                    touches.value.unshift(newTouch);
                    pagination.value.total++;
                }

                return newTouch;
            }

            throw new Error(response.data.message || 'Failed to track touch');
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to track attribution touch';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const fetchSummary = async (userIds: string[]): Promise<AttributionSummaryResponse> => {
        loading.value = true;
        error.value = '';

        try {
            const params = new URLSearchParams();
            userIds.forEach(id => params.append('user_ids[]', id));
            if (filters.value.start_date) params.append('start_date', filters.value.start_date);
            if (filters.value.end_date) params.append('end_date', filters.value.end_date);
            if (filters.value.model) params.append('model', filters.value.model);

            const response = await axios.get<AttributionSummaryResponse>(`/api/analytics/attribution-summary?${params}`);
            const data = response.data;

            if (data.success) {
                summary.value = data.data;
            }

            return data;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to fetch attribution summary';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const updateFilters = (newFilters: Partial<AttributionFilters>) => {
        filters.value = { ...filters.value, ...newFilters };
    };

    const setUser = (userId: string) => {
        filters.value.user_id = userId;
    };

    const setDateRange = (startDate: string, endDate: string) => {
        filters.value.start_date = startDate;
        filters.value.end_date = endDate;
    };

    const setModel = (model: 'last_touch' | 'first_touch' | 'linear' | 'time_decay') => {
        filters.value.model = model;
    };

    const setSource = (source?: string) => {
        filters.value.source = source;
    };

    const clearData = () => {
        touches.value = [];
        attributionReport.value = null;
        summary.value = null;
        touchHistory.value = [];
        error.value = '';
        pagination.value = {
            current_page: 1,
            per_page: 50,
            total: 0,
            last_page: 1,
        };
    };

    const refreshData = async () => {
        try {
            if (filters.value.user_id) {
                await Promise.all([
                    fetchTouches(pagination.value.current_page),
                    fetchAttribution(),
                ]);
            } else {
                await fetchTouches(pagination.value.current_page);
            }
        } catch (err) {
            console.error('Failed to refresh attribution data:', err);
        }
    };

    // Helper function to check if a touch should be included based on current filters
    const shouldIncludeTouch = (touch: AttributionTouch): boolean => {
        if (filters.value.user_id && touch.user_id !== filters.value.user_id) {
            return false;
        }

        if (filters.value.source && touch.source !== filters.value.source) {
            return false;
        }

        if (filters.value.start_date && touch.timestamp < filters.value.start_date) {
            return false;
        }

        if (filters.value.end_date && touch.timestamp > filters.value.end_date) {
            return false;
        }

        return true;
    };

    // Initialize store
    const initialize = async () => {
        try {
            await fetchTouches();
        } catch (err) {
            console.error('Failed to initialize attribution store:', err);
        }
    };

    return {
        // State
        touches,
        attributionReport,
        summary,
        touchHistory,
        loading,
        error,
        filters,
        pagination,

        // Getters
        isLoading,
        hasError,
        hasTouches,
        hasReport,
        hasSummary,

        // Actions
        fetchTouches,
        fetchAttribution,
        trackTouch,
        fetchSummary,
        updateFilters,
        setUser,
        setDateRange,
        setModel,
        setSource,
        clearData,
        refreshData,
        initialize,
    };
});