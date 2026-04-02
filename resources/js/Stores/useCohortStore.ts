import axios from 'axios';
import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import type {
    CohortData,
    CohortComparisonData,
    CohortFilterOptions,
    CohortApiResponse
} from '../types/analytics';

export const useCohortStore = defineStore('cohort', () => {
    // State
    const cohortData = ref<CohortData | null>(null);
    const comparisonData = ref<CohortComparisonData | null>(null);
    const availableCohorts = ref<CohortData[]>([]);
    const loading = ref(false);
    const error = ref('');
    const filters = ref<CohortFilterOptions>({
        dateRange: { from: '', to: '' },
        metrics: ['retention', 'engagement', 'conversion'],
        cohortIds: [],
        comparisonMode: false,
    });

    // Getters
    const isLoading = computed(() => loading.value);
    const hasError = computed(() => !!error.value);
    const hasCohortData = computed(() => !!cohortData.value);
    const hasComparisonData = computed(() => !!comparisonData.value);
    const selectedCohorts = computed(() =>
        availableCohorts.value.filter(cohort => filters.value.cohortIds.includes(cohort.id))
    );

    // Actions
    const fetchCohort = async (cohortId: string): Promise<CohortData> => {
        loading.value = true;
        error.value = '';

        try {
            const response = await axios.get<CohortApiResponse>(`/api/analytics/cohorts/${cohortId}`);
            const data = response.data.data as CohortData;
            cohortData.value = data;

            // Update available cohorts if not already present
            const existingIndex = availableCohorts.value.findIndex(c => c.id === cohortId);
            if (existingIndex === -1) {
                availableCohorts.value.push(data);
            } else {
                availableCohorts.value[existingIndex] = data;
            }

            return data;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to fetch cohort data';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const fetchComparison = async (cohortIds: string[]): Promise<CohortComparisonData> => {
        loading.value = true;
        error.value = '';

        try {
            const params = new URLSearchParams();
            cohortIds.forEach(id => params.append('cohort_ids[]', id));

            const response = await axios.get<CohortApiResponse>(`/api/analytics/cohorts/compare?${params}`);
            const data = response.data.data as CohortComparisonData;
            comparisonData.value = data;

            return data;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to fetch cohort comparison';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const createCohort = async (cohortData: { name: string; criteria: Record<string, any> }): Promise<CohortData> => {
        loading.value = true;
        error.value = '';

        try {
            const response = await axios.post<CohortApiResponse>('/api/analytics/cohorts', cohortData);
            const data = response.data.data as CohortData;

            // Add to available cohorts
            availableCohorts.value.push(data);

            return data;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to create cohort';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const fetchCohortInsights = async (cohortId: string) => {
        try {
            const response = await axios.get(`/api/analytics/cohorts/${cohortId}/insights`);
            return response.data.data;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to fetch cohort insights';
            throw err;
        }
    };

    const listCohorts = async (filters: Partial<CohortFilterOptions> = {}): Promise<CohortData[]> => {
        loading.value = true;
        error.value = '';

        try {
            const params = new URLSearchParams();

            if (filters.dateRange?.from) params.append('date_from', filters.dateRange.from);
            if (filters.dateRange?.to) params.append('date_to', filters.dateRange.to);

            const response = await axios.get(`/api/analytics/cohorts?${params}`);
            const cohorts = response.data.data || [];
            availableCohorts.value = cohorts;

            return cohorts;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to fetch cohorts list';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const updateFilters = (newFilters: Partial<CohortFilterOptions>) => {
        filters.value = { ...filters.value, ...newFilters };
    };

    const toggleCohortSelection = (cohortId: string) => {
        const index = filters.value.cohortIds.indexOf(cohortId);
        if (index === -1) {
            filters.value.cohortIds.push(cohortId);
        } else {
            filters.value.cohortIds.splice(index, 1);
        }
    };

    const setComparisonMode = (enabled: boolean) => {
        filters.value.comparisonMode = enabled;
        if (!enabled && filters.value.cohortIds.length > 1) {
            // Keep only the first cohort when disabling comparison mode
            filters.value.cohortIds = filters.value.cohortIds.slice(0, 1);
        }
    };

    const clearData = () => {
        cohortData.value = null;
        comparisonData.value = null;
        error.value = '';
    };

    const refreshData = async () => {
        if (filters.value.comparisonMode && filters.value.cohortIds.length > 1) {
            await fetchComparison(filters.value.cohortIds);
        } else if (filters.value.cohortIds.length === 1) {
            await fetchCohort(filters.value.cohortIds[0]);
        }
    };

    // Initialize store with default data
    const initialize = async () => {
        try {
            await listCohorts();
        } catch (err) {
            console.error('Failed to initialize cohort store:', err);
        }
    };

    return {
        // State
        cohortData,
        comparisonData,
        availableCohorts,
        loading,
        error,
        filters,

        // Getters
        isLoading,
        hasError,
        hasCohortData,
        hasComparisonData,
        selectedCohorts,

        // Actions
        fetchCohort,
        fetchComparison,
        createCohort,
        fetchCohortInsights,
        listCohorts,
        updateFilters,
        toggleCohortSelection,
        setComparisonMode,
        clearData,
        refreshData,
        initialize,
    };
});