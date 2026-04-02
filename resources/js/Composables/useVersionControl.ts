import { reactive, ref } from 'vue';

interface PageVersion {
    id: number;
    version_number: number;
    change_summary?: string;
    is_published: boolean;
    created_at: string;
    published_at?: string;
    creator: {
        id: number;
        name: string;
        email: string;
    };
    grapejs_data: any;
    metadata?: any;
}

interface VersionControlState {
    versions: PageVersion[];
    isLoadingVersions: boolean;
    isCreatingVersion: boolean;
    isPublishing: boolean;
    isRollingBack: boolean;
    autoSaveEnabled: boolean;
    autoSaveInterval: number | null;
}

const state = reactive<VersionControlState>({
    versions: [],
    isLoadingVersions: false,
    isCreatingVersion: false,
    isPublishing: false,
    isRollingBack: false,
    autoSaveEnabled: true,
    autoSaveInterval: null,
});

export function useVersionControl(pageId: number) {
    const loadVersions = async () => {
        state.isLoadingVersions = true;

        try {
            const response = await fetch(`/api/pages/${pageId}/versions`, {
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            if (response.ok) {
                const data = await response.json();
                state.versions = data.data;
            } else {
                console.error('Failed to load versions:', response.statusText);
            }
        } catch (error) {
            console.error('Error loading versions:', error);
        } finally {
            state.isLoadingVersions = false;
        }
    };

    const createVersion = async (grapeJSData: any, changeSummary?: string, metadata?: any): Promise<PageVersion | null> => {
        state.isCreatingVersion = true;

        try {
            const response = await fetch(`/api/pages/${pageId}/versions`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({
                    grapejs_data: grapeJSData,
                    change_summary: changeSummary,
                    metadata: metadata,
                }),
            });

            if (response.ok) {
                const data = await response.json();
                const newVersion = data.data;

                // Add to the beginning of the versions array
                state.versions.unshift(newVersion);

                return newVersion;
            } else {
                const errorData = await response.json();
                console.error('Failed to create version:', errorData.message);
                return null;
            }
        } catch (error) {
            console.error('Error creating version:', error);
            return null;
        } finally {
            state.isCreatingVersion = false;
        }
    };

    const publishVersion = async (version: PageVersion): Promise<PageVersion | null> => {
        state.isPublishing = true;

        try {
            const response = await fetch(`/api/pages/${pageId}/versions/${version.id}/publish`, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            if (response.ok) {
                const data = await response.json();
                const publishedVersion = data.data;

                // Update versions array
                state.versions = state.versions.map((v) => ({
                    ...v,
                    is_published: v.id === publishedVersion.id,
                }));

                return publishedVersion;
            } else {
                const errorData = await response.json();
                console.error('Failed to publish version:', errorData.message);
                return null;
            }
        } catch (error) {
            console.error('Error publishing version:', error);
            return null;
        } finally {
            state.isPublishing = false;
        }
    };

    const rollbackToVersion = async (version: PageVersion): Promise<PageVersion | null> => {
        state.isRollingBack = true;

        try {
            const response = await fetch(`/api/pages/${pageId}/versions/${version.id}/rollback`, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            if (response.ok) {
                const data = await response.json();
                const newVersion = data.data;

                // Add the rollback version to the beginning
                state.versions.unshift(newVersion);

                return newVersion;
            } else {
                const errorData = await response.json();
                console.error('Failed to rollback:', errorData.message);
                return null;
            }
        } catch (error) {
            console.error('Error rolling back:', error);
            return null;
        } finally {
            state.isRollingBack = false;
        }
    };

    const autoSaveVersion = async (grapeJSData: any): Promise<PageVersion | null> => {
        if (!state.autoSaveEnabled) return null;

        try {
            const response = await fetch(`/api/pages/${pageId}/versions/auto-save`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({
                    grapejs_data: grapeJSData,
                }),
            });

            if (response.ok) {
                const data = await response.json();
                const autoSavedVersion = data.data;

                // Update or add the auto-saved version
                const existingIndex = state.versions.findIndex((v) => v.id === autoSavedVersion.id);
                if (existingIndex >= 0) {
                    state.versions[existingIndex] = autoSavedVersion;
                } else {
                    state.versions.unshift(autoSavedVersion);
                }

                return autoSavedVersion;
            }
        } catch (error) {
            console.error('Auto-save error:', error);
        }

        return null;
    };

    const compareVersions = async (version1: PageVersion, version2: PageVersion) => {
        try {
            const response = await fetch(`/api/pages/${pageId}/versions/${version1.id}/compare/${version2.id}`, {
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            if (response.ok) {
                const data = await response.json();
                return data.data;
            } else {
                console.error('Failed to compare versions:', response.statusText);
                return null;
            }
        } catch (error) {
            console.error('Error comparing versions:', error);
            return null;
        }
    };

    const getPublishedVersion = async (): Promise<PageVersion | null> => {
        try {
            const response = await fetch(`/api/pages/${pageId}/versions/published`, {
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            if (response.ok) {
                const data = await response.json();
                return data.data;
            } else if (response.status === 404) {
                return null; // No published version
            } else {
                console.error('Failed to get published version:', response.statusText);
                return null;
            }
        } catch (error) {
            console.error('Error getting published version:', error);
            return null;
        }
    };

    const toggleAutoSave = () => {
        state.autoSaveEnabled = !state.autoSaveEnabled;

        if (state.autoSaveEnabled) {
            startAutoSave();
        } else {
            stopAutoSave();
        }
    };

    const startAutoSave = () => {
        if (state.autoSaveInterval) return;

        // Auto-save every 30 seconds
        state.autoSaveInterval = window.setInterval(() => {
            // This would be called by the page builder component
            // when it detects changes
        }, 30000);
    };

    const stopAutoSave = () => {
        if (state.autoSaveInterval) {
            clearInterval(state.autoSaveInterval);
            state.autoSaveInterval = null;
        }
    };

    // Initialize auto-save if enabled
    if (state.autoSaveEnabled) {
        startAutoSave();
    }

    return {
        // State
        versions: ref(state.versions),
        isLoadingVersions: ref(state.isLoadingVersions),
        isCreatingVersion: ref(state.isCreatingVersion),
        isPublishing: ref(state.isPublishing),
        isRollingBack: ref(state.isRollingBack),
        autoSaveEnabled: ref(state.autoSaveEnabled),

        // Actions
        loadVersions,
        createVersion,
        publishVersion,
        rollbackToVersion,
        autoSaveVersion,
        compareVersions,
        getPublishedVersion,
        toggleAutoSave,
        startAutoSave,
        stopAutoSave,
    };
}
