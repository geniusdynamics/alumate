<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { usePrivacyStore } from '@/stores/privacyStore';
import LoadingSpinner from '@/components/LoadingSpinner.vue';

interface Props {
    userId?: number;
    compact?: boolean;
    showDataRights?: boolean;
    showConsentHistory?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    compact: false,
    showDataRights: true,
    showConsentHistory: true,
});

const privacyStore = usePrivacyStore();

// Local state
const activeTab = ref<'consent' | 'data-rights' | 'history' | 'settings'>('consent');
const showDeleteConfirmation = ref(false);
const showAnonymizeConfirmation = ref(false);
const showExportConfirmation = ref(false);
const deleteConfirmationText = ref('');
const anonymizeConfirmationText = ref('');
const exportFormat = ref<'json' | 'csv'>('json');

// Computed
const isLoading = computed(() => privacyStore.isLoading);
const error = computed(() => privacyStore.error);
const consentStatus = computed(() => privacyStore.consentStatus);
const consentHistory = computed(() => privacyStore.consentHistory);
const privacySettings = computed(() => privacyStore.privacySettings);
const currentUserId = computed(() => props.userId ?? privacyStore.currentUserId ?? 0);

// Consent types with descriptions
const consentTypes = [
    {
        key: 'analytics',
        label: 'Analytics & Performance',
        description: 'Allow us to collect and analyze usage data to improve our services',
        icon: 'chart-bar',
    },
    {
        key: 'marketing',
        label: 'Marketing Communications',
        description: 'Receive updates about new features, promotions, and events',
        icon: 'megaphone',
    },
    {
        key: 'personalization',
        label: 'Personalization',
        description: 'Get personalized recommendations and content based on your preferences',
        icon: 'adjustments',
    },
    {
        key: 'third_party',
        label: 'Third-Party Sharing',
        description: 'Allow sharing of anonymized data with trusted partners',
        icon: 'share',
    },
];

// Methods
const loadPrivacyData = async () => {
    await privacyStore.fetchPrivacyData(currentUserId.value);
};

const updateConsent = async (consentType: string, consented: boolean) => {
    await privacyStore.updateConsent(currentUserId.value, consentType, consented);
};

const updateAllConsents = async (consented: boolean) => {
    for (const type of consentTypes) {
        await updateConsent(type.key, consented);
    }
};

const requestDataExport = async () => {
    const data = await privacyStore.exportData(currentUserId.value);
    if (data) {
        downloadExportedData(data, exportFormat.value);
    }
    showExportConfirmation.value = false;
};

const downloadExportedData = (data: Record<string, unknown>, format: string) => {
    const blob = format === 'json'
        ? new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' })
        : new Blob([convertToCSV(data)], { type: 'text/csv' });
    
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `privacy-data-export-${new Date().toISOString().split('T')[0]}.${format}`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
};

const convertToCSV = (data: Record<string, unknown>): string => {
    const flat: Record<string, string> = {};
    
    const flatten = (obj: Record<string, unknown>, prefix = '') => {
        for (const [key, value] of Object.entries(obj)) {
            const newKey = prefix ? `${prefix}_${key}` : key;
            if (value && typeof value === 'object' && !Array.isArray(value)) {
                flatten(value as Record<string, unknown>, newKey);
            } else {
                flat[newKey] = String(value ?? '');
            }
        }
    };
    
    flatten(data);
    
    const headers = Object.keys(flat);
    const rows = headers.map(key => flat[key]);
    
    return [headers.join(','), rows.join(',')].join('\n');
};

const requestDataDeletion = async () => {
    if (deleteConfirmationText.value.toLowerCase() === 'delete') {
        await privacyStore.deleteData(currentUserId.value);
        showDeleteConfirmation.value = false;
        deleteConfirmationText.value = '';
    }
};

