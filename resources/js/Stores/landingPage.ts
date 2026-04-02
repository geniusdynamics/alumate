import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import type {
    LandingPage,
    LandingPageFilters,
    LandingPageCreateData,
    LandingPagePublishOptions,
    PaginatedResponse,
} from '@/Types';

export const useLandingPageStore = defineStore('landingPage', () => {
    const landingPages = ref<LandingPage[]>([]);
    const currentPage = ref<LandingPage | null>(null);
    const selectedPage = ref<LandingPage | null>(null);
    const loading = ref(false);
    const error = ref<string | null>(null);
    const pagination = ref({
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 0,
    });

    const hasPages = computed(() => landingPages.value.length > 0);
    const publishedPages = computed(() => landingPages.value.filter(p => p.status === 'published'));
    const draftPages = computed(() => landingPages.value.filter(p => p.status === 'draft'));

    async function fetchLandingPages(filters: LandingPageFilters = {}) {
        loading.value = true;
        error.value = null;
        try {
            const params = new URLSearchParams();
            Object.entries(filters).forEach(([key, value]) => {
                if (value !== undefined && value !== null) {
                    params.append(key, String(value));
                }
            });

            const response = await fetch(`/api/landing-pages?${params.toString()}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) throw new Error('Failed to fetch landing pages');

            const data = await response.json();
            landingPages.value = data.data ?? data;
            if (data.meta) {
                pagination.value = data.meta;
            }
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Unknown error';
        } finally {
            loading.value = false;
        }
    }

    async function fetchLandingPage(id: number) {
        loading.value = true;
        error.value = null;
        try {
            const response = await fetch(`/api/landing-pages/${id}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) throw new Error('Failed to fetch landing page');

            currentPage.value = await response.json();
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Unknown error';
        } finally {
            loading.value = false;
        }
    }

    async function createLandingPage(data: LandingPageCreateData): Promise<LandingPage | null> {
        loading.value = true;
        error.value = null;
        try {
            const response = await fetch('/api/landing-pages', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(data),
            });

            if (!response.ok) {
                const errData = await response.json();
                throw new Error(errData.message ?? 'Failed to create landing page');
            }

            const page = await response.json();
            landingPages.value.unshift(page);
            return page;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Unknown error';
            return null;
        } finally {
            loading.value = false;
        }
    }

    async function updateLandingPage(id: number, data: Partial<LandingPageCreateData>): Promise<LandingPage | null> {
        loading.value = true;
        error.value = null;
        try {
            const response = await fetch(`/api/landing-pages/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(data),
            });

            if (!response.ok) throw new Error('Failed to update landing page');

            const page = await response.json();
            const index = landingPages.value.findIndex(p => p.id === id);
            if (index !== -1) {
                landingPages.value[index] = page;
            }
            if (currentPage.value?.id === id) {
                currentPage.value = page;
            }
            return page;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Unknown error';
            return null;
        } finally {
            loading.value = false;
        }
    }

    async function deleteLandingPage(id: number): Promise<boolean> {
        loading.value = true;
        error.value = null;
        try {
            const response = await fetch(`/api/landing-pages/${id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) throw new Error('Failed to delete landing page');

            landingPages.value = landingPages.value.filter(p => p.id !== id);
            if (currentPage.value?.id === id) currentPage.value = null;
            if (selectedPage.value?.id === id) selectedPage.value = null;
            return true;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Unknown error';
            return false;
        } finally {
            loading.value = false;
        }
    }

    async function publishLandingPage(id: number, options: LandingPagePublishOptions = {}): Promise<boolean> {
        loading.value = true;
        error.value = null;
        try {
            const response = await fetch(`/api/landing-pages/${id}/publish`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(options),
            });

            if (!response.ok) throw new Error('Failed to publish landing page');

            const page = await response.json();
            const index = landingPages.value.findIndex(p => p.id === id);
            if (index !== -1) landingPages.value[index] = page;
            if (currentPage.value?.id === id) currentPage.value = page;
            return true;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Unknown error';
            return false;
        } finally {
            loading.value = false;
        }
    }

    async function unpublishLandingPage(id: number): Promise<boolean> {
        loading.value = true;
        error.value = null;
        try {
            const response = await fetch(`/api/landing-pages/${id}/unpublish`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) throw new Error('Failed to unpublish landing page');

            const page = await response.json();
            const index = landingPages.value.findIndex(p => p.id === id);
            if (index !== -1) landingPages.value[index] = page;
            if (currentPage.value?.id === id) currentPage.value = page;
            return true;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Unknown error';
            return false;
        } finally {
            loading.value = false;
        }
    }

    async function archiveLandingPage(id: number): Promise<boolean> {
        loading.value = true;
        error.value = null;
        try {
            const response = await fetch(`/api/landing-pages/${id}/archive`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) throw new Error('Failed to archive landing page');

            const page = await response.json();
            const index = landingPages.value.findIndex(p => p.id === id);
            if (index !== -1) landingPages.value[index] = page;
            return true;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Unknown error';
            return false;
        } finally {
            loading.value = false;
        }
    }

    function selectPage(page: LandingPage) {
        selectedPage.value = page;
    }

    function clearSelectedPage() {
        selectedPage.value = null;
    }

    function setCurrentPage(page: LandingPage) {
        currentPage.value = page;
    }

    function clearCurrentPage() {
        currentPage.value = null;
    }

    function clearError() {
        error.value = null;
    }

    return {
        landingPages,
        currentPage,
        selectedPage,
        loading,
        error,
        pagination,
        hasPages,
        publishedPages,
        draftPages,
        fetchLandingPages,
        fetchLandingPage,
        createLandingPage,
        updateLandingPage,
        deleteLandingPage,
        publishLandingPage,
        unpublishLandingPage,
        archiveLandingPage,
        selectPage,
        clearSelectedPage,
        setCurrentPage,
        clearCurrentPage,
        clearError,
    };
});
