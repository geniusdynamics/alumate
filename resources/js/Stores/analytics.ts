import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import type {
    TemplateMetrics,
    LandingPageMetrics,
    BrandMetrics,
    TemplateABTest,
    TemplateABTestResult,
    TemplateAnalyticsFilters,
} from '@/Types';

export const useAnalyticsStore = defineStore('analytics', () => {
    const templateMetrics = ref<TemplateMetrics | null>(null);
    const landingPageMetrics = ref<LandingPageMetrics | null>(null);
    const brandMetrics = ref<BrandMetrics | null>(null);
    const abTests = ref<TemplateABTest[]>([]);
    const abTestResults = ref<TemplateABTestResult | null>(null);
    const loading = ref(false);
    const error = ref<string | null>(null);

    const hasTemplateData = computed(() => templateMetrics.value !== null);
    const hasLandingPageData = computed(() => landingPageMetrics.value !== null);
    const hasBrandData = computed(() => brandMetrics.value !== null);

    async function fetchTemplateAnalytics(templateId: number, filters: TemplateAnalyticsFilters = {}) {
        loading.value = true;
        error.value = null;
        try {
            const params = new URLSearchParams();
            Object.entries(filters).forEach(([key, value]) => {
                if (value !== undefined && value !== null) {
                    if (typeof value === 'object') {
                        params.append(key, JSON.stringify(value));
                    } else {
                        params.append(key, String(value));
                    }
                }
            });

            const response = await fetch(`/api/analytics/templates/${templateId}?${params.toString()}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) throw new Error('Failed to fetch template analytics');

            templateMetrics.value = await response.json();
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Unknown error';
        } finally {
            loading.value = false;
        }
    }

    async function fetchLandingPageAnalytics(pageId: number, filters: TemplateAnalyticsFilters = {}) {
        loading.value = true;
        error.value = null;
        try {
            const params = new URLSearchParams();
            Object.entries(filters).forEach(([key, value]) => {
                if (value !== undefined && value !== null) {
                    if (typeof value === 'object') {
                        params.append(key, JSON.stringify(value));
                    } else {
                        params.append(key, String(value));
                    }
                }
            });

            const response = await fetch(`/api/analytics/landing-pages/${pageId}?${params.toString()}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) throw new Error('Failed to fetch landing page analytics');

            landingPageMetrics.value = await response.json();
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Unknown error';
        } finally {
            loading.value = false;
        }
    }

    async function fetchBrandAnalytics() {
        loading.value = true;
        error.value = null;
        try {
            const response = await fetch('/api/analytics/brand', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) throw new Error('Failed to fetch brand analytics');

            brandMetrics.value = await response.json();
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Unknown error';
        } finally {
            loading.value = false;
        }
    }

    async function fetchABTests() {
        loading.value = true;
        error.value = null;
        try {
            const response = await fetch('/api/ab-tests', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) throw new Error('Failed to fetch A/B tests');

            abTests.value = await response.json();
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Unknown error';
        } finally {
            loading.value = false;
        }
    }

    async function fetchABTestResults(testId: number) {
        loading.value = true;
        error.value = null;
        try {
            const response = await fetch(`/api/ab-tests/${testId}/results`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) throw new Error('Failed to fetch A/B test results');

            abTestResults.value = await response.json();
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Unknown error';
        } finally {
            loading.value = false;
        }
    }

    async function trackTemplateUsage(templateId: number, context: string = 'view') {
        try {
            await fetch(`/api/analytics/templates/${templateId}/track`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ context }),
            });
        } catch {
            // Silently fail for tracking - not critical
        }
    }

    async function trackConversion(pageId: number, type: string = 'form_submit') {
        try {
            await fetch(`/api/analytics/landing-pages/${pageId}/convert`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ type }),
            });
        } catch {
            // Silently fail for tracking - not critical
        }
    }

    function clearError() {
        error.value = null;
    }

    return {
        templateMetrics,
        landingPageMetrics,
        brandMetrics,
        abTests,
        abTestResults,
        loading,
        error,
        hasTemplateData,
        hasLandingPageData,
        hasBrandData,
        fetchTemplateAnalytics,
        fetchLandingPageAnalytics,
        fetchBrandAnalytics,
        fetchABTests,
        fetchABTestResults,
        trackTemplateUsage,
        trackConversion,
        clearError,
    };
});
