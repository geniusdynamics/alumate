import axios from 'axios';
import { defineStore } from 'pinia';
import { computed, ref } from 'vue';

interface ConsentStatus {
    has_consent?: boolean;
    granted_at?: string;
    updated_at?: string;
}

interface ConsentHistoryEntry {
    consent_type: string;
    action: 'granted' | 'revoked' | 'updated';
    timestamp: string;
    ip_address?: string;
    user_agent?: string;
}

interface PrivacySettings {
    profile_visibility: string;
    show_activity_status: boolean;
    allow_indexing: boolean;
    data_retention_period: string;
    two_factor_enabled: boolean;
}

export const usePrivacyStore = defineStore('privacy', () => {
    // State
    const consentStatus = ref<Record<string, ConsentStatus>>({});
    const consentHistory = ref<ConsentHistoryEntry[]>([]);
    const privacySettings = ref<PrivacySettings>({
        profile_visibility: 'alumni_only',
        show_activity_status: true,
        allow_indexing: false,
        data_retention_period: '2',
        two_factor_enabled: false,
    });
    const currentUserId = ref<number | null>(null);
    const loading = ref(false);
    const error = ref('');
    const exportedData = ref<Record<string, unknown> | null>(null);

    // Getters
    const isLoading = computed(() => loading.value);
    const hasError = computed(() => !!error.value);
    const hasAnyConsent = computed(() => 
        Object.values(consentStatus.value).some(s => s.has_consent)
    );
    const grantedConsents = computed(() => 
        Object.entries(consentStatus.value)
            .filter(([_, status]) => status.has_consent)
            .map(([type]) => type)
    );

    // Actions
    const fetchPrivacyData = async (userId: number): Promise<void> => {
        loading.value = true;
        error.value = '';
        currentUserId.value = userId;

        try {
            const response = await axios.get(`/api/analytics/privacy/${userId}`);
            
            if (response.data.success) {
                consentStatus.value = response.data.data.consent_status || {};
            } else {
                error.value = response.data.error || 'Failed to fetch privacy data';
            }
        } catch (err: unknown) {
            const axiosError = err as { response?: { data?: { error?: string } } };
            error.value = axiosError.response?.data?.error || 'Failed to fetch privacy data';
            console.error('Failed to fetch privacy data:', err);
        } finally {
            loading.value = false;
        }
    };

    const updateConsent = async (
        userId: number,
        consentType: string,
        consented: boolean
    ): Promise<boolean> => {
        loading.value = true;
        error.value = '';

        try {
            const response = await axios.post(`/api/analytics/privacy/${userId}/consent`, {
                consent_type: consentType,
                consented,
            });

            if (response.data.success) {
                // Update local state
                if (!consentStatus.value[consentType]) {
                    consentStatus.value[consentType] = {};
                }
                consentStatus.value[consentType] = {
                    ...consentStatus.value[consentType],
                    has_consent: consented,
                    updated_at: new Date().toISOString(),
                    granted_at: consented ? new Date().toISOString() : undefined,
                };
                return true;
            } else {
                error.value = response.data.error || 'Failed to update consent';
                return false;
            }
        } catch (err: unknown) {
            const axiosError = err as { response?: { data?: { error?: string } } };
            error.value = axiosError.response?.data?.error || 'Failed to update consent';
            console.error('Failed to update consent:', err);
            return false;
        } finally {
            loading.value = false;
        }
    };

    const revokeConsent = async (userId: number, consentType: string): Promise<boolean> => {
        loading.value = true;
        error.value = '';

        try {
            const response = await axios.delete(`/api/analytics/privacy/${userId}/consent`, {
                data: { consent_type: consentType },
            });

            if (response.data.success) {
                if (consentStatus.value[consentType]) {
                    consentStatus.value[consentType] = {
                        ...consentStatus.value[consentType],
                        has_consent: false,
                        updated_at: new Date().toISOString(),
                    };
                }
                return true;
            } else {
                error.value = response.data.error || 'Failed to revoke consent';
                return false;
            }
        } catch (err: unknown) {
            const axiosError = err as { response?: { data?: { error?: string } } };
            error.value = axiosError.response?.data?.error || 'Failed to revoke consent';
            console.error('Failed to revoke consent:', err);
            return false;
        } finally {
            loading.value = false;
        }
    };

    const exportDataRequest = async (userId: number): Promise<Record<string, unknown> | null> => {
        loading.value = true;
        error.value = '';

        try {
            const response = await axios.get(`/api/analytics/privacy/${userId}/export`);

            if (response.data.success) {
                const data = response.data.data || {};
                exportedData.value = data;
                return data;
            } else {
                error.value = response.data.error || 'Failed to export data';
                return null;
            }
        } catch (err: unknown) {
            const axiosError = err as { response?: { data?: { error?: string } } };
            error.value = axiosError.response?.data?.error || 'Failed to export data';
            console.error('Failed to export data:', err);
            return null;
        } finally {
            loading.value = false;
        }
    };

    const deleteDataRequest = async (userId: number): Promise<boolean> => {
        loading.value = true;
        error.value = '';

        try {
            const response = await axios.delete(`/api/analytics/privacy/${userId}/delete`);

            if (response.data.success) {
                // Clear local data after successful deletion
                consentStatus.value = {};
                consentHistory.value = [];
                return true;
            } else {
                error.value = response.data.error || 'Failed to delete data';
                return false;
            }
        } catch (err: unknown) {
            const axiosError = err as { response?: { data?: { error?: string } } };
            error.value = axiosError.response?.data?.error || 'Failed to delete data';
            console.error('Failed to delete data:', err);
            return false;
        } finally {
            loading.value = false;
        }
    };

    const anonymizeDataRequest = async (userId: number): Promise<boolean> => {
        loading.value = true;
        error.value = '';

        try {
            const response = await axios.post(`/api/analytics/privacy/${userId}/anonymize`);

            if (response.data.success) {
                return true;
            } else {
                error.value = response.data.error || 'Failed to anonymize data';
                return false;
            }
        } catch (err: unknown) {
            const axiosError = err as { response?: { data?: { error?: string } } };
            error.value = axiosError.response?.data?.error || 'Failed to anonymize data';
            console.error('Failed to anonymize data:', err);
            return false;
        } finally {
            loading.value = false;
        }
    };

    const fetchConsentHistory = async (userId: number): Promise<void> => {
        loading.value = true;
        error.value = '';

        try {
            const response = await axios.get(`/api/analytics/privacy/${userId}/history`);

            if (response.data.success) {
                consentHistory.value = response.data.data || [];
            } else {
                error.value = response.data.error || 'Failed to fetch consent history';
            }
        } catch (err: unknown) {
            const axiosError = err as { response?: { data?: { error?: string } } };
            error.value = axiosError.response?.data?.error || 'Failed to fetch consent history';
            console.error('Failed to fetch consent history:', err);
        } finally {
            loading.value = false;
        }
    };

    const fetchPrivacySettings = async (userId: number): Promise<void> => {
        loading.value = true;
        error.value = '';

        try {
            const response = await axios.get(`/api/analytics/privacy/${userId}/settings`);

            if (response.data.success) {
                privacySettings.value = response.data.data || privacySettings.value;
            } else {
                error.value = response.data.error || 'Failed to fetch privacy settings';
            }
        } catch (err: unknown) {
            const axiosError = err as { response?: { data?: { error?: string } } };
            error.value = axiosError.response?.data?.error || 'Failed to fetch privacy settings';
            console.error('Failed to fetch privacy settings:', err);
        } finally {
            loading.value = false;
        }
    };

    const updatePrivacySetting = async (
        userId: number,
        setting: string,
        value: string | boolean
    ): Promise<boolean> => {
        loading.value = true;
        error.value = '';

        try {
            const response = await axios.put(`/api/analytics/privacy/${userId}/settings`, {
                [setting]: value,
            });

            if (response.data.success) {
                // Update local state
                (privacySettings.value as Record<string, unknown>)[setting] = value;
                return true;
            } else {
                error.value = response.data.error || 'Failed to update privacy setting';
                return false;
            }
        } catch (err: unknown) {
            const axiosError = err as { response?: { data?: { error?: string } } };
            error.value = axiosError.response?.data?.error || 'Failed to update privacy setting';
            console.error('Failed to update privacy setting:', err);
            return false;
        } finally {
            loading.value = false;
        }
    };

    // Alias methods for component compatibility
    const exportData = exportDataRequest;
    const deleteData = deleteDataRequest;
    const anonymizeData = anonymizeDataRequest;

    const clearError = () => {
        error.value = '';
    };

    const reset = () => {
        consentStatus.value = {};
        consentHistory.value = [];
        privacySettings.value = {
            profile_visibility: 'alumni_only',
            show_activity_status: true,
            allow_indexing: false,
            data_retention_period: '2',
            two_factor_enabled: false,
        };
        error.value = '';
        exportedData.value = null;
    };

    return {
        // State
        consentStatus,
        consentHistory,
        privacySettings,
        currentUserId,
        loading,
        error,
        exportedData,

        // Getters
        isLoading,
        hasError,
        hasAnyConsent,
        grantedConsents,

        // Actions
        fetchPrivacyData,
        updateConsent,
        revokeConsent,
        exportDataRequest,
        deleteDataRequest,
        anonymizeDataRequest,
        fetchConsentHistory,
        fetchPrivacySettings,
        updatePrivacySetting,
        exportData,
        deleteData,
        anonymizeData,
        clearError,
        reset,
    };
});