const requestDataAnonymization = async () => {
    if (anonymizeConfirmationText.value.toLowerCase() === 'anonymize') {
        await privacyStore.anonymizeData(currentUserId.value);
        showAnonymizeConfirmation.value = false;
        anonymizeConfirmationText.value = '';
    }
};

const updatePrivacySetting = async (setting: string, value: string | boolean) => {
    await privacyStore.updatePrivacySetting(currentUserId.value, setting, value);
};

const formatDate = (dateString: string | undefined): string => {
    if (!dateString) return 'Never';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getConsentStatusText = (status: boolean | undefined): string => {
    if (status === undefined) return 'Not set';
    return status ? 'Granted' : 'Denied';
};

const getConsentStatusClass = (status: boolean | undefined): string => {
    if (status === undefined) return 'bg-gray-100 text-gray-800';
    return status ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
};

// Lifecycle
onMounted(() => {
    loadPrivacyData();
});
</script>

<template>
    <div class="privacy-management" :class="{ 'compact': compact }">
        <!-- Header -->
        <div class="privacy-header">
            <div class="header-content">
                <h2 class="header-title">
                    <svg class="icon-shield" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                    </svg>
                    Privacy Management
                </h2>
                <p class="header-subtitle">
                    Manage your privacy settings, consent preferences, and data rights
                </p>
            </div>
            <div v-if="isLoading" class="header-loading">
                <LoadingSpinner size="sm" />
            </div>
        </div>

        <!-- Error Display -->
        <div v-if="error" class="error-banner">
            <svg class="icon-error" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10" />
                <line x1="12" y1="8" x2="12" y2="12" />
                <line x1="12" y1="16" x2="12.01" y2="16" />
            </svg>
            <span>{{ error }}</span>
            <button class="error-dismiss" @click="privacyStore.clearError()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18" />
                    <line x1="6" y1="6" x2="18" y2="18" />
                </svg>
            </button>
        </div>

        <!-- Tabs Navigation -->
        <div class="tabs-nav">
            <button
                v-for="tab in ['consent', 'data-rights', 'history', 'settings']"
                :key="tab"
                class="tab-button"
                :class="{ 'active': activeTab === tab }"
                @click="activeTab = tab as 'consent' | 'data-rights' | 'history' | 'settings'"
            >
                {{ tab === 'data-rights' ? 'Data Rights' : tab.charAt(0).toUpperCase() + tab.slice(1) }}
            </button>
        </div>

        <!-- Tab Content -->
        <div class="tab-content">
            <!-- Consent Management Tab -->
            <div v-if="activeTab === 'consent'" class="consent-tab">
                <div class="consent-header-actions">
                    <button
                        class="btn-secondary"
                        @click="updateAllConsents(true)"
                        :disabled="isLoading"
                    >
                        Grant All
                    </button>
                    <button
                        class="btn-secondary"
                        @click="updateAllConsents(false)"
                        :disabled="isLoading"
                    >
                        Revoke All
                    </button>
                </div>

                <div class="consent-grid">
                    <div
                        v-for="consent in consentTypes"
                        :key="consent.key"
                        class="consent-card"
                    >
                        <div class="consent-card-header">
                            <div class="consent-icon-wrapper">
                                <svg v-if="consent.icon === 'chart-bar'" class="consent-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M18 20V10M12 20V4M6 20v-6" />
                                </svg>
                                <svg v-else-if="consent.icon === 'megaphone'" class="consent-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 5L6 9H2v6h4l5 4V5z" />
                                    <path d="M15.54 8.46a5 5 0 0 1 0 7.07" />
                                </svg>
                                <svg v-else-if="consent.icon === 'adjustments'" class="consent-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="3" />
                                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z" />
                                </svg>
                                <svg v-else class="consent-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="18" cy="5" r="3" />
                                    <circle cx="6" cy="12" r="3" />
                                    <circle cx="18" cy="19" r="3" />
                                    <line x1="8.59" y1="13.51" x2="15.42" y2="17.49" />
                                    <line x1="15.41" y1="6.51" x2="8.59" y2="10.49" />
                                </svg>
                            </div>
                            <div class="consent-title-wrapper">
                                <h3 class="consent-title">{{ consent.label }}</h3>
                                <span
                                    class="consent-status-badge"
                                    :class="getConsentStatusClass(consentStatus[consent.key]?.has_consent)"
                                >
                                    {{ getConsentStatusText(consentStatus[consent.key]?.has_consent) }}
                                </span>
                            </div>
                        </div>
                        <p class="consent-description">{{ consent.description }}</p>
                        <div class="consent-toggle-wrapper">
                            <label class="consent-toggle">
                                <input
                                    type="checkbox"
                                    :checked="consentStatus[consent.key]?.has_consent ?? false"
                                    @change="updateConsent(consent.key, !consentStatus[consent.key]?.has_consent)"
                                    :disabled="isLoading"
                                />
                                <span class="toggle-slider"></span>
                            </label>
                            <span class="consent-toggle-label">
                                {{ consentStatus[consent.key]?.has_consent ? 'Enabled' : 'Disabled' }}
                            </span>
                        </div>
                        <div v-if="consentStatus[consent.key]?.updated_at" class="consent-meta">
                            Last updated: {{ formatDate(consentStatus[consent.key]?.updated_at) }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Data Rights Tab -->
            <div v-if="activeTab === 'data-rights'" class="data-rights-tab">
                <div class="data-rights-intro">
                    <svg class="icon-info" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10" />
                        <line x1="12" y1="16" x2="12" y2="12" />
                        <line x1="12" y1="8" x2="12.01" y2="8" />
                    </svg>
                    <p>
                        Under GDPR and CCPA regulations, you have the right to access, export,
                        delete, or anonymize your personal data. Choose an action below.
                    </p>
                </div>

                <div class="data-rights-grid">
                    <!-- Data Export Card -->
                    <div class="data-right-card export-card">
                        <div class="data-right-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                <polyline points="7 10 12 15 17 10" />
                                <line x1="12" y1="15" x2="12" y2="3" />
                            </svg>
                        </div>
                        <h3 class="data-right-title">Export Your Data</h3>
                        <p class="data-right-description">
                            Download a copy of all your personal data in JSON or CSV format.
                        </p>
                        <button
                            class="btn-primary"
                            @click="showExportConfirmation = true"
                            :disabled="isLoading"
                        >
                            Request Export
                        </button>
                    </div>

                    <!-- Data Deletion Card -->
                    <div class="data-right-card deletion-card">
                        <div class="data-right-icon warning">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="3 6 5 6 21 6" />
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                                <line x1="10" y1="11" x2="10" y2="17" />
                                <line x1="14" y1="11" x2="14" y2="17" />
                            </svg>
                        </div>
                        <h3 class="data-right-title">Delete Your Data</h3>
                        <p class="data-right-description">
                            Permanently delete all your personal data. This action cannot be undone.
                        </p>
                        <button
                            class="btn-danger"
                            @click="showDeleteConfirmation = true"
                            :disabled="isLoading"
                        >
                            Request Deletion
                        </button>
                    </div>

                    <!-- Data Anonymization Card -->
                    <div class="data-right-card anonymization-card">
                        <div class="data-right-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                                <line x1="12" y1="8" x2="12" y2="12" />
                                <line x1="12" y1="16" x2="12.01" y2="16" />
                            </svg>
                        </div>
                        <h3 class="data-right-title">Anonymize Your Data</h3>
                        <p class="data-right-description">
                            Remove personal identifiers while preserving aggregate statistics.
                        </p>
                        <button
                            class="btn-warning"
                            @click="showAnonymizeConfirmation = true"
                            :disabled="isLoading"
                        >
                            Request Anonymization
                        </button>
                    </div>
                </div>
            </div>

            <!-- Consent History Tab -->
            <div v-if="activeTab === 'history'" class="history-tab">
                <div class="history-header">
                    <h3 class="history-title">Consent History</h3>
                    <p class="history-subtitle">
                        A log of all consent-related actions on your account
                    </p>
                </div>

                <div v-if="consentHistory.length === 0" class="empty-state">
                    <svg class="empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10" />
                        <path d="M12 6v6l4 2" />
                    </svg>
                    <p>No consent history available</p>
                </div>

                <div v-else class="history-list">
                    <div
                        v-for="(entry, index) in consentHistory"
                        :key="index"
                        class="history-entry"
                    >
                        <div class="history-entry-icon" :class="entry.action">
                            <svg v-if="entry.action === 'granted'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20 6 9 17 4 12" />
                            </svg>
                            <svg v-else-if="entry.action === 'revoked'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18" />
                                <line x1="6" y1="6" x2="18" y2="18" />
                            </svg>
                            <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10" />
                                <line x1="12" y1="8" x2="12" y2="12" />
                                <line x1="12" y1="16" x2="12.01" y2="16" />
                            </svg>
                        </div>
                        <div class="history-entry-content">
                            <div class="history-entry-header">
                                <span class="history-entry-action">
                                    Consent {{ entry.action }} for {{ entry.consent_type }}
                                </span>
                                <span class="history-entry-time">
                                    {{ formatDate(entry.timestamp) }}
                                </span>
                            </div>
                            <div class="history-entry-details">
                                <span v-if="entry.ip_address" class="history-detail">
                                    IP: {{ entry.ip_address }}
                                </span>
                                <span v-if="entry.user_agent" class="history-detail">
                                    {{ entry.user_agent }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Privacy Settings Tab -->
            <div v-if="activeTab === 'settings'" class="settings-tab">
                <div class="settings-header">
                    <h3 class="settings-title">Privacy Settings</h3>
                    <p class="settings-subtitle">
                        Additional privacy controls for your account
                    </p>
                </div>

                <div class="settings-list">
                    <div class="setting-item">
                        <div class="setting-info">
                            <h4 class="setting-label">Profile Visibility</h4>
                            <p class="setting-description">
                                Control who can view your profile information
                            </p>
                        </div>
                        <select
                            class="setting-select"
                            :value="privacySettings.profile_visibility"
                            @change="updatePrivacySetting('profile_visibility', ($event.target as HTMLSelectElement).value)"
                        >
                            <option value="public">Public</option>
                            <option value="alumni_only">Alumni Only</option>
                            <option value="private">Private</option>
                        </select>
                    </div>

                    <div class="setting-item">
                        <div class="setting-info">
                            <h4 class="setting-label">Activity Status</h4>
                            <p class="setting-description">
                                Show your online status to other users
                            </p>
                        </div>
                        <label class="setting-toggle">
                            <input
                                type="checkbox"
                                :checked="privacySettings.show_activity_status"
                                @change="updatePrivacySetting('show_activity_status', !privacySettings.show_activity_status)"
                            />
                            <span class="toggle-slider"></span>
                        </label>
                    </div>

                    <div class="setting-item">
                        <div class="setting-info">
                            <h4 class="setting-label">Search Engine Indexing</h4>
                            <p class="setting-description">
                                Allow search engines to index your profile
                            </p>
                        </div>
                        <label class="setting-toggle">
                            <input
                                type="checkbox"
                                :checked="privacySettings.allow_indexing"
                                @change="updatePrivacySetting('allow_indexing', !privacySettings.allow_indexing)"
                            />
                            <span class="toggle-slider"></span>
                        </label>
                    </div>

                    <div class="setting-item">
                        <div class="setting-info">
                            <h4 class="setting-label">Data Retention Period</h4>
                            <p class="setting-description">
                                How long to keep your inactive account data
                            </p>
                        </div>
                        <select
                            class="setting-select"
                            :value="privacySettings.data_retention_period"
                            @change="updatePrivacySetting('data_retention_period', ($event.target as HTMLSelectElement).value)"
                        >
                            <option value="1">1 Year</option>
                            <option value="2">2 Years</option>
                            <option value="5">5 Years</option>
                            <option value="0">Indefinite</option>
                        </select>
                    </div>

                    <div class="setting-item">
                        <div class="setting-info">
                            <h4 class="setting-label">Two-Factor Authentication</h4>
                            <p class="setting-description">
                                Add an extra layer of security to your account
                            </p>
                        </div>
                        <button
                            class="btn-secondary btn-sm"
                            :class="{ 'btn-danger': privacySettings.two_factor_enabled }"
                        >
                            {{ privacySettings.two_factor_enabled ? 'Disable' : 'Enable' }} 2FA
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Export Confirmation Modal -->
        <div v-if="showExportConfirmation" class="modal-overlay" @click.self="showExportConfirmation = false">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Export Your Data</h3>
                    <button class="modal-close" @click="showExportConfirmation = false">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18" />
                            <line x1="6" y1="6" x2="18" y2="18" />
                        </svg>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="modal-description">
                        Your data will be prepared for download. Choose your preferred format:
                    </p>
                    <div class="export-format-options">
                        <label class="format-option">
                            <input type="radio" v-model="exportFormat" value="json" />
                            <span class="format-label">JSON</span>
                            <span class="format-description">Structured, machine-readable format</span>
                        </label>
                        <label class="format-option">
                            <input type="radio" v-model="exportFormat" value="csv" />
                            <span class="format-label">CSV</span>
                            <span class="format-description">Spreadsheet-compatible format</span>
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn-secondary" @click="showExportConfirmation = false">
                        Cancel
                    </button>
                    <button
                        class="btn-primary"
                        @click="requestDataExport"
                        :disabled="isLoading"
                    >
                        {{ isLoading ? 'Preparing...' : 'Download Data' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteConfirmation" class="modal-overlay" @click.self="showDeleteConfirmation = false">
            <div class="modal-content danger">
                <div class="modal-header">
                    <h3 class="modal-title">Delete Your Data</h3>
                    <button class="modal-close" @click="showDeleteConfirmation = false">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18" />
                            <line x1="6" y1="6" x2="18" y2="18" />
                        </svg>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="warning-banner">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
                            <line x1="12" y1="9" x2="12" y2="13" />
                            <line x1="12" y1="17" x2="12.01" y2="17" />
                        </svg>
                        <p>This action is permanent and cannot be undone.</p>
                    </div>
                    <p class="modal-description">
                        To confirm, please type <strong>DELETE</strong> below:
                    </p>
                    <input
                        type="text"
                        v-model="deleteConfirmationText"
                        class="confirmation-input"
                        placeholder="Type DELETE to confirm"
                    />
                </div>
                <div class="modal-footer">
                    <button class="btn-secondary" @click="showDeleteConfirmation = false">
                        Cancel
                    </button>
                    <button
                        class="btn-danger"
                        @click="requestDataDeletion"
                        :disabled="deleteConfirmationText.toLowerCase() !== 'delete' || isLoading"
                    >
                        {{ isLoading ? 'Deleting...' : 'Permanently Delete' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Anonymize Confirmation Modal -->
        <div v-if="showAnonymizeConfirmation" class="modal-overlay" @click.self="showAnonymizeConfirmation = false">
            <div class="modal-content warning">
                <div class="modal-header">
                    <h3 class="modal-title">Anonymize Your Data</h3>
                    <button class="modal-close" @click="showAnonymizeConfirmation = false">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18" />
                            <line x1="6" y1="6" x2="18" y2="18" />
                        </svg>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="warning-banner">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="12" y1="8" x2="12" y2="12" />
                            <line x1="12" y1="16" x2="12.01" y2="16" />
                        </svg>
                        <p>Your personal identifiers will be removed, but aggregate data will be preserved.</p>
                    </div>
                    <p class="modal-description">
                        To confirm, please type <strong>ANONYMIZE</strong> below:
                    </p>
                    <input
                        type="text"
                        v-model="anonymizeConfirmationText"
                        class="confirmation-input"
                        placeholder="Type ANONYMIZE to confirm"
                    />
                </div>
                <div class="modal-footer">
                    <button class="btn-secondary" @click="showAnonymizeConfirmation = false">
                        Cancel
                    </button>
                    <button
                        class="btn-warning"
                        @click="requestDataAnonymization"
                        :disabled="anonymizeConfirmationText.toLowerCase() !== 'anonymize' || isLoading"
                    >
                        {{ isLoading ? 'Anonymizing...' : 'Anonymize Data' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.privacy-management {
    @apply bg-white rounded-lg shadow-sm border border-gray-200;
    @apply p-6 md:p-8;
    @apply max-w-6xl mx-auto;
}

.compact {
    @apply p-4;
}

/* Header */
.privacy-header {
    @apply flex items-start justify-between mb-8;
}

.header-title {
    @apply text-2xl font-bold text-gray-900 flex items-center gap-3;
}

.icon-shield {
    @apply w-8 h-8 text-indigo-600;
}

.header-subtitle {
    @apply text-gray-600 mt-2;
}

.header-loading {
    @apply ml-4;
}

/* Error Banner */
.error-banner {
    @apply bg-red-50 border border-red-200 rounded-lg p-4 mb-6;
    @apply flex items-center gap-3;
    @apply text-red-800;
}

.icon-error {
    @apply w-5 h-5 flex-shrink-0;
}

.error-dismiss {
    @apply ml-auto p-1 rounded hover:bg-red-100;
}

.error-dismiss svg {
    @apply w-4 h-4;
}

/* Tabs Navigation */
.tabs-nav {
    @apply flex gap-2 mb-6 border-b border-gray-200 pb-4;
    @apply overflow-x-auto;
}

.tab-button {
    @apply px-4 py-2 rounded-lg font-medium transition-colors;
    @apply text-gray-600 hover:text-gray-900 hover:bg-gray-100;
}

.tab-button.active {
    @apply bg-indigo-50 text-indigo-700;
}

/* Consent Tab */
.consent-header-actions {
    @apply flex gap-3 mb-6;
}

.consent-grid {
    @apply grid gap-6;
    @apply grid-cols-1 md:grid-cols-2;
}

.consent-card {
    @apply bg-gray-50 rounded-xl p-6 border border-gray-200;
    @apply transition-all duration-200;
}

.consent-card:hover {
    @apply shadow-md border-gray-300;
}

.consent-card-header {
    @apply flex items-start gap-4 mb-4;
}

.consent-icon-wrapper {
    @apply w-12 h-12 rounded-xl bg-indigo-100 flex items-center justify-center flex-shrink-0;
}

.consent-icon {
    @apply w-6 h-6 text-indigo-600;
}

.consent-title-wrapper {
    @apply flex-1;
}

.consent-title {
    @apply text-lg font-semibold text-gray-900;
}

.consent-status-badge {
    @apply inline-block mt-1 px-2 py-0.5 rounded-full text-xs font-medium;
}

.consent-description {
    @apply text-gray-600 text-sm mb-4;
}

.consent-toggle-wrapper {
    @apply flex items-center gap-3;
}

.consent-toggle {
    @apply relative inline-flex items-center cursor-pointer;
}

.consent-toggle input {
    @apply sr-only;
}

.toggle-slider {
    @apply w-11 h-6 bg-gray-300 rounded-full transition-colors;
    @apply after:content-[''] after:absolute after:top-0.5 after:left-0.5;
    @apply after:w-5 after:h-5 after:bg-white after:rounded-full;
    @apply after:transition-transform;
}

.consent-toggle input:checked + .toggle-slider {
    @apply bg-indigo-600;
}

.consent-toggle input:checked + .toggle-slider::after {
    @apply translate-x-5;
}

.consent-toggle-label {
    @apply text-sm text-gray-600;
}

.consent-meta {
    @apply mt-4 text-xs text-gray-500;
}

/* Data Rights Tab */
.data-rights-intro {
    @apply bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6;
    @apply flex gap-3;
}

.data-rights-intro .icon-info {
    @apply w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5;
}

.data-rights-intro p {
    @apply text-blue-800 text-sm;
}

.data-rights-grid {
    @apply grid gap-6;
    @apply grid-cols-1 md:grid-cols-3;
}

.data-right-card {
    @apply bg-gray-50 rounded-xl p-6 border border-gray-200;
    @apply text-center;
}

.data-right-icon {
    @apply w-14 h-14 rounded-xl bg-indigo-100 flex items-center justify-center mx-auto mb-4;
}

.data-right-icon svg {
    @apply w-7 h-7 text-indigo-600;
}

.data-right-icon.warning {
    @apply bg-red-100;
}

.data-right-icon.warning svg {
    @apply text-red-600;
}

.data-right-title {
    @apply text-lg font-semibold text-gray-900 mb-2;
}

.data-right-description {
    @apply text-gray-600 text-sm mb-4;
}

/* History Tab */
.history-header {
    @apply mb-6;
}

.history-title {
    @apply text-xl font-semibold text-gray-900;
}

.history-subtitle {
    @apply text-gray-600 mt-1;
}

.empty-state {
    @apply text-center py-12 text-gray-500;
}

.empty-icon {
    @apply w-12 h-12 mx-auto mb-4 text-gray-400;
}

.history-list {
    @apply space-y-4;
}

.history-entry {
    @apply flex gap-4 p-4 bg-gray-50 rounded-lg border border-gray-200;
}

.history-entry-icon {
    @apply w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0;
}

.history-entry-icon.granted {
    @apply bg-green-100 text-green-600;
}

.history-entry-icon.revoked {
    @apply bg-red-100 text-red-600;
}

.history-entry-icon.default {
    @apply bg-gray-100 text-gray-600;
}

.history-entry-icon svg {
    @apply w-5 h-5;
}

.history-entry-content {
    @apply flex-1 min-w-0;
}

.history-entry-header {
    @apply flex items-center justify-between mb-1;
}

.history-entry-action {
    @apply font-medium text-gray-900;
}

.history-entry-time {
    @apply text-sm text-gray-500;
}

.history-entry-details {
    @apply flex gap-4 text-xs text-gray-500;
}

/* Settings Tab */
.settings-header {
    @apply mb-6;
}

.settings-title {
    @apply text-xl font-semibold text-gray-900;
}

.settings-subtitle {
    @apply text-gray-600 mt-1;
}

.settings-list {
    @apply space-y-4;
}

.setting-item {
    @apply flex items-center justify-between;
    @apply p-4 bg-gray-50 rounded-lg border border-gray-200;
    @apply gap-4;
}

.setting-info {
    @apply flex-1 min-w-0;
}

.setting-label {
    @apply font-medium text-gray-900;
}

.setting-description {
    @apply text-sm text-gray-500 mt-0.5;
}

.setting-select {
    @apply px-3 py-2 border border-gray-300 rounded-lg text-sm;
    @apply focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500;
    @apply bg-white min-w-[140px];
}

.setting-toggle {
    @apply relative inline-flex items-center cursor-pointer;
}

.setting-toggle input {
    @apply sr-only;
}

.setting-toggle .toggle-slider {
    @apply w-11 h-6 bg-gray-300 rounded-full transition-colors;
    @apply after:content-[''] after:absolute after:top-0.5 after:left-0.5;
    @apply after:w-5 after:h-5 after:bg-white after:rounded-full;
    @apply after:transition-transform;
}

.setting-toggle input:checked + .toggle-slider {
    @apply bg-indigo-600;
}

.setting-toggle input:checked + .toggle-slider::after {
    @apply translate-x-5;
}

/* Modals */
.modal-overlay {
    @apply fixed inset-0 bg-black/50 flex items-center justify-center z-50;
    @apply p-4;
}

.modal-content {
    @apply bg-white rounded-xl shadow-xl max-w-md w-full;
    @apply overflow-hidden;
}

.modal-content.danger {
    @apply border-2 border-red-200;
}

.modal-content.warning {
    @apply border-2 border-yellow-200;
}

.modal-header {
    @apply flex items-center justify-between p-6 border-b border-gray-200;
}

.modal-title {
    @apply text-xl font-semibold text-gray-900;
}

.modal-close {
    @apply p-1 rounded-lg hover:bg-gray-100;
}

.modal-close svg {
    @apply w-5 h-5 text-gray-500;
}

.modal-body {
    @apply p-6;
}

.modal-description {
    @apply text-gray-600 mb-4;
}

.warning-banner {
    @apply bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4;
    @apply flex gap-3;
}

.warning-banner svg {
    @apply w-5 h-5 text-yellow-600 flex-shrink-0;
}

.warning-banner p {
    @apply text-yellow-800 text-sm;
}

.export-format-options {
    @apply space-y-3;
}

.format-option {
    @apply flex items-center gap-3 p-3 border border-gray-200 rounded-lg;
    @apply cursor-pointer hover:bg-gray-50;
}

.format-option input:checked + .format-label {
    @apply text-indigo-600 font-medium;
}

.format-label {
    @apply font-medium text-gray-900;
}

.format-description {
    @apply text-sm text-gray-500 ml-auto;
}

.confirmation-input {
    @apply w-full px-4 py-3 border border-gray-300 rounded-lg;
    @apply focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500;
    @apply font-mono text-center uppercase tracking-wide;
}

.modal-footer {
    @apply flex justify-end gap-3 p-6 border-t border-gray-200;
}

/* Buttons */
.btn-primary {
    @apply px-4 py-2 bg-indigo-600 text-white rounded-lg font-medium;
    @apply hover:bg-indigo-700 transition-colors;
    @apply disabled:opacity-50 disabled:cursor-not-allowed;
}

.btn-secondary {
    @apply px-4 py-2 bg-gray-100 text-gray-900 rounded-lg font-medium;
    @apply hover:bg-gray-200 transition-colors;
    @apply disabled:opacity-50 disabled:cursor-not-allowed;
}

.btn-danger {
    @apply px-4 py-2 bg-red-600 text-white rounded-lg font-medium;
    @apply hover:bg-red-700 transition-colors;
    @apply disabled:opacity-50 disabled:cursor-not-allowed;
}

.btn-warning {
    @apply px-4 py-2 bg-yellow-500 text-white rounded-lg font-medium;
    @apply hover:bg-yellow-600 transition-colors;
    @apply disabled:opacity-50 disabled:cursor-not-allowed;
}

.btn-sm {
    @apply px-3 py-1.5 text-sm;
}

/* Responsive */
@media (max-width: 768px) {
    .privacy-management {
        @apply p-4;
    }

    .tabs-nav {
        @apply overflow-x-auto pb-2;
    }

    .tab-button {
        @apply whitespace-nowrap px-3 py-1.5 text-sm;
    }

    .consent-grid {
        @apply grid-cols-1;
    }

    .data-rights-grid {
        @apply grid-cols-1;
    }

    .setting-item {
        @apply flex-col items-start gap-3;
    }

    .setting-select {
        @apply w-full;
    }

    .modal-content {
        @apply max-w-full;
    }
}
</style>
