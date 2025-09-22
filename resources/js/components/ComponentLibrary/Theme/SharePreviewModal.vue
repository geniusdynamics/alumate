<template>
    <div class="modal-overlay" @click="handleOverlayClick">
        <div class="modal-container" @click.stop>
            <div class="modal-header">
                <div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Share Theme Preview</h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Generate shareable links for stakeholder review and collaboration</p>
                </div>
                <button @click="$emit('close')" class="btn-close">
                    <Icon name="x" class="h-5 w-5" />
                </button>
            </div>

            <div class="modal-body">
                <!-- Share Options -->
                <div class="share-options">
                    <h4 class="section-title">Share Options</h4>
                    <div class="options-grid">
                        <label class="option-card" :class="{ 'option-selected': shareSettings.includeComparison }">
                            <input v-model="shareSettings.includeComparison" type="checkbox" class="option-checkbox" :disabled="!comparisonTheme" />
                            <div class="option-content">
                                <Icon name="git-compare" class="h-5 w-5 text-blue-600" />
                                <div class="option-info">
                                    <span class="option-name">Include Comparison</span>
                                    <span class="option-description">
                                        {{ comparisonTheme ? 'Show side-by-side comparison' : 'No comparison theme selected' }}
                                    </span>
                                </div>
                            </div>
                        </label>

                        <label class="option-card" :class="{ 'option-selected': shareSettings.allowComments }">
                            <input v-model="shareSettings.allowComments" type="checkbox" class="option-checkbox" />
                            <div class="option-content">
                                <Icon name="message-circle" class="h-5 w-5 text-green-600" />
                                <div class="option-info">
                                    <span class="option-name">Allow Comments</span>
                                    <span class="option-description">Enable feedback and annotations</span>
                                </div>
                            </div>
                        </label>

                        <label class="option-card" :class="{ 'option-selected': shareSettings.requireAuth }">
                            <input v-model="shareSettings.requireAuth" type="checkbox" class="option-checkbox" />
                            <div class="option-content">
                                <Icon name="lock" class="h-5 w-5 text-red-600" />
                                <div class="option-info">
                                    <span class="option-name">Require Authentication</span>
                                    <span class="option-description">Restrict access to team members</span>
                                </div>
                            </div>
                        </label>

                        <label class="option-card" :class="{ 'option-selected': shareSettings.enableDownload }">
                            <input v-model="shareSettings.enableDownload" type="checkbox" class="option-checkbox" />
                            <div class="option-content">
                                <Icon name="download" class="h-5 w-5 text-purple-600" />
                                <div class="option-info">
                                    <span class="option-name">Enable Download</span>
                                    <span class="option-description">Allow theme file downloads</span>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Access Control -->
                <div class="access-control">
                    <h4 class="section-title">Access Control</h4>
                    <div class="access-options">
                        <div class="access-option">
                            <label class="access-label">
                                <input v-model="shareSettings.accessType" type="radio" value="public" class="access-radio" />
                                <div class="access-content">
                                    <Icon name="globe" class="h-5 w-5 text-blue-600" />
                                    <div class="access-info">
                                        <span class="access-name">Public Link</span>
                                        <span class="access-description">Anyone with the link can view</span>
                                    </div>
                                </div>
                            </label>
                        </div>

                        <div class="access-option">
                            <label class="access-label">
                                <input v-model="shareSettings.accessType" type="radio" value="team" class="access-radio" />
                                <div class="access-content">
                                    <Icon name="users" class="h-5 w-5 text-green-600" />
                                    <div class="access-info">
                                        <span class="access-name">Team Only</span>
                                        <span class="access-description">Restricted to team members</span>
                                    </div>
                                </div>
                            </label>
                        </div>

                        <div class="access-option">
                            <label class="access-label">
                                <input v-model="shareSettings.accessType" type="radio" value="password" class="access-radio" />
                                <div class="access-content">
                                    <Icon name="key" class="h-5 w-5 text-orange-600" />
                                    <div class="access-info">
                                        <span class="access-name">Password Protected</span>
                                        <span class="access-description">Require password to access</span>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Password Input -->
                    <div v-if="shareSettings.accessType === 'password'" class="password-section">
                        <label class="password-label">
                            Password
                            <input v-model="shareSettings.password" type="password" class="password-input" placeholder="Enter password" />
                        </label>
                        <button @click="generatePassword" class="generate-password">
                            <Icon name="refresh-cw" class="mr-2 h-4 w-4" />
                            Generate
                        </button>
                    </div>
                </div>

                <!-- Expiration -->
                <div class="expiration-section">
                    <h4 class="section-title">Link Expiration</h4>
                    <div class="expiration-options">
                        <label v-for="option in expirationOptions" :key="option.value" class="expiration-option">
                            <input v-model="shareSettings.expiration" :value="option.value" type="radio" class="expiration-radio" />
                            <span class="expiration-text">{{ option.label }}</span>
                        </label>
                    </div>
                </div>

                <!-- Recipients -->
                <div v-if="shareSettings.accessType === 'team'" class="recipients-section">
                    <h4 class="section-title">Recipients</h4>
                    <div class="recipients-input">
                        <input
                            v-model="recipientEmail"
                            type="email"
                            class="recipient-input"
                            placeholder="Enter email address"
                            @keyup.enter="addRecipient"
                        />
                        <button @click="addRecipient" class="add-recipient" :disabled="!recipientEmail">
                            <Icon name="plus" class="h-4 w-4" />
                        </button>
                    </div>

                    <div v-if="shareSettings.recipients.length > 0" class="recipients-list">
                        <div v-for="(recipient, index) in shareSettings.recipients" :key="index" class="recipient-item">
                            <Icon name="mail" class="h-4 w-4 text-gray-600" />
                            <span class="recipient-email">{{ recipient }}</span>
                            <button @click="removeRecipient(index)" class="remove-recipient">
                                <Icon name="x" class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Preview Settings -->
                <div class="preview-settings">
                    <h4 class="section-title">Preview Settings</h4>
                    <div class="settings-grid">
                        <div class="setting-item">
                            <label class="setting-label">
                                Default View Mode
                                <select v-model="shareSettings.defaultView" class="setting-select">
                                    <option value="components">Components</option>
                                    <option value="styleguide">Style Guide</option>
                                    <option value="accessibility">Accessibility</option>
                                    <option value="responsive">Responsive</option>
                                </select>
                            </label>
                        </div>

                        <div class="setting-item">
                            <label class="setting-label">
                                Default Device
                                <select v-model="shareSettings.defaultDevice" class="setting-select">
                                    <option value="desktop">Desktop</option>
                                    <option value="tablet">Tablet</option>
                                    <option value="mobile">Mobile</option>
                                </select>
                            </label>
                        </div>

                        <div class="setting-item">
                            <label class="setting-checkbox">
                                <input v-model="shareSettings.showAccessibilityOverlay" type="checkbox" class="checkbox" />
                                <span>Show Accessibility Overlay</span>
                            </label>
                        </div>

                        <div class="setting-item">
                            <label class="setting-checkbox">
                                <input v-model="shareSettings.showPerformanceMetrics" type="checkbox" class="checkbox" />
                                <span>Show Performance Metrics</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Generated Links -->
                <div v-if="generatedLinks.length > 0" class="generated-links">
                    <h4 class="section-title">Generated Links</h4>
                    <div class="links-list">
                        <div v-for="link in generatedLinks" :key="link.id" class="link-item">
                            <div class="link-info">
                                <div class="link-header">
                                    <Icon :name="getLinkIcon(link.type)" class="h-4 w-4" />
                                    <span class="link-title">{{ link.title }}</span>
                                    <span class="link-status" :class="getLinkStatusClass(link.status)">
                                        {{ link.status }}
                                    </span>
                                </div>
                                <div class="link-url">{{ link.url }}</div>
                                <div class="link-meta">
                                    Created {{ formatDate(link.createdAt) }} • {{ link.views }} views • Expires {{ formatDate(link.expiresAt) }}
                                </div>
                            </div>
                            <div class="link-actions">
                                <button @click="copyLink(link.url)" class="link-action" title="Copy link">
                                    <Icon name="copy" class="h-4 w-4" />
                                </button>
                                <button @click="openLink(link.url)" class="link-action" title="Open link">
                                    <Icon name="external-link" class="h-4 w-4" />
                                </button>
                                <button @click="revokeLink(link.id)" class="link-action text-red-600" title="Revoke link">
                                    <Icon name="trash-2" class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button @click="$emit('close')" class="btn-secondary">Cancel</button>
                <button @click="generateShareLink" class="btn-primary" :disabled="generating || !isValidSettings">
                    <Icon name="share" class="mr-2 h-4 w-4" />
                    {{ generating ? 'Generating...' : 'Generate Link' }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import Icon from '@/Components/Common/Icon.vue';
import { useNotifications } from '@/Composables/useNotifications';
import type { GrapeJSThemeData } from '@/Types/components';
import { computed, reactive, ref } from 'vue';

interface Props {
    theme: GrapeJSThemeData;
    comparisonTheme?: GrapeJSThemeData | null;
    viewSettings: any;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    close: [];
    share: [data: any];
}>();

// State
const generating = ref(false);
const recipientEmail = ref('');
const generatedLinks = ref<any[]>([]);

const shareSettings = reactive({
    includeComparison: !!props.comparisonTheme,
    allowComments: true,
    requireAuth: false,
    enableDownload: false,
    accessType: 'public',
    password: '',
    expiration: '7d',
    recipients: [] as string[],
    defaultView: props.viewSettings.viewMode || 'components',
    defaultDevice: props.viewSettings.device || 'desktop',
    showAccessibilityOverlay: props.viewSettings.showAccessibilityOverlay || false,
    showPerformanceMetrics: props.viewSettings.showPerformanceMetrics || false,
});

const { showNotification } = useNotifications();

// Expiration options
const expirationOptions = [
    { value: '1h', label: '1 hour' },
    { value: '24h', label: '24 hours' },
    { value: '7d', label: '7 days' },
    { value: '30d', label: '30 days' },
    { value: 'never', label: 'Never expires' },
];

// Computed
const isValidSettings = computed(() => {
    if (shareSettings.accessType === 'password' && !shareSettings.password) {
        return false;
    }
    if (shareSettings.accessType === 'team' && shareSettings.recipients.length === 0) {
        return false;
    }
    return true;
});

// Methods
const handleOverlayClick = () => {
    emit('close');
};

const addRecipient = () => {
    if (recipientEmail.value && isValidEmail(recipientEmail.value)) {
        if (!shareSettings.recipients.includes(recipientEmail.value)) {
            shareSettings.recipients.push(recipientEmail.value);
            recipientEmail.value = '';
        } else {
            showNotification('Email already added', 'warning');
        }
    } else {
        showNotification('Please enter a valid email address', 'warning');
    }
};

const removeRecipient = (index: number) => {
    shareSettings.recipients.splice(index, 1);
};

const generatePassword = () => {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    let password = '';
    for (let i = 0; i < 12; i++) {
        password += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    shareSettings.password = password;
};

const generateShareLink = async () => {
    if (!isValidSettings.value) {
        showNotification('Please complete all required settings', 'warning');
        return;
    }

    generating.value = true;

    try {
        // Simulate API call
        await new Promise((resolve) => setTimeout(resolve, 2000));

        const linkId = generateLinkId();
        const baseUrl = window.location.origin;
        const linkUrl = `${baseUrl}/theme-preview/${linkId}`;

        const newLink = {
            id: linkId,
            title: `${props.theme.name} Preview`,
            url: linkUrl,
            type: shareSettings.accessType,
            status: 'active',
            createdAt: new Date(),
            expiresAt: calculateExpirationDate(shareSettings.expiration),
            views: 0,
            settings: { ...shareSettings },
        };

        generatedLinks.value.unshift(newLink);

        // Emit share data
        emit('share', {
            link: newLink,
            settings: shareSettings,
            theme: props.theme,
            comparisonTheme: props.comparisonTheme,
        });

        showNotification('Share link generated successfully', 'success');
    } catch (error) {
        console.error('Failed to generate share link:', error);
        showNotification('Failed to generate share link', 'error');
    } finally {
        generating.value = false;
    }
};

const copyLink = async (url: string) => {
    try {
        await navigator.clipboard.writeText(url);
        showNotification('Link copied to clipboard', 'success');
    } catch (error) {
        console.error('Failed to copy link:', error);
        showNotification('Failed to copy link', 'error');
    }
};

const openLink = (url: string) => {
    window.open(url, '_blank');
};

const revokeLink = async (linkId: string) => {
    if (!confirm('Are you sure you want to revoke this link? It will no longer be accessible.')) {
        return;
    }

    try {
        // Simulate API call
        await new Promise((resolve) => setTimeout(resolve, 500));

        const linkIndex = generatedLinks.value.findIndex((link) => link.id === linkId);
        if (linkIndex > -1) {
            generatedLinks.value[linkIndex].status = 'revoked';
        }

        showNotification('Link revoked successfully', 'success');
    } catch (error) {
        console.error('Failed to revoke link:', error);
        showNotification('Failed to revoke link', 'error');
    }
};

const getLinkIcon = (type: string) => {
    switch (type) {
        case 'public':
            return 'globe';
        case 'team':
            return 'users';
        case 'password':
            return 'key';
        default:
            return 'link';
    }
};

const getLinkStatusClass = (status: string) => {
    switch (status) {
        case 'active':
            return 'status-active';
        case 'expired':
            return 'status-expired';
        case 'revoked':
            return 'status-revoked';
        default:
            return 'status-unknown';
    }
};

const formatDate = (date: Date) => {
    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(date);
};

const isValidEmail = (email: string) => {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
};

const generateLinkId = () => {
    return Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
};

const calculateExpirationDate = (expiration: string) => {
    const now = new Date();
    switch (expiration) {
        case '1h':
            return new Date(now.getTime() + 60 * 60 * 1000);
        case '24h':
            return new Date(now.getTime() + 24 * 60 * 60 * 1000);
        case '7d':
            return new Date(now.getTime() + 7 * 24 * 60 * 60 * 1000);
        case '30d':
            return new Date(now.getTime() + 30 * 24 * 60 * 60 * 1000);
        case 'never':
            return new Date(now.getTime() + 365 * 24 * 60 * 60 * 1000); // 1 year
        default:
            return new Date(now.getTime() + 7 * 24 * 60 * 60 * 1000);
    }
};
</script>

<style scoped>
.modal-overlay {
    @apply fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4;
}

.modal-container {
    @apply flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-lg bg-white shadow-xl dark:bg-gray-800;
}

.modal-header {
    @apply flex items-start justify-between border-b border-gray-200 p-6 dark:border-gray-700;
}

.modal-body {
    @apply flex-1 space-y-6 overflow-y-auto p-6;
}

.modal-footer {
    @apply flex items-center justify-end gap-3 border-t border-gray-200 p-6 dark:border-gray-700;
}

.btn-close {
    @apply rounded-md p-2 text-gray-600 transition-colors duration-200 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white;
}

.section-title {
    @apply text-md mb-4 font-medium text-gray-900 dark:text-white;
}

.share-options {
    @apply space-y-4;
}

.options-grid {
    @apply grid grid-cols-1 gap-3 md:grid-cols-2;
}

.option-card {
    @apply flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 p-3 transition-all duration-200 hover:border-blue-300 dark:border-gray-700 dark:hover:border-blue-600;
}

.option-selected {
    @apply border-blue-500 bg-blue-50 dark:border-blue-400 dark:bg-blue-900/20;
}

.option-checkbox {
    @apply mt-1 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700;
}

.option-content {
    @apply flex items-start gap-3;
}

.option-info {
    @apply space-y-1;
}

.option-name {
    @apply block font-medium text-gray-900 dark:text-white;
}

.option-description {
    @apply block text-sm text-gray-600 dark:text-gray-400;
}

.access-control {
    @apply space-y-4;
}

.access-options {
    @apply space-y-3;
}

.access-option {
    @apply block;
}

.access-label {
    @apply flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 p-3 transition-all duration-200 hover:border-blue-300 dark:border-gray-700 dark:hover:border-blue-600;
}

.access-radio {
    @apply mt-1 border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700;
}

.access-content {
    @apply flex items-start gap-3;
}

.access-info {
    @apply space-y-1;
}

.access-name {
    @apply block font-medium text-gray-900 dark:text-white;
}

.access-description {
    @apply block text-sm text-gray-600 dark:text-gray-400;
}

.password-section {
    @apply mt-4 flex gap-3;
}

.password-label {
    @apply flex-1 space-y-2;
}

.password-input {
    @apply w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white;
}

.generate-password {
    @apply flex items-center rounded-md bg-gray-100 px-3 py-2 font-medium text-gray-700 transition-colors duration-200 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600;
}

.expiration-section {
    @apply space-y-4;
}

.expiration-options {
    @apply flex flex-wrap gap-4;
}

.expiration-option {
    @apply flex cursor-pointer items-center gap-2;
}

.expiration-radio {
    @apply border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700;
}

.expiration-text {
    @apply text-sm text-gray-700 dark:text-gray-300;
}

.recipients-section {
    @apply space-y-4;
}

.recipients-input {
    @apply flex gap-3;
}

.recipient-input {
    @apply flex-1 rounded-md border border-gray-300 bg-white px-3 py-2 text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white;
}

.add-recipient {
    @apply rounded-md bg-blue-600 px-3 py-2 text-white transition-colors duration-200 hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50;
}

.recipients-list {
    @apply space-y-2;
}

.recipient-item {
    @apply flex items-center gap-3 rounded-md bg-gray-50 p-2 dark:bg-gray-700;
}

.recipient-email {
    @apply flex-1 text-sm text-gray-900 dark:text-white;
}

.remove-recipient {
    @apply rounded p-1 text-gray-600 transition-colors duration-200 hover:bg-gray-200 hover:text-red-600 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-red-400;
}

.preview-settings {
    @apply space-y-4;
}

.settings-grid {
    @apply grid grid-cols-1 gap-4 md:grid-cols-2;
}

.setting-item {
    @apply space-y-2;
}

.setting-label {
    @apply block space-y-2 text-sm font-medium text-gray-700 dark:text-gray-300;
}

.setting-select {
    @apply w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white;
}

.setting-checkbox {
    @apply flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300;
}

.checkbox {
    @apply rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700;
}

.generated-links {
    @apply space-y-4;
}

.links-list {
    @apply space-y-3;
}

.link-item {
    @apply flex items-start justify-between rounded-lg border border-gray-200 p-4 dark:border-gray-700;
}

.link-info {
    @apply flex-1 space-y-2;
}

.link-header {
    @apply flex items-center gap-2;
}

.link-title {
    @apply font-medium text-gray-900 dark:text-white;
}

.link-status {
    @apply rounded px-2 py-1 text-xs font-medium;
}

.status-active {
    @apply bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200;
}

.status-expired {
    @apply bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200;
}

.status-revoked {
    @apply bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200;
}

.status-unknown {
    @apply bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200;
}

.link-url {
    @apply break-all font-mono text-sm text-blue-600 dark:text-blue-400;
}

.link-meta {
    @apply text-xs text-gray-600 dark:text-gray-400;
}

.link-actions {
    @apply flex gap-2;
}

.link-action {
    @apply rounded p-2 text-gray-600 transition-colors duration-200 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white;
}

.btn-primary {
    @apply flex items-center rounded-md bg-blue-600 px-4 py-2 font-medium text-white transition-colors duration-200 hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50;
}

.btn-secondary {
    @apply rounded-md bg-gray-100 px-4 py-2 font-medium text-gray-700 transition-colors duration-200 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600;
}
</style>





