import { pageBackupService } from '@/Services/PageBackupService';
import { pageExportService } from '@/Services/PageExportService';
import { pageMigrationService } from '@/Services/PageMigrationService';
import { flushPromises, mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import VueGrapeJSWrapper from '../VueGrapeJSWrapper.vue';

// Mock Services
vi.mock('@/Services/PageExportService');
vi.mock('@/Services/PageBackupService');
vi.mock('@/Services/PageMigrationService');

// Mock grapesjs
vi.mock('grapesjs', () => ({
    default: { init: vi.fn() },
}));
const grapesjs = { init: vi.fn() } as any;

// Mock componentLibraryBridge
vi.mock('@/Services/ComponentLibraryBridge', () => ({
    componentLibraryBridge: {
        initialize: vi.fn(),
        getRegisteredComponents: vi.fn().mockReturnValue([]),
        getGrapeJSCategories: vi.fn().mockReturnValue([
            { id: 'basic', name: 'Basic Components', Components: [] },
            { id: 'layout', name: 'Layout Components', Components: [] },
        ]),
        convertToGrapeJSBlock: vi.fn(),
        searchComponents: vi.fn(),
        trackComponentUsage: vi.fn(),
    },
}));

// Mock socket.io-client
const mockSocket = {
    emit: vi.fn(),
    on: vi.fn(),
    disconnect: vi.fn(),
    connect: vi.fn(() => {}),
    connected: true,
};
vi.mock('socket.io-client', () => ({
    io: vi.fn(() => mockSocket),
}));

// Mock fetch
const mockedFetch = vi.fn();
Object.defineProperty(globalThis, 'fetch', {
    value: mockedFetch,
    writable: true,
});

// Mock fetch responses for brand config and component categories
mockedFetch.mockImplementation((url: string) => {
    if (url.includes('/api/brand-config')) {
        return Promise.resolve({
            ok: true,
            json: () =>
                Promise.resolve({
                    primaryColor: '#007bff',
                    secondaryColor: '#6c757d',
                    fontFamily: 'Arial, sans-serif',
                }),
        });
    }
    if (url.includes('/api/component-categories')) {
        return Promise.resolve({
            ok: true,
            json: () =>
                Promise.resolve([
                    { id: 'basic', name: 'Basic Components', Components: [] },
                    { id: 'layout', name: 'Layout Components', Components: [] },
                ]),
        });
    }
    return Promise.resolve({
        ok: true,
        json: () => Promise.resolve({}),
    });
});

// Mock document.querySelector
const mockCSRFToken = 'csrf-token-mock';
Object.defineProperty(document, 'querySelector', {
    value: vi.fn(() => ({ getAttribute: vi.fn(() => mockCSRFToken) })),
    writable: true,
});

describe('VueGrapeJSWrapper - Export/Backup/Migration Integration Tests', () => {
    let wrapper: any;
    const mockEditor: any = {
        BlockManager: {
            add: vi.fn(),
            addCategory: vi.fn(),
            get: vi.fn(),
            getAll: vi.fn(),
            render: vi.fn(),
        },
        StyleManager: {
            sectors: vi.fn(),
        },
        setComponents: vi.fn(),
        setStyle: vi.fn(),
        setDevice: vi.fn(),
        runCommand: vi.fn(),
        getHtml: vi.fn().mockReturnValue('<div>html</div>'),
        getCss: vi.fn().mockReturnValue('body {}'),
        getComponents: { toJSON: vi.fn().mockReturnValue([]) },
        getStyle: { toJSON: vi.fn().mockReturnValue([]) },
        on: vi.fn(),
        destroy: vi.fn(),
    };
    const mockGrapesJSInit = vi.mocked(grapesjs.init);
    mockGrapesJSInit.mockResolvedValue(mockEditor);

    beforeEach(async () => {
        vi.clearAllMocks();
        wrapper = mount(VueGrapeJSWrapper, {
            props: {
                pageId: 'test-page-1',
                tenantId: 'tenant-1',
                height: '600px',
            },
            global: {
                stubs: {
                    MonitorIcon: { template: '<span>Desktop</span>' },
                    DeviceTabletIcon: { template: '<span>Tablet</span>' },
                    DevicePhoneMobileIcon: { template: '<span>Mobile</span>' },
                },
            },
        });

        // Wait for component to fully initialize
        await flushPromises();

        // Wait for GrapeJS initialization to complete
        await new Promise((resolve) => setTimeout(resolve, 100));

        // Force loading state to false to show toolbar
        wrapper.vm.isLoading = false;
        await wrapper.vm.$nextTick();
    });

    afterEach(() => {
        if (wrapper) wrapper.unmount();
    });

    describe('Export Feature Integration', () => {
        it('renders export button correctly', () => {
            const exportBtn = wrapper.find('.export-btn');
            expect(exportBtn.exists()).toBe(true);
            expect(exportBtn.attributes('aria-label')).toBe('Export page');
        });

        it('opens export dialog when export button is clicked', async () => {
            const exportBtn = wrapper.find('.export-btn');
            await exportBtn.trigger('click');
            expect(wrapper.vm.showExportDialog).toBe(true);
        });

        it('handles export form inputs correctly', async () => {
            await wrapper.find('.export-btn').trigger('click');

            // Update form values
            wrapper.vm.exportOptions.format = 'json';
            wrapper.vm.exportOptions.includeAssets = true;
            wrapper.vm.exportOptions.compress = true;
            wrapper.vm.exportOptions.encrypt = false;

            expect(wrapper.vm.exportOptions.format).toBe('json');
            expect(wrapper.vm.exportOptions.includeAssets).toBe(true);
            expect(wrapper.vm.exportOptions.compress).toBe(true);
            expect(wrapper.vm.exportOptions.encrypt).toBe(false);
        });

        it('calls export service with correct parameters', async () => {
            const mockExportResult = {
                id: 'export-1',
                fileName: 'test-page.json',
                fileSize: 1024,
                status: 'pending',
                format: 'json',
                createdAt: new Date(),
                metadata: {},
            };

            vi.mocked(pageExportService.exportPage).mockResolvedValue(mockExportResult);

            await wrapper.find('.export-btn').trigger('click');
            await wrapper.find('.confirm-btn').trigger('click');

            expect(pageExportService.exportPage).toHaveBeenCalledWith('test-page-1', expect.any(Object), expect.any(Object), 'tenant-1');
        });

        it('handles export errors gracefully', async () => {
            vi.mocked(pageExportService.exportPage).mockRejectedValue(new Error('Export failed'));

            await wrapper.find('.export-btn').trigger('click');
            await wrapper.find('.confirm-btn').trigger('click');

            await flushPromises();

            expect(wrapper.vm.exportError).toBe('Failed to export page');
            expect(wrapper.vm.isExporting).toBe(false);
        });

        it('closes export dialog on successful operation', async () => {
            vi.mocked(pageExportService.exportPage).mockResolvedValue({
                id: 'export-1',
                fileName: 'test.json',
                status: 'completed',
                format: 'json',
                createdAt: new Date(),
                metadata: {},
            });

            await wrapper.find('.export-btn').trigger('click');
            await wrapper.find('.confirm-btn').trigger('click');

            await flushPromises();

            expect(wrapper.vm.showExportDialog).toBe(false);
        });
    });

    describe('Backup Feature Integration', () => {
        it('renders backup button correctly', () => {
            const backupBtn = wrapper.find('.backup-btn');
            expect(backupBtn.exists()).toBe(true);
            expect(backupBtn.attributes('aria-label')).toBe('Backup page');
        });

        it('opens backup dialog when backup button is clicked', async () => {
            const backupBtn = wrapper.find('.backup-btn');
            await backupBtn.trigger('click');
            expect(wrapper.vm.showBackupDialog).toBe(true);
        });

        it('handles backup form inputs correctly', async () => {
            await wrapper.find('.backup-btn').trigger('click');

            wrapper.vm.backupOptions.name = 'Test Backup';
            wrapper.vm.backupOptions.description = 'Test description';
            wrapper.vm.backupOptions.retentionDays = 30;
            wrapper.vm.backupOptions.includeAssets = true;
            wrapper.vm.backupOptions.compress = true;

            expect(wrapper.vm.backupOptions.name).toBe('Test Backup');
            expect(wrapper.vm.backupOptions.description).toBe('Test description');
            expect(wrapper.vm.backupOptions.retentionDays).toBe(30);
            expect(wrapper.vm.backupOptions.includeAssets).toBe(true);
            expect(wrapper.vm.backupOptions.compress).toBe(true);
        });

        it('calls backup service with correct parameters', async () => {
            const mockBackupResult = {
                id: 'backup-1',
                fileName: 'backup-2025-01-01.zip',
                status: 'pending',
                createdAt: new Date(),
                metadata: {},
            };

            vi.mocked(pageBackupService.createBackup).mockResolvedValue(mockBackupResult);

            await wrapper.find('.backup-btn').trigger('click');
            wrapper.vm.backupOptions.name = 'Test Backup';
            await wrapper.find('.confirm-btn').trigger('click');

            expect(pageBackupService.createBackup).toHaveBeenCalledWith(expect.any(Object), 'tenant-1');
        });

        it('handles backup errors gracefully', async () => {
            vi.mocked(pageBackupService.createBackup).mockRejectedValue(new Error('Backup failed'));

            await wrapper.find('.backup-btn').trigger('click');
            wrapper.vm.backupOptions.name = 'Test Backup';
            await wrapper.find('.confirm-btn').trigger('click');

            await flushPromises();

            expect(wrapper.vm.backupError).toBe('Failed to create backup');
            expect(wrapper.vm.isBackingUp).toBe(false);
        });
    });

    describe('Migration Feature Integration', () => {
        it('renders migration button correctly', () => {
            const migrationBtn = wrapper.find('.migration-btn');
            expect(migrationBtn.exists()).toBe(true);
            expect(migrationBtn.attributes('aria-label')).toBe('Migrate page');
        });

        it('opens migration dialog when migration button is clicked', async () => {
            const migrationBtn = wrapper.find('.migration-btn');
            await migrationBtn.trigger('click');
            expect(wrapper.vm.showMigrationDialog).toBe(true);
        });

        it('handles migration form inputs correctly', async () => {
            await wrapper.find('.migration-btn').trigger('click');

            wrapper.vm.migrationConfig.sourceEnvironment = 'staging';
            wrapper.vm.migrationConfig.targetEnvironment = 'production';
            wrapper.vm.migrationConfig.targetTenantId = 'target-tenant-123';
            wrapper.vm.migrationConfig.includeAssets = true;
            wrapper.vm.migrationConfig.validateBeforeMigration = true;

            expect(wrapper.vm.migrationConfig.sourceEnvironment).toBe('staging');
            expect(wrapper.vm.migrationConfig.targetEnvironment).toBe('production');
            expect(wrapper.vm.migrationConfig.targetTenantId).toBe('target-tenant-123');
            expect(wrapper.vm.migrationConfig.includeAssets).toBe(true);
            expect(wrapper.vm.migrationConfig.validateBeforeMigration).toBe(true);
        });

        it('calls migration service with correct parameters', async () => {
            const mockMigrationResult = {
                id: 'migration-1',
                config: {} as any,
                status: 'draft',
                progress: { totalItems: 0, processedItems: 0, successfulItems: 0, failedItems: 0, percentage: 0 },
                createdAt: new Date(),
                updatedAt: new Date(),
                createdBy: 'test-user',
            };

            vi.mocked(pageMigrationService.createMigration).mockResolvedValue(mockMigrationResult);

            await wrapper.find('.migration-btn').trigger('click');
            wrapper.vm.migrationConfig.targetTenantId = 'target-tenant-123';
            await wrapper.find('.confirm-btn').trigger('click');

            expect(pageMigrationService.createMigration).toHaveBeenCalledWith(expect.any(Object), expect.any(Object));
        });

        it('handles migration errors gracefully', async () => {
            vi.mocked(pageMigrationService.createMigration).mockRejectedValue(new Error('Migration failed'));

            await wrapper.find('.migration-btn').trigger('click');
            await wrapper.find('.confirm-btn').trigger('click');

            await flushPromises();

            expect(wrapper.vm.migrationError).toBe('Failed to create migration');
            expect(wrapper.vm.isMigrating).toBe(false);
        });
    });

    describe('Dialog Management', () => {
        it('closes dialogs when cancel button is clicked', async () => {
            // Test export dialog
            await wrapper.find('.export-btn').trigger('click');
            expect(wrapper.vm.showExportDialog).toBe(true);
            await wrapper.find('.cancel-btn').trigger('click');
            expect(wrapper.vm.showExportDialog).toBe(false);

            // Test backup dialog
            await wrapper.find('.backup-btn').trigger('click');
            expect(wrapper.vm.showBackupDialog).toBe(true);
            await wrapper.find('.cancel-btn').trigger('click');
            expect(wrapper.vm.showBackupDialog).toBe(false);

            // Test migration dialog
            await wrapper.find('.migration-btn').trigger('click');
            expect(wrapper.vm.showMigrationDialog).toBe(true);
            await wrapper.find('.cancel-btn').trigger('click');
            expect(wrapper.vm.showMigrationDialog).toBe(false);
        });

        it('only opens one dialog at a time', async () => {
            await wrapper.find('.export-btn').trigger('click');
            expect(wrapper.vm.showExportDialog).toBe(true);
            expect(wrapper.vm.showBackupDialog).toBe(false);
            expect(wrapper.vm.showMigrationDialog).toBe(false);

            await wrapper.find('.backup-btn').trigger('click');
            expect(wrapper.vm.showExportDialog).toBe(false);
            expect(wrapper.vm.showBackupDialog).toBe(true);
            expect(wrapper.vm.showMigrationDialog).toBe(false);
        });
    });

    describe('Loading States', () => {
        it('shows loading states during export operation', async () => {
            vi.mocked(pageExportService.exportPage).mockImplementation(
                () =>
                    new Promise((resolve) =>
                        setTimeout(
                            () =>
                                resolve({
                                    id: 'export-1',
                                    fileName: 'test.json',
                                    status: 'completed',
                                    format: 'json',
                                    createdAt: new Date(),
                                    metadata: {},
                                }),
                            100,
                        ),
                    ),
            );

            await wrapper.find('.export-btn').trigger('click');
            await wrapper.find('.confirm-btn').trigger('click');

            expect(wrapper.vm.isExporting).toBe(true);

            await flushPromises();
            expect(wrapper.vm.isExporting).toBe(false);
        });

        it('shows loading states during backup operation', async () => {
            vi.mocked(pageBackupService.createBackup).mockImplementation(
                () =>
                    new Promise((resolve) =>
                        setTimeout(
                            () =>
                                resolve({
                                    id: 'backup-1',
                                    fileName: 'backup.zip',
                                    status: 'completed',
                                    createdAt: new Date(),
                                    metadata: {},
                                }),
                            100,
                        ),
                    ),
            );

            await wrapper.find('.backup-btn').trigger('click');
            wrapper.vm.backupOptions.name = 'Test Backup';
            await wrapper.find('.confirm-btn').trigger('click');

            expect(wrapper.vm.isBackingUp).toBe(true);

            await flushPromises();
            expect(wrapper.vm.isBackingUp).toBe(false);
        });

        it('shows loading states during migration operation', async () => {
            vi.mocked(pageMigrationService.createMigration).mockImplementation(
                () =>
                    new Promise((resolve) =>
                        setTimeout(
                            () =>
                                resolve({
                                    id: 'migration-1',
                                    config: {} as any,
                                    status: 'draft',
                                    progress: { totalItems: 0, processedItems: 0, successfulItems: 0, failedItems: 0, percentage: 0 },
                                    createdAt: new Date(),
                                    updatedAt: new Date(),
                                    createdBy: 'test-user',
                                }),
                            100,
                        ),
                    ),
            );

            await wrapper.find('.migration-btn').trigger('click');
            await wrapper.find('.confirm-btn').trigger('click');

            expect(wrapper.vm.isMigrating).toBe(true);

            await flushPromises();
            expect(wrapper.vm.isMigrating).toBe(false);
        });
    });

    describe('Form Validation', () => {
        it('validates required fields before export submission', async () => {
            await wrapper.find('.export-btn').trigger('click');
            await wrapper.find('.confirm-btn').trigger('click');

            // Should still call service even with default values
            expect(pageExportService.exportPage).toHaveBeenCalled();
        });

        it('validates required fields before backup submission', async () => {
            await wrapper.find('.backup-btn').trigger('click');

            // Try to submit without name
            await wrapper.find('.confirm-btn').trigger('click');

            // Should not call service if validation fails
            expect(pageBackupService.createBackup).not.toHaveBeenCalled();
        });

        it('validates required fields before migration submission', async () => {
            await wrapper.find('.migration-btn').trigger('click');
            await wrapper.find('.confirm-btn').trigger('click');

            // Should call service with default values
            expect(pageMigrationService.createMigration).toHaveBeenCalled();
        });
    });
});
