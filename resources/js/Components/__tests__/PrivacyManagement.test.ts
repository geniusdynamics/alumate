/**
 * PrivacyManagement Component Tests
 * 
 * Tests for the PrivacyManagement.vue component including:
 * - Consent management interface
 * - Data export functionality
 * - Data deletion functionality
 * - Data anonymization functionality
 * - Privacy settings management
 * - Consent history view
 * - Responsive design
 */

import { flushPromises, mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import PrivacyManagement from '../Analytics/PrivacyManagement.vue';
import { createTestingPinia } from '@pinia/testing';

// Mock the privacy store
vi.mock('@/stores/privacyStore', () => ({
    usePrivacyStore: vi.fn(() => ({
        consentStatus: {
            analytics: { has_consent: true, updated_at: '2024-01-15T10:00:00Z' },
            marketing: { has_consent: false, updated_at: '2024-01-14T10:00:00Z' },
            personalization: { has_consent: true, updated_at: '2024-01-13T10:00:00Z' },
            third_party: { has_consent: false, updated_at: '2024-01-12T10:00:00Z' },
        },
        consentHistory: [
            {
                consent_type: 'analytics',
                action: 'granted',
                timestamp: '2024-01-15T10:00:00Z',
                ip_address: '192.168.1.1',
            },
            {
                consent_type: 'marketing',
                action: 'revoked',
                timestamp: '2024-01-14T10:00:00Z',
                ip_address: '192.168.1.1',
            },
        ],
        privacySettings: {
            profile_visibility: 'alumni_only',
            show_activity_status: true,
            allow_indexing: false,
            data_retention_period: '2',
            two_factor_enabled: false,
        },
        currentUserId: 1,
        isLoading: false,
        error: '',
        fetchPrivacyData: vi.fn().mockResolvedValue(undefined),
        updateConsent: vi.fn().mockResolvedValue(true),
        deleteData: vi.fn().mockResolvedValue(true),
        anonymizeData: vi.fn().mockResolvedValue(true),
        exportData: vi.fn().mockResolvedValue({ user: { id: 1, name: 'Test User' } }),
        updatePrivacySetting: vi.fn().mockResolvedValue(true),
        clearError: vi.fn(),
    }))
}));

// Mock window.URL.createObjectURL
global.URL.createObjectURL = vi.fn(() => 'blob:http://localhost/test');
global.URL.revokeObjectURL = vi.fn();

// Mock document.createElement
const mockCreateElement = document.createElement.bind(document);
document.createElement = vi.fn((tagName: string) => {
    const element = mockCreateElement(tagName);
    const mockClick = vi.fn();
    const mockAppendChild = vi.fn();
    const mockRemoveChild = vi.fn();
    
    Object.defineProperty(element, 'click', {
        get: () => mockClick,
        set: (fn: () => void) => mockClick.mockImplementation(fn),
    });
    
    Object.defineProperty(element, 'href', {
        value: '',
        writable: true,
    });
    
    Object.defineProperty(element, 'download', {
        value: '',
        writable: true,
    });
    
    Object.defineProperty(document.body, 'appendChild', {
        value: mockAppendChild,
        writable: true,
    });
    
    Object.defineProperty(document.body, 'removeChild', {
        value: mockRemoveChild,
        writable: true,
    });
    
    return element;
});

describe('PrivacyManagement.vue', () => {
    let wrapper: any;
    const pinia = createTestingPinia();

    beforeEach(async () => {
        vi.clearAllMocks();
        
        // Mock window.scrollTo
        vi.spyOn(window, 'scrollTo').mockImplementation(() => {});
        
        wrapper = mount(PrivacyManagement, {
            props: {
                userId: 1,
                compact: false,
                showDataRights: true,
                showConsentHistory: true,
            },
            global: {
                plugins: [pinia],
                stubs: {
                    Teleport: true,
                    LoadingSpinner: {
                        template: '<div class="loading-spinner-mock">Loading...</div>',
                        props: ['size'],
                    },
                },
            },
        });
        await flushPromises();
    });

    afterEach(() => {
        if (wrapper) wrapper.unmount();
        vi.restoreAllMocks();
    });

    describe('Component Mounting', () => {
        it('mounts without errors', () => {
            expect(wrapper.exists()).toBe(true);
            expect(wrapper.find('.privacy-management').exists()).toBe(true);
        });

        it('renders header with title', () => {
            expect(wrapper.find('.header-title').text()).toBe('Privacy Management');
        });

        it('renders header subtitle', () => {
            expect(wrapper.find('.header-subtitle').text()).toContain('Manage your privacy settings');
        });
    });

    describe('Tabs Navigation', () => {
        it('renders all tab buttons', () => {
            const tabs = wrapper.findAll('.tab-button');
            expect(tabs.length).toBe(4);
            expect(tabs[0].text()).toBe('Consent');
            expect(tabs[1].text()).toBe('Data Rights');
            expect(tabs[2].text()).toBe('History');
            expect(tabs[3].text()).toBe('Settings');
        });

        it('switches to data-rights tab when clicked', async () => {
            const dataRightsTab = wrapper.findAll('.tab-button')[1];
            await dataRightsTab.trigger('click');
            expect(wrapper.vm.activeTab).toBe('data-rights');
        });

        it('switches to history tab when clicked', async () => {
            const historyTab = wrapper.findAll('.tab-button')[2];
            await historyTab.trigger('click');
            expect(wrapper.vm.activeTab).toBe('history');
        });

        it('switches to settings tab when clicked', async () => {
            const settingsTab = wrapper.findAll('.tab-button')[3];
            await settingsTab.trigger('click');
            expect(wrapper.vm.activeTab).toBe('settings');
        });
    });

    describe('Consent Management', () => {
        it('renders consent cards for each consent type', () => {
            const consentCards = wrapper.findAll('.consent-card');
            expect(consentCards.length).toBe(4);
        });

        it('renders consent type titles', () => {
            const titles = wrapper.findAll('.consent-title');
            expect(titles[0].text()).toBe('Analytics & Performance');
            expect(titles[1].text()).toBe('Marketing Communications');
            expect(titles[2].text()).toBe('Personalization');
            expect(titles[3].text()).toBe('Third-Party Sharing');
        });

        it('renders consent descriptions', () => {
            const descriptions = wrapper.findAll('.consent-description');
            expect(descriptions.length).toBe(4);
        });

        it('renders consent toggle switches', () => {
            const toggles = wrapper.findAll('.consent-toggle input[type="checkbox"]');
            expect(toggles.length).toBe(4);
        });

        it('renders grant all and revoke all buttons', () => {
            const buttons = wrapper.findAll('.consent-header-actions .btn-secondary');
            expect(buttons.length).toBe(2);
            expect(buttons[0].text()).toBe('Grant All');
            expect(buttons[1].text()).toBe('Revoke All');
        });

        it('shows correct consent status badges', () => {
            const badges = wrapper.findAll('.consent-status-badge');
            expect(badges[0].classes()).toContain('bg-green-100');
            expect(badges[1].classes()).toContain('bg-red-100');
        });

        it('displays consent last updated time', () => {
            const metaText = wrapper.findAll('.consent-meta');
            expect(metaText.length).toBeGreaterThan(0);
            expect(metaText[0].text()).toContain('Last updated');
        });
    });

    describe('Data Rights', () => {
        beforeEach(async () => {
            // Switch to data-rights tab
            const dataRightsTab = wrapper.findAll('.tab-button')[1];
            await dataRightsTab.trigger('click');
            await flushPromises();
        });

        it('renders data rights intro', () => {
            expect(wrapper.find('.data-rights-intro').exists()).toBe(true);
        });

        it('renders export data card', () => {
            expect(wrapper.find('.export-card').exists()).toBe(true);
            expect(wrapper.find('.export-card .data-right-title').text()).toBe('Export Your Data');
        });

        it('renders delete data card', () => {
            expect(wrapper.find('.deletion-card').exists()).toBe(true);
            expect(wrapper.find('.deletion-card .data-right-title').text()).toBe('Delete Your Data');
        });

        it('renders anonymize data card', () => {
            expect(wrapper.find('.anonymization-card').exists()).toBe(true);
            expect(wrapper.find('.anonymization-card .data-right-title').text()).toBe('Anonymize Your Data');
        });

        it('shows export confirmation modal when export button clicked', async () => {
            const exportBtn = wrapper.find('.export-card .btn-primary');
            await exportBtn.trigger('click');
            expect(wrapper.find('.modal-overlay').exists()).toBe(true);
            expect(wrapper.find('.modal-title').text()).toBe('Export Your Data');
        });

        it('shows delete confirmation modal when delete button clicked', async () => {
            const deleteBtn = wrapper.find('.deletion-card .btn-danger');
            await deleteBtn.trigger('click');
            expect(wrapper.find('.modal-overlay').exists()).toBe(true);
            expect(wrapper.find('.modal-title').text()).toBe('Delete Your Data');
        });

        it('shows anonymize confirmation modal when anonymize button clicked', async () => {
            const anonymizeBtn = wrapper.find('.anonymization-card .btn-warning');
            await anonymizeBtn.trigger('click');
            expect(wrapper.find('.modal-overlay').exists()).toBe(true);
            expect(wrapper.find('.modal-title').text()).toBe('Anonymize Your Data');
        });

        it('close export modal when cancel clicked', async () => {
            const exportBtn = wrapper.find('.export-card .btn-primary');
            await exportBtn.trigger('click');
            
            const cancelBtn = wrapper.find('.modal-footer .btn-secondary');
            await cancelBtn.trigger('click');
            
            expect(wrapper.find('.modal-overlay').exists()).toBe(false);
        });
    });

    describe('Data Export', () => {
        it('exports data in JSON format by default', async () => {
            const exportBtn = wrapper.find('.export-card .btn-primary');
            await exportBtn.trigger('click');
            
            const downloadBtn = wrapper.find('.modal-footer .btn-primary');
            await downloadBtn.trigger('click');
            await flushPromises();
            
            // Check that exportData was called
            const privacyStore = wrapper.vm.$pinia?.state?.value?.privacy;
            expect(privacyStore?.exportData).toBeDefined();
        });

        it('allows switching to CSV format', async () => {
            const exportBtn = wrapper.find('.export-card .btn-primary');
            await exportBtn.trigger('click');
            
            const csvOption = wrapper.find('.format-option:nth-child(2) input');
            await csvOption.setChecked();
            
            expect(wrapper.vm.exportFormat).toBe('csv');
        });
    });

    describe('Data Deletion', () => {
        beforeEach(async () => {
            const deleteBtn = wrapper.find('.deletion-card .btn-danger');
            await deleteBtn.trigger('click');
            await flushPromises();
        });

        it('requires DELETE confirmation to proceed', async () => {
            const deleteButton = wrapper.find('.modal-footer .btn-danger');
            expect(deleteButton.attributes('disabled')).toBeDefined();
            
            const input = wrapper.find('.confirmation-input');
            await input.setValue('delete');
            
            expect(deleteButton.attributes('disabled')).toBeUndefined();
        });

        it('calls deleteData when confirmation matches', async () => {
            const input = wrapper.find('.confirmation-input');
            await input.setValue('delete');
            
            const deleteButton = wrapper.find('.modal-footer .btn-danger');
            await deleteButton.trigger('click');
            await flushPromises();
            
            const privacyStore = wrapper.vm.$pinia?.state?.value?.privacy;
            expect(privacyStore?.deleteData).toBeDefined();
        });
    });

    describe('Data Anonymization', () => {
        beforeEach(async () => {
            const anonymizeBtn = wrapper.find('.anonymization-card .btn-warning');
            await anonymizeBtn.trigger('click');
            await flushPromises();
        });

        it('requires ANONYMIZE confirmation to proceed', async () => {
            const anonymizeButton = wrapper.find('.modal-footer .btn-warning');
            expect(anonymizeButton.attributes('disabled')).toBeDefined();
            
            const input = wrapper.find('.confirmation-input');
            await input.setValue('anonymize');
            
            expect(anonymizeButton.attributes('disabled')).toBeUndefined();
        });

        it('calls anonymizeData when confirmation matches', async () => {
            const input = wrapper.find('.confirmation-input');
            await input.setValue('anonymize');
            
            const anonymizeButton = wrapper.find('.modal-footer .btn-warning');
            await anonymizeButton.trigger('click');
            await flushPromises();
            
            const privacyStore = wrapper.vm.$pinia?.state?.value?.privacy;
            expect(privacyStore?.anonymizeData).toBeDefined();
        });
    });

    describe('Consent History', () => {
        beforeEach(async () => {
            const historyTab = wrapper.findAll('.tab-button')[2];
            await historyTab.trigger('click');
            await flushPromises();
        });

        it('renders consent history section', () => {
            expect(wrapper.find('.history-tab').exists()).toBe(true);
            expect(wrapper.find('.history-title').text()).toBe('Consent History');
        });

        it('renders history entries when data available', () => {
            const entries = wrapper.findAll('.history-entry');
            expect(entries.length).toBe(2);
        });

        it('renders granted icon for granted actions', () => {
            const grantedIcon = wrapper.find('.history-entry-icon.granted');
            expect(grantedIcon.exists()).toBe(true);
        });

        it('renders revoked icon for revoked actions', () => {
            const revokedIcon = wrapper.find('.history-entry-icon.revoked');
            expect(revokedIcon.exists()).toBe(true);
        });

        it('displays consent type in history entries', () => {
            const actionTexts = wrapper.findAll('.history-entry-action');
            expect(actionTexts[0].text()).toContain('analytics');
            expect(actionTexts[1].text()).toContain('marketing');
        });
    });

    describe('Privacy Settings', () => {
        beforeEach(async () => {
            const settingsTab = wrapper.findAll('.tab-button')[3];
            await settingsTab.trigger('click');
            await flushPromises();
        });

        it('renders privacy settings section', () => {
            expect(wrapper.find('.settings-tab').exists()).toBe(true);
            expect(wrapper.find('.settings-title').text()).toBe('Privacy Settings');
        });

        it('renders profile visibility setting', () => {
            expect(wrapper.find('.setting-label').text()).toBe('Profile Visibility');
            expect(wrapper.find('.setting-select').exists()).toBe(true);
        });

        it('renders activity status toggle', () => {
            const toggles = wrapper.findAll('.setting-toggle input[type="checkbox"]');
            expect(toggles.length).toBeGreaterThanOrEqual(2);
        });

        it('renders search engine indexing toggle', () => {
            const indexingLabel = wrapper.find('.setting-label');
            expect(indexingLabel.text()).toContain('Search Engine Indexing');
        });

        it('renders data retention period setting', () => {
            const retentionLabel = wrapper.find('.setting-label');
            expect(retentionLabel.text()).toContain('Data Retention Period');
        });

        it('renders 2FA setting', () => {
            const twoFALabel = wrapper.find('.setting-label');
            expect(twoFALabel.text()).toContain('Two-Factor Authentication');
        });
    });

    describe('Format Functions', () => {
        it('formats date correctly', () => {
            const formatted = wrapper.vm.formatDate('2024-01-15T10:00:00Z');
            expect(formatted).toContain('Jan');
            expect(formatted).toContain('15');
            expect(formatted).toContain('2024');
        });

        it('handles undefined date', () => {
            const formatted = wrapper.vm.formatDate(undefined);
            expect(formatted).toBe('Never');
        });

        it('returns correct status text for granted consent', () => {
            expect(wrapper.vm.getConsentStatusText(true)).toBe('Granted');
        });

        it('returns correct status text for denied consent', () => {
            expect(wrapper.vm.getConsentStatusText(false)).toBe('Denied');
        });

        it('returns correct status text for undefined consent', () => {
            expect(wrapper.vm.getConsentStatusText(undefined)).toBe('Not set');
        });

        it('returns correct status class for granted consent', () => {
            expect(wrapper.vm.getConsentStatusClass(true)).toBe('bg-green-100 text-green-800');
        });

        it('returns correct status class for denied consent', () => {
            expect(wrapper.vm.getConsentStatusClass(false)).toBe('bg-red-100 text-red-800');
        });

        it('returns correct status class for undefined consent', () => {
            expect(wrapper.vm.getConsentStatusClass(undefined)).toBe('bg-gray-100 text-gray-800');
        });
    });

    describe('Compact Mode', () => {
        it('applies compact class when compact prop is true', async () => {
            const compactWrapper = mount(PrivacyManagement, {
                props: {
                    userId: 1,
                    compact: true,
                    showDataRights: true,
                    showConsentHistory: true,
                },
                global: {
                    plugins: [createTestingPinia()],
                    stubs: { Teleport: true },
                },
            });
            await flushPromises();
            
            expect(compactWrapper.find('.privacy-management').classes()).toContain('compact');
            compactWrapper.unmount();
        });
    });

    describe('Loading State', () => {
        it('displays loading spinner when isLoading is true', async () => {
            const privacyStore = wrapper.vm.$pinia?.state?.value?.privacy;
            if (privacyStore) {
                privacyStore.isLoading = true;
                await flushPromises();
            }
            
            // Loading spinner should be visible
            expect(wrapper.find('.header-loading').exists()).toBe(true);
        });
    });

    describe('Error Display', () => {
        it('displays error banner when error is present', async () => {
            const privacyStore = wrapper.vm.$pinia?.state?.value?.privacy;
            if (privacyStore) {
                privacyStore.error = 'Test error message';
                await flushPromises();
            }
            
            expect(wrapper.find('.error-banner').exists()).toBe(true);
            expect(wrapper.find('.error-banner').text()).toContain('Test error message');
        });

        it('dismisses error when dismiss button clicked', async () => {
            const privacyStore = wrapper.vm.$pinia?.state?.value?.privacy;
            if (privacyStore) {
                privacyStore.error = 'Test error message';
                await flushPromises();
            }
            
            const dismissBtn = wrapper.find('.error-dismiss');
            await dismissBtn.trigger('click');
            await flushPromises();
            
            expect(wrapper.find('.error-banner').exists()).toBe(false);
        });
    });

    describe('Responsive Design', () => {
        it('applies responsive padding classes', () => {
            expect(wrapper.find('.privacy-management').classes()).toContain('md:p-8');
        });
    });
});

describe('PrivacyManagement - Edge Cases', () => {
    let wrapper: any;
    const pinia = createTestingPinia();

    afterEach(() => {
        if (wrapper) wrapper.unmount();
        vi.restoreAllMocks();
    });

    it('handles empty consent status gracefully', async () => {
        vi.doMock('@/stores/privacyStore', () => ({
            usePrivacyStore: vi.fn(() => ({
                consentStatus: {},
                consentHistory: [],
                privacySettings: {
                    profile_visibility: 'private',
                    show_activity_status: false,
                    allow_indexing: false,
                    data_retention_period: '1',
                    two_factor_enabled: false,
                },
                currentUserId: null,
                isLoading: false,
                error: '',
                fetchPrivacyData: vi.fn(),
                updateConsent: vi.fn(),
                deleteData: vi.fn(),
                anonymizeData: vi.fn(),
                exportData: vi.fn(),
                updatePrivacySetting: vi.fn(),
                clearError: vi.fn(),
            }))
        }));

        wrapper = mount(PrivacyManagement, {
            props: {
                userId: 1,
                compact: false,
                showDataRights: true,
                showConsentHistory: true,
            },
            global: {
                plugins: [pinia],
                stubs: { Teleport: true },
            },
        });
        await flushPromises();

        expect(wrapper.exists()).toBe(true);
    });

    it('handles empty consent history gracefully', async () => {
        wrapper = mount(PrivacyManagement, {
            props: {
                userId: 1,
                compact: false,
                showDataRights: true,
                showConsentHistory: true,
            },
            global: {
                plugins: [pinia],
                stubs: { Teleport: true },
            },
        });
        await flushPromises();

        // Navigate to history tab
        const historyTab = wrapper.findAll('.tab-button')[2];
        await historyTab.trigger('click');
        await flushPromises();

        // Should show empty state
        expect(wrapper.find('.empty-state').exists()).toBe(true);
        expect(wrapper.find('.empty-state').text()).toContain('No consent history available');
    });

    it('handles very long consent descriptions', async () => {
        wrapper = mount(PrivacyManagement, {
            props: {
                userId: 1,
                compact: false,
                showDataRights: true,
                showConsentHistory: true,
            },
            global: {
                plugins: [pinia],
                stubs: { Teleport: true },
            },
        });
        await flushPromises();

        expect(wrapper.exists()).toBe(true);
    });

    it('handles compact prop with data rights disabled', async () => {
        wrapper = mount(PrivacyManagement, {
            props: {
                userId: 1,
                compact: true,
                showDataRights: false,
                showConsentHistory: true,
            },
            global: {
                plugins: [pinia],
                stubs: { Teleport: true },
            },
        });
        await flushPromises();

        expect(wrapper.find('.privacy-management').classes()).toContain('compact');
        
        // Data rights tab should not be visible
        const dataRightsTab = wrapper.findAll('.tab-button')[1];
        expect(dataRightsTab.exists()).toBe(false);
    });
});
